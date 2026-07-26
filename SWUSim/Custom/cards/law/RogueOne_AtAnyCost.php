<?php
// LAW_119
// Cost 3 - Rogue One - At Any Cost - [Vigilance] - Power 3 - HP 3
// Text: When a friendly unit is defeated: Look at the top 2 cards of your deck. Put any number of them on the bottom of your deck and the rest on top in any order.

// LAW_119 Rogue One — put the chosen top cards on the bottom of the deck (the rest stay on top).
$customDQHandlers["LAW_119#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;   // none → all stay on top
    $cardIDs = [];
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) { $cardIDs[] = $o->CardID; $o->removed = true; }
    }
    if (empty($cardIDs)) return;
    DecisionQueueController::CleanupRemovedCards();
    _topDeckPutRemainingToBottom(intval($player), $cardIDs);
};
