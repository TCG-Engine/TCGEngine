<?php
// SOR_197
// Cost 6 - Lando Calrissian - Responsible Businessman - [Cunning,Heroism] - Power 6 - HP 5
// Text: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / When Played: Return up to 2 friendly resources to their owners' hands.

// SOR_197 Lando Calrissian — "When Played: Return up to 2 friendly resources to their owners'
// hands." MZMULTICHOOSE up to 2 of the controller's resources; each returns to its owner's hand.
$whenPlayedAbilities["SOR_197:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    // "a FRIENDLY resource" spans the TEAM (user ruling 2026-08-26); the p{n} mzIDs a teammate's
    // resources come back as are what makes the transport REVEAL them instead of showing card backs.
    $resources = array_values(SWUFriendlyResourceMzIDs(intval($player)));
    if (empty($resources)) return;
    $targetStr = implode('&', $resources);
    DecisionQueueController::AddDecision(intval($player), 'MZMULTICHOOSE', "0|2|{$targetStr}", 1,
        'Return_up_to_2_friendly_resources_to_hand');
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'SOR_197#0', 1, dontSkipOnPass: 1);
};

$customDQHandlers["SOR_197#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '' || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    // Snapshot the chosen resource objects BEFORE any removal (indices shift on each removal).
    $chosen = [];
    foreach (explode('&', $lastDecision) as $m) {
        $m = trim($m);
        if ($m === '' || $m === '-' || $m === 'PASS') continue;
        $o = GetZoneObject($m);
        if ($o !== null && empty($o->removed)) $chosen[] = $o;
    }
    foreach ($chosen as $o) {
        $owner = intval($o->Owner ?? 0);
        if ($owner <= 0) $owner = intval($player); // unset Owner → the controller (friendly)
        $o->removed = true;
        AddHand($owner, CardID:$o->CardID);
    }
    DecisionQueueController::CleanupRemovedCards();
};
