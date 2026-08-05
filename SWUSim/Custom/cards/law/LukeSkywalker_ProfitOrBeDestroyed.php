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
    $opp  = OtherPlayer(intval($player));
    $playerID = $opp;
    DecisionQueueController::AddDecision($opp, "OPTIONCHOOSE", "CreditAndReady&Deal5", 1, "Opponent_chooses:_create_a_Credit_+_ready_Luke,_OR_let_them_deal_5");
    DecisionQueueController::AddDecision($opp, "CUSTOM", "LAW_080#0|" . intval($player) . "|{$uid}", 1);
};

$customDQHandlers["LAW_080#0"] = function($player, $parts, $lastDecision) {
    // $player = the OPPONENT (the chooser). parts: [caster, lukeUID].
    global $playerID;
    $caster  = intval($parts[0] ?? OtherPlayer(intval($player)));
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
