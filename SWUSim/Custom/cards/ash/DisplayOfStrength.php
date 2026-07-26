<?php
// ASH_136
// Cost 2 - Display of Strength - [Command]
// Text: Give a unit +3/+3 for this phase.

$whenPlayedAbilities["ASH_136:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $tg = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    );
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Give_a_unit_+3/+3_this_phase", "APPLY_PHASE_BUFF|3|3|ASH_136");
};
