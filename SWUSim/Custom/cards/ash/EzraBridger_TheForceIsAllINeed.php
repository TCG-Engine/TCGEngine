<?php
// ASH_209
// Cost 6 - Ezra Bridger - The Force is All I Need - [Cunning,Heroism] - Power 6 - HP 6
// Text: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack.) / On Attack: If this unit is upgraded, you may give a unit -3/-0 for this phase.

// ASH_209 Ezra Bridger — On Attack: if this unit is upgraded, you may give a unit -3/-0 for this phase.
$onAttackAbilities["ASH_209:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self) || !_SWUIsUpgraded($self)) return;
    $tg = SWUAllUnits();
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Give_a_unit_-3/-0_this_phase?", "Choose_a_unit", "APPLY_PHASE_DEBUFF|3|0|ASH_209");
};
