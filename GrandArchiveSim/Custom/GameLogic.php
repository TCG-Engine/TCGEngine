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
    // Check if opponent has Corhazi Outlook lockdown effect
    $opponent = ($player == 1) ? 2 : 1;
    if(GlobalEffectCount($opponent, "rw8qq1uwq8-lockdown") > 0) {
        // Activation is blocked
        return;
    }
    
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
            if(PropertyContains(CardType($fieldObj->CardID), "CHAMPION")) {
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

    // Deflecting Edge (g7uDOmUf2u): costs 1 less if you control a Sword weapon
    if($obj->CardID === "g7uDOmUf2u") {
        if(!empty(ZoneSearch("myField", ["WEAPON"], cardSubtypes: ["SWORD"]))) {
            $reserveCost = max(0, $reserveCost - 1);
        }
    }

    //1.3 Declaring Costs — Prepare keyword: optional removal of preparation counters
    $hasPrepare = false;
    if(HasKeyword_Prepare($obj)) {
        $prepValue = intval(GetKeyword_Prepare_Value($obj));
        $myField = GetZone("myField");
        $champMZ = null;
        foreach($myField as $fi => $fieldObj) {
            if(PropertyContains(CardType($fieldObj->CardID), "CHAMPION")) {
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

    if(!$hasAdditionalCost) {
        // No additional cost — store default and queue normal reserve + opportunity
        DecisionQueueController::StoreVariable("additionalCostPaid", "NO");

        //1.8 Paying Costs
        for($i = 0; $i < $reserveCost; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }

        //1.9 Activation — grant Opportunity to the opponent before resolving
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
    }
    // When $hasAdditionalCost is true, the DeclareAdditionalCost handler takes over
    // queuing reserve payments and EffectStackOpportunity after the player answers.
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
    } else if(PropertyContains($cardType, "PHANTASIA")) {
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
            MZMove($player, $mzCard, "myBanish");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "iohZMWh5v5": // Blazing Throw: sacrifice a weapon as additional cost
            $weapons = ZoneSearch("myField", ["WEAPON"]);
            if(!empty($weapons)) {
                $choices = implode("&", $weapons);
                DecisionQueueController::AddDecision($player, "MZCHOOSE", $choices, 1);
                DecisionQueueController::AddDecision($player, "CUSTOM", "BT_SacrificeWeapon", 1);
            }
            break;
    }
}

function DoActivatedAbility($player, $mzCard, $abilityIndex = 0) {
    global $customDQHandlers;
    $sourceObject = &GetZoneObject($mzCard);
    // Capture cardID now — the card may be moved to banishment as a cost below.
    $cardID = $sourceObject->CardID;
    
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
    $customDQHandlers["AbilityActivated"]($player, [$cardID, $selectedAbilityIndex], null);

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

function OnLeaveField($player, $mzID) {
    global $leaveFieldAbilities;
    $obj = GetZoneObject($mzID);
    if($obj === null) return;
    $controller = $obj->Controller;
    DecisionQueueController::CleanupRemovedCards();
    if(isset($leaveFieldAbilities[$obj->CardID . ":0"])) $leaveFieldAbilities[$obj->CardID . ":0"]($controller);
}

function DoAllyDestroyed($player, $mzCard) {
    global $allyDestroyedAbilities;
    $obj = GetZoneObject($mzCard);
    $controller = $obj->Controller;
    OnLeaveField($player, $mzCard);
    $dest = $player == $controller ? "myGraveyard" : "theirGraveyard";
    MZMove($player, $mzCard, $dest);
    if(isset($allyDestroyedAbilities[$obj->CardID . ":0"])) {
        $allyDestroyedAbilities[$obj->CardID . ":0"]($controller);
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
            $field[$i]->Status = 2;
        }
    }
}

function OnEnter($player, $mzID) {
    global $enterAbilities;
    $obj = GetZoneObject($mzID);
    $CardID = $obj->CardID;
    DecisionQueueController::CleanupRemovedCards();
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
                case "P7hHZBVScB": // Orb of Glitter: glimpse 1 during recollection
                    Glimpse($turnPlayer, 1);
                    break;
                case "ZfCtSldRIy": // Windrider Mage: CB may return to hand + enlighten
                    if(IsClassBonusActive($turnPlayer, CardClasses("ZfCtSldRIy"))) {
                        DecisionQueueController::AddDecision($turnPlayer, "YESNO", "-", 1, tooltip:"Return_Windrider_Mage_to_hand?");
                        DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "WindriderMageBounce|$i", 1);
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

    // Mistbound Watcher (mA4n0Z7BQz): CB add 1 enlighten counter on champion at end of turn
    $field = &GetField($turnPlayer);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "mA4n0Z7BQz") {
            if(IsClassBonusActive($turnPlayer, CardClasses("mA4n0Z7BQz"))) {
                // Find champion and add enlighten counter
                for($ci = 0; $ci < count($field); ++$ci) {
                    if(!$field[$ci]->removed && CardType($field[$ci]->CardID) === "CHAMPION") {
                        AddCounters($turnPlayer, "myField-" . $ci, "enlighten", 1);
                        break;
                    }
                }
            }
            break; // Only one Mistbound Watcher matters
        }
    }

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
        case "WUAOMTZ7P2": // Intrepid Highwayman: +3 POWER while retaliating
            if(DecisionQueueController::GetVariable("CombatRetaliator") !== null) {
                $power += 3;
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
            default:
                // Imperious Highlander: dynamic +X POWER until end of turn (effect ID: 659ytyj2s3-X)
                if(strpos($effectID, "659ytyj2s3-") === 0) {
                    $power += intval(substr($effectID, strlen("659ytyj2s3-")));
                }
                break;
        }
    }
    // Lorraine, Blademaster (TJTeWcZnsQ): if champion has TJTeWcZnsQ TurnEffect,
    // all attack cards get +2 POWER until end of turn.
    if(PropertyContains(CardType($obj->CardID), "ATTACK")) {
        $controller = $obj->Controller ?? null;
        if($controller !== null && $controller > 0) {
            $field = GetField($controller);
            foreach($field as $fieldObj) {
                if(PropertyContains(CardType($fieldObj->CardID), "CHAMPION") && in_array("TJTeWcZnsQ", $fieldObj->TurnEffects)) {
                    $power += 2;
                    break;
                }
            }
        }
    }
    // Zander, Always Watching (tOK1Gr0N8f) — Inherited Effect:
    // +1 POWER to attacks while attacking a rested unit.
    // Applies when tOK1Gr0N8f is in the champion's lineage (current champion or subcards).
    if(PropertyContains(CardType($obj->CardID), "ATTACK")) {
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
            default: break;
        }
    }
    // Field-presence passives — iterate once and switch on card ID
    // Each unique card's passive is only counted once (duplicates don't stack)
    if(PropertyContains(CardType($obj->CardID), "CHAMPION")) {
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
            case "nIKhHFa0rK_HP": // Cry for Help class bonus: +1 LIFE until end of turn
                $cardLife += 1;
                break;
            case "x7u6wzh973": // Frostbinder Apostle: -4 LIFE until end of turn
                $cardLife -= 4;
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
    // Allies and champions can attack; champions with weapons can also start combat
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

// Persistent per-card TurnEffects that survive ExpireEffects across turns.
// SKIP_WAKEUP: consumed by WakeUpPhase (one-time skip).
// FROZEN_BY_SNOW_FAIRY: persists as long as opponent controls Snow Fairy.
// SPELLSHROUD_NEXT_TURN / STEALTH_NEXT_TURN: "until beginning of your next turn" effects,
//   consumed by WakeUpPhase of the controller's next turn.
$persistentTurnEffects = [];
$persistentTurnEffects["SKIP_WAKEUP"] = true;
$persistentTurnEffects["FROZEN_BY_SNOW_FAIRY"] = true;
$persistentTurnEffects["SPELLSHROUD_NEXT_TURN"] = true;
$persistentTurnEffects["STEALTH_NEXT_TURN"] = true;

$doesGlobalEffectApply["9GWxrTMfBz"] = function($obj) { //Cram Session
    return PropertyContains(CardType($obj->CardID), "CHAMPION");
};

$doesGlobalEffectApply["zpkcFs72Ah"] = function($obj) { //Smack with Flute: champion gets +1 level until end of turn
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

$doesGlobalEffectApply["rxxwQT054x"] = function($obj) { //Command the Hunt: allies get +2 POWER
    return PropertyContains(CardType($obj->CardID), "ALLY");
};

$doesGlobalEffectApply["rxxwQT054x_VIGOR"] = function($obj) { //Command the Hunt: allies gain vigor
    return PropertyContains(CardType($obj->CardID), "ALLY");
};

$doesGlobalEffectApply["vcZSHNHvKX"] = function($obj) { //Spirit Blade: Ghost Strike: +1 POWER on champion attacks
    return PropertyContains(CardType($obj->CardID), "CHAMPION");
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
    return PropertyContains(CardType($obj->CardID), "CHAMPION");
};

$doesGlobalEffectApply["rw8qq1uwq8-lockdown"] = function($obj) { //Corhazi Outlook: Opponents can't activate cards this turn
    return false; // Global effect only, no visual on cards
};

$doesGlobalEffectApply["aKgdkLSBza"] = function($obj) { //Wilderness Harpist
    return PropertyContains(CardType($obj->CardID), "CHAMPION");
};

$doesGlobalEffectApply["HsaWNAsmAQ"] = function($obj) { // Bestial Frenzy: +1 level applies only to champions
    return PropertyContains(CardType($obj->CardID), "CHAMPION");
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
        if(!$obj->removed && PropertyContains(CardType($obj->CardID), "CHAMPION") && $obj->Controller == $player) {
            $champElement = CardElement($obj->CardID);
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
            // Safeguard Amulet: prevent up to 4 non-combat damage (one-time)
            if(in_array("yj2rJBREH8", $obj->TurnEffects)) {
                $prevented = min(4, $amount);
                $amount -= $prevented;
                $obj->TurnEffects = array_values(array_filter($obj->TurnEffects, fn($e) => $e !== "yj2rJBREH8"));
            }
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

/**
 * Get the full lineage of a player's champion (current champion CardID + all subcards).
 * Returns an array of CardIDs representing the lineage from newest to oldest.
 * @param int $player  The player number
 * @return array  Array of CardIDs, empty if no champion on field
 */
function GetChampionLineage($player) {
    $field = &GetField($player);
    foreach($field as $obj) {
        if(!$obj->removed && PropertyContains(CardType($obj->CardID), "CHAMPION") && $obj->Controller == $player) {
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

function HasVigor($obj) {
    if(HasKeyword_Vigor($obj)) return true;
    // Command the Hunt (rxxwQT054x): allies gain vigor via global effect
    if(ObjectHasEffect($obj, "rxxwQT054x_VIGOR")) return true;
    return false;
}

function HasStealth($obj) {
    // Patient Rogue: [Class Bonus] stealth while awake
    if($obj->CardID === "CvvgJR4fNa") {
        return isset($obj->Status) && $obj->Status == 2 && IsClassBonusActive($obj->Controller, ["ASSASSIN"]);
    }
    // Blackmarket Broker (hHVf5xyjob): CB stealth while champion has 3+ prep counters
    if($obj->CardID === "hHVf5xyjob") {
        if(IsClassBonusActive($obj->Controller, CardClasses("hHVf5xyjob"))) {
            $pField = &GetField($obj->Controller);
            foreach($pField as $fCard) {
                if(!$fCard->removed && CardType($fCard->CardID) === "CHAMPION") {
                    if(GetCounterCount($fCard, "prep") >= 3) return true;
                    break;
                }
            }
        }
    }
    if(HasKeyword_Stealth($obj)) return true;
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
        }
    }
    return false;
}

function HasTrueSight($obj) {
    if(HasKeyword_TrueSight($obj)) return true;
    if(ObjectHasEffect($obj, "iiZtKTulPg")) return true; // Eye of Argus
    if(ObjectHasEffect($obj, "F1t18omUlx_SIGHT")) return true; // Beastbond Paws
    return false;
}

/**
 * Check whether a field object currently has Spellshroud.
 * Objects with spellshroud can't be targeted by Spells.
 * Sources:
 *   - TurnEffect "SPELLSHROUD" (until end of turn, e.g. Beastbond Boots)
 *   - TurnEffect "SPELLSHROUD_NEXT_TURN" (until beginning of next turn, e.g. Zander)
 */
function HasSpellshroud($obj) {
    if(in_array("SPELLSHROUD", $obj->TurnEffects)) return true;
    if(in_array("SPELLSHROUD_NEXT_TURN", $obj->TurnEffects)) return true;
    return false;
}

/**
 * Check whether a card has the Hindered keyword.
 * Hindered: "This object enters the field rested."
 * Hindered is redundant.
 */
function HasHindered($obj) {
    if(HasKeyword_Hindered($obj)) return true;
    return false;
}

/**
 * Check whether a field object currently has the Reservable keyword.
 * Reservable: "While paying for a reserve cost, you may rest this object to pay for 1 of that cost."
 * Reservable is redundant.
 */
function HasReservable($obj) {
    if(HasKeyword_Reservable($obj)) return true;
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
 * Virtual property callback: returns the number of durability counters on the object.
 * Used for the DurabilityCounterCount display badge.
 */
function GetDurabilityCounterCount($obj) {
    return GetCounterCount($obj, "durability");
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

?>