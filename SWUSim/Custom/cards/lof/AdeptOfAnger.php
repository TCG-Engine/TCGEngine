<?php
// LOF_178
// Cost 1 - Adept of Anger - [Cunning,Villainy] - Power 1 - HP 3
// Text: Action [Exhaust, use the Force (lose your Force token)]: Exhaust a unit.

// LOF_178 Adept of Anger — Action [Exhaust, use the Force]: exhaust a unit.
$unitAbilities["LOF_178"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    UseTheForce(intval($player));
    $targets = array_values(SWUAllUnits());
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Exhaust_a_unit", "EXHAUST_UNIT");
    SWUQueueAfterAction($player);
};
