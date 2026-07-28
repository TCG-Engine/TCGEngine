<?php
// ASH_077
// Cost 3 - Ryder Azadi - Restored Governor - [Vigilance] - Power 2 - HP 5
// Text: Restore 1 / When Played: Name a card. While this unit is in play, opponents can't play cards with that name.

// ASH_077 Ryder Azadi — Restore 1 (keyword) + When Played: name a card; while this unit is in play,
// opponents can't play cards with that name. (Same mechanism as SOR_062 Regional Governor.)
$whenPlayedAbilities["ASH_077:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $obj = GetZoneObject($mzID);
    $uid = SWUObjUID($obj, 0);
    if ($uid === 0) return;
    DecisionQueueController::AddDecision($player, "NAMECARD", "", 1, "Name_a_card_opponents_cannot_play");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ASH_077#0|{$uid}", 1);
};

$customDQHandlers["ASH_077#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    $uid = intval($parts[0] ?? 0);
    if ($uid === 0) return;
    $encName = str_replace(' ', '_', trim($lastDecision));
    AddGlobalEffects(intval($player), "SWU_NAMEBLOCK|{$uid}|{$encName}");
    AddGameLogEntry('NAMECARD', 'P' . intval($player) . ' named ' . trim($lastDecision) . " (opponents can't play it)", 'ALL');
};
