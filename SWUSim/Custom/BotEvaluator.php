<?php
// Board reads for the heuristic bot stack (and, later, the RL state key). See
// docs/superpowers/specs/2026-09-13-swusim-rl-bots-design.md — Section 2 ("Normal's clock test", layer-2
// rules 2 and 3) and Section 3 (the clock-based state key).
//
// PURE READS: nothing here mutates the game or touches the RNG counter (the no-op hash depends on that —
// see SWUSim/BotHeuristic.php's header).
//
// Deliberate approximations, all in the SAFE direction — they make a confident rule fire LESS, never more:
//   • On Attack / On Defense / "while attacking" abilities other than Raid are ignored.
//   • Overwhelm excess is not counted toward lethal.
//   • Any card-text route to a Sentinel counts as a route (SWUBotOpponentCanGetSentinel).
// Bot Practice is 2-seat by spec scope, so "the opponent" is the other seat.

const SWU_BOT_NO_CLOCK = 99;   // "no reachable damage" — larger than any real clock

function SWUBotOpponent(int $seat): int { return $seat === 1 ? 2 : 1; }

// A flat, read-only view of one unit. $arena ('Ground'|'Space') is passed by callers that know it; otherwise
// it is read from the object's Location (the zone name, e.g. "GroundArena" — ZoneClasses' mzID builder
// uses it). Subcards go through GetUpgradesOnUnit(), which normalises the array-vs-object shapes and drops
// removed and captive subcards.
function SWUBotUnitView($obj, string $arena = ''): array {
    $raid = function_exists('GetKeyword_Raid_Value') ? intval(GetKeyword_Raid_Value($obj) ?? 0) : 0;
    $power = intval(ObjectCurrentPower($obj));
    $hp    = intval(ObjectCurrentHP($obj));
    $shields = 0; $upgrades = 0;
    foreach (GetUpgradesOnUnit($obj) as $s) {
        $upgrades++;
        if (strval($s->CardID ?? '') === 'SOR_T02') $shields++;   // Shield token
    }
    if ($arena === '') $arena = (stripos(strval($obj->Location ?? ''), 'Space') !== false) ? 'Space' : 'Ground';
    return [
        'obj' => $obj, 'uid' => intval($obj->UniqueID ?? 0), 'cardID' => strval($obj->CardID ?? ''),
        'controller' => intval($obj->Controller ?? 0),
        'arena' => $arena,
        'power' => $power, 'attackPower' => $power + $raid,   // Raid applies only while attacking (CR 7.5.8)
        'hp' => $hp, 'remaining' => $hp - intval($obj->Damage ?? 0),
        'ready' => intval($obj->Status ?? 0) === 1,            // Status: 1 = ready, 0 = exhausted
        'sentinel' => (bool)HasKeyword_Sentinel($obj), 'saboteur' => (bool)HasKeyword_Saboteur($obj),
        'overwhelm' => (bool)HasKeyword_Overwhelm($obj), 'grit' => (bool)HasKeyword_Grit($obj),
        'shields' => $shields, 'upgrades' => $upgrades,
        'cost' => intval(CardCost(strval($obj->CardID ?? ''))),
        'isLeader' => function_exists('IsLeaderUnit') && IsLeaderUnit($obj),
    ];
}

function SWUBotUnits(int $seat): array {
    $out = [];
    foreach (['Ground', 'Space'] as $arena) {
        foreach (GetUnitsInArena($seat, $arena) as $u) $out[] = SWUBotUnitView($u, $arena);
    }
    return $out;
}

// An mzID in $seat's frame ("myGroundArena-0", "theirSpaceArena-1") → its view, or null if it is gone.
function SWUBotViewForMz(int $seat, string $mz): ?array {
    global $playerID;
    $saved = $playerID; $playerID = $seat;
    $o = GetZoneObject($mz);
    $playerID = $saved;
    if ($o === null || !is_object($o) || !empty($o->removed)) return null;
    return SWUBotUnitView($o, str_contains($mz, 'SpaceArena') ? 'Space' : (str_contains($mz, 'GroundArena') ? 'Ground' : ''));
}

// The arenas where $defSeat has a Sentinel — readiness ignored: an exhausted Sentinel still guards (CR 7.5.11).
// Reads only the keyword (the same HasKeyword_Sentinel() the unit view uses), not a full view per unit: a full
// view per enemy unit per attacker made SWUBotClock() quadratic — ~27 ms a call on a late-game board, which
// rule 4's lookahead leaves pay twice each (sweep run 3, 2026-09-13).
function _SWUBotSentinelArenas(int $defSeat): array {
    $out = ['Ground' => false, 'Space' => false];
    foreach (['Ground', 'Space'] as $arena) {
        foreach (GetUnitsInArena($defSeat, $arena) as $u) { if (HasKeyword_Sentinel($u)) { $out[$arena] = true; break; } }
    }
    return $out;
}

function SWUBotArenaHasSentinel(int $defSeat, string $arena): bool {
    return _SWUBotSentinelArenas($defSeat)[$arena] ?? false;
}

// Base damage $seat's units could deal to $defSeat's base: every unit (or only ready ones) whose arena has
// no enemy Sentinel, plus Saboteurs anywhere (CR 6.3.2b, 7.5.10).
function SWUBotBasePotential(int $seat, int $defSeat, bool $readyOnly): int {
    $guarded = _SWUBotSentinelArenas($defSeat);
    $total = 0;
    foreach (SWUBotUnits($seat) as $v) {
        if ($readyOnly && !$v['ready']) continue;
        if (!$v['saboteur'] && ($guarded[$v['arena']] ?? false)) continue;
        $total += $v['attackPower'];
    }
    return $total;
}

// ── THREAT = base damage a unit can deal ─────────────────────────────────────────────────────────────
// Owner ruling 2026-09-18: judge removal and wipes by the BASE DAMAGE THEY PREVENT, not by what the target
// costs — "if it's not really considered a bomb, it can still use removal if that would be the best way to
// mitigate damage to base", and "paying 7 to only wipe Boba Fett on a 4+ cost ship is the only play to mitigate
// 8+ damage". Same Sentinel/Saboteur rules as SWUBotBasePotential below (CR 6.3.2b, 7.5.10): a unit facing my
// Sentinel in its arena cannot reach my base, unless it has Saboteur.
function SWUBotUnitBaseThreat(int $defSeat, array $v): int {
    $guarded = _SWUBotSentinelArenas($defSeat);
    return (!$v['saboteur'] && ($guarded[$v['arena']] ?? false)) ? 0 : intval($v['attackPower']);
}

// Rounds for $seat's board to reduce $defSeat's base to 0. Counts exhausted units: everything readies at
// the regroup (CR 5.5.1d).
function SWUBotClock(int $seat, int $defSeat): int {
    $pot = SWUBotBasePotential($seat, $defSeat, false);
    if ($pot <= 0) return SWU_BOT_NO_CLOCK;
    return max(1, intdiv(SWUBaseRemainingHp($defSeat) + $pot - 1, $pot));
}

// THE DAMAGE BUDGET (proposal 'dmgbudget', BotFeatures.php). Owner ruling 2026-09-18: "control wants to minimize
// damage to below 50-60% of their base total by the 6R/7R turn. if they keep it below 40% then they are performing
// really well." Round N carries N+1 resources (2 starting + 1 per regroup, CR 5.4), so "the 6R/7R turn" is round
// 5-6. A linear pace of 10% of base HP per round reaches 50% at round 5 and is capped at 55% — the middle of the
// owner's 50-60% band — from round 6 on, so a seat at 70% on round 8 is still over budget.
const SWU_BOT_DMG_BUDGET_PER_ROUND = 0.10;
const SWU_BOT_DMG_BUDGET_CAP = 0.55;

// Fraction of $seat's base total already taken as damage, 0..1.
function SWUBotBaseDamageFraction(int $seat): float {
    $b = GetBase($seat);
    if (empty($b) || !empty($b[0]->removed)) return 0.0;
    $hp = intval(CardHp($b[0]->CardID));
    return $hp > 0 ? min(1.0, intval($b[0]->Damage ?? 0) / $hp) : 0.0;
}

// Is $seat taking damage faster than the owner's pace for this round?
function SWUBotOverDamageBudget(int $seat): bool {
    $round = max(1, intval(GetTurnNumber()));
    $budget = min(SWU_BOT_DMG_BUDGET_CAP, SWU_BOT_DMG_BUDGET_PER_ROUND * $round);
    return SWUBotBaseDamageFraction($seat) > $budget;
}

function SWUBotLethalNow(int $seat, int $defSeat): bool {
    return SWUBotBasePotential($seat, $defSeat, true) >= SWUBaseRemainingHp($defSeat);
}

function SWUBotLethalNextRound(int $seat, int $defSeat): bool {
    return SWUBotBasePotential($seat, $defSeat, false) >= SWUBaseRemainingHp($defSeat);
}

// Normal's clock test (spec Section 2): race when my clock is 3 or less AND faster than theirs; on a tie
// the initiative holder swings first, so they win the race. The counter reads "P{n}_UNCLAIMED" or
// "P{n}_CLAIMED" (SWUTakeInitiative); either way P{n} holds it.
function SWUBotIsRacing(int $seat, int $defSeat): bool {
    $mine = SWUBotClock($seat, $defSeat);
    $theirs = SWUBotClock($defSeat, $seat);
    if ($mine > 3) return false;
    if ($mine !== $theirs) return $mine < $theirs;
    return str_starts_with(strval(GetInitiativeCounter() ?? ''), 'P' . $seat . '_');
}

// Rule 5's "stabilises" (spec Section 2): after the wipe the opponent's clock is at least 3 rounds AND longer
// than before. SWU_BOT_NO_CLOCK (no reachable damage) is the longest clock there is.
function SWUBotStabilises(int $clockBefore, int $clockAfter): bool {
    return $clockAfter >= 3 && $clockAfter > $clockBefore;
}

// Combat arithmetic for $att attacking $def (CR 6.3.4-5): simultaneous damage, a Shield prevents one
// instance (CR 3.7.6), Saboteur defeats the defender's Shields On Attack (CR 7.5.10), Grit's bonus from
// NEW damage does not apply this combat (CR 7.5.6c) — ObjectCurrentPower already counts existing damage.
function SWUBotCombatOutcome(array $att, array $def): string {
    $defShielded = $def['shields'] > 0 && !$att['saboteur'];
    $attShielded = $att['shields'] > 0;
    $kills = !$defShielded && $att['attackPower'] >= $def['remaining'];
    $dies  = !$attShielded && $def['power'] >= $att['remaining'];
    if ($kills && !$dies) return 'kill-survive';
    if ($kills && $dies)  return 'trade';
    if (!$kills && !$dies) return 'bounce';
    return 'die';
}

// True when $att has Overwhelm and its hit defeats $def, so excess damage reaches the base (CR 7.5.7;
// a Shield stops both the kill and the excess, 7.5.7e).
function SWUBotOverwhelmKills(array $att, array $def): bool {
    if (!$att['overwhelm']) return false;
    $defShielded = $def['shields'] > 0 && !$att['saboteur'];
    return !$defShielded && $att['attackPower'] >= $def['remaining'];
}

// What a unit is worth to remove or lose: its printed cost plus what dies with it — upgrades and tokens
// are defeated when the unit leaves play (CR 1.5.5d, 3.6.11). The guide "removal value counts everything
// that goes with the unit".
function SWUBotUnitValue(array $v): float {
    $cost = floatval($v['cost']);
    // A token has no printed cost; value it by its body (feature 'targeting', diagnosis 2026-09-14: a TIE token was
    // worth 0, so a ping that could defeat it hit a 4/5 instead).
    if ($cost <= 0 && function_exists('SWUBotFeatureOn') && SWUBotFeatureOn('targeting')) $cost = (floatval($v['power']) + floatval($v['hp'])) / 2.0;
    return $cost + floatval($v['upgrades']) + 0.5 * floatval($v['shields']);
}

// Could $oppSeat get a Sentinel into play during the rest of this round? Rule 3's guard. Conservative:
// any payment capacity at all counts (their hand is hidden), and so does any leader or unit whose text
// could produce a Sentinel for free — an undeployed leader with Epic Action left whose deployed side has
// Sentinel, an undeployed leader whose front text mentions Sentinel, or a unit with an Action mentioning it.
function SWUBotOpponentCanGetSentinel(int $oppSeat): bool {
    if (SWUTotalPaymentCapacity($oppSeat) > 0) return true;
    $truthy = fn($v) => !empty($v) && strval($v) !== 'false';
    foreach (GetLeader($oppSeat) as $l) {
        if ($l === null || !empty($l->removed)) continue;
        if ($truthy($l->Deployed ?? null)) continue;
        $cid = strval($l->CardID ?? '');
        if (!$truthy($l->EpicActionUsed ?? null) && stripos(strval(CardDeployText($cid) ?? ''), 'Sentinel') !== false) return true;
        if (stripos(strval(CardText($cid) ?? ''), 'Sentinel') !== false) return true;
    }
    foreach (SWUBotUnits($oppSeat) as $v) {
        $t = strval(CardText($v['cardID']) ?? '');
        if (stripos($t, 'Action') !== false && stripos($t, 'Sentinel') !== false) return true;
    }
    return false;
}
