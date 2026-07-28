<?php
// SEC_227
// Cost 2 - Special Modifications - [Cunning] - Upgrade Power 1 - Upgrade HP 3
// Text: Attach to a Vehicle unit. / When Played: If attached unit is a Transport, you may create a Spy token.

// SEC_227 Special Modifications (Upgrade, attach to a Vehicle) — When Played: if the attached unit is a
// Transport, you may create a Spy token. (whenPlayed on an upgrade gets the HOST mzID.)
$whenPlayedAbilities["SEC_227:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if ($host === null || !HasTrait($host->CardID ?? '', 'Transport')) return;
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Create_a_Spy_token?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_227#0", 1);
};

$customDQHandlers["SEC_227#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === 'YES') SWUCreateUnitToken(intval($player), 'SEC_T01');
};
