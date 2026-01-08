<?php

$debugMode = true;

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
                DoFighterAction($turnPlayer, $cardZone);
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
    switch(CardCard_type($sourceObject->CardID)) {
        case "Fighter":
            DoPlayFighter($player, $mzCard);
            UseActions(amount:1);
            break;
        case "Tactic":
            $newObj = MZMove($player, $mzCard, "myGraveyard");
            $customDQHandlers["CardPlayed"]($player, [$sourceObject->CardID], null);
            UseActions(amount:CardCost($sourceObject->CardID));
            break;
        default: break;
    }
    //My played card effects
    $zones = ["BG1", "BG2", "BG3", "BG4", "BG5", "BG6", "BG7", "BG8", "BG9"];
    foreach($zones as $zoneName) {
        CardPlayedEffects($player, GetTopCard($zoneName), $sourceObject->CardID);
    }

    $dqController = new DecisionQueueController();
    $dqController->ExecuteStaticMethods($player, "-");
}

function CardPlayedEffects($player, $card, $cardPlayed) {
    switch($card->CardID) {
        case "RYBF1HKNLM"://Kennel Master
            if($card->Controller == $player && $card->Status == 2 && CardCard_type($cardPlayed) == "Tactic") {
                AddHand($player, "RYBF1GFDG"); //Gryffdog
            }
            break;
        default: break;
    }
}

function DoActivatedAbility($player, $mzCard) {
    global $customDQHandlers;
    $sourceObject = &GetZoneObject($mzCard);
    switch(CardCard_type($sourceObject->CardID)) {
        case "Fighter":
            $sourceObject->Status = 1; // Exhaust the unit
            break;
        default: break;
    }
    //My activated ability effects
    $customDQHandlers["AbilityActivated"]($player, [$sourceObject->CardID], null);

    $dqController = new DecisionQueueController();
    $dqController->ExecuteStaticMethods($player, "-");
}

function DoFighterAction($player, $cardZone, $includeMove = true, $includeAttack = true) {
    $adjacentZones = AdjacentZones($cardZone);
    $legalZones = [];
    foreach($adjacentZones as $zone) {
        $zoneArr = &GetZone($zone);
        if(count($zoneArr) == 1) {
            //Can move to empty zone
            if($includeMove) array_push($legalZones, $zone);
        } else if(count($zoneArr) > 1) {
            $topCard = $zoneArr[count($zoneArr) - 1];
            if($includeAttack && $topCard->Controller != $player) {
                if(CanAttack($player, $cardZone, $zone)) array_push($legalZones, $zone);
            } else if($includeMove && $topCard->Controller == $player) {
                array_push($legalZones, $zone);
            }
        }
    }
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $legalZones), 1);
    DecisionQueueController::AddDecision($player, "CUSTOM", "FighterAction|" . $cardZone, 1);
}

function CanAttack($player, $fromZone, $toZone) {
    $fromZoneArr = &GetZone($fromZone);
    $toZoneArr = &GetZone($toZone);
    if(count($fromZoneArr) > 1 && count($toZoneArr) > 1) {
        $fromTop = $fromZoneArr[count($fromZoneArr) - 1];
        $toTop = $toZoneArr[count($toZoneArr) - 1];
        if($fromTop->Controller != $player || $toTop->Controller == $player) return false;
        if($toTop->HasTurnEffects("RYBF1HBTCS") || $toTop->HasTurnEffects("RYBTDVPT")) return false;
        return true;
    }
    return false;
}

function GetDeployZones($player, $cardID) {
    $legalZones = [];
    if($player == 1) {
        array_push($legalZones, "BG1");
        array_push($legalZones, "BG2");
        array_push($legalZones, "BG3");
    } else {
        array_push($legalZones, "BG7");
        array_push($legalZones, "BG8");
        array_push($legalZones, "BG9");
    }
    return $legalZones;
}

function DoPlayFighter($player, $mzCard) {
    $sourceObject = &GetZoneObject($mzCard);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", GetDeployZones($player, $sourceObject->CardID)), 1);
    DecisionQueueController::AddDecision($player, "MZMOVE", $mzCard . "->{<-}", 1);
    DecisionQueueController::AddDecision($player, "CUSTOM", "AfterFighterPlayed|-", 1);
    DecisionQueueController::AddDecision($player, "CUSTOM", "CardPlayed|" . $sourceObject->CardID, 1);
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
    $gates = GetGates($turnPlayer);
    $topGates = GetTopCard($gates);
    echo("Gates: " . $gates . "\n");
    echo("Top gate controller: " . ($topGates !== null ? $topGates->Controller : "null") . "\n");
    if($topGates !== null && $topGates->Controller != $turnPlayer) {
        //Siege happened
        $deck = &GetDeck($turnPlayer);
        if(count($deck) == 0) {
            SetFlashMessage("Player " . $topGates->Controller . " has won!");
        }
        else {
            SetFlashMessage("Player " . $turnPlayer . " was sieged!");
        }
    } else {
        SetFlashMessage("Player " . $turnPlayer . "'s turn has begun");
        Draw($turnPlayer, amount: 1);
    }
}

function GetGates($player) {
    if($player == 1) {
        return "BG2";
    } else {
        return "BG8";
    }
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
    $destZoneName = explode("-", $lastResult)[0];
    $fromZoneName = explode("-", $param[0])[0];
    $fromZone = &GetZone($fromZoneName);
    $destZone = &GetZone($destZoneName);
    for($i = 1; $i < count($fromZone); ++$i) {
        $fromZone[$i]->Status = 1; // Exhaust the unit
    }
    UseActions(amount:1);
    if(count($destZone) == 1) {
        //This is a move, move the whole stack from 1 -> end
        for($i = 1; $i < count($fromZone); ++$i) {
            MZMove($player, $fromZoneName . "-" . $i, $destZoneName);
        }
        return;
    } else {
        //This is an attack
        $fromTop = $fromZone[count($fromZone) - 1];
        $destTop = $destZone[count($destZone) - 1];
        $fromPower = CurrentCardPower($fromZone, $destZone, true);
        $destPower = CurrentCardPower($fromZone, $destZone, false);
        //Simple combat: Higher power wins, both are destroyed on a tie
        if($fromPower > $destPower) {
            //Attacker wins
            FighterDestroyed($destTop->Controller, $destZoneName . "-" . (count($destZone) - 1));
            if(count($destZone) == 2) { //Means there was only one defender
                //Move the whole stack
                for($i = 1; $i < count($fromZone); ++$i) {
                    MZMove($player, $fromZoneName . "-" . $i, $destZoneName);
                }
            }
        } else if($fromPower < $destPower) {
            //Defender wins
            FighterDestroyed($fromTop->Controller, $fromZoneName . "-" . (count($fromZone) - 1));
        } else {
            //Both destroyed
            FighterDestroyed($destTop->Controller, $destZoneName . "-" . (count($destZone) - 1));
            FighterDestroyed($fromTop->Controller, $fromZoneName . "-" . (count($fromZone) - 1));
        }
    }
};

function CurrentCardPower($fromZone, $destZone, $isAttacker=false) {
    $fromTop = $fromZone[count($fromZone) - 1];
    $destTop = $destZone[count($destZone) - 1];
    $thisCard = $isAttacker ? $fromTop : $destTop;
    $totalPower = CardPower($thisCard->CardID);
    //Self power modifiers
    switch($thisCard->CardID) {
        case "RYBF1DSKH": case "RYBF2DSKH": case "RYBF3DSKH": //Dusklight Hunter
            if($isAttacker && TraitContains($destTop, "Brute")) {
                $totalPower += 1;
            }
            break;
        case "RYBF1DWNB": case "RYBF2DWNB": case "RYBF3DWNB": //Dawnbringer Brute
            if($isAttacker && TraitContains($destTop, "Soldier")) {
                $totalPower += 1;
            }
            break;
        case "RYBF1SLSD": case "RYBF2SLSD": case "RYBF3SLSD": //Solaran Soldier
            if($isAttacker && TraitContains($destTop, "Hunter")) {
                $totalPower += 1;
            }
            break;
        default: break;
    }
    $adjacentZones = AdjacentZones($thisCard->Location);
    foreach($adjacentZones as $zoneName) {
        $totalPower += AdjacentZonePowerModifiers($fromTop, $destTop, $zoneName, $totalPower, $isAttacker);
    }
    return $totalPower;
}

function AdjacentZonePowerModifiers($fromTop, $toTop, $checkZone, $currentPower, $isAttacker=false) {
    $modifier = 0;
    $checkTop = GetTopCard($checkZone);
    if($checkTop === null || $toTop === null) return $modifier;
    switch($checkTop->CardID) {
        case "RYBF1HLMSH"://Luminous Shieldsman
            if($isAttacker == false && $checkTop->Controller == $toTop->Controller && $currentPower < 3) {
                $modifier += 1;
            }
            break;
        default: break;
    }
    return $modifier;
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

$customDQHandlers["AbilityActivated"] = function($player, $param, $lastResult) {
    global $activateAbilityAbilities;
    $cardID = $param[0];
    if(isset($activateAbilityAbilities[$cardID])) {
        $activateAbilityAbilities[$cardID]($player);
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

function CardHasAbility($obj) {
    global $debugMode;
    if($debugMode) {
        return CardActivateAbilityCount($obj->CardID) > 0 ? 1 : 0;
    }
    $turnPlayer = &GetTurnPlayer();
    return $obj->Status == 2 && $turnPlayer == $obj->Controller && CardActivateAbilityCount($obj->CardID) > 0 ? 1 : 0;
}

    // return json_encode(['color' => 'rgba(255, 100, 100, 0.7)']); // Red highlight
    //return null;
function SelectionMetadata($obj) {
    // Only highlight cards during the ACT phase when the decision queue is empty
    // and the card belongs to the turn player
    
    $currentPhase = GetCurrentPhase();
    if ($currentPhase !== "ACT") {
        return json_encode(['highlight' => false]);
    }
    
    // Check if decision queue is empty
    $turnPlayer = &GetTurnPlayer();
    $decisionQueue = &GetDecisionQueue($turnPlayer);
    if (count($decisionQueue) > 0) {
        return json_encode(['highlight' => false]);
    }
    
    // Don't highlight terrain
    if ($obj->CardID == "GudnakTerrain") {
        return json_encode(['highlight' => false]);
    }

    if (isset($obj->Status) && $obj->Status != 2) { // Not ready
        if($obj->CardID != "RYBF1GFDG" && $obj->CardID != "RYBF2HSLRC") { //Gryffdogs and Solaran Cavalry can attack and move while exhausted
            return json_encode(['highlight' => false]);
        }
    }
    
    // Only highlight cards belonging to the turn player
    // Check both Controller (for BG zones) and PlayerID (for Hand zone)
    $owner = isset($obj->Controller) ? $obj->Controller : (isset($obj->PlayerID) ? $obj->PlayerID : null);
    if ($owner !== $turnPlayer) {
        return json_encode(['highlight' => false]);
    }
    
    // Return bright vibrant lime green highlight for valid selectable cards
    return json_encode(['color' => 'rgba(0, 255, 0, 0.95)']);
}

function SwapPosition($unit1, $unit2) {
    // Parse zone names from unit references (e.g., "BG4-0" -> "BG4")
    $zone1Name = explode("-", $unit1)[0];
    $zone2Name = explode("-", $unit2)[0];
    
    // Get references to both zones
    $zone1 = &GetZone($zone1Name);
    $zone2 = &GetZone($zone2Name);
    
    // Swap the entire zone contents (all cards in both positions)
    $temp = $zone1;
    $zone1 = $zone2;
    $zone2 = $temp;
}

function AdjacentZones($zone) {
    switch($zone) {
        case "BG1": return ["BG2", "BG4"];
        case "BG2": return ["BG1", "BG3", "BG5"];
        case "BG3": return ["BG2", "BG6"];
        case "BG4": return ["BG1", "BG5", "BG7"];
        case "BG5": return ["BG2", "BG4", "BG6", "BG8"];
        case "BG6": return ["BG3", "BG5", "BG9"];
        case "BG7": return ["BG4", "BG8"];
        case "BG8": return ["BG5", "BG7", "BG9"];
        case "BG9": return ["BG6", "BG8"];
        default: return [];
    }
}

function GetTopCard($zoneName) {
    $zone = &GetZone($zoneName);
    if(count($zone) > 1) {
        $topIndex = count($zone) - 1;
        return $zone[$topIndex];
    }
    return null;
}

function DestroyTopCard($zoneName) {
    $zone = &GetZone($zoneName);
    if(count($zone) > 1) {
        $topIndex = count($zone) - 1;
        FighterDestroyed($zone[$topIndex]->Controller, $zoneName . "-" . $topIndex);
    }
}

?>