<?php
// ASH_047
// Cost 3 - Gar Saxon - Coveting Power - [Vigilance,Villainy] - Power 3 - HP 4
// Text: When you play an upgrade on this unit: You may create a Mandalorian token. Use this ability only once each round.

$customDQHandlers["ASH_047#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES' && $lastDecision !== '1') return;
    global $playerID; $playerID = intval($player);
    SWUCreateUnitToken(intval($player), 'ASH_T01');
};
