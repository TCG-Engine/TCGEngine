<?php
// Part 49 'creditengine' (2026-10-13) — from Ninin's 5 human games on Krennic Blue Naboo (force-fam-HMW-predictions/
// director-krennic_law_blue_naboo, 4-1; human vs human, online) read against 420 bot games
// on the same list (.claude/tmp/knaboo-20261013/: base/ = the sweep, full/ = 120 fully traced games, kn_profile.py = the profile).
// Owner: "look for at least 10 bot features we can ship immediately and turn them on". Each feature names the measured gap it corrects.
// The helpers live here; each feature hooks in at its call site (BotResourcing / BotFallback), under its own switch.
require_once __DIR__ . '/BotFeatures.php';

// The Credit-sac leader on its FRONT side: "Action [Exhaust, defeat a friendly unit]: Create a Credit token" (LAW_008 Director Krennic).
// The engine closes at deploy (BotFallback.php "BANKED CREDITS"), so a deployed one is null.
function SWUBotCreditSacLeaderFront(int $seat): ?object {
    foreach (GetLeader($seat) as $l) {
        if ($l === null || !empty($l->removed) || in_array(strval($l->Deployed ?? 'false'), ['true', '1'], true)) continue;
        if (preg_match('/defeat a friendly unit\]:\s*Create a Credit token/i', strval(CardText(strval($l->CardID ?? ''))))) return $l;
    }
    return null;
}

// A cheap FODDER body for that engine: a unit costing 2 or less whose When Defeated pays back (a draw, a heal, a body) or whose When
// Played draws once another unit has entered (HMW_103 Disposable B1). ASH_116 Ant Droid, LAW_097 Imperial Door Technician, JTL_033
// Onyx Squadron Brute, HMW_103 Disposable B1. Not JTL_032 Director Krennic (no When Defeated: it is the discount ENGINE, below).
function SWUBotIsCreditFodder(string $cid): bool {
    if (!str_contains(strval(CardType($cid)), 'Unit') || intval(CardCost($cid)) > 2) return false;
    return (bool)preg_match('/When Defeated:[^.]*\b(draw|heal|create)\b|When Played: If another friendly unit entered play this phase[^.]*\bdraw\b/i',
                            strval(CardText($cid)));
}

// A unit that comes back as a RESOURCE when defeated: LAW_159 Expendable Mercenary, "When Defeated: You may resource this unit".
function SWUBotIsResourceLoopUnit(string $cid): bool {
    return str_contains(strval(CardType($cid)), 'Unit') && (bool)preg_match('/When Defeated:[^.]*\bresource this unit\b/i', strval(CardText($cid)));
}

// ── 'fodderkeep' + 'mercres' (resourcing) ────────────────────────────────────────────────────────────────────────────────────────────
// The OPENING pick banked the engine's fodder: Ant Droid 26/30 times it was in the opening hand, Imperial Door Technician 18/24,
// Disposable B1 28/50, Expendable Mercenary 12/26 (full/ traces). Control's "castable soon" rule gives every card costing <= 4 a keep of
// 100 + cost, so the CHEAPEST go first — and round 1 had no body to sacrifice. Owner: 3 of 5 games opened with two 1-drops (Ant Droid /
// Door Technician + B1) and sacrificed one in round 1; Mercenary was played and sacrificed in round 2 in 4 of 5 (ramp + a Credit back).
// So while the Credit engine is open and resources are few, the first SWU_BOT_FODDER_KEEP fodder bodies and the first resource-loop unit
// are kept off the resource pick. The bonus is added to the keep value; it never forces a pick when only fodder is left.
const SWU_BOT_FODDER_KEEP = 2;
const SWU_BOT_FODDER_KEEP_BONUS = 150.0;
const SWU_BOT_FODDER_KEEP_UNTIL_RES = 5;   // the engine runs every early round; by 6 resources the deck is casting its bombs
function SWUBotFodderKeepIndices(int $seat): array {
    if (SWUBotCreditSacLeaderFront($seat) === null || SWUResourceCount($seat) >= SWU_BOT_FODDER_KEEP_UNTIL_RES) return [];
    $keep = []; $fodder = 0; $loop = false;
    foreach (GetHand($seat) as $i => $o) {
        if ($o === null || !empty($o->removed)) continue;
        $cid = strval($o->CardID ?? '');
        if (SWUBotFeatureOn('fodderkeep') && $fodder < SWU_BOT_FODDER_KEEP && SWUBotIsCreditFodder($cid)) { $keep[] = $i; $fodder++; continue; }
        if (SWUBotFeatureOn('mercres') && !$loop && SWUBotIsResourceLoopUnit($cid)) { $keep[] = $i; $loop = true; }
    }
    return $keep;
}

// ── 'mercplay' (play value) ─────────────────────────────────────────────────────────────────────────────────────────────────────────
// Mercenary was NEVER cast in 88 of 120 traced games (in round 2: 2 of 120); the owner's round-2 line in 4 of 5 is Credit → Mercenary →
// Krennic sacrifices it → the Credit back AND Mercenary in the resources (one permanent resource). Its play value saw only a 4-cost 3/3:
// the 'resource-ramp' tag carries no weight. With the Action ready this round the loop is live, so the play carries the resource it
// becomes — a 4-cost card's floor again (W['develop'] x cost), the same scale the play floor prices a card at.
function SWUBotMercLoopValue(int $seat, string $cid, array $W): float {
    if (!SWUBotFeatureOn('mercplay') || !SWUBotIsResourceLoopUnit($cid)) return 0.0;
    $l = SWUBotCreditSacLeaderFront($seat);
    if ($l === null || !in_array(strval($l->Ready ?? 'false'), ['true', '1'], true)) return 0.0;   // the Action is still to use this round
    return floatval($W['develop'] ?? 0.3) * intval(CardCost($cid));
}

// ── 'healfodder' (sacrifice pricing) ────────────────────────────────────────────────────────────────────────────────────────────────
// A "When Defeated: Heal N damage from (your|a) base" is worth what it can heal: min(N, damage on my base) / N of the payback allowance.
// 175 of 406 heal-unit sacrifices in the sweep healed NOTHING (no HEAL line — the base was undamaged); the owner's 5 all landed.
function SWUBotHealWdShare(int $seat, string $wd): ?float {
    if (!SWUBotFeatureOn('healfodder') || !preg_match('/\bheal (\d+) damage from (your|a) base\b/i', $wd, $m)
        || preg_match('/\bdraw (a card|\d+)|\bcreate \d+/i', $wd)) return null;
    $n = max(1, intval($m[1]));
    $dmg = intval((GetBase($seat)[0] ?? null)->Damage ?? 0);
    return min(1.0, $dmg / $n);
}

// ── 'krennicengine' (sacrifice pricing) ─────────────────────────────────────────────────────────────────────────────────────────────
// JTL_032 Director Krennic: "The first unit you play each round that has a 'When Defeated' ability costs 1 resource less." The engine
// was sacrificed to the Credit Action 46 times and taken by Chimaera's friendly pick 22 times in the sweep; the owner never did either.
// Its discount is a resource every round I still play When Defeated units, so it is priced as one more unit of value while my hand
// holds such a unit (the next round's discount is then real).
const SWU_BOT_KRENNIC_ENGINE_VALUE = 1.5;
function SWUBotDiscountEngineValue(int $seat, string $cid): float {
    if (!SWUBotFeatureOn('krennicengine') || !preg_match('/The first unit you play each round that has a "When Defeated" ability costs \d+ resources? less/i', strval(CardText($cid)))) return 0.0;
    foreach (GetHand($seat) as $o) {
        if ($o === null || !empty($o->removed)) continue;
        $h = strval($o->CardID ?? '');
        if (str_contains(strval(CardType($h)), 'Unit') && stripos(strval(CardText($h)), 'When Defeated') !== false) return SWU_BOT_KRENNIC_ENGINE_VALUE;
    }
    return 0.0;
}

// ── 'sachealwait' (the Credit Action's timing) ──────────────────────────────────────────────────────────────────────────────────────
// Which unit the Credit Action would take: the cheapest by sacrifice price (the pick the "Defeat_a_friendly_unit_to_create_a_Credit"
// prompt makes). Null when there is none.
function SWUBotCreditSacPick(int $seat): ?array {
    $best = null; $bestCost = INF;
    foreach (SWUBotUnits($seat) as $v) {
        if (!empty($v['isLeader'])) continue;
        $c = _SWUBotSacrificeCostByValue($v);
        if ($c < $bestCost) { $bestCost = $c; $best = $v; }
    }
    return $best;
}
// The Action WAITS — yields to every other worthwhile action this round — while the unit it would take heals my base on defeat and my
// base has less damage than that heal, and an enemy unit is still ready to hit my base this round (after it attacks, the heal lands).
// The owner sacrificed Door Technician / Onyx after taking damage every time (Cham R3/R5, Talzin R3, Thrawn R4); the bot's 43% waste
// was the Action used at the top of the round on an undamaged base (vs Ahsoka Yellow R2, 0 damage). Never past the round: the cap only
// orders it below the other actions, it stays above PASS.
function SWUBotCreditSacShouldWait(int $seat): bool {
    if (!SWUBotFeatureOn('sachealwait') || SWUBotCreditSacLeaderFront($seat) === null) return false;
    $pick = SWUBotCreditSacPick($seat);
    if ($pick === null || !preg_match('/When Defeated:[^.]*\bheal (\d+) damage from (your|a) base\b/i', strval(CardText($pick['cardID'])), $m)) return false;
    if (intval((GetBase($seat)[0] ?? null)->Damage ?? 0) >= intval($m[1])) return false;
    foreach (SWUBotEnemyUnits($seat) as $e) if ($e['ready'] && intval($e['attackPower']) > 0) return true;
    return false;
}

// ── 'strikeleader' (the deploy strike's target) ─────────────────────────────────────────────────────────────────────────────────────
// "A friendly unit deals damage equal to its power to an enemy unit" (LAW_008's When Deployed reuses SOR_127#1): a killable enemy
// LEADER unit is the pick. Owner: 3 of 5 deploys struck the leader the round it came down (Talzin 7, Cham 7, Hemlock 7); the bot passed a
// killable leader for another unit 3 of 14 times (a 6/6 Pre Vizsla over Doctor Hemlock 3/6). A leader is the opponent's engine and it
// comes back to deploy no more (its Epic Action is spent), so it carries half a kill more.
function SWUBotStrikeLeaderPremium(int $seat, string $mz, int $amount, string $head, array $W): float {
    if (!SWUBotFeatureOn('strikeleader') || $head !== 'SOR_127#1' || !SWUBotIsEnemyMz($seat, $mz)) return 0.0;
    $v = SWUBotViewForMz($seat, $mz);
    if ($v === null || empty($v['isLeader']) || intval($v['shields']) > 0 || $amount < intval($v['remaining'])) return 0.0;
    _SWUBotFired('strikeleader', $seat);
    return 0.5 * floatval($W['kill'] ?? 1.0) * SWUBotUnitValue($v);
}

// ── 'foddermull' (mulligan) ─────────────────────────────────────────────────────────────────────────────────────────────────────────
// Owner, Krennic Blue (2026-10-06): the keep is "the Krennic unit + 1-drop"; Ninin mulliganed 2 of 5 here and both kept hands opened on
// a round-1 body (Ant Droid + B1; the Krennic unit). The bot mulliganed 0 of 120. A Credit-sac hand with no unit costing 2 or less has
// nothing to play and sacrifice in round 1 (~13% of 6-card hands for this list) — it goes back.
function SWUBotFodderMulligan(int $seat): bool {
    if (!SWUBotFeatureOn('foddermull') || SWUBotCreditSacLeaderFront($seat) === null) return false;
    foreach (GetHand($seat) as $o) {
        if ($o === null || !empty($o->removed)) continue;
        $cid = strval($o->CardID ?? '');
        if (str_contains(strval(CardType($cid)), 'Unit') && intval(CardCost($cid)) <= 2) return false;
    }
    _SWUBotFired('foddermull', $seat);
    return true;
}

// ── 'powerdamage' (targeting) ───────────────────────────────────────────────────────────────────────────────────────────────────────
// ASH_102 Ravager: "When you play a unit: You may have it deal damage equal to its power to a unit in the same arena." The prompt
// ("Choose_a_unit_in_the_same_arena", continuation ASH_102#0|<dealer uid>) read as NEUTRAL — the hostile text reader matched only
// "deal N damage" — so the FIRST listed candidate was taken: the bot's OWN unit 55 of 90 times (an Onyx Squadron Brute's 2 into its own
// Ravager with two enemy ships legal). The amount is the dealer's power, carried by uid in the continuation.
function SWUBotPowerDamageAmount(string $head, string $next): int {
    if (!SWUBotFeatureOn('powerdamage') || !preg_match('/^([A-Z0-9]+_[A-Z0-9]+)#\d+\|(\d+)/', $next, $m)) return 0;
    if (!preg_match('/deals? damage equal to its power to/i', strval(CardText($m[1])))) return 0;
    $mz = function_exists('SWUFindMzByUID') ? SWUFindMzByUID(intval($m[2])) : null;
    $o = $mz === null ? null : GetZoneObject($mz);
    return ($o === null || !empty($o->removed)) ? 0 : max(0, intval(ObjectCurrentPower($o)));
}
