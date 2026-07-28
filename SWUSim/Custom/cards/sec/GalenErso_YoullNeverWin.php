<?php
// SEC_046
// Cost 4 - Galen Erso - You'll Never Win - [Vigilance,Heroism] - Power 3 - HP 5
// Text: When Played: Name a card. While this unit is in play, each non-leader card an opponent owns with that name, including those not in play, loses all abilities (and can't gain abilities). / Plot

// SEC_046 Galen Erso — When Played: Name a card. While this unit is in play, each non-leader card an
// opponent owns with that name (incl. those not in play) loses all abilities and can't gain abilities.
// Stores SWU_GALEN|{galenUID}|{encName} on Galen's controller; LostAbilities reads it (see KeywordEffects).
$whenPlayedAbilities["SEC_046:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $obj = GetZoneObject($mzID);
    $uid = SWUObjUID($obj, 0);
    if ($uid === 0) return;
    DecisionQueueController::AddDecision(intval($player), "NAMECARD", "", 1, "Name_a_card");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_046#0|{$uid}", 1);
};

$customDQHandlers["SEC_046#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    $uid = intval($parts[0] ?? 0);
    if ($uid === 0) return;
    $name    = trim($lastDecision);
    $encName = str_replace(' ', '_', $name);
    AddGlobalEffects(intval($player), "SWU_GALEN|{$uid}|{$encName}");
    AddGameLogEntry('NAMECARD', 'P' . intval($player) . ' named ' . $name . ' (Galen Erso — loses all abilities)', 'ALL');
};
