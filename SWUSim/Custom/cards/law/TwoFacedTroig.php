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
    // "You may have AN OPPONENT take control of this unit" — accepting first, then choosing WHO, keeps
    // the existing 2-player YES/NO sequence byte-identical (the picker auto-resolves invisibly at one
    // eligible opponent) and matches the printed order: the "may" is the gate, the seat is the detail.
    // OFFICIAL RULING (03/27/2026): "If there are multiple opponents, the controlling player chooses which
    // one will be 'an opponent.'"
    // ⚠ NO $eligible filter: any live opponent can take control of a unit. LAW_149 Rey's "opponents can't
    // take control of this unit" is a property of the UNIT and blocks every opponent equally, so it never
    // shrinks the menu (same reasoning as LAW_002).
    // ⚠ Handler named #1 — #0 is already taken by the YES/NO continuation above. A duplicate key would
    // silently overwrite it with no error.
    SWUQueueChooseOpponent(intval($player), "LAW_092#1|{$uid}", "Choose_an_opponent_to_take_control");
};

$customDQHandlers["LAW_092#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $uid = intval($parts[0] ?? 0);
    $opp = SWUPickedOpponent($lastDecision);
    if ($uid <= 0 || $opp <= 0 || $opp === intval($player)) return;
    $mz = SWUFindMzByUID($uid);
    if ($mz === null) return;                       // left play while the pick was open
    // "IF YOU DO, create 2 Credit tokens" — gated on the transfer actually happening, so a blocked
    // take-control (LAW_149 Rey) yields no Credits.
    $newMz = SWUTakeControlOfUnit($opp, $mz);
    if ($newMz !== '') SWUCreateCreditToken(intval($player), 2);
};
