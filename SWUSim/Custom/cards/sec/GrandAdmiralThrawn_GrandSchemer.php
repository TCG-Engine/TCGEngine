<?php
// SEC_193
// Cost 7 - Grand Admiral Thrawn - Grand Schemer - [Cunning,Villainy] - Power 8 - HP 7
// Text: When Played: An opponent may choose a non-leader unit they control. If they do, this unit captures that unit. If they don't, ready this unit. / When Defeated: A friendly unit captures an enemy non-leader unit in the same arena.

// SEC_193 Grand Admiral Thrawn — When Played: an opponent MAY choose a non-leader unit they control; if
// they do, Thrawn captures it, else ready Thrawn. When Defeated: a friendly unit captures an enemy
// non-leader unit in the same arena.
$whenPlayedAbilities["SEC_193:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $thrawnUID = SWUObjUID($self, 0);
    $opp = OtherPlayer(intval($player));
    $playerID = $opp;
    $units = array_merge(ZoneSearch('myGroundArena', NonLeaderUnitFilter), ZoneSearch('mySpaceArena', NonLeaderUnitFilter));
    if (empty($units)) {   // opponent can't choose → ready Thrawn
        $playerID = intval($player);
        $tmz = SWUFindMzByUID($thrawnUID);
        if ($tmz !== null) OnReadyCard(intval($player), $tmz);
        return;
    }
    DecisionQueueController::AddDecision($opp, "MZMAYCHOOSE", implode('&', $units), 1, tooltip: "Choose_a_unit_to_be_captured_by_Thrawn_(or_pass)");
    DecisionQueueController::AddDecision($opp, "CUSTOM", "SEC_193#0|{$thrawnUID}|" . intval($player), 1);
    // leave $playerID = $opp so MZCountChoices resolves the relative mzIDs under the opponent
};

$customDQHandlers["SEC_193#0"] = function($player, $parts, $lastDecision) {   // $player = the opponent
    global $playerID; $playerID = intval($player);
    $thrawnUID = intval($parts[0] ?? 0);
    $caster    = intval($parts[1] ?? 0);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $chosen = GetZoneObject($lastDecision);
        $chosenUID = ($chosen !== null) ? intval($chosen->UniqueID ?? 0) : 0;
        $playerID = $caster;
        $tmz = SWUFindMzByUID($thrawnUID);
        $cmz = SWUFindMzByUID($chosenUID);
        if ($tmz !== null && $cmz !== null) DoCaptureUnit($caster, $tmz, $cmz);
        return;
    }
    // declined → ready Thrawn
    $playerID = $caster;
    $tmz = SWUFindMzByUID($thrawnUID);
    if ($tmz !== null) OnReadyCard($caster, $tmz);
};

$whenDefeatedAbilities["SEC_193:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $friendly = SWUAllUnits('my');
    if (empty($friendly)) return;
    SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_capturing_unit", "SEC_193#1");
};

$customDQHandlers["SEC_193#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $captor = GetZoneObject($lastDecision);
    if (SWUObjGone($captor)) return;
    $captorUID = intval($captor->UniqueID ?? 0);
    $isSpace = strpos((string)($captor->Location ?? ''), 'Space') !== false;
    $enemies = array_values(array_filter(ZoneSearch($isSpace ? 'theirSpaceArena' : 'theirGroundArena', NonLeaderUnitFilter),
        fn($mz) => ($e = GetZoneObject($mz)) !== null && empty($e->removed)));
    if (empty($enemies)) return;
    SWUQueueChooseTarget(intval($player), $enemies, "Capture_an_enemy_unit_in_the_same_arena", "SEC_193#2|{$captorUID}");
};

$customDQHandlers["SEC_193#2"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $captor = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($captor !== null) DoCaptureUnit(intval($player), $captor, $lastDecision);
};
