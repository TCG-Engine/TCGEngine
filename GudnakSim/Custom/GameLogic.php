<?php

//TODO: Add this to a schema
function ActionMap($actionCard)
{
    global $currentPlayer;
    $currentPhase = GetCurrentPhase();
    $cardArr = explode("-", $actionCard);
    $cardZone = $cardArr[0];
    $cardIndex = $cardArr[1];
    switch ($cardZone) {
        case "myHand":
            if($currentPhase == "ACT") {
                PlayCard($currentPlayer, $actionCard, false);
                return "PLAY";
            }
            break;
        default: break;
    }
    return "";
}

function DoPlayCard($player, $mzCard, $ignoreCost = false)
{
    global $customDQHandlers;
    $sourceObject = &GetZoneObject($mzCard);
    if(!$ignoreCost) {
        $toPay = CardCost($sourceObject->CardID);
        switch($sourceObject->CardID) { //Self cost modifications

            default:
                break;
        }
        //$amountPaid = PayEnergy($player, $toPay);

    }
    switch(CardType($sourceObject->CardID)) {
        case "Unit":
            DecisionQueueController::AddDecision($player, "MZCHOOSE", "myBattlefield&theirBattlefield&myBase:index=0", 1);
            DecisionQueueController::AddDecision($player, "MZMOVE", $mzCard . "->{<-}", 1);
            DecisionQueueController::AddDecision($player, "CUSTOM", "CardPlayed|" . $sourceObject->CardID, 1);
            break;
        case "Spell":
            MZMove($player, $mzCard, "myTrash");
            $customDQHandlers["CardPlayed"]($player, [$sourceObject->CardID], null);
            break;
        default: break;
    }
    //My played card effects

    $dqController = new DecisionQueueController();
    $dqController->ExecuteStaticMethods($player, "-");
}


function CardPlayedEffects($player, $card, $cardPlayed) {
    switch($card->CardID) {

        default: break;
    }
}

function AwakenStep() {

    
}

function BeginningStep() {
}

function ChannelStep() {

    
}

function DrawStep() {
    $turnPlayer = &GetTurnPlayer();
    Draw($turnPlayer, amount: 1);
}

function ActionStep() {
    global $currentPlayer;
    $turnPlayer = &GetTurnPlayer();
    $currentPlayer = $turnPlayer;
}

function PassTurn() {
    $firstPlayer = &GetFirstPlayer();
    $currentTurn = &GetTurnNumber();
    $turnPlayer = &GetTurnPlayer();

    $turnPlayer = ($turnPlayer == 1) ? 2 : 1;

    if ($turnPlayer == $firstPlayer) {
        ++$currentTurn;
    }
}

$customDQHandlers = [];

$customDQHandlers["Ready"] = function($player, $param, $lastResult) {
    // lastResult is expected to be an mzID like 'myBattlefield-3'
    if ($lastResult && $lastResult !== "-") {
        $target = &GetZoneObject($lastResult);
        if ($target !== null) {
            $target->Status = 2; // Ready the unit
        }
    }
};

$customDQHandlers["Bounce"] = function($player, $param, $lastResult) {
    // lastResult is expected to be an mzID like 'myBattlefield-3'
    if ($lastResult && $lastResult !== "-") {
        MZMove($player, $lastResult, "myHand");
    }
};

$customDQHandlers["CardPlayed"] = function($player, $param, $lastResult) {
    global $playCardAbilities;
    $cardID = $param[0];
    if(isset($playCardAbilities[$cardID])) {
        $playCardAbilities[$cardID]($player);
    }
};

function OnCardChosen($player, $lastResult) {
    $card = &GetZoneObject($lastResult);
}

?>