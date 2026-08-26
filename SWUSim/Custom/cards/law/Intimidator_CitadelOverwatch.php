<?php
// LAW_140
// Cost 11 - Intimidator - Citadel Overwatch - [Command,Villainy] - Power 11 - HP 11
// Text: When Played: Return any number of friendly resources to their owners' hands. For each resource returned this way, create a Credit token.

// LAW_140 Intimidator — When Played: return any number of friendly resources to their owners' hands.
// For each resource returned this way, create a Credit token.
$whenPlayedAbilities["LAW_140:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // "a FRIENDLY resource" spans the TEAM (user ruling 2026-08-26); the p{n} mzIDs a teammate's
    // resources come back as are what makes the transport REVEAL them instead of showing card backs.
    $specs = SWUFriendlyResourceMzIDs(intval($player), fn($o) => !SWUIsCreditToken($o->CardID ?? ''));  // Credit tokens aren't resources
    if (empty($specs)) return;
    $k = count($specs);
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|{$k}|" . implode("&", $specs), 1, tooltip: "Return_any_number_of_friendly_resources_(create_a_Credit_for_each)");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_140#0", 1);
};

$customDQHandlers["LAW_140#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    // Snapshot owner+cardID BEFORE removal (returning a resource shifts indices), then return all + count.
    $toReturn = [];
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        $owner = intval($o->Owner ?? $player); if ($owner <= 0) $owner = intval($player);
        $toReturn[] = ['owner' => $owner, 'cardID' => $o->CardID];
        $o->removed = true;
    }
    DecisionQueueController::CleanupRemovedCards();
    foreach ($toReturn as $r) AddHand($r['owner'], CardID: $r['cardID']);
    for ($i = 0; $i < count($toReturn); $i++) SWUCreateCreditToken(intval($player), 1);
};
