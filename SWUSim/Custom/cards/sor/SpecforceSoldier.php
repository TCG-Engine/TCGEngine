<?php
// SOR_140
// Cost 1 - SpecForce Soldier - [Aggression,Heroism] - Power 2 - HP 2
// Text: When Played: A unit loses Sentinel for this phase.

// SOR_140 SpecForce Soldier — When Played: a unit loses Sentinel for this phase.
// Only units that currently have Sentinel are eligible targets.
$whenPlayedAbilities["SOR_140:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (HasKeyword_Sentinel($o)) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Choose_a_unit_to_lose_Sentinel", "SOR_140#0");
};

// Tag with the bare CardID — drives SWUKeywordSuppressed (via $keywordSuppressors)
// and doubles as the Active Effects UI source.
$customDQHandlers["SOR_140#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    AddTurnEffect($lastDecision, "SOR_140");
};
