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
        case "BG1": case "BG2": case "BG3": case "BG4": case "BG5": case "BG6": case "BG7": case "BG8": case "BG9":
            if($currentPhase == "ACT") {
                DecisionQueueController::AddDecision($turnPlayer, "MZCHOOSE", "BG1&BG2&BG3&BG4&BG5&BG6&BG7&BG8&BG9", 1);
                DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", $mzCard . "FighterAction|" . $cardZone, 1);
                return "MOVE";
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
            DecisionQueueController::AddDecision($player, "CUSTOM", "AfterFighterPlayed|-", 1);
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

function GainActions($amount=1, $player=null) {
    if($player === null) {
        $turnPlayer = &GetTurnPlayer();
        $player = $turnPlayer;
    }
    $actions = &GetActions($player);
    $actions += $amount;
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

    $bg1 = &GetZone("BG1");
    foreach($bg1 as $index => $obj) {
        if($obj->Controller == $turnPlayer) $obj->Status = 2;
    }
    $bg2 = &GetZone("BG2");
    foreach($bg2 as $index => $obj) {
        if($obj->Controller == $turnPlayer) $obj->Status = 2;
    }
    $bg3 = &GetZone("BG3");
    foreach($bg3 as $index => $obj) {
        if($obj->Controller == $turnPlayer) $obj->Status = 2;
    }
    $bg4 = &GetZone("BG4");
    foreach($bg4 as $index => $obj) {
        if($obj->Controller == $turnPlayer) $obj->Status = 2;
    }
    $bg5 = &GetZone("BG5");
    foreach($bg5 as $index => $obj) {
        if($obj->Controller == $turnPlayer) $obj->Status = 2;
    }
    $bg6 = &GetZone("BG6");
    foreach($bg6 as $index => $obj) {
        if($obj->Controller == $turnPlayer) $obj->Status = 2;
    }
    $bg7 = &GetZone("BG7");
    foreach($bg7 as $index => $obj) {
        if($obj->Controller == $turnPlayer) $obj->Status = 2;
    }   
    $bg8 = &GetZone("BG8");
    foreach($bg8 as $index => $obj) {
        if($obj->Controller == $turnPlayer) $obj->Status = 2;
    }
    $bg9 = &GetZone("BG9");
    foreach($bg9 as $index => $obj) {
        if($obj->Controller == $turnPlayer) $obj->Status = 2;
    }
}

$customDQHandlers = [];

$customDQHandlers["AfterFighterPlayed"] = function($player, $param, $lastResult) {
    $zoneName = explode("-", $lastResult)[0];
    $zoneArr = &GetZone($zoneName);
    if (!empty($zoneArr)) {
        $lastIndex = count($zoneArr) - 1;
        $target = &GetZoneObject($zoneName . "-" . $lastIndex);
        if ($target !== null) {
            $target->Status = 1; // Exhaust the unit
            $target->Controller = $player;
        }
    }
};

$customDQHandlers["FighterAction"] = function($player, $param, $lastResult) {
    UseActions(amount:1);
    $destZoneName = explode("-", $lastResult)[0];
    $fromZone = &GetZone($param[0]);
    $destZone = &GetZone($destZoneName);
    for($i = 1; $i < count($fromZone); ++$i) {
        $fromZone[$i]->Status = 1; // Exhaust the unit
    }
    if(count($destZone) == 1) {
        //This is a move, move the whole stack from 1 -> end
        for($i = 1; $i < count($fromZone); ++$i) {
            MZMove($player, $param[0] . "-" . $i, $destZoneName);
        }
        return;
    } else {
        //This is an attack
        $fromTop = $fromZone[count($fromZone) - 1];
        $destTop = $destZone[count($destZone) - 1];
        $fromPower = CurrentCardPower($fromZone, $destZone, true);
        $destPower = CurrentCardPower($destZone, $fromZone, false);
        //Simple combat: Higher power wins, both are destroyed on a tie
        if($fromPower > $destPower) {
            //Attacker wins
            FighterDestroyed($player, $destZoneName . "-" . (count($destZone) - 1));
            //MZMove($player, $destZoneName . "-" . (count($destZone) - 1), "theirGraveyard");//TODO: Should be a macro
            if(count($destZone) == 2) { //Means there was only one defender
                //Move the whole stack
                for($i = 1; $i < count($fromZone); ++$i) {
                    MZMove($player, $param[0] . "-" . $i, $destZoneName);
                }
            }
        } else if($fromPower < $destPower) {
            //Defender wins
            //MZMove($player, $param . "-0", "myGraveyard");
        } else {
            //Both destroyed
            //MZMove($player, $param . "-0", "myGraveyard");
            //MZMove($player, $lastResult, "theirGraveyard");
        }

        //MZMove($player, $param . "-0", $destZoneName);
    }
};

function CurrentCardPower($fromZone, $destZone, $isAttacker=false) {
    $fromTop = $fromZone[count($fromZone) - 1];
    $destTop = $destZone[count($destZone) - 1];
    $fromPower = CardPower($fromTop->CardID);
    //Self power modifiers
    switch($fromTop->CardID) {
        case "RYBF1DSKH": case "RYBF2DSKH": case "RYBF3DSKH": //Dusklight Hunter
            if($isAttacker && TraitContains($destTop, "Brute")) {
                $fromPower += 1;
            }
            break;
        case "RYBF1DWNB": case "RYBF2DWNB": case "RYBF3DWNB": //Dawnbringer Brute
            if($isAttacker && TraitContains($destTop, "Soldier")) {
                $fromPower += 1;
            }
            break;
        case "RYBF1SLSD": case "RYBF2SLSD": case "RYBF3SLSD": //Solaran Soldier
            if($isAttacker && TraitContains($destTop, "Hunter")) {
                $fromPower += 1;
            }
            break;
        default: break;
    }
    return $fromPower;
}

function DoFighterDestroyed($player, $mzCard) {
    MZMove($player, $mzCard, "myGraveyard");
}

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

function TraitContains($card, $trait) {
    $traits = CardTraits($card->CardID);
    $traitArr = explode(",", $traits);
    return in_array($trait, $traitArr);
}

?>