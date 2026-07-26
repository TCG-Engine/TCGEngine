<?php
// SEC_186
// Cost 2 - Garindan - Information Broker - [Cunning,Villainy] - Power 1 - HP 3
// Text: When Played: Name a card. Look at an opponent's hand and discard a card with that name from it. / Plot (When you deploy a leader, you may play this card from your resources, paying its cost. Replace it with the top card of your deck.)

// SEC_186 Garindan — When Played: name a card; look at an opponent's hand and discard a card with
// that name from it.
$whenPlayedAbilities["SEC_186:0"] = function($player, $mzID) {
    // If the opponent's hand is empty there is nothing to look at or discard, so the naming is
    // meaningless — skip the whole ability (no NAMECARD prompt).
    global $playerID; $savedPID = $playerID; $playerID = OtherPlayer(intval($player));
    $oppHandEmpty = count(ZoneSearch("myHand", null)) === 0;
    $playerID = $savedPID;
    if ($oppHandEmpty) return;
    DecisionQueueController::AddDecision(intval($player), "NAMECARD", "", 1, "Name_a_card");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_186#0", 1);
};

$customDQHandlers["SEC_186#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $savedPID = $playerID;
    $named = trim($lastDecision);
    $opp = OtherPlayer(intval($player));
    $playerID = $opp;
    foreach (ZoneSearch("myHand", null) as $mz) {       // opponent's hand, in opp frame
        $c = GetZoneObject($mz);
        if ($c !== null && empty($c->removed) && CardTitle($c->CardID ?? '') === $named) {
            $c->Remove();
            SWUAddToDiscard($opp, $c->CardID, 'HAND');
            break;                                       // discard one matching card
        }
    }
    $playerID = $savedPID;
};
