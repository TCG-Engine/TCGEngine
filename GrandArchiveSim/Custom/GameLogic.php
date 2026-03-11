<?php

$debugMode = true;
$customDQHandlers = [];

include_once __DIR__ . '/CardLogic.php';
include_once __DIR__ . '/CombatLogic.php';
include_once __DIR__ . '/MaterializeLogic.php';

// --- Additional Activation Costs Registry ---
// Cards that offer an optional extra reserve cost at activation time (Grand Archive rule 1.3).
// Each entry maps a cardID to:
//   'prompt'       => string shown in the YesNo dialog
//   'extraReserve' => int, number of extra hand→memory payments
//   'condition'    => callable($player) that returns true if the option should be offered
$additionalActivationCosts = [];

// Crux Sight (P9Y1Q5cQ0F): "As an additional cost you may pay (2). If you do,
// banish this card as it resolves and return a crux card from graveyard to hand."
// No condition — always offer when affordable; recovery may fizzle at resolution.
$additionalActivationCosts["P9Y1Q5cQ0F"] = [
    'prompt'       => 'Pay_2_additional_reserve_to_banish_and_recover_crux?',
    'extraReserve' => 2,
];

// --- Lineage Release Abilities Registry ---
// Maps cardID => ['name' => display name, 'effect' => function($player) { ... }]
// When a card with a Lineage Release entry is in the champion's inner lineage (subcards),
// the topmost champion shows a dynamic "LR: <name>" button. Activating it banishes the
// subcard and executes the registered effect.
$lineageReleaseAbilities = [];

$lineageReleaseAbilities["da2ha4dk88"] = [ // Spirit of Serene Fire
    'name' => 'LR: Recover 6',
    'effect' => function($player) { RecoverChampion($player, 6); }
];
$lineageReleaseAbilities["h973fdt8pt"] = [ // Spirit of Serene Wind
    'name' => 'LR: Recover 6',
    'effect' => function($player) { RecoverChampion($player, 6); }
];
$lineageReleaseAbilities["zq9ox7u6wz"] = [ // Spirit of Serene Water
    'name' => 'LR: Recover 6',
    'effect' => function($player) { RecoverChampion($player, 6); }
];

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
            $cardType = EffectiveCardType($obj);
            if(PropertyContains($cardType, "ALLY") || PropertyContains($cardType, "CHAMPION")) {
                BeginCombatPhase($actionCard);
            }
            break;
        default: break;
    }
    return "";
}

function DoActivateCard($player, $mzCard, $ignoreCost = false) {
    // Check if opponent has Corhazi Outlook lockdown effect
    $opponent = ($player == 1) ? 2 : 1;
    if(GlobalEffectCount($opponent, "rw8qq1uwq8-lockdown") > 0) {
        // Activation is blocked
        return;
    }
    
    $sourceObject = &GetZoneObject($mzCard);

    // 1.5 Ally Link pre-check: if the card has Ally Link, there must be at least
    // one ally on the field to link to. If not, the activation is illegal.
    global $AllyLink_Cards;
    $hasAllyLink = isset($AllyLink_Cards[$sourceObject->CardID]);
    if($hasAllyLink) {
        $allyTargets = ZoneSearch("myField", ["ALLY"]);
        if(empty($allyTargets)) return; // No valid Link target — block activation
    }

    // Peaceful Reunion: can only activate if you have not declared an attack this turn
    if($sourceObject->CardID === "wr42i6eifn" && OnAttackCallCount($player) > 0) {
        SetFlashMessage("Peaceful Reunion can only be activated if you haven't declared an attack this turn.");
        return;
    }

    // Smash with Obelisk (2kkvoqk1l7): mandatory sacrifice of a domain you control
    if($sourceObject->CardID === "2kkvoqk1l7") {
        $domains = ZoneSearch("myField", ["DOMAIN"]);
        if(empty($domains)) return; // No domains to sacrifice — block activation
    }

    // Firetuned Automaton (lzjmwuir99): mandatory discard of a fire element card
    if($sourceObject->CardID === "lzjmwuir99") {
        $hand = GetZone("myHand");
        $hasFireDiscard = false;
        foreach($hand as $hi => $hObj) {
            if(!$hObj->removed && "myHand-" . $hi !== $mzCard && CardElement($hObj->CardID) === "FIRE") {
                $hasFireDiscard = true;
                break;
            }
        }
        if(!$hasFireDiscard) return; // No fire card to discard — block activation
    }

    //1.1 Announcing Activation: First, the player announces the card they are activating and places it onto the effects stack.
    $obj = MZMove($player, $mzCard, "EffectStack");
    $obj->Controller = $player;

    //TODO: 1.2 Checking Elements: Then, the game checks whether the player has the required elements enabled to activate the card. If not, the activation is illegal.
    
    //TODO: 1.3 Declaring Costs: Next, the player declares the intended cost parameters for the card.

    //TODO: 1.4 Selecting Modes

    //TODO: 1.5 Declaring Targets

    //TODO: 1.6 Checking Legality

    // Right of Realm (ptrz1bqry4): "Whenever you activate a domain card, you may sacrifice
    // Right of Realm. If you do, that domain enters the field without any of its upkeep abilities."
    $cardType = CardType($obj->CardID);
    if(PropertyContains($cardType, "DOMAIN")) {
        $field = GetZone("myField");
        for($ri = 0; $ri < count($field); ++$ri) {
            if(!$field[$ri]->removed && $field[$ri]->CardID === "ptrz1bqry4") {
                DecisionQueueController::AddDecision($player, "YESNO", "-", 100,
                    tooltip:"Sacrifice_Right_of_Realm_so_domain_enters_without_upkeep?");
                DecisionQueueController::AddDecision($player, "CUSTOM", "RightOfRealmChoice|$ri", 100);
                break; // Only one Right of Realm can trigger
            }
        }
    }

    //1.7 Calculating Reserve Cost
    $reserveCost = CardCost_reserve($obj->CardID);

    // Class Bonus: reduce cost if champion's class matches card's class
    $classBonusDiscount = ClassBonusActivateCostReduction($obj->CardID);
    if($classBonusDiscount > 0 && IsClassBonusActive($player, explode(",", CardClasses($obj->CardID)))) {
        $reserveCost = max(0, $reserveCost - $classBonusDiscount);
    }

    // Arcane Elemental: [Class Bonus] costs 1 less per arcane element card in banishment
    if($obj->CardID === "wFH1kBLrWh" && IsClassBonusActive($player, ["MAGE"])) {
        $arcaneCount = count(ZoneSearch("myBanish", cardElements: ["ARCANE"]));
        $reserveCost = max(0, $reserveCost - $arcaneCount);
    }

    // Efficiency: reduce cost by the champion's current level
    global $Efficiency_Cards;
    if(isset($Efficiency_Cards[$obj->CardID])) {
        $myField = GetZone("myField");
        foreach($myField as $fieldObj) {
            if(PropertyContains(EffectiveCardType($fieldObj), "CHAMPION")) {
                $champLevel = ObjectCurrentLevel($fieldObj);
                $reserveCost = max(0, $reserveCost - $champLevel);
                break;
            }
        }
    }

    // Channeling Stone (EBWWwvSxr3): global effect reduces next card cost by 2
    if(GlobalEffectCount($player, "EBWWwvSxr3") > 0) {
        $reserveCost = max(0, $reserveCost - 2);
        RemoveGlobalEffect($player, "EBWWwvSxr3");
    }

    // Horn of Beastcalling (6e7lRnczfL): global effect reduces next Beast ally cost by 3
    if(GlobalEffectCount($player, "6e7lRnczfL") > 0) {
        if(PropertyContains(CardType($obj->CardID), "ALLY") && PropertyContains(CardSubtypes($obj->CardID), "BEAST")) {
            $reserveCost = max(0, $reserveCost - 3);
            RemoveGlobalEffect($player, "6e7lRnczfL");
        }
    }

    // Command the Hunt (rxxwQT054x): global effect reduces next card cost by 2 if no attacks this turn  
    if(GlobalEffectCount($player, "rxxwQT054x_COST") > 0) {
        $reserveCost = max(0, $reserveCost - 2);
        RemoveGlobalEffect($player, "rxxwQT054x_COST");
    }

    // Summon Sentinels (5tlzsmw3rr): [Class Bonus] costs 1 less for each domain you control
    if($obj->CardID === "5tlzsmw3rr" && IsClassBonusActive($player, ["GUARDIAN"])) {
        $domainCount = count(ZoneSearch("myField", ["DOMAIN"]));
        $reserveCost = max(0, $reserveCost - $domainCount);
    }

    // Deflecting Edge (g7uDOmUf2u): costs 1 less if you control a Sword weapon
    if($obj->CardID === "g7uDOmUf2u") {
        if(!empty(ZoneSearch("myField", ["WEAPON"], cardSubtypes: ["SWORD"]))) {
            $reserveCost = max(0, $reserveCost - 1);
        }
    }

    // Meltdown (ht2tsn0ye3): [Level 2+] costs 1 less
    if($obj->CardID === "ht2tsn0ye3" && PlayerLevel($player) >= 2) {
        $reserveCost = max(0, $reserveCost - 1);
    }

    // Winds of Retribution (huqj5bbae3): [Class Bonus][Level 2+] costs 2 less
    if($obj->CardID === "huqj5bbae3" && IsClassBonusActive($player, ["GUARDIAN"]) && PlayerLevel($player) >= 2) {
        $reserveCost = max(0, $reserveCost - 2);
    }

    // Astral Seal (e3aebjvwbc): [Class Bonus] costs 3 less if a card in any banishment shares a name with a card on the effect stack
    if($obj->CardID === "e3aebjvwbc" && IsClassBonusActive($player, ["CLERIC"])) {
        $banishCardIDs = [];
        foreach(array_merge(GetZone("myBanish"), GetZone("theirBanish")) as $bObj) {
            $banishCardIDs[$bObj->CardID] = true;
        }
        $es = GetZone("EffectStack");
        foreach($es as $esObj) {
            if($esObj->removed) continue;
            if(isset($banishCardIDs[$esObj->CardID])) {
                $reserveCost = max(0, $reserveCost - 3);
                break;
            }
        }
    }

    // Rally the Peasants (q1uwq8sdbz): [Class Bonus] costs 3 less if opponent controls 3+ allies
    if($obj->CardID === "q1uwq8sdbz" && IsClassBonusActive($player, ["WARRIOR"])) {
        $oppAllies = ZoneSearch("theirField", ["ALLY"]);
        if(count($oppAllies) >= 3) {
            $reserveCost = max(0, $reserveCost - 3);
        }
    }

    // Steady Verse Harmony Discount: next Harmony card costs 1 less
    if(GlobalEffectCount($player, "STEADY_VERSE_HARMONY_DISCOUNT") > 0) {
        if(PropertyContains(CardSubtypes($obj->CardID), "HARMONY")) {
            $reserveCost = max(0, $reserveCost - 1);
            RemoveGlobalEffect($player, "STEADY_VERSE_HARMONY_DISCOUNT");
        }
    }

    // Incarnate Majesty (7dl5j4lx6x): costs 1 less per regalia weapon in banishment
    if($obj->CardID === "7dl5j4lx6x") {
        $banishWeapons = ZoneSearch("myBanish", ["WEAPON"]);
        $regaliaCount = 0;
        foreach($banishWeapons as $bwMZ) {
            $bwObj = GetZoneObject($bwMZ);
            if(PropertyContains(CardType($bwObj->CardID), "REGALIA")) {
                $regaliaCount++;
            }
        }
        $reserveCost = max(0, $reserveCost - $regaliaCount);
    }

    // Neos Elemental (jwsl7dedg6): [Class Bonus] costs 1 less per token object you control
    if($obj->CardID === "jwsl7dedg6" && IsClassBonusActive($player, ["GUARDIAN"])) {
        $tokenCount = count(ZoneSearch("myField", ["TOKEN"]));
        $reserveCost = max(0, $reserveCost - $tokenCount);
    }

    // Frigid Bash (k2c7wklzjm): costs 2 less if you control a Shield item
    if($obj->CardID === "k2c7wklzjm") {
        if(!empty(ZoneSearch("myField", ["ITEM"], cardSubtypes: ["SHIELD"]))) {
            $reserveCost = max(0, $reserveCost - 2);
        }
    }

    // Diffusive Block (o7eanl1gxr): costs 1 less if you control a Shield item
    if($obj->CardID === "o7eanl1gxr") {
        if(!empty(ZoneSearch("myField", ["ITEM"], cardSubtypes: ["SHIELD"]))) {
            $reserveCost = max(0, $reserveCost - 1);
        }
    }

    // Seasprite Diver (mxqsm4o98v): costs 1 less if opponent has 4+ cards in graveyard
    if($obj->CardID === "mxqsm4o98v") {
        $oppGY = ZoneSearch("theirGraveyard");
        if(count($oppGY) >= 4) {
            $reserveCost = max(0, $reserveCost - 1);
        }
    }

    // Excoriate (ls6g7xgwve): [Level 2+] costs 1 less
    if($obj->CardID === "ls6g7xgwve" && PlayerLevel($player) >= 2) {
        $reserveCost = max(0, $reserveCost - 1);
    }

    // Sidestep (voy5ttkk39): [Level 2+] costs 1 less
    if($obj->CardID === "voy5ttkk39" && PlayerLevel($player) >= 2) {
        $reserveCost = max(0, $reserveCost - 1);
    }

    // Viridian Protective Trinket (s3572j3oda): during your turn, opponent's water element cards cost 2 more
    $opponent = ($player == 1) ? 2 : 1;
    $turnPlayer = &GetTurnPlayer();
    if($player !== $turnPlayer) {
        // It's the opponent's turn — check if the turn player controls Viridian Protective Trinket
        global $playerID;
        $oppFieldZone = ($turnPlayer == $playerID) ? "myField" : "theirField";
        $oppField = GetZone($oppFieldZone);
        foreach($oppField as $oppObj) {
            if(!$oppObj->removed && $oppObj->CardID === "s3572j3oda") {
                if(CardElement($obj->CardID) === "WATER") {
                    $reserveCost += 2;
                }
                break;
            }
        }
    }

    // Nia, Mistveiled Scout (PZM9uvCFai): named card costs 1 more — stored as TurnEffect "PZM9uvCFai-<CardID>" on Nia
    foreach(array_merge(GetZone("myField"), GetZone("theirField")) as $niaFieldObj) {
        if($niaFieldObj->removed || $niaFieldObj->CardID !== "PZM9uvCFai") continue;
        foreach($niaFieldObj->TurnEffects as $niaTe) {
            if(strpos($niaTe, "PZM9uvCFai-") === 0) {
                $namedCardID = substr($niaTe, strlen("PZM9uvCFai-"));
                if($obj->CardID === $namedCardID) {
                    $reserveCost += 1;
                    break 2;
                }
            }
        }
    }

    // Dawn of Ashes (4coy34bro8): "Non-norm element cards cost 1 more to activate."
    // This applies to ALL players when any player controls Dawn of Ashes on the field.
    if(CardElement($obj->CardID) !== "NORM") {
        // Check both players' fields for Dawn of Ashes
        $myField = GetZone("myField");
        $theirField = GetZone("theirField");
        foreach(array_merge($myField, $theirField) as $fObj) {
            if(!$fObj->removed && $fObj->CardID === "4coy34bro8") {
                $reserveCost += 1;
                break; // Multiple Dawn of Ashes shouldn't stack (unique)
            }
        }
    }

    // Wayfinder's Map (porhlq2kkv): domain cards cost 1 less
    if(PropertyContains($cardType, "DOMAIN")) {
        $myField = GetZone("myField");
        foreach($myField as $fObj) {
            if(!$fObj->removed && $fObj->CardID === "porhlq2kkv") {
                $reserveCost = max(0, $reserveCost - 1);
                break;
            }
        }
    }

    // 1.5 Declaring Targets — Ally Link: prompt the player to choose a target ally
    if($hasAllyLink) {
        $allyTargets = ZoneSearch("myField", ["ALLY"]);
        $allyChoices = implode("&", $allyTargets);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $allyChoices, 100, tooltip:"Choose_ally_to_link");
        DecisionQueueController::AddDecision($player, "CUSTOM", "DeclareAllyLinkTarget", 100);
    }

    //1.3 Declaring Costs — Prepare keyword: optional removal of preparation counters
    $hasPrepare = false;
    if(HasKeyword_Prepare($obj)) {
        $prepValue = intval(GetKeyword_Prepare_Value($obj));
        $myField = GetZone("myField");
        $champMZ = null;
        foreach($myField as $fi => $fieldObj) {
            if(PropertyContains(EffectiveCardType($fieldObj), "CHAMPION")) {
                $champMZ = "myField-" . $fi;
                break;
            }
        }
        if($champMZ !== null) {
            $champObj = GetZoneObject($champMZ);
            if(GetPrepCounterCount($champObj) >= $prepValue) {
                $hasPrepare = true;
                DecisionQueueController::AddDecision($player, "YESNO", "-", 100, tooltip:"Remove_" . $prepValue . "_preparation_counters?");
                DecisionQueueController::AddDecision($player, "CUSTOM",
                    "DeclarePrepareCost|" . $champMZ . "|" . $prepValue, 100);
            }
        }
    }
    if(!$hasPrepare) {
        DecisionQueueController::StoreVariable("wasPrepared", "NO");
    }

    //1.3 Declaring Costs — Innervate Knowledge / Innervate Agility: mandatory delevel + recover 5
    if($obj->CardID === "pcescfpwak" || $obj->CardID === "v43ehjdu50" || $obj->CardID === "wpbhigka5a") {
        Delevel($player);
        // Recover 5
        RecoverChampion($player, 5);
    }

    //1.3 Declaring Costs — Smash with Obelisk (2kkvoqk1l7): mandatory sacrifice of a domain
    if($obj->CardID === "2kkvoqk1l7") {
        $domains = ZoneSearch("myField", ["DOMAIN"]);
        if(!empty($domains)) {
            $domainChoices = implode("&", $domains);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $domainChoices, 100, tooltip:"Sacrifice_a_domain");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SmashWithObeliskSacrifice", 100);
        }
    }

    //1.3 Declaring Costs — Firetuned Automaton (lzjmwuir99): mandatory discard of a fire element card
    if($obj->CardID === "lzjmwuir99") {
        $fireCards = [];
        $hand = GetZone("myHand");
        foreach($hand as $hi => $hObj) {
            if(!$hObj->removed && CardElement($hObj->CardID) === "FIRE") {
                $fireCards[] = "myHand-" . $hi;
            }
        }
        if(!empty($fireCards)) {
            $fireStr = implode("&", $fireCards);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $fireStr, 100, tooltip:"Discard_a_fire_element_card");
            DecisionQueueController::AddDecision($player, "CUSTOM", "FiretunedAutomatonDiscard", 100);
        }
    }

    //1.3 Declaring Costs — Song of Frost (t1cn1tzgcx): [Class Bonus] may banish floating-memory GY card instead of reserve
    $hasSongOfFrostAltCost = false;
    if($obj->CardID === "t1cn1tzgcx" && IsClassBonusActive($player, ["TAMER"])) {
        $floatingGY = [];
        $gy = GetZone("myGraveyard");
        for($gi = 0; $gi < count($gy); ++$gi) {
            if(!$gy[$gi]->removed && HasFloatingMemory($gy[$gi])) {
                $floatingGY[] = "myGraveyard-" . $gi;
            }
        }
        if(!empty($floatingGY) && $reserveCost > 0) {
            $hasSongOfFrostAltCost = true;
            DecisionQueueController::AddDecision($player, "YESNO", "-", 100, tooltip:"Banish_floating-memory_GY_card_instead_of_reserve?");
            DecisionQueueController::AddDecision($player, "CUSTOM",
                "SongOfFrostAltCost|" . $reserveCost, 100);
        }
    }

    //1.3 Declaring Costs — check for optional additional costs
    global $additionalActivationCosts;
    $hasAdditionalCost = false;
    if(isset($additionalActivationCosts[$obj->CardID])) {
        $costEntry = $additionalActivationCosts[$obj->CardID];
        $extraReserve = $costEntry['extraReserve'];
        $hand = GetZone("myHand");
        $conditionMet = !isset($costEntry['condition']) || $costEntry['condition']($player);
        if($conditionMet && count($hand) >= $reserveCost + $extraReserve) {
            $hasAdditionalCost = true;
            $prompt = $costEntry['prompt'];
            DecisionQueueController::AddDecision($player, "YESNO", "-", 100, tooltip:$prompt);
            DecisionQueueController::AddDecision($player, "CUSTOM",
                "DeclareAdditionalCost|" . $obj->CardID . "|" . $reserveCost . "|" . $extraReserve, 100);
        }
    }

    if(!$hasAdditionalCost && !$hasSongOfFrostAltCost) {
        // No additional cost — store default and queue normal reserve + opportunity
        DecisionQueueController::StoreVariable("additionalCostPaid", "NO");

        //1.8 Paying Costs
        if(!$ignoreCost) {
            for($i = 0; $i < $reserveCost; ++$i) {
                DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
            }
        }

        //1.9 Activation — grant Opportunity to the opponent before resolving
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
    }
    // When $hasAdditionalCost is true, the DeclareAdditionalCost handler takes over
    // queuing reserve payments and EffectStackOpportunity after the player answers.
    // When $hasSongOfFrostAltCost is true, SongOfFrostAltCost handler queues its own
    // reserve/banish + EffectStackOpportunity.
}

$customDQHandlers["ReserveCard"] = function($player, $parts, $lastDecision) {
    // Build MZCHOOSE source: hand cards + ready reservable field cards
    $source = "myHand";
    $field = GetZone("myField");
    foreach($field as $i => $fieldObj) {
        if($fieldObj->removed) continue;
        if(isset($fieldObj->Status) && $fieldObj->Status == 2 && HasReservable($fieldObj)) {
            $source .= "&myField-" . $i;
        }
    }
    $tooltip = "Choose_a_card_to_pay_reserve_cost";
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $source, 1, $tooltip);
    DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard_Process", 99);
};

$customDQHandlers["ReserveCard_Process"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "PASS") return;
    // Determine if the chosen card is from the field (reservable) or hand
    if(strpos($lastDecision, "myField-") === 0) {
        // Reservable card on field: rest/exhaust it to pay for 1 reserve cost
        ExhaustCard($player, $lastDecision);
    } else {
        // Hand card: move to memory as normal
        OnCardReserved($player, $lastDecision);
    }
};

/**
 * DQ handler: processes the player's YesNo answer for an optional additional
 * activation cost. Stores the result so ability code can read it at resolution,
 * then queues ALL reserve payments (base + extra if YES) followed by the
 * EffectStackOpportunity. Parts: [cardID, baseReserve, extraReserve].
 */
$customDQHandlers["DeclareAdditionalCost"] = function($player, $parts, $lastDecision) {
    $cardID      = $parts[0];
    $baseReserve = intval($parts[1]);
    $extraReserve = intval($parts[2]);

    DecisionQueueController::StoreVariable("additionalCostPaid", $lastDecision);

    $totalCost = $baseReserve;
    if($lastDecision === "YES") {
        $totalCost += $extraReserve;
    }

    for($i = 0; $i < $totalCost; ++$i) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
};

/**
 * DQ handler: processes the player's YesNo answer for the optional Prepare cost.
 * If YES, removes N preparation counters from the champion and stores wasPrepared = YES.
 * Parts: [champMZ, prepValue].
 */
$customDQHandlers["DeclarePrepareCost"] = function($player, $parts, $lastDecision) {
    $champMZ   = $parts[0];
    $prepValue = intval($parts[1]);

    if($lastDecision === "YES") {
        RemoveCounters($player, $champMZ, "preparation", $prepValue);
        DecisionQueueController::StoreVariable("wasPrepared", "YES");
    } else {
        DecisionQueueController::StoreVariable("wasPrepared", "NO");
    }
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
    } else if(PropertyContains($cardType, "WEAPON")) {
        // Weapons enter the field like allies (main-deck weapons with reserve cost)
        $obj = MZMove($player, $mzCard, "myField");
        $obj->Controller = $player;
    }  else if(PropertyContains($cardType, "REGALIA")) {
        // Regalia enter the field like allies (main-deck regalia with reserve cost)
        $obj = MZMove($player, $mzCard, "myField");
        $obj->Controller = $player;
    } else if(PropertyContains($cardType, "PHANTASIA")) {
        // Ally Link fizzle check: validate the link target is still legal at resolution time
        global $AllyLink_Cards;
        if(isset($AllyLink_Cards[$obj->CardID])) {
            $linkTargetMZ = DecisionQueueController::GetVariable("linkTargetMZ");
            $linkTargetCardID = DecisionQueueController::GetVariable("linkTargetCardID");
            $targetObj = (!empty($linkTargetMZ) && $linkTargetMZ !== "-") ? GetZoneObject($linkTargetMZ) : null;
            $targetValid = ($targetObj !== null && !$targetObj->removed
                && $targetObj->CardID === $linkTargetCardID
                && PropertyContains(CardType($targetObj->CardID), "ALLY"));
            if(!$targetValid && !empty($linkTargetCardID)) {
                // Target index may have shifted — scan field for the same CardID
                $field = GetZone("myField");
                $targetValid = false;
                for($fi = 0; $fi < count($field); $fi++) {
                    if(!$field[$fi]->removed && $field[$fi]->CardID === $linkTargetCardID
                        && PropertyContains(CardType($field[$fi]->CardID), "ALLY")) {
                        DecisionQueueController::StoreVariable("linkTargetMZ", "myField-" . $fi);
                        $targetValid = true;
                        break;
                    }
                }
            }
            if(!$targetValid) {
                // Fizzle — link target no longer exists
                $obj = MZMove($player, $mzCard, "myGraveyard");
                DecisionQueueController::StoreVariable("linkTargetMZ", "");
                DecisionQueueController::CleanupRemovedCards();
                return;
            }
        }
        $obj = MZMove($player, $mzCard, "myField");
        $obj->Controller = $player;
    } else if(PropertyContains($cardType, "DOMAIN")) {
        // Domains enter the field like allies/regalia — they are objects that persist
        $obj = MZMove($player, $mzCard, "myField");
        $obj->Controller = $player;
    }  else if(PropertyContains($cardType, "ACTION")) {
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
        // Tag with PREPARED TurnEffect if the Prepare cost was paid
        $wasPrepared = DecisionQueueController::GetVariable("wasPrepared");
        if($wasPrepared === "YES") {
            $intentZone = &GetZone("myIntent");
            $intentIdx = count($intentZone) - 1;
            AddTurnEffect("myIntent-" . $intentIdx, "PREPARED");
        }
    }
    DecisionQueueController::CleanupRemovedCards();
    if(isset($cardActivatedAbilities[$obj->CardID . ":0"])) {
        $cardActivatedAbilities[$obj->CardID . ":0"]($player);
    }

    // "Whenever you activate" triggers — check field for listening cards
    $field = &GetField($player);
    $subtypes = CardSubtypes($obj->CardID);
    $activatedElement = CardElement($obj->CardID);
    for($fi = 0; $fi < count($field); ++$fi) {
        if($field[$fi]->removed) continue;
        switch($field[$fi]->CardID) {
            case "3traenEA8M": // Galatine: when you activate a Sword attack, add a durability counter
                if(PropertyContains($cardType, "ATTACK") && PropertyContains($subtypes, "SWORD")) {
                    AddCounters($player, "myField-" . $fi, "durability", 1);
                }
                break;
            case "aKgdkLSBza": // Wilderness Harpist: when you activate a Melody or Harmony, +1 level this turn
                if(PropertyContains($subtypes, "MELODY") || PropertyContains($subtypes, "HARMONY")) {
                    AddTurnEffect("myField-" . $fi, "aKgdkLSBza");
                }
                break;
            case "41WnFOT5YS": // Avalon, Cursed Isle: whenever you activate a water element card,
                // target player puts the top two cards of their deck into their graveyard
                if($activatedElement === "WATER") {
                    DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
                        tooltip:"Mill_yourself?_(No=mill_opponent)");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "AvalonMill", 1);
                }
                break;
        }
    }

    // Rai, Archmage (zdIhSL5RhK) — Inherited Effect:
    // Whenever you activate your first Mage action card each turn, put an enlighten counter on your champion.
    if(PropertyContains($cardType, "ACTION") && PropertyContains(CardClasses($obj->CardID), "MAGE")) {
        if(ChampionHasInLineage($player, "zdIhSL5RhK") && GlobalEffectCount($player, "RAI_ARCHMAGE_TRIGGERED") == 0) {
            AddGlobalEffects($player, "RAI_ARCHMAGE_TRIGGERED");
            // Find champion and add enlighten counter
            $champField = &GetField($player);
            for($ci = 0; $ci < count($champField); ++$ci) {
                if(!$champField[$ci]->removed && PropertyContains(CardType($champField[$ci]->CardID), "CHAMPION") && $champField[$ci]->Controller == $player) {
                    AddCounters($player, "myField-" . $ci, "enlighten", 1);
                    break;
                }
            }
        }
    }

    // Effigy of Gaia (akb1k0zi5h): when an OPPONENT activates an attack card with cleave,
    // Animal/Beast allies you control get +2 LIFE until end of turn.
    if(PropertyContains($cardType, "ATTACK")) {
        $opponent = ($player == 1) ? 2 : 1;
        $oppField = &GetField($opponent);
        for($ofi = 0; $ofi < count($oppField); ++$ofi) {
            if(!$oppField[$ofi]->removed && $oppField[$ofi]->CardID === "akb1k0zi5h") {
                // Check if the attack card has cleave
                if(HasKeyword_Cleave($obj)) {
                    AddGlobalEffects($opponent, "akb1k0zi5h");
                }
                break;
            }
        }
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

/**
 * Pay the non-reserve costs for an activated ability (e.g., banish self, REST).
 * Called before AbilityActivated so the card is already gone before the opponent
 * receives priority via AbilityOpportunity.
 *
 * @param int    $player  The activating player
 * @param string $mzCard  The mzID of the card paying the cost
 * @param string $cardID  The card's dictionary ID
 */
function ActivatedAbilityCost($player, $mzCard, $cardID) {
    switch($cardID) {
        // --- Always banish self ---
        case "iiZtKTulPg": // Eye of Argus
        case "usb5FgKvZX": // Sharpening Stone
        case "F1t18omUlx": // Beastbond Paws
        case "ScGcOmkoQt": // Smoke Bombs
        case "qYH9PJP7uM": // Blinding Orb
        case "OofVX5hX8X": // Poisoned Coating Oil
        case "Z9TCpaMJTc": // Bauble of Abundance
        case "EQZZsiUDyl": // Storm Tyrant's Eye
        case "6e7lRnczfL": // Horn of Beastcalling
        case "EBWWwvSxr3": // Channeling Stone
        case "s23UHXgcZq": // Luxera's Map — REST + banish self
        case "Tx6iJQNSA6": // Majestic Spirit's Crest — [Class Bonus] banish self
        case "WAFNy2lY5t": // Melodious Flute — [Class Bonus] banish self
        case "UiohpiTtgs": // Chalice of Blood — banish self only if champion has 20+ damage
        case "xjuCkODVRx": // Beastbond Boots — banish self
        case "73fdt8ptrz": // Windwalker Boots — banish self
        case "n0wpbhigka": // Wand of Frost — banish self
        case "96659ytyj2": // Crimson Protective Trinket — banish self
        case "m3pal7cpvn": // Azure Protective Trinket — banish self
        case "9agwj4f15j": // Crystalline Mirror — banish self
        case "af098kmoi0": // Orb of Hubris — banish self
        case "fp66pv4n1n": // Rusted Warshield — banish self
        case "porhlq2kkv": // Wayfinder's Map — banish self
            MZMove($player, $mzCard, "myBanish");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "d6soporhlq": // Obelisk of Protection — REST
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            break;
        case "iohZMWh5v5": // Blazing Throw: sacrifice a weapon as additional cost
            $weapons = ZoneSearch("myField", ["WEAPON"]);
            if(!empty($weapons)) {
                $choices = implode("&", $weapons);
                DecisionQueueController::AddDecision($player, "MZCHOOSE", $choices, 1);
                DecisionQueueController::AddDecision($player, "CUSTOM", "BT_SacrificeWeapon", 1);
            }
            break;
        case "5joh300z2s": // Manaroot: sacrifice self to graveyard
        case "69iq4d5vet": // Springleaf: sacrifice self to graveyard
        case "5swaf8urrq": // Whirlwind Vizier: sacrifice self to graveyard
        case "bd7ozuj68m": // Silvershine: sacrifice self to graveyard
        case "i0a5uhjxhk": // Blightroot: sacrifice self to graveyard
        case "jnltv5klry": // Razorvine: sacrifice self to graveyard
            MZMove($player, $mzCard, "myGraveyard");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "a5uhjxhkur": // Resplendent Kite Shield: REST + remove refinement counter
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            RemoveCounters($player, $mzCard, "refinement", 1);
            break;
        case "n1voy5ttkk": // Shatterfall Keep: REST
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            break;
        case "uy4xippor7": // Oasis Trading Post: REST
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            break;
        case "soporhlq2k": // Fraysia: sacrifice self to graveyard
            MZMove($player, $mzCard, "myGraveyard");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "uhuy4xippo": // Fractal of Snow: sacrifice self to graveyard
            MZMove($player, $mzCard, "myGraveyard");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "sw2ugmnmp5": // Navigation Compass: REST + discard a domain card
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            $domCards = [];
            $hZone = GetZone("myHand");
            foreach($hZone as $hIdx => $hObj) {
                if(!$hObj->removed && PropertyContains(CardType($hObj->CardID), "DOMAIN")) {
                    $domCards[] = "myHand-" . $hIdx;
                }
            }
            if(!empty($domCards)) {
                $domStr = implode("&", $domCards);
                DecisionQueueController::AddDecision($player, "MZCHOOSE", $domStr, 1, "Discard_a_domain_card");
                DecisionQueueController::AddDecision($player, "CUSTOM", "NavCompass_Discard", 1);
            }
            break;
        case "oy34bro89w": // Cunning Broker: remove 2 prep counters from champion
            $pField = &GetField($player);
            for($ci = 0; $ci < count($pField); ++$ci) {
                if(!$pField[$ci]->removed && PropertyContains(EffectiveCardType($pField[$ci]), "CHAMPION")) {
                    RemoveCounters($player, "myField-" . $ci, "preparation", 2);
                    break;
                }
            }
            break;
    }
}

function DoActivatedAbility($player, $mzCard, $abilityIndex = 0) {
    global $customDQHandlers;
    $sourceObject = &GetZoneObject($mzCard);
    // Capture cardID now — the card may be moved to banishment as a cost below.
    $cardID = $sourceObject->CardID;

    // Crystalline Mirror (9agwj4f15j): CB + 3+ phantasias required
    if($cardID === "9agwj4f15j") {
        if(!IsClassBonusActive($player, explode(",", CardClasses("9agwj4f15j"))) || count(ZoneSearch("myField", ["PHANTASIA"])) < 3) return;
    }
    // Resplendent Kite Shield (a5uhjxhkur): needs refinement counter
    if($cardID === "a5uhjxhkur" && GetCounterCount($sourceObject, "refinement") < 1) return;
    // Wayfinder's Map (porhlq2kkv): needs 3+ domains on field
    if($cardID === "porhlq2kkv" && count(ZoneSearch("myField", ["DOMAIN"])) < 3) return;
    // Dormant Sacrificial Altar (px8jypwc8t): needs both Automaton and Human allies on field
    if($cardID === "px8jypwc8t") {
        $automatons = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["AUTOMATON"]);
        $humans = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["HUMAN"]);
        if(empty($automatons) || empty($humans)) return;
        $combined = array_unique(array_merge($automatons, $humans));
        if(count($combined) < 2) return;
    }
    // Navigation Compass (sw2ugmnmp5): must be awake + have domain card in hand
    if($cardID === "sw2ugmnmp5") {
        if($sourceObject->Status != 2) return;
        $domainHand = [];
        $hand = GetZone("myHand");
        foreach($hand as $hObj) {
            if(!$hObj->removed && PropertyContains(CardType($hObj->CardID), "DOMAIN")) { $domainHand[] = true; break; }
        }
        if(empty($domainHand)) return;
    }
    // Shatterfall Keep (n1voy5ttkk): needs floating memory card in graveyard + must be awake
    if($cardID === "n1voy5ttkk") {
        if($sourceObject->Status != 2) return;
        $gy = GetZone("myGraveyard");
        $hasFloating = false;
        foreach($gy as $gyObj) {
            if(!$gyObj->removed && HasFloatingMemory($gyObj)) { $hasFloating = true; break; }
        }
        if(!$hasFloating) return;
    }
    // Fractal of Snow (uhuy4xippo): needs class bonus
    if($cardID === "uhuy4xippo" && !IsClassBonusActive($player, explode(",", CardClasses("uhuy4xippo")))) return;
    // Oasis Trading Post (uy4xippor7): must be awake
    if($cardID === "uy4xippor7" && $sourceObject->Status != 2) return;
    
    // Ability index is now passed directly from the frontend button click
    $selectedAbilityIndex = intval($abilityIndex);
    // Exhaust the unit as the REST cost — only for static abilities, not dynamic ones (which have their own costs)
    $cardType = CardType($cardID);
    $staticAbilityCount = CardActivateAbilityCount($cardID);
    if($selectedAbilityIndex < $staticAbilityCount && (PropertyContains($cardType, "ALLY") || PropertyContains($cardType, "CHAMPION"))) {
        $sourceObject->Status = 1;
    }

    // Pay non-reserve costs (e.g., banish self) before the opponent gets priority.
    ActivatedAbilityCost($player, $mzCard, $cardID);

    //My activated ability effects
    // Reconstruct which dynamic ability was selected (matching GetDynamicAbilities index assignment)
    $isDynamic = $selectedAbilityIndex >= $staticAbilityCount;
    $handledDynamic = false;
    if($isDynamic) {
        global $lineageReleaseAbilities;
        $dynIndex = $staticAbilityCount;
        // Enlighten check
        if(PropertyContains($cardType, "CHAMPION") && GetCounterCount($sourceObject, "enlighten") >= 3) {
            if($selectedAbilityIndex == $dynIndex) {
                RemoveCounters($player, $mzCard, "enlighten", 3);
                Draw($player, 1);
                $handledDynamic = true;
            }
            $dynIndex++;
        }
        // Lineage Release check
        if(!$handledDynamic && PropertyContains($cardType, "CHAMPION")) {
            $subcards = is_array($sourceObject->Subcards) ? $sourceObject->Subcards : [];
            foreach($subcards as $scIdx => $subcardID) {
                if(isset($lineageReleaseAbilities[$subcardID])) {
                    if($selectedAbilityIndex == $dynIndex) {
                        // Cost: banish the subcard from the inner lineage
                        array_splice($sourceObject->Subcards, $scIdx, 1);
                        MZAddZone($player, "myBanish", $subcardID);
                        // Effect: execute the registered LR ability
                        $lineageReleaseAbilities[$subcardID]['effect']($player);
                        $handledDynamic = true;
                        break;
                    }
                    $dynIndex++;
                }
            }
        }
    }
    if(!$isDynamic) {
        $customDQHandlers["AbilityActivated"]($player, [$cardID, $selectedAbilityIndex], null);
    }

    // Queue Opportunity for the opponent to respond after the ability resolves.
    // Block 200 ensures it runs after all ability decisions (block 1-100).
    DecisionQueueController::AddDecision($player, "CUSTOM", "AbilityOpportunity", 200);

    $dqController = new DecisionQueueController();
    $dqController->ExecuteStaticMethods($player, "-");
}

function OnLeaveField($player, $mzID) {
    global $leaveFieldAbilities;
    $obj = GetZoneObject($mzID);
    if($obj === null) return;
    $controller = $obj->Controller;
    // Check and break any Link connections involving the departing card
    CheckAndBreakLinks($player, $mzID);
    DecisionQueueController::CleanupRemovedCards();
    if(!HasNoAbilities($obj) && isset($leaveFieldAbilities[$obj->CardID . ":0"])) $leaveFieldAbilities[$obj->CardID . ":0"]($controller);
}

function DoAllyDestroyed($player, $mzCard) {
    global $allyDestroyedAbilities;
    $obj = GetZoneObject($mzCard);
    $controller = $obj->Controller;
    $suppressed = HasNoAbilities($obj);
    OnLeaveField($player, $mzCard);
    // Fireworks Display (sx6q3p6i0i): banish instead of graveyard
    $fireworksBanish = GlobalEffectCount($controller, "FIREWORKS_BANISH") > 0;
    if($fireworksBanish) {
        $dest = $player == $controller ? "myBanish" : "theirBanish";
    } else {
        $dest = $player == $controller ? "myGraveyard" : "theirGraveyard";
    }
    MZMove($player, $mzCard, $dest);
    if(!$suppressed && isset($allyDestroyedAbilities[$obj->CardID . ":0"])) {
        $allyDestroyedAbilities[$obj->CardID . ":0"]($controller);
    }
    // Synthetic Core (w0y6isxy5l): whenever a non-token Automaton ally you control dies,
    // you may banish Synthetic Core to return that ally to your memory.
    if(PropertyContains(CardSubtypes($obj->CardID), "AUTOMATON") && !PropertyContains(CardType($obj->CardID), "TOKEN")) {
        global $playerID;
        $controllerField = $controller == $playerID ? "myField" : "theirField";
        $field = GetZone($controllerField);
        for($si = 0; $si < count($field); ++$si) {
            if(!$field[$si]->removed && $field[$si]->CardID === "w0y6isxy5l" && !HasNoAbilities($field[$si])) {
                DecisionQueueController::AddDecision($controller, "YESNO", "-", 1, tooltip:"Banish_Synthetic_Core_to_return_ally_to_memory?");
                DecisionQueueController::AddDecision($controller, "CUSTOM", "SyntheticCoreChoice|$si|" . $obj->CardID, 1);
                break;
            }
        }
    }
}

function WakeUpPhase() {
    // Wake Up phase — ready all cards on the turn player's field
    SetFlashMessage("Wake Up Phase");
    $turnPlayer = &GetTurnPlayer();
    $otherPlayer = ($turnPlayer == 1) ? 2 : 1;
    $field = &GetField($turnPlayer);

    // Check if opponent controls Snow Fairy (4s0c9XgLg7)
    $opponentField = &GetField($otherPlayer);
    $opponentHasSnowFairy = false;
    foreach($opponentField as $opp) {
        if(!$opp->removed && $opp->CardID === "4s0c9XgLg7") {
            $opponentHasSnowFairy = true;
            break;
        }
    }

    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed) {
            // SKIP_WAKEUP: one-time skip — consume the effect and don't wake
            if(in_array("SKIP_WAKEUP", $field[$i]->TurnEffects)) {
                $field[$i]->TurnEffects = array_values(array_diff($field[$i]->TurnEffects, ["SKIP_WAKEUP"]));
                continue;
            }
            // FROZEN_BY_SNOW_FAIRY: persistent freeze while opponent controls Snow Fairy
            if(in_array("FROZEN_BY_SNOW_FAIRY", $field[$i]->TurnEffects)) {
                if($opponentHasSnowFairy) {
                    continue; // Still frozen — don't wake, keep the effect
                } else {
                    // Snow Fairy gone — remove the effect, card will wake normally
                    $field[$i]->TurnEffects = array_values(array_diff($field[$i]->TurnEffects, ["FROZEN_BY_SNOW_FAIRY"]));
                }
            }
            // SPELLSHROUD_NEXT_TURN / STEALTH_NEXT_TURN: expire at beginning of controller's next turn
            if(in_array("SPELLSHROUD_NEXT_TURN", $field[$i]->TurnEffects)) {
                $field[$i]->TurnEffects = array_values(array_diff($field[$i]->TurnEffects, ["SPELLSHROUD_NEXT_TURN"]));
            }
            if(in_array("STEALTH_NEXT_TURN", $field[$i]->TurnEffects)) {
                $field[$i]->TurnEffects = array_values(array_diff($field[$i]->TurnEffects, ["STEALTH_NEXT_TURN"]));
            }
            // Blazing Charge (s5jwsl7ded): expire damage amp at beginning of controller's next turn
            if(in_array("BLAZING_CHARGE_NEXT_TURN", $field[$i]->TurnEffects)) {
                $field[$i]->TurnEffects = array_values(array_diff($field[$i]->TurnEffects, ["BLAZING_CHARGE_NEXT_TURN"]));
            }
            // TAUNT_NEXT_TURN / VIGOR_NEXT_TURN: expire at beginning of controller's next turn
            if(in_array("TAUNT_NEXT_TURN", $field[$i]->TurnEffects)) {
                $field[$i]->TurnEffects = array_values(array_diff($field[$i]->TurnEffects, ["TAUNT_NEXT_TURN"]));
            }
            if(in_array("VIGOR_NEXT_TURN", $field[$i]->TurnEffects)) {
                $field[$i]->TurnEffects = array_values(array_diff($field[$i]->TurnEffects, ["VIGOR_NEXT_TURN"]));
            }
            $field[$i]->Status = 2;
        }
    }
}

function OnEnter($player, $mzID) {
    global $enterAbilities;
    $obj = GetZoneObject($mzID);
    $CardID = $obj->CardID;
    DecisionQueueController::CleanupRemovedCards();
    if(HasNoAbilities($obj)) return;
    if(isset($enterAbilities[$CardID . ":0"])) $enterAbilities[$CardID . ":0"]($player);
}

function FieldAfterAdd($player, $CardID="-", $Status=2, $Owner="-", $Damage=0, $Controller="-", $TurnEffects="-", $Counters="-", $Subcards="-") {
    $field = &GetField($player);
    $added = $field[count($field)-1];
    $added->Controller = $player;
    if($added->Owner == 0) $added->Owner = $player;
    
    // Hindered keyword: this object enters the field rested
    if(HasHindered($added)) {
        $added->Status = 1;
    }
    // Crusader of Aesa (2Q60hBYO3i): enters the field rested (card text, not keyword)
    if($added->CardID == "2Q60hBYO3i") {
        $added->Status = 1;
    }
    // Luxera's Map (s23UHXgcZq): enters the field rested (card text, not keyword)
    if($added->CardID == "s23UHXgcZq") {
        $added->Status = 1;
    }
    // Artificer's Opus (G5E0PIUd0W): enters the field rested (card text, not keyword)
    if($added->CardID == "G5E0PIUd0W") {
        $added->Status = 1;
    }
    
    // Weapons enter with durability counters equal to their printed durability stat
    if(PropertyContains(CardType($added->CardID), "WEAPON")) {
        $durability = CardDurability($added->CardID);
        if($durability !== null && $durability > 0) {
            AddCounters($player, "myField-" . (count($field) - 1), "durability", $durability);
        }
    }

    // Ally Link: if the entering card has Ally Link, establish the link via Subcards
    global $AllyLink_Cards;
    if(isset($AllyLink_Cards[$added->CardID])) {
        $linkTargetMZ = DecisionQueueController::GetVariable("linkTargetMZ");
        if(!empty($linkTargetMZ) && $linkTargetMZ !== "-") {
            $phantasiaMZ = "myField-" . (count($field) - 1);
            CreateAllyLink($player, $phantasiaMZ, $linkTargetMZ);
            DecisionQueueController::StoreVariable("linkTargetMZ", ""); // Clear after use
        }
    }

    // Silvie, Wilds Whisperer (RfPP8h16Wv): next Animal/Beast ally enters with a buff counter
    if(PropertyContains(CardType($added->CardID), "ALLY")) {
        $subtypes = CardSubtypes($added->CardID);
        if(PropertyContains($subtypes, "ANIMAL") || PropertyContains($subtypes, "BEAST")) {
            if(GlobalEffectCount($player, "RfPP8h16Wv") > 0) {
                AddCounters($player, "myField-" . (count($field) - 1), "buff", 1);
                // Remove one instance of the global effect
                $ge = &GetGlobalEffects($player);
                foreach($ge as $gIdx => $geItem) {
                    if($geItem->CardID === "RfPP8h16Wv") {
                        $ge[$gIdx]->removed = true;
                        break;
                    }
                }
            }
        }
    }

    // Crystalline Mirror (9agwj4f15j): whenever a phantasia enters the field under your control, glimpse 1
    if(PropertyContains(CardType($added->CardID), "PHANTASIA")) {
        for($cm = 0; $cm < count($field); ++$cm) {
            if(!$field[$cm]->removed && $field[$cm]->CardID === "9agwj4f15j" && !HasNoAbilities($field[$cm])) {
                Glimpse($player, 1);
                break;
            }
        }
    }

    // Fractal of Snow (uhuy4xippo): next allies enter rested
    if(PropertyContains(CardType($added->CardID), "ALLY")) {
        if(GlobalEffectCount($player, "uhuy4xippo") > 0) {
            $added->Status = 1;
            RemoveGlobalEffect($player, "uhuy4xippo");
        }
    }
    
    Enter($player, $field[count($field)-1]->GetMzID());
}

function RecollectionPhase() {
    // Recollection phase
    SetFlashMessage("Recollection Phase");
    $turnPlayer = &GetTurnPlayer();
    
    // --- Domain Recollection Upkeep ---
    // Process domain upkeep checks that trigger "at the beginning of your recollection phase".
    // Must run BEFORE memory is returned to hand, since the checks reveal memory cards.
    DomainRecollectionUpkeep($turnPlayer);

    // Peaceful Reunion: clear attack-prevention at the beginning of the caster's next turn
    if(GlobalEffectCount($turnPlayer, "wr42i6eifn") > 0) {
        RemoveGlobalEffect($turnPlayer, "wr42i6eifn");
    }

    // Plea for Peace: clear attack tax at the beginning of the caster's next turn
    if(GlobalEffectCount($turnPlayer, "ir99sx6q3p") > 0) {
        RemoveGlobalEffect($turnPlayer, "ir99sx6q3p");
    }
    
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
                case "P7hHZBVScB": // Orb of Glitter: glimpse 1 during recollection
                    Glimpse($turnPlayer, 1);
                    break;
                case "ZfCtSldRIy": // Windrider Mage: CB may return to hand + enlighten
                    if(IsClassBonusActive($turnPlayer, CardClasses("ZfCtSldRIy"))) {
                        DecisionQueueController::AddDecision($turnPlayer, "YESNO", "-", 1, tooltip:"Return_Windrider_Mage_to_hand?");
                        DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "WindriderMageBounce|$i", 1);
                    }
                    break;
                case "ka5av43ehj": // Morgan, Soul Guide: [CB] Glimpse 1 or Recover 1
                    if(!HasNoAbilities($field[$i]) && IsClassBonusActive($turnPlayer, CardClasses("ka5av43ehj"))) {
                        DecisionQueueController::AddDecision($turnPlayer, "YESNO", "-", 1, tooltip:"Glimpse_1?_(No=Recover_1)");
                        DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "MorganSoulGuideRecollection", 1);
                    }
                    break;
                case "ao8bls6g7x": // Healing Aura: recover 1 at beginning of recollection phase
                    if(!HasNoAbilities($field[$i])) {
                        RecoverChampion($turnPlayer, 1);
                    }
                    break;
                case "c7wklzjmwu": // Palatial Concourse: glimpse 1 at beginning of recollection phase
                    if(!HasNoAbilities($field[$i])) {
                        Glimpse($turnPlayer, 1);
                    }
                    break;
                case "fzcyfrzrpl": // Heatwave Generator: target ally gets +1 POWER until end of turn
                    if(!HasNoAbilities($field[$i])) {
                        $allies = ZoneSearch("myField", ["ALLY"]);
                        if(!empty($allies)) {
                            DecisionQueueController::AddDecision($turnPlayer, "MZCHOOSE", implode("&", $allies), 1, tooltip:"Choose_ally_for_+1_POWER");
                            DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "HeatwaveGeneratorBuff", 1);
                        }
                    }
                    break;
                case "fyoz23yfzk": // The Eternal Kingdom: pay 2 or sacrifice
                    if(!HasNoAbilities($field[$i]) && !in_array("NO_UPKEEP", $field[$i]->TurnEffects)) {
                        DecisionQueueController::AddDecision($turnPlayer, "YESNO", "-", 1, tooltip:"Pay_2_to_keep_The_Eternal_Kingdom?");
                        DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "EternalKingdomUpkeep|$i", 1);
                    }
                    break;
                case "t3q2svd53z": // Aqueous Armor: mill 1 at beginning of recollection phase
                    if(!HasNoAbilities($field[$i])) {
                        MillCards($turnPlayer, "myDeck", "myGraveyard", 1);
                    }
                    break;
                case "ta6qsesw2u": // Tonoris, Genesis Aegis: choose one that hasn't been chosen — summon Obelisk token
                    if(!HasNoAbilities($field[$i])) {
                        TonorisRecollection($turnPlayer, $i);
                    }
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

/**
 * Suppress an ally: banish it and schedule its return at the beginning of the next end phase.
 * The card is moved to its owner's banishment zone and tagged with a "SUPPRESSED" TurnEffect
 * on the banished card itself so EndPhase can find and return it.
 * @param int    $player  The acting player.
 * @param string $mzCard  The mzID of the ally to suppress (e.g. "theirField-2").
 */
function SuppressAlly($player, $mzCard) {
    $obj = GetZoneObject($mzCard);
    if($obj === null) return;
    $owner = $obj->Owner;
    OnLeaveField($player, $mzCard);
    // Move to the owner's banishment zone
    $banishZone = ($player == $owner) ? "myBanish" : "theirBanish";
    $banishObj = MZMove($player, $mzCard, $banishZone);
    if($banishObj !== null) {
        // Clear any field TurnEffects carried over by the zone copy, then tag as suppressed
        $banishObj->ClearTurnEffects();
        $banishObj->AddTurnEffects("SUPPRESSED");
    }
}

function BeforeEndPhase() {
    global $playerID;

    // BANISH_SELF TurnEffect: move any field card tagged BANISH_SELF to banishment.
    // Uses "my"/"their" zone names relative to $playerID — no $playerID mutation needed.
    $field = GetZone("myField");
    for($fi = count($field) - 1; $fi >= 0; --$fi) {
        if(!$field[$fi]->removed && in_array("BANISH_SELF", $field[$fi]->TurnEffects)) {
            OnLeaveField($playerID, "myField-" . $fi);
            MZMove($playerID, "myField-" . $fi, "myBanish");
        }
    }
    $field = GetZone("theirField");
    for($fi = count($field) - 1; $fi >= 0; --$fi) {
        if(!$field[$fi]->removed && in_array("BANISH_SELF", $field[$fi]->TurnEffects)) {
            OnLeaveField($playerID, "theirField-" . $fi);
            MZMove($playerID, "theirField-" . $fi, "theirBanish");
        }
    }

    // Suppress: return suppressed allies from banishment to the field under their owner's control.
    // Iterate highest-index-first so that splicing doesn't shift unvisited entries.
    // Zone references ("my"/"their") are resolved relative to global $playerID.
    $banish = GetZone("myBanish");
    for($sbi = count($banish) - 1; $sbi >= 0; --$sbi) {
        if(!$banish[$sbi]->removed && in_array("SUPPRESSED", $banish[$sbi]->TurnEffects)) {
            MZMove($playerID, "myBanish-" . $sbi, "myField");
        }
    }
    $banish = GetZone("theirBanish");
    for($sbi = count($banish) - 1; $sbi >= 0; --$sbi) {
        if(!$banish[$sbi]->removed && in_array("SUPPRESSED", $banish[$sbi]->TurnEffects)) {
            MZMove($playerID, "theirBanish-" . $sbi, "theirField");
        }
    }

    // MEM_BANISHED: return banished memory cards to their owner's memory.
    // Only scan myBanish so the return fires on the banished card's owner's end phase,
    // not the opponent's end phase. (The card sits in theirBanish from the acting player's
    // perspective, which becomes myBanish when that player's own end phase runs.)
    $banish = GetZone("myBanish");
    for($sbi = count($banish) - 1; $sbi >= 0; --$sbi) {
        if(!$banish[$sbi]->removed && in_array("MEM_BANISHED", $banish[$sbi]->TurnEffects)) {
            MZMove($playerID, "myBanish-" . $sbi, "myMemory");
        }
    }
}

function EndPhase() {
    $firstPlayer = &GetFirstPlayer();
    $currentTurn = &GetTurnNumber();
    $turnPlayer = &GetTurnPlayer();

    // Clear any remaining intent cards (unused attack cards) to graveyard
    ClearIntent($turnPlayer);

    // Forgelight Scepter (smw3rrii17): at beginning of each opponent's end phase,
    // if that player has odd cards in memory, deal 2 unpreventable damage to their champion
    $otherPlayer = ($turnPlayer == 1) ? 2 : 1;
    $forgeField = &GetField($otherPlayer);
    for($fi = 0; $fi < count($forgeField); ++$fi) {
        if(!$forgeField[$fi]->removed && $forgeField[$fi]->CardID === "smw3rrii17" && !HasNoAbilities($forgeField[$fi])) {
            $tpMemory = &GetMemory($turnPlayer);
            if(count($tpMemory) % 2 == 1) {
                // Deal 2 unpreventable damage directly to turn player's champion
                $champField = &GetField($turnPlayer);
                for($ci = 0; $ci < count($champField); ++$ci) {
                    if(!$champField[$ci]->removed && PropertyContains(EffectiveCardType($champField[$ci]), "CHAMPION")) {
                        $champField[$ci]->Damage += 2;
                        break;
                    }
                }
            }
            break;
        }
    }

    // Mistbound Watcher (mA4n0Z7BQz): CB add 1 enlighten counter on champion at end of turn
    $field = &GetField($turnPlayer);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "mA4n0Z7BQz") {
            if(IsClassBonusActive($turnPlayer, CardClasses("mA4n0Z7BQz"))) {
                // Find champion and add enlighten counter
                for($ci = 0; $ci < count($field); ++$ci) {
                    if(!$field[$ci]->removed && EffectiveCardType($field[$ci]) === "CHAMPION") {
                        AddCounters($turnPlayer, "myField-" . $ci, "enlighten", 1);
                        break;
                    }
                }
            }
            break; // Only one Mistbound Watcher matters
        }
    }

    // Windwalker Boots (73fdt8ptrz): [Class Bonus] if champion is awake, add preparation counter
    $field = &GetField($turnPlayer);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "73fdt8ptrz") {
            if(IsClassBonusActive($turnPlayer, ["ASSASSIN"])) {
                for($ci = 0; $ci < count($field); ++$ci) {
                    if(!$field[$ci]->removed && PropertyContains(EffectiveCardType($field[$ci]), "CHAMPION")) {
                        if($field[$ci]->Status == 2) { // Awake
                            AddCounters($turnPlayer, "myField-" . $ci, "preparation", 1);
                        }
                        break;
                    }
                }
            }
            break;
        }
    }

    // Tristan, Grim Stalker (K5luT8aRzc): At beginning of your end phase, if Tristan is awake, put a preparation counter on Tristan.
    $field = &GetField($turnPlayer);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "K5luT8aRzc") {
            if($field[$i]->Status == 2) { // Awake (Status 2 = ready)
                AddCounters($turnPlayer, "myField-" . $i, "preparation", 1);
            }
            break;
        }
    }

    $field = &GetField($turnPlayer);
    for($i=count($field)-1; $i>=0; --$i) {
        if(HasVigor($field[$i])) {
            $field[$i]->Status = 2; // Vigor units ready themselves at end of turn
        }
        // Attune with Flames (nvx7mnu1xh): clear ATTUNE_FLAMES_BUFF at end of controller's turn
        if(in_array("ATTUNE_FLAMES_BUFF", $field[$i]->TurnEffects)) {
            $field[$i]->TurnEffects = array_values(array_diff($field[$i]->TurnEffects, ["ATTUNE_FLAMES_BUFF"]));
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
    if($power === null || $power < 0) $power = 0;
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
        case "jF1VuIR7a6": // Warrior's Longsword: [Class Bonus] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["WARRIOR"])) {
                $power += 1;
            }
            break;
        case "dpu9pHGX48": // Sword of Adversity: [Class Bonus] +1 POWER while no allies
            if(IsClassBonusActive($obj->Controller, ["WARRIOR"])) {
                global $playerID;
                $zone = $obj->Controller == $playerID ? "myField" : "theirField";
                if(empty(ZoneSearch($zone, ["ALLY"]))) {
                    $power += 1;
                }
            }
            break;
        case "krgjMyVHRd": // Lakeside Serpent: [Class Bonus] +1 POWER per water card in graveyard
            if(IsClassBonusActive($obj->Controller, ["TAMER"])) {
                global $playerID;
                $gravZone = $obj->Controller == $playerID ? "myGraveyard" : "theirGraveyard";
                $waterCards = ZoneSearch($gravZone, cardElements: ["WATER"]);
                $power += count($waterCards);
            }
            break;
        case "vBetRTn3eW": // Opening Cut: [Class Bonus] +2 POWER while exactly 1 card in memory
            if(IsClassBonusActive($obj->Controller, ["WARRIOR"])) {
                $memory = &GetMemory($obj->Controller);
                if(count($memory) == 1) {
                    $power += 2;
                }
            }
            break;
        case "a4dk88zq9o": // Varuckan Acolyte: [Level 3+] +3 POWER
            if(PlayerLevel($obj->Controller) >= 3) {
                $power += 3;
            }
            break;
        case "sxg6WefxIe": // Backstab: [Class Bonus] +2 POWER while attacking rested unit
            if(IsClassBonusActive($obj->Controller, ["ASSASSIN"])) {
                $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
                if($combatTarget != "-" && $combatTarget != "") {
                    $targetObj = GetZoneObject($combatTarget);
                    if($targetObj !== null && isset($targetObj->Status) && $targetObj->Status == 1) {
                        $power += 2;
                    }
                }
            }
            break;
        case "LNSRQ5xW6E": // Stillwater Patrol: +1 POWER while attacking a unit with stealth
            $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
            if($combatTarget != "-" && $combatTarget != "") {
                $targetObj = GetZoneObject($combatTarget);
                if($targetObj !== null && HasStealth($targetObj)) {
                    $power += 1;
                }
            }
            break;
        case "3traenEA8M": // Galatine, Sword of Sunlight: [Class Bonus] +1 POWER per 3 durability counters
            if(IsClassBonusActive($obj->Controller, ["WARRIOR"])) {
                $durability = GetCounterCount($obj, "durability");
                $power += intdiv($durability, 3);
            }
            break;
        case "W1g0hNzXAC": // Invigorated Slash: +2 POWER while champion leveled up this turn
            if(GlobalEffectCount($obj->Controller, "LEVELED_UP_THIS_TURN") > 0) {
                $power += 2;
            }
            break;
        case "TgYTZg6TaG": // Wind Cutter: [Class Bonus] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["RANGER", "WARRIOR"])) {
                $power += 1;
            }
            break;
        case "1gxrpx8jyp": // Fanatical Devotee: [Memory 4+] +1 POWER
            $memory = &GetMemory($obj->Controller);
            if(count($memory) >= 4) $power += 1;
            break;
        case "2tsn0ye3ae": // Allied Warpriestess: [Class Bonus] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["CLERIC", "GUARDIAN"])) $power += 1;
            break;
        case "WUAOMTZ7P2": // Intrepid Highwayman: +3 POWER while retaliating
            if(DecisionQueueController::GetVariable("CombatRetaliator") !== null) {
                $power += 3;
            }
            break;
        case "88zq9ox7u6": // Seeking Shot: +3 POWER while attacking a Human ally
            $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
            if($combatTarget != "-" && $combatTarget != "") {
                $targetObj = GetZoneObject($combatTarget);
                if($targetObj !== null && PropertyContains(CardSubtypes($targetObj->CardID), "HUMAN")) {
                    $power += 3;
                }
            }
            break;
        case "9q1wl8ao8b": // Crimson Tear: [Level 1+] +1 POWER while attacking/retaliating vs Human
            if(PlayerLevel($obj->Controller) >= 1) {
                $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
                if($combatTarget != "-" && $combatTarget != "") {
                    $targetObj = GetZoneObject($combatTarget);
                    if($targetObj !== null && PropertyContains(CardSubtypes($targetObj->CardID), "HUMAN")) {
                        $power += 1;
                    }
                }
            }
            break;
        case "bcizm6h38l": // Subjugating Lash: +2 POWER while champion has 12+ damage
            {
                $controller = $obj->Controller;
                $champField = &GetField($controller);
                foreach($champField as $champObj) {
                    if(!$champObj->removed && PropertyContains(EffectiveCardType($champObj), "CHAMPION")) {
                        if($champObj->Damage >= 12) {
                            $power += 2;
                        }
                        break;
                    }
                }
            }
            break;
        case "jwsl7dedg6": // Neos Elemental: +1 POWER per token object you control
            {
                global $playerID;
                $zone = $obj->Controller == $playerID ? "myField" : "theirField";
                $tokenCount = count(ZoneSearch($zone, ["TOKEN"]));
                $power += $tokenCount;
            }
            break;
        case "m4c8ljyevp": // Academy Attendant: [Class Bonus][Memory 4+] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["CLERIC"])) {
                $memory = &GetMemory($obj->Controller);
                if(count($memory) >= 4) $power += 1;
            }
            break;
        case "s5jwsl7ded": // Blazing Charge: [Class Bonus] +2 POWER
            if(IsClassBonusActive($obj->Controller, ["GUARDIAN"])) {
                $power += 2;
            }
            break;
        case "ot4nmxqsm4": // Inzali, Unshackled Blaze: [Level 3+][Memory 4+] +2 POWER
            if(PlayerLevel($obj->Controller) >= 3) {
                $memory = &GetMemory($obj->Controller);
                if(count($memory) >= 4) $power += 2;
            }
            break;
        default: break;
    }
    // Field-presence passives — Banner Knight gives +1 POWER to other allies and weapons
    if($obj->Controller != -1 && !PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fieldObj) {
            if($fieldObj->CardID === "IAkuSSnzYB") { // Banner Knight: [Class Bonus][Level 2+] Other allies and weapons get +1 POWER
                if($obj->CardID !== "IAkuSSnzYB" &&
                   (PropertyContains(EffectiveCardType($obj), "ALLY") || PropertyContains(EffectiveCardType($obj), "WEAPON")) &&
                   IsClassBonusActive($obj->Controller, ["WARRIOR"]) &&
                   PlayerLevel($obj->Controller) >= 2) {
                    $power += 1;
                }
                break; // Only count the first Banner Knight (duplicates don't stack)
            }
        }
        // Exalted Dorumegian Throne (p4lpnvx7mn): allies get +1 POWER
        if(PropertyContains(EffectiveCardType($obj), "ALLY")) {
            foreach($field as $fieldObj) {
                if(!$fieldObj->removed && $fieldObj->CardID === "p4lpnvx7mn") {
                    $power += 1;
                    break;
                }
            }
        }
    }
    $cardCurrentEffects = explode(",", CardCurrentEffects($obj));
    foreach($cardCurrentEffects as $effectID) {
        switch($effectID) {
            case "vcZSHNHvKX": // Spirit Blade: Ghost Strike
                $power += 1;
                break;
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
            case "qyQLlDYBlr": // Ornamental Greatsword: +1 POWER until end of turn
                $power += 1;
                break;
            case "XMb6pSHFJg": // Embersong class bonus: +2 POWER until end of turn
                $power += 2;
                break;
            case "W1vZwOXfG3": // Embertail Squirrel: +2 POWER until end of turn
                $power += 2;
                break;
            case "usb5FgKvZX": // Sharpening Stone: +1 POWER until end of turn
                $power += 1;
                break;
            case "F1t18omUlx_POWER": // Beastbond Paws: +1 POWER until end of turn
                $power += 1;
                break;
            case "OofVX5hX8X": // Poisoned Coating Oil: +2 POWER until end of turn
                $power += 2;
                break;
            case "8yzADlgx4R": // Assassin's Ripper: +2 POWER until end of turn
                $power += 2;
                break;
            case "QQaOgurnjX": // Imbue in Frost: +2 POWER until end of turn
                $power += 2;
                break;
            case "vcZSHNHvKX": // Spirit Blade: Ghost Strike: champion attacks +1 POWER
                $power += 1;
                break;
            case "dZ960Hnkzv": // Vertus, Gaia's Roar: +1 POWER until end of turn
                $power += 1;
                break;
            case "rxxwQT054x": // Command the Hunt: +2 POWER until end of turn
                $power += 2;
                break;
            case "HsaWNAsmAQ_POWER": // Bestial Frenzy: +1 POWER until end of turn
                $power += 1;
                break;
            case "GRkBQ1Uvir_POWER": // Ignited Stab: if prepared, +2 POWER until end of turn
                $power += 2;
                break;
            case "qufoIF014c_POWER": // Gleaming Cut: revealed luxem card from memory, +2 POWER
                $power += 2;
                break;
            case "i6eifnz0fg": // Zephyr's Edge: [CB] +1 POWER until end of turn (entered outside materialize phase)
                $power += 1;
                break;
            case "yuvuxnrw8q": // Hone by Fire: +2 POWER until end of turn
                $power += 2;
                break;
            case "sdbzr5zs29-debuff": // Corhazi Trapper: target unit's attacks get -3 POWER until end of turn
                $power -= 3;
                break;
            case "lx6xwr42i6": // Windrider Invoker: +3 POWER until end of turn
                $power += 3;
                break;
            case "w9n0wpbhig": // Lancelot, Goliath of Aesa: +3 POWER until end of turn
                $power += 3;
                break;
            case "suo6gb0op3": // Fractured Crown: first attack each turn +2 POWER
                $power += 2;
                break;
            case "huqj5bbae3": // Winds of Retribution: +2 POWER until end of turn
                $power += 2;
                break;
            case "fzcyfrzrpl": // Heatwave Generator: +1 POWER until end of turn
                $power += 1;
                break;
            case "qmyn2rz308": // Flameblessed Trainee: +3 POWER from fire discard
                $power += 3;
                break;
            case "i1f0ht2tsn": // Strategic Warfare: allies get +1 POWER until end of turn
                $power += 1;
                break;
            case "ATTUNE_FLAMES_BUFF": // Attune with Flames: +5 POWER until end of next turn
                $power += 5;
                break;
            default:
                // Imperious Highlander: dynamic +X POWER until end of turn (effect ID: 659ytyj2s3-X)
                if(strpos($effectID, "659ytyj2s3-") === 0) {
                    $power += intval(substr($effectID, strlen("659ytyj2s3-")));
                }
                // Smash with Obelisk: +X POWER from sacrificed domain (effect ID: 2kkvoqk1l7-X)
                if(strpos($effectID, "2kkvoqk1l7-") === 0) {
                    $power += intval(substr($effectID, strlen("2kkvoqk1l7-")));
                }
                // Amorphous Strike: +X POWER from banished attack card (effect ID: 5kt3q2svd5-X)
                if(strpos($effectID, "5kt3q2svd5-") === 0) {
                    $power += intval(substr($effectID, strlen("5kt3q2svd5-")));
                }
                break;
        }
    }
    // Lorraine, Blademaster (TJTeWcZnsQ): if champion has TJTeWcZnsQ TurnEffect,
    // all attack cards get +2 POWER until end of turn.
    if(PropertyContains(EffectiveCardType($obj), "ATTACK")) {
        $controller = $obj->Controller ?? null;
        if($controller !== null && $controller > 0) {
            $field = GetField($controller);
            foreach($field as $fieldObj) {
                if(PropertyContains(EffectiveCardType($fieldObj), "CHAMPION") && in_array("TJTeWcZnsQ", $fieldObj->TurnEffects)) {
                    $power += 2;
                    break;
                }
            }
        }
    }
    // Wand of Frost (n0wpbhigka): if the attacking unit has n0wpbhigka TurnEffect,
    // attacks from that unit get -3 POWER until end of turn.
    $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
    if($combatAttacker !== null && $combatAttacker != "-" && $combatAttacker != "" && $obj->GetMzID() === $combatAttacker) {
        if($obj !== null && in_array("n0wpbhigka", $obj->TurnEffects)) {
                $power -= 3;
        }
    }
    // Conjure Downpour (r0zadf9q1w): whenever a unit attacks, that attack gets -2 POWER
    if($combatAttacker !== null && $combatAttacker != "-" && $combatAttacker != "" && $obj->GetMzID() === $combatAttacker) {
        $p1Count = GlobalEffectCount(1, "r0zadf9q1w");
        $p2Count = GlobalEffectCount(2, "r0zadf9q1w");
        $power -= 2 * ($p1Count + $p2Count);
    }
    // Ally Link: check for power bonuses from linked Phantasia cards via Subcards
    $linkedCards = GetLinkedCards($obj);
    foreach($linkedCards as $linkedObj) {
        switch($linkedObj->CardID) {
            case "80mttsvbgl": // Mark of Fervor: linked ally gets +1 POWER
                $power += 1;
                break;
            case "c8ljyevpmu": // Alliance Gearshield: [Class Bonus] +2 POWER while retaliating
                if(IsClassBonusActive($obj->Controller, ["GUARDIAN"])
                    && DecisionQueueController::GetVariable("CombatRetaliator") !== null) {
                    $power += 2;
                }
                break;
            default: break;
        }
    }
    // Zander, Always Watching (tOK1Gr0N8f) — Inherited Effect:
    // +1 POWER to attacks while attacking a rested unit.
    // Applies when tOK1Gr0N8f is in the champion's lineage (current champion or subcards).
    if(PropertyContains(EffectiveCardType($obj), "ATTACK")) {
        $controller = $obj->Controller ?? null;
        if($controller !== null && $controller > 0 && ChampionHasInLineage($controller, "tOK1Gr0N8f")) {
            $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
            if($combatTarget != "-" && $combatTarget != "") {
                $targetObj = GetZoneObject($combatTarget);
                if($targetObj !== null && isset($targetObj->Status) && $targetObj->Status == 1) {
                    $power += 1;
                }
            }
        }
    }
    // Into the Fray (tu9agwj4f1): +N POWER until end of turn (N encoded in TurnEffect)
    foreach($obj->TurnEffects as $te) {
        if(strpos($te, "tu9agwj4f1-") === 0) {
            $power += intval(substr($te, strlen("tu9agwj4f1-")));
        }
    }
    return $power;
}

function ObjectCurrentLevel($obj) {
    $cardLevel = CardLevel($obj->CardID);
    // Level counter modifier: +1 level per level counter on this card
    $cardLevel += GetCounterCount($obj, "level");
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
            case "zpkcFs72Ah"://Smack with Flute: champion gets +1 level until end of turn
                $cardLevel += 1;
                break;
            case "XLrHaYV9VB": // Arcane Sight: +1 level until end of turn
                $cardLevel += 1;
                break;
            case "MECS7RHRZ8": // Impassioned Tutor: +1 level until end of turn
                $cardLevel += 1;
                break;
            case "raG5r85ieO": // Piper's Lullaby: +1 level until end of turn
                $cardLevel += 1;
                break;
            case "HsaWNAsmAQ": // Bestial Frenzy: +1 level until end of turn
                $cardLevel += 1;
                break;
            case "aKgdkLSBza": // Wilderness Harpist: +1 level until end of turn
                $cardLevel += 1;
                break;
            case "5joh300z2s": // Manaroot: +1 level until end of turn
                $cardLevel += 1;
                break;
            case "i0a5uhjxhk": // Blightroot: +1 level until end of turn
                $cardLevel += 1;
                break;
            default:
                // Erupting Rhapsody (dBAdWMoPEz): +1 level per banished card, encoded as "dBAdWMoPEz-N"
                if(strpos($effectID, "dBAdWMoPEz-") === 0) {
                    $cardLevel += intval(substr($effectID, strlen("dBAdWMoPEz-")));
                }
                break;
        }
    }
    // Field-presence passives — iterate once and switch on card ID
    // Each unique card's passive is only counted once (duplicates don't stack)
    if(PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
        // Champion self-level modifiers
        if($obj->CardID === "YPaL2BxDSN") { // Allen, Beast Beckoner: +2 level while controlling 2+ Animal/Beast allies
            global $playerID;
            $zone = $obj->Controller == $playerID ? "myField" : "theirField";
            $animalBeast = ZoneSearch($zone, ["ALLY"], cardSubtypes: ["ANIMAL", "BEAST"]);
            if(count($animalBeast) >= 2) {
                $cardLevel += 2;
            }
        }
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
                case "pnDhApDNvR": // Magus Disciple: champion gets +1 level
                    $cardLevel += 1;
                    $appliedPassives[$fID] = true;
                    break;
                case "q2okpDFJw5": // Energetic Beastbonder: +1 level while Animal/Beast
                case "qxbdXU7H4Z": // Deep Sea Beastbonder: same
                case "izGEjxBPo9": // Menagerie Beastbonder: same
                case "JPcFmCpdiF": // Beastbond Ears: +1 level while Animal/Beast ally
                    if(!empty(ZoneSearch($zone, ["ALLY"], cardSubtypes: ["ANIMAL", "BEAST"]))) {
                        $cardLevel += 1;
                    }
                    $appliedPassives[$fID] = true;
                    break;
                case "WAFNy2lY5t": // Melodious Flute: +1 level while Animal/Beast
                    if(!empty(ZoneSearch($zone, ["ALLY"], cardSubtypes: ["ANIMAL", "BEAST"]))) {
                        $cardLevel += 1;
                    }
                    $appliedPassives[$fID] = true;
                    break;
                case "umSsPWqb5H": // Merlin, Memory Thief: +1 level per level counter
                    $cardLevel += GetCounterCount($fieldObj, "level");
                    $appliedPassives[$fID] = true;
                    break;
                case "akb1k0zi5h": // Effigy of Gaia: [Class Bonus] +1 level while controlling Animal/Beast ally
                    if(IsClassBonusActive($obj->Controller, ["TAMER"])) {
                        if(!empty(ZoneSearch($zone, ["ALLY"], cardSubtypes: ["ANIMAL", "BEAST"]))) {
                            $cardLevel += 1;
                        }
                    }
                    $appliedPassives[$fID] = true;
                    break;
                default: break;
            }
        }
        // Material zone passives — items that grant level while materialized
        $matZone = GetMaterial($obj->Controller);
        foreach($matZone as $matObj) {
            if($matObj->removed) continue;
            switch($matObj->CardID) {
                case "yDARN8eV6B": // Tome of Knowledge: [Class Bonus] champion gets +1 level
                    if(IsClassBonusActive($obj->Controller, ["MAGE"])) {
                        $cardLevel += 1;
                    }
                    break;
                case "j5iQQPd2m5": // Crystal of Argus: [Class Bonus] +1 level per 3 enlighten counters on champion
                    if(IsClassBonusActive($obj->Controller, ["MAGE"])) {
                        $champField = GetZone($zone);
                        foreach($champField as $cObj) {
                            if(PropertyContains(CardType($cObj->CardID), "CHAMPION")) {
                                $enlighten = GetCounterCount($cObj, "enlighten");
                                $cardLevel += intdiv($enlighten, 3);
                                break;
                            }
                        }
                    }
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
        case "w9n0wpbhig": // Lancelot, Goliath of Aesa: [Class Bonus][Level 2+] +2 LIFE
            if(IsClassBonusActive($obj->Controller, ["GUARDIAN", "WARRIOR"]) && PlayerLevel($obj->Controller) >= 2) {
                $cardLife += 2;
            }
            break;
        case "5swaf8urrq": // Whirlwind Vizier: [Class Bonus] +1 LIFE
            if(IsClassBonusActive($obj->Controller, ["CLERIC"])) {
                $cardLife += 1;
            }
            break;
        case "jwsl7dedg6": // Neos Elemental: +1 LIFE per token object you control
            {
                global $playerID;
                $zone = $obj->Controller == $playerID ? "myField" : "theirField";
                $tokenCount = count(ZoneSearch($zone, ["TOKEN"]));
                $cardLife += $tokenCount;
            }
            break;
        default: break;
    }
    // Exalted Dorumegian Throne (p4lpnvx7mn): allies get +1 LIFE
    if(PropertyContains(EffectiveCardType($obj), "ALLY") && $obj->Controller != -1) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fieldObj) {
            if(!$fieldObj->removed && $fieldObj->CardID === "p4lpnvx7mn") {
                $cardLife += 1;
                break;
            }
        }
    }
    // Fractured Crown (suo6gb0op3): [Class Bonus] champion gets +2 LIFE per unique ally card in GY
    if(PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fieldObj) {
            if(!$fieldObj->removed && $fieldObj->CardID === "suo6gb0op3") {
                if(IsClassBonusActive($obj->Controller, ["WARRIOR"])) {
                    $gravZone = $obj->Controller == $playerID ? "myGraveyard" : "theirGraveyard";
                    $gyAllies = ZoneSearch($gravZone, ["ALLY"]);
                    $uniqueCount = 0;
                    foreach($gyAllies as $gyMZ) {
                        $gyObj = GetZoneObject($gyMZ);
                        if(PropertyContains(CardType($gyObj->CardID), "UNIQUE")) {
                            $uniqueCount++;
                        }
                    }
                    $cardLife += $uniqueCount * 2;
                }
                break;
            }
        }
    }
    // Ally Link: check for bonuses from linked Phantasia cards via Subcards
    $linkedCards = GetLinkedCards($obj);
    foreach($linkedCards as $linkedObj) {
        switch($linkedObj->CardID) {
            case "4muq2r6v37": // Ocean's Blessing: linked ally gets +1 LIFE
                $cardLife += 1;
                break;
            case "80mttsvbgl": // Mark of Fervor: linked ally gets +1 LIFE
                $cardLife += 1;
                break;
            case "c8ljyevpmu": // Alliance Gearshield: linked ally gets +1 LIFE
                $cardLife += 1;
                break;
            case "t3q2svd53z": // Aqueous Armor: [Class Bonus] linked ally gets +2 LIFE
                if(IsClassBonusActive($linkedObj->Controller, ["GUARDIAN"])) {
                    $cardLife += 2;
                }
                break;
            default: break;
        }
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
            case "nIKhHFa0rK_HP": // Cry for Help class bonus: +1 LIFE until end of turn
                $cardLife += 1;
                break;
            case "x7u6wzh973": // Frostbinder Apostle: -4 LIFE until end of turn
                $cardLife -= 4;
                break;
            case "cyfrzrplyw": // Hypothermia: -4 LIFE until end of turn
                $cardLife -= 4;
                break;
            case "vbgl6ffqsu-HP": // Anthem of Vitality: +3 LIFE until end of turn
                $cardLife += 3;
                break;
            case "akb1k0zi5h": // Effigy of Gaia: Animal/Beast allies get +2 LIFE until end of turn
                $cardLife += 2;
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
    if(!PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
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
        // Tithe Proclamation (q8sdbzr5zs): draw cap of 3 per player per turn (not on first turn)
        $currentTurn = &GetTurnNumber();
        if($currentTurn > 1) {
            $titheOnField = false;
            $p1Field = GetField(1);
            foreach($p1Field as $fObj) {
                if(!$fObj->removed && $fObj->CardID === "q8sdbzr5zs") { $titheOnField = true; break; }
            }
            if(!$titheOnField) {
                $p2Field = GetField(2);
                foreach($p2Field as $fObj) {
                    if(!$fObj->removed && $fObj->CardID === "q8sdbzr5zs") { $titheOnField = true; break; }
                }
            }
            if($titheOnField && DrawTurnCount($player) >= 3) {
                return; // Draw cap reached
            }
        }
        if(count($zone) == 0) {
            return;
        }
        $card = array_shift($zone);
        array_push($hand, $card);
    }
}

/**
 * Draw cards from the top of the deck into memory instead of hand.
 * Used by effects that say "draw a card into your memory."
 * @param int $player The acting player.
 * @param int $amount Number of cards to draw into memory.
 */
function DrawIntoMemory($player, $amount=1) {
    $zone = &GetDeck($player);
    $memory = &GetMemory($player);
    for($i=0; $i<$amount; ++$i) {
        if(count($zone) == 0) return;
        $card = array_shift($zone);
        array_push($memory, $card);
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
    global $revealAbilities;
    $obj = GetZoneObject($revealedMZ);
    if($obj === null) return null;
    $CardID = $obj->CardID;
    // Accumulate REVEAL: messages so multiple reveals in one response all display.
    // Format: REVEAL:id1|id2|id3
    $existing = GetFlashMessage();
    if(is_string($existing) && strpos($existing, 'REVEAL:') === 0) {
        SetFlashMessage($existing . '|' . $CardID);
    } else {
        SetFlashMessage('REVEAL:' . $CardID);
    }
    // Determine source zone from the mzID (e.g. "myMemory-3" → "myMemory")
    $parts = explode("-", $revealedMZ);
    $sourceZone = $parts[0];
    // Fire reveal triggers for this card
    if(isset($revealAbilities[$CardID . ":0"])) {
        DecisionQueueController::StoreVariable("revealedMZ", $revealedMZ);
        DecisionQueueController::StoreVariable("revealSourceZone", $sourceZone);
        $revealAbilities[$CardID . ":0"]($player);
    }
    return $revealedMZ;
}
$revealAbilities = [];

function DoSacrificeFighter($player, $mzCard) {
    DoAllyDestroyed($player, $mzCard);
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
        OnLeaveField($player, $lastResult);
        MZMove($player, $lastResult, "myHand");
    }
};

$customDQHandlers["SpellshieldWindBuff"] = function($player, $param, $lastResult) {
    if($lastResult && $lastResult !== "-") {
        AddCounters($player, $lastResult, "buff", 1);
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
 * Azure Protective Trinket (m3pal7cpvn): continue banishing up to N more fire element cards
 * from the chosen graveyard. Queues MZMAYCHOOSE + handler for each remaining pick.
 */
function AzureTrinketContinue($player, $gravRef, $remaining) {
    if($remaining <= 0) return;
    $fireGY = ZoneSearch($gravRef, cardElements: ["FIRE"]);
    if(empty($fireGY)) return;
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $fireGY), 1, tooltip:"Banish_fire_card?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "AzureTrinketPick|$gravRef|$remaining", 1);
}

$customDQHandlers["AzureTrinketPick"] = function($player, $parts, $lastDecision) {
    $gravRef = $parts[0];
    $remaining = intval($parts[1]);
    if($lastDecision == "-" || $lastDecision == "") return;
    $destZone = (strpos($gravRef, "my") === 0) ? "myBanish" : "theirBanish";
    MZMove($player, $lastDecision, $destZone);
    AzureTrinketContinue($player, $gravRef, $remaining - 1);
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

$customDQHandlers["WindriderMageBounce"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        $fieldIndex = intval($parts[0]);
        $field = &GetField($player);
        if(isset($field[$fieldIndex]) && !$field[$fieldIndex]->removed && $field[$fieldIndex]->CardID === "ZfCtSldRIy") {
            OnLeaveField($player, "myField-" . $fieldIndex);
            MZMove($player, "myField-" . $fieldIndex, "myHand");
            // Put an enlighten counter on champion
            $pField = &GetField($player);
            for($ci = 0; $ci < count($pField); ++$ci) {
                if(!$pField[$ci]->removed && CardType($pField[$ci]->CardID) === "CHAMPION") {
                    AddCounters($player, "myField-" . $ci, "enlighten", 1);
                    break;
                }
            }
        }
    }
};

$customDQHandlers["MorganSoulGuideRecollection"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        Glimpse($player, 1);
    } else {
        RecoverChampion($player, 1);
    }
};

$customDQHandlers["ParcenetReveal"] = function($player, $parts, $lastDecision) {
    $deck = &GetDeck($player);
    if(empty($deck)) return;
    $topCard = $deck[0];
    DoRevealCard($player, "myDeck-0");
    if(CardElement($topCard->CardID) === "WIND") {
        // Find Parcenet's mzID so we can exclude it from choices
        $parcenetMZ = null;
        $field = &GetField($player);
        for($i = 0; $i < count($field); ++$i) {
            if(!$field[$i]->removed && $field[$i]->CardID === "xxoo7dl5j4") {
                $parcenetMZ = "myField-" . $i;
                break;
            }
        }
        $allies = ZoneSearch("myField", ["ALLY"]);
        $targets = [];
        foreach($allies as $a) {
            if($a !== $parcenetMZ) $targets[] = $a;
        }
        if(!empty($targets)) {
            $targetStr = implode("&", $targets);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, tooltip:"Choose_ally_to_gain_stealth");
            DecisionQueueController::AddDecision($player, "CUSTOM", "ParcenetGrantStealth", 1);
        }
    }
};

$customDQHandlers["ParcenetGrantStealth"] = function($player, $parts, $lastDecision) {
    if($lastDecision && $lastDecision !== "-") {
        AddTurnEffect($lastDecision, "xxoo7dl5j4_STEALTH");
    }
};

$customDQHandlers["GreenSlimeTransfer"] = function($player, $parts, $lastDecision) {
    if($lastDecision && $lastDecision !== "-") {
        $buffCount = intval(DecisionQueueController::GetVariable("GreenSlimeBuffCount"));
        if($buffCount > 0) {
            AddCounters($player, $lastDecision, "buff", $buffCount);
        }
    }
};

// Orb of Hubris: shuffle cards from hand into deck one at a time
function OrbOfHubrisShuffle($player, $remaining) {
    if($remaining <= 0) return;
    $hand = ZoneSearch("myHand");
    if(empty($hand)) return;
    $choices = implode("&", $hand);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $choices, 1, tooltip:"Choose_card_to_shuffle_into_deck");
    DecisionQueueController::AddDecision($player, "CUSTOM", "OrbOfHubrisShuffleStep|$remaining", 1);
}

$customDQHandlers["OrbOfHubrisShuffleStep"] = function($player, $parts, $lastDecision) {
    $remaining = intval($parts[0]);
    MZMove($player, $lastDecision, "myDeck");
    if($remaining > 1) {
        OrbOfHubrisShuffle($player, $remaining - 1);
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
    if(HasNoAbilities($obj)) return 0;
    $hasDynamic = GetDynamicAbilities($obj) !== "";
    if($debugMode) {
        return (CardActivateAbilityCount($obj->CardID) > 0 || $hasDynamic) ? 1 : 0;
    }
    $turnPlayer = &GetTurnPlayer();
    $hasAbility = (CardActivateAbilityCount($obj->CardID) > 0 || $hasDynamic);
    if(!$hasAbility) return 0;
    if($obj->Status != 2 || $turnPlayer != $obj->Controller) return 0;

    // Cunning Broker (oy34bro89w): requires 2+ prep counters on champion
    if($obj->CardID === "oy34bro89w") {
        $pField = &GetField($obj->Controller);
        foreach($pField as $fCard) {
            if(!$fCard->removed && PropertyContains(EffectiveCardType($fCard), "CHAMPION")) {
                if(GetCounterCount($fCard, "preparation") < 2) return 0;
                break;
            }
        }
    }

    return 1;
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
    $cardType = EffectiveCardType($obj);
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

function ZoneSearch($zoneName, $cardTypes=null, $floatingMemoryOnly=false, $cardElements=null, $cardSubtypes=null, $excludeSubtypes=null, $forPlayer=null) {
    global $playerID;
    // $forPlayer: when specified and different from $playerID, flip the zone name so we
    // search the zone that corresponds to "my..." from $forPlayer's perspective. Results
    // are then flipped back to $forPlayer's coordinate space so they can be used directly
    // in MZChoose decisions (which are always resolved from the requesting player's view).
    $flip = ($forPlayer !== null && $forPlayer != $playerID);
    $searchZone = $zoneName;
    if ($flip) {
        if (substr($searchZone, 0, 2) === "my")        $searchZone = "their" . substr($searchZone, 2);
        elseif (substr($searchZone, 0, 5) === "their") $searchZone = "my"    . substr($searchZone, 5);
    }
    $results = [];
    $zoneArr = &GetZone($searchZone);
    for($i = 0; $i < count($zoneArr); ++$i) {
        $obj = $zoneArr[$i];
        $cardTypeStr = EffectiveCardType($obj);
        $cardTypes_arr = $cardTypeStr ? explode(",", $cardTypeStr) : [];
        $cardSubtypesStr = EffectiveCardSubtypes($obj);
        $cardSubtypes_arr = $cardSubtypesStr ? explode(",", $cardSubtypesStr) : [];
        if(($cardTypes === null || count(array_intersect($cardTypes_arr, (array)$cardTypes)) > 0) &&
           ($cardElements === null || in_array(EffectiveCardElement($obj), (array)$cardElements)) &&
           ($cardSubtypes === null || count(array_intersect($cardSubtypes_arr, (array)$cardSubtypes)) > 0) &&
           ($excludeSubtypes === null || count(array_intersect($cardSubtypes_arr, (array)$excludeSubtypes)) === 0) &&
           (!$floatingMemoryOnly || HasFloatingMemory($obj))) {
            $mzID = $searchZone . "-" . $i;
            if ($flip) $mzID = FlipZonePerspective($mzID);
            array_push($results, $mzID);
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

    // Clear per-card TurnEffects from the expiring player's field,
    // but retain effects flagged as persistent (survive across turns).
    global $persistentTurnEffects;
    $fieldZone = $isEndTurn ? "myField" : "theirField";
    $fieldArr = &GetZone($fieldZone);
    foreach($fieldArr as &$fieldObj) {
        $newEffects = [];
        foreach($fieldObj->TurnEffects as $effect) {
            if(isset($persistentTurnEffects[$effect])) {
                $newEffects[] = $effect;
            }
            // Nia, Mistveiled Scout (PZM9uvCFai): named-card lock persists while Nia is on the field
            if(strpos($effect, "PZM9uvCFai-") === 0) {
                $newEffects[] = $effect;
            }
        }
        $fieldObj->TurnEffects = $newEffects;
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
// Peaceful Reunion: never auto-expire (cleared manually at caster's RecollectionPhase)
$foreverEffects["wr42i6eifn"] = true;
// Don't display this effect on field cards — it's a global attack-prevention flag
$doesGlobalEffectApply["wr42i6eifn"] = function($obj) { return false; };

// Persistent per-card TurnEffects that survive ExpireEffects across turns.
// SKIP_WAKEUP: consumed by WakeUpPhase (one-time skip).
// FROZEN_BY_SNOW_FAIRY: persists as long as opponent controls Snow Fairy.
// SPELLSHROUD_NEXT_TURN / STEALTH_NEXT_TURN: "until beginning of your next turn" effects,
//   consumed by WakeUpPhase of the controller's next turn.
// NO_UPKEEP: Right of Realm exemption — domain permanently skips its upkeep abilities.
$persistentTurnEffects = [];
$persistentTurnEffects["SKIP_WAKEUP"] = true;
$persistentTurnEffects["FROZEN_BY_SNOW_FAIRY"] = true;
$persistentTurnEffects["SPELLSHROUD_NEXT_TURN"] = true;
$persistentTurnEffects["STEALTH_NEXT_TURN"] = true;
$persistentTurnEffects["NO_UPKEEP"] = true;
$persistentTurnEffects["ATTUNE_FLAMES_BUFF"] = true;
$persistentTurnEffects["BLAZING_CHARGE_NEXT_TURN"] = true;
$persistentTurnEffects["TAUNT_NEXT_TURN"] = true;
$persistentTurnEffects["VIGOR_NEXT_TURN"] = true;

$doesGlobalEffectApply["9GWxrTMfBz"] = function($obj) { //Cram Session
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["zpkcFs72Ah"] = function($obj) { //Smack with Flute: champion gets +1 level until end of turn
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["Kc5Bktw0yK"] = function($obj) { //Empowering Harmony
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["dsAqxMezGb"] = function($obj) { //Favorable Winds
    return PropertyContains(EffectiveCardType($obj), "ALLY");
};

$doesGlobalEffectApply["DBJ4DuLABr"] = function($obj) { //Shroud in Mist: units you control gain stealth
    return PropertyContains(EffectiveCardType($obj), "ALLY") || PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["k71PE3clOI"] = function($obj) { //Inspiring Call: allies get +1 POWER until end of turn
    return PropertyContains(EffectiveCardType($obj), "ALLY");
};

$doesGlobalEffectApply["fMv7tIOZwL-PWR"] = function($obj) { //Aqueous Enchanting: allies get +1 POWER until end of turn
    return PropertyContains(EffectiveCardType($obj), "ALLY");
};

$doesGlobalEffectApply["fMv7tIOZwL-LIF"] = function($obj) { //Aqueous Enchanting: allies get +1 LIFE until end of turn
    return PropertyContains(EffectiveCardType($obj), "ALLY");
};

$doesGlobalEffectApply["hw8dxKAnMX"] = function($obj) { //Mist Resonance: allies get +1 LIFE until end of turn
    return PropertyContains(EffectiveCardType($obj), "ALLY");
};

$doesGlobalEffectApply["rxxwQT054x"] = function($obj) { //Command the Hunt: allies get +2 POWER
    return PropertyContains(EffectiveCardType($obj), "ALLY");
};

$doesGlobalEffectApply["rxxwQT054x_VIGOR"] = function($obj) { //Command the Hunt: allies gain vigor
    return PropertyContains(EffectiveCardType($obj), "ALLY");
};

$doesGlobalEffectApply["vcZSHNHvKX"] = function($obj) { //Spirit Blade: Ghost Strike: +1 POWER on champion attacks
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["LEVELED_UP_THIS_TURN"] = function($obj) { //Flag only — no visual effect on cards
    return false;
};

$doesGlobalEffectApply["RAI_ARCHMAGE_TRIGGERED"] = function($obj) { //Flag only — tracks first Mage action this turn for Rai, Archmage inherited effect
    return false;
};

$doesGlobalEffectApply["RfPP8h16Wv"] = function($obj) { //Flag only — next Animal/Beast ally gets buff counter, no visual effect
    return false;
};

$doesGlobalEffectApply["MECS7RHRZ8"] = function($obj) { //Impassioned Tutor
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["5joh300z2s"] = function($obj) { //Manaroot: champion gets +1 level until end of turn
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["rw8qq1uwq8-lockdown"] = function($obj) { //Corhazi Outlook: Opponents can't activate cards this turn
    return false; // Global effect only, no visual on cards
};

$doesGlobalEffectApply["aKgdkLSBza"] = function($obj) { //Wilderness Harpist
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["HsaWNAsmAQ"] = function($obj) { // Bestial Frenzy: +1 level applies only to champions
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["WAFNy2lY5t"] = function($obj) { //Melodious Flute
    return false;
};

$doesGlobalEffectApply["6e7lRnczfL"] = function($obj) { //Horn of Beastcalling
    return false;
};

$doesGlobalEffectApply["EBWWwvSxr3"] = function($obj) { //Horn of Beastcalling
    return false;
};

$doesGlobalEffectApply["STEADY_VERSE_HARMONY_DISCOUNT"] = function($obj) { //Steady Verse: flag only — next Harmony card costs 1 less
    return false;
};

$doesGlobalEffectApply["INNERVATE_STEALTH"] = function($obj) { //Innervate Agility: units gain stealth
    return PropertyContains(EffectiveCardType($obj), "ALLY") || PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["INNERVATE_SPELLSHROUD"] = function($obj) { //Innervate Agility: units gain spellshroud
    return PropertyContains(EffectiveCardType($obj), "ALLY") || PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["FRACTURED_CROWN_FIRED"] = function($obj) { //Fractured Crown: first attack this turn flag (flag only)
    return false;
};

$doesGlobalEffectApply["39i1f0ht2t"] = function($obj) { //Storm of Thorns: flag only — prevention handled in OnDealDamage
    return false;
};

$doesGlobalEffectApply["akb1k0zi5h"] = function($obj) { //Effigy of Gaia: Animal/Beast allies get +2 LIFE
    return PropertyContains(EffectiveCardType($obj), "ALLY")
        && (PropertyContains(EffectiveCardSubtypes($obj), "ANIMAL") || PropertyContains(EffectiveCardSubtypes($obj), "BEAST"));
};

$doesGlobalEffectApply["huqj5bbae3"] = function($obj) { //Winds of Retribution: allies get +2 POWER
    return PropertyContains(EffectiveCardType($obj), "ALLY");
};

$doesGlobalEffectApply["i1f0ht2tsn"] = function($obj) { //Strategic Warfare: allies get +1 POWER
    return PropertyContains(EffectiveCardType($obj), "ALLY");
};

$doesGlobalEffectApply["i0a5uhjxhk"] = function($obj) { //Blightroot: champion gets +1 level
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

// Plea for Peace (ir99sx6q3p): flag only — attack tax handled in BeginCombatPhase
$foreverEffects["ir99sx6q3p"] = true;
$doesGlobalEffectApply["ir99sx6q3p"] = function($obj) { return false; };

// Conjure Downpour (r0zadf9q1w): flag only — power reduction handled in ObjectCurrentPower
$effectAppliesToBoth["r0zadf9q1w"] = true;
$doesGlobalEffectApply["r0zadf9q1w"] = function($obj) { return false; };

// Fireworks Display (sx6q3p6i0i): flag only — banish-instead-of-die handled in DoAllyDestroyed
$doesGlobalEffectApply["FIREWORKS_BANISH"] = function($obj) { return false; };

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

function RemoveGlobalEffect($player, $effectID) {
    $ge = &GetGlobalEffects($player);
    foreach($ge as $gIdx => $geItem) {
        if($geItem->CardID === $effectID) {
            array_splice($ge, $gIdx, 1);
            return true;
        }
    }
    return false;
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
        if(PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
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
        $cardClasses = explode(",", EffectiveCardClasses($obj));
        if(PropertyContains(EffectiveCardType($obj), "CHAMPION") && ($classes === null || count(array_intersect($cardClasses, (array)$classes)) > 0)) {
            return true;
        }
    }
    return false;
}

/**
 * Check if Element Bonus is active for a given card.
 * Element Bonus is active when the player's champion's element matches the card's element.
 * @param int    $player  The player number
 * @param string $cardID  The card ID to check element for
 * @return bool  True if the champion's element matches the card's element
 */
function IsElementBonusActive($player, $cardID) {
    return true;//TODO: Delete this
    $cardElement = CardElement($cardID);
    if($cardElement === null || $cardElement === "NORM") return false;
    $field = &GetField($player);
    foreach($field as $obj) {
        if(!$obj->removed && PropertyContains(EffectiveCardType($obj), "CHAMPION") && $obj->Controller == $player) {
            $champElement = EffectiveCardElement($obj);
            return $champElement === $cardElement;
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
        'mdiK8UC78c' => 2, // Call the Pack: [Class Bonus] costs 2 less
        'Uxn14UqyQg' => 2, // Immolation Trap: [Class Bonus] costs 2 less
        '215upufyoz' => 2, // Tether in Flames: [Class Bonus] costs 2 less
        'nmp5af098k' => 2, // Spellshield: Astra: [Class Bonus] costs 2 less
        'nvx7mnu1xh' => 2, // Attune with Flames: [Class Bonus] costs 2 less
    ];
    return isset($reductions[$cardID]) ? $reductions[$cardID] : 0;
}

function DealChampionDamage($player, $amount=1) {
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $zoneArr = &GetZone($zone);
    for($i = 0; $i < count($zoneArr); ++$i) {
        $obj = &$zoneArr[$i];
        if(PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
            // Safeguard Amulet: prevent up to 4 non-combat damage (one-time)
            if(in_array("yj2rJBREH8", $obj->TurnEffects)) {
                $prevented = min(4, $amount);
                $amount -= $prevented;
                $obj->TurnEffects = array_values(array_filter($obj->TurnEffects, fn($e) => $e !== "yj2rJBREH8"));
            }
            // Blazing Charge (s5jwsl7ded): champion takes +1 damage
            if(in_array("BLAZING_CHARGE_NEXT_TURN", $obj->TurnEffects)) {
                $amount += 1;
            }
            $obj->Damage += $amount;
            return $obj;
        }
    }
    return null;
}

function RecoverChampion($player, $amount=1) {
    global $playerID;

    // Morgan, Soul Guide (ka5av43ehj): [Level 2+] opponents can't recover
    $opponent = ($player == 1) ? 2 : 1;
    $oppField = &GetField($opponent);
    foreach($oppField as $oppObj) {
        if(!$oppObj->removed && $oppObj->CardID === "ka5av43ehj" && !HasNoAbilities($oppObj)) {
            if(PlayerLevel($oppObj->Controller) >= 2) {
                return null; // Recovery blocked by Morgan
            }
        }
    }

    $zone = $player == $playerID ? "myField" : "theirField";
    $zoneArr = &GetZone($zone);
    for($i = 0; $i < count($zoneArr); ++$i) {
        $obj = &$zoneArr[$i];
        if(PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
            $obj->Damage = max(0, $obj->Damage - $amount);
            return $obj;
        }
    }
    return null;
}

/**
 * Get the full lineage of a player's champion (current champion CardID + all subcards).
 * Returns an array of CardIDs representing the lineage from newest to oldest.
 * @param int $player  The player number
 * @return array  Array of CardIDs, empty if no champion on field
 */
function GetChampionLineage($player) {
    $field = &GetField($player);
    foreach($field as $obj) {
        if(!$obj->removed && PropertyContains(EffectiveCardType($obj), "CHAMPION") && $obj->Controller == $player) {
            $subcards = is_array($obj->Subcards) ? $obj->Subcards : [];
            return array_merge([$obj->CardID], $subcards);
        }
    }
    return [];
}

/**
 * Check if a specific card is part of a player's champion lineage.
 * This includes the current champion itself and all subcards beneath it.
 * Used for "Inherited Effect" abilities that persist while a card is in the lineage.
 * @param int    $player  The player number
 * @param string $cardID  The card ID to check for
 * @return bool  True if the card is in the lineage
 */
function ChampionHasInLineage($player, $cardID) {
    $lineage = GetChampionLineage($player);
    return in_array($cardID, $lineage);
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
    if(HasKeyword_FloatingMemory($obj)) return true;
    // Intrepid Highwayman (WUAOMTZ7P2): [Class Bonus] Floating Memory
    if($obj->CardID === "WUAOMTZ7P2" && IsClassBonusActive($obj->Controller, ["ASSASSIN"])) return true;
    // Firetuned Automaton (lzjmwuir99): [Class Bonus] Floating Memory
    if($obj->CardID === "lzjmwuir99" && IsClassBonusActive($obj->Controller, ["GUARDIAN"])) return true;
    // Mordred (WI2owxIw0z): attack cards in graveyard have floating memory
    if(PropertyContains(CardType($obj->CardID), "ATTACK")) {
        for($p = 1; $p <= 2; $p++) {
            $pField = &GetField($p);
            foreach($pField as $fCard) {
                if(!$fCard->removed && $fCard->CardID === "WI2owxIw0z") {
                    return true;
                }
            }
        }
    }
    return false;
}

// =============================================================================
// Card Property Override System
// =============================================================================
// Provides runtime overrides for card properties (element, type, subtypes,
// classes) and a blanket ability suppression flag (NO_ABILITIES).
//
// Temporary overrides use TurnEffects (cleared by ExpireEffects at end of turn).
// Persistent/indefinite overrides use Counters['_overrides'] on field objects
// (survives serialization and ExpireEffects).
//
// Effective* wrappers should be used wherever an on-field (or in-graveyard)
// object's property is queried and might differ from its printed card.
// Raw Card*() functions remain correct for static lookups by card ID (e.g.
// dictionary queries, deck/hand cards that have no field overrides).
// =============================================================================

/**
 * Check whether a field object currently has all abilities suppressed.
 * A card with NO_ABILITIES loses all keyword abilities (pride, intercept,
 * stealth, vigor, etc.), all triggered abilities (On Enter, On Hit, etc.),
 * and all activated abilities.
 *
 * Sources:
 *   - TurnEffect "NO_ABILITIES" (until end of turn, e.g. Capricious Lynx)
 *   - Persistent override in Counters (indefinite, e.g. Fracturize)
 */
function HasNoAbilities($obj) {
    if(isset($obj->TurnEffects) && in_array("NO_ABILITIES", $obj->TurnEffects)) return true;
    if(isset($obj->Counters['_overrides']['NO_ABILITIES']) && $obj->Counters['_overrides']['NO_ABILITIES']) return true;
    return false;
}

/**
 * Get the effective element for a zone object, considering runtime overrides.
 * Checks: persistent overrides (Fracturize), zone-based overrides (Nullifying
 * Lantern — cards in graveyards are NORM), then falls back to card dictionary.
 *
 * @param object $obj  A zone object with at least CardID and Location properties.
 * @return string|null The effective element string (e.g. "FIRE", "NORM").
 */
function EffectiveCardElement($obj) {
    // Persistent override (e.g. Fracturize transforms element)
    if(isset($obj->Counters['_overrides']['element'])) {
        return $obj->Counters['_overrides']['element'];
    }
    // Zone override: cards in graveyards are NORM while Nullifying Lantern is on the field
    if(isset($obj->Location) && $obj->Location === 'Graveyard') {
        if(ZoneContainsCardID("myField", "urKxcUjz9a") || ZoneContainsCardID("theirField", "urKxcUjz9a")) {
            return "NORM";
        }
    }
    return CardElement($obj->CardID);
}

/**
 * Get the effective type for a zone object, considering runtime overrides.
 * Checks persistent overrides (Fracturize), then falls back to card dictionary.
 *
 * @param object $obj  A zone object (typically Field).
 * @return string|null The effective type string (e.g. "ALLY", "PHANTASIA").
 */
function EffectiveCardType($obj) {
    if(isset($obj->Counters['_overrides']['type'])) {
        return $obj->Counters['_overrides']['type'];
    }
    return CardType($obj->CardID);
}

/**
 * Get the effective subtypes for a zone object, considering runtime overrides.
 * Checks persistent overrides (Fracturize), then falls back to card dictionary.
 *
 * @param object $obj  A zone object (typically Field).
 * @return string|null The effective subtypes (comma-separated, e.g. "CLERIC,FRACTAL").
 */
function EffectiveCardSubtypes($obj) {
    if(isset($obj->Counters['_overrides']['subtypes'])) {
        return $obj->Counters['_overrides']['subtypes'];
    }
    return CardSubtypes($obj->CardID);
}

/**
 * Get the effective classes for a zone object, considering runtime overrides.
 * Checks persistent overrides (Fracturize), then falls back to card dictionary.
 *
 * @param object $obj  A zone object (typically Field).
 * @return string|null The effective classes (comma-separated, e.g. "CLERIC").
 */
function EffectiveCardClasses($obj) {
    if(isset($obj->Counters['_overrides']['classes'])) {
        return $obj->Counters['_overrides']['classes'];
    }
    return CardClasses($obj->CardID);
}

/**
 * Apply a persistent (indefinite) card property override to a field object.
 * Stored in Counters['_overrides'] so it survives serialization and ExpireEffects.
 * Used for effects like Fracturize that last indefinitely.
 *
 * @param string $mzCard    The mzID of the field card (e.g. "myField-3").
 * @param array  $overrides Key-value pairs. Valid keys:
 *                          'type', 'subtypes', 'classes', 'element', 'NO_ABILITIES' (bool).
 */
function ApplyPersistentOverride($mzCard, $overrides) {
    $obj = &GetZoneObject($mzCard);
    if($obj === null) return;
    if(!isset($obj->Counters) || !is_array($obj->Counters)) {
        $obj->Counters = [];
    }
    if(!isset($obj->Counters['_overrides'])) {
        $obj->Counters['_overrides'] = [];
    }
    foreach($overrides as $key => $value) {
        $obj->Counters['_overrides'][$key] = $value;
    }
}

/**
 * Check if a zone contains a specific card ID (not removed).
 * Scans the zone array — O(n) where n is zone size.
 *
 * @param string $zoneName Zone name (e.g. "myField", "theirGraveyard").
 * @param string $cardID   Card ID to search for.
 * @return bool
 */
function ZoneContainsCardID($zoneName, $cardID) {
    $zoneArr = &GetZone($zoneName);
    foreach($zoneArr as $obj) {
        if(!$obj->removed && $obj->CardID === $cardID) return true;
    }
    return false;
}

/**
 * Check if a field object has a keyword explicitly granted by persistent overrides.
 * Used for effects like Fracturize that grant specific keywords while suppressing all others.
 *
 * @param object $obj     Field object to check.
 * @param string $keyword Keyword name (e.g. 'Reservable').
 * @return bool
 */
function HasGrantedKeyword($obj, $keyword) {
    if(!isset($obj->Counters['_overrides']['granted_keywords'])) return false;
    return in_array($keyword, $obj->Counters['_overrides']['granted_keywords']);
}

function HasVigor($obj) {
    if(HasNoAbilities($obj)) return false;
    if(HasKeyword_Vigor($obj)) return true;
    // VIGOR_EOT TurnEffect: granted vigor until end of turn (e.g. Assemble the Ancients)
    if(in_array("VIGOR_EOT", $obj->TurnEffects)) return true;
    // VIGOR_NEXT_TURN: granted vigor until beginning of controller's next turn (e.g. Rousing Slam)
    if(in_array("VIGOR_NEXT_TURN", $obj->TurnEffects)) return true;
    // Uther, Illustrious King (5h8asbierp): always has Vigor
    if($obj->CardID === "5h8asbierp") return true;
    // Command the Hunt (rxxwQT054x): allies gain vigor via global effect
    if(ObjectHasEffect($obj, "rxxwQT054x_VIGOR")) return true;
    // Ally Link: Mark of Fervor (80mttsvbgl): linked ally has vigor
    $linkedCards = GetLinkedCards($obj);
    foreach($linkedCards as $linkedObj) {
        if($linkedObj->CardID === "80mttsvbgl") return true;
    }
    return false;
}

function HasStealth($obj) {
    if(HasNoAbilities($obj)) return false;
    // Lurking Assailant (uq2r6v374c): stealth as long as it's awake
    if($obj->CardID === "uq2r6v374c") {
        return isset($obj->Status) && $obj->Status == 2;
    }
    // Patient Rogue: [Class Bonus] stealth while awake
    if($obj->CardID === "CvvgJR4fNa") {
        return isset($obj->Status) && $obj->Status == 2 && IsClassBonusActive($obj->Controller, ["ASSASSIN"]);
    }
    // Blackmarket Broker (hHVf5xyjob): CB stealth while champion has 3+ prep counters
    if($obj->CardID === "hHVf5xyjob") {
        if(IsClassBonusActive($obj->Controller, CardClasses("hHVf5xyjob"))) {
            $pField = &GetField($obj->Controller);
            foreach($pField as $fCard) {
                if(!$fCard->removed && EffectiveCardType($fCard) === "CHAMPION") {
                    if(GetCounterCount($fCard, "prep") >= 3) return true;
                    break;
                }
            }
        }
    }
    if(HasKeyword_Stealth($obj)) return true;
    // STEALTH: granted stealth until end of turn (e.g. Vanish from Sight, Sidestep)
    if(in_array("STEALTH", $obj->TurnEffects)) return true;
    // STEALTH_NEXT_TURN: persistent stealth until beginning of controller's next turn (e.g. Zander)
    if(in_array("STEALTH_NEXT_TURN", $obj->TurnEffects)) return true;
    // Check for temporary stealth effects granted by other cards
    $effects = explode(",", CardCurrentEffects($obj));
    foreach($effects as $effectID) {
        switch($effectID) {
            case "DBJ4DuLABr": // Shroud in Mist: units you control gain stealth
                return true;
            case "ScGcOmkoQt": // Smoke Bombs: target ally gains stealth this turn
                return true;
            case "INNERVATE_STEALTH": // Innervate Agility: units gain stealth until EOT
                return true;
            case "xxoo7dl5j4_STEALTH": // Parcenet, Royal Maid: target ally gains stealth until EOT
                return true;
        }
    }
    return false;
}

function HasTrueSight($obj) {
    if(HasNoAbilities($obj)) return false;
    if(HasKeyword_TrueSight($obj)) return true;
    if(ObjectHasEffect($obj, "iiZtKTulPg")) return true; // Eye of Argus
    if(ObjectHasEffect($obj, "F1t18omUlx_SIGHT")) return true; // Beastbond Paws
    if(ObjectHasEffect($obj, "i1f0ht2tsn_SIGHT")) return true; // Strategic Warfare
    // Seeking Shot: [Level 2+] True Sight
    if($obj->CardID === "88zq9ox7u6" && PlayerLevel($obj->Controller) >= 2) return true;
    return false;
}

/**
 * Get damage prevention value from Protective Fractal effect.
 * Protective Fractal grants 1 damage prevention per active effect.
 */
function GetProtectiveFractalPrevention($obj) {
    $count = 0;
    foreach($obj->TurnEffects as $effect) {
        if($effect === "1lw9n0wpbh") $count++;
    }
    return $count;
}

/**
 * Check whether a field object currently has Spellshroud.
 * Objects with spellshroud can't be targeted by Spells.
 * Sources:
 *   - TurnEffect "SPELLSHROUD" (until end of turn, e.g. Beastbond Boots)
 *   - TurnEffect "SPELLSHROUD_NEXT_TURN" (until beginning of next turn, e.g. Zander)
 */
function HasSpellshroud($obj) {
    if(HasNoAbilities($obj)) return false;
    if(in_array("SPELLSHROUD", $obj->TurnEffects)) return true;
    if(in_array("SPELLSHROUD_NEXT_TURN", $obj->TurnEffects)) return true;
    // Innervate Agility: units gain spellshroud until EOT via global effect
    $effects = explode(",", CardCurrentEffects($obj));
    if(in_array("INNERVATE_SPELLSHROUD", $effects)) return true;
    return false;
}

/**
 * Check whether a card has the Hindered keyword.
 * Hindered: "This object enters the field rested."
 * Hindered is redundant.
 */
function HasHindered($obj) {
    if(HasNoAbilities($obj)) return false;
    if(HasKeyword_Hindered($obj)) return true;
    return false;
}

/**
 * Check whether a field object currently has the Reservable keyword.
 * Reservable: "While paying for a reserve cost, you may rest this object to pay for 1 of that cost."
 * Reservable is redundant.
 */
function HasReservable($obj) {
    // Check for keywords explicitly granted by persistent overrides (e.g. Fracturize)
    if(HasGrantedKeyword($obj, 'Reservable')) return true;
    if(HasNoAbilities($obj)) return false;
    if(HasKeyword_Reservable($obj)) return true;
    // The Eternal Kingdom (fyoz23yfzk): [Class Bonus] Domains you control have reservable
    if(PropertyContains(EffectiveCardType($obj), "DOMAIN")) {
        $controller = $obj->Controller;
        global $playerID;
        $zone = $controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fieldObj) {
            if(!$fieldObj->removed && $fieldObj->CardID === "fyoz23yfzk" && !HasNoAbilities($fieldObj)
                && IsClassBonusActive($controller, ["GUARDIAN"])) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Check whether a field object currently has Taunt.
 * Taunt: "While awake, this must be targeted before other objects you control during your
 *         opponents' attack declarations, if able."
 * Taunt is redundant. Multiple instances don't increase priority.
 * Sources:
 *   - Static keyword from card dictionary (HasKeyword_Taunt)
 *   - TurnEffect "TAUNT" (until end of turn, e.g. granted by a spell)
 *   - TurnEffect "TAUNT_NEXT_TURN" (until beginning of controller's next turn, e.g. On Enter grant)
 */
function HasTaunt($obj) {
    if(HasNoAbilities($obj)) return false;
    if(in_array("NO_TAUNT", $obj->TurnEffects)) return false;
    if(HasKeyword_Taunt($obj)) return true;
    if(in_array("TAUNT", $obj->TurnEffects)) return true;
    if(in_array("TAUNT_NEXT_TURN", $obj->TurnEffects)) return true;
    // Ally Link: check if any linked Phantasia/ally grants taunt
    $linkedCards = GetLinkedCards($obj);
    foreach($linkedCards as $linkedObj) {
        switch($linkedObj->CardID) {
            case "4muq2r6v37": // Ocean's Blessing: linked ally has taunt
                return true;
            case "fqsuo6gb0o": // Avatar of Gaia: linked ally has taunt
                return true;
        }
    }
    // Bedivere, Woodland Overseer (pwakb1k0zi): has taunt while controlling another Animal/Beast ally
    if($obj->CardID === "pwakb1k0zi") {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $animalBeast = ZoneSearch($zone, ["ALLY"], cardSubtypes: ["ANIMAL", "BEAST"]);
        // Must control ANOTHER Animal/Beast ally (not counting Bedivere itself)
        foreach($animalBeast as $abMZ) {
            $abObj = GetZoneObject($abMZ);
            if($abObj !== null && $abObj->CardID !== "pwakb1k0zi") return true;
        }
    }
    return false;
}

/**
 * Filter an array of mzID strings, removing any that point to objects with Spellshroud.
 * Use this when building target lists for abilities that are Spell sources.
 *
 * @param array $mzIDs  Array of mzID strings (e.g. ["myField-0", "theirField-2"])
 * @return array  Filtered array with spellshroud objects removed
 */
function FilterSpellshroudTargets($mzIDs) {
    $filtered = [];
    foreach($mzIDs as $mzID) {
        $obj = GetZoneObject($mzID);
        if($obj !== null && HasSpellshroud($obj)) continue;
        $filtered[] = $mzID;
    }
    return $filtered;
}

function PrideAmount($obj) {
    if(HasNoAbilities($obj)) return 0;
    $prideValue = GetKeyword_Pride_Value($obj);
    if($prideValue === null) return 0;
    // Avatar of Gaia (fqsuo6gb0o): linked ally loses pride
    $linkedCards = GetLinkedCards($obj);
    foreach($linkedCards as $linkedObj) {
        if($linkedObj->CardID === "fqsuo6gb0o") return 0;
    }
    return $prideValue;
}

function CardMemoryCost($obj) {
    $cost = CardCost_memory($obj->CardID);
    // Heatwave Generator (fzcyfrzrpl): [Class Bonus] costs 1 less to materialize
    if($obj->CardID === "fzcyfrzrpl") {
        $turnPlayer = &GetTurnPlayer();
        if(IsClassBonusActive($turnPlayer, ["GUARDIAN"])) {
            $cost = max(0, $cost - 1);
        }
    }
    // Academy Guide (kk39i1f0ht): Champion cards you materialize cost 1 less
    if(PropertyContains(CardType($obj->CardID), "CHAMPION")) {
        $turnPlayer = &GetTurnPlayer();
        global $playerID;
        $zone = $turnPlayer == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fieldObj) {
            if(!$fieldObj->removed && $fieldObj->CardID === "kk39i1f0ht" && !HasNoAbilities($fieldObj)) {
                $cost = max(0, $cost - 1);
                break;
            }
        }
    }
    // Forgelight Scepter (smw3rrii17): [Class Bonus] costs 1 less to materialize
    if($obj->CardID === "smw3rrii17") {
        $turnPlayer = &GetTurnPlayer();
        if(IsClassBonusActive($turnPlayer, ["CLERIC"])) {
            $cost = max(0, $cost - 1);
        }
    }
    // Navigation Compass (sw2ugmnmp5): [Class Bonus] costs 1 less to materialize
    if($obj->CardID === "sw2ugmnmp5") {
        $turnPlayer = &GetTurnPlayer();
        if(IsClassBonusActive($turnPlayer, ["RANGER"])) {
            $cost = max(0, $cost - 1);
        }
    }
    return $cost;
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
// Ally Link System — link tracking via Subcards
// =============================================================================

/**
 * Create a link between a Phantasia card (with Ally Link keyword) and an ally.
 * The ally's Subcards stores the Phantasia's CardID (for UI display).
 * The Phantasia's Counters stores {"linkedToAlly": allyCardID} for reverse lookup.
 * @param int    $player       The acting player.
 * @param string $phantasiaMZ  The mzID of the Phantasia (e.g. "myField-5").
 * @param string $allyMZ       The mzID of the ally to link to (e.g. "myField-2").
 */
function CreateAllyLink($player, $phantasiaMZ, $allyMZ) {
    $phantasiaObj = &GetZoneObject($phantasiaMZ);
    $allyObj = &GetZoneObject($allyMZ);
    if($phantasiaObj === null || $allyObj === null) return;

    // Store Phantasia CardID in ally's Subcards for UI subcard display
    if(!is_array($allyObj->Subcards)) $allyObj->Subcards = [];
    if(!in_array($phantasiaObj->CardID, $allyObj->Subcards)) {
        array_push($allyObj->Subcards, $phantasiaObj->CardID);
    }

    // Store reverse link in Phantasia's Counters for reverse lookup
    if(!is_array($phantasiaObj->Counters)) {
        $phantasiaObj->Counters = [];
    }
    $phantasiaObj->Counters['linkedToAlly'] = $allyObj->CardID;
}

/**
 * Find all Phantasia field cards linked to a given ally object.
 * The ally's Subcards contains the Phantasia's CardID; the Phantasia's
 * Counters->linkedToAlly points back to the ally's CardID.
 * @param object $obj  A Field zone object (the ally).
 * @return array  Array of linked Phantasia field objects.
 */
function GetLinkedCards($obj) {
    if(!isset($obj->Subcards) || !is_array($obj->Subcards) || empty($obj->Subcards)) return [];
    global $playerID;
    $zoneRef = $obj->Controller == $playerID ? "myField" : "theirField";
    $field = GetZone($zoneRef);
    $linked = [];
    foreach($obj->Subcards as $subcardCardID) {
        if(empty($subcardCardID)) continue;
        foreach($field as $fObj) {
            if($fObj->removed) continue;
            if($fObj->CardID !== $subcardCardID) continue;
            // Confirm it's actually linked (has the reverse pointer)
            if(!is_array($fObj->Counters) || !isset($fObj->Counters['linkedToAlly'])) continue;
            $linked[] = $fObj;
        }
    }
    return $linked;
}

/**
 * Check if the departing card was involved in any Ally Link relationships and
 * break those links.
 * - If the departing card is an ally with linked Phantasias (CardIDs in Subcards),
 *   sacrifice all linked Phantasias.
 * - If the departing card is a Phantasia (has Counters->linkedToAlly),
 *   remove it from the linked ally's Subcards.
 * @param int    $player      The acting player.
 * @param string $departingMZ The mzID of the card leaving the field.
 */
function CheckAndBreakLinks($player, $departingMZ) {
    $obj = GetZoneObject($departingMZ);
    if($obj === null) return;

    global $playerID;
    $controller = $obj->Controller;
    $zoneRef = ($controller == $playerID) ? "myField" : "theirField";
    $field = GetZone($zoneRef);

    // Case 1: Departing card is a Phantasia — remove it from the linked ally's Subcards
    if(is_array($obj->Counters) && isset($obj->Counters['linkedToAlly'])) {
        $allyCardID = $obj->Counters['linkedToAlly'];
        foreach($field as $idx => $fObj) {
            if($fObj->removed) continue;
            if($fObj->CardID !== $allyCardID) continue;
            if(!is_array($fObj->Subcards)) continue;
            $fObj->Subcards = array_values(array_filter($fObj->Subcards, fn($id) => $id !== $obj->CardID));
            break;
        }
        return;
    }

    // Case 2: Departing card is an ally with linked Phantasias
    if(!is_array($obj->Subcards) || empty($obj->Subcards)) return;

    $toSacrifice = [];
    foreach($field as $idx => $fObj) {
        if($fObj->removed) continue;
        if(!in_array($fObj->CardID, $obj->Subcards)) continue;
        if(!is_array($fObj->Counters) || !isset($fObj->Counters['linkedToAlly'])) continue;
        // This is a linked Phantasia — its link is broken when the ally leaves
        $toSacrifice[] = $idx;
    }

    // Sacrifice linked Phantasias in reverse index order to preserve indices
    rsort($toSacrifice);
    foreach($toSacrifice as $idx) {
        DoSacrificeFighter($controller, $zoneRef . "-" . $idx);
    }
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
 * Virtual property callback: returns the number of durability counters on the object.
 * Used for the DurabilityCounterCount display badge.
 */
function GetDurabilityCounterCount($obj) {
    return GetCounterCount($obj, "durability");
}

function GetRefinementCounterCount($obj) {
    return GetCounterCount($obj, "refinement");
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
    if(HasNoAbilities($obj)) return "";
    global $lineageReleaseAbilities;
    $abilities = [];
    $staticCount = CardActivateAbilityCount($obj->CardID);
    $nextIndex = $staticCount;
    // Enlighten: champion may remove 3 enlighten counters to draw a card
    if(PropertyContains(EffectiveCardType($obj), "CHAMPION") && GetCounterCount($obj, "enlighten") >= 3) {
        $abilities[] = ["name" => "Enlighten", "index" => $nextIndex];
        $nextIndex++;
    }
    // Lineage Release: show a button for each subcard that has a registered LR ability
    if(PropertyContains(EffectiveCardType($obj), "CHAMPION") && $obj->Status == 2) {
        $subcards = is_array($obj->Subcards) ? $obj->Subcards : [];
        foreach($subcards as $subcardID) {
            if(isset($lineageReleaseAbilities[$subcardID])) {
                $abilities[] = ["name" => $lineageReleaseAbilities[$subcardID]['name'], "index" => $nextIndex];
                $nextIndex++;
            }
        }
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

    // Bushwhack Bandit (kT8CeTFj82): CB Critical 1
    if($obj->CardID === "kT8CeTFj82" && IsClassBonusActive($player, CardClasses("kT8CeTFj82"))) {
        $maxCritical = max($maxCritical, 1);
    }

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

/**
 * Custom DQ handler: Blazing Throw (iohZMWh5v5) — move the chosen weapon to the graveyard.
 */
$customDQHandlers["BT_SacrificeWeapon"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $obj = GetZoneObject($lastDecision);
    if($obj === null) return;
    OnLeaveField($player, $lastDecision);
    MZMove($player, $lastDecision, "myGraveyard");
    DecisionQueueController::CleanupRemovedCards();
};

/**
 * Custom DQ handler: Intangible Geist (Zu53izIFTX) Enter — recursively put regalia from banishment to material.
 */
$customDQHandlers["Zu53izIFTX_RecurseRegalia"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "") {
        MZMove($player, $lastDecision, "myMaterial");
        DecisionQueueController::CleanupRemovedCards();
    } else {
        return; // Player passed — stop loop
    }
    $regalias = ZoneSearch("myBanish", ["REGALIA"]);
    if(empty($regalias)) return;
    $choices = implode("&", $regalias);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $choices, 1);
    DecisionQueueController::AddDecision($player, "CUSTOM", "Zu53izIFTX_RecurseRegalia", 1);
};

/**
 * Custom DQ handlers: Bestial Frenzy (HsaWNAsmAQ)
 * Option A: champion +1 level (AddGlobalEffects)
 * Option B: target Beast ally +1 POWER (AddTurnEffect HsaWNAsmAQ_POWER)
 * Option C: target Beast ally gains cleave (AddTurnEffect HsaWNAsmAQ_CLEAVE)
 * Choose one; [Class Bonus] choose up to two instead.
 */
$customDQHandlers["HsaWNAsmAQ_OptionA"] = function($player, $parts, $lastDecision) {
    $chosen = 0;
    if($lastDecision === "YES") {
        AddGlobalEffects($player, "HsaWNAsmAQ");
        $chosen = 1;
    }
    DecisionQueueController::StoreVariable("BF_chosen", "$chosen");
    $maxChoices = IsClassBonusActive($player) ? 2 : 1;
    if($chosen < $maxChoices) {
        $beasts = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["BEAST"]);
        if(!empty($beasts)) {
            DecisionQueueController::AddDecision($player, "YESNO", "-", 1, "Use_B:_Beast_ally_+1_POWER?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "HsaWNAsmAQ_OptionB", 1);
        } else {
            // No beasts for B — check for C
            DecisionQueueController::AddDecision($player, "YESNO", "-", 1, "Use_C:_Beast_ally_gains_cleave?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "HsaWNAsmAQ_OptionC", 1);
        }
    }
};

$customDQHandlers["HsaWNAsmAQ_OptionB"] = function($player, $parts, $lastDecision) {
    $chosen = intval(DecisionQueueController::GetVariable("BF_chosen"));
    $maxChoices = IsClassBonusActive($player) ? 2 : 1;
    if($lastDecision === "YES") {
        $beasts = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["BEAST"]);
        if(!empty($beasts)) {
            $chosen++;
            DecisionQueueController::StoreVariable("BF_chosen", "$chosen");
            $choices = implode("&", $beasts);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $choices, 1);
            DecisionQueueController::AddDecision($player, "CUSTOM", "HsaWNAsmAQ_TargetB", 1);
        }
    }
    if($chosen < $maxChoices) {
        $beasts = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["BEAST"]);
        if(!empty($beasts)) {
            DecisionQueueController::AddDecision($player, "YESNO", "-", 1, "Use_C:_Beast_ally_gains_cleave?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "HsaWNAsmAQ_OptionC", 1);
        }
    }
};

$customDQHandlers["HsaWNAsmAQ_TargetB"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "") {
        AddTurnEffect($lastDecision, "HsaWNAsmAQ_POWER");
    }
};

$customDQHandlers["HsaWNAsmAQ_OptionC"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        $beasts = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["BEAST"]);
        if(!empty($beasts)) {
            $choices = implode("&", $beasts);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $choices, 1);
            DecisionQueueController::AddDecision($player, "CUSTOM", "HsaWNAsmAQ_TargetC", 1);
        }
    }
};

$customDQHandlers["HsaWNAsmAQ_TargetC"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "") {
        AddTurnEffect($lastDecision, "HsaWNAsmAQ_CLEAVE");
    }
};

/**
 * Custom DQ handler: Call the Pack (mdiK8UC78c) — loop once per Animal ally,
 * offering the player the chance to put a Beast from hand onto the field.
 */
$customDQHandlers["CTP_BeastLoop"] = function($player, $parts, $lastDecision) {
    $remaining = intval(DecisionQueueController::GetVariable("CTP_remaining")) - 1;
    DecisionQueueController::StoreVariable("CTP_remaining", "$remaining");
    if($lastDecision !== "-" && $lastDecision !== "") {
        MZMove($player, $lastDecision, "myField");
        DecisionQueueController::CleanupRemovedCards();
    }
    if($remaining <= 0) return;
    $beasts = ZoneSearch("myHand", ["ALLY"], cardSubtypes: ["BEAST"]);
    if(empty($beasts)) return;
    $choices = implode("&", $beasts);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $choices, 1);
    DecisionQueueController::AddDecision($player, "CUSTOM", "CTP_BeastLoop", 1);
};

/**
 * Anthem of Vitality Harmonize: if harmonize is active, choose Animal/Beast ally for buff counters.
 */
$customDQHandlers["AnthemOfVitalityHarmonize"] = function($player, $parts, $lastDecision) {
    if(!IsHarmonizeActive($player)) return;
    $harmTargets = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["ANIMAL", "BEAST"]);
    if(empty($harmTargets)) return;
    $harmChoices = implode("&", $harmTargets);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $harmChoices, 1, "Choose_Animal/Beast_ally_for_buff_counters");
    DecisionQueueController::AddDecision($player, "CUSTOM", "AnthemOfVitalityBuff", 1);
};

$customDQHandlers["AnthemOfVitalityBuff"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "PASS" && $lastDecision !== "-" && !empty($lastDecision)) {
        AddCounters($player, $lastDecision, "buff", 2);
    }
};

/**
 * Rally the Peasants: process MZREARRANGE result.
 * lastDecision format: "ToHand=cardA;Reveal=cardB,cardC;"
 * If player dragged a Human to ToHand, pull it from the deck (already on bottom) into hand.
 */
$customDQHandlers["RallyPeasantsApply"] = function($player, $parts, $lastDecision) {
    $toHandIDs = [];
    foreach(explode(";", $lastDecision) as $pile) {
        $pile = trim($pile);
        if(empty($pile) || strpos($pile, "=") === false) continue;
        [$pileName, $cardStr] = explode("=", $pile, 2);
        $cardStr = trim($cardStr);
        if($pileName === "ToHand" && !empty($cardStr)) {
            $toHandIDs = explode(",", $cardStr);
        }
    }
    if(empty($toHandIDs)) return;
    $chosenID = $toHandIDs[0]; // only take the first one regardless of how many player moved
    $deck = &GetDeck($player);
    $hand = &GetHand($player);
    foreach($deck as $i => $card) {
        if($card->CardID === $chosenID) {
            SetFlashMessage('REVEAL:' . $chosenID);
            $obj = array_splice($deck, $i, 1)[0];
            array_push($hand, $obj);
            return;
        }
    }
};

/**
 * Slay the King On Attack: process YesNo answer to banish from material deck.
 */
$customDQHandlers["SlayTheKingOnAttack"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") {
        DecisionQueueController::StoreVariable("SlayTheKing_BanishedCardID", "");
        return;
    }
    $matChoices = DecisionQueueController::GetVariable("slayTheKingMats");
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $matChoices, 1, "Choose_card_to_banish");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SlayTheKingBanish", 1);
};

$customDQHandlers["SlayTheKingBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "PASS" || $lastDecision === "-" || empty($lastDecision)) {
        DecisionQueueController::StoreVariable("SlayTheKing_BanishedCardID", "");
        return;
    }
    $obj = GetZoneObject($lastDecision);
    DecisionQueueController::StoreVariable("SlayTheKing_BanishedCardID", $obj->CardID);
    MZMove($player, $lastDecision, "myBanish");
};

/**
 * Slay the King On Kill: process YesNo to play the banished card.
 */
$customDQHandlers["SlayTheKingOnKill"] = function($player, $parts, $lastDecision) {
    $banishedCardID = DecisionQueueController::GetVariable("SlayTheKing_BanishedCardID");
    DecisionQueueController::StoreVariable("SlayTheKing_BanishedCardID", "");
    if($lastDecision !== "YES") return;
    if(empty($banishedCardID) || $banishedCardID === "-") return;
    $banish = GetZone("myBanish");
    for($i = 0; $i < count($banish); ++$i) {
        if(!$banish[$i]->removed && $banish[$i]->CardID === $banishedCardID) {
            DoActivateCard($player, "myBanish-" . $i, true);
            break;
        }
    }
};

/**
 * Innervate Agility: apply stealth or spellshroud to all units you control.
 */
$customDQHandlers["InnervateAgilityApply"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        // Stealth: add TurnEffect to all units (champion + allies)
        AddGlobalEffects($player, "INNERVATE_STEALTH");
    } else {
        // Spellshroud: add TurnEffect to all units
        AddGlobalEffects($player, "INNERVATE_SPELLSHROUD");
    }
};

/**
 * Song of Frost: process YesNo to banish floating-memory GY card instead of reserve cost.
 */
$customDQHandlers["SongOfFrostAltCost"] = function($player, $parts, $lastDecision) {
    $reserveCost = intval($parts[0]);
    if($lastDecision === "YES") {
        // Banish the floating-memory GY card instead of paying reserve
        $floatingGY = [];
        $gy = GetZone("myGraveyard");
        for($i = 0; $i < count($gy); ++$i) {
            if(!$gy[$i]->removed && HasFloatingMemory($gy[$i])) {
                $floatingGY[] = "myGraveyard-" . $i;
            }
        }
        if(!empty($floatingGY)) {
            $choices = implode("&", $floatingGY);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $choices, 1, "Banish_a_floating-memory_card");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SongOfFrostBanish", 1);
        }
    } else {
        // Pay normal reserve cost
        for($i = 0; $i < $reserveCost; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
};

$customDQHandlers["SongOfFrostBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "PASS" && $lastDecision !== "-" && !empty($lastDecision)) {
        MZMove($player, $lastDecision, "myBanish");
    }
};

/**
 * Custom DQ handler: DeclareAllyLinkTarget — stores the chosen ally mzID
 * and CardID as DQ variables for Ally Link resolution.
 */
$customDQHandlers["DeclareAllyLinkTarget"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "PASS" || $lastDecision === "-" || empty($lastDecision)) {
        DecisionQueueController::StoreVariable("linkTargetMZ", "");
        DecisionQueueController::StoreVariable("linkTargetCardID", "");
        return;
    }
    $targetObj = GetZoneObject($lastDecision);
    DecisionQueueController::StoreVariable("linkTargetMZ", $lastDecision);
    DecisionQueueController::StoreVariable("linkTargetCardID", $targetObj !== null ? $targetObj->CardID : "");
};

// ============================================================================
// Domain Card Type — Recollection Upkeep, Passive Effects, and Helpers
// ============================================================================

/**
 * Process domain upkeep checks that trigger "At the beginning of your recollection phase".
 * Called from RecollectionPhase() BEFORE memory is returned to hand.
 *
 * Dawn of Ashes (4coy34bro8): reveal random memory; if not norm element, sacrifice.
 * Prismatic Sanctuary (9w0ejcyuvu): reveal random memory; if not fire/water/wind, sacrifice.
 */
function DomainRecollectionUpkeep($player) {
    $field = &GetField($player);
    for($i = count($field) - 1; $i >= 0; --$i) {
        if($field[$i]->removed) continue;
        // Right of Realm exemption: domains tagged NO_UPKEEP skip recollection upkeep
        if(in_array("NO_UPKEEP", $field[$i]->TurnEffects)) continue;
        switch($field[$i]->CardID) {
            case "4coy34bro8": // Dawn of Ashes
                DomainRevealMemoryUpkeep($player, $i, ["NORM"], "4coy34bro8");
                break;
            case "9w0ejcyuvu": // Prismatic Sanctuary
                DomainRevealMemoryUpkeep($player, $i, ["FIRE", "WATER", "WIND"], "9w0ejcyuvu");
                break;
            case "n1voy5ttkk": // Shatterfall Keep: sacrifice if < 3 water in graveyard
                {
                    global $playerID;
                    $gravZone = $player == $playerID ? "myGraveyard" : "theirGraveyard";
                    $waterGY = ZoneSearch($gravZone, cardElements: ["WATER"]);
                    if(count($waterGY) < 3) {
                        DoSacrificeFighter($player, "myField-" . $i);
                        DecisionQueueController::CleanupRemovedCards();
                    }
                }
                break;
            case "p4lpnvx7mn": // Exalted Dorumegian Throne: sacrifice if <= 4 other domains
                {
                    global $playerID;
                    $zone = $player == $playerID ? "myField" : "theirField";
                    $otherDomains = 0;
                    $fieldArr = GetZone($zone);
                    foreach($fieldArr as $fi => $fObj) {
                        if(!$fObj->removed && $fObj->CardID !== "p4lpnvx7mn" && PropertyContains(CardType($fObj->CardID), "DOMAIN")) {
                            $otherDomains++;
                        }
                    }
                    if($otherDomains <= 4) {
                        DoSacrificeFighter($player, "myField-" . $i);
                        DecisionQueueController::CleanupRemovedCards();
                    }
                }
                break;
        }
    }
}

/**
 * Domain upkeep helper: reveal a random memory card. If its element is NOT in the
 * allowed list, sacrifice the domain at fieldIndex.
 *
 * @param int    $player          The controlling player
 * @param int    $fieldIndex      Index of the domain in the field
 * @param array  $allowedElements Array of allowed element strings (e.g. ["NORM"])
 * @param string $domainCardID    CardID of the domain (for logging/identification)
 */
function DomainRevealMemoryUpkeep($player, $fieldIndex, $allowedElements, $domainCardID) {
    $memory = &GetMemory($player);
    if(count($memory) == 0) {
        // No memory cards — sacrifice (can't meet element condition)
        DoSacrificeFighter($player, "myField-" . $fieldIndex);
        DecisionQueueController::CleanupRemovedCards();
        return;
    }
    // Pick a random memory card and reveal it
    $randomIdx = array_rand($memory);
    $memObj = $memory[$randomIdx];
    $memMZ = "myMemory-" . $randomIdx;
    DoRevealCard($player, $memMZ);

    $element = CardElement($memObj->CardID);
    if(!in_array($element, $allowedElements)) {
        // Element doesn't match — sacrifice the domain
        // Re-fetch field index since CleanupRemovedCards may have shifted indices
        $field = &GetField($player);
        for($si = 0; $si < count($field); ++$si) {
            if(!$field[$si]->removed && $field[$si]->CardID === $domainCardID) {
                DoSacrificeFighter($player, "myField-" . $si);
                break;
            }
        }
        DecisionQueueController::CleanupRemovedCards();
    }
}

/**
 * Right of Realm (ptrz1bqry4): "Whenever you activate a domain card, you may sacrifice
 * CARDNAME. If you do, that domain enters the field without any of its upkeep abilities."
 *
 * This DQ handler is called after the YesNo prompt. If YES, sacrifice Right of Realm
 * and tag the domain (on the EffectStack) with NO_UPKEEP so materialize-sacrifice
 * and recollection upkeep checks skip it.
 */
$customDQHandlers["RightOfRealmChoice"] = function($player, $parts, $lastDecision) {
    $rightOfRealmIdx = intval($parts[0]);
    if($lastDecision !== "YES") return;

    // Sacrifice Right of Realm
    $field = &GetField($player);
    if(isset($field[$rightOfRealmIdx]) && !$field[$rightOfRealmIdx]->removed
        && $field[$rightOfRealmIdx]->CardID === "ptrz1bqry4") {
        DoSacrificeFighter($player, "myField-" . $rightOfRealmIdx);
        DecisionQueueController::CleanupRemovedCards();
    }

    // Tag the domain on the EffectStack with NO_UPKEEP
    // The domain is currently the top of the EffectStack (about to resolve)
    $es = &GetEffectStack();
    if(count($es) > 0) {
        $topIdx = count($es) - 1;
        $es[$topIdx]->TurnEffects[] = "NO_UPKEEP";
    }
};

/**
 * Mill N cards from a player's deck (move top N cards from deck to graveyard).
 * @param int    $player   The acting player (perspective for zone names)
 * @param string $deckRef  "myDeck" or "theirDeck"
 * @param string $gyRef    "myGraveyard" or "theirGraveyard"
 * @param int    $amount   Number of cards to mill
 */
function MillCards($player, $deckRef, $gyRef, $amount) {
    $deck = GetZone($deckRef);
    $n = min($amount, count($deck));
    // Always move index 0 (top of deck) — after each move the next card becomes index 0
    for($i = 0; $i < $n; ++$i) {
        MZMove($player, "$deckRef-0", $gyRef);
    }
}

/**
 * Avalon, Cursed Isle (41WnFOT5YS): "Whenever you activate a water element card,
 * target player puts the top two cards of their deck into their graveyard."
 * YES = mill yourself, NO = mill opponent.
 */
$customDQHandlers["AvalonMill"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        MillCards($player, "myDeck", "myGraveyard", 2);
    } else {
        MillCards($player, "theirDeck", "theirGraveyard", 2);
    }
};

// ============================================================================
// Split Damage — shared helper for processing MZSplitAssign results
// ============================================================================

/**
 * Process the comma-separated mzID:amount result from an MZSplitAssign decision.
 * Calls DealDamage for each non-zero assignment.
 * @param int    $player        The acting player
 * @param string $source        The mzID of the damage source (e.g. the card dealing damage)
 * @param string $assignmentStr The MZSplitAssign result, e.g. "myField-0:3,theirField-1:2"
 */
function ProcessSplitDamage($player, $source, $assignmentStr) {
    if(empty($assignmentStr) || $assignmentStr === "-") return;
    $pairs = explode(",", $assignmentStr);
    foreach($pairs as $pair) {
        $parts = explode(":", $pair);
        if(count($parts) < 2) continue;
        $targetMZ = $parts[0];
        $amount = intval($parts[1]);
        if($amount > 0) {
            DealDamage($player, $source, $targetMZ, $amount);
        }
    }
}

// ============================================================================
// Delevel — return the top card of the champion's lineage to material deck
// ============================================================================

/**
 * Delevel a player's champion: the current champion card is returned to the
 * owner's material deck, and the top subcard becomes the new champion.
 * @param int $player The acting player
 * @return bool True if deleveled successfully, false if champion has no lineage
 */
function Delevel($player) {
    $field = &GetField($player);
    foreach($field as &$obj) {
        if(!$obj->removed && PropertyContains(EffectiveCardType($obj), "CHAMPION") && $obj->Controller == $player) {
            $subcards = is_array($obj->Subcards) ? $obj->Subcards : [];
            if(empty($subcards)) return false; // Level 1 champion, can't delevel
            // Current champion goes to material deck
            $demotedCardID = $obj->CardID;
            MZAddZone($player, "myMaterial", $demotedCardID);
            // Previous champion (top subcard) becomes current
            $obj->CardID = array_shift($obj->Subcards);
            return true;
        }
    }
    return false;
}

// ============================================================================
// Erupting Rhapsody (dBAdWMoPEz) — banish fire GY cards, +level, harmonize split damage
// ============================================================================

/**
 * Begin the loop of optionally banishing fire cards from graveyard.
 * @param int $player        The acting player
 * @param int $banishedCount Number of cards banished so far
 */
function EruptingRhapsodyContinue($player, $banishedCount) {
    $fireGY = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
    if(empty($fireGY)) {
        EruptingRhapsodyFinalize($player, $banishedCount);
        return;
    }
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $fireGY), 1, tooltip:"Banish_a_fire_card_from_graveyard?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "EruptingRhapsodyPick|$banishedCount", 1);
}

$customDQHandlers["EruptingRhapsodyPick"] = function($player, $parts, $lastDecision) {
    $banishedCount = intval($parts[0]);
    if($lastDecision == "-" || $lastDecision == "") {
        EruptingRhapsodyFinalize($player, $banishedCount);
        return;
    }
    MZMove($player, $lastDecision, "myBanish");
    EruptingRhapsodyContinue($player, $banishedCount + 1);
};

/**
 * After banishing is done: apply +1 level per banished card, then harmonize check.
 * @param int $player        The acting player
 * @param int $banishedCount Number of fire cards banished
 */
function EruptingRhapsodyFinalize($player, $banishedCount) {
    if($banishedCount > 0) {
        // Champion gets +1 level per banished card until end of turn
        $champions = ZoneSearch("myField", ["CHAMPION"]);
        if(!empty($champions)) {
            AddTurnEffect($champions[0], "dBAdWMoPEz-" . $banishedCount);
        }
    }
    // Harmonize: if you've activated a Melody card this turn
    if(IsHarmonizeActive($player)) {
        // Get champion's current level (including the boost just applied)
        $champMZArr = ZoneSearch("myField", ["CHAMPION"]);
        $level = 0;
        if(!empty($champMZArr)) {
            $champObj = GetZoneObject($champMZArr[0]);
            $level = ObjectCurrentLevel($champObj);
        }
        if($level > 0) {
            $allUnits = array_merge(
                ZoneSearch("myField", ["ALLY", "CHAMPION"]),
                ZoneSearch("theirField", ["ALLY", "CHAMPION"])
            );
            $allUnits = FilterSpellshroudTargets($allUnits);
            if(!empty($allUnits)) {
                $mzID = DecisionQueueController::GetVariable("mzID");
                DecisionQueueController::StoreVariable("eruptingRhapsodySource", $mzID);
                $targetStr = implode("&", $allUnits);
                DecisionQueueController::AddDecision($player, "MZSPLITASSIGN", $level . "|" . $targetStr, 1, tooltip:"Split_LV_damage_among_units");
                DecisionQueueController::AddDecision($player, "CUSTOM", "EruptingRhapsodyDamage", 1);
            }
        }
    }
}

$customDQHandlers["EruptingRhapsodyDamage"] = function($player, $parts, $lastDecision) {
    $source = DecisionQueueController::GetVariable("eruptingRhapsodySource");
    ProcessSplitDamage($player, $source, $lastDecision);
};

// ============================================================================
// Lightweaver's Assault (zxB4tzy9iy) — [CB][EB] Reveal trigger: deal 2 to chosen unit
// ============================================================================

$customDQHandlers["LightweaverRevealDmg"] = function($player, $parts, $lastDecision) {
    if($lastDecision == "-" || $lastDecision == "") return;
    $revealedMZ = DecisionQueueController::GetVariable("revealedMZ");
    DealDamage($player, $revealedMZ, $lastDecision, 2);
};

// ============================================================================
// Advent of the Stormcaller (ZSSegCjquB) — reveal top LV, banish arcane, deal 2 each, rearrange rest
// ============================================================================

/**
 * Begin the loop of optionally banishing arcane cards from temp zone (revealed cards).
 * @param int $player        The acting player
 * @param int $banishedCount Number of arcane cards banished so far
 */
function AdventStormcallerBanishLoop($player, $banishedCount) {
    $arcaneTmp = ZoneSearch("myTempZone", cardElements: ["ARCANE"]);
    if(empty($arcaneTmp)) {
        AdventStormcallerDamagePhase($player, $banishedCount);
        return;
    }
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $arcaneTmp), 1, tooltip:"Banish_an_arcane_card?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "AdventBanishPick|$banishedCount", 1);
}

$customDQHandlers["AdventBanishPick"] = function($player, $parts, $lastDecision) {
    $banishedCount = intval($parts[0]);
    if($lastDecision == "-" || $lastDecision == "") {
        AdventStormcallerDamagePhase($player, $banishedCount);
        return;
    }
    MZMove($player, $lastDecision, "myBanish");
    AdventStormcallerBanishLoop($player, $banishedCount + 1);
};

/**
 * After banishing: deal 2 damage per banished card (each to a separate chosen unit).
 * @param int $player        The acting player
 * @param int $banishedCount Number of arcane cards banished
 */
function AdventStormcallerDamagePhase($player, $banishedCount) {
    if($banishedCount > 0) {
        DecisionQueueController::StoreVariable("adventDmgRemaining", strval($banishedCount));
        AdventStormcallerDamageStep($player, $banishedCount);
    } else {
        AdventStormcallerRearrange($player);
    }
}

/**
 * Recursive step: choose a unit and deal 2 damage, then continue or rearrange.
 * @param int $player    The acting player
 * @param int $remaining Number of damage choices remaining
 */
function AdventStormcallerDamageStep($player, $remaining) {
    if($remaining <= 0) {
        AdventStormcallerRearrange($player);
        return;
    }
    $allUnits = array_merge(
        ZoneSearch("myField", ["ALLY", "CHAMPION"]),
        ZoneSearch("theirField", ["ALLY", "CHAMPION"])
    );
    $allUnits = FilterSpellshroudTargets($allUnits);
    if(empty($allUnits)) {
        AdventStormcallerRearrange($player);
        return;
    }
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $allUnits), 1, tooltip:"Choose_unit_to_deal_2_damage");
    DecisionQueueController::AddDecision($player, "CUSTOM", "AdventDealDmg|$remaining", 1);
}

$customDQHandlers["AdventDealDmg"] = function($player, $parts, $lastDecision) {
    $remaining = intval($parts[0]);
    if($lastDecision != "-" && $lastDecision != "") {
        $mzID = DecisionQueueController::GetVariable("mzID");
        DealDamage($player, $mzID, $lastDecision, 2);
    }
    AdventStormcallerDamageStep($player, $remaining - 1);
};

/**
 * After damage phase: move remaining temp zone cards to deck top, then Glimpse to rearrange.
 */
function AdventStormcallerRearrange($player) {
    $tempCards = ZoneSearch("myTempZone");
    if(empty($tempCards)) return;
    $n = count($tempCards);
    // Move temp zone cards to top of deck
    $deck = &GetDeck($player);
    $tempZone = &GetTempZone($player);
    foreach($tempZone as $obj) {
        if(!$obj->removed) {
            $newDeckObj = new Deck($obj->CardID, 'Deck', $player);
            array_unshift($deck, $newDeckObj);
            $obj->Remove();
        }
    }
    // Reindex deck
    for($i = 0; $i < count($deck); ++$i) {
        $deck[$i]->mzIndex = $i;
    }
    // Use Glimpse to let the player arrange top N
    Glimpse($player, $n);
}

// --- Smash with Obelisk (2kkvoqk1l7): sacrifice domain and store its reserve cost ---
$customDQHandlers["SmashWithObeliskSacrifice"] = function($player, $parts, $lastDecision) {
    if($lastDecision == "-" || $lastDecision == "") {
        DecisionQueueController::StoreVariable("smashObeliskBonus", "0");
        return;
    }
    $obj = GetZoneObject($lastDecision);
    $cost = ($obj !== null) ? CardCost_reserve($obj->CardID) : 0;
    DoSacrificeFighter($player, $lastDecision);
    DecisionQueueController::StoreVariable("smashObeliskBonus", strval($cost));
};

$customDQHandlers["FiretunedAutomatonDiscard"] = function($player, $parts, $lastDecision) {
    if($lastDecision == "-" || $lastDecision == "" || $lastDecision == "PASS") return;
    DoDiscardCard($player, $lastDecision);
};

$customDQHandlers["FrigidBashPayment"] = function($player, $parts, $lastDecision) {
    $targetMZ = $parts[0];
    if($lastDecision === "YES") {
        // Player chose to pay (2) — queue 2 reserve card payments
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 1);
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 1);
    } else {
        // Player declined — target doesn't wake up during next wake up phase
        AddTurnEffect($targetMZ, "SKIP_WAKEUP");
    }
};

$customDQHandlers["SanctumOfEsotericTruth1"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    // Put first card on bottom of deck
    MZMove($player, $lastDecision, "myDeck");
    // Choose second card
    $handAndMemory = array_merge(ZoneSearch("myHand"), ZoneSearch("myMemory"));
    if(!empty($handAndMemory)) {
        $choices = implode("&", $handAndMemory);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $choices, 1,
            tooltip:"Sanctum:_Put_card_on_bottom_of_deck_(2/2)");
        DecisionQueueController::AddDecision($player, "CUSTOM", "SanctumOfEsotericTruth2", 1);
    }
};

$customDQHandlers["SanctumOfEsotericTruth2"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    // Put second card on bottom of deck
    MZMove($player, $lastDecision, "myDeck");
    // Draw two cards
    Draw($player, 2);
};

// --- Fanatical Devotee (1gxrpx8jyp): multi-step banish fire cards from graveyard ---
function FanaticalDevoteeContinue($player, $firstChoice) {
    if($firstChoice == "-" || $firstChoice == "" || $firstChoice == "PASS") return;
    // Banish the first chosen card
    MZMove($player, $firstChoice, "myBanish");
    // Build choices for second fire card (excluding the one we just banished)
    $fireCards = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
    $filtered = [];
    $skippedSelf = false;
    foreach($fireCards as $fc) {
        $obj = GetZoneObject($fc);
        if(!$skippedSelf && $obj->CardID === "1gxrpx8jyp") {
            $skippedSelf = true;
            continue;
        }
        $filtered[] = $fc;
    }
    if(empty($filtered)) return;
    $choices2 = implode("&", $filtered);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $choices2, 1, tooltip:"Banish_second_fire_card");
    DecisionQueueController::AddDecision($player, "CUSTOM", "FanaticalDevoteeBanish2", 1);
}

$customDQHandlers["FanaticalDevoteeBanish2"] = function($player, $parts, $lastDecision) {
    if($lastDecision == "-" || $lastDecision == "") return;
    // Banish the second fire card
    MZMove($player, $lastDecision, "myBanish");
    // Now deal 3 damage to target champion
    $champions = array_merge(
        ZoneSearch("myField", ["CHAMPION"]),
        ZoneSearch("theirField", ["CHAMPION"])
    );
    $champions = FilterSpellshroudTargets($champions);
    if(empty($champions)) return;
    $targetStr = implode("&", $champions);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, tooltip:"Choose_champion_to_deal_3_damage");
    DecisionQueueController::AddDecision($player, "CUSTOM", "FanaticalDevoteeDamage", 1);
};

$customDQHandlers["FanaticalDevoteeDamage"] = function($player, $parts, $lastDecision) {
    if($lastDecision == "-" || $lastDecision == "") return;
    // Determine which player's champion was chosen
    $targetPlayer = (strpos($lastDecision, "their") === 0) ? (($player == 1) ? 2 : 1) : $player;
    DealChampionDamage($targetPlayer, 3);
};

$customDQHandlers["HeatwaveGeneratorBuff"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddTurnEffect($lastDecision, "fzcyfrzrpl");
};

$customDQHandlers["EternalKingdomUpkeep"] = function($player, $parts, $lastDecision) {
    $fieldIdx = intval($parts[0]);
    $field = &GetField($player);
    if($lastDecision === "YES") {
        // Pay 2 reserve
        $hand = &GetHand($player);
        if(count($hand) >= 2) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 1);
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 1);
        } else {
            // Can't pay — sacrifice
            if(isset($field[$fieldIdx]) && !$field[$fieldIdx]->removed && $field[$fieldIdx]->CardID === "fyoz23yfzk") {
                DoSacrificeFighter($player, "myField-" . $fieldIdx);
                DecisionQueueController::CleanupRemovedCards();
            }
        }
    } else {
        // Sacrifice
        if(isset($field[$fieldIdx]) && !$field[$fieldIdx]->removed && $field[$fieldIdx]->CardID === "fyoz23yfzk") {
            DoSacrificeFighter($player, "myField-" . $fieldIdx);
            DecisionQueueController::CleanupRemovedCards();
        }
    }
};

/**
 * Enhance Hearing: after player chooses or passes, move remaining TempZone to bottom of deck.
 */
function EnhanceHearingFinish($player, $chosen) {
    if($chosen !== "PASS" && $chosen !== "-" && $chosen !== "") {
        Reveal($player, revealedMZ: $chosen);
        MZMove($player, $chosen, "myHand");
    }
    // Move remaining TempZone cards to bottom of deck
    $remaining = ZoneSearch("myTempZone");
    foreach($remaining as $rmz) {
        MZMove($player, $rmz, "myDeck");
    }
}

// --- Assemble the Ancients (moi0a5uhjx): sacrifice domains then summon Automaton Drone tokens ---

function AssembleAncientsSacrifice($player, $count) {
    $domains = ZoneSearch("myField", ["DOMAIN"]);
    if(empty($domains)) {
        AssembleAncientsFinalize($player, $count);
        return;
    }
    $domainStr = implode("&", $domains);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $domainStr, 1, "Sacrifice_a_domain?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "AssembleAncientsSacChoice|$count", 1);
}

$customDQHandlers["AssembleAncientsSacChoice"] = function($player, $parts, $lastDecision) {
    $count = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        AssembleAncientsFinalize($player, $count);
        return;
    }
    DoSacrificeFighter($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    AssembleAncientsSacrifice($player, $count + 1);
};

function AssembleAncientsFinalize($player, $count) {
    if($count <= 0) return;
    for($i = 0; $i < $count; ++$i) {
        MZAddZone($player, "myField", "mu6gvnta6q"); // Automaton Drone token
        $field = &GetField($player);
        $newIdx = count($field) - 1;
        $field[$newIdx]->Status = 1; // Enter rested
        AddCounters($player, "myField-" . $newIdx, "buff", $count);
        AddTurnEffect("myField-" . $newIdx, "VIGOR_EOT");
    }
}

// --- Navigation Compass (sw2ugmnmp5) DQ handler ---
$customDQHandlers["NavCompass_Discard"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    DoDiscardCard($player, $lastDecision);
};

// --- Swooping Talons (rj52215upu) helpers ---
function SwoopingTalonsMode1($player) {
    $allAllies = array_merge(ZoneSearch("myField", ["ALLY"]), ZoneSearch("theirField", ["ALLY"]));
    if(empty($allAllies)) return;
    $allyStr = implode("&", $allAllies);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $allyStr, 1, "Deal_2_damage_to_target_ally");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SwoopingTalons_DealDmg", 1);
}

function SwoopingTalonsMode2($player) {
    $validItems = [];
    $allItems = array_merge(ZoneSearch("myField", ["ITEM", "REGALIA"]), ZoneSearch("theirField", ["ITEM", "REGALIA"]));
    foreach($allItems as $mzI) {
        $iObj = GetZoneObject($mzI);
        if(CardCost_memory($iObj->CardID) == 0 || CardCost_reserve($iObj->CardID) <= 4) {
            $validItems[] = $mzI;
        }
    }
    if(empty($validItems)) return;
    $itemStr = implode("&", $validItems);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $itemStr, 1, "Destroy_target_item");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SwoopingTalons_DestroyItem", 1);
}

$customDQHandlers["SwoopingTalons_DealDmg"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $mzID = DecisionQueueController::GetVariable("mzID");
    DealDamage($player, $mzID, $lastDecision, 2);
};

$customDQHandlers["SwoopingTalons_DestroyItem"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $targetObj = GetZoneObject($lastDecision);
    if($targetObj === null) return;
    OnLeaveField($player, $lastDecision);
    $dest = $player == $targetObj->Controller ? "myGraveyard" : "theirGraveyard";
    MZMove($player, $lastDecision, $dest);
    DecisionQueueController::CleanupRemovedCards();
};

$customDQHandlers["SwoopingTalons_Choice"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        SwoopingTalonsMode1($player);
    } else {
        SwoopingTalonsMode2($player);
    }
};

// --- Gather (Grand Archive keyword): summon a random herb token ---

function Gather($player) {
    $herbTokens = ["i0a5uhjxhk", "5joh300z2s", "bd7ozuj68m", "soporhlq2k", "jnltv5klry", "69iq4d5vet"];
    $randomHerb = $herbTokens[array_rand($herbTokens)];
    MZAddZone($player, "myField", $randomHerb);
}

// --- Tonoris, Genesis Aegis (ta6qsesw2u): recollection phase Obelisk summon ---

function TonorisRecollection($player, $fieldIndex) {
    $field = &GetField($player);
    $obj = $field[$fieldIndex];
    $obelisks = [
        "wk0pw0y6is" => "Summon_Obelisk_of_Armaments?",
        "xy5lh23qu7" => "Summon_Obelisk_of_Fabrication?",
        "d6soporhlq" => "Summon_Obelisk_of_Protection?"
    ];
    $counters = is_array($obj->Counters) ? $obj->Counters : [];
    $chosen = isset($counters['tonoris_chosen']) ? $counters['tonoris_chosen'] : [];
    $available = [];
    foreach($obelisks as $id => $tooltip) {
        if(!in_array($id, $chosen)) {
            $available[$id] = $tooltip;
        }
    }
    if(empty($available)) return;
    if(count($available) == 1) {
        $obeliskID = array_key_first($available);
        MZAddZone($player, "myField", $obeliskID);
        $chosen[] = $obeliskID;
        if(!is_array($field[$fieldIndex]->Counters)) $field[$fieldIndex]->Counters = [];
        $field[$fieldIndex]->Counters['tonoris_chosen'] = $chosen;
        return;
    }
    $firstID = array_key_first($available);
    $firstTooltip = $available[$firstID];
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:$firstTooltip);
    DecisionQueueController::AddDecision($player, "CUSTOM", "TonorisChooseObelisk|$fieldIndex|$firstID", 1);
}

$customDQHandlers["TonorisChooseObelisk"] = function($player, $parts, $lastDecision) {
    $fieldIndex = intval($parts[0]);
    $currentObeliskID = $parts[1];
    $field = &GetField($player);
    if(!isset($field[$fieldIndex]) || $field[$fieldIndex]->removed) return;
    $obj = $field[$fieldIndex];
    $counters = is_array($obj->Counters) ? $obj->Counters : [];
    $chosen = isset($counters['tonoris_chosen']) ? $counters['tonoris_chosen'] : [];

    if($lastDecision === "YES") {
        MZAddZone($player, "myField", $currentObeliskID);
        $chosen[] = $currentObeliskID;
        if(!is_array($field[$fieldIndex]->Counters)) $field[$fieldIndex]->Counters = [];
        $field[$fieldIndex]->Counters['tonoris_chosen'] = $chosen;
        return;
    }

    // Player said NO — move to next available obelisk
    $obelisks = ["wk0pw0y6is", "xy5lh23qu7", "d6soporhlq"];
    $tooltips = [
        "wk0pw0y6is" => "Summon_Obelisk_of_Armaments?",
        "xy5lh23qu7" => "Summon_Obelisk_of_Fabrication?",
        "d6soporhlq" => "Summon_Obelisk_of_Protection?"
    ];
    $remaining = [];
    foreach($obelisks as $id) {
        if(!in_array($id, $chosen) && $id !== $currentObeliskID) {
            $remaining[] = $id;
        }
    }
    if(count($remaining) == 1) {
        MZAddZone($player, "myField", $remaining[0]);
        $chosen[] = $remaining[0];
        if(!is_array($field[$fieldIndex]->Counters)) $field[$fieldIndex]->Counters = [];
        $field[$fieldIndex]->Counters['tonoris_chosen'] = $chosen;
    } elseif(count($remaining) > 1) {
        DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:$tooltips[$remaining[0]]);
        DecisionQueueController::AddDecision($player, "CUSTOM", "TonorisChooseObelisk|$fieldIndex|$remaining[0]", 1);
    }
};

// --- Synthetic Core (w0y6isxy5l): return dying Automaton ally to memory ---

$customDQHandlers["SyntheticCoreChoice"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $fieldIndex = intval($parts[0]);
    $dyingCardID = $parts[1];
    // Banish Synthetic Core
    MZMove($player, "myField-" . $fieldIndex, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    // Return the dying ally from graveyard to memory
    $gy = GetZone("myGraveyard");
    for($gi = count($gy) - 1; $gi >= 0; --$gi) {
        if(!$gy[$gi]->removed && $gy[$gi]->CardID === $dyingCardID) {
            MZMove($player, "myGraveyard-" . $gi, "myMemory");
            break;
        }
    }
};

?>