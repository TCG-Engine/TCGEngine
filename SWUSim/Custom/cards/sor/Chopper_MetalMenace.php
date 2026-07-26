<?php
// SOR_188
// Cost 1 - Chopper - Metal Menace - [Cunning,Heroism] - Power 1 - HP 3
// Text: While you control another SPECTRE unit, this unit gains Raid 1. / On Attack: Discard a card from the defending player's deck. If it's an event, exhaust a resource that player controls.

// SOR_188 Chopper — "On Attack: Discard a card from the defending player's deck. If it's an event,
// exhaust a resource that player controls." (Conditional Raid 1 lives in GetConditionalKeyword_Raid_Value.)
$onAttackAbilities["SOR_188:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $defender = GetOpponent(intval($player));
    $milled = SWUMillTopCard($defender);
    if ($milled === null) return;
    if (strpos(CardType($milled) ?? '', 'Event') !== false) {
        SWUExhaustResources($defender, 1); // exhaust a resource the defending player controls
    }
};
