<?php
// LAW_080
// Cost 7 - Luke Skywalker - Profit or Be Destroyed - [Aggression,Cunning,Heroism] - Power 9 - HP 7
// Text: When Played: An opponent chooses one: / They create a Credit token. Ready this unit. / You may deal 5 damage to a unit. /

// LAW_080 Luke Skywalker — When Played: an opponent chooses one: [they create a Credit token; ready
// this unit] OR [you may deal 5 damage to a unit]. The opponent's OPTIONCHOOSE drives the branch.
$whenPlayedAbilities["LAW_080:0"] = function($player, $mzID) {
    global $playerID;
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    // "AN opponent chooses one" — the caster picks WHICH opponent decides. Auto-resolves invisibly at
    // one eligible (I1). ⚠ NO $eligible filter: the chosen player needs nothing on their board — both
    // modes are things that happen to the CASTER (or a Credit they simply gain), so no opponent can be
    // unable to choose. Taxonomy shape 3, same as LOF_065/SHD_205.
    SWUQueueChooseOpponent(intval($player), "LAW_080#1|" . intval($player) . "|{$uid}",
        "Choose_an_opponent_to_make_the_choice");
};

$customDQHandlers["LAW_080#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? $player);
    $uid    = intval($parts[1] ?? 0);
    $opp    = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === $caster) return;
    $playerID = $opp;
    DecisionQueueController::AddDecision($opp, "OPTIONCHOOSE", "CreditAndReady&Deal5", 1, "Opponent_chooses:_create_a_Credit_+_ready_Luke,_OR_let_them_deal_5");
    DecisionQueueController::AddDecision($opp, "CUSTOM", "LAW_080#0|" . $caster . "|{$uid}", 1);
};

$customDQHandlers["LAW_080#0"] = function($player, $parts, $lastDecision) {
    // $player = the OPPONENT (the chooser). parts: [caster, lukeUID].
    global $playerID;
    // ⚠ NO ?: FALLBACK. The seat always rides the Param now; a missing one is a NO-OP, never a guess
    // (§5 defect class 3 — a fallback that invents a seat is the bug, not the safety net).
    $caster  = intval($parts[0] ?? 0);
    if ($caster <= 0 || $caster === intval($player)) return;
    $lukeUID = intval($parts[1] ?? 0);
    if ($lastDecision === 'Deal5') {
        $playerID = $caster;
        $units = SWUAllUnits();
        if (empty($units)) return;
        SWUQueueMayChooseTarget($caster, $units, "Deal_5_damage_to_a_unit?", "Deal_5_damage_to_a_unit", "DEAL_UNIT_DAMAGE|5");
        return;
    }
    // CreditAndReady: the opponent creates a Credit token; ready Luke (the caster's unit).
    SWUCreateCreditToken(intval($player), 1);
    $playerID = $caster;
    $lukeMz = SWUFindMzByUID($lukeUID);
    if ($lukeMz !== null) OnReadyCard($caster, $lukeMz);
};
