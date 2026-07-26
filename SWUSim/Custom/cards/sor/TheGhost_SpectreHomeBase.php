<?php
// SOR_050
// Cost 6 - The Ghost - Spectre Home Base - [Vigilance,Heroism] - Power 5 - HP 5
// Text: Shielded (When you play this unit, give a Shield token to it.) / When Played/On Attack: You may give a Shield token to another SPECTRE unit.

// SOR_050 The Ghost — When Played/On Attack: You may give a Shield token to another
// SPECTRE unit. Shared closure; $mzID is The Ghost's own mzID (excluded — "another").
$whenPlayedAbilities["SOR_050:0"] =
$onAttackAbilities["SOR_050:0"]   = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    SWUQueueMayChooseTarget(intval($player),
        _SWUCollectUnits($selfUID, fn($o) => HasTrait($o->CardID, 'Spectre')),
        'Give_a_Shield_to_another_Spectre_unit?', 'Choose_a_Spectre_unit_to_Shield', 'GIVE_SHIELD');
};
