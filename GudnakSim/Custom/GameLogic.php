<?php

include_once __DIR__ . '/CardLogic.php';

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
    $zones = ["BG1", "BG2", "BG3", "BG4", "BG5", "BG6", "BG7", "BG8", "BG9"];
    $sourceObject = &GetZoneObject($mzCard);
    switch(CardCard_type($sourceObject->CardID)) {
        case "Fighter":
            DoPlayFighter($player, $mzCard);
            UseActions(amount:1);
            break;
        case "Tactic":
            $newObj = MZMove($player, $mzCard, "myGraveyard");
            DecisionQueueController::CleanupRemovedCards();
            $customDQHandlers["CardPlayed"]($player, [$sourceObject->CardID], null);
            $actionCost = CardCost($sourceObject->CardID);
            foreach($zones as $zoneName) {
                $actionCost += BattlefieldCostReductions($player, $zoneName, $sourceObject->CardID);
            }
            if($actionCost < 0) $actionCost = 0;
            UseActions(amount:$actionCost);
            break;
        default: break;
    }
    //My played card effects
    foreach($zones as $zoneName) {
        CardPlayedEffects($player, GetTopCard($zoneName), $sourceObject->CardID);
    }

    $dqController = new DecisionQueueController();
    $dqController->ExecuteStaticMethods($player, "-");
}

function BattlefieldCostReductions($player, $zoneName, $cardPlayed) {
    $modifier = 0;
    $zoneArr = &GetZone($zoneName);
    foreach($zoneArr as $index => $obj) {
        switch($obj->CardID) {
            case "RYBF2SNSR"://Sunseer
                if($obj->Controller == $player && CardCard_type($cardPlayed) == "Tactic") {
                    $modifier -= 1;
                }
                break;
            default: break;
        }
    }
    return $modifier;
}

function CardPlayedEffects($player, $card, $cardPlayed) {
    if($card === null) return;
    switch($card->CardID) {
        case "RYBF1HKNLM"://Kennel Master
            if($card->Controller == $player && $card->Status == 2 && CardCard_type($cardPlayed) == "Tactic") {
                AddHand($player, "RYBF1GFDG"); //Gryffdog
            }
            break;
        default: break;
    }
}

function DoActivatedAbility($player, $mzCard, $abilityIndex = 0) {
    global $customDQHandlers;
    $sourceObject = &GetZoneObject($mzCard);
    $cardID = $sourceObject->CardID;
    
    // Ability index is now passed directly from the frontend button click
    $selectedAbilityIndex = intval($abilityIndex);
    
    switch(CardCard_type($sourceObject->CardID)) {
        case "Fighter":
            $sourceObject->Status = 1; // Exhaust the unit
            break;
        default: break;
    }
    //My activated ability effects
    $customDQHandlers["AbilityActivated"]($player, [$sourceObject->CardID, $selectedAbilityIndex], null);

    $dqController = new DecisionQueueController();
    $dqController->ExecuteStaticMethods($player, "-");
    UseActions(amount:1);
}

function DoFighterAction($player, $cardZone, $includeMove = true, $includeAttack = true, $shouldExhaust = true, $ignoreActionCost = false) {
    $cardZone = explode("-", $cardZone)[0];
    $selectedCard = GetTopCard($cardZone);
    if($selectedCard->Controller != $player) {
        //TODO: Should only apply to Gates
        DoDefendAction($player, $cardZone);
        return;
    }
    if($selectedCard->CardID == "DNBF3HNLKS") { //Nihl'othrakis
        $includeMove = false;
    }
    $includeDiagonals = PlayerHasCard($player, "SHBF3HELVV"); //Elven Valkyrie
    $adjacentZones = AdjacentZones($cardZone, $includeDiagonals);
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
    
    // Check for card-specific attack targets
    $cardSpecificTargets = GetCardSpecificAttackTargets($player, $cardZone, $selectedCard->CardID, $includeAttack, $includeDiagonals);
    foreach($cardSpecificTargets as $zone) {
        if(!in_array($zone, $legalZones)) {
            array_push($legalZones, $zone);
        }
    }
    if(count($legalZones) == 0) {
        SetFlashMessage("No legal actions available for this fighter.");
        return;
    }
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $legalZones), 1);
    DecisionQueueController::AddDecision($player, "CUSTOM", "FighterAction|" . $cardZone . "|" . ($shouldExhaust ? "1" : "0") . "|" . ($ignoreActionCost ? "1" : "0"), 1);
}

function DoDefendAction($player, $cardZone) {
    $topCard = GetTopCard($cardZone);
    DiscardCards($player, amount:CardPower($topCard->CardID));
    FighterDestroyed($topCard->Controller, $cardZone . "-" . $topCard->mzIndex);
    UseActions(amount:1);
}

function CanAttack($player, $fromZone, $toZone) {
    $fromZoneArr = &GetZone($fromZone);
    $toZoneArr = &GetZone($toZone);
    if(count($fromZoneArr) > 1 && count($toZoneArr) > 1) {
        $fromTop = $fromZoneArr[count($fromZoneArr) - 1];
        $toTop = $toZoneArr[count($toZoneArr) - 1];
        if($fromTop->Controller != $player || $toTop->Controller == $player) return false;
        if($toTop->HasTurnEffects("RYBF1HBTCS") || $toTop->HasTurnEffects("RYBTDVPT")) return false;
        if(GlobalEffectCount($toTop->Controller, "GMBTWHTT") > 0) return false;
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
    $zones = ["BG1", "BG2", "BG3", "BG4", "BG5", "BG6", "BG7", "BG8", "BG9"];
    switch($cardID) {
        case "RYBF3HBLGR"://Bullgryff Rider
            foreach($zones as $zoneName) {
                $zoneArr = &GetZone($zoneName);
                if(count($zoneArr) == 1) {
                    $adjZones = AdjacentZones($zoneName);
                    foreach($adjZones as $adjZone) {
                        $topCard = GetTopCard($adjZone);
                        if($topCard !== null && $topCard->Controller == $player) {
                            array_push($legalZones, $zoneName);
                            break;
                        }
                    }
                }
            }
            break;
        default: break;
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

    ExpireEffects(isEndTurn:true);
    $turnPlayer = ($turnPlayer == 1) ? 2 : 1;

    $actions = &GetActions($turnPlayer);
    $actions = 2;

    if ($turnPlayer == $firstPlayer) {
        ++$currentTurn;
    }

    $bg1 = &GetZone("BG1");
    foreach($bg1 as $index => $obj) {
        if($obj->Controller == $turnPlayer) $obj->Status = 2;
        if(ObjectHasEffect($obj, "SHBF2HGRCK") || ObjectHasEffect($obj, "SHBTSNAR")) $obj->Status = 1;
    }
    $bg2 = &GetZone("BG2");
    foreach($bg2 as $index => $obj) {
        if($obj->Controller == $turnPlayer) $obj->Status = 2;
        if(ObjectHasEffect($obj, "SHBF2HGRCK") || ObjectHasEffect($obj, "SHBTSNAR")) $obj->Status = 1;
    }
    $bg3 = &GetZone("BG3");
    foreach($bg3 as $index => $obj) {
        if($obj->Controller == $turnPlayer) $obj->Status = 2;
        if(ObjectHasEffect($obj, "SHBF2HGRCK") || ObjectHasEffect($obj, "SHBTSNAR")) $obj->Status = 1;
    }
    $bg4 = &GetZone("BG4");
    foreach($bg4 as $index => $obj) {
        if($obj->Controller == $turnPlayer) $obj->Status = 2;
        if(ObjectHasEffect($obj, "SHBF2HGRCK") || ObjectHasEffect($obj, "SHBTSNAR")) $obj->Status = 1;
    }
    $bg5 = &GetZone("BG5");
    foreach($bg5 as $index => $obj) {
        if($obj->Controller == $turnPlayer) $obj->Status = 2;
        if(ObjectHasEffect($obj, "SHBF2HGRCK") || ObjectHasEffect($obj, "SHBTSNAR")) $obj->Status = 1;
    }
    $bg6 = &GetZone("BG6");
    foreach($bg6 as $index => $obj) {
        if($obj->Controller == $turnPlayer) $obj->Status = 2;
        if(ObjectHasEffect($obj, "SHBF2HGRCK") || ObjectHasEffect($obj, "SHBTSNAR")) $obj->Status = 1;
    }
    $bg7 = &GetZone("BG7");
    foreach($bg7 as $index => $obj) {
        if($obj->Controller == $turnPlayer) $obj->Status = 2;
        if(ObjectHasEffect($obj, "SHBF2HGRCK") || ObjectHasEffect($obj, "SHBTSNAR")) $obj->Status = 1;
    }   
    $bg8 = &GetZone("BG8");
    foreach($bg8 as $index => $obj) {
        if($obj->Controller == $turnPlayer) $obj->Status = 2;
        if(ObjectHasEffect($obj, "SHBF2HGRCK") || ObjectHasEffect($obj, "SHBTSNAR")) $obj->Status = 1;
    }
    $bg9 = &GetZone("BG9");
    foreach($bg9 as $index => $obj) {
        if($obj->Controller == $turnPlayer) $obj->Status = 2;
        if(ObjectHasEffect($obj, "SHBF2HGRCK") || ObjectHasEffect($obj, "SHBTSNAR")) $obj->Status = 1;
    }
    ExpireEffects(isEndTurn:false);
}

$customDQHandlers = [];

$customDQHandlers["AfterFighterPlayed"] = function($player, $param, $lastResult) {
    $zoneName = explode("-", $lastResult)[0];
    $zoneArr = &GetZone($zoneName);
    if (!empty($zoneArr)) {
        $lastIndex = count($zoneArr) - 1;
        $mzIndex = $zoneName . "-" . $lastIndex;
        DecisionQueueController::StoreVariable("mzID", $mzIndex);
        $target = &GetZoneObject($mzIndex);
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
    if($param[1] == "1") {
        for($i = 1; $i < count($fromZone); ++$i) {
            $fromZone[$i]->Status = 1; // Exhaust the unit
        }
    }
    if($param[2] == "0") UseActions(amount:1);
    if(count($destZone) == 1) {
        //This is a move, move the whole stack from 1 -> end
        for($i = 1; $i < count($fromZone); ++$i) {
            MZMove($player, $fromZoneName . "-" . $i, $destZoneName);
        }
        return;
    } else {
        ResolveAttack($fromZoneName, $destZoneName);
    }
};

function ResolveAttack($fromZoneName, $destZoneName) {
    $destZoneName = explode("-", $destZoneName)[0];
    $fromZoneName = explode("-", $fromZoneName)[0];
    $fromZone = &GetZone($fromZoneName);
    $destZone = &GetZone($destZoneName);
    $fromTop = $fromZone[count($fromZone) - 1];
    $destTop = $destZone[count($destZone) - 1];
    $fromPower = CurrentCardPower($fromZone, $destZone, true);
    $destPower = CurrentCardPower($fromZone, $destZone, false);
    if($fromPower > $destPower) {
        //Attacker wins
        FighterDestroyed($destTop->Controller, $destZoneName . "-" . (count($destZone) - 1));
        if(count($destZone) == 2) { //Means there was only one defender
            if($fromTop->CardID != "SHBF2HMSTH" && $fromTop->CardID != "SHBF3HELVV") MoveStack($fromZoneName, $destZoneName);
        }
    } else if($fromPower < $destPower) {
        //Defender wins
        FighterDestroyed($fromTop->Controller, $fromZoneName . "-" . (count($fromZone) - 1));
    } else {
        //Both destroyed
        echo($destZoneName . "-" . (count($destZone) - 1));
        echo($fromZoneName . "-" . (count($fromZone) - 1));
        FighterDestroyed($destTop->Controller, $destZoneName . "-" . (count($destZone) - 1));
        FighterDestroyed($fromTop->Controller, $fromZoneName . "-" . (count($fromZone) - 1));
    }
}

function MoveStack($fromZone, $toZone) {
    $fromZoneName = explode("-", $fromZone)[0];
    $toZoneName = explode("-", $toZone)[0];
    $fromZoneArr = &GetZone($fromZoneName);
    if(count($fromZoneArr) <= 1) return;
    $player = $fromZoneArr[1]->Controller;
    for($i = 1; $i < count($fromZoneArr); ++$i) {
        MZMove($player, $fromZoneName . "-" . $i, $toZoneName);
    }
}

function CurrentCardPower($fromZone, $destZone, $isAttacker=false) {
    global $doesGlobalEffectApply;
    $fromTop = $fromZone[count($fromZone) - 1];
    $destTop = $destZone[count($destZone) - 1];
    $thisCard = $isAttacker ? $fromTop : $destTop;
    $totalPower = CardPower($thisCard->CardID);
    //Self power modifiers
    switch($thisCard->CardID) {
        case "RYBF1DSKH": case "RYBF2DSKH": case "RYBF3DSKH": //Dusklight Hunter
        case "GMBF1SPCH": case "GMBF2SPCH": case "GMBF3SPCH": //Spectral Hunter
        case "SHBF1GBHT": case "SHBF2GBHT": case "SHBF3GBHT": //Goblin Hunter
        case "DNBF1CHNT": case "DNBF2CHNT": case "DNBF3CHNT": //Echo Hunter
            if($isAttacker && TraitContains($destTop, "Brute")) {
                $totalPower += 1;
            }
            break;
        case "RYBF1DWNB": case "RYBF2DWNB": case "RYBF3DWNB": //Dawnbringer Brute
        case "GMBF1AMBT": case "GMBF2AMBT": case "GMBF3AMBT": //Amalgam Brute
        case "SHBF1OGRB": case "SHBF2OGRB": case "SHBF3OGRB": //Ogre Brute
        case "DNBF1CSHB": case "DNBF2CSHB": case "DNBF3CSHB": //Crusher Brute
            if($isAttacker && TraitContains($destTop, "Soldier")) {
                $totalPower += 1;
            }
            break;
        case "RYBF1SLSD": case "RYBF2SLSD": case "RYBF3SLSD": //Solaran Soldier
        case "GMBF1SKLS": case "GMBF2SKLS": case "GMBF3SKLS": //Spectral Soldier
        case "SHBF1ORCS": case "SHBF2ORCS": case "SHBF3ORCS": //Orc Soldier
        case "DNBF1DRKS": case "DNBF2DRKS": case "DNBF3DRKS": //Deeprock Soldier
            if($isAttacker && TraitContains($destTop, "Hunter")) {
                $totalPower += 1;
            }
            break;
        case "DNBF2HFGFT"://Forgefather
            $hand = &GetHand($thisCard->Controller);
            if(count($hand) >= 6) {
                $totalPower += 1;
            }
            break;
        default: break;
    }
    $adjacentZones = AdjacentZones($thisCard->Location);
    foreach($adjacentZones as $zoneName) {
        $totalPower += AdjacentZonePowerModifiers($fromTop, $destTop, $zoneName, $totalPower, $isAttacker);
    }
    $cardCurrentEffects = explode(",", CardCurrentEffects($thisCard));
    //First effects that set power to specific value
    foreach($cardCurrentEffects as $effectID) {
        switch($effectID) {
            case "GMBF3HVRKG"://The Everking
                $totalPower = 1;
                break;
            default: break;
        }
    }
    //Now effects that modify power
    foreach($cardCurrentEffects as $effectID) {
        switch($effectID) {
            case "RYBTPDRL"://Precision Drills
                if($totalPower < 3) $totalPower += 1;
                break;
            case "SHBF1HELDR"://Elven Druid
                $totalPower += 2;
                break;
            case "DNBTSCTN"://Secret Tunnel
                $totalPower += 1;
                break;
            default: break;
        }
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

function DoSacrificeFighter($player, $mzCard) {
    
    FighterDestroyed($player, $mzCard);
}

function DoFighterDestroyed($player, $mzCard) {
    $card = &GetZoneObject($mzCard);
    switch($card->CardID) {
        case "GMBF1HNDMN"://Undying Minion
            $deck = &GetDeck($card->Controller);
            MZMove($player, $mzCard, "myHand");
            return;
        case "GMBF2HDTHK"://Death Knight
            if(GlobalEffectCount($card->Controller, "GMBF2HDTHK") == 0) {
                AddGlobalEffects($card->Controller, "GMBF2HDTHK");
                MZMove($player, $mzCard, "myHand");
                return;
            }
            $deck = &GetDeck($card->Controller);
            break;
        default: break;
    }
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
    $handlerName = $cardID . ":0";
    if(isset($playCardAbilities[$handlerName])) {
        $playCardAbilities[$handlerName]($player);
    }
};

$customDQHandlers["AbilityActivated"] = function($player, $param, $lastResult) {
    global $activateAbilityAbilities;
    $cardID = $param[0];
    $abilityIndex = isset($param[1]) ? intval($param[1]) : 0;
    // Use CardID:Index as the key for ability lookup
    $abilityKey = $cardID . ":" . $abilityIndex;
    if(isset($activateAbilityAbilities[$abilityKey])) {
        $activateAbilityAbilities[$abilityKey]($player);
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

function CardCurrentEffects($obj) {
    global $doesGlobalEffectApply, $effectAppliesToBoth,$playerID;
    //Start with this object's effects
    $effects = $obj->TurnEffects;
    //Now add global effects
    if($obj->Controller != -1) {
        $controllerEffects = $obj->Controller == $playerID ? GetZone("myGlobalEffects") : GetZone("theirGlobalEffects");
        foreach($controllerEffects as $index => $effectObj) {
            if(!isset($doesGlobalEffectApply[$effectObj->CardID]) || $doesGlobalEffectApply[$effectObj->CardID]($obj)) {
                array_push($effects, $effectObj->CardID);
            }
        }
        $otherEffects = $obj->Controller != $playerID ? GetZone("myGlobalEffects") : GetZone("theirGlobalEffects");
        foreach($otherEffects as $index => $effectObj) {
            if(isset($effectAppliesToBoth[$effectObj->CardID]) && (!isset($doesGlobalEffectApply[$effectObj->CardID]) || $doesGlobalEffectApply[$effectObj->CardID]($obj))) {
                array_push($effects, $effectObj->CardID);
            }
        }
    }
    return implode(",", $effects);
}

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
    
    // Only highlight cards belonging to the turn player, except for defend action
    $owner = isset($obj->Controller) ? $obj->Controller : (isset($obj->PlayerID) ? $obj->PlayerID : null);
    if ($owner !== $turnPlayer) {
        if(IsGates($turnPlayer, $obj->Location)) {
            return json_encode(['color' => 'rgba(255, 0, 0, 0.95)']);
        }
        return json_encode(['highlight' => false]);
    }
    
    if (isset($obj->Status) && $obj->Status != 2) { // Not ready
        if(!CanActExhausted($obj)) {
            return json_encode(['highlight' => false]);
        }
    }
    
    // Return bright vibrant lime green highlight for valid selectable cards
    return json_encode(['color' => 'rgba(0, 255, 0, 0.95)']);
}

function CanActExhausted($obj) {
    if($obj->CardID == "RYBF1GFDG" || $obj->CardID == "RYBF2HSLRC") return true; //Gryffdogs and Solaran Cavalry can act while exhausted
    if(TraitContains($obj, "Hunter") && PlayerHasCard($obj->Controller, "SHBF2HMSTH") || GlobalEffectCount($obj->Controller, "SHBTTRBZ") > 0) { //Master Hunter or Trailblaze effect
        return true;
    }
    return false;
}

function IsGates($player, $zoneName) {
    $zoneName = explode("-", $zoneName)[0];
    $gates = GetGates($player);
    return $zoneName == $gates;
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

function AdjacentZones($zone, $includeDiagonals=false) {
    switch($zone) {
        case "BG1": return $includeDiagonals ? ["BG2", "BG4", "BG5"] : ["BG2", "BG4"];
        case "BG2": return $includeDiagonals ? ["BG1", "BG3", "BG4", "BG5", "BG6"] : ["BG1", "BG3", "BG5"];
        case "BG3": return $includeDiagonals ? ["BG2", "BG6", "BG5"] : ["BG2", "BG6"];
        case "BG4": return $includeDiagonals ? ["BG1", "BG2", "BG5", "BG7", "BG8"] : ["BG1", "BG5", "BG7"];
        case "BG5": return $includeDiagonals ? ["BG2", "BG4", "BG6", "BG8", "BG1", "BG3", "BG7", "BG9"] : ["BG2", "BG4", "BG6", "BG8"];
        case "BG6": return $includeDiagonals ? ["BG3", "BG5", "BG9", "BG5"] : ["BG3", "BG5", "BG9"];
        case "BG7": return $includeDiagonals ? ["BG4", "BG8", "BG5"] : ["BG4", "BG8"];
        case "BG8": return $includeDiagonals ? ["BG4", "BG5", "BG6", "BG7", "BG9"] : ["BG5", "BG7", "BG9"];
        case "BG9": return $includeDiagonals ? ["BG6", "BG8", "BG5"] : ["BG6", "BG8"];
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

function GetBattlefieldCardIDs($zoneName) {
    $zone = &GetZone($zoneName);
    $cards = [];
    for($i = 1; $i < count($zone); ++$i) {
        array_push($cards, $zone[$i]->CardID);
    }
    return $cards;
}

function DestroyTopCard($zoneName) {
    $zone = &GetZone($zoneName);
    if(count($zone) > 1) {
        $topIndex = count($zone) - 1;
        FighterDestroyed($zone[$topIndex]->Controller, $zoneName . "-" . $topIndex);
    }
}

function RearrangeBattlefield($zone, $order) {
    $order = explode("=", $order)[1];
    $orderArr = explode(",", $order);
    $zoneArr = &GetZone($zone);
    $newZoneArr = [$zoneArr[0]]; // Keep the terrain card at index 0
    foreach($orderArr as $cardID) {
        // Find the card object with this ID in the zone
        for($i = 1; $i < count($zoneArr); ++$i) {
            if($zoneArr[$i]->CardID === $cardID) {
                array_push($newZoneArr, $zoneArr[$i]);
                break;
            }
        }
    }
    $zoneArr = $newZoneArr;
}

function UnoccupiedBattlefields() {
    $unoccupied = [];
    $zones = ["BG1", "BG2", "BG3", "BG4", "BG5", "BG6", "BG7", "BG8", "BG9"];
    foreach($zones as $zoneName) {
        $zoneArr = &GetZone($zoneName);
        if(count($zoneArr) == 1) {
            array_push($unoccupied, $zoneName);
        }
    }
    return $unoccupied;
}

function PlayerHasCard($player, $cardID) {
    $zones = ["BG1", "BG2", "BG3", "BG4", "BG5", "BG6", "BG7", "BG8", "BG9"];
    foreach($zones as $zoneName) {
        $topCard = GetTopCard($zoneName);
        if($topCard !== null && $topCard->Controller == $player && $topCard->CardID == $cardID) {
            return true;
        }
    }
    return false;
}

function BattlefieldSearch($zoneOnly=true, $controller=null, $minBasePower=null, $maxBasePower=null, $adjacentTo=null, $emptyOnly=false, $minFighters=null, $maxFighters=null, $excludeGates=null, $hasTrait=null, $excludeTrait=null) {
    if($adjacentTo !== null) $adjacentTo = explode("-", $adjacentTo)[0];
    $results = [];
    $zones = ["BG1", "BG2", "BG3", "BG4", "BG5", "BG6", "BG7", "BG8", "BG9"];
    $gatesZone = $excludeGates != null ? GetGates($excludeGates) : null; // Get gates zone to exclude if needed
    foreach($zones as $zoneName) {
        // Skip gates zone if excludeGates is true
        if($excludeGates && $zoneName == $gatesZone) {
            continue;
        }
        
        $zoneArr = &GetZone($zoneName);
        
        // If emptyOnly is true, only consider empty zones (count == 1 means only terrain)
        if($emptyOnly && count($zoneArr) != 1) {
            continue;
        }
        
        // Calculate number of fighters (total count - 1 for terrain)
        $numFighters = count($zoneArr) - 1;
        
        // Apply minFighters filter
        if($minFighters !== null && $numFighters < $minFighters) {
            continue;
        }
        
        // Apply maxFighters filter
        if($maxFighters !== null && $numFighters > $maxFighters) {
            continue;
        }
        
        for($i = $zoneOnly ? count($zoneArr)-1 : 1; $i < count($zoneArr); ++$i) {
            $obj = $zoneArr[$i];
            if(($controller === null || $obj->Controller == $controller) &&
               ($minBasePower === null || CardPower($obj->CardID) >= $minBasePower) &&
               ($maxBasePower === null || CardPower($obj->CardID) <= $maxBasePower) &&
               ($adjacentTo === null || in_array($zoneName, AdjacentZones($adjacentTo))) &&
               ($hasTrait === null || TraitContains($obj, $hasTrait)) &&
               ($excludeTrait === null || !TraitContains($obj, $excludeTrait))) {
                if($zoneOnly) {
                    if(!in_array($zoneName, $results)) {
                        array_push($results, $zoneName);
                    }
                    // No need to check other cards in this zone when only zone names are requested
                    break;
                } else {
                    array_push($results, $zoneName . "-" . $i);
                }
            }
        }
    }
    return $results;
}

function ZoneSearch($zoneName, $minBasePower=null, $maxBasePower=null, $hasTrait=null) {
    $results = [];
    $zoneArr = &GetZone($zoneName);
    for($i = 0; $i < count($zoneArr); ++$i) {
        $obj = $zoneArr[$i];
        echo($obj->CardID . " " . ($hasTrait ? "yes" : "no") . " " . (TraitContains($obj, $hasTrait) ? "yes" : "no") . " \n");
        if(($minBasePower === null || CardPower($obj->CardID) >= $minBasePower) &&
           ($maxBasePower === null || CardPower($obj->CardID) <= $maxBasePower) &&
           ($hasTrait === null || TraitContains($obj, $hasTrait))) {
            array_push($results, $zoneName . "-" . $i);
        }
    }
    return $results;
}

function ZoneCardSearch($zoneName, $cardID) {
    $zoneName = explode("-", $zoneName)[0];
    $results = [];
    $zoneArr = &GetZone($zoneName);
    for($i = 0; $i < count($zoneArr); ++$i) {
        $obj = $zoneArr[$i];
        if($obj->CardID == $cardID) {
            array_push($results, $zoneName . "-" . $i);
        }
    }
    return $results;
}

function DiscardCards($player, $amount=1) {
    for($i = 0; $i < $amount; ++$i) {
        DecisionQueueController::AddDecision($player, "MZCHOOSE", ZoneMZIndices("myHand"), 1);
        DecisionQueueController::AddDecision($player, "MZMOVE", "{<-}->myGraveyard", 1);
    }
}

function ExpireEffects($isEndTurn=true) {
    $turnPlayer = &GetTurnPlayer();
    global $untilBeginTurnEffects, $foreverEffects;
    $zones = ["BG1", "BG2", "BG3", "BG4", "BG5", "BG6", "BG7", "BG8", "BG9"];
    foreach($zones as $zoneName) {
        $zoneArr = &GetZone($zoneName);
        foreach($zoneArr as $index => $obj) {
            if($obj->Controller != $turnPlayer) continue;
            $newEffects = [];
            foreach($obj->TurnEffects as $effect) {
                if($isEndTurn && isset($untilBeginTurnEffects[$effect])) { //Effects that last until end of turn
                    array_push($newEffects, $effect);
                }
            }
            $obj->TurnEffects = $newEffects;
        }
    }
    //Global effects
    if($isEndTurn) {
        $globalEffects = &GetZone("myGlobalEffects");
    } else {
        $globalEffects = &GetZone("theirGlobalEffects");
    }
    $newGlobalEffects = [];
    foreach($globalEffects as $index => $effectObj) {
        if(isset($foreverEffects[$effectObj->CardID]) || ($isEndTurn && isset($untilBeginTurnEffects[$effectObj->CardID]))) {
            array_push($newGlobalEffects, $effectObj);
        }
    }
    $globalEffects = $newGlobalEffects;
}

$untilBeginTurnEffects["RYBF1HBTCS"] = true;
$untilBeginTurnEffects["RYBTPDRL"] = true;
$untilBeginTurnEffects["GMBF3HVRKG"] = true;
$untilBeginTurnEffects["GMBTWHTT"] = true;
$untilBeginTurnEffects["SHBF1HELDR"] = true;
$untilBeginTurnEffects["DNBTSCTN"] = true;//Secret Tunnel
$foreverEffects["GMBTMNTM"] = true;
$effectAppliesToBoth["GMBF3HVRKG"] = true;

$doesGlobalEffectApply["RYBTPDRL"] = function($obj) { //Precision Drills
    $zone = GetZone($obj->Location);
    return count($zone) > 2;
};

$doesGlobalEffectApply["GMBTMNTM"] = function($obj) { //Memento Mori
    return false;
};

$doesGlobalEffectApply["GMBF2HDTHK"] = function($obj) { //Death Knight
    return false;
};

$doesGlobalEffectApply["SHBTTRBZ"] = function($obj) { //Trailblaze
    return TraitContains($obj, "Hunter");
};

function GlobalEffectCount($player, $effectID) {
    $zoneArr = &GetGlobalEffects($player);
    $count = 0;
    foreach($zoneArr as $index => $obj) {
        if($obj->CardID == $effectID) {
            ++$count;
        }
    }
    return $count;
}

function ObjectHasEffect($obj, $targetEffect) {
    $cardCurrentEffects = explode(",", CardCurrentEffects($obj));
    //First effects that set power to specific value
    foreach($cardCurrentEffects as $effectID) {
        if($effectID == $targetEffect) {
            return true;
        }
    }
    return false;
}

?>