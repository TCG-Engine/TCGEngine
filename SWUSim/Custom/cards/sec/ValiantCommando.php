<?php
// SEC_150
// Cost 3 - Valiant Commando - [Aggression,Heroism] - Power 3 - HP 3
// Text: When this unit deals combat damage to a base: You may defeat this unit. If you do, deal 3 damage to that base.

// SEC_150 Valiant Commando — combat-hit continuation: defeat itself → 3 to the opponent's base.
$customDQHandlers["SEC_150#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $self = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($self !== null) SWUDefeatUnit(intval($player), $self);
    SWUDealDamageToBase(3, OtherPlayer(intval($player)));
};
