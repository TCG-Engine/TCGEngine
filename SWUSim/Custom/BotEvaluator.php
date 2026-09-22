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
    if (function_exists('SWUBotProposalOn') && SWUBotProposalOn('sentinelpot')) return _SWUBotBasePotentialThroughSentinels($seat, $defSeat, $readyOnly);
    $guarded = _SWUBotSentinelArenas($defSeat);
    $total = 0;
    foreach (SWUBotUnits($seat) as $v) {
        if ($readyOnly && !$v['ready']) continue;
        if (!$v['saboteur'] && ($guarded[$v['arena']] ?? false)) continue;
        $total += $v['attackPower'];
    }
    return $total;
}

// PROPOSAL 'sentinelpot' (default OFF). Loss mining 2026-09-19: the model above treats ONE Sentinel as blocking its
// whole arena, but a Sentinel only redirects attacks while it is in play (CR 7.5.11) — once it is defeated the rest
// of the arena's attackers reach the base. Seen in a lost game: at 19 HP, facing 7+7+2 on the ground, rule 4 played a
// 3-HP Sentinel "to break lethal" instead of Lost and Forgotten, and took 15 next round.
// Per arena: Saboteurs ignore Sentinels and always count. The rest are spent, smallest first, on the defender's
// Sentinels, easiest first — one attack per Shield (a Shield prevents a whole instance, CR 3.7.6), then enough power
// to cover remaining HP (no Overwhelm carry). Attackers left over reach the base. Greedy, deterministic.
function _SWUBotBasePotentialThroughSentinels(int $seat, int $defSeat, bool $readyOnly): int {
    $total = 0;
    $defenders = SWUBotUnits($defSeat);
    foreach (['Ground', 'Space'] as $arena) {
        $att = [];
        foreach (SWUBotUnits($seat) as $v) {
            if ($v['arena'] !== $arena || ($readyOnly && !$v['ready'])) continue;
            if ($v['saboteur']) { $total += $v['attackPower']; continue; }
            $att[] = intval($v['attackPower']);
        }
        sort($att);
        $sent = array_values(array_filter($defenders, fn($d) => $d['arena'] === $arena && $d['sentinel']));
        usort($sent, fn($a, $b) => [$a['shields'], $a['remaining']] <=> [$b['shields'], $b['remaining']]);
        foreach ($sent as $d) {
            for ($i = 0; $i < intval($d['shields']) && !empty($att); $i++) array_shift($att);   // pop the Shields
            $need = max(1, intval($d['remaining']));
            while ($need > 0 && !empty($att)) $need -= array_shift($att);
            if ($need > 0) { $att = []; break; }   // this Sentinel survives: nothing else in the arena gets through
        }
        $total += array_sum($att);
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
    $own = (!$v['saboteur'] && ($guarded[$v['arena']] ?? false)) ? 0 : intval($v['attackPower']);
    return $own + ((function_exists('SWUBotProposalOn') && SWUBotProposalOn('aurathreat')) ? _SWUBotAuraGrantedPower($v) : 0);
}

// PROPOSAL 'aurathreat' (default OFF) — owner ruling 4 (2026-09-22): a unit's threat includes the damage it GRANTS
// its allies. Victor Leader ("each other friendly space unit gets +1/+1") with four other ships threatens its own 2
// plus 4 more — removing it mitigates 6, where the 5/6 Stolen AT-Hauler mitigates 5. Read from printed text:
// "Each other friendly [space|ground ]unit gets +N/…" × the other friendly units it reaches.
function _SWUBotAuraGrantedPower(array $v): int {
    static $parsed = [];
    $cid = strval($v['cardID']);
    if (!array_key_exists($cid, $parsed)) {
        $parsed[$cid] = preg_match('/Each other friendly (space |ground )?unit gets \+(\d+)\/[+-]?\d+/i', strval(CardText($cid)), $m)
            ? [trim(strtolower($m[1])), intval($m[2])] : null;
    }
    if ($parsed[$cid] === null) return 0;
    [$arena, $n] = $parsed[$cid];
    $others = 0;
    foreach (SWUBotUnits(intval($v['controller'])) as $u) {
        if ($u['uid'] === $v['uid']) continue;
        if ($arena === '' || strtolower($u['arena']) === $arena) $others++;
    }
    return $n * $others;
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
    if (function_exists('SWUBotProposalOn') && (SWUBotProposalOn('unitvalue') || SWUBotProposalOn('unitvalue2'))) return SWUBotUnitValueV2($v);
    // 'aurathreat': what the unit grants its allies is worth removing too — one point of granted power per point.
    if (function_exists('SWUBotProposalOn') && SWUBotProposalOn('aurathreat')) return _SWUBotUnitValueV1($v) + _SWUBotAuraGrantedPower($v);
    return _SWUBotUnitValueV1($v);
}

function _SWUBotUnitValueV1(array $v): float {
    $cost = floatval($v['cost']);
    // A token has no printed cost; value it by its body (feature 'targeting', diagnosis 2026-09-14: a TIE token was
    // worth 0, so a ping that could defeat it hit a 4/5 instead).
    if ($cost <= 0 && function_exists('SWUBotFeatureOn') && SWUBotFeatureOn('targeting')) $cost = (floatval($v['power']) + floatval($v['hp'])) / 2.0;
    return $cost + floatval($v['upgrades']) + 0.5 * floatval($v['shields']);
}

// PROPOSAL 'unitvalue' (default OFF) — THE VALUE ALGORITHM. Owner ruling 2026-09-19: "look at stats first (power
// being a little more valuable than hp), whether or not it is a saboteur or sentinel, whether or not it has upgrades
// like shields or equipment … also consider when defeated effects. For example, Loth Cat when defeated exhausts a
// ground unit, so it is more valuable to kill it when it's my last ground unit attacking into it."
// Same SCALE as the cost-based value it replaces (so every weight that multiplies it keeps its meaning): the stat
// weights are the game's own pricing, fitted over the pool's 107 vanilla units — cost ≈ 0.584·power + 0.409·HP − 0.68
// (rmse 0.49) — so power IS worth ~1.4× HP, as ruled. Keyword premiums are the mean cost residual of units whose only
// text is that keyword (Sentinel +1.05 n=45, Restore +0.92, Grit +0.84, Raid +0.81, Overwhelm +0.31, Saboteur +0.28);
// Ambush/Hidden/Shielded are play-time only and are NOT counted — the Shield TOKENS a unit actually has are (+0.67
// each, the Shielded residual). Current power (upgrades included) and REMAINING HP. Each non-Shield upgrade adds
// 0.5: its stats are already in power/HP, but the card dies with the unit.
// When Defeated: what the ability would do for its controller RIGHT NOW is subtracted (the kill is worth less), or
// added when it hurts its controller (Savage Opress). See _SWUBotWhenDefeatedPayout.
const SWU_BOT_UV_POWER = 0.584;
const SWU_BOT_UV_HP = 0.409;
const SWU_BOT_UV_BASE = -0.676;
const SWU_BOT_UV_SHIELD = 0.67;
const SWU_BOT_UV_UPGRADE = 0.5;
const SWU_BOT_UV_KEYWORDS = ['sentinel' => 1.05, 'restore' => 0.92, 'grit' => 0.84, 'raid' => 0.81, 'overwhelm' => 0.31, 'saboteur' => 0.28];

function _SWUBotUnitStatsValue(array $v): float {
    $val = max(0.5, SWU_BOT_UV_POWER * floatval($v['power']) + SWU_BOT_UV_HP * max(0, floatval($v['remaining'])) + SWU_BOT_UV_BASE);
    foreach (['sentinel', 'grit', 'overwhelm', 'saboteur'] as $k) { if (!empty($v[$k])) $val += SWU_BOT_UV_KEYWORDS[$k]; }
    if (intval($v['attackPower']) > intval($v['power'])) $val += SWU_BOT_UV_KEYWORDS['raid'];
    if (isset($v['obj']) && function_exists('HasKeyword_Restore') && HasKeyword_Restore($v['obj'])) $val += SWU_BOT_UV_KEYWORDS['restore'];
    return $val + SWU_BOT_UV_SHIELD * intval($v['shields']) + SWU_BOT_UV_UPGRADE * max(0, intval($v['upgrades']) - intval($v['shields']));
}

function SWUBotUnitValueV2(array $v): float {
    return max(0.2, _SWUBotUnitStatsValue($v) + _SWUBotAbilityPremium($v) - _SWUBotWhenDefeatedPayout($v));
}

// PROPOSAL 'unitvalue2' (default OFF) — 'unitvalue' RESCALED. Measured 2026-09-19: 'unitvalue' changed half of all
// games and trended DOWN (−50 / 3,780, p .076). Hypothesis: pricing a unit by its BODY alone loses what the designers
// charged for its TEXT, so every ability unit is undervalued against the cost-based scale the kill/loss weights were
// tuned on (a 4-cost 3/3 with a strong ability priced 2.3 instead of 4), which shifts every trade and every removal
// target. The premium restores exactly that, from PRINTED stats: cost − the fitted price of its body, never negative.
// Keywords are already priced above, so a unit whose only text is keywords gets ~0 here; it is the non-keyword text
// that this pays for. 'unitvalue' keeps its measured behaviour (premium 0) so its −50 stays reproducible.
function _SWUBotAbilityPremium(array $v): float {
    if (!SWUBotProposalOn('unitvalue2')) return 0.0;
    static $cache = [];
    $cid = strval($v['cardID']);
    if (!array_key_exists($cid, $cache)) {
        $cost = floatval(CardCost($cid));
        $body = SWU_BOT_UV_POWER * floatval(CardPower($cid)) + SWU_BOT_UV_HP * floatval(CardHp($cid)) + SWU_BOT_UV_BASE;
        $cache[$cid] = $cost > 0 ? max(0.0, $cost - $body) : 0.0;   // a token (no printed cost) gets nothing
    }
    return $cache[$cid];
}

// What $v's printed When Defeated ability would give its CONTROLLER against the current board, in unit-value units
// (≈ resources). Positive = it pays them back (killing it is worth less); negative = it hurts them. The clause is
// sorted into categories by its printed words (148 distinct clauses in the pool, 2026-09-19); the board decides
// how much each is worth now. Unrecognised clauses get a small default.
//   exhaust a unit  — worth ~1 only while the OTHER side has 2+ ready units in that arena: with one ready unit left,
//                     that unit is almost always the attacker, exhausted by its own attack (owner's Loth-Cat case).
//   damage a unit   — the best other-side unit it would defeat (its stats value), else 0.3 per point.
//   damage a base   — 0.5 per point; "your base" is the controller's own — negative.
//   -N/-N           — as damage N.   tokens: 1 each.   draw: 1 per card.   heal: 0.3 per point.
//   comes back      — return to hand / resource it / play it again: 1.5.   ready a unit: 0.8.
//   give a token    — 0.6.   defeat a unit: 1.5.   the opponent gains (Credits, a resource): −0.5.
function _SWUBotWhenDefeatedPayout(array $v): float {
    static $clauses = [];
    $cid = strval($v['cardID']);
    if (!array_key_exists($cid, $clauses)) {
        $clauses[$cid] = preg_match('/When (?:Played ?\/ ?)?Defeated:\s*([^\n]*)/i', strval(CardText($cid)), $m) ? $m[1] : '';
    }
    $t = $clauses[$cid];
    if ($t === '') return 0.0;
    $other = SWUBotOpponent(intval($v['controller']));
    $arena = preg_match('/\bspace unit/i', $t) ? 'Space' : (preg_match('/\bground unit/i', $t) ? 'Ground' : '');
    $theirs = array_values(array_filter(SWUBotUnits($other), fn($u) => $arena === '' || $u['arena'] === $arena));
    $pay = 0.0; $hit = false;
    if (preg_match('/\bexhaust (a|an|each|up to \w+)\b[^.]*unit/i', $t)) {
        $hit = true;
        $pay += count(array_filter($theirs, fn($u) => $u['ready'])) >= 2 ? 1.0 : 0.0;
    }
    $n = null;
    if (preg_match('/deal (\d+) damage (divided )?[^.]*\b(unit|units)\b/i', $t, $m) && !preg_match('/damage to (your|a|each)[^.]*base/i', $t)) $n = intval($m[1]);
    if (preg_match("/damage equal to this unit's power/i", $t)) $n = intval($v['power']);
    if (preg_match('/[-–](\d+)\/[-–]\d+/u', $t, $m)) $n = intval($m[1]);
    if ($n !== null) {
        $hit = true;
        $best = 0.0;
        foreach ($theirs as $u) { if ($u['remaining'] <= $n && $u['shields'] == 0) $best = max($best, _SWUBotUnitStatsValue($u)); }
        $pay += $best > 0 ? $best : 0.3 * $n;
    }
    if (preg_match('/deal (\d+) (indirect )?damage to (a player|each opponent|an opponent|the opponent|a base|each enemy base)/i', $t, $m)) { $hit = true; $pay += 0.5 * intval($m[1]); }
    if (preg_match('/deal (\d+) damage to your base/i', $t, $m)) { $hit = true; $pay -= 0.5 * intval($m[1]); }
    if (preg_match('/\bcreate (a|an|one|two|three|\d+)?\b/i', $t, $m) && !preg_match('/(opponent|each player) creates/i', $t)) {
        $hit = true;
        $words = ['' => 1, 'a' => 1, 'an' => 1, 'one' => 1, 'two' => 2, 'three' => 3];
        $pay += floatval($words[strtolower($m[1] ?? '')] ?? intval($m[1] ?? 1));
    }
    if (preg_match('/(opponent|each player) creates|opponent may ready a resource/i', $t)) { $hit = true; $pay -= 0.5; }
    if (preg_match('/\bdraw (a card|(\d+) cards?)/i', $t, $m)) { $hit = true; $pay += isset($m[2]) && $m[2] !== '' ? intval($m[2]) : 1.0; }
    if (preg_match('/\bheal (up to )?(\d+)/i', $t, $m)) { $hit = true; $pay += 0.3 * intval($m[2]); }
    if (preg_match('/return[^.]*to (its owner\'s|your|their) hand|resource this unit|into play as a resource|play (this unit|it|him|her|that unit)[^.]*from/i', $t)) { $hit = true; $pay += 1.5; }
    if (preg_match('/\bready (a|another)\b[^.]*unit/i', $t)) { $hit = true; $pay += 0.8; }
    if (preg_match('/give (a|an|\d+)?[^.]*(Experience|Shield|Advantage) token/i', $t)) { $hit = true; $pay += 0.6; }
    if (preg_match('/\bdefeat (a|an)\b/i', $t)) { $hit = true; $pay += 1.5; }
    return $hit ? $pay : 0.4;
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
