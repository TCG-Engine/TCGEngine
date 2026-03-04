<?php

$debugMode = true;
$customDQHandlers = [];

include_once __DIR__ . '/CardLogic.php';
include_once __DIR__ . '/CombatLogic.php';
include_once __DIR__ . '/MaterializeLogic.php';

//TODO: Add this to a schema
function ActionMap($actionCard)
{
    global $playerID;
    $turnPlayer = &GetTurnPlayer();
    $currentPhase = GetCurrentPhase();
    $cardArr = explode("-", $actionCard);
    $cardZone = $cardArr[0];
    $cardIndex = $cardArr[1];

    // Block all FSM actions while any player has pending DQ decisions
    // (Opportunity windows, ability choices, combat decisions, etc.)
    $dqController = new DecisionQueueController();
    if(!$dqController->AllQueuesEmpty()) return "";

    switch ($cardZone) {
        case "myHand":
            if($currentPhase == "MAIN" && $playerID == $turnPlayer) {
                // Turn player can play any card during their main phase
                ActivateCard($playerID, $actionCard, false);
                return "PLAY";
            }
            break;
        case "myField":
            if($playerID != $turnPlayer) break; // Only turn player can declare attacks
            $obj = &GetZoneObject($actionCard);
            $cardType = CardType($obj->CardID);
            if(PropertyContains($cardType, "ALLY") || PropertyContains($cardType, "CHAMPION")) {
                BeginCombatPhase($actionCard);
            }
            break;
        default: break;
    }
    return "";
}

function DoActivateCard($player, $mzCard, $ignoreCost = false) {
    $sourceObject = &GetZoneObject($mzCard);
    //1.1 Announcing Activation: First, the player announces the card they are activating and places it onto the effects stack.
    $obj = MZMove($player, $mzCard, "EffectStack");
    $obj->Controller = $player;

    //TODO: 1.2 Checking Elements: Then, the game checks whether the player has the required elements enabled to activate the card. If not, the activation is illegal.
    
    //TODO: 1.3 Declaring Costs: Next, the player declares the intended cost parameters for the card.

    //TODO: 1.4 Selecting Modes

    //TODO: 1.5 Declaring Targets

    //TODO: 1.6 Checking Legality

    //1.7 Calculating Reserve Cost
    $reserveCost = CardCost_reserve($obj->CardID);

    // Class Bonus: reduce cost if champion's class matches card's class
    $classBonusDiscount = ClassBonusActivateCostReduction($obj->CardID);
    if($classBonusDiscount > 0 && IsClassBonusActive($player, explode(",", CardClasses($obj->CardID)))) {
        $reserveCost = max(0, $reserveCost - $classBonusDiscount);
    }

    // Efficiency: reduce cost by the champion's current level
    global $Efficiency_Cards;
    if(isset($Efficiency_Cards[$obj->CardID])) {
        $myField = GetZone("myField");
        foreach($myField as $fieldObj) {
            if(PropertyContains(CardType($fieldObj->CardID), "CHAMPION")) {
                $champLevel = ObjectCurrentLevel($fieldObj);
                $reserveCost = max(0, $reserveCost - $champLevel);
                break;
            }
        }
    }

    //1.8 Paying Costs
    for($i = 0; $i < $reserveCost; ++$i) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
    }

    //1.9 Activation — grant Opportunity to the opponent before resolving
    // (The generated ActivateCard() wrapper calls ExecuteStaticMethods, which will
    //  process ReserveCard costs, then EffectStackOpportunity.)
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
}

$customDQHandlers["ReserveCard"] = function($player, $parts, $lastDecision) {
    ReserveCard($player);
};

function OnCardReserved($player, $mzCard) {
    $obj = MZMove($player, $mzCard, "myMemory");
}

$customDQHandlers["CardActivated"] = function($player, $parts, $lastDecision) {
    CardActivated($player, $parts[0]);
};

function OnCardActivated($player, $mzCard) {
    global $cardActivatedAbilities;
    $obj = GetZoneObject($mzCard);
    $cardType = CardType($obj->CardID);
    if(PropertyContains($cardType, "ALLY")) {
        $obj = MZMove($player, $mzCard, "myField");
        $obj->Controller = $player;
    } else if(PropertyContains($cardType, "ACTION")) {
        // Special case: Preserve cards go to Material zone
        if($obj->CardID == "2Ojrn7buPe") { // Tera Sight - Preserve
            $obj = MZMove($player, $mzCard, "myMaterial");
        } else {
            $obj = MZMove($player, $mzCard, "myGraveyard");
        }
    } else if(PropertyContains($cardType, "ATTACK")) {
        // Attack cards resolve and enter the champion's intent zone
        $obj = MZMove($player, $mzCard, "myIntent");
        $obj->Controller = $player;
    }
    DecisionQueueController::CleanupRemovedCards();
    if(isset($cardActivatedAbilities[$obj->CardID . ":0"])) {
        $cardActivatedAbilities[$obj->CardID . ":0"]($player);
    }
    // After an attack card enters intent and its abilities resolve, declare the champion attack
    if(PropertyContains($cardType, "ATTACK")) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "DeclareChampionAttack", 100);
    }
}

function DoPlayCard($player, $mzCard, $ignoreCost = false)
{
    global $customDQHandlers;
    $sourceObject = &GetZoneObject($mzCard);

    $dqController = new DecisionQueueController();
    $dqController->ExecuteStaticMethods($player, "-");
}

function CardPlayedEffects($player, $card, $cardPlayed) {
    if($card === null) return;
    switch($card->CardID) {
        
        default: break;
    }
}

function DoActivatedAbility($player, $mzCard, $abilityIndex = 0) {
    global $customDQHandlers;
    $sourceObject = &GetZoneObject($mzCard);
    $cardID = $sourceObject->CardID;
    
    // Ability index is now passed directly from the frontend button click
    $selectedAbilityIndex = intval($abilityIndex);
    
    // Exhaust the unit as the REST cost — only for static abilities, not dynamic ones (which have their own costs)
    $cardType = CardType($sourceObject->CardID);
    $staticAbilityCount = CardActivateAbilityCount($cardID);
    if($selectedAbilityIndex < $staticAbilityCount && (PropertyContains($cardType, "ALLY") || PropertyContains($cardType, "CHAMPION"))) {
        $sourceObject->Status = 1;
    }

    //My activated ability effects
    $customDQHandlers["AbilityActivated"]($player, [$sourceObject->CardID, $selectedAbilityIndex], null);

    // Enlighten activated ability: triggered when abilityIndex is beyond static count and champion has 3+ enlighten counters
    if($selectedAbilityIndex >= $staticAbilityCount && GetCounterCount($sourceObject, "enlighten") >= 3) {
        RemoveCounters($player, $mzCard, "enlighten", 3);
        Draw($player, 1);
    }

    // Queue Opportunity for the opponent to respond after the ability resolves.
    // Block 200 ensures it runs after all ability decisions (block 1-100).
    DecisionQueueController::AddDecision($player, "CUSTOM", "AbilityOpportunity", 200);

    $dqController = new DecisionQueueController();
    $dqController->ExecuteStaticMethods($player, "-");
}

function DoAllyDestroyed($player, $mzCard) {
    global $allyDestroyedAbilities;
    $obj = GetZoneObject($mzCard);
    $controller = $obj->Controller;
    $dest = $player == $controller ? "myGraveyard" : "theirGraveyard";
    MZMove($player, $mzCard, $dest);
    if(isset($allyDestroyedAbilities[$obj->CardID . ":0"])) {
        $allyDestroyedAbilities[$obj->CardID . ":0"]($controller);
    }
}

function WakeUpPhase() {
    // Wake Up phase
    SetFlashMessage("Wake Up Phase");
}

function OnEnter($player, $mzID) {
    global $enterAbilities;
    $obj = GetZoneObject($mzID);
    $CardID = $obj->CardID;
    DecisionQueueController::CleanupRemovedCards();
    if(isset($enterAbilities[$CardID . ":0"])) $enterAbilities[$CardID . ":0"]($player);
}

function FieldAfterAdd($player, $CardID="-", $Status=2, $Owner="-", $Damage=0, $Controller="-", $TurnEffects="-", $Counters="-") {
    $field = &GetField($player);
    $added = $field[count($field)-1];
    $added->Controller = $player;
    if($added->Owner == 0) $added->Owner = $player;
    
    // Crusader of Aesa (2Q60hBYO3i): enters the field rested
    if($added->CardID == "2Q60hBYO3i") {
        $added->Status = 1;
    }
    
    Enter($player, $field[count($field)-1]->GetMzID());
}

function RecollectionPhase() {
    // Recollection phase
    SetFlashMessage("Recollection Phase");
    $turnPlayer = &GetTurnPlayer();
    
    // Trigger recollection phase abilities for cards on the field
    $field = &GetField($turnPlayer);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed) {
            switch($field[$i]->CardID) {
                case "075L8pLihO": // Arima, Gaia's Wings: Put three buff counters on Arima
                    AddCounters($turnPlayer, "myField-" . $i, "buff", 3);
                    break;
                case "CvvgJR4fNa": // Patient Rogue: gets +3 POWER until end of turn
                    AddTurnEffect("myField-" . $i, "CvvgJR4fNa");
                    break;
                default: break;
            }
        }
    }
    
    $memory = &GetMemory($turnPlayer);
    for($i=count($memory)-1; $i>=0; --$i) {
        MZMove($turnPlayer, "myMemory-" . $i, "myHand");
    }
}

function DrawPhase() {
    // Draw phase - player draws a card
    $currentTurn = &GetTurnNumber();
    if($currentTurn == 1) return;//Don't draw on first turn
    $turnPlayer = &GetTurnPlayer();
    Draw($turnPlayer, amount: 1);
}

function MainPhase() {
    // Main phase - player can play cards and activate abilities
    SetFlashMessage("Main Phase");
}

function EndPhase() {
    $firstPlayer = &GetFirstPlayer();
    $currentTurn = &GetTurnNumber();
    $turnPlayer = &GetTurnPlayer();

    // Clear any remaining intent cards (unused attack cards) to graveyard
    ClearIntent($turnPlayer);

    $field = &GetField($turnPlayer);
    for($i=count($field)-1; $i>=0; --$i) {
        if(HasVigor($field[$i])) {
            $field[$i]->Status = 2; // Vigor units ready themselves at end of turn
        }
    }

    ExpireEffects(isEndTurn:true);
    $turnPlayer = ($turnPlayer == 1) ? 2 : 1;

    if ($turnPlayer == $firstPlayer) {
        ++$currentTurn;
    }

    ExpireEffects(isEndTurn:false);
}

function ObjectCurrentPower($obj) {
    $power = CardPower($obj->CardID);
    if($power === null || $power < 0) return 0; // No power stat — buff counters do not generate one
    // Buff counter modifier: +1 power per buff counter (applied before other modifiers)
    $power += GetCounterCount($obj, "buff");
    switch($obj->CardID) { //Self power modifiers
        case "HWFWO0TB8l"://Tempest Silverback
            if(IsClassBonusActive($obj->Controller, ["TAMER"])) {
                $power += 2;
            }
            break;
        case "JAs9SmLqUS"://Gildas, Chronicler of Aesal
            $memory = &GetMemory($obj->Controller);
            $hand = &GetHand($obj->Controller);
            if(count($memory) == count($hand)) $power += 3;
            break;
        case "7NMFSRR5V3"://Fervent Beastmaster: +1 POWER while you control a Beast ally
            global $playerID;
            $zone = $obj->Controller == $playerID ? "myField" : "theirField";
            if(!empty(ZoneSearch($zone, ["ALLY"], cardSubtypes: ["BEAST"]))) {
                $power += 1;
            }
            break;
        case "csMiEObm2l": // Strapping Conscript: [Class Bonus][Level 2+] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["WARRIOR"]) && PlayerLevel($obj->Controller) >= 2) {
                $power += 1;
            }
            break;
        case "LUfgfsWTTO": // Fiery Momentum: [Class Bonus] +1 POWER for each fire element card in your graveyard
            if(IsClassBonusActive($obj->Controller, ["WARRIOR"])) {
                global $playerID;
                $gravZone = $obj->Controller == $playerID ? "myGraveyard" : "theirGraveyard";
                $fireCards = ZoneSearch($gravZone, cardElements: ["FIRE"]);
                $power += count($fireCards);
            }
            break;
        case "FGvq4eQPbP": // Flame Sweep: [Class Bonus][Level 2+] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["WARRIOR"]) && PlayerLevel($obj->Controller) >= 2) {
                $power += 1;
            }
            break;
        default: break;
    }
    // Field-presence passives — Banner Knight gives +1 POWER to other allies and weapons
    if($obj->Controller != -1 && !PropertyContains(CardType($obj->CardID), "CHAMPION")) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fieldObj) {
            if($fieldObj->CardID === "IAkuSSnzYB") { // Banner Knight: [Class Bonus][Level 2+] Other allies and weapons get +1 POWER
                if($obj->CardID !== "IAkuSSnzYB" &&
                   (PropertyContains(CardType($obj->CardID), "ALLY") || PropertyContains(CardType($obj->CardID), "WEAPON")) &&
                   IsClassBonusActive($obj->Controller, ["WARRIOR"]) &&
                   PlayerLevel($obj->Controller) >= 2) {
                    $power += 1;
                }
                break; // Only count the first Banner Knight (duplicates don't stack)
            }
        }
    }
    $cardCurrentEffects = explode(",", CardCurrentEffects($obj));
    foreach($cardCurrentEffects as $effectID) {
        switch($effectID) {
            case "FCbKYZcbNq"://Trusty Steed
                $power += 2;
                break;
            case "Huh1DljE0j"://Second Wind
                $power += 1;
                break;
            case "1i6ierdDjq"://Flamelash Subduer activated ability: +2 POWER until end of turn
                $power += 2;
                break;
            case "4hbA9FT56L-2"://Song of Nurturing (Class Bonus): +1 POWER until end of turn
                $power += 1;
                break;
            case "k71PE3clOI": // Inspiring Call: allies get +1 POWER until end of turn
                $power += 1;
                break;
            case "CvvgJR4fNa": // Patient Rogue: +3 POWER from beginning of recollection phase
                $power += 3;
                break;
            case "fMv7tIOZwL-PWR": // Aqueous Enchanting: allies get +1 POWER until end of turn
                $power += 1;
                break;
            default: break;
        }
    }
    return $power;
}

function ObjectCurrentLevel($obj) {
    $cardLevel = CardLevel($obj->CardID);
    $cardCurrentEffects = explode(",", CardCurrentEffects($obj));
    foreach($cardCurrentEffects as $effectID) {
        switch($effectID) {
            case "9GWxrTMfBz"://Cram Session
                $cardLevel += 1;
                break;
            case "Kc5Bktw0yK"://Empowering Harmony
                $cardLevel += 2;
                break;
            case "gvXQa57cxe"://Shout at Your Pets: +1 level until end of turn
                $cardLevel += 1;
                break;
            case "dmfoA7jOjy"://Crystal of Empowerment: +2 level until end of turn
                $cardLevel += 2;
                break;
            default: break;
        }
    }
    // Field-presence passives — iterate once and switch on card ID
    // Each unique card's passive is only counted once (duplicates don't stack)
    if(PropertyContains(CardType($obj->CardID), "CHAMPION")) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        $appliedPassives = [];
        foreach($field as $fieldObj) {
            $fID = $fieldObj->CardID;
            if(isset($appliedPassives[$fID])) continue;
            switch($fID) {
                case "1i6ierdDjq": // Flamelash Subduer: +1 level while you control an Animal or Beast ally
                    if(!empty(ZoneSearch($zone, ["ALLY"], cardSubtypes: ["ANIMAL", "BEAST"]))) {
                        $cardLevel += 1;
                    }
                    $appliedPassives[$fID] = true;
                    break;
                default: break;
            }
        }
    }
    return $cardLevel;
}

function ObjectCurrentHP($obj) {
    $cardLife = CardLife($obj->CardID);
    if($cardLife === null || $cardLife < 0) return 0; // No life stat — buff counters do not generate one
    // Buff counter modifier: +1 life per buff counter (applied before other modifiers)
    $cardLife += GetCounterCount($obj, "buff");
    switch($obj->CardID) { //Self hp modifiers
        case "HWFWO0TB8l"://Tempest Silverback
            if(IsClassBonusActive($obj->Controller, ["TAMER"])) {
                $cardLife += 2;
            }
            break;
        case "7NMFSRR5V3"://Fervent Beastmaster: +1 LIFE while you control a Beast ally
            global $playerID;
            $zone = $obj->Controller == $playerID ? "myField" : "theirField";
            if(!empty(ZoneSearch($zone, ["ALLY"], cardSubtypes: ["BEAST"]))) {
                $cardLife += 1;
            }
            break;
        case "csMiEObm2l": // Strapping Conscript: [Class Bonus][Level 2+] +1 LIFE
            if(IsClassBonusActive($obj->Controller, ["WARRIOR"]) && PlayerLevel($obj->Controller) >= 2) {
                $cardLife += 1;
            }
            break;
        default: break;
    }
    $cardCurrentEffects = explode(",", CardCurrentEffects($obj));
    foreach($cardCurrentEffects as $effectID) {
        switch($effectID) {
            case "dsAqxMezGb"://Favorable Winds
                $cardLife += 1;
                break;
            case "4hbA9FT56L-1"://Song of Nurturing: +2 LIFE until end of turn
                $cardLife += 2;
                break;
            case "fMv7tIOZwL-LIF": // Aqueous Enchanting: allies get +1 LIFE until end of turn
                $cardLife += 1;
                break;
            case "hw8dxKAnMX": // Mist Resonance: allies get +1 LIFE until end of turn
                $cardLife += 1;
                break;
            case "hLHpI5rHIK": // Bauble of Mending class bonus: +1 LIFE until end of turn
                $cardLife += 1;
                break;
            default: break;
        }
    }
    return $cardLife;
}

function ObjectCurrentPowerDisplay($obj) {
    $cardPower = CardPower($obj->CardID);
    $currentCardPower = ObjectCurrentPower($obj);
    return $cardPower == $currentCardPower ? 0 : $currentCardPower;
}

function ObjectCurrentHPDisplay($obj) {
    $cardLife = CardLife($obj->CardID);
    $currentCardLife = ObjectCurrentHP($obj);
    return $cardLife == $currentCardLife ? 0 : $currentCardLife;
}


function ObjectCurrentLevelDisplay($obj) {
    if(!PropertyContains(CardType($obj->CardID), "CHAMPION")) {
        return 0;
    }
    $cardLevel = CardLevel($obj->CardID);
    $currentLevel = ObjectCurrentLevel($obj);
    return $cardLevel == $currentLevel ? 0 : $currentLevel;
}

function DoDrawCard($player, $amount=1) {
    $zone = &GetDeck($player);
    $hand = &GetHand($player);
    for($i=0; $i<$amount; ++$i) {
        if(count($zone) == 0) {
            return;
        }
        $card = array_shift($zone);
        array_push($hand, $card);
    }
}

/**
 * Glimpse N: show the top N cards of the player's deck and let them choose
 * which cards go back to the top vs. the bottom, in any order.
 * Queues an MZREARRANGE decision followed by a GlimpseApply custom handler.
 *
 * @param int $player The acting player.
 * @param int $amount Number of cards to glimpse.
 */
function Glimpse($player, $amount) {
    $zone = &GetDeck($player);
    $n = min($amount, count($zone));
    if($n == 0) return;

    // Collect the top N card IDs (they stay in the deck; we just show them)
    $cardIDs = [];
    for($i = 0; $i < $n; ++$i) {
        $cardIDs[] = $zone[$i]->CardID;
    }

    // Build MZREARRANGE param: all cards start in the Top pile
    $param = "Top=" . implode(",", $cardIDs) . ";Bottom=";

    // Remember how many cards are being glimpsed so the handler knows how many to remove
    DecisionQueueController::StoreVariable("glimpseCount", strval($n));

    DecisionQueueController::AddDecision($player, "MZREARRANGE", $param, 1, "Glimpse:_Top=return_to_top,_Bottom=put_on_bottom");
    DecisionQueueController::AddDecision($player, "CUSTOM", "GlimpseApply", 1);
}

function DoDiscardCard($player, $mzCard) {
    MZMove($player, $mzCard, "myGraveyard");
}

function DoRevealCard($player, $revealedMZ) {
    $obj = GetZoneObject($revealedMZ);
    if($obj === null) return null;
    $CardID = $obj->CardID;
    SetFlashMessage("Revealed: " . CardName($CardID));
    return $revealedMZ;
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

/**
 * Resolves a Glimpse decision. Called after the player submits their MZREARRANGE choice.
 * $lastDecision is the serialized pile string, e.g. "Top=cardA;Bottom=cardB,cardC".
 * Cards in the "Top" pile are placed on top of the deck (in order).
 * Cards in the "Bottom" pile are placed on the bottom of the deck (in order).
 */
$customDQHandlers["GlimpseApply"] = function($player, $parts, $lastDecision) {
    $zone = &GetDeck($player);
    $n = intval(DecisionQueueController::GetVariable("glimpseCount"));

    // Remove the top N cards from the deck — these are the ones the player viewed
    $removedCards = [];
    for($i = 0; $i < $n; ++$i) {
        if(count($zone) > 0) {
            $removedCards[] = array_shift($zone);
        }
    }

    // Build a map from cardID to the actual card object
    $cardMap = [];
    foreach($removedCards as $cardObj) {
        // A deck can have duplicates; map each ID to an array of objects
        $cardMap[$cardObj->CardID][] = $cardObj;
    }
    // Helper to pop one card object by ID from the map
    $popCard = function($cardID) use (&$cardMap) {
        if(!isset($cardMap[$cardID]) || count($cardMap[$cardID]) === 0) return null;
        return array_shift($cardMap[$cardID]);
    };

    // Parse the MZREARRANGE result into piles
    $piles = ["Top" => [], "Bottom" => []];
    $pileStrings = explode(";", $lastDecision);
    foreach($pileStrings as $pileStr) {
        $eqPos = strpos($pileStr, "=");
        if($eqPos === false) continue;
        $pileName = substr($pileStr, 0, $eqPos);
        $cardsStr = trim(substr($pileStr, $eqPos + 1));
        $cardIDs = ($cardsStr !== "") ? explode(",", $cardsStr) : [];
        $piles[$pileName] = $cardIDs;
    }

    // Put "Top" pile cards at the front of the deck (reverse-iterate to preserve order)
    $topCards = $piles["Top"];
    for($i = count($topCards) - 1; $i >= 0; --$i) {
        $obj = $popCard($topCards[$i]);
        if($obj !== null) array_unshift($zone, $obj);
    }

    // Put "Bottom" pile cards at the back of the deck (in order)
    $bottomCards = $piles["Bottom"];
    foreach($bottomCards as $cardID) {
        $obj = $popCard($cardID);
        if($obj !== null) array_push($zone, $obj);
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
    $hasDynamic = GetDynamicAbilities($obj) !== "";
    if($debugMode) {
        return (CardActivateAbilityCount($obj->CardID) > 0 || $hasDynamic) ? 1 : 0;
    }
    $turnPlayer = &GetTurnPlayer();
    return $obj->Status == 2 && $turnPlayer == $obj->Controller && (CardActivateAbilityCount($obj->CardID) > 0 || $hasDynamic) ? 1 : 0;
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
    global $playerID;
    $currentPhase = GetCurrentPhase();
    $turnPlayer = &GetTurnPlayer();

    // Standard main phase check
    if ($currentPhase !== "MAIN") {
        return json_encode(['highlight' => false]);
    }
    
    // Check if decision queue is empty
    $decisionQueue = &GetDecisionQueue($turnPlayer);
    if (count($decisionQueue) > 0) {
        return json_encode(['highlight' => false]);
    }
    
    // Only highlight cards belonging to the turn player
    $owner = isset($obj->Controller) ? $obj->Controller : (isset($obj->PlayerID) ? $obj->PlayerID : null);
    if ($owner !== $turnPlayer) {
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

function FieldSelectionMetadata($obj) {
    $currentPhase = GetCurrentPhase();
    if ($currentPhase !== "MAIN") {
        return json_encode(['highlight' => false]);
    }
    $cardType = CardType($obj->CardID);
    if(!PropertyContains($cardType, "ALLY") && !PropertyContains($cardType, "CHAMPION")) {
        return json_encode(['highlight' => false]);
    }
    
    // Check if decision queue is empty
    $turnPlayer = &GetTurnPlayer();
    $decisionQueue = &GetDecisionQueue($turnPlayer);
    if (count($decisionQueue) > 0) {
        return json_encode(['highlight' => false]);
    }

    if ($obj->Controller !== $turnPlayer) {
        return json_encode(['highlight' => false]);
    }

    $prideAmount = PrideAmount($obj);
    if($prideAmount > 0 && PlayerLevel($turnPlayer) < $prideAmount) {
        return json_encode(['highlight' => false]);
    }

    // Return bright vibrant lime green highlight for valid selectable cards
    return json_encode(['color' => 'rgba(0, 255, 0, 0.95)']);
}

function CanActExhausted($obj) {
    
    return false;
}

function ZoneSearch($zoneName, $cardTypes=null, $floatingMemoryOnly=false, $cardElements=null, $cardSubtypes=null, $excludeSubtypes=null) {
    $results = [];
    $zoneArr = &GetZone($zoneName);
    for($i = 0; $i < count($zoneArr); ++$i) {
        $obj = $zoneArr[$i];
        $cardTypeStr = CardType($obj->CardID);
        $cardTypes_arr = $cardTypeStr ? explode(",", $cardTypeStr) : [];
        $cardSubtypesStr = CardSubtypes($obj->CardID);
        $cardSubtypes_arr = $cardSubtypesStr ? explode(",", $cardSubtypesStr) : [];
        if(($cardTypes === null || count(array_intersect($cardTypes_arr, (array)$cardTypes)) > 0) &&
           ($cardElements === null || in_array(CardElement($obj->CardID), (array)$cardElements)) &&
           ($cardSubtypes === null || count(array_intersect($cardSubtypes_arr, (array)$cardSubtypes)) > 0) &&
           ($excludeSubtypes === null || count(array_intersect($cardSubtypes_arr, (array)$excludeSubtypes)) === 0) &&
           (!$floatingMemoryOnly || HasFloatingMemory($obj))) {
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
    /*
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
                //It expired, apply any expiration effects
                switch($effect) {
                    case "DNBTLTMS"://Ultimate Sacrifice
                        SacrificeFighter($turnPlayer, $zoneName . "-" . $index);
                        break;
                    default: break;
                }
            }
            $obj->TurnEffects = $newEffects;
        }
    }
        */
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

    // Clear per-card TurnEffects from the expiring player's field
    $fieldZone = $isEndTurn ? "myField" : "theirField";
    $fieldArr = &GetZone($fieldZone);
    foreach($fieldArr as &$fieldObj) {
        $fieldObj->TurnEffects = [];
    }
    unset($fieldObj);
}

function AddTurnEffect($mzCard, $effectID) {
    $obj = &GetZoneObject($mzCard);
    if($obj === null) return;
    if(!in_array($effectID, $obj->TurnEffects)) {
        array_push($obj->TurnEffects, $effectID);
    }
}

$untilBeginTurnEffects["RYBF1HBTCS"] = true;
$foreverEffects["GMBTMNTM"] = true;
$effectAppliesToBoth["GMBF3HVRKG"] = true;

$doesGlobalEffectApply["9GWxrTMfBz"] = function($obj) { //Cram Session
    return PropertyContains(CardType($obj->CardID), "CHAMPION");
};

$doesGlobalEffectApply["Kc5Bktw0yK"] = function($obj) { //Empowering Harmony
    return PropertyContains(CardType($obj->CardID), "CHAMPION");
};

$doesGlobalEffectApply["dsAqxMezGb"] = function($obj) { //Favorable Winds
    return PropertyContains(CardType($obj->CardID), "ALLY");
};

$doesGlobalEffectApply["DBJ4DuLABr"] = function($obj) { //Shroud in Mist: units you control gain stealth
    return PropertyContains(CardType($obj->CardID), "ALLY") || PropertyContains(CardType($obj->CardID), "CHAMPION");
};

$doesGlobalEffectApply["k71PE3clOI"] = function($obj) { //Inspiring Call: allies get +1 POWER until end of turn
    return PropertyContains(CardType($obj->CardID), "ALLY");
};

$doesGlobalEffectApply["fMv7tIOZwL-PWR"] = function($obj) { //Aqueous Enchanting: allies get +1 POWER until end of turn
    return PropertyContains(CardType($obj->CardID), "ALLY");
};

$doesGlobalEffectApply["fMv7tIOZwL-LIF"] = function($obj) { //Aqueous Enchanting: allies get +1 LIFE until end of turn
    return PropertyContains(CardType($obj->CardID), "ALLY");
};

$doesGlobalEffectApply["hw8dxKAnMX"] = function($obj) { //Mist Resonance: allies get +1 LIFE until end of turn
    return PropertyContains(CardType($obj->CardID), "ALLY");
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

function PlayerLevel($player) {
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $zoneArr = GetZone($zone);
    $maxLevel = 0;
    foreach($zoneArr as $index => $obj) {
        if(PropertyContains(CardType($obj->CardID), "CHAMPION")) {
            $cardLevel = CardLevel($obj->CardID);
            if($cardLevel > $maxLevel) {
                $maxLevel = $cardLevel;
            }
        }
    }
    return $maxLevel;
}

function IsClassBonusActive($player, $classes=null) {
    global $playerID;
    return true;//TODO: Delete this
    $zone = $player == $playerID ? "myField" : "theirField";
    $zoneArr = GetZone($zone);
    foreach($zoneArr as $index => $obj) {
        $cardClasses = explode(",", CardClasses($obj->CardID));
        if(PropertyContains(CardType($obj->CardID), "CHAMPION") && ($classes === null || count(array_intersect($cardClasses, (array)$classes)) > 0)) {
            return true;
        }
    }
    return false;
}

// Lookup for cards with "[Class Bonus] This card costs N less to activate"
// Returns the flat discount amount (0 if card has no class bonus cost reduction)
function ClassBonusActivateCostReduction($cardID) {
    static $reductions = [
        'qwtprd5b5r' => 1,
        'ioxgugw9r9' => 1,
        '4gdubtwij9' => 1,
        'hmjr33ijq6' => 1,
        'ej4mcnqsm3' => 1,
        'xi74wa4x7e' => 1,
        'yhu0djqlp8' => 1,
        'ao8bls6g7x' => 1,
        'rqtjot4nmx' => 1,
        '7iak6hyh6b' => 1,
        '2ugmnmp5af' => 1,
        'bb3oeup7oq' => 1,
        'w7g91ru45w' => 1,
        '5sw9f8uqrp' => 1,
        'oz13xfpk9x' => 1,
        'ru4g75uz1i' => 1,
        '4a8hl5dben' => 1,
        'i7sbjy86ep' => 1,
        '145y6KBhxe' => 1,
        'grlpk1akxj' => 1,
        'xhs5jwsl7d' => 1,
        'edg616r0za' => 1,
        'df9q1wl8ao' => 1,
        '67duh1cy3g' => 1,
        'btjuxztaug' => 1,
        '99sx6q3p6i' => 1,
        'n0esog2898' => 1,
        'gn1b2sbrq9' => 1,
        'zc7wxgur23' => 1,
        'pc0y3xneg7' => 1,
        '8qgr2drym1' => 1,
        'usa6qyq3ka' => 1,
        'MwXulmKsIg' => 1,
        'yunjm0of8e' => 1,
        'o0nkly21ee' => 1,
        'RUqtU0Lczf' => 1,
        'yrzexkW5Ej' => 1,
        'DBJ4DuLABr' => 2,
        'RIVahUIQVD' => 2, // Fireball: [Class Bonus] costs 2 less
    ];
    return isset($reductions[$cardID]) ? $reductions[$cardID] : 0;
}

function DealChampionDamage($player, $amount=1) {
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $zoneArr = &GetZone($zone);
    for($i = 0; $i < count($zoneArr); ++$i) {
        $obj = &$zoneArr[$i];
        if(PropertyContains(CardType($obj->CardID), "CHAMPION")) {
            $obj->Damage += $amount;
            return $obj;
        }
    }
    return null;
}

function RecoverChampion($player, $amount=1) {
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $zoneArr = &GetZone($zone);
    for($i = 0; $i < count($zoneArr); ++$i) {
        $obj = &$zoneArr[$i];
        if(PropertyContains(CardType($obj->CardID), "CHAMPION")) {
            $obj->Damage = max(0, $obj->Damage - $amount);
            return $obj;
        }
    }
    return null;
}

function OnExhaustCard($player, $mzCard) {
    $obj = &GetZoneObject($mzCard);
    $obj->Status = 1; // Exhaust the card
}

function OnRestCard($player, $mzCard) {
    $obj = &GetZoneObject($mzCard);
    $obj->Status = 1; // Rest the card (Grand Archive terminology for exhaust)
}

function OnWakeupCard($player, $mzCard) {
    $obj = &GetZoneObject($mzCard);
    $obj->Status = 2; // Wake up the card
}

/**
 * Check if combat is currently active (an attacker has been declared).
 */
function IsCombatActive() {
    $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
    return ($combatAttacker !== null && $combatAttacker !== "" && $combatAttacker !== "-");
}

/**
 * Check if a unit (given by mzID from current player perspective) is the
 * current combat attacker.
 */
function IsUnitAttacking($mzTarget) {
    $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
    if($combatAttacker === null || $combatAttacker === "" || $combatAttacker === "-") return false;

    global $playerID;
    $turnPlayer = GetTurnPlayer();

    // CombatAttacker was stored from the turn player's perspective.
    // If current player is NOT the turn player, flip to match perspective.
    $normalizedAttacker = $combatAttacker;
    if($playerID != $turnPlayer) {
        $normalizedAttacker = FlipZonePerspective($combatAttacker);
    }

    return $mzTarget === $normalizedAttacker;
}

/**
 * End the current combat: clear intent cards and combat tracking variables.
 * Any remaining combat decisions (damage, retaliation) that are still queued
 * will be skipped by their handlers because CombatAttacker is cleared.
 */
function EndCombat($player) {
    $turnPlayer = GetTurnPlayer();
    ClearIntent($turnPlayer);
    DecisionQueueController::ClearVariable("CombatAttacker");

    // Pop remaining combat decisions (AttackTargetChosen, CleaveAttack,
    // Retaliate, CombatCleanup) from both players' queues.
    for($p = 1; $p <= 2; ++$p) {
        $queue = &GetDecisionQueue($p);
        $filtered = [];
        foreach($queue as $decision) {
            $param = $decision->Param ?? '';
            // Keep non-combat decisions
            if(strpos($param, 'AttackTargetChosen') === false
                && strpos($param, 'CleaveAttack') === false
                && strpos($param, 'Retaliate') === false
                && strpos($param, 'CombatCleanup') === false
                && strpos($param, 'CriticalResolve') === false
                && strpos($param, 'FinishCombatDamage') === false
                && strpos($param, 'DeclareChampionAttack') === false) {
                $filtered[] = $decision;
            }
        }
        $queue = $filtered;
    }
}

/**
 * Check if a player currently has Opportunity to act at fast speed.
 * A player has Opportunity when:
 *  - The EffectStack is non-empty (a spell/ability is pending resolution), OR
 *  - Combat is active (between attack declaration and cleanup)
 */
function HasOpportunity($player) {
    $effectStack = &GetEffectStack();
    if(!empty($effectStack)) return true;
    if(IsCombatActive()) return true;
    return false;
}

// =============================================================================
// Opportunity / Priority System
// =============================================================================

/**
 * Get the list of fast-speed cards a player can play from their hand.
 * Returns an array of mzID strings from the player's own perspective (e.g. "myHand-0").
 *
 * @param int $player The player to check.
 * @return array Array of mzID strings for fast-speed hand cards.
 */
function GetPlayableFastCards($player) {
    $hand = &GetHand($player);
    $fastCards = [];
    for($i = 0; $i < count($hand); $i++) {
        $obj = $hand[$i];
        if(isset($obj->removed) && $obj->removed) continue;
        $speed = CardSpeed($obj->CardID);
        if($speed === true) { // Fast speed
            $fastCards[] = "myHand-" . $i;
        }
    }
    return $fastCards;
}

// --- EffectStack Opportunity ---------------------------------------------------
// After a card enters the EffectStack, the player who activated it gets priority
// first (they can chain more fast cards), then the opponent. Both must pass for
// the topmost card to resolve.

/**
 * DQ handler: After a card is placed on the EffectStack and costs are paid,
 * grant Opportunity. Per rules, the player who activated receives priority first.
 *
 * $player = the player who just placed a card on the EffectStack.
 */
$customDQHandlers["EffectStackOpportunity"] = function($player, $parts, $lastDecision) {
    $effectStack = &GetEffectStack();
    DecisionQueueController::CleanupRemovedCards();
    $effectStack = &GetEffectStack();
    if(empty($effectStack)) return;

    $otherPlayer = ($player == 1) ? 2 : 1;

    // Active player gets priority first (per rules: they can chain)
    $fastCards = GetPlayableFastCards($player);
    if(!empty($fastCards)) {
        $cardList = implode("&", $fastCards);
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $cardList, 100, "Play_a_fast_card?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackActiveResponse|$otherPlayer", 100, "", 1);
    } else {
        // Active player can't respond, check opponent
        $fastCards2 = GetPlayableFastCards($otherPlayer);
        if(!empty($fastCards2)) {
            $cardList = implode("&", $fastCards2);
            DecisionQueueController::AddDecision($otherPlayer, "MZMAYCHOOSE", $cardList, 100, "Play_a_fast_card?");
            DecisionQueueController::AddDecision($otherPlayer, "CUSTOM", "EffectStackOpponentResponse", 100, "", 1);
        } else {
            // Neither can respond, auto-resolve
            ResolveTopOfEffectStack();
        }
    }
};

/**
 * DQ handler: active player responded to EffectStack Opportunity.
 * $parts[0] = the other player's ID.
 */
$customDQHandlers["EffectStackActiveResponse"] = function($player, $parts, $lastDecision) {
    $otherPlayer = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // Active player passed, check opponent
        $fastCards = GetPlayableFastCards($otherPlayer);
        if(!empty($fastCards)) {
            $cardList = implode("&", $fastCards);
            DecisionQueueController::AddDecision($otherPlayer, "MZMAYCHOOSE", $cardList, 100, "Play_a_fast_card?");
            DecisionQueueController::AddDecision($otherPlayer, "CUSTOM", "EffectStackOpponentResponse", 100, "", 1);
        } else {
            // Both passed (opponent has no cards), resolve
            ResolveTopOfEffectStack();
        }
    } else {
        // Active player played a fast card — they keep priority
        ActivateCard($player, $lastDecision, false);
    }
};

/**
 * DQ handler: opponent responded to EffectStack Opportunity.
 */
$customDQHandlers["EffectStackOpponentResponse"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // Both players passed, resolve top of stack
        ResolveTopOfEffectStack();
    } else {
        // Opponent played a fast card — they get priority
        ActivateCard($player, $lastDecision, false);
    }
};

/**
 * DQ handler: After a card resolves from the EffectStack and all its abilities
 * finish, check whether there are more cards on the stack to resolve.
 * If stack is non-empty, grant Opportunity (turn player gets priority first after resolution).
 * If stack is empty, check for a pending Opportunity window (combat/ability) and re-grant it.
 *
 * Uses high block (200) so it runs after any ability decisions (block 1-100).
 */
$customDQHandlers["PostResolutionCheck"] = function($player, $parts, $lastDecision) {
    DecisionQueueController::CleanupRemovedCards();
    $effectStack = &GetEffectStack();

    if(!empty($effectStack)) {
        // More cards to resolve — turn player gets priority first (per rules)
        $turnPlayer = GetTurnPlayer();
        $otherPlayer = ($turnPlayer == 1) ? 2 : 1;

        $fastCards = GetPlayableFastCards($turnPlayer);
        if(!empty($fastCards)) {
            $cardList = implode("&", $fastCards);
            DecisionQueueController::AddDecision($turnPlayer, "MZMAYCHOOSE", $cardList, 100, "Play_a_fast_card?");
            DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "EffectStackActiveResponse|$otherPlayer", 100, "", 1);
        } else {
            $fastCards2 = GetPlayableFastCards($otherPlayer);
            if(!empty($fastCards2)) {
                $cardList = implode("&", $fastCards2);
                DecisionQueueController::AddDecision($otherPlayer, "MZMAYCHOOSE", $cardList, 100, "Play_a_fast_card?");
                DecisionQueueController::AddDecision($otherPlayer, "CUSTOM", "EffectStackOpponentResponse", 100, "", 1);
            } else {
                ResolveTopOfEffectStack();
            }
        }
    } else {
        // Stack is empty — check for a pending Opportunity window (combat/ability)
        $pendingHandler = DecisionQueueController::GetVariable("PendingOpportunityHandler");
        if($pendingHandler !== null && $pendingHandler !== "") {
            $firstPlayer = intval(DecisionQueueController::GetVariable("PendingOpportunityFirstPlayer") ?? GetTurnPlayer());
            $nextPlayer = intval(DecisionQueueController::GetVariable("PendingOpportunityNextPlayer") ?? GetTurnPlayer());
            // Re-grant the Opportunity window (re-checks fast cards for both players)
            GrantOpportunityWindow($firstPlayer, $pendingHandler, $nextPlayer);
        }
    }
};

/**
 * Resolve the top card of the EffectStack.
 *
 * Swaps $playerID to match the card owner so that all my/their zone references
 * resolve correctly, then calls the generated CardActivated() wrapper (which
 * stores mzID, tracks MacroTurnIndex, calls OnCardActivated, and processes
 * ability decisions). After resolution, queues PostResolutionCheck to handle
 * remaining EffectStack entries.
 */
function ResolveTopOfEffectStack() {
    $effectStack = &GetEffectStack();
    DecisionQueueController::CleanupRemovedCards();
    $effectStack = &GetEffectStack();
    if(empty($effectStack)) return;

    $topIndex = count($effectStack) - 1;
    $topObj = $effectStack[$topIndex];
    $cardOwner = $topObj->Controller;
    $topMZ = "EffectStack-" . $topIndex;

    // Swap $playerID to the card owner for correct my/their resolution
    global $playerID;
    $savedPlayerID = $playerID;
    $playerID = $cardOwner;

    // Call the generated CardActivated() wrapper, which:
    //  - Stores mzID variable for ability code
    //  - Tracks MacroTurnIndex
    //  - Calls OnCardActivated (moves card, fires abilities)
    //  - Calls ExecuteStaticMethods to process any ability decisions
    CardActivated($cardOwner, $topMZ);

    // Queue PostResolutionCheck to run after all ability interactions (block 200)
    DecisionQueueController::AddDecision($cardOwner, "CUSTOM", "PostResolutionCheck", 200);

    // Process PostResolutionCheck now if no interactive decisions are pending
    $dqController = new DecisionQueueController();
    $dqController->ExecuteStaticMethods($cardOwner, "-");

    // Restore $playerID
    $playerID = $savedPlayerID;
}

// --- General Opportunity Window ------------------------------------------------
// Used for combat and ability Opportunity windows. Stores a pending handler
// in DQ variables so that after any EffectStack detour (fast card played during
// the window), PostResolutionCheck can re-grant the window.

/**
 * Grant a full 2-player priority Opportunity window.
 * $firstPlayer gets priority first. After both pass, $nextHandler runs for $nextPlayer.
 * If either player plays a fast card, the EffectStack handles it, and after it
 * empties, PostResolutionCheck re-grants this window via the stored variables.
 *
 * @param int    $firstPlayer Player who gets priority first.
 * @param string $nextHandler CUSTOM DQ handler name to queue after both pass.
 * @param int    $nextPlayer  Player for whom to queue $nextHandler (default = $firstPlayer).
 */
function GrantOpportunityWindow($firstPlayer, $nextHandler, $nextPlayer = null) {
    if($nextPlayer === null) $nextPlayer = $firstPlayer;
    $secondPlayer = ($firstPlayer == 1) ? 2 : 1;

    // Store pending state so PostResolutionCheck can re-grant after EffectStack detour
    DecisionQueueController::StoreVariable("PendingOpportunityHandler", $nextHandler);
    DecisionQueueController::StoreVariable("PendingOpportunityNextPlayer", strval($nextPlayer));
    DecisionQueueController::StoreVariable("PendingOpportunityFirstPlayer", strval($firstPlayer));

    // Check first player's fast cards
    $fastCards1 = GetPlayableFastCards($firstPlayer);
    if(!empty($fastCards1)) {
        $cardList = implode("&", $fastCards1);
        DecisionQueueController::AddDecision($firstPlayer, "MZMAYCHOOSE", $cardList, 100, "Play_a_fast_card?");
        DecisionQueueController::AddDecision($firstPlayer, "CUSTOM", "OpportunityWindowFirstResponse", 100, "", 1);
    } else {
        // First player can't act, try second
        $fastCards2 = GetPlayableFastCards($secondPlayer);
        if(!empty($fastCards2)) {
            $cardList = implode("&", $fastCards2);
            DecisionQueueController::AddDecision($secondPlayer, "MZMAYCHOOSE", $cardList, 100, "Play_a_fast_card?");
            DecisionQueueController::AddDecision($secondPlayer, "CUSTOM", "OpportunityWindowSecondResponse", 100, "", 1);
        } else {
            // Neither can act, resolve immediately
            ResolveOpportunityWindow();
        }
    }
}

/**
 * Both players passed the Opportunity window. Clear pending state and queue the next handler.
 */
function ResolveOpportunityWindow() {
    $nextHandler = DecisionQueueController::GetVariable("PendingOpportunityHandler");
    $nextPlayer = intval(DecisionQueueController::GetVariable("PendingOpportunityNextPlayer") ?? "1");
    ClearOpportunityVariables();

    if($nextHandler === null || $nextHandler === "" || $nextHandler === "NoOp") return;

    global $playerID;
    $savedPlayerID = $playerID;
    $playerID = $nextPlayer;

    DecisionQueueController::AddDecision($nextPlayer, "CUSTOM", $nextHandler, 100);
    $dqController = new DecisionQueueController();
    $dqController->ExecuteStaticMethods($nextPlayer, "-");

    $playerID = $savedPlayerID;
}

function ClearOpportunityVariables() {
    DecisionQueueController::ClearVariable("PendingOpportunityHandler");
    DecisionQueueController::ClearVariable("PendingOpportunityNextPlayer");
    DecisionQueueController::ClearVariable("PendingOpportunityFirstPlayer");
}

/**
 * First player in an Opportunity window responded.
 */
$customDQHandlers["OpportunityWindowFirstResponse"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // First player passed, check second player
        $secondPlayer = ($player == 1) ? 2 : 1;
        $fastCards = GetPlayableFastCards($secondPlayer);
        if(!empty($fastCards)) {
            $cardList = implode("&", $fastCards);
            DecisionQueueController::AddDecision($secondPlayer, "MZMAYCHOOSE", $cardList, 100, "Play_a_fast_card?");
            DecisionQueueController::AddDecision($secondPlayer, "CUSTOM", "OpportunityWindowSecondResponse", 100, "", 1);
        } else {
            // Both passed (second has no cards), resolve
            ResolveOpportunityWindow();
        }
    } else {
        // Player played a fast card — they keep priority
        ActivateCard($player, $lastDecision, false);
        // ActivateCard → DoActivateCard → EffectStack → EffectStackOpportunity
        // After stack empties, PostResolutionCheck re-grants this window
    }
};

/**
 * Second player in an Opportunity window responded.
 */
$customDQHandlers["OpportunityWindowSecondResponse"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // Both players passed, resolve
        ResolveOpportunityWindow();
    } else {
        // Player played a fast card — they keep priority
        ActivateCard($player, $lastDecision, false);
        // After stack empties, PostResolutionCheck re-grants this window
    }
};

/**
 * No-op handler for Opportunity windows that don't have a next step
 * (e.g., ability Opportunity — after both pass, game simply continues).
 */
$customDQHandlers["NoOp"] = function($player, $parts, $lastDecision) {
    // Intentionally empty
};

/**
 * DQ handler: After an activated ability resolves, grant Opportunity.
 * Per rules: the player who activated the ability receives priority first.
 * After both pass, game simply continues (NoOp).
 */
$customDQHandlers["AbilityOpportunity"] = function($player, $parts, $lastDecision) {
    GrantOpportunityWindow($player, "NoOp", $player);
};

function HasFloatingMemory($obj) {
    return HasKeyword_FloatingMemory($obj);
}

function HasVigor($obj) {
    return HasKeyword_Vigor($obj);
}

function HasStealth($obj) {
    // Patient Rogue: [Class Bonus] stealth while awake
    if($obj->CardID === "CvvgJR4fNa") {
        return isset($obj->Status) && $obj->Status == 2 && IsClassBonusActive($obj->Controller, ["ASSASSIN"]);
    }
    if(HasKeyword_Stealth($obj)) return true;
    // Check for temporary stealth effects granted by other cards
    $effects = explode(",", CardCurrentEffects($obj));
    foreach($effects as $effectID) {
        switch($effectID) {
            case "DBJ4DuLABr": // Shroud in Mist: units you control gain stealth
                return true;
        }
    }
    return false;
}

function HasTrueSight($obj) {
    return HasKeyword_TrueSight($obj);
}

function PrideAmount($obj) {
    $prideValue = GetKeyword_Pride_Value($obj);
    return $prideValue !== null ? $prideValue : 0;
}

function CardMemoryCost($obj) {
    return CardCost_memory($obj->CardID);
}

function IsHarmonizeActive($player) {
    $cards = CardActivatedTurnCards($player);
    foreach($cards as $cardID => $count) {
        $subtypes = explode(",", CardSubtypes($cardID));
        if(in_array("MELODY", $subtypes)) {
            return true;
        }
    }
    return false;
}

// =============================================================================
// Counter System — generic add/remove/query for card-level counters
// =============================================================================

/**
 * Get the number of a specific counter type on a card object.
 * @param object $obj   A Field zone object with a Counters property (json array/assoc).
 * @param string $type  Counter type key, e.g. "buff", "debuff".
 * @return int
 */
function GetCounterCount($obj, $type) {
    if(!isset($obj->Counters) || !is_array($obj->Counters)) return 0;
    return isset($obj->Counters[$type]) ? intval($obj->Counters[$type]) : 0;
}

/**
 * Virtual property callback: returns the number of buff counters on the object.
 * Used for the BuffCounterCount display badge.
 */
function GetBuffCounterCount($obj) {
    return GetCounterCount($obj, "buff");
}

/**
 * Virtual property callback: returns the number of enlighten counters on the object.
 * Used for the EnlightenCounterCount display badge.
 */
function GetEnlightenCounterCount($obj) {
    return GetCounterCount($obj, "enlighten");
}

/**
 * Virtual property callback: returns the number of preparation counters on the object.
 * Used for the PrepCounterCount display badge.
 */
function GetPrepCounterCount($obj) {
    return GetCounterCount($obj, "preparation");
}

/**
 * Virtual property callback: returns a JSON-encoded array of dynamic activated abilities
 * currently available on this card based on game state (e.g. counter thresholds).
 * Each entry is {"name":"...","index":N} where index is the ability slot (after static abilities).
 * Returns an empty string when no dynamic abilities are available.
 * UILibraries.js reads this generically — no game-specific logic in core UI code.
 *
 * @param object $obj  A Field zone object.
 * @return string JSON array, or empty string.
 */
function GetDynamicAbilities($obj) {
    $abilities = [];
    $staticCount = CardActivateAbilityCount($obj->CardID);
    // Enlighten: champion may remove 3 enlighten counters to draw a card
    if(PropertyContains(CardType($obj->CardID), "CHAMPION") && GetCounterCount($obj, "enlighten") >= 3) {
        $abilities[] = ["name" => "Enlighten", "index" => $staticCount];
    }
    if(empty($abilities)) return "";
    return json_encode($abilities);
}

/**
 * Add counters of a given type to a card on the field.
 * Handles buff/debuff cancellation: if adding buff counters to a card with debuff
 * counters, each buff counter cancels one debuff counter and vice versa.
 *
 * @param int    $player      The acting player.
 * @param string $mzCard      The mzID of the card (e.g. "myField-3").
 * @param string $counterType The counter type key, e.g. "buff", "debuff".
 * @param int    $amount      Number of counters to add (positive).
 */
function AddCounters($player, $mzCard, $counterType, $amount = 1) {
    if($amount <= 0) return;
    $obj = &GetZoneObject($mzCard);
    if(!isset($obj->Counters) || !is_array($obj->Counters)) $obj->Counters = [];

    // Determine the opposite type for cancellation
    $oppositeType = null;
    if($counterType === "buff") $oppositeType = "debuff";
    else if($counterType === "debuff") $oppositeType = "buff";

    // If there is an opposite counter type, cancel pairs first
    if($oppositeType !== null && isset($obj->Counters[$oppositeType]) && $obj->Counters[$oppositeType] > 0) {
        $oppositeCount = intval($obj->Counters[$oppositeType]);
        $cancelAmount = min($amount, $oppositeCount);
        $obj->Counters[$oppositeType] -= $cancelAmount;
        $amount -= $cancelAmount;
        if($obj->Counters[$oppositeType] <= 0) {
            unset($obj->Counters[$oppositeType]);
        }
    }

    // Add remaining counters
    if($amount > 0) {
        if(!isset($obj->Counters[$counterType])) $obj->Counters[$counterType] = 0;
        $obj->Counters[$counterType] += $amount;
    }
}

/**
 * Remove counters of a given type from a card on the field.
 *
 * @param int    $player      The acting player.
 * @param string $mzCard      The mzID of the card.
 * @param string $counterType The counter type key, e.g. "buff", "debuff".
 * @param int    $amount      Number of counters to remove (positive).
 */
function RemoveCounters($player, $mzCard, $counterType, $amount = 1) {
    if($amount <= 0) return;
    $obj = &GetZoneObject($mzCard);
    if(!isset($obj->Counters) || !is_array($obj->Counters)) return;
    if(!isset($obj->Counters[$counterType])) return;

    $obj->Counters[$counterType] = max(0, intval($obj->Counters[$counterType]) - $amount);
    if($obj->Counters[$counterType] <= 0) {
        unset($obj->Counters[$counterType]);
    }
}

/**
 * Remove ALL counters of a given type from a card on the field.
 */
function ClearCounters($player, $mzCard, $counterType) {
    $obj = &GetZoneObject($mzCard);
    if(!isset($obj->Counters) || !is_array($obj->Counters)) return;
    unset($obj->Counters[$counterType]);
}

/**
 * Remove ALL counters of every type from a card on the field.
 */
function ClearAllCounters($player, $mzCard) {
    $obj = &GetZoneObject($mzCard);
    $obj->Counters = [];
}

/**
 * Get the critical amount on a combat source.
 * Checks the unit's TurnEffects for dynamically-granted critical (e.g. "CRITICAL_1").
 * Also checks intent cards for critical effects (attack cards with critical).
 *
 * @param object $obj    The attacking unit's zone object.
 * @param int    $player The attacking player.
 * @return int The highest critical N value found (0 if none).
 */
function GetCriticalAmount($obj, $player) {
    $maxCritical = 0;

    // Check the unit's own TurnEffects
    if(isset($obj->TurnEffects) && is_array($obj->TurnEffects)) {
        foreach($obj->TurnEffects as $effect) {
            if(preg_match('/^CRITICAL_(\d+)$/', $effect, $matches)) {
                $maxCritical = max($maxCritical, intval($matches[1]));
            }
        }
    }

    // Check intent cards for critical effects
    $intentCards = GetIntentCards($player);
    foreach($intentCards as $intentMZ) {
        $intentObj = &GetZoneObject($intentMZ);
        if(isset($intentObj->TurnEffects) && is_array($intentObj->TurnEffects)) {
            foreach($intentObj->TurnEffects as $effect) {
                if(preg_match('/^CRITICAL_(\d+)$/', $effect, $matches)) {
                    $maxCritical = max($maxCritical, intval($matches[1]));
                }
            }
        }
    }

    return $maxCritical;
}

?>