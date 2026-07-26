<?php
// TS26_81
// Cost 2 - Mislead - [Cunning]
// Text: Give a Shield token to a unit. / Give a unit -3/-0 for this phase.

// TS26_81 Mislead — shield the first chosen unit, then debuff a chosen unit -3/-0 this phase.
$customDQHandlers["TS26_81#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && str_contains($lastDecision, '-')) DoGiveShieldToken(intval($player), $lastDecision);
    $tg = SWUAllUnits();
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Give_a_unit_-3/-0_for_this_phase", "APPLY_PHASE_DEBUFF|3|0|TS26_81");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TS26_81:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $tg = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    );
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Give_a_Shield_token_to_a_unit", "TS26_81#0");
};
