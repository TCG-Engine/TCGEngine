<?php
// SEC_150
// Cost 3 - Valiant Commando - [Aggression,Heroism] - Power 3 - HP 3
// Text: When this unit deals combat damage to a base: You may defeat this unit. If you do, deal 3 damage to that base.

// SEC_150 Valiant Commando — combat-hit continuation: defeat itself → 3 to THAT base (the one it just
// damaged), whose seat is threaded in as $parts[1].
$customDQHandlers["SEC_150#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $self = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($self !== null) SWUDefeatUnit(intval($player), $self);
    // The seat is threaded from the trigger (see SEC150SacTrigger) because it is the base this unit
    // actually damaged — never OtherPlayer(), which names one seat.
    $baseSeat = intval($parts[1] ?? 0);
    if ($baseSeat <= 0) $baseSeat = SWUCurrentDefendingSeat(intval($player));
    if ($baseSeat > 0) SWUDealDamageToBase(3, $baseSeat);
};
