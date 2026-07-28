<?php
// LAW_032
// Cost 6 - Cad Bane - Now It's My Turn - [Vigilance,Command,Villainy] - Power 6 - HP 6
// Text: Shielded / Overwhelm / On Attack: Defeat any number of friendly Credit tokens. Give an Experience token to this unit for each Credit defeated this way.

// LAW_032 Cad Bane — Shielded + Overwhelm + On Attack: defeat any number of friendly Credit tokens;
// give an Experience token to this unit for each Credit defeated.
$onAttackAbilities["LAW_032:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $credits = SWUUsableCreditTokenMzIDs(intval($player));
    if (empty($credits)) return;
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    $k = count($credits);
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|{$k}|" . implode("&", $credits), 1, tooltip: "Defeat_any_number_of_friendly_Credit_tokens_(1_Experience_each)");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_032#0|{$uid}", 1);
};

$customDQHandlers["LAW_032#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $uid = intval($parts[0] ?? 0);
    // Resolve all chosen credit objects FIRST, then mark removed + one cleanup (per-item
    // SWUDefeatCreditToken cleans up immediately, shifting the remaining indices).
    $n = 0;
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && SWUIsCreditToken($o->CardID ?? '')) { $o->removed = true; $n++; }
    }
    if ($n > 0) DecisionQueueController::CleanupRemovedCards();
    $self = SWUFindMzByUID($uid);
    if ($self !== null) for ($i = 0; $i < $n; $i++) DoGiveExperienceToken(intval($player), $self);
};
