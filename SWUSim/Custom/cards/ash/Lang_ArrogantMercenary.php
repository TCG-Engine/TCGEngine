<?php
// ASH_123
// Cost 3 - Lang - Arrogant Mercenary - [Command] - Power 2 - HP 5
// Text: Action [Exhaust]: This unit deals damage equal to his power to a ground unit.

// ASH_123 Lang — Action [Exhaust]: this unit deals damage equal to his power to a ground unit.
$unitAbilities["ASH_123"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $pow  = $self ? intval(ObjectCurrentPower($self)) : 0;
    $tg = SWUAllUnits(null, GroundArena);
    if (empty($tg) || $pow <= 0) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $tg, "Deal_{$pow}_to_a_ground_unit", "DEAL_UNIT_DAMAGE|{$pow}");
    SWUQueueAfterAction($player);
};
