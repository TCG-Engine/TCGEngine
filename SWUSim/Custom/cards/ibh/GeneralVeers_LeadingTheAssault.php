<?php
// IBH_068
// Cost 5 - General Veers - Leading the Assault - [Aggression,Villainy] - Power 3 - HP 6
// Text: When Played: If you control a Vigilance unit, deal 2 damage to an enemy base and heal 2 damage from your base.

// IBH_068 / IBH_088 General Veers — When Played: if you control a Vigilance unit, deal 2 to an enemy
// base and heal 2 from your base.
$whenPlayedAbilities["IBH_068:0"] =
$whenPlayedAbilities["IBH_088:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (!_SWUControlsUnitWithAspect(intval($player), 'Vigilance')) return;
    // "AN enemy base" names no seat — the caster picks which. SWUQueueChooseOpponent auto-resolves to an
    // invisible PASSPARAMETER at one eligible opponent, so Premier is byte-identical.
    SWUQueueChooseOpponent(intval($player), 'IBH_068#BASE', "Deal_2_to_which_opponent's_base?");
    OnHealBase(intval($player), intval($player), 2);
};

$customDQHandlers["IBH_068#BASE"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp > 0) SWUDealDamageToBase(2, $opp);
};
