<?php
// LAW_092
// Cost 3 - Two-Faced Troig - [Cunning,Vigilance] - Power 2 - HP 4
// Text: Sentinel / When Played: You may have an opponent take control of this unit. If you do, create 2 Credit tokens.

// LAW_092 Two-Faced Troig — Sentinel + When Played: you may have an opponent take control of this unit.
// If you do, create 2 Credit tokens.
$whenPlayedAbilities["LAW_092:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Have_an_opponent_take_control_of_this_unit_(create_2_Credits)?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_092#0|{$uid}", 1);
};

$customDQHandlers["LAW_092#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $uid = intval($parts[0] ?? 0);
    $mz  = SWUFindMzByUID($uid);
    if ($mz === null) return;
    SWUTakeControlOfUnit(OtherPlayer(intval($player)), $mz);
    SWUCreateCreditToken(intval($player), 2);
};
