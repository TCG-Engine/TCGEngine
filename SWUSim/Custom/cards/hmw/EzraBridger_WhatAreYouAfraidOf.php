<?php
// HMW_168
// Cost 4 - Ezra Bridger, What Are You Afraid Of? - [Aggression][Heroism] - Unit (Ground) 5/4
//   Traits: Force, Rebel, Spectre - Unique
// Text: When you take the initiative: You may deal 3 damage to your base. If you do, create a Beast token.
//
// The "when you take the initiative" offer is armed in SWUTakeInitiative (GameLogic.php). This is the "if
// you do" continuation: accepting deals the 3 and creates the Beast token (HMW_T03, a 3/3 ground Creature).
// ★ JUDGE RULING 2026-09-14: prevented damage still satisfies "If you do" — "you still tried to damage it"
// (CR 9.2; the Malakili ruling). So a base-damage prevention (JTL_074 Close the Shield Gate) stops the 3
// but NOT the Beast. This used to sample the base and skip the Beast on a prevented hit — the wrong reading.
$customDQHandlers["HMW_168#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;   // "you may" — declined
    global $playerID; $playerID = intval($player);
    SWUDealDamageToBase(3, intval($player));           // "deal 3 damage to your base" (self-damage)
    SWUCreateUnitToken(intval($player), 'HMW_T03');
};
