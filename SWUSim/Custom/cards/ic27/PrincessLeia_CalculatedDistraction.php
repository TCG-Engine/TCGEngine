<?php
// IC27_159
// Cost 5 - Princess Leia - Calculated Distraction - [Cunning,Heroism] - Unit (Ground) 5/5 (unique)
//   Traits: Rebel, Official
// Text: When Played/On Attack: Exhaust up to 2 units in the same arena with combined cost 4 or less.
//
// One closure on both triggers. Two optional picks in a row rather than the SWUQueueBudgetMultiChoose modal: that
// modal caps the budget but can neither cap the COUNT at 2 nor tie the picks to one arena, so a player could
// build a selection the server would then have to silently trim. The first pick is over every READY unit of
// printed cost 4 or less (an exhausted unit is never offered — same rule as _SWUBudgetExhaustWeights); the
// second is over the ready units in the FIRST pick's arena that fit what is left. "Same arena" ties the two
// picks to each other, not to Leia. "Units" has no qualifier: friendly, enemy, leader units, far seats. Cost is
// the printed cost (a token unit costs 0). The first pick rides the second decision's handler param as a
// UniqueID (request boundary), and both are exhausted together once the picks are made.
// Tests: SWUSim/Tests/Cases/ic27/PrincessLeia_CalculatedDistraction.md

// Ready units costing $budget or less, optionally in one arena ('Ground'/'Space') and excluding a UniqueID.
function _SWUIc27159Pool(int $player, ?string $arena, int $budget, int $excludeUID = 0): array {
    global $playerID; $playerID = $player;
    $out = [];
    foreach (SWUAllUnits(null, $arena) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->Status ?? 0) !== 1) continue;
        if ($excludeUID > 0 && intval($o->UniqueID ?? 0) === $excludeUID) continue;
        if (intval(CardCost($o->CardID ?? '')) > $budget) continue;
        $out[] = $mz;
    }
    return $out;
}

$whenPlayedAbilities["IC27_159:0"] =
$onAttackAbilities["IC27_159:0"] = function($player, $mzID) {
    $pool = _SWUIc27159Pool(intval($player), null, 4);
    SWUQueueMayChooseTarget(intval($player), $pool, "Exhaust_up_to_2_units_in_the_same_arena_with_combined_cost_4_or_less?",
        "Choose_a_unit_to_exhaust_(combined_cost_4_or_less)", "IC27_159#0");
};

$customDQHandlers["IC27_159#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $first = GetZoneObject($lastDecision);
    if (SWUObjGone($first)) return;
    $uid   = intval($first->UniqueID ?? 0);
    $arena = (strpos($lastDecision, 'Space') !== false) ? 'Space' : 'Ground';
    $pool  = _SWUIc27159Pool(intval($player), $arena, 4 - intval(CardCost($first->CardID ?? '')), $uid);
    if (empty($pool)) { SWUExhaustUnitObj(intval($player), $first, $lastDecision); return; }
    SWUQueueMayChooseTarget(intval($player), $pool, "Exhaust_a_second_unit?",
        "Choose_a_second_unit_in_the_same_arena_to_exhaust", "IC27_159#1|{$uid}");
};

$customDQHandlers["IC27_159#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $firstMz = SWUFindMzByUID(intval($parts[0] ?? 0));
    $picks = [];
    if ($firstMz !== null && $firstMz !== '') $picks[] = $firstMz;
    if (!SWUDecisionDeclined($lastDecision)) $picks[] = $lastDecision;
    foreach ($picks as $mz) {
        $o = GetZoneObject($mz);
        if (!SWUObjGone($o)) SWUExhaustUnitObj(intval($player), $o, $mz);
    }
};
