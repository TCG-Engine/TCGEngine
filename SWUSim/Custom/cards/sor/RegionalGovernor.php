<?php
// SOR_062
// Cost 2 - Regional Governor - [Vigilance] - Power 1 - HP 4
// Text: When Played: Name a card. While this unit is in play, opponents can't play the named card.

// SOR_062 Regional Governor — "When Played: Name a card. While this unit is in play, opponents
// can't play the named card." Stores the named title (keyed by this unit's UID) as a GlobalEffects
// flag on the controller; SWUCardPlayBlocked consults it (and clears it when the unit leaves play).
$whenPlayedAbilities["SOR_062:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($mzID);
    $uid = SWUObjUID($obj, 0);
    if ($uid === 0) return;
    DecisionQueueController::AddDecision($player, "NAMECARD", "", 1, "Name_a_card_opponents_cannot_play");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_062#0|{$uid}", 1);
};

$customDQHandlers["SOR_062#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    $uid = intval($parts[0] ?? 0);
    if ($uid === 0) return;
    $name    = trim($lastDecision);
    $encName = str_replace(' ', '_', $name);     // GlobalEffects flags are space-delimited
    AddGlobalEffects(intval($player), "SWU_NAMEBLOCK|{$uid}|{$encName}");
    AddGameLogEntry('NAMECARD', 'P' . intval($player) . ' named ' . $name . " (opponents can't play it)", 'ALL');
};
