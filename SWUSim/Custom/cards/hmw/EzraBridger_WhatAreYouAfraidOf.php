<?php
// HMW_158
// Cost 4 - Ezra Bridger, What Are You Afraid Of? - [Aggression][Heroism] - Unit (Ground) 5/4
//   Traits: Force, Rebel, Spectre - Unique
// Text: When you take the initiative: You may deal 3 damage to your base. If you do, create a Beast token.
//
// The "when you take the initiative" offer is armed in SWUTakeInitiative (GameLogic.php). This is the "if
// you do" continuation: the 3 self-damage is the cost, so the Beast token (HMW_T03, a 3/3 ground Creature)
// is created only when that damage actually lands — a base-damage prevention (Close the Shield Gate) leaves
// the base damage unchanged, so no Beast.
$customDQHandlers["HMW_158#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;   // "you may" — declined
    global $playerID; $playerID = intval($player);
    $zone = GetBase(intval($player)); $base = $zone[0] ?? null;
    if ($base === null) return;
    $before = intval($base->Damage ?? 0);
    SWUDealDamageToBase(3, intval($player));           // "deal 3 damage to your base" (self-damage)
    if (intval($base->Damage ?? 0) > $before) {        // damage landed (not prevented) → "if you do"
        SWUCreateUnitToken(intval($player), 'HMW_T03');
    }
};
