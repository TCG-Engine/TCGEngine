<?php
// SOR_059
// Cost 1 - 2-1B Surgical Droid - [Vigilance] - Power 1 - HP 3
// Text: On Attack: You may heal 2 damage from another unit.

// SOR_059 2-1B Surgical Droid — On Attack: You may heal 2 damage from another unit.
$onAttackAbilities["SOR_059:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    SWUQueueMayChooseTarget(intval($player),
        _SWUCollectUnits($selfUID, fn($o) => true),
        'Heal_2_damage_from_another_unit?', 'Choose_a_unit_to_heal_2', 'HEAL_TARGET|2');
};
