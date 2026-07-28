<?php
// SEC_106
// Cost 6 - Dismantle the Conspiracy - [Command,Heroism]
// Text: A friendly unit captures any number of enemy non-leader units with a total of 7 or less remaining HP.

// SEC_106 Dismantle the Conspiracy — a friendly unit captures any number of enemy non-leader units with a
// total of 7-or-less remaining HP. #0 stores the captor; #1 offers an affordable enemy; #2 captures it
// and loops with the reduced budget. (budget = remaining-HP allowance.)
$customDQHandlers["SEC_106#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $captor = GetZoneObject($lastDecision);
    if (SWUObjGone($captor)) return;
    $captorUID = intval($captor->UniqueID ?? 0);
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_106#1|{$captorUID}|7", 1);
};

$customDQHandlers["SEC_106#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $captorUID = intval($parts[0] ?? 0);
    $budget    = intval($parts[1] ?? 0);
    $targets = [];
    foreach (array_merge(ZoneSearch("theirGroundArena", NonLeaderUnitFilter), ZoneSearch("theirSpaceArena", NonLeaderUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        $remHP = intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0);
        if ($remHP <= $budget) $targets[] = $mz;
    }
    if (empty($targets)) return;   // nothing affordable → stop
    SWUQueueMayChooseTarget(intval($player), $targets, "Capture_another_enemy_unit?", "Choose_an_enemy_non-leader_unit_(HP<={$budget})", "SEC_106#2|{$captorUID}|{$budget}");
};

$customDQHandlers["SEC_106#2"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $captorUID = intval($parts[0] ?? 0);
    $budget    = intval($parts[1] ?? 0);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $remHP  = intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0);
    $captor = SWUFindMzByUID($captorUID);
    if ($captor === null) return;
    DoCaptureUnit(intval($player), $captor, $lastDecision);
    $newBudget = $budget - max(0, $remHP);
    if ($newBudget > 0) DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_106#1|{$captorUID}|{$newBudget}", 1);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_106:0"] = function($player, $mzID = '') {
// Dismantle the Conspiracy — "A friendly unit captures any number of enemy
                          // non-leader units with a total of 7 or less remaining HP."
            global $playerID; $playerID = intval($player);
            $friendly = array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter));
            if (empty($friendly)) return;
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_capturing_unit", "SEC_106#0");
            return;
};
