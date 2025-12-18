<?php

//TODO: Add this to a schema
function ActionMap($actionCard)
{
    $turnPlayer = &GetTurnPlayer();
    $currentPhase = GetCurrentPhase();
    $cardArr = explode("-", $actionCard);
    $cardZone = $cardArr[0];
    $cardIndex = $cardArr[1];
    switch ($cardZone) {
        case "myHand":
            if($currentPhase == "ACT") {
                PlayCard($turnPlayer, $actionCard, false);
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
    echo("Playing card: " . $sourceObject->CardID . " for player " . $player . "<BR>");

    switch(CardCard_type($sourceObject->CardID)) {
        case "Fighter":
            UseActions(amount:1);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", "BG1&BG2&BG3&BG4&BG5&BG6&BG7&BG8&BG9", 1);
            DecisionQueueController::AddDecision($player, "MZMOVE", $mzCard . "->{<-}", 1);
            DecisionQueueController::AddDecision($player, "CUSTOM", "ExhaustLast|-", 1);
            DecisionQueueController::AddDecision($player, "CUSTOM", "CardPlayed|" . $sourceObject->CardID, 1);
            break;
        case "Tactic":
            UseActions(amount:CardCost($sourceObject->CardID));
            $newObj = MZMove($player, $mzCard, "myGraveyard");
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

function UseActions($amount=1, $player=null) {
    if($player === null) {
        $turnPlayer = &GetTurnPlayer();
        $player = $turnPlayer;
    }
    $actions = &GetActions($player);
    $actions -= $amount;
    if($actions <= 0) {
        $actions = 0;
        SetCurrentPhase("STPASS");
    }
    $dqController = new DecisionQueueController();
    $dqController->ExecuteStaticMethods($player, "-");
}

function CheckSiege() {
    $turnPlayer = &GetTurnPlayer();
    Draw($turnPlayer, amount: 1);
}

function ActionStep() {

    
}

function PassTurn() {
    $firstPlayer = &GetFirstPlayer();
    $currentTurn = &GetTurnNumber();
    $turnPlayer = &GetTurnPlayer();

    $turnPlayer = ($turnPlayer == 1) ? 2 : 1;

    $actions = &GetActions($turnPlayer);
    $actions = 2;

    if ($turnPlayer == $firstPlayer) {
        ++$currentTurn;
    }
}

$customDQHandlers = [];

$customDQHandlers["ExhaustLast"] = function($player, $param, $lastResult) {
    $zoneName = explode("-", $lastResult)[0];
    $zoneArr = &GetZone($zoneName);
    if (!empty($zoneArr)) {
        $lastIndex = count($zoneArr) - 1;
        echo("Exhausting index: " . $zoneName . "-" . $lastIndex . "<BR>");
        $target = &GetZoneObject($zoneName . "-" . $lastIndex);
        if ($target !== null) {
            $target->Status = 1; // Exhaust the unit
        }
    }
};

$customDQHandlers["Ready"] = function($player, $param, $lastResult) {
    if ($lastResult && $lastResult !== "-") {
        $target = &GetZoneObject($lastResult);
        if ($target !== null) {
            $target->Status = 2; // Ready the unit
        }
    }
};

$customDQHandlers["Bounce"] = function($player, $param, $lastResult) {
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