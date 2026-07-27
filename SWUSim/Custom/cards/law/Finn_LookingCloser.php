<?php
// LAW_095
// Cost 6 - Finn - Looking Closer - [Cunning,Vigilance] - Power 6 - HP 5
// Text: Ambush (When you play this unit, he may attack an enemy unit.) / On Attack: You may give a Shield token to a non-<uq> unit.

// LAW_095 Finn — Ambush + On Attack: you may give a Shield token to a non-unique unit.
$onAttackAbilities["LAW_095:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, ['continuation'=>'GIVE_SHIELD','may'=>true,
        'extraFilter'=>fn($o)=>!CardUnique($o->CardID ?? ''),
        'question'=>"Give_a_Shield_token_to_a_non-unique_unit?",'prompt'=>"Choose_a_unit"]);
};
