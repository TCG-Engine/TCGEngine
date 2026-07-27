<?php
// LAW_087
// Cost 6 - Jango Fett - Wily Mercenary - [Cunning,Vigilance,Villainy] - Power 6 - HP 5
// Text: Shielded (When you play this unit, give a Shield token to him.) / On Attack: If this unit is upgraded, exhaust an enemy unit.

// LAW_087 Jango Fett — Shielded + On Attack: if this unit is upgraded, exhaust an enemy unit.
$onAttackAbilities["LAW_087:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if ($self === null || !_SWUIsUpgraded($self)) return;
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'EXHAUST_UNIT', 'side' => 'their', 'may' => true,
        'question' => "Exhaust_an_enemy_unit?", 'prompt' => "Choose_an_enemy_unit",
    ]);
};
