<?php
// TWI_206
// Cost 1 - Independent Senator - [Cunning] - Power 0 - HP 4
// Text: Action [2 resources, Exhaust]: Exhaust a unit with 4 or less power.

// TWI_206 Independent Senator — "Action [2 resources, Exhaust]: Exhaust a unit with 4 or less power."
$unitActionCostKind["TWI_206"] = 'exhaust';

$unitActionResourceCosts["TWI_206"] = 2;

$unitAbilities["TWI_206"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1 && intval(ObjectCurrentPower($o)) <= 4) $targets[] = $mz;
        }
    }
    if (empty($targets)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Exhaust_a_unit_with_4_or_less_power", "EXHAUST_UNIT");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SWU_AFTER_ACTION", 1);
};
