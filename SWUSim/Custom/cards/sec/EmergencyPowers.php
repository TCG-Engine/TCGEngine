<?php
// SEC_040
// Cost 1 - Emergency Powers - [Vigilance,Villainy]
// Text: Choose a non-leader unit and pay any number of resources. For each resource paid this way, give an Experience token to the chosen unit.

// SEC_040 Emergency Powers — chosen non-leader unit; pay any number of resources → that many Exp tokens.
$customDQHandlers["SEC_040#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $maxX = SWUResourceCount(intval($player), true);
    if ($maxX <= 0) return;
    DecisionQueueController::AddDecision(intval($player), "NUMBERCHOOSE", "0|" . $maxX, 1, tooltip: "Pay_any_number_of_resources_(1_Experience_each)");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_040#1|" . intval($o->UniqueID ?? 0), 1);
};

$customDQHandlers["SEC_040#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $x = intval($lastDecision);
    if ($x <= 0) return;
    if (!SWUExhaustResources(intval($player), $x)) return;
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz === null) return;
    for ($i = 0; $i < $x; $i++) DoGiveExperienceToken(intval($player), $mz);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_040:0"] = function($player, $mzID = '') {
// Emergency Powers — "Choose a non-leader unit and pay any number of resources.
                          // For each resource paid, give an Experience token to the chosen unit."
            global $playerID; $playerID = intval($player);
            $units = array_merge(ZoneSearch("myGroundArena", NonLeaderUnitFilter), ZoneSearch("mySpaceArena", NonLeaderUnitFilter),
                                 ZoneSearch("theirGroundArena", NonLeaderUnitFilter), ZoneSearch("theirSpaceArena", NonLeaderUnitFilter));
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Choose_a_non-leader_unit", "SEC_040#0");
            return;
};
