<?php
// LAW_040
// Cost 5 - Taramyn Barcona - Eyes Front! - [Vigilance,Command] - Power 4 - HP 6
// Text: When Played: You may defeat a Credit token (belonging to any player). If you do, give an Experience token to this unit and another friendly unit.

// LAW_040 Taramyn Barcona — When Played: you may defeat a Credit token (any player's). If you do, give
// an Experience token to this unit and another friendly unit.
$whenPlayedAbilities["LAW_040:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    $credits = array_merge(SWUUsableCreditTokenMzIDs(intval($player)), SWUEnemyCreditTokenMzIDs(intval($player)));
    if (empty($credits)) return;
    SWUQueueMayChooseTarget(intval($player), $credits, "Defeat_a_Credit_token?", "Choose_a_Credit_token", "LAW_040#0|{$uid}");
};

$customDQHandlers["LAW_040#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    if (!SWUDefeatCreditToken($lastDecision)) return;
    $uid  = intval($parts[0] ?? 0);
    $self = SWUFindMzByUID($uid);
    if ($self !== null) DoGiveExperienceToken(intval($player), $self);
    // Give an Experience token to ANOTHER friendly unit.
    $others = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $uid) $others[] = $mz;
    }
    if (empty($others)) return;
    SWUQueueChooseTarget(intval($player), $others, "Give_an_Experience_token_to_another_friendly_unit", "GIVE_EXPERIENCE|1");
};
