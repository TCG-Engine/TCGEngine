<?php
// ASH_217
// Cost 2 - Mayor's Majordomo - No Problem Groveling - [Cunning] - Power 1 - HP 4
// Text: Action [Exhaust, discard a card from your hand]: Exhaust a unit.

// ASH_217 Mayor's Majordomo — Action [Exhaust, discard a card from your hand]: exhaust a unit.
$unitAbilities["ASH_217"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hand = array_values(ZoneSearch("myHand"));
    if (empty($hand)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $hand, "Discard_a_card_to_exhaust_a_unit", "ASH_217#0");
};

$customDQHandlers["ASH_217#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction($player); return; }
    DoDiscardCard(intval($player), $lastDecision);   // pay the discard cost
    $tg = SWUAllUnits();
    if (empty($tg)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $tg, "Exhaust_a_unit", "EXHAUST_UNIT");
    SWUQueueAfterAction($player);
};
