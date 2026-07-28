<?php
// SOR_115
// Cost 5 - Agent Kallus - Seeking the Rebels - [Command] - Power 4 - HP 4
// Text: Ambush (After you play this unit, he may ready and attack an enemy unit.) / When another unique unit is defeated: You may draw a card. Use this ability only once each round.

// SOR_115 Agent Kallus — optional draw on the once-per-round defeat trigger. The round's use was
// already consumed at collect time, so declining (NO) just draws nothing.
$customDQHandlers["SOR_115#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    DoDrawCard(intval($player), 1);
};
