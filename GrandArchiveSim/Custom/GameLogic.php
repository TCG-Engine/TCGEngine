<?php

$debugMode = true;
$customDQHandlers = [];

include_once __DIR__ . '/CardLogic.php';
include_once __DIR__ . '/CombatLogic.php';
include_once __DIR__ . '/MaterializeLogic.php';
include_once __DIR__ . '/PotionLogic.php';

// --- Additional Activation Costs Registry ---
// Cards that offer an optional extra reserve cost at activation time (Grand Archive rule 1.3).
// Each entry maps a cardID to:
//   'prompt'       => string shown in the YesNo dialog
//   'extraReserve' => int, number of extra hand→memory payments
//   'condition'    => callable($player) that returns true if the option should be offered
$additionalActivationCosts = [];

// --- Imbue Cards Registry ---
// Maps cardID => imbue threshold (N). A card becomes imbued when at least N of
// the cards reserved to pay its cost match the card's element.
$Imbue_Cards = [];
$Imbue_Cards["7cx66hjlgx"] = 3; // Verdigris Decree (WIND)
$Imbue_Cards["ipl6gt7lh9"] = 3; // Cerulean Decree (WATER)
$Imbue_Cards["tjej4mcnqs"] = 3; // Vermilion Decree (FIRE)
$Imbue_Cards["3zb9p4lgdl"] = 2; // Fractal of Rain (WATER)
$Imbue_Cards["coxpnjvt9y"] = 2; // Suffocating Miasma (UMBRA)
$Imbue_Cards["cy3gme0xxw"] = 1; // Cultivate (WIND)
$Imbue_Cards["ooffy4dwav"] = 2; // Slip Away (UMBRA)
$Imbue_Cards["brq9x9z2k2"] = 2; // Skirting Step (WIND)
$Imbue_Cards["08kkz07nau"] = 3; // Surging Bolt (FIRE)
$Imbue_Cards["fz1nr5a3pm"] = 2; // Windmill Engineer (WIND)
$Imbue_Cards["vw2ifz1nr5"] = 3; // Andronika (WIND)
$Imbue_Cards["4a87hk0bkh"] = 3; // Splashing Spearguard (WATER)
$Imbue_Cards["disqw3d0o5"] = 4; // Captain Archer (WIND)
$Imbue_Cards["ep3ajxiyd3"] = 2; // Squallbind Pounce (WIND)
$Imbue_Cards["flzvpkc0ni"] = 2; // Moontide Illusionist (WATER)
$Imbue_Cards["myvztzk3v8"] = 3; // Razorblade Execution (WIND)
$Imbue_Cards["s2tzwv1uw3"] = 3; // Shangxiang, Fierce Princess (NORM)

// Crux Sight (P9Y1Q5cQ0F): "As an additional cost you may pay (2). If you do,
// banish this card as it resolves and return a crux card from graveyard to hand."
// No condition — always offer when affordable; recovery may fizzle at resolution.
$additionalActivationCosts["P9Y1Q5cQ0F"] = [
    'prompt'       => 'Pay_2_additional_reserve_to_banish_and_recover_crux?',
    'extraReserve' => 2,
];

// --- Kindle Cards Registry ---
// Maps cardID => kindle N value. Kindle N: "You may banish up to N fire element cards
// from your graveyard as you activate this card. Each one pays for (1) of this card's cost."
// Only active when Class Bonus is met.
$Kindle_Cards = [];
$Kindle_Cards["1ym2py8u7q"] = 3; // Glowering Conflagration (FIRE)
$Kindle_Cards["xllhbjr20n"] = 3; // Lu Xun, Pyre Strategist (FIRE) - Kindle 3
$Kindle_Cards["0s6solta0h"] = 4; // Rapid Combustion (FIRE) - Kindle 4

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
$lineageReleaseAbilities["e3z4pyx8bd"] = [ // Diana, Keen Huntress
    'name' => 'LR: Materialize Gun',
    'effect' => function($player) {
        // Materialize a Gun card from material deck (you still pay its costs)
        $materialZone = GetZone("myMaterial");
        $gunMZs = [];
        for($i = 0; $i < count($materialZone); ++$i) {
            $obj = $materialZone[$i];
            if(!$obj->removed && PropertyContains(CardSubtypes($obj->CardID), "GUN")) {
                $gunMZs[] = "myMaterial-" . $i;
            }
        }
        if(empty($gunMZs)) return;
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $gunMZs), 1, "Materialize_a_Gun");
        DecisionQueueController::AddDecision($player, "CUSTOM", "MATERIALIZE", 1);
    }
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
        case "myGraveyard":
            // Frost Shard (jnsl7ddcgw): [CB] activate from GY if leveled up this turn, banish on resolve
            if($currentPhase == "MAIN" && $playerID == $turnPlayer) {
                $gyObj = GetZoneObject($actionCard);
                if($gyObj !== null && !$gyObj->removed && $gyObj->CardID === "jnsl7ddcgw") {
                    if(IsClassBonusActive($playerID, ["MAGE"]) &&
                       GlobalEffectCount($playerID, "LEVELED_UP_THIS_TURN") > 0) {
                        $handObj = MZMove($playerID, $actionCard, "myHand");
                        $hand = &GetHand($playerID);
                        $handIdx = count($hand) - 1;
                        global $gyActivatedCardID;
                        $gyActivatedCardID = "jnsl7ddcgw";
                        ActivateCard($playerID, "myHand-" . $handIdx, false);
                        return "PLAY";
                    }
                }
                // Sword Saint of Everflame (lpy7ie4v8n): [CB:Warrior] (2), banish from GY → fire weapon/ally +2 POWER
                if($gyObj !== null && !$gyObj->removed && $gyObj->CardID === "lpy7ie4v8n") {
                    if(IsClassBonusActive($playerID, ["WARRIOR"])) {
                        $hand = &GetHand($playerID);
                        if(count($hand) >= 2) {
                            $fireTargets = array_merge(
                                ZoneSearch("myField", ["ALLY"], cardElements: ["FIRE"]),
                                ZoneSearch("myField", ["WEAPON"], cardElements: ["FIRE"]),
                                ZoneSearch("theirField", ["ALLY"], cardElements: ["FIRE"]),
                                ZoneSearch("theirField", ["WEAPON"], cardElements: ["FIRE"])
                            );
                            $fireTargets = FilterSpellshroudTargets($fireTargets);
                            if(!empty($fireTargets)) {
                                MZMove($playerID, $actionCard, "myBanish");
                                DecisionQueueController::CleanupRemovedCards();
                                DecisionQueueController::AddDecision($playerID, "CUSTOM", "ReserveCard", 100);
                                DecisionQueueController::AddDecision($playerID, "CUSTOM", "ReserveCard", 100);
                                DecisionQueueController::AddDecision($playerID, "CUSTOM", "EffectStackOpportunity", 100);
                                $targetStr = implode("&", $fireTargets);
                                DecisionQueueController::AddDecision($playerID, "MZCHOOSE", $targetStr, 1, tooltip:"Give_fire_unit_+2_POWER");
                                DecisionQueueController::AddDecision($playerID, "CUSTOM", "SwordSaintGY_Apply", 1);
                                return "PLAY";
                            }
                        }
                    }
                }
                // Seaside Rangefinder (5qyee9vkp8): [CB:Ranger][EB] (2), banish from GY → target unit becomes distant
                if($gyObj !== null && !$gyObj->removed && $gyObj->CardID === "5qyee9vkp8") {
                    if(IsClassBonusActive($playerID, ["RANGER"]) && IsElementBonusActive($playerID, "5qyee9vkp8")) {
                        $hand = &GetHand($playerID);
                        if(count($hand) >= 2) {
                            $units = array_merge(
                                ZoneSearch("myField", ["ALLY", "CHAMPION"]),
                                ZoneSearch("theirField", ["ALLY", "CHAMPION"])
                            );
                            $units = FilterSpellshroudTargets($units);
                            if(!empty($units)) {
                                MZMove($playerID, $actionCard, "myBanish");
                                DecisionQueueController::CleanupRemovedCards();
                                DecisionQueueController::AddDecision($playerID, "CUSTOM", "ReserveCard", 100);
                                DecisionQueueController::AddDecision($playerID, "CUSTOM", "ReserveCard", 100);
                                DecisionQueueController::AddDecision($playerID, "CUSTOM", "EffectStackOpportunity", 100);
                                $targetStr = implode("&", $units);
                                DecisionQueueController::AddDecision($playerID, "MZCHOOSE", $targetStr, 1, tooltip:"Target_unit_becomes_distant");
                                DecisionQueueController::AddDecision($playerID, "CUSTOM", "SeasideRangefinderGY_Apply", 1);
                                return "PLAY";
                            }
                        }
                    }
                }
                // Molten Arrow (mvfcd0ukk6): Banish 3 other fire GY cards → load from GY into unloaded Bow
                if($gyObj !== null && !$gyObj->removed && $gyObj->CardID === "mvfcd0ukk6") {
                    $fireGY = [];
                    $gy = GetZone("myGraveyard");
                    for($gi = 0; $gi < count($gy); ++$gi) {
                        if(!$gy[$gi]->removed && CardElement($gy[$gi]->CardID) === "FIRE"
                            && $gy[$gi]->CardID !== "mvfcd0ukk6") {
                            $fireGY[] = "myGraveyard-" . $gi;
                        }
                    }
                    $bows = GetUnloadedBows($playerID);
                    if(count($fireGY) >= 3 && !empty($bows)) {
                        DecisionQueueController::StoreVariable("MoltenArrowGYMZ", $actionCard);
                        DecisionQueueController::AddDecision($playerID, "MZCHOOSE", implode("&", $fireGY), 1, tooltip:"Banish_fire_card_1_of_3");
                        DecisionQueueController::AddDecision($playerID, "CUSTOM", "MoltenArrowGYBanish1", 1);
                        return "PLAY";
                    }
                }
            }
            // Generic Ephemerate: activate card from graveyard by paying ephemerate cost
            $gyObj = GetZoneObject($actionCard);
            if($gyObj !== null && !$gyObj->removed && CanPayEphemerate($playerID, $gyObj->CardID)) {
                global $ephemerateCards;
                $config = $ephemerateCards[$gyObj->CardID];
                $cost = GetEphemerateCost($playerID, $gyObj->CardID);
                // Move from graveyard to hand, then activate
                $handObj = MZMove($playerID, $actionCard, "myHand");
                $hand = &GetHand($playerID);
                $handIdx = count($hand) - 1;
                DecisionQueueController::StoreVariable("wasEphemerated", "YES");
                // Handle extra costs before normal DoActivateCard flow
                if(isset($config['extraCostHandler']) && $config['extraCostHandler'] === 'EphemerateBanishFloating') {
                    DecisionQueueController::StoreVariable("ephemerateCostOverride", "$cost");
                    DecisionQueueController::StoreVariable("ephemerateHandMZ", "myHand-" . $handIdx);
                    $floatingGY = [];
                    $gy = GetZone("myGraveyard");
                    for($gi = 0; $gi < count($gy); ++$gi) {
                        if(!$gy[$gi]->removed && HasFloatingMemory($gy[$gi])) {
                            $floatingGY[] = "myGraveyard-" . $gi;
                        }
                    }
                    DecisionQueueController::AddDecision($playerID, "MZCHOOSE", implode("&", $floatingGY), 1, tooltip:"Banish_a_card_with_floating_memory");
                    DecisionQueueController::AddDecision($playerID, "CUSTOM", "EphemerateBanishFloatingProcess", 1);
                } else {
                    ActivateCard($playerID, "myHand-" . $handIdx, false);
                }
                return "PLAY";
            }
            break;
        case "myBanish":
            // Naia, Diviner of Fortunes (jdmthh88rx): activate spell from banishment
            if($currentPhase == "MAIN" && $playerID == $turnPlayer) {
                $bObj = GetZoneObject($actionCard);
                if($bObj !== null && !$bObj->removed && in_array("NAIA_BANISHED", $bObj->TurnEffects)) {
                    // Check if Naia is still on the field
                    $naiaOnField = false;
                    $field = GetZone("myField");
                    foreach($field as $fObj) {
                        if(!$fObj->removed && $fObj->CardID === "jdmthh88rx" && !HasNoAbilities($fObj)) {
                            $naiaOnField = true;
                            break;
                        }
                    }
                    if($naiaOnField) {
                        // Move to hand and activate normally
                        $handObj = MZMove($playerID, $actionCard, "myHand");
                        $hand = &GetHand($playerID);
                        $handIdx = count($hand) - 1;
                        ActivateCard($playerID, "myHand-" . $handIdx, false);
                        return "PLAY";
                    }
                }
            }
            // Shattering Discharge (uutqo9hm33): activate from banishment if it has a charge counter
            if($currentPhase == "MAIN" && $playerID == $turnPlayer) {
                $bObj = GetZoneObject($actionCard);
                if($bObj !== null && !$bObj->removed && $bObj->CardID === "uutqo9hm33"
                    && GetCounterCount($bObj, "charge") > 0) {
                    RemoveCounters($playerID, $actionCard, "charge", 1);
                    $handObj = MZMove($playerID, $actionCard, "myHand");
                    $hand = &GetHand($playerID);
                    $handIdx = count($hand) - 1;
                    ActivateCard($playerID, "myHand-" . $handIdx, false);
                    return "PLAY";
                }
            }
            // Kongming, Erudite Strategist (0i139x5eub): may play banished card if SC faces matching direction
            if($currentPhase == "MAIN" && $playerID == $turnPlayer) {
                $bObj = GetZoneObject($actionCard);
                if($bObj !== null && !$bObj->removed && is_array($bObj->TurnEffects)) {
                    foreach(["NORTH", "EAST", "SOUTH", "WEST"] as $d) {
                        if(in_array("KONGMING_" . $d, $bObj->TurnEffects) && GetShiftingCurrents($playerID) === $d) {
                            $handObj = MZMove($playerID, $actionCard, "myHand");
                            $hand = &GetHand($playerID);
                            $handIdx = count($hand) - 1;
                            ActivateCard($playerID, "myHand-" . $handIdx, false);
                            return "PLAY";
                        }
                    }
                }
            }
            break;
        case "myMaterial":
            // Bagua of Vital Demise (imdj3c7oh0): may activate from material deck when SC faces West
            if($currentPhase == "MAIN" && $playerID == $turnPlayer) {
                $mObj = GetZoneObject($actionCard);
                if($mObj !== null && !$mObj->removed && $mObj->CardID === "imdj3c7oh0"
                    && GetShiftingCurrents($playerID) === "WEST") {
                    $handObj = MZMove($playerID, $actionCard, "myHand");
                    $hand = &GetHand($playerID);
                    $handIdx = count($hand) - 1;
                    ActivateCard($playerID, "myHand-" . $handIdx, false);
                    return "PLAY";
                }
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

    // Weapon Link pre-check: Sheath of Faceted Lapis (0cnn1eh85y) requires a Warrior weapon on field
    // Fang of Dragon's Breath (iebo5fu381) requires a Polearm weapon on field
    $hasWeaponLink = ($sourceObject->CardID === "0cnn1eh85y" || $sourceObject->CardID === "iebo5fu381");
    if($hasWeaponLink) {
        if($sourceObject->CardID === "iebo5fu381") {
            $weaponTargets = ZoneSearch("myField", ["WEAPON"], cardSubtypes: ["POLEARM"]);
        } else {
            $weaponTargets = ZoneSearch("myField", ["WEAPON"], cardSubtypes: ["WARRIOR"]);
        }
        if(empty($weaponTargets)) return; // No valid weapon — block activation
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

    // Primordial Ritual (4mcnqsm3n9): mandatory sacrifice of an ally you control
    if($sourceObject->CardID === "4mcnqsm3n9") {
        $allies = ZoneSearch("myField", ["ALLY"]);
        if(empty($allies)) return; // No allies to sacrifice — block activation
    }

    // Primeval Ritual (fan41iqm8b): mandatory sacrifice of an ally you control
    if($sourceObject->CardID === "fan41iqm8b") {
        $allies = ZoneSearch("myField", ["ALLY"]);
        if(empty($allies)) return; // No allies to sacrifice — block activation
    }

    // Turbo Charge (cnqsm3n9yv): mandatory sacrifice of a Powercell
    // Atmos Armor Type-Hermes (dlx7mdk0xh): mandatory sacrifice of a Powercell
    if($sourceObject->CardID === "cnqsm3n9yv" || $sourceObject->CardID === "dlx7mdk0xh") {
        $powercells = ZoneSearch("myField", cardSubtypes: ["POWERCELL"]);
        if(empty($powercells)) return;
    }

    // Overlord Mk III (sl7ddcgw05): mandatory sacrifice of 4 Powercells
    if($sourceObject->CardID === "sl7ddcgw05") {
        $powercells = ZoneSearch("myField", cardSubtypes: ["POWERCELL"]);
        if(count($powercells) < 4) return;
    }

    // Ravishing Finale (jlgx72rfgv): mandatory banish 2 floating memory from GY
    if($sourceObject->CardID === "jlgx72rfgv") {
        $floatingGY = [];
        $gy = GetZone("myGraveyard");
        for($gi = 0; $gi < count($gy); ++$gi) {
            if(!$gy[$gi]->removed && HasFloatingMemory($gy[$gi])) {
                $floatingGY[] = "myGraveyard-" . $gi;
            }
        }
        if(count($floatingGY) < 2) return;
    }

    // Kindling Flare (dcgw05qzza): needs at least one Herb to sacrifice
    if($sourceObject->CardID === "dcgw05qzza") {
        $herbs = ZoneSearch("myField", cardSubtypes: ["HERB"]);
        if(empty($herbs)) return;
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

    // Expunge (r73opcqtzs): mandatory discard of a Curse from any champion's lineage
    if($sourceObject->CardID === "r73opcqtzs") {
        $opp = ($player == 1) ? 2 : 1;
        if(CountCursesInLineage($player) + CountCursesInLineage($opp) == 0) return;
    }

    //1.1 Announcing Activation: First, the player announces the card they are activating and places it onto the effects stack.
    // Track the source zone so "whenever you activate from memory" triggers can check it in OnCardActivated.
    DecisionQueueController::StoreVariable("activationSourceZone", strtok($mzCard, "-"));
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

    // Ephemerate: override reserve cost with ephemerate cost
    $wasEphemerated = DecisionQueueController::GetVariable("wasEphemerated");
    if($wasEphemerated === "YES") {
        $reserveCost = GetEphemerateCost($player, $obj->CardID);
    }

    // Ghostsight Glass (cc0jmpmman): activated ability costs (3) reserve
    if($obj->CardID === "cc0jmpmman") $reserveCost = 3;

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

    // Strategem of Myriad Ice (id0ybub247): conditional efficiency when SC faces East
    if($obj->CardID === "id0ybub247" && GetShiftingCurrents($player) === "EAST") {
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

    // Exia Sight (1fy8l4pxs9): [Damage 20+] next card costs 1 less
    if(GlobalEffectCount($player, "1fy8l4pxs9_COST") > 0) {
        $reserveCost = max(0, $reserveCost - 1);
        RemoveGlobalEffect($player, "1fy8l4pxs9_COST");
    }

    // Summon Sentinels (5tlzsmw3rr): [Class Bonus] costs 1 less for each domain you control
    if($obj->CardID === "5tlzsmw3rr" && IsClassBonusActive($player, ["GUARDIAN"])) {
        $domainCount = count(ZoneSearch("myField", ["DOMAIN"]));
        $reserveCost = max(0, $reserveCost - $domainCount);
    }

    // Spectral Diffusion (lathqgiqgi): costs 1 less for each ephemeral object you control (up to 2)
    if($obj->CardID === "lathqgiqgi") {
        $ephCount = min(2, CountEphemeralObjects($player));
        $reserveCost = max(0, $reserveCost - $ephCount);
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

    // Celestial Calling (izm6h38lrj): [Class Bonus] costs 2 less
    if($obj->CardID === "izm6h38lrj" && IsClassBonusActive($player, ["CLERIC"])) {
        $reserveCost = max(0, $reserveCost - 2);
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

    // Glow Forth (27jlb9h1a5): costs 1 less for each Animal you control
    if($obj->CardID === "27jlb9h1a5") {
        $animalCount = count(ZoneSearch("myField", cardSubtypes: ["ANIMAL"]));
        $reserveCost = max(0, $reserveCost - $animalCount);
    }

    // Blade of Creation (iqs2hipwsc): [Class Bonus] costs 1 less per token object you control
    if($obj->CardID === "iqs2hipwsc" && IsClassBonusActive($player, ["GUARDIAN"])) {
        $tokenCount = count(ZoneSearch("myField", ["TOKEN"]));
        $reserveCost = max(0, $reserveCost - $tokenCount);
    }

    // Frigid Bash (k2c7wklzjm): costs 2 less if you control a Shield item
    if($obj->CardID === "k2c7wklzjm") {
        if(!empty(ZoneSearch("myField", ["ITEM"], cardSubtypes: ["SHIELD"]))) {
            $reserveCost = max(0, $reserveCost - 2);
        }
    }

    // Phalanx Captain (rPpLwLPGaL): [CB] costs 1 less per Human ally you control
    if($obj->CardID === "rPpLwLPGaL" && IsClassBonusActive($player, ["WARRIOR"])) {
        $humanAllies = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["HUMAN"]);
        $reserveCost = max(0, $reserveCost - count($humanAllies));
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

    // Tempest Downfall (4etkr73opc): [Class Bonus] costs 3 less if an ally has been suppressed this turn
    if($obj->CardID === "4etkr73opc" && IsClassBonusActive($player, ["MAGE"])) {
        $allyWasSuppressed = false;
        foreach(array_merge(GetZone("myBanish"), GetZone("theirBanish")) as $bObj) {
            if(!$bObj->removed && in_array("SUPPRESSED", $bObj->TurnEffects)) {
                $allyWasSuppressed = true;
                break;
            }
        }
        if($allyWasSuppressed) {
            $reserveCost = max(0, $reserveCost - 3);
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

    // Cinder Geyser (stiyh3pmk3): [Class Bonus] costs 2 less if opponent has 4+ cards in memory
    if($obj->CardID === "stiyh3pmk3" && IsClassBonusActive($player, ["CLERIC"])) {
        $oppMemory = ZoneSearch("theirMemory");
        if(count($oppMemory) >= 4) {
            $reserveCost = max(0, $reserveCost - 2);
        }
    }

    // Rescue the Heir (t0240ykvj0): [Level 1+] costs 1 less if you control a unique ally
    if($obj->CardID === "t0240ykvj0" && PlayerLevel($player) >= 1) {
        $myField = GetZone("myField");
        foreach($myField as $fObj) {
            if(!$fObj->removed && PropertyContains(EffectiveCardType($fObj), "ALLY") && PropertyContains(EffectiveCardType($fObj), "UNIQUE")) {
                $reserveCost = max(0, $reserveCost - 1);
                break;
            }
        }
    }

    // Mend Flesh (ju2d98w3j0): [Damage 25+] costs 2 less
    if($obj->CardID === "ju2d98w3j0") {
        $champ = ZoneSearch("myField", ["CHAMPION"]);
        if(!empty($champ)) {
            $champObj = GetZoneObject($champ[0]);
            if($champObj !== null && $champObj->Damage >= 25) {
                $reserveCost = max(0, $reserveCost - 2);
            }
        }
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

    // Xuchang, Frozen Citadel (xpb20rar4k): next card activated costs 2 more (one-shot)
    if(GlobalEffectCount($player, "XUCHANG_COST_INCREASE") > 0) {
        $reserveCost += 2;
        RemoveGlobalEffect($player, "XUCHANG_COST_INCREASE");
    }

    // Ceasing Edict (4f3bi5lohu): costs 2 less while Shifting Currents face South
    if($obj->CardID === "4f3bi5lohu" && GetShiftingCurrents($player) === "SOUTH") {
        $reserveCost = max(0, $reserveCost - 2);
    }

    // Rebounding Gust (9e0z7hb9id): costs 2 less while targeting an attacking ally (approximated: during combat)
    if($obj->CardID === "9e0z7hb9id" && IsCombatActive()) {
        $reserveCost = max(0, $reserveCost - 2);
    }

    // Enervating Decay (jh9s424gjr): [Class Bonus][Level 5+] costs 2 less
    if($obj->CardID === "jh9s424gjr" && IsClassBonusActive($player, ["CLERIC"]) && PlayerLevel($player) >= 5) {
        $reserveCost = max(0, $reserveCost - 2);
    }

    // Break Apart (4ns2jbt4hq): costs 2 more if targeting a regalia
    $hasBreakApartCost = false;
    if($obj->CardID === "4ns2jbt4hq") {
        $allBPTargets = array_merge(
            ZoneSearch("myField", ["ITEM"]), ZoneSearch("myField", ["WEAPON"]),
            ZoneSearch("theirField", ["ITEM"]), ZoneSearch("theirField", ["WEAPON"])
        );
        $bpRegaliaCount = 0;
        $bpNonRegaliaCount = 0;
        foreach($allBPTargets as $bpMZ) {
            $bpObj = GetZoneObject($bpMZ);
            if(PropertyContains(CardType($bpObj->CardID), "REGALIA")) {
                $bpRegaliaCount++;
            } else {
                $bpNonRegaliaCount++;
            }
        }
        if($bpRegaliaCount > 0 && $bpNonRegaliaCount == 0) {
            $reserveCost += 2;
            DecisionQueueController::StoreVariable("breakApartTargetRegalia", "YES");
        } else if($bpRegaliaCount > 0 && $bpNonRegaliaCount > 0) {
            $hasBreakApartCost = true;
            DecisionQueueController::AddDecision($player, "YESNO", "-", 100, tooltip:"Target_a_regalia?_(costs_2_more)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "BreakApartCostChoice|$reserveCost", 100);
        } else {
            DecisionQueueController::StoreVariable("breakApartTargetRegalia", "NO");
        }
    }

    // Coronation Ceremony (y4PZCiE26a): costs 2 less if it targets a unique ally
    $hasCoronationCost = false;
    if($obj->CardID === "y4PZCiE26a") {
        $allUnits = array_merge(
            ZoneSearch("myField", ["ALLY", "CHAMPION"]),
            ZoneSearch("theirField", ["ALLY", "CHAMPION"])
        );
        $allUnits = FilterSpellshroudTargets($allUnits);
        $uniqueAllyCount = 0;
        $nonUniqueCount = 0;
        foreach($allUnits as $uMZ) {
            $uObj = GetZoneObject($uMZ);
            if(PropertyContains(EffectiveCardType($uObj), "ALLY") && PropertyContains(EffectiveCardType($uObj), "UNIQUE")) {
                $uniqueAllyCount++;
            } else {
                $nonUniqueCount++;
            }
        }
        if($uniqueAllyCount > 0 && $nonUniqueCount == 0) {
            $reserveCost = max(0, $reserveCost - 2);
            DecisionQueueController::StoreVariable("coronationTargetUnique", "YES");
        } else if($uniqueAllyCount > 0 && $nonUniqueCount > 0) {
            $hasCoronationCost = true;
            DecisionQueueController::AddDecision($player, "YESNO", "-", 100, tooltip:"Target_a_unique_ally?_(costs_2_less)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "CoronationCeremonyChoice|$reserveCost", 100);
        } else {
            DecisionQueueController::StoreVariable("coronationTargetUnique", "NO");
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

    // Polkhawk, Bombastic Shot (ryvfq3huqj): Ranger Reaction cards cost 1 less
    if(PropertyContains(CardSubtypes($obj->CardID), "REACTION") && PropertyContains(CardClasses($obj->CardID), "RANGER")) {
        $myField = GetZone("myField");
        foreach($myField as $fObj) {
            if(!$fObj->removed && $fObj->CardID === "ryvfq3huqj" && !HasNoAbilities($fObj)) {
                $reserveCost = max(0, $reserveCost - 1);
                break;
            }
        }
    }

    // Umbral Tithe (2snsdwmxz1): costs 1 less for each Curse card in any champion's lineage
    if($obj->CardID === "2snsdwmxz1") {
        $opponent = ($player == 1) ? 2 : 1;
        $curseCount = CountCursesInLineage($player) + CountCursesInLineage($opponent);
        $reserveCost = max(0, $reserveCost - $curseCount);
    }

    // Debilitating Grasp (wbsmks4etk) Inherited Effect:
    // "The first card you activate each turn costs 1 more to activate."
    if(ChampionHasInLineage($player, "wbsmks4etk") && CardActivatedCallCount($player) == 0) {
        $reserveCost += 1;
    }

    // Keep of the Golden Sashes (gjhv2etytr): first card opponents activate each turn costs 1 more
    if($player !== $turnPlayer) {
        // This is the opponent activating — check if turn player controls Keep
        global $playerID;
        $keepZone = ($turnPlayer == $playerID) ? "myField" : "theirField";
        $keepField = GetZone($keepZone);
        foreach($keepField as $kObj) {
            if(!$kObj->removed && $kObj->CardID === "gjhv2etytr" && !HasNoAbilities($kObj)) {
                if(CardActivatedCallCount($player) == 0) {
                    $reserveCost += 1;
                }
                break;
            }
        }
    }

    // Calamity Cannon (lwabipl6gt): [Polkhawk Bonus] costs 3 less
    if($obj->CardID === "lwabipl6gt") {
        $myField = GetZone("myField");
        foreach($myField as $fObj) {
            if(!$fObj->removed && PropertyContains(EffectiveCardType($fObj), "CHAMPION")) {
                if(in_array($fObj->CardID, ["ryvfq3huqj", "8eyeqhc37y"])) {
                    $reserveCost = max(0, $reserveCost - 3);
                }
                break;
            }
        }
    }

    // Geldus, Terror of Dorumegia (n9yvn1uoy5): [Level 3+] costs 2 less
    if($obj->CardID === "n9yvn1uoy5" && PlayerLevel($player) >= 3) {
        $reserveCost = max(0, $reserveCost - 2);
    }

    // Thunderclap (0xm513tj3j): [Level 3+] costs 2 less
    if($obj->CardID === "0xm513tj3j" && PlayerLevel($player) >= 3) {
        $reserveCost = max(0, $reserveCost - 2);
    }

    // Bolster Ranks (n0esog2898): [Class Bonus] costs 1 less
    if($obj->CardID === "n0esog2898") {
        if(IsClassBonusActive($player, ["GUARDIAN"])) {
            $reserveCost = max(0, $reserveCost - 1);
        }
    }

    // Cavalier Rescue (75uhspxqme): Equestrian — costs 2 less if you control a Horse ally
    if($obj->CardID === "75uhspxqme") {
        if(!empty(ZoneSearch("myField", ["ALLY"], cardSubtypes: ["HORSE"]))) {
            $reserveCost = max(0, $reserveCost - 2);
        }
    }

    // Determined Spearman (c8z5ntioqs): Equestrian — costs 1 less while you control a Horse ally
    if($obj->CardID === "c8z5ntioqs") {
        if(!empty(ZoneSearch("myField", ["ALLY"], cardSubtypes: ["HORSE"]))) {
            $reserveCost = max(0, $reserveCost - 1);
        }
    }

    // Shu Frontliner (uhaao91ee1): Equestrian — costs 1 less while you control a Horse ally
    if($obj->CardID === "uhaao91ee1") {
        if(!empty(ZoneSearch("myField", ["ALLY"], cardSubtypes: ["HORSE"]))) {
            $reserveCost = max(0, $reserveCost - 1);
        }
    }

    // Cao Cao, Aspirant of Chaos (d5og6z31q9): Equestrian — costs 3 less while you control a Horse ally
    if($obj->CardID === "d5og6z31q9") {
        if(!empty(ZoneSearch("myField", ["ALLY"], cardSubtypes: ["HORSE"]))) {
            $reserveCost = max(0, $reserveCost - 3);
        }
    }

    // Hire Mercenaries (8swok9u930): costs 1 less if opponent controls 1+ allies
    if($obj->CardID === "8swok9u930") {
        if(!empty(ZoneSearch("theirField", ["ALLY"]))) {
            $reserveCost = max(0, $reserveCost - 1);
        }
    }

    // Lost Wisdom (8codb9zatv): [Class Bonus] costs 2 less if you control 1+ unique allies
    if($obj->CardID === "8codb9zatv" && IsClassBonusActive($player, ["CLERIC", "MAGE"])) {
        $myField = GetZone("myField");
        foreach($myField as $fObj) {
            if(!$fObj->removed && PropertyContains(EffectiveCardType($fObj), "ALLY") && PropertyContains(EffectiveCardType($fObj), "UNIQUE")) {
                $reserveCost = max(0, $reserveCost - 2);
                break;
            }
        }
    }

    // Paired Minds, Kindred Souls (7qjnqww067): global effect — next Horse ally costs 2 less
    if(GlobalEffectCount($player, "7qjnqww067") > 0) {
        if(PropertyContains(CardType($obj->CardID), "ALLY") && PropertyContains(CardSubtypes($obj->CardID), "HORSE")) {
            $reserveCost = max(0, $reserveCost - 2);
            RemoveGlobalEffect($player, "7qjnqww067");
        }
    }

    // Crimson Prescience (0dsdojl6l3): [Class Bonus][Damage 25+] costs 1 less
    if($obj->CardID === "0dsdojl6l3" && IsClassBonusActive($player, ["WARRIOR"])) {
        $champ = ZoneSearch("myField", ["CHAMPION"]);
        if(!empty($champ)) {
            $champObj = GetZoneObject($champ[0]);
            if($champObj !== null && $champObj->Damage >= 25) {
                $reserveCost = max(0, $reserveCost - 1);
            }
        }
    }

    // Guan Yu, Prime Exemplar (0oyxjld8jh): costs 2 less if a Human ally you controlled died this turn
    if($obj->CardID === "0oyxjld8jh") {
        $deadAllies = AllyDestroyedTurnCards($player);
        foreach($deadAllies as $deadCardID => $deadCount) {
            if(PropertyContains(CardSubtypes($deadCardID), "HUMAN")) {
                $reserveCost = max(0, $reserveCost - 2);
                break;
            }
        }
    }

    // Acolyte of Cultivation (nsowyyn6jt): [CB:Cleric/Mage] costs 3 less if a Spell was activated this turn
    if($obj->CardID === "nsowyyn6jt" && IsClassBonusActive($player, ["CLERIC", "MAGE"])) {
        $activatedThisTurn = CardActivatedTurnCards($player);
        foreach($activatedThisTurn as $actCardID => $actCount) {
            if(PropertyContains(CardSubtypes($actCardID), "SPELL")) {
                $reserveCost = max(0, $reserveCost - 3);
                break;
            }
        }
    }

    // Modulating Cadence (p5p0azskw4): [CB] costs 1 less for each Animal ally you control
    if($obj->CardID === "p5p0azskw4" && IsClassBonusActive($player, ["TAMER"])) {
        $animalCount = count(ZoneSearch("myField", ["ALLY"], cardSubtypes: ["ANIMAL"]));
        $reserveCost = max(0, $reserveCost - $animalCount);
    }

    // Maiden of Glimmer's Dusk (qa4ke7txh0): [CB] costs 1 less per phantasia you control, up to 2
    if($obj->CardID === "qa4ke7txh0" && IsClassBonusActive($player, ["CLERIC"])) {
        $phantasiaCount = min(2, count(ZoneSearch("myField", ["PHANTASIA"])));
        $reserveCost = max(0, $reserveCost - $phantasiaCount);
    }

    // Mana Resonance (qp65vbdw7c): [CB] costs X less, where X is highest reserve cost among
    // Spell cards your opponents control on the effects stack
    if($obj->CardID === "qp65vbdw7c" && IsClassBonusActive($player, ["CLERIC"])) {
        $es = GetZone("EffectStack");
        $highestCost = 0;
        foreach($es as $esObj) {
            if($esObj->removed) continue;
            if($esObj->Controller == $player) continue; // only opponent's spells
            if(PropertyContains(CardSubtypes($esObj->CardID), "SPELL")) {
                $cost = intval(CardReserveCost($esObj->CardID));
                if($cost > $highestCost) $highestCost = $cost;
            }
        }
        $reserveCost = max(0, $reserveCost - $highestCost);
    }

    // Mana Resonance: store final reserve cost for ability resolution
    if($obj->CardID === "qp65vbdw7c") {
        DecisionQueueController::StoreVariable("manaResonanceReservePaid", strval($reserveCost));
    }

    // Disciple of the Waves (m9sfzj5d1i): Deluge 3 — costs 1 less
    if($obj->CardID === "m9sfzj5d1i" && DelugeAmount($player) >= 3) {
        $reserveCost = max(0, $reserveCost - 1);
    }

    // Sleety Retreat (j9fkuzgg9i): Deluge 4 — costs 2 less
    if($obj->CardID === "j9fkuzgg9i" && DelugeAmount($player) >= 4) {
        $reserveCost = max(0, $reserveCost - 2);
    }

    // 1.5 Declaring Targets — Ally Link: prompt the player to choose a target ally
    if($hasAllyLink) {
        $allyTargets = ZoneSearch("myField", ["ALLY"]);
        $allyChoices = implode("&", $allyTargets);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $allyChoices, 100, tooltip:"Choose_ally_to_link");
        DecisionQueueController::AddDecision($player, "CUSTOM", "DeclareAllyLinkTarget", 100);
    }

    // Weapon Link: prompt the player to choose a target weapon
    if($hasWeaponLink) {
        if($sourceObject->CardID === "iebo5fu381") {
            $weaponTargets = ZoneSearch("myField", ["WEAPON"], cardSubtypes: ["POLEARM"]);
        } else {
            $weaponTargets = ZoneSearch("myField", ["WEAPON"], cardSubtypes: ["WARRIOR"]);
        }
        $weaponChoices = implode("&", $weaponTargets);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $weaponChoices, 100, tooltip:"Choose_weapon_to_link");
        DecisionQueueController::AddDecision($player, "CUSTOM", "DeclareWeaponLinkTarget", 100);
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

    //1.3 Declaring Costs — Intervention (vmqe225jkb): [CB] may rest champion to pay for 2 reserve
    $hasInterventionCost = false;
    if($obj->CardID === "vmqe225jkb" && IsClassBonusActive($player, ["WARRIOR"])) {
        $champField = GetZone("myField");
        foreach($champField as $fObj) {
            if(!$fObj->removed && PropertyContains(EffectiveCardType($fObj), "CHAMPION")
               && $fObj->Controller == $player && isset($fObj->Status) && $fObj->Status == 2) {
                // Champion is awake — offer to rest it to reduce reserve cost by 2
                $hasInterventionCost = true;
                DecisionQueueController::AddDecision($player, "YESNO", "-", 100, tooltip:"Rest_champion_to_pay_2_of_reserve_cost?");
                DecisionQueueController::AddDecision($player, "CUSTOM", "InterventionRestCost|" . $reserveCost, 100);
                break;
            }
        }
    }

    //1.3 Declaring Costs — Ravishing Finale (jlgx72rfgv): mandatory banish 2 floating memory from GY
    $hasRavishingFinaleCost = false;
    if($obj->CardID === "jlgx72rfgv") {
        $hasRavishingFinaleCost = true;
        $floatingGY = [];
        $gy = GetZone("myGraveyard");
        for($gi = 0; $gi < count($gy); ++$gi) {
            if(!$gy[$gi]->removed && HasFloatingMemory($gy[$gi])) {
                $floatingGY[] = "myGraveyard-" . $gi;
            }
        }
        $floatingStr = implode("&", $floatingGY);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $floatingStr, 100, tooltip:"Banish_floating-memory_card_(1_of_2)");
        DecisionQueueController::AddDecision($player, "CUSTOM", "RavishingFinaleBanish1|$reserveCost", 100);
    }

    //1.3 Declaring Costs — Primordial Ritual (4mcnqsm3n9): mandatory sacrifice of an ally
    if($obj->CardID === "4mcnqsm3n9") {
        $allies = ZoneSearch("myField", ["ALLY"]);
        if(!empty($allies)) {
            $allyChoices = implode("&", $allies);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $allyChoices, 100, tooltip:"Sacrifice_an_ally");
            DecisionQueueController::AddDecision($player, "CUSTOM", "PrimordialRitualSacrifice", 100);
        }
    }

    //1.3 Declaring Costs — Primeval Ritual (fan41iqm8b): mandatory sacrifice of an ally
    if($obj->CardID === "fan41iqm8b") {
        $allies = ZoneSearch("myField", ["ALLY"]);
        if(!empty($allies)) {
            $allyChoices = implode("&", $allies);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $allyChoices, 100, tooltip:"Sacrifice_an_ally");
            DecisionQueueController::AddDecision($player, "CUSTOM", "PrimordialRitualSacrifice", 100);
        }
    }

    //1.3 Declaring Costs — Turbo Charge (cnqsm3n9yv) / Atmos Armor Type-Hermes (dlx7mdk0xh): sacrifice a Powercell
    if($obj->CardID === "cnqsm3n9yv" || $obj->CardID === "dlx7mdk0xh") {
        $powercells = ZoneSearch("myField", cardSubtypes: ["POWERCELL"]);
        if(!empty($powercells)) {
            $pcChoices = implode("&", $powercells);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $pcChoices, 100, tooltip:"Sacrifice_a_Powercell");
            DecisionQueueController::AddDecision($player, "CUSTOM", "PowercellSacrifice", 100);
        }
    }

    //1.3 Declaring Costs — Overlord Mk III (sl7ddcgw05): sacrifice 4 Powercells
    if($obj->CardID === "sl7ddcgw05") {
        $powercells = ZoneSearch("myField", cardSubtypes: ["POWERCELL"]);
        if(count($powercells) >= 4) {
            $pcChoices = implode("&", $powercells);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $pcChoices, 100, tooltip:"Sacrifice_a_Powercell_(1_of_4)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "OverlordSacrifice|3", 100);
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

    //1.3 Declaring Costs — Scry the Stars (oz23yfzk96): [CB] may banish Scry the Skies from GY instead of reserve
    $hasScryAltCost = false;
    if($obj->CardID === "oz23yfzk96" && IsClassBonusActive($player, ["CLERIC"])) {
        $hasScryTheSkies = false;
        $gy = GetZone("myGraveyard");
        for($gi = 0; $gi < count($gy); ++$gi) {
            if(!$gy[$gi]->removed && $gy[$gi]->CardID === "F9POfB5Nah") {
                $hasScryTheSkies = true;
                break;
            }
        }
        if($hasScryTheSkies && $reserveCost > 0) {
            $hasScryAltCost = true;
            DecisionQueueController::AddDecision($player, "YESNO", "-", 100, tooltip:"Banish_Scry_the_Skies_from_GY_instead_of_reserve?");
            DecisionQueueController::AddDecision($player, "CUSTOM",
                "ScryTheStarsAltCost|" . $reserveCost, 100);
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

    //1.3 Declaring Costs — Kindling Flare (dcgw05qzza): sacrifice any amount of Herbs
    $hasKindlingFlareCost = false;
    if($obj->CardID === "dcgw05qzza") {
        $hasKindlingFlareCost = true;
        $herbs = ZoneSearch("myField", cardSubtypes: ["HERB"]);
        DecisionQueueController::StoreVariable("kindlingHerbCount", "0");
        DecisionQueueController::StoreVariable("additionalCostPaid", "NO");
        if(!empty($herbs)) {
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $herbs), 100, tooltip:"Sacrifice_an_Herb?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "KindlingFlareSacHerb|$reserveCost", 100);
        } else {
            for($i = 0; $i < $reserveCost; ++$i) {
                DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
            }
            DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
        }
    }

    //1.3 Declaring Costs — Brew: may sacrifice herbs instead of paying reserve
    global $brewCosts;
    $hasBrewAltCost = false;
    if(isset($brewCosts[$obj->CardID])) {
        $slots = $brewCosts[$obj->CardID];
        if(CanPayBrewCost($player, $slots)) {
            $hasBrewAltCost = true;
            DecisionQueueController::AddDecision($player, "YESNO", "-", 100, tooltip:"Brew_(sacrifice_herbs_instead_of_reserve)?");
            DecisionQueueController::AddDecision($player, "CUSTOM",
                "DeclareBrew|" . $obj->CardID . "|" . $reserveCost, 100);
        }
    }

    //1.3 Declaring Costs — Expunge (r73opcqtzs): mandatory discard of a Curse from any champion's lineage
    $hasExpungeCost = false;
    if($obj->CardID === "r73opcqtzs") {
        $hasExpungeCost = true;
        $championsWithCurses = [];
        foreach([1, 2] as $p) {
            global $playerID;
            $fz = $p == $playerID ? "myField" : "theirField";
            $fld = GetZone($fz);
            for($fi = 0; $fi < count($fld); ++$fi) {
                if($fld[$fi]->removed) continue;
                if(!PropertyContains(EffectiveCardType($fld[$fi]), "CHAMPION")) continue;
                if(!is_array($fld[$fi]->Subcards)) continue;
                foreach($fld[$fi]->Subcards as $sc) {
                    if(PropertyContains(CardSubtypes($sc), "CURSE")) {
                        $championsWithCurses[] = $fz . "-" . $fi;
                        break;
                    }
                }
            }
        }
        if(count($championsWithCurses) == 1) {
            DecisionQueueController::StoreVariable("expungeChampion", $championsWithCurses[0]);
            DecisionQueueController::AddDecision($player, "CUSTOM", "ExpungePickCurse|$reserveCost", 100);
        } else {
            $champStr = implode("&", $championsWithCurses);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $champStr, 100, tooltip:"Choose_champion_for_Curse_discard");
            DecisionQueueController::AddDecision($player, "CUSTOM", "ExpungeChosenChampion|$reserveCost", 100);
        }
    }

    if(!$hasAdditionalCost && !$hasSongOfFrostAltCost && !$hasBrewAltCost && !$hasScryAltCost && !$hasKindlingFlareCost && !$hasRavishingFinaleCost && !$hasExpungeCost && !$hasInterventionCost && !$hasBreakApartCost && !$hasCoronationCost) {
        // No additional cost — store default and queue normal reserve + opportunity
        DecisionQueueController::StoreVariable("additionalCostPaid", "NO");

        // Kindle: check if card has Kindle N and class bonus is active
        global $Kindle_Cards;
        $hasKindle = isset($Kindle_Cards[$obj->CardID]) && !$ignoreCost && $reserveCost > 0
            && IsClassBonusActive($player, CardClasses($obj->CardID));
        if($hasKindle) {
            $kindleN = $Kindle_Cards[$obj->CardID];
            $fireGY = [];
            $gy = GetZone("myGraveyard");
            for($gi = 0; $gi < count($gy); ++$gi) {
                if(!$gy[$gi]->removed && CardElement($gy[$gi]->CardID) === "FIRE") {
                    $fireGY[] = "myGraveyard-" . $gi;
                }
            }
            $maxKindle = min($kindleN, count($fireGY), $reserveCost);
            if($maxKindle > 0) {
                DecisionQueueController::StoreVariable("kindleMax", "$maxKindle");
                DecisionQueueController::StoreVariable("kindleBanished", "0");
                DecisionQueueController::StoreVariable("kindleReserveCost", "$reserveCost");
                DecisionQueueController::AddDecision($player, "CUSTOM", "KindleChoose", 100);
                // KindleChoose will handle queuing remaining ReserveCard + EffectStackOpportunity
            } else {
                $hasKindle = false; // No valid fire GY cards to kindle
            }
        }

        if(!$hasKindle) {
            // Imbue: snapshot memory before reserve payment so we can count element-matching additions
            global $Imbue_Cards;
        $hasImbue = isset($Imbue_Cards[$obj->CardID]);
        if($hasImbue) {
            $memoryBefore = count(GetZone("myMemory"));
            $imbueElement = CardElement($obj->CardID);
            $imbueThreshold = $Imbue_Cards[$obj->CardID];
            DecisionQueueController::StoreVariable("imbueMemoryBefore", "$memoryBefore");
            DecisionQueueController::StoreVariable("imbueElement", $imbueElement);
            DecisionQueueController::StoreVariable("imbueThreshold", "$imbueThreshold");
        }

        //1.8 Paying Costs
        if(!$ignoreCost) {
            for($i = 0; $i < $reserveCost; ++$i) {
                DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
            }
        }

        // Imbue: after reserve payment, evaluate whether the card is imbued
        if($hasImbue) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "CheckImbue", 100);
        } else {
            DecisionQueueController::StoreVariable("isImbued", "NO");
        }

        //1.9 Activation — grant Opportunity to the opponent before resolving
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
        } // end if(!$hasKindle)
    }
    // When $hasAdditionalCost is true, the DeclareAdditionalCost handler takes over
    // queuing reserve payments and EffectStackOpportunity after the player answers.
    // When $hasSongOfFrostAltCost is true, SongOfFrostAltCost handler queues its own
    // reserve/banish + EffectStackOpportunity.
    // When $hasBrewAltCost is true, DeclareBrew handler queues herb sacrifice or
    // normal reserve + EffectStackOpportunity.
    // When $hasScryAltCost is true, ScryTheStarsAltCost handler queues banish or
    // normal reserve + EffectStackOpportunity.
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

// Ephemerate extra cost: banish a card with floating memory from graveyard, then activate
$customDQHandlers["EphemerateBanishFloatingProcess"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "PASS" || empty($lastDecision)) return;
    MZMove($player, $lastDecision, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    $handMZ = DecisionQueueController::GetVariable("ephemerateHandMZ");
    // Re-locate the card in hand (index may have shifted)
    $hand = &GetHand($player);
    $handIdx = count($hand) - 1;
    ActivateCard($player, "myHand-" . $handIdx, false);
};

// Shackled Theurgist (vkqzk1jik7): On Death DQ handlers
$customDQHandlers["ShackledTheurgistChoice"] = function($player, $params, $lastDecision) {
    $controller = intval($params[0]);
    $controllerFieldZone = $params[1];
    $oppFieldZone = $params[2];
    if($lastDecision === "YES") {
        $allies = ZoneSearch($oppFieldZone, ["ALLY"]);
        if(!empty($allies)) {
            $allyStr = implode("&", $allies);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $allyStr, 1, tooltip:"Choose_an_ally_to_sacrifice");
            DecisionQueueController::AddDecision($player, "CUSTOM", "ShackledTheurgistSacrifice", 1);
        }
    } else {
        ShackledTheurgistReturn($controller, $controllerFieldZone);
    }
};

$customDQHandlers["ShackledTheurgistSacrifice"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    DoAllyDestroyed($player, $lastDecision);
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
 * DQ handler: after reserve payment completes for an Imbue card, count how many
 * of the newly added memory cards match the card's element. Stores "isImbued"
 * as "YES" or "NO" for ability code to read at resolution time.
 */
$customDQHandlers["CheckImbue"] = function($player, $parts, $lastDecision) {
    $memoryBefore = intval(DecisionQueueController::GetVariable("imbueMemoryBefore"));
    $element = DecisionQueueController::GetVariable("imbueElement");
    $threshold = intval(DecisionQueueController::GetVariable("imbueThreshold"));

    $memory = GetZone("myMemory");
    $elementMatches = 0;
    // Count element-matching cards among the newly added memory entries
    for($i = $memoryBefore; $i < count($memory); ++$i) {
        if(!$memory[$i]->removed && CardElement($memory[$i]->CardID) === $element) {
            $elementMatches++;
        }
    }
    DecisionQueueController::StoreVariable("isImbued", $elementMatches >= $threshold ? "YES" : "NO");
};

// --- Kindle DQ Handlers ---
// KindleChoose: Present fire GY cards for optional banish. Repeats up to kindleMax times.
$customDQHandlers["KindleChoose"] = function($player, $parts, $lastDecision) {
    $maxKindle = intval(DecisionQueueController::GetVariable("kindleMax"));
    $banished = intval(DecisionQueueController::GetVariable("kindleBanished"));
    $reserveCost = intval(DecisionQueueController::GetVariable("kindleReserveCost"));

    $remaining = $maxKindle - $banished;
    $fireGY = [];
    if($remaining > 0) {
        $gy = GetZone("myGraveyard");
        for($gi = 0; $gi < count($gy); ++$gi) {
            if(!$gy[$gi]->removed && CardElement($gy[$gi]->CardID) === "FIRE") {
                $fireGY[] = "myGraveyard-" . $gi;
            }
        }
    }

    if($remaining > 0 && !empty($fireGY) && $reserveCost - $banished > 0) {
        $kindleLeft = $remaining;
        $tooltip = "Kindle:_banish_fire_card_from_GY_to_reduce_cost?_(" . $kindleLeft . "_remaining)";
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $fireGY), 100, tooltip:$tooltip);
        DecisionQueueController::AddDecision($player, "CUSTOM", "KindleProcess", 100, "", 1);
    } else {
        // Done kindling — queue remaining reserve costs + opportunity
        $remainingReserve = max(0, $reserveCost - $banished);
        for($i = 0; $i < $remainingReserve; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
        DecisionQueueController::StoreVariable("isImbued", "NO");
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
    }
};

// KindleProcess: Handle the player's response from KindleChoose.
$customDQHandlers["KindleProcess"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        // Player chose a fire GY card to banish
        MZMove($player, $lastDecision, "myBanish");
        $banished = intval(DecisionQueueController::GetVariable("kindleBanished")) + 1;
        DecisionQueueController::StoreVariable("kindleBanished", "$banished");
        DecisionQueueController::AddDecision($player, "CUSTOM", "KindleChoose", 100);
    } else {
        // Player passed — done kindling, queue remaining reserves
        $reserveCost = intval(DecisionQueueController::GetVariable("kindleReserveCost"));
        $banished = intval(DecisionQueueController::GetVariable("kindleBanished"));
        $remainingReserve = max(0, $reserveCost - $banished);
        for($i = 0; $i < $remainingReserve; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
        DecisionQueueController::StoreVariable("isImbued", "NO");
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
    }
};

// Break Apart (4ns2jbt4hq): DQ handler for the YESNO choice of whether to target a regalia
$customDQHandlers["BreakApartCostChoice"] = function($player, $parts, $lastDecision) {
    $baseReserve = intval($parts[0]);
    DecisionQueueController::StoreVariable("breakApartTargetRegalia", $lastDecision);
    $totalCost = $baseReserve;
    if($lastDecision === "YES") {
        $totalCost += 2;
    }
    DecisionQueueController::StoreVariable("additionalCostPaid", "NO");
    for($i = 0; $i < $totalCost; ++$i) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
    }
    DecisionQueueController::StoreVariable("isImbued", "NO");
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
};

// Coronation Ceremony (y4PZCiE26a): YesNo cost choice handler
$customDQHandlers["CoronationCeremonyChoice"] = function($player, $parts, $lastDecision) {
    $baseReserve = intval($parts[0]);
    DecisionQueueController::StoreVariable("coronationTargetUnique", $lastDecision);
    $totalCost = $baseReserve;
    if($lastDecision === "YES") {
        $totalCost = max(0, $totalCost - 2);
    }
    DecisionQueueController::StoreVariable("additionalCostPaid", "NO");
    for($i = 0; $i < $totalCost; ++$i) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
    }
    DecisionQueueController::StoreVariable("isImbued", "NO");
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
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
 * DQ handler: Intervention (vmqe225jkb) — if YES, rest champion and reduce reserve cost by 2.
 * Parts: [baseReserve].
 */
$customDQHandlers["InterventionRestCost"] = function($player, $parts, $lastDecision) {
    $baseReserve = intval($parts[0]);
    if($lastDecision === "YES") {
        // Rest the champion as payment
        $champField = GetZone("myField");
        for($fi = 0; $fi < count($champField); ++$fi) {
            if(!$champField[$fi]->removed && PropertyContains(EffectiveCardType($champField[$fi]), "CHAMPION")
               && $champField[$fi]->Controller == $player) {
                ExhaustCard($player, "myField-" . $fi);
                break;
            }
        }
        $totalCost = max(0, $baseReserve - 2);
    } else {
        $totalCost = $baseReserve;
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

/**
 * Hook fired by MZMove whenever a card moves from any memory zone to banishment.
 * Called after the card has arrived in banishment.
 * @param int    $player    The nominal player (1 or 2).
 * @param string $cardID    The card ID that was moved.
 * @param object $newObj    The new banishment zone object.
 */
function OnBanishedFromMemory($player, $cardID, $newObj) {
    // Shattering Discharge (uutqo9hm33): [CB] whenever banished from memory, put a charge counter on it in banishment.
    if($cardID === "uutqo9hm33" && IsClassBonusActive($player, ["MAGE"])) {
        $banish = GetBanish($player);
        for($i = count($banish) - 1; $i >= 0; --$i) {
            if(!$banish[$i]->removed && $banish[$i]->CardID === "uutqo9hm33") {
                AddCounters($player, "myBanish-" . $i, "charge", 1);
                break;
            }
        }
    }
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
        // Weapon Link fizzle check: validate the weapon link target
        if($obj->CardID === "0cnn1eh85y" || $obj->CardID === "iebo5fu381") {
            $wlTargetMZ = DecisionQueueController::GetVariable("weaponLinkTargetMZ");
            $wlTargetCardID = DecisionQueueController::GetVariable("weaponLinkTargetCardID");
            $wlTargetObj = (!empty($wlTargetMZ) && $wlTargetMZ !== "-") ? GetZoneObject($wlTargetMZ) : null;
            $wlTargetValid = ($wlTargetObj !== null && !$wlTargetObj->removed
                && $wlTargetObj->CardID === $wlTargetCardID
                && PropertyContains(CardType($wlTargetObj->CardID), "WEAPON"));
            if(!$wlTargetValid && !empty($wlTargetCardID)) {
                $field = GetZone("myField");
                $wlTargetValid = false;
                for($fi = 0; $fi < count($field); $fi++) {
                    if(!$field[$fi]->removed && $field[$fi]->CardID === $wlTargetCardID
                        && PropertyContains(CardType($field[$fi]->CardID), "WEAPON")) {
                        DecisionQueueController::StoreVariable("weaponLinkTargetMZ", "myField-" . $fi);
                        $wlTargetValid = true;
                        break;
                    }
                }
            }
            if(!$wlTargetValid) {
                $obj = MZMove($player, $mzCard, "myGraveyard");
                DecisionQueueController::StoreVariable("weaponLinkTargetMZ", "");
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
    } else if(PropertyContains($cardType, "ITEM")) {
        // Items (e.g. Potions, Accessories) enter the field as persistent objects
        $obj = MZMove($player, $mzCard, "myField");
        $obj->Controller = $player;
    }  else if(PropertyContains($cardType, "ACTION")) {
        // Ephemerate: ephemeral actions are banished on resolve
        $wasEphemerationAction = DecisionQueueController::GetVariable("wasEphemerated");
        // Frost Shard (jnsl7ddcgw): banish on resolve when activated from graveyard
        global $gyActivatedCardID, $Preserve_Cards;
        if($wasEphemerationAction === "YES") {
            $obj = MZMove($player, $mzCard, "myBanish");
        } else if(isset($gyActivatedCardID) && $gyActivatedCardID === $obj->CardID) {
            $obj = MZMove($player, $mzCard, "myBanish");
            $gyActivatedCardID = null;
        // Special case: Preserve cards go to Material zone
        } else if(isset($Preserve_Cards[$obj->CardID])) {
            $obj = MZMove($player, $mzCard, "myMaterial");
        } else if(HasFloatingMemory($obj) && IsBrackishLutistOnField()) {
            // Brackish Lutist (1clswn3ba2): floating memory → banish instead of graveyard
            $obj = MZMove($player, $mzCard, "myBanish");
        } else if($obj->CardID === "imdj3c7oh0" && GetShiftingCurrents($player) === "EAST") {
            // Bagua of Vital Demise: if SC faces East, return to material deck preserved
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
    // Ephemerate: tag field objects as ephemeral when activated via Ephemerate
    $wasEph = DecisionQueueController::GetVariable("wasEphemerated");
    if($wasEph === "YES" && !PropertyContains($cardType, "ACTION") && !PropertyContains($cardType, "ATTACK")) {
        $field = &GetField($player);
        $fieldIdx = count($field) - 1;
        if($fieldIdx >= 0 && !$field[$fieldIdx]->removed) {
            MakeEphemeral("myField-" . $fieldIdx);
        }
    }
    // Clear ephemerate flag so it doesn't leak into subsequent activations
    if($wasEph === "YES") {
        DecisionQueueController::StoreVariable("wasEphemerated", "NO");
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
            case "f28y5rn0dt": // Sly Songstress: whenever you activate a Harmony or Melody, may discard→draw
                if((PropertyContains($subtypes, "HARMONY") || PropertyContains($subtypes, "MELODY"))
                    && !HasNoAbilities($field[$fi])) {
                    $hand = &GetHand($player);
                    if(count($hand) > 0) {
                        DecisionQueueController::AddDecision($player, "YESNO", "-", 1, "Discard_a_card_to_draw_a_card?(Sly_Songstress)");
                        DecisionQueueController::AddDecision($player, "CUSTOM", "SlySongstressDiscard|" . $fi, 1);
                    }
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
            case "0yetaebjlw": // Lunar Conduit: whenever you activate an astra element card, put a charge counter
                if($activatedElement === "ASTRA" && !HasNoAbilities($field[$fi])) {
                    AddCounters($player, "myField-" . $fi, "charge", 1);
                }
                break;
            case "u6o6eanbrf": // Imperial Apprentice: whenever you activate a Spell card,
                // you may banish a floating memory card from GY to draw a card
                if(PropertyContains($subtypes, "SPELL") && !HasNoAbilities($field[$fi])) {
                    $floatingGY = [];
                    $gy = GetZone("myGraveyard");
                    for($gi = 0; $gi < count($gy); ++$gi) {
                        if(!$gy[$gi]->removed && HasFloatingMemory($gy[$gi])) {
                            $floatingGY[] = "myGraveyard-" . $gi;
                        }
                    }
                    if(!empty($floatingGY)) {
                        $choices = implode("&", $floatingGY);
                        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $choices, 1, "Banish_floating_memory_to_draw?");
                        DecisionQueueController::AddDecision($player, "CUSTOM", "ImperialApprenticeFloating", 1);
                    }
                }
                break;
            case "q2svdv3zb9": // Clockwork Musicbox: [CB] banish Harmony/Melody as it resolves
                if((PropertyContains($subtypes, "MELODY") || PropertyContains($subtypes, "HARMONY"))
                    && !HasNoAbilities($field[$fi])
                    && IsClassBonusActive($player, ["TAMER"])) {
                    DecisionQueueController::StoreVariable("MusicboxBanishCardID", $obj->CardID);
                    DecisionQueueController::StoreVariable("MusicboxFieldIdx", strval($fi));
                    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, "Banish_via_Clockwork_Musicbox?");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "MusicboxBanish", 1);
                }
                break;
            case "1m48260b7b": // Razorgale Calling: whenever you activate a wind element card, deal 1 damage to target champion
                if($activatedElement === "WIND" && !HasNoAbilities($field[$fi])) {
                    DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
                        tooltip:"Target_your_champion?_(No=opponent)");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "RazorgaleCallingDamage", 1);
                }
                break;
            case "9f0nsj62l6": // Apprentice Aeromancer: [CB] whenever you activate a wind Spell, +1 POWER until EOT
                if($activatedElement === "WIND" && PropertyContains($subtypes, "SPELL")
                    && !HasNoAbilities($field[$fi]) && IsClassBonusActive($player, ["CLERIC", "MAGE"])) {
                    AddTurnEffect("myField-" . $fi, "9f0nsj62l6-POWER");
                }
                break;
            case "aws20fsihd": // Fervent Lancer: whenever you activate an exia element card, may banish it as it resolves
                if($activatedElement === "EXIA" && !HasNoAbilities($field[$fi])) {
                    DecisionQueueController::StoreVariable("FerventLancerIdx", strval($fi));
                    DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
                        tooltip:"Banish_this_card_via_Fervent_Lancer?");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "FerventLancerBanish", 1);
                }
                break;
            case "r44lyrzo6o": // Sword Saint's Vow: [CB] whenever you activate a Craft action, add 2 durability
                if(PropertyContains($cardType, "ACTION") && PropertyContains($subtypes, "CRAFT")
                    && !HasNoAbilities($field[$fi]) && IsClassBonusActive($player, ["WARRIOR"])) {
                    AddCounters($player, "myField-" . $fi, "durability", 2);
                }
                break;
            case "wum3f33kay": // Maiden of Shrouded Fog: [CB] whenever you activate from memory, buff a phantasia ally
                if(!HasNoAbilities($field[$fi]) && IsClassBonusActive($player, ["CLERIC"])) {
                    $sourceZone = DecisionQueueController::GetVariable("activationSourceZone");
                    if($sourceZone === "myMemory") {
                        $phantasias = ZoneSearch("myField", ["PHANTASIA"]);
                        if(!empty($phantasias)) {
                            $choices = implode("&", $phantasias);
                            DecisionQueueController::AddDecision($player, "MZCHOOSE", $choices, 1,
                                tooltip:"Choose_phantasia_ally_to_buff_(Maiden_of_Shrouded_Fog)");
                            DecisionQueueController::AddDecision($player, "CUSTOM", "MaidenShroudedFogBuff|" . $fi, 1);
                        }
                    }
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

    // Kongming, Fel Eidolon (7x2v4tdop1): Inherited — whenever you activate a Spell card,
    // may change SC to an adjacent direction.
    if(PropertyContains($subtypes, "SPELL") && HasShiftingCurrents($player)
        && ChampionHasInLineage($player, "7x2v4tdop1")) {
        QueueShiftingCurrentsChoice($player, "adjacent", true);
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
function ActivatedAbilityCost($player, $mzCard, $cardID, $abilityIndex = 0) {
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
        case "9gv4vm4kj3": // Backup Charger — banish self
        case "9xycwz9gv4": // Memento Mori — banish self
        case "uqrptjej4m": // Tonic of Remembrance — banish self
        case "xfpk9xycwz": // Alkahest — banish self
        case "sz1ty7vq6z": // Fan of Insight — banish self
        case "nrvth9vyz1": // Everflame Staff — banish self
        case "mhc5a9jpi6": // Enthralling Chime — banish self
            MZMove($player, $mzCard, "myBanish");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "d6soporhlq": // Obelisk of Protection — REST
        case "wk0pw0y6is": // Obelisk of Armaments — REST
        case "xy5lh23qu7": // Obelisk of Fabrication — REST
        case "waf8urrqtj": // Gloamspire, Black Market — REST
        case "4nmxqsm4o9": // The Elysian Astrolabe — REST
        case "0yetaebjlw": // Lunar Conduit — REST
        case "q2svdv3zb9": // Clockwork Musicbox — REST
        case "5pw07bh5wf": // Fractal of Sparks — REST
        case "si9ux3ak6o": // Razor Broadhead — REST
        case "mvfcd0ukk6": // Molten Arrow — REST
        case "szeb8zzj86": // Fractal of Mana — REST
        case "sq0ou8vas3": // Tome of Sorcery — REST
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            break;
        case "vt9y597fqr": // Prima Materia — REST
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            break;
        case "cc0jmpmman": // Ghostsight Glass — REST
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            break;
        case "7gz0j8p4sx": // Minister of Ceremony — REST
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            break;
        case "7kr1haizu8": // Forgetful Concoction — REST + sacrifice self
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            ProcessPotionInfusionTriggers($player, $mzCard);
            MZMove($player, $mzCard, "myGraveyard");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "m6c8xy4cje": // Misteye Archer — REST
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            break;
        case "pol1nz0j1n": // Nullifying Mirror — REST
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            break;
        case "nsjukk5zk4": // Invigorating Concoction — REST + sacrifice self
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            ProcessPotionInfusionTriggers($player, $mzCard);
            MZMove($player, $mzCard, "myGraveyard");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "yorsltrnu3": // Explosive Concoction — REST + sacrifice self
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            ProcessPotionInfusionTriggers($player, $mzCard);
            MZMove($player, $mzCard, "myGraveyard");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "9cy4wipw4k": // Tabula of Salvage — banish self
            MZMove($player, $mzCard, "myBanish");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "x7mnu1xhs5": // Fractal of Creation — sacrifice self
            DoSacrificeFighter($player, $mzCard);
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
        case "5joh300z2s": // Manaroot: sacrifice self to graveyard
        case "69iq4d5vet": // Springleaf: sacrifice self to graveyard
        case "5swaf8urrq": // Whirlwind Vizier: sacrifice self to graveyard
        case "bd7ozuj68m": // Silvershine: sacrifice self to graveyard
        case "i0a5uhjxhk": // Blightroot: sacrifice self to graveyard
        case "jnltv5klry": // Razorvine: sacrifice self to graveyard
        case "bae3z4pyx8": // Serum of Wisdom: sacrifice self
        case "qtb31x97n2": // Potion of Healing: sacrifice self
        case "g616r0zadf": // Bottled Forgelight: sacrifice self
        case "l8ao8bls6g": // Convalescent Tonic: sacrifice self
        case "lpnvx7mnu1": // Draught of Stamina: sacrifice self
        case "9g44vm5kt3": // Empowering Tincture: sacrifice self
        case "14m4c8ljye": // Condensed Supernova: sacrifice self
            ProcessPotionInfusionTriggers($player, $mzCard);
            MZMove($player, $mzCard, "myGraveyard");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "h38lrj5221": // Distilled Atrophy: sacrifice self — store age counters
        case "tjot4nmxqs": // Wildgrowth Elixir: sacrifice self — store age counters
        case "k0hliqs2hi": // Liquid Amnesia: sacrifice self — store age counters
        case "y5ttkat9hr": // Aqua Vitae: sacrifice self — store age counters
            {
                $ageObj = GetZoneObject($mzCard);
                $age = isset($ageObj->Counters['age']) ? $ageObj->Counters['age'] : 0;
                DecisionQueueController::StoreVariable("ageCounters", strval($age));
            }
            ProcessPotionInfusionTriggers($player, $mzCard);
            MZMove($player, $mzCard, "myGraveyard");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "ettczb14m4": // Alchemist's Kit: banish self — store refinement counters
            {
                $kitObj = GetZoneObject($mzCard);
                $refinement = isset($kitObj->Counters['refinement']) ? $kitObj->Counters['refinement'] : 0;
                DecisionQueueController::StoreVariable("refinementCounters", strval($refinement));
            }
            MZMove($player, $mzCard, "myBanish");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "dwmxz1vdxi": // Brewing Kit: REST + sacrifice 3 Herbs
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            // Herb sacrifice is handled in the ActivateAbility body
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
        case "qzzadf9q1v": // Powercell: sacrifice self to graveyard
            OnLeaveField($player, $mzCard);
            MZMove($player, $mzCard, "myGraveyard");
            DecisionQueueController::CleanupRemovedCards();
            TriggerPowercellSacrifice($player);
            break;
        case "df9q1vk8ao": // Molten Cinder: sacrifice self to graveyard
            ProcessPotionInfusionTriggers($player, $mzCard);
            MZMove($player, $mzCard, "myGraveyard");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "uhuy4xippo": // Fractal of Snow: sacrifice self to graveyard
        case "5fnmnpavo4": // Fractal of Polar Depths: sacrifice self to graveyard
        case "to1pmvo54d": // Mnemonic Charm: sacrifice self to graveyard
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
        // Bullets: REST + choose unloaded Gun (load is handled by ability body)
        case "0iqmyn2rz3": // Vanishing Shot
        case "9htu9agwj4": // Mindbreak Bullet
        case "r7ch2bbmoq": // Freezing Round
        case "ii17fzcyfr": // Anathema's End
        case "f8urrqtjot": // Turbulent Bullet
        case "ywc08c9htu": // Cascading Round
        case "ao8bki6fxx": // Steel Slug
        case "dcgw05q66h": // Purified Shot
        case "hreqhj1trn": // Windpiercer
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1; // REST
            break;
        case "gmuesdu6o6": // Worn Diary
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1; // REST
            if($abilityIndex == 1) {
                // Ability 1: REST, Banish — draw a card (only if 10+ page counters)
                MZMove($player, $mzCard, "myBanish");
                DecisionQueueController::CleanupRemovedCards();
            }
            break;
        case "3p5iqigcom": // Spirit Shard: sacrifice self
            MZMove($player, $mzCard, "myGraveyard");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "9krp8brw64": // Keeper of the Wild: sacrifice self
            DoSacrificeFighter($player, $mzCard);
            break;
        case "6p3p5iqigc": // Portside Pirate: banish floating memory card from graveyard
            {
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
                    DecisionQueueController::AddDecision($player, "CUSTOM", "PortsidePirateBanish", 1);
                }
            }
            break;
        case "g31dg6zl3j": // Sigil of Budding Embers: REST + banish self
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            MZMove($player, $mzCard, "myBanish");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "lzfkc8ntn4": // Windspire Crest
            if($abilityIndex == 0) {
                // Ability 0: REST + remove 2 glimmer from champion
                $sourceObj = &GetZoneObject($mzCard);
                $sourceObj->Status = 1;
                $wcChampMZ = FindChampionMZ($player);
                if($wcChampMZ !== null) {
                    RemoveCounters($player, $wcChampMZ, "glimmer", 2);
                }
            } else if($abilityIndex == 1) {
                // Ability 1: banish self
                MZMove($player, $mzCard, "myBanish");
                DecisionQueueController::CleanupRemovedCards();
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
    // Tonic of Remembrance (uqrptjej4m): [CB] needs cards in memory
    if($cardID === "uqrptjej4m") {
        if(!IsClassBonusActive($player, ["CLERIC"])) return;
        if(empty(ZoneSearch("myMemory"))) return;
    }
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
    // Fractal of Mana (szeb8zzj86): [CB] REST — must be awake + class bonus
    if($cardID === "szeb8zzj86") {
        if($sourceObject->Status != 2) return;
        if(!IsClassBonusActive($player, ["CLERIC", "MAGE"])) return;
    }
    // Tome of Sorcery (sq0ou8vas3): REST — must be awake
    if($cardID === "sq0ou8vas3" && $sourceObject->Status != 2) return;
    // Mnemonic Charm (to1pmvo54d): [CB] Sacrifice — needs class bonus
    if($cardID === "to1pmvo54d" && !IsClassBonusActive($player, ["CLERIC", "MAGE"])) return;
    // Cloudstone Orb (ygqehvpblj): [CB] (3) — needs class bonus + 3 cards in hand
    if($cardID === "ygqehvpblj") {
        if(!IsClassBonusActive($player, ["MAGE"])) return;
        $hand = &GetHand($player);
        if(count($hand) < 3) return;
    }
    // Oasis Trading Post (uy4xippor7): must be awake
    if($cardID === "uy4xippor7" && $sourceObject->Status != 2) return;
    // Obelisk of Armaments (wk0pw0y6is): must be awake
    if($cardID === "wk0pw0y6is" && $sourceObject->Status != 2) return;
    // Obelisk of Fabrication (xy5lh23qu7): must be awake
    if($cardID === "xy5lh23qu7" && $sourceObject->Status != 2) return;
    // Gloamspire, Black Market (waf8urrqtj): must be awake + slow speed only
    if($cardID === "waf8urrqtj") {
        if($sourceObject->Status != 2) return;
        if(HasOpportunity($player)) return;
    }
    // Geni, Gifted Mechanist (wuir99sx6q): must have cards in graveyard
    if($cardID === "wuir99sx6q" && empty(ZoneSearch("myGraveyard"))) return;
    // Fractal of Creation (x7mnu1xhs5): needs tokens on field
    if($cardID === "x7mnu1xhs5" && empty(ZoneSearch("myField", ["TOKEN"]))) return;
    // Perse, Relentless Raptor (nl1gxrpx8j): must be awake, must be distant, needs valid target
    if($cardID === "nl1gxrpx8j") {
        if($sourceObject->Status != 2) return;
        if(!IsDistant($sourceObject)) return;
        $targets = array_merge(
            ZoneSearch("theirField", ["ALLY"]),
            ZoneSearch("theirField", ["REGALIA"], cardSubtypes: ["ITEM"]),
            ZoneSearch("theirField", ["REGALIA"], cardSubtypes: ["WEAPON"])
        );
        if(empty($targets)) return;
    }
    // Lena, Dorumegia's Herald (gwve1d47o7): must be awake, needs deck cards
    if($cardID === "gwve1d47o7") {
        if($sourceObject->Status != 2) return;
        if(empty(ZoneSearch("myDeck"))) return;
    }
    // Reconnaissance Field (2rz308kuz0): must be awake + CB required
    if($cardID === "2rz308kuz0") {
        if($sourceObject->Status != 2) return;
        if(!IsClassBonusActive($player, ["RANGER"])) return;
    }
    // Backup Charger (9gv4vm4kj3): (3), Banish self — needs 3+ cards in hand for reserve
    if($cardID === "9gv4vm4kj3") {
        $hand = &GetHand($player);
        if(count($hand) < 3) return;
    }
    // Memento Mori (9xycwz9gv4): Banish self — slow speed only + 6+ prize counters
    if($cardID === "9xycwz9gv4") {
        if(HasOpportunity($player)) return;
        if(GetCounterCount($sourceObject, "prize") < 6) return;
    }
    // Bullets (REST: Load into unloaded Gun): must be awake + unloaded Gun exists
    if($cardID === "0iqmyn2rz3" || $cardID === "9htu9agwj4" || $cardID === "r7ch2bbmoq"
       || $cardID === "ii17fzcyfr" || $cardID === "f8urrqtjot" || $cardID === "ywc08c9htu"
       || $cardID === "ao8bki6fxx" || $cardID === "dcgw05q66h" || $cardID === "hreqhj1trn") {
        if($sourceObject->Status != 2) return;
        if(empty(GetUnloadedGuns($player))) return;
    }
    // Worn Diary (gmuesdu6o6): both abilities require awake
    if($cardID === "gmuesdu6o6") {
        if($sourceObject->Status != 2) return;
        // Ability 1 (banish + draw): requires 10+ page counters
        if(intval($abilityIndex) == 1 && GetCounterCount($sourceObject, "page") < 10) return;
    }
    // Molten Cinder (df9q1vk8ao): sacrifice self — target champion that leveled up this turn
    if($cardID === "df9q1vk8ao") {
        if(GlobalEffectCount(1, "LEVELED_UP_THIS_TURN") == 0 && GlobalEffectCount(2, "LEVELED_UP_THIS_TURN") == 0) return;
    }
    // The Elysian Astrolabe (4nmxqsm4o9): REST - must be awake
    if($cardID === "4nmxqsm4o9") {
        if($sourceObject->Status != 2) return;
    }
    // Razor Broadhead (si9ux3ak6o): REST — must be awake + needs unloaded Bow
    if($cardID === "si9ux3ak6o") {
        if($sourceObject->Status != 2) return;
        if(empty(GetUnloadedBows($player))) return;
    }
    // Savage Arrow (uuty5scwug): REST — must be awake + needs unloaded Bow
    if($cardID === "uuty5scwug") {
        if($sourceObject->Status != 2) return;
        if(empty(GetUnloadedBows($player))) return;
    }
    // Molten Arrow (mvfcd0ukk6): REST — must be awake + needs unloaded Bow
    if($cardID === "mvfcd0ukk6") {
        if($sourceObject->Status != 2) return;
        if(empty(GetUnloadedBows($player))) return;
    }
    // Blazing Lunge (wewvlfkfp7): [CB] banish 2 fire from GY for unpreventable — must be in intent
    if($cardID === "wewvlfkfp7") {
        if(!IsClassBonusActive($player, ["WARRIOR"])) return;
        if(count(ZoneSearch("myGraveyard", cardElements: ["FIRE"])) < 2) return;
        if(strpos($mzCard, "myIntent") !== 0) return;
    }
    // Fan of Insight (sz1ty7vq6z): Banish — needs cards in memory
    if($cardID === "sz1ty7vq6z") {
        if(empty(ZoneSearch("myMemory"))) return;
    }
    // Enthralling Chime (mhc5a9jpi6): [Diao Chan Bonus] (3), Banish: gain control of ally with 3+ wither
    if($cardID === "mhc5a9jpi6") {
        if(!IsDiaoChanBonus($player)) return;
        $hand = &GetHand($player);
        if(count($hand) < 3) return;
        // Must have a valid target: an ally with 3+ wither counters
        $witherAllies = [];
        foreach(["myField", "theirField"] as $zn) {
            $allies = ZoneSearch($zn, ["ALLY"]);
            foreach($allies as $aMZ) {
                $aObj = GetZoneObject($aMZ);
                if($aObj !== null && GetCounterCount($aObj, "wither") >= 3) {
                    $witherAllies[] = $aMZ;
                }
            }
        }
        $witherAllies = FilterSpellshroudTargets($witherAllies);
        if(empty($witherAllies)) return;
    }
    // Prima Materia (vt9y597fqr): REST - must be awake
    if($cardID === "vt9y597fqr") {
        if($sourceObject->Status != 2) return;
    }
    // Everflame Staff (nrvth9vyz1): [CB] Banish — 3+ refinement counters required
    if($cardID === "nrvth9vyz1") {
        if(!IsClassBonusActive($player, ["CLERIC", "MAGE"])) return;
        if(GetCounterCount($sourceObject, "refinement") < 3) return;
    }
    // Minister of Ceremony (7gz0j8p4sx): REST — must be awake + slow speed + SC East
    if($cardID === "7gz0j8p4sx") {
        if($sourceObject->Status != 2) return;
        if(HasOpportunity($player)) return;
        if(GetShiftingCurrents($player) !== "EAST") return;
    }
    // Forgetful Concoction (7kr1haizu8): REST + sacrifice — must be awake + opponent has memory
    if($cardID === "7kr1haizu8") {
        if($sourceObject->Status != 2) return;
        $oppMem = &GetMemory(($player == 1) ? 2 : 1);
        if(empty($oppMem)) return;
    }
    // Ghostsight Glass (cc0jmpmman): (3), REST — must be awake + slow speed only
    if($cardID === "cc0jmpmman") {
        if($sourceObject->Status != 2) return;
        if(HasOpportunity($player)) return;
    }
    // Lunar Conduit (0yetaebjlw): (3), REST — must be awake + has charge counters + 3 cards in hand
    if($cardID === "0yetaebjlw") {
        if($sourceObject->Status != 2) return;
        if(GetCounterCount($sourceObject, "charge") < 1) return;
        $hand = &GetHand($player);
        if(count($hand) < 3) return;
    }
    // Spirit Shard (3p5iqigcom): [Level 3+] sacrifice self: draw a card
    if($cardID === "3p5iqigcom" && PlayerLevel($player) < 3) return;
    // Portside Pirate (6p3p5iqigc): [CB] need floating memory card in graveyard
    if($cardID === "6p3p5iqigc") {
        if(!IsClassBonusActive($player, ["ASSASSIN"])) return;
        $gy = GetZone("myGraveyard");
        $hasFloating = false;
        foreach($gy as $gyObj) {
            if(!$gyObj->removed && HasFloatingMemory($gyObj)) { $hasFloating = true; break; }
        }
        if(!$hasFloating) return;
    }
    // Lunar Seer (qjt0ooffy4): [CB] REST — must be awake + CB
    if($cardID === "qjt0ooffy4") {
        if($sourceObject->Status != 2) return;
        if(!IsClassBonusActive($player, ["CLERIC"])) return;
    }
    // Powercell (qzzadf9q1v): REST + sacrifice — must be awake + have Automaton allies
    if($cardID === "qzzadf9q1v") {
        if($sourceObject->Status != 2) return;
        if(empty(ZoneSearch("myField", ["ALLY"], cardSubtypes: ["AUTOMATON"]))) return;
    }
    // Clockwork Musicbox (q2svdv3zb9): REST — must be awake + have musicbox-banished cards
    if($cardID === "q2svdv3zb9") {
        if($sourceObject->Status != 2) return;
        $banish = GetZone("myBanish");
        $hasMusicboxCards = false;
        foreach($banish as $bObj) {
            if(!$bObj->removed && isset($bObj->Counters['_musicbox'])) { $hasMusicboxCards = true; break; }
        }
        if(!$hasMusicboxCards) return;
    }
    // Alkahest (xfpk9xycwz): [Level 4+] Banish self — destroy target item/weapon
    if($cardID === "xfpk9xycwz" && PlayerLevel($player) < 4) return;
    // Misteye Archer (m6c8xy4cje): (3), REST — must be awake + needs 3 cards in hand for reserve
    if($cardID === "m6c8xy4cje") {
        if($sourceObject->Status != 2) return;
        $hand = &GetHand($player);
        if(count($hand) < 3) return;
    }
    // Invigorating Concoction (nsjukk5zk4): REST, Sacrifice — must be awake + slow speed
    if($cardID === "nsjukk5zk4") {
        if($sourceObject->Status != 2) return;
        if(HasOpportunity($player)) return;
    }
    // Explosive Concoction (yorsltrnu3): REST, Sacrifice — must be awake + slow speed
    if($cardID === "yorsltrnu3") {
        if($sourceObject->Status != 2) return;
        if(HasOpportunity($player)) return;
    }
    // Sigil of Budding Embers (g31dg6zl3j): [Diao Chan Bonus] — requires DC bonus + champion glimmer
    if($cardID === "g31dg6zl3j") {
        if(!IsDiaoChanBonus($player)) return;
        $champObj = GetPlayerChampion($player);
        if($champObj === null || GetCounterCount($champObj, "glimmer") <= 0) return;
    }
    // Windspire Crest (lzfkc8ntn4): ability 0 requires DC bonus + 2+ glimmer
    if($cardID === "lzfkc8ntn4" && intval($abilityIndex) == 0) {
        if(!IsDiaoChanBonus($player)) return;
        $champObj = GetPlayerChampion($player);
        if($champObj === null || GetCounterCount($champObj, "glimmer") < 2) return;
    }
    
    // Ability index is now passed directly from the frontend button click
    $selectedAbilityIndex = intval($abilityIndex);
    // Exhaust the unit as the REST cost — only for static abilities, not dynamic ones (which have their own costs)
    $cardType = CardType($cardID);
    $staticAbilityCount = CardActivateAbilityCount($cardID);
    if($selectedAbilityIndex < $staticAbilityCount && (PropertyContains($cardType, "ALLY") || PropertyContains($cardType, "CHAMPION") || PropertyContains($cardType, "PHANTASIA"))) {
        $sourceObject->Status = 1;
    }

    // Pay non-reserve costs (e.g., banish self) before the opponent gets priority.
    ActivatedAbilityCost($player, $mzCard, $cardID, $selectedAbilityIndex);

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
        // Freydis, Master Tactician: Remove 3 tactic counters → permanent distant
        if(!$handledDynamic && $cardID === "7dedg616r0" && GetCounterCount($sourceObject, "tactic") >= 3) {
            if($selectedAbilityIndex == $dynIndex) {
                RemoveCounters($player, $mzCard, "tactic", 3);
                AddGlobalEffects($player, "FREYDIS_PERMANENT_DISTANT");
                $handledDynamic = true;
            }
            $dynIndex++;
        }
        // Diana, Cursebreaker (o0qtb31x97): Banish all curses from lineage, materialize 2 Bullets,
        // grant "On Attack: Wake up Diana" until end of turn
        if(!$handledDynamic && $cardID === "o0qtb31x97" && CountCursesInLineage($player) >= 4) {
            if($selectedAbilityIndex == $dynIndex) {
                // Cost: Banish all Curse cards from Diana's lineage
                if(is_array($sourceObject->Subcards)) {
                    $remaining = [];
                    foreach($sourceObject->Subcards as $scID) {
                        if(PropertyContains(CardSubtypes($scID), "CURSE")) {
                            MZAddZone($player, "myBanish", $scID);
                        } else {
                            $remaining[] = $scID;
                        }
                    }
                    $sourceObject->Subcards = $remaining;
                }
                // Effect: Materialize two Bullet cards from material deck
                CursebreakerMaterializeBullets($player, 2);
                // Grant "On Attack: Wake up Diana" until end of turn
                AddTurnEffect($mzCard, "CURSEBREAKER_ON_ATTACK");
                $handledDynamic = true;
            }
            $dynIndex++;
        }
        // Fang of Dragon's Breath (iebo5fu381): [Jin Bonus] weapon REST ability — deal 2 damage to a unit
        if(!$handledDynamic && PropertyContains(CardType($cardID), "WEAPON")) {
            $linkedCards = GetLinkedCards($sourceObject);
            $fangObj = null;
            $fangMZ = null;
            foreach($linkedCards as $lObj) {
                if($lObj->CardID === "iebo5fu381" && !HasNoAbilities($lObj)) {
                    if(GetCounterCount($lObj, "durability") > 0) {
                        $fangObj = $lObj;
                        $fangMZ = $lObj->GetMzID();
                    }
                    break;
                }
            }
            if($fangMZ !== null) {
                global $playerID;
                $zone = $sourceObject->Controller == $playerID ? "myField" : "theirField";
                $controllerField = GetZone($zone);
                $isJin = false;
                foreach($controllerField as $fObj) {
                    if(!$fObj->removed && PropertyContains(EffectiveCardType($fObj), "CHAMPION")) {
                        if(strpos(CardName($fObj->CardID), "Jin") === 0) $isJin = true;
                        break;
                    }
                }
                if($isJin && $selectedAbilityIndex == $dynIndex) {
                    // Cost: REST (exhaust) the weapon and remove a durability counter from Fang
                    $sourceObject->Status = 1;
                    RemoveCounters($player, $fangMZ, "durability", 1);
                    // Effect: deal 2 damage to target unit
                    $allUnits = array_merge(
                        ZoneSearch("myField", ["ALLY", "CHAMPION"]),
                        ZoneSearch("theirField", ["ALLY", "CHAMPION"])
                    );
                    $allUnits = FilterSpellshroudTargets($allUnits);
                    if(!empty($allUnits)) {
                        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $allUnits), 1,
                            tooltip:"Deal_2_damage_to_target_unit_(Fang_of_Dragon's_Breath)");
                        DecisionQueueController::AddDecision($player, "CUSTOM", "FangDragonBreathDamage|" . $mzCard, 1);
                    }
                    $handledDynamic = true;
                }
                $dynIndex++;
            }
        }
    }
    if(!$isDynamic) {
        // Captivating Opulence (tnl3qr42vp): [Diao Chan Bonus] opponents' regalia activated abilities cost (2) more
        if(PropertyContains(CardType($cardID), "REGALIA")) {
            $opponent = ($player == 1) ? 2 : 1;
            global $playerID;
            $oppField = $opponent == $playerID ? "myField" : "theirField";
            $oppZone = GetZone($oppField);
            foreach($oppZone as $coObj) {
                if(!$coObj->removed && $coObj->CardID === "tnl3qr42vp" && !HasNoAbilities($coObj) && IsDiaoChanBonus($opponent)) {
                    for($ri = 0; $ri < 2; $ri++) {
                        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 1);
                    }
                    break;
                }
            }
        }
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
    // Xiao Qiao, Cinderkeeper (3hgldrogit): if unit was hit by Xiao Qiao this turn, banish instead
    $xiaoQiaoBanish = in_array("HIT_BY_3hgldrogit", $obj->TurnEffects);
    // Fireworks Display (sx6q3p6i0i): banish instead of graveyard
    $fireworksBanish = GlobalEffectCount($controller, "FIREWORKS_BANISH") > 0;
    // Ephemeral: object is banished instead of leaving the field
    $isEphemeral = IsEphemeral($obj);
    if(IsRenewable($obj->CardID) && !$fireworksBanish && !$xiaoQiaoBanish && !$isEphemeral) {
        // Renewable: goes to material deck instead of graveyard/banish
        $dest = $player == $controller ? "myMaterial" : "theirMaterial";
    } else if($fireworksBanish || $xiaoQiaoBanish || $isEphemeral) {
        $dest = $player == $controller ? "myBanish" : "theirBanish";
    } else {
        $dest = $player == $controller ? "myGraveyard" : "theirGraveyard";
    }
    // Brackish Lutist (1clswn3ba2): if floating memory card would go to graveyard, banish instead
    if(strpos($dest, "Graveyard") !== false && HasFloatingMemory($obj)) {
        if(IsBrackishLutistOnField()) {
            $dest = $player == $controller ? "myBanish" : "theirBanish";
        }
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
    // Carter, Synthetic Reaper (1wl8ao8bls): whenever an ally dies, the champion recovers 1
    {
        global $playerID;
        $controllerField = $controller == $playerID ? "myField" : "theirField";
        $field = GetZone($controllerField);
        foreach($field as $carterObj) {
            if(!$carterObj->removed && $carterObj->CardID === "1wl8ao8bls" && !HasNoAbilities($carterObj)) {
                RecoverChampion($controller, 1);
                break;
            }
        }
    }
    // Claude, Fated Visionary (52215upufy): Automaton allies you control have "On Death: Glimpse 3"
    if(PropertyContains(EffectiveCardSubtypes($obj), "AUTOMATON") && !PropertyContains(EffectiveCardType($obj), "TOKEN") && !$suppressed) {
        global $playerID;
        $controllerField = $controller == $playerID ? "myField" : "theirField";
        $field = GetZone($controllerField);
        foreach($field as $claudeObj) {
            if(!$claudeObj->removed && $claudeObj->CardID === "52215upufy" && !HasNoAbilities($claudeObj)) {
                Glimpse($controller, 3);
                break;
            }
        }
    }
    // Memento Mori (9xycwz9gv4): whenever an ally dies, put a prize counter on Memento Mori
    {
        global $playerID;
        $controllerField = $controller == $playerID ? "myField" : "theirField";
        $field = GetZone($controllerField);
        for($mi = 0; $mi < count($field); ++$mi) {
            if(!$field[$mi]->removed && $field[$mi]->CardID === "9xycwz9gv4" && !HasNoAbilities($field[$mi])) {
                AddCounters($controller, $controllerField . "-" . $mi, "prize", 1);
            }
        }
    }
    // Harvester Mk II (ttkat9hreq): Automaton allies you control have "On Death: Summon a Powercell token."
    if(PropertyContains(EffectiveCardSubtypes($obj), "AUTOMATON") && !$suppressed) {
        global $playerID;
        $controllerField = $controller == $playerID ? "myField" : "theirField";
        $field = GetZone($controllerField);
        foreach($field as $harvesterObj) {
            if(!$harvesterObj->removed && $harvesterObj->CardID === "ttkat9hreq" && !HasNoAbilities($harvesterObj)) {
                MZAddZone($controller, "myField", "qzzadf9q1v"); // Powercell token
                break;
            }
        }
    }
    // Lumen Borealis (3ejd9yj9rl): whenever an Animal ally you control dies, you may reveal from memory
    if(PropertyContains(EffectiveCardSubtypes($obj), "ANIMAL") && PropertyContains(EffectiveCardType($obj), "ALLY")) {
        global $playerID;
        $controllerField = $controller == $playerID ? "myField" : "theirField";
        $field = GetZone($controllerField);
        foreach($field as $lbObj) {
            if(!$lbObj->removed && $lbObj->CardID === "3ejd9yj9rl" && !HasNoAbilities($lbObj)) {
                $memCards = ZoneSearch($controller == $playerID ? "myMemory" : "theirMemory");
                if(!empty($memCards)) {
                    $memStr = implode("&", $memCards);
                    DecisionQueueController::AddDecision($controller, "MZMAYCHOOSE", $memStr, 1, "Reveal_a_card_from_memory?");
                    DecisionQueueController::AddDecision($controller, "CUSTOM", "LumenBorealisReveal", 1);
                }
                break;
            }
        }
    }
    // Diao Chan, Idyll Corsage (d7l6i5thdy): whenever a non-token object an opponent controls
    // is destroyed, you may banish it. If you do, that opponent summons a Flowerbud token.
    if(!PropertyContains(CardType($obj->CardID), "TOKEN")) {
        $dcPlayer = ($controller == 1) ? 2 : 1; // Diao Chan's player is the opponent of the destroyed card's controller
        global $playerID;
        $dcField = $dcPlayer == $playerID ? "myField" : "theirField";
        $field = GetZone($dcField);
        $hasDC = false;
        foreach($field as $dcObj) {
            if(!$dcObj->removed && $dcObj->CardID === "d7l6i5thdy" && !HasNoAbilities($dcObj) && $dcObj->Controller == $dcPlayer) {
                $hasDC = true;
                break;
            }
        }
        if($hasDC) {
            // Find the destroyed card in its current destination (GY/banish/material) and offer to banish it
            DecisionQueueController::AddDecision($dcPlayer, "YESNO", "-", 1,
                tooltip:"Banish_destroyed_object_and_give_opponent_a_Flowerbud?");
            DecisionQueueController::AddDecision($dcPlayer, "CUSTOM", "DiaoChanIdyllBanish|" . $obj->CardID . "|" . $controller, 1);
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
            // FROZEN_BY_TORPID: persistent rest while opponent controls Torpid Fractal (h9u9584zpn)
            if(in_array("FROZEN_BY_TORPID", $field[$i]->TurnEffects)) {
                $oppHasTorpid = false;
                foreach($opponentField as $opp) {
                    if(!$opp->removed && $opp->CardID === "h9u9584zpn" && !HasNoAbilities($opp)) {
                        $oppHasTorpid = true;
                        break;
                    }
                }
                if($oppHasTorpid) {
                    continue; // Still frozen — don't wake
                } else {
                    $field[$i]->TurnEffects = array_values(array_diff($field[$i]->TurnEffects, ["FROZEN_BY_TORPID"]));
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
            // Calamity Cannon: convert persistent marker to active (consumable this turn)
            if(in_array("CALAMITY_CANNON", $field[$i]->TurnEffects)) {
                $field[$i]->TurnEffects = array_values(array_diff($field[$i]->TurnEffects, ["CALAMITY_CANNON"]));
                $field[$i]->TurnEffects[] = "CALAMITY_CANNON_ACTIVE";
            }
            // Ingress of Sanguine Ire: convert persistent marker to active (consumable this turn)
            if(in_array("INGRESS_SANGUINE", $field[$i]->TurnEffects)) {
                $field[$i]->TurnEffects = array_values(array_diff($field[$i]->TurnEffects, ["INGRESS_SANGUINE"]));
                $field[$i]->TurnEffects[] = "INGRESS_ACTIVE";
            }
            // TAUNT_NEXT_TURN / VIGOR_NEXT_TURN: expire at beginning of controller's next turn
            if(in_array("TAUNT_NEXT_TURN", $field[$i]->TurnEffects)) {
                $field[$i]->TurnEffects = array_values(array_diff($field[$i]->TurnEffects, ["TAUNT_NEXT_TURN"]));
            }
            if(in_array("VIGOR_NEXT_TURN", $field[$i]->TurnEffects)) {
                $field[$i]->TurnEffects = array_values(array_diff($field[$i]->TurnEffects, ["VIGOR_NEXT_TURN"]));
            }
            // Stand Fast (ao1cfkhbp6): +1 LIFE until beginning of next turn
            if(in_array("ao1cfkhbp6", $field[$i]->TurnEffects)) {
                $field[$i]->TurnEffects = array_values(array_diff($field[$i]->TurnEffects, ["ao1cfkhbp6"]));
            }
            $field[$i]->Status = 2;
        }
    }
    // Bring Down the Mighty: clear CANT_ATTACK_NEXT_TURN from opponent's field at beginning of caster's turn
    $oppField = &GetField($otherPlayer);
    for($i = 0; $i < count($oppField); ++$i) {
        if(!$oppField[$i]->removed && in_array("CANT_ATTACK_NEXT_TURN", $oppField[$i]->TurnEffects)) {
            $oppField[$i]->TurnEffects = array_values(array_diff($oppField[$i]->TurnEffects, ["CANT_ATTACK_NEXT_TURN"]));
        }
    }
}

function OnEnter($player, $mzID) {
    global $enterAbilities;
    $obj = GetZoneObject($mzID);
    $CardID = $obj->CardID;
    DecisionQueueController::CleanupRemovedCards();
    // Re-store mzID after cleanup: CleanupRemovedCards reindexes the field so the
    // stored index may differ from the card's actual current position.
    DecisionQueueController::StoreVariable("mzID", $obj->GetMzID());
    if(HasNoAbilities($obj)) return;
    if(isset($enterAbilities[$CardID . ":0"])) $enterAbilities[$CardID . ":0"]($player);
}

function FieldAfterAdd($player, $CardID="-", $Status=2, $Owner="-", $Damage=0, $Controller="-", $TurnEffects="-", $Counters="-", $Subcards="-") {
    $field = &GetField($player);
    $added = $field[count($field)-1];
    $added->Controller = $player;
    if($added->Owner == 0) $added->Owner = $player;

    // Track that this card entered the field this turn (for Tempest Downfall etc.)
    $added->TurnEffects[] = "ENTERED_THIS_TURN";
    
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
    // The Elysian Astrolabe (4nmxqsm4o9): Hindered — enters the field rested
    if($added->CardID == "4nmxqsm4o9") {
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

    // Siegeable domains enter with durability counters equal to their printed durability stat
    if(IsSiegeable($added)) {
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

    // Weapon Link: if the entering card has Weapon Link, establish the link
    if($added->CardID === "0cnn1eh85y" || $added->CardID === "iebo5fu381") {
        $weaponLinkTargetMZ = DecisionQueueController::GetVariable("weaponLinkTargetMZ");
        if(!empty($weaponLinkTargetMZ) && $weaponLinkTargetMZ !== "-") {
            $phantasiaMZ = "myField-" . (count($field) - 1);
            CreateWeaponLink($player, $phantasiaMZ, $weaponLinkTargetMZ);
            DecisionQueueController::StoreVariable("weaponLinkTargetMZ", "");
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

    // Wildheart Hymn (f05n4ulo84): whenever an Animal enters the field under your control, put a buff counter on it
    if(PropertyContains(CardType($added->CardID), "ALLY")) {
        $subtypes = CardSubtypes($added->CardID);
        if(PropertyContains($subtypes, "ANIMAL")) {
            if(GlobalEffectCount($player, "f05n4ulo84") > 0) {
                AddCounters($player, "myField-" . (count($field) - 1), "buff", 1);
            }
        }
    }

    // Wildgrowth Feline (3krdvxapdp): [Class Bonus] when another Animal/Beast ally enters, put a buff counter on Wildgrowth Feline
    if(PropertyContains(CardType($added->CardID), "ALLY")) {
        $subtypes = CardSubtypes($added->CardID);
        if(PropertyContains($subtypes, "ANIMAL") || PropertyContains($subtypes, "BEAST")) {
            for($wgf = 0; $wgf < count($field); ++$wgf) {
                if(!$field[$wgf]->removed && $field[$wgf]->CardID === "3krdvxapdp"
                    && !HasNoAbilities($field[$wgf])
                    && $wgf !== (count($field) - 1)
                    && IsClassBonusActive($player, ["TAMER"])) {
                    AddCounters($player, "myField-" . $wgf, "buff", 1);
                }
            }
        }
    }

    // Fraternal Garrison (ln926ymxdc): [Jin Bonus] when another ally enters, +1 POWER until end of turn
    if(PropertyContains(CardType($added->CardID), "ALLY")) {
        for($fg = 0; $fg < count($field); ++$fg) {
            if(!$field[$fg]->removed && $field[$fg]->CardID === "ln926ymxdc"
                && !HasNoAbilities($field[$fg])
                && $fg !== (count($field) - 1)) {
                // Check Jin Bonus: champion must be named "Jin"
                $isJin = false;
                foreach($field as $fObj) {
                    if(!$fObj->removed && PropertyContains(EffectiveCardType($fObj), "CHAMPION")) {
                        if(strpos(CardName($fObj->CardID), "Jin") === 0) {
                            $isJin = true;
                        }
                        break;
                    }
                }
                if($isJin) {
                    AddTurnEffect("myField-" . $fg, "ln926ymxdc");
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

    // Collapsing Trap (v2214upufo): next time allies enter the field this turn, they enter rested
    if(PropertyContains(CardType($added->CardID), "ALLY") || (PropertyContains(CardType($added->CardID), "TOKEN") && PropertyContains(CardSubtypes($added->CardID), "ALLY"))) {
        for($ctp = 1; $ctp <= 2; ++$ctp) {
            if(GlobalEffectCount($ctp, "COLLAPSING_TRAP") > 0) {
                $added->Status = 1;
                RemoveGlobalEffect($ctp, "COLLAPSING_TRAP");
                break;
            }
        }
    }

    // Sudden Snow (dxAEI20h8F): allies enter the field rested this turn
    if(PropertyContains(CardType($added->CardID), "ALLY")) {
        for($ssp = 1; $ssp <= 2; ++$ssp) {
            if(GlobalEffectCount($ssp, "SUDDEN_SNOW_RESTED") > 0) {
                $added->Status = 1;
                break;
            }
        }
    }

    // Freezing Steel (x7mdk0xhi5): next items enter the field rested
    if(PropertyContains(CardType($added->CardID), "ITEM") || PropertyContains(CardType($added->CardID), "TOKEN")) {
        // Check both players for the global effect (applies to "this turn" items)
        for($fsp = 1; $fsp <= 2; ++$fsp) {
            if(GlobalEffectCount($fsp, "FREEZING_STEEL") > 0) {
                $added->Status = 1;
                RemoveGlobalEffect($fsp, "FREEZING_STEEL");
                break;
            }
        }
    }

    // Krustallan Ruins (fei7chsbal): whenever an ally enters the field under a player's control,
    // rest that ally unless that player pays (1).
    $enteredCardType = CardType($added->CardID);
    if(PropertyContains($enteredCardType, "ALLY") || (PropertyContains($enteredCardType, "TOKEN") && PropertyContains(CardSubtypes($added->CardID), "ALLY"))) {
        // Check both players' fields for Krustallan Ruins
        for($kp = 1; $kp <= 2; ++$kp) {
            $kField = &GetField($kp);
            $hasRuins = false;
            foreach($kField as $kObj) {
                if(!$kObj->removed && $kObj->CardID === "fei7chsbal" && !HasNoAbilities($kObj)) {
                    $hasRuins = true;
                    break;
                }
            }
            if($hasRuins) {
                // Check if player has cards in hand to pay (1)
                $hand = &GetHand($player);
                if(count($hand) > 0) {
                    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Pay_(1)_to_keep_ally_awake?_(Krustallan_Ruins)");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "KrustallanRuinsPayOrRest|" . (count($field) - 1), 1);
                } else {
                    // Can't pay — rest the ally
                    $added->Status = 1;
                }
                break; // Only one Krustallan Ruins trigger per entry
            }
        }
    }

    // Airship Captain (t9hreqhj1t): whenever a domain enters under your control, deal 2 to target champion
    if(PropertyContains(CardType($added->CardID), "DOMAIN")) {
        for($ac = 0; $ac < count($field); ++$ac) {
            if(!$field[$ac]->removed && $field[$ac]->CardID === "t9hreqhj1t" && !HasNoAbilities($field[$ac])) {
                $champions = array_merge(
                    ZoneSearch("myField", ["CHAMPION"]),
                    ZoneSearch("theirField", ["CHAMPION"])
                );
                if(!empty($champions)) {
                    if(count($champions) == 1) {
                        DealDamage($player, "t9hreqhj1t", $champions[0], 2);
                    } else {
                        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $champions), 1, tooltip:"Deal_2_damage_to_target_champion_(Airship_Captain)");
                        DecisionQueueController::AddDecision($player, "CUSTOM", "AirshipCaptainDamage", 1);
                    }
                }
                break;
            }
        }
    }

    // Vengeful Gust (q4dvnn3zp1): [Level 3+] suppressed card enters with "On Enter: Deal 4 damage to your champion"
    if(in_array("VENGEFUL_GUST_PENALTY", $added->TurnEffects)) {
        $added->TurnEffects = array_values(array_diff($added->TurnEffects, ["VENGEFUL_GUST_PENALTY"]));
        DealChampionDamage($player, 4);
    }

    Enter($player, $field[count($field)-1]->GetMzID());
}

// Airship Captain (t9hreqhj1t): deal 2 damage to chosen champion
$customDQHandlers["AirshipCaptainDamage"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    DealDamage($player, "t9hreqhj1t", $lastDecision, 2);
};

// Xuchang, Frozen Citadel (xpb20rar4k): opponent chose whether to banish a floating-memory GY card
$customDQHandlers["XuchangFrozenCitadel"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") {
        // Didn't banish — next card activated this turn costs 2 more
        AddGlobalEffects($player, "XUCHANG_COST_INCREASE");
    } else {
        // Banished the chosen card
        MZMove($player, $lastDecision, "myBanish");
    }
};

// Inner Court Schemer (spijrps4ny): On Attack — remove preparation counter for +2 POWER
$customDQHandlers["InnerCourtSchemerRemovePrep"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $champMZ = $parts[0];
    RemoveCounters($player, $champMZ, "preparation", 1);
    $attackerMZ = DecisionQueueController::GetVariable("CombatAttacker");
    if($attackerMZ !== null) {
        AddTurnEffect($attackerMZ, "spijrps4ny");
    }
};

// Mystic Purifier (s9qtcq0rzh): On Enter — may pay (2) to destroy target phantasia
function MysticPurifierExecute($player, $answer) {
    if($answer !== "YES") return;
    for($i = 0; $i < 2; ++$i) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 1);
    }
    $phantasias = array_merge(
        ZoneSearch("myField", ["PHANTASIA"]),
        ZoneSearch("theirField", ["PHANTASIA"])
    );
    $phantasias = FilterSpellshroudTargets($phantasias);
    if(!empty($phantasias)) {
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $phantasias), 1, "Destroy_target_phantasia");
        DecisionQueueController::AddDecision($player, "CUSTOM", "MysticPurifierDestroy", 1);
    }
}
$customDQHandlers["MysticPurifierDestroy"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $obj = GetZoneObject($lastDecision);
    if($obj === null) return;
    $owner = $obj->Owner;
    OnLeaveField($player, $lastDecision);
    $gravZone = ($player == $owner) ? "myGraveyard" : "theirGraveyard";
    $gravZone = EphemeralRedirectDest($obj, $gravZone, $player);
    MZMove($player, $lastDecision, $gravZone);
    DecisionQueueController::CleanupRemovedCards();
};

// Burst Asunder (rzsr6aw4hz): sacrifice Fractals for additional 2 damage each
function BurstAsunderSacrificeFractals($player, $source, $target) {
    $targetObj = GetZoneObject($target);
    if($targetObj === null || $targetObj->removed) return;
    $fractals = ZoneSearch("myField", cardSubtypes: ["FRACTAL"]);
    if(empty($fractals)) return;
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip: "Sacrifice_a_Fractal_for_2_more_damage?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "BurstAsunderSacrifice|" . $source . "|" . $target, 1);
}
$customDQHandlers["BurstAsunderSacrifice"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $source = $parts[0];
    $target = $parts[1];
    $targetObj = GetZoneObject($target);
    if($targetObj === null || $targetObj->removed) return;
    $fractals = ZoneSearch("myField", cardSubtypes: ["FRACTAL"]);
    if(empty($fractals)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $fractals), 1, "Sacrifice_a_Fractal");
    DecisionQueueController::AddDecision($player, "CUSTOM", "BurstAsunderDoSacrifice|" . $source . "|" . $target, 1);
};
$customDQHandlers["BurstAsunderDoSacrifice"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $source = $parts[0];
    $target = $parts[1];
    DoSacrificeFighter($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    DealDamage($player, $source, $target, 2);
    BurstAsunderSacrificeFractals($player, $source, $target);
};

// Hulao Gate, Sun's Ascent (snke7lneo4): domain upkeep — banish fire from GY or sacrifice
$customDQHandlers["HulaoGateUpkeep"] = function($player, $parts, $lastDecision) {
    $fieldIdx = $parts[0];
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        MZMove($player, $lastDecision, "myBanish");
        DecisionQueueController::CleanupRemovedCards();
    } else {
        DoSacrificeFighter($player, "myField-" . $fieldIdx);
        DecisionQueueController::CleanupRemovedCards();
    }
};

// Immaterial Dissolution (55d9w9uuvq): accumulate up to 3 chosen non-regalia tokens (total cost <= 4)
// then destroy them all. Each call processes one MZMAYCHOOSE resolution.
$customDQHandlers["ImmaterialDissolveSelect"] = function($player, $parts, $lastDecision) {
    global $customDQHandlers;
    $count = intval(DecisionQueueController::GetVariable("dissolveCount"));
    $cost = intval(DecisionQueueController::GetVariable("dissolveCost"));
    $targets = DecisionQueueController::GetVariable("dissolveTargets");

    // Player passed (no selection) or we've hit 3
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS" || $count >= 3) {
        // Destroy all accumulated targets
        if(!empty($targets)) {
            foreach(explode(",", $targets) as $mz) {
                $mz = trim($mz);
                if($mz === "") continue;
                $dObj = GetZoneObject($mz);
                if($dObj !== null && !$dObj->removed) {
                    OnLeaveField($player, $mz);
                    MZMove($player, $mz, "myGraveyard");
                }
            }
            DecisionQueueController::CleanupRemovedCards();
        }
        return;
    }

    // Validate chosen target
    $chosenObj = GetZoneObject($lastDecision);
    if($chosenObj === null || $chosenObj->removed) return;
    $cardCost = intval(CardCost_reserve($chosenObj->CardID));
    $newCost = $cost + $cardCost;
    if($newCost > 4) {
        // Over budget — stop, destroy what we have
        if(!empty($targets)) {
            foreach(explode(",", $targets) as $mz) {
                $mz = trim($mz);
                if($mz === "") continue;
                $dObj = GetZoneObject($mz);
                if($dObj !== null && !$dObj->removed) {
                    OnLeaveField($player, $mz);
                    MZMove($player, $mz, "myGraveyard");
                }
            }
            DecisionQueueController::CleanupRemovedCards();
        }
        return;
    }

    // Accept this target
    $count++;
    $newTargets = empty($targets) ? $lastDecision : ($targets . "," . $lastDecision);
    DecisionQueueController::StoreVariable("dissolveCost", strval($newCost));
    DecisionQueueController::StoreVariable("dissolveCount", strval($count));
    DecisionQueueController::StoreVariable("dissolveTargets", $newTargets);

    if($count < 3) {
        // Offer another choice — rebuild remaining token list excluding already-chosen
        $chosenArr = explode(",", $newTargets);
        $tokens = [];
        foreach(["myField", "theirField"] as $zone) {
            $field = GetZone($zone);
            foreach($field as $i => $obj) {
                if($obj->removed) continue;
                $mz = $zone . "-" . $i;
                if(in_array($mz, $chosenArr)) continue;
                $ct = EffectiveCardType($obj);
                if(!PropertyContains($ct, "REGALIA") && IsToken($obj->CardID)) {
                    $tokens[] = $mz;
                }
            }
        }
        $tokens = FilterSpellshroudTargets($tokens);
        if(!empty($tokens)) {
            $remaining = 4 - $newCost;
            $affordable = array_filter($tokens, function($mz) use ($remaining) {
                $o = GetZoneObject($mz);
                return $o !== null && intval(CardCost_reserve($o->CardID)) <= $remaining;
            });
            if(!empty($affordable)) {
                $targetStr = implode("&", $affordable);
                DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1,
                    "Destroy_another_token?_(cost_remaining:" . $remaining . ")");
                DecisionQueueController::AddDecision($player, "CUSTOM", "ImmaterialDissolveSelect", 1);
                return;
            }
        }
    }

    // Done selecting — destroy all accumulated targets
    foreach(explode(",", $newTargets) as $mz) {
        $mz = trim($mz);
        if($mz === "") continue;
        $dObj = GetZoneObject($mz);
        if($dObj !== null && !$dObj->removed) {
            OnLeaveField($player, $mz);
            MZMove($player, $mz, "myGraveyard");
        }
    }
    DecisionQueueController::CleanupRemovedCards();
};

// Shining Marchador (lnl94ijbi1): pay (2) then put buff counter on target ally
function ShiningMarchadorPay($player) {
    $hand = ZoneSearch("myHand");
    if(count($hand) < 2) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $hand), 1, tooltip: "Choose_card_to_pay_(1/2)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ShiningMarchadorReserve1", 1);
}
$customDQHandlers["ShiningMarchadorReserve1"] = function($player, $parts, $lastDecision) {
    MZMove($player, $lastDecision, "myMemory");
    $hand = ZoneSearch("myHand");
    if(empty($hand)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $hand), 1, tooltip: "Choose_card_to_pay_(2/2)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ShiningMarchadorReserve2", 1);
};
$customDQHandlers["ShiningMarchadorReserve2"] = function($player, $parts, $lastDecision) {
    MZMove($player, $lastDecision, "myMemory");
    $allies = ZoneSearch("myField", ["ALLY"]);
    if(empty($allies)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $allies), 1, tooltip: "Choose_ally_for_buff_counter");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ShiningMarchadorBuff", 1);
};
$customDQHandlers["ShiningMarchadorBuff"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    AddCounters($player, $lastDecision, "buff", 1);
};

// Brisk Windtrotter (mala1jaff7): [Level 2+] second buff counter
function BriskWindtrotterLevel2Buff($player) {
    if(PlayerLevel($player) < 2) return;
    $mzID = DecisionQueueController::GetVariable("mzID");
    $allies = ZoneSearch("myField", ["ALLY"]);
    $others = array_values(array_filter($allies, fn($a) => $a !== $mzID));
    if(empty($others)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $others), 1, tooltip: "Choose_another_ally_for_buff_counter_(Level_2+)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "BriskWindtrotterBuff2", 1);
}
$customDQHandlers["BriskWindtrotterBuff2"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    AddCounters($player, $lastDecision, "buff", 1);
};

// Jin, Fate Defiant (zd8l14052j): Inherited Effect — +1 POWER to chosen Horse or Human ally
$customDQHandlers["JinFateDefiantBuff"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddTurnEffect($lastDecision, "zd8l14052j");
};

// Maiden of Shrouded Fog (wum3f33kay): [CB] whenever you activate from memory, buff a chosen phantasia ally
$customDQHandlers["MaidenShroudedFogBuff"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddCounters($player, $lastDecision, "buff", 1);
};

// Lu Xun, Pyre Strategist (xllhbjr20n): [CB] whenever enlighten removed from champion, may rest Lu Xun to empower 3
$customDQHandlers["LuXunRestEmpower"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $fi = intval(DecisionQueueController::GetVariable("LuXunFieldIdx"));
    global $playerID;
    $zone = ($player == $playerID) ? "myField" : "theirField";
    $mzLuXun = $zone . "-" . $fi;
    $luXunObj = GetZoneObject($mzLuXun);
    if($luXunObj === null || $luXunObj->removed || $luXunObj->CardID !== "xllhbjr20n") return;
    // Rest Lu Xun
    ExhaustCard($player, $mzLuXun);
    // Empower 3: champion gets +3 level until end of turn
    Empower($player, 3, "xllhbjr20n");
};

// Fang of Dragon's Breath (iebo5fu381): Jin Bonus REST ability — deal 2 damage to chosen unit
$customDQHandlers["FangDragonBreathDamage"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $weaponMZ = $parts[0];
    DealDamage($player, $weaponMZ, $lastDecision, 2);
};

// Beseeching Flourish (d60jobz3ct): Jin Bonus On Hit — materialize chosen Polearm weapon
$customDQHandlers["BeeseechingFlourishMaterialize"] = function($player, $parts, $lastDecision) {
    global $customDQHandlers;
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $customDQHandlers["MATERIALIZE"]($player, [], $lastDecision);
};

// Scorchfire Assassin (o4h8cfo21a): remove prep counters for +2 POWER each
function ScorchfireAssassinRemovePrep($player, $maxRemove) {
    if($maxRemove <= 0) return;
    $champMZ = DecisionQueueController::GetVariable("ScorchfireChampMZ");
    if(empty($champMZ)) return;
    $champObj = GetZoneObject($champMZ);
    if($champObj === null) return;
    $prepCount = GetCounterCount($champObj, "preparation");
    if($prepCount <= 0) return;
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
        tooltip: "Remove_a_preparation_counter_for_+2_POWER?");
    DecisionQueueController::AddDecision($player, "CUSTOM",
        "ScorchfireRemovePrep|" . $champMZ . "|" . ($maxRemove - 1), 1);
}
$customDQHandlers["ScorchfireRemovePrep"] = function($player, $params, $lastDecision) {
    if($lastDecision !== "YES") return;
    $champMZ = $params[0];
    $remaining = intval($params[1]);
    RemoveCounters($player, $champMZ, "preparation", 1);
    // Add +2 POWER TurnEffect to the attacker
    $attackerMZ = DecisionQueueController::GetVariable("CombatAttacker");
    if($attackerMZ !== null) {
        AddTurnEffect($attackerMZ, "o4h8cfo21a");
    }
    if($remaining > 0) {
        $champObj = GetZoneObject($champMZ);
        if($champObj !== null && GetCounterCount($champObj, "preparation") > 0) {
            DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
                tooltip: "Remove_another_preparation_counter_for_+2_POWER?");
            DecisionQueueController::AddDecision($player, "CUSTOM",
                "ScorchfireRemovePrep|" . $champMZ . "|" . ($remaining - 1), 1);
        }
    }
};

// Nature's Appeal (oj0oh7pjoq): choose card for material then put rest on bottom
function NaturesAppealMaterialAndBottom($player) {
    $remaining = ZoneSearch("myTempZone");
    if(empty($remaining)) return;
    if(count($remaining) == 1) {
        // Only one card left, it goes to material deck preserved
        MZMove($player, $remaining[0], "myMaterial");
        return;
    }
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $remaining), 1,
        tooltip: "Choose_card_for_material_deck_(preserved)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "NaturesAppealMaterial", 1);
}
$customDQHandlers["NaturesAppealMaterial"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    MZMove($player, $lastDecision, "myMaterial");
    // Put rest on bottom of deck
    $remaining = ZoneSearch("myTempZone");
    foreach($remaining as $r) {
        MZMove($player, $r, "myDeck");
    }
};

function RecollectionPhase() {
    // Recollection phase
    SetFlashMessage("Recollection Phase");
    $turnPlayer = &GetTurnPlayer();
    
    // --- Domain Recollection Upkeep ---
    // Process domain upkeep checks that trigger "at the beginning of your recollection phase".
    // Must run BEFORE memory is returned to hand, since the checks reveal memory cards.
    DomainRecollectionUpkeep($turnPlayer);

    // Kongming, Erudite Strategist (0i139x5eub): clear "may play until beginning of next turn" tags from banished cards
    $kongmingBanish = &GetBanish($turnPlayer);
    for($bi = 0; $bi < count($kongmingBanish); ++$bi) {
        if($kongmingBanish[$bi]->removed || !is_array($kongmingBanish[$bi]->TurnEffects)) continue;
        $kongmingBanish[$bi]->TurnEffects = array_values(array_filter(
            $kongmingBanish[$bi]->TurnEffects,
            fn($e) => !in_array($e, ["KONGMING_NORTH", "KONGMING_EAST", "KONGMING_SOUTH", "KONGMING_WEST"])
        ));
    }

    // Peaceful Reunion: clear attack-prevention at the beginning of the caster's next turn
    if(GlobalEffectCount($turnPlayer, "wr42i6eifn") > 0) {
        RemoveGlobalEffect($turnPlayer, "wr42i6eifn");
    }

    // Plea for Peace: clear attack tax at the beginning of the caster's next turn
    if(GlobalEffectCount($turnPlayer, "ir99sx6q3p") > 0) {
        RemoveGlobalEffect($turnPlayer, "ir99sx6q3p");
    }

    // Fatal Timepiece (6gvnta6qse): at the beginning of each player's recollection phase,
    // if that player did not materialize a card this turn → deal 2 unpreventable to their champion.
    $hasTimepiece = false;
    foreach(array_merge(GetField(1), GetField(2)) as $tpObj) {
        if(!$tpObj->removed && $tpObj->CardID === "6gvnta6qse" && !HasNoAbilities($tpObj)) {
            $hasTimepiece = true;
            break;
        }
    }
    if($hasTimepiece && MaterializeCallCount($turnPlayer) === 0) {
        $champField = &GetField($turnPlayer);
        for($ci = 0; $ci < count($champField); ++$ci) {
            if(!$champField[$ci]->removed && PropertyContains(EffectiveCardType($champField[$ci]), "CHAMPION")) {
                $champField[$ci]->Damage += 2;
                break;
            }
        }
    }
    
    // --- Foster Processing ---
    // "At the beginning of your recollection phase, if this ally hasn't been dealt damage
    // since the end of your previous turn, it becomes fostered."
    $field = &GetField($turnPlayer);
    $newlyFostered = [];
    for($i = 0; $i < count($field); ++$i) {
        if($field[$i]->removed) continue;
        if(!HasFoster($field[$i])) continue;
        $hasDamage = in_array("DAMAGED_SINCE_LAST_TURN", $field[$i]->TurnEffects);
        if(!$hasDamage) {
            $wasFostered = IsFostered($field[$i]);
            BecomeFostered($turnPlayer, "myField-" . $i);
            if(!$wasFostered) {
                $newlyFostered[] = $i;
            }
        } else {
            // Ally was damaged — remove fostered state if present
            $field[$i]->TurnEffects = array_values(array_filter($field[$i]->TurnEffects, fn($e) => $e !== "FOSTERED"));
        }
    }
    // Clear DAMAGED_SINCE_LAST_TURN from all field cards (reset for next cycle)
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed) {
            $field[$i]->TurnEffects = array_values(array_filter($field[$i]->TurnEffects, fn($e) => $e !== "DAMAGED_SINCE_LAST_TURN"));
        }
    }
    // Seasoned Shieldmaster (qsm4o98vn1): whenever an ally becomes fostered → draw into memory
    if(!empty($newlyFostered)) {
        for($i = 0; $i < count($field); ++$i) {
            if(!$field[$i]->removed && $field[$i]->CardID === "qsm4o98vn1" && !HasNoAbilities($field[$i])) {
                DrawIntoMemory($turnPlayer, count($newlyFostered));
                break;
            }
        }
    }
    // Fire OnFoster triggered abilities for newly fostered allies
    foreach($newlyFostered as $fieldIdx) {
        if(!$field[$fieldIdx]->removed) {
            OnFoster($turnPlayer, "myField-" . $fieldIdx);
        }
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
                case "h38lrj5221": // Distilled Atrophy: put an age counter
                case "tjot4nmxqs": // Wildgrowth Elixir: put an age counter
                    if(!HasNoAbilities($field[$i])) {
                        AddCounters($turnPlayer, "myField-" . $i, "age", 1);
                    }
                    break;
                case "k0hliqs2hi": // Liquid Amnesia: [CB] put an age counter
                    if(!HasNoAbilities($field[$i]) && IsClassBonusActive($turnPlayer, ["CLERIC"])) {
                        AddCounters($turnPlayer, "myField-" . $i, "age", 1);
                    }
                    break;
                case "xfpk9xycwz": // Alkahest: put an age counter on a Potion item you control
                    if(!HasNoAbilities($field[$i])) {
                        $potions = ZoneSearch("myField", ["ITEM"], cardSubtypes: ["POTION"]);
                        if(!empty($potions)) {
                            if(count($potions) == 1) {
                                AddCounters($turnPlayer, $potions[0], "age", 1);
                            } else {
                                DecisionQueueController::AddDecision($turnPlayer, "MZCHOOSE", implode("&", $potions), 1, tooltip:"Choose_Potion_for_age_counter");
                                DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "AlkahestAgeCounter", 1);
                            }
                        }
                    }
                    break;
                case "y5ttkat9hr": // Aqua Vitae: [CB] put an age counter on self
                    if(!HasNoAbilities($field[$i]) && IsClassBonusActive($turnPlayer, ["CLERIC"])) {
                        AddCounters($turnPlayer, "myField-" . $i, "age", 1);
                    }
                    break;
                case "8bls6g7xgw": // Fertile Grounds: summon a token copy of an Herb you control
                    if(!HasNoAbilities($field[$i])) {
                        FertileGroundsRecollection($turnPlayer);
                    }
                    break;
                case "b1w1mvu68a": // Convoking Slime: [CB] summon a copy of self rested
                    if(!HasNoAbilities($field[$i]) && IsClassBonusActive($turnPlayer, ["TAMER"])) {
                        MZAddZone($turnPlayer, "myField", "b1w1mvu68a");
                        $fieldRef = &GetField($turnPlayer);
                        $fieldRef[count($fieldRef) - 1]->Status = 1; // Rested
                    }
                    break;
                case "7dedg616r0": // Freydis, Master Tactician: [CB] put a tactic counter + Glimpse X
                    if(!HasNoAbilities($field[$i]) && IsClassBonusActive($turnPlayer, ["RANGER"])) {
                        AddCounters($turnPlayer, "myField-" . $i, "tactic", 1);
                        $tacticCount = GetCounterCount($field[$i], "tactic");
                        if($tacticCount > 0) {
                            Glimpse($turnPlayer, $tacticCount);
                        }
                    }
                    break;
                case "ljyevpmu6g": // Supply Drone: [CB] materialize a 0-cost Bullet from material deck
                    if(!HasNoAbilities($field[$i]) && IsClassBonusActive($turnPlayer, ["RANGER"])) {
                        SupplyDroneMaterialize($turnPlayer);
                    }
                    break;
                case "1dvdlhtiym": // Winbless Hurricane Farm: reveal 3 wind element cards from memory → summon Powercell
                    if(!HasNoAbilities($field[$i])) {
                        $windMem = ZoneSearch("myMemory", cardElements: ["WIND"]);
                        if(count($windMem) >= 3) {
                            DecisionQueueController::AddDecision($turnPlayer, "YESNO", "-", 1, tooltip:"Reveal_3_wind_cards_from_memory_to_summon_Powercell?");
                            DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "WinblessHurricaneFarmReveal", 1);
                        }
                    }
                    break;
                case "eanbrfnrow": // Blast Shield: deal 2 damage to your champion at recollection
                    if(!HasNoAbilities($field[$i])) {
                        DealChampionDamage($turnPlayer, 2);
                    }
                    break;
                case "3zb9p4lgdl": // Fractal of Rain: if imbued, target player mills 1
                    if(!HasNoAbilities($field[$i]) && in_array("IMBUED", $field[$i]->TurnEffects)) {
                        $champions = array_merge(ZoneSearch("myField", ["CHAMPION"]), ZoneSearch("theirField", ["CHAMPION"]));
                        if(!empty($champions)) {
                            DecisionQueueController::AddDecision($turnPlayer, "MZCHOOSE", implode("&", $champions), 1, tooltip:"Choose_player_to_mill_1_(Fractal_of_Rain)");
                            DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "FractalOfRainMill", 1);
                        }
                    }
                    break;
                case "tiymuyv3fp": // Waterveil Apostle: [CB][Memory 4+] gather at recollection
                    if(!HasNoAbilities($field[$i]) && IsClassBonusActive($turnPlayer, ["CLERIC"])) {
                        $memory = &GetMemory($turnPlayer);
                        if(count($memory) >= 4) {
                            Gather($turnPlayer);
                        }
                    }
                    break;
                case "dqqwey9xys": // Relic of Sunken Past: if 3+ water cards in GY, may sacrifice to draw
                    if(!HasNoAbilities($field[$i])) {
                        $waterGY = ZoneSearch("myGraveyard", cardElements: ["WATER"]);
                        if(count($waterGY) >= 3) {
                            DecisionQueueController::AddDecision($turnPlayer, "YESNO", "-", 1, tooltip:"Sacrifice_Relic_of_Sunken_Past_to_draw?");
                            DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "RelicOfSunkenPastSacrifice|$i", 1);
                        }
                    }
                    break;
                case "xp6qqi6vwf": // Tempus Stalker: [CB] put a buff counter at recollection
                    if(!HasNoAbilities($field[$i]) && IsClassBonusActive($turnPlayer, ["TAMER"])) {
                        AddCounters($turnPlayer, "myField-" . $i, "buff", 1);
                    }
                    break;
                case "4s1kmjeaks": // Floodbloom: banish top 2 cards of deck at recollection
                    if(!HasNoAbilities($field[$i])) {
                        for($fb = 0; $fb < 2; ++$fb) {
                            $deck = GetZone("myDeck");
                            if(!empty($deck)) {
                                MZMove($turnPlayer, "myDeck-0", "myBanish");
                            }
                        }
                    }
                    break;
                case "55d7vo62fc": // Zhou Yu, Enlightened Sage: [CB] if control Book/Scripture, enlighten counter on champion
                    if(!HasNoAbilities($field[$i]) && IsClassBonusActive($turnPlayer, ["MAGE"])) {
                        $bookScriptureFound = false;
                        for($zyi = 0; $zyi < count($field); ++$zyi) {
                            if($field[$zyi]->removed) continue;
                            if(PropertyContains(EffectiveCardSubtypes($field[$zyi]), "BOOK")
                               || PropertyContains(EffectiveCardSubtypes($field[$zyi]), "SCRIPTURE")) {
                                $bookScriptureFound = true;
                                break;
                            }
                        }
                        if($bookScriptureFound) {
                            $champField = GetZone("myField");
                            for($ci = 0; $ci < count($champField); ++$ci) {
                                if(!$champField[$ci]->removed && PropertyContains(EffectiveCardType($champField[$ci]), "CHAMPION")) {
                                    AddCounters($turnPlayer, "myField-" . $ci, "enlighten", 1);
                                    break;
                                }
                            }
                        }
                    }
                    break;
                case "89nl1vcn33": // Lycoria: deal 1 unpreventable damage to your champion
                    if($field[$i]->Controller == $turnPlayer && !HasNoAbilities($field[$i])) {
                        $champZone = $turnPlayer == $playerID ? "myField" : "theirField";
                        $champField = GetZone($champZone);
                        for($ci = 0; $ci < count($champField); ++$ci) {
                            if(PropertyContains(EffectiveCardType($champField[$ci]), "CHAMPION")) {
                                DealUnpreventableDamage($turnPlayer, $champZone . "-" . $i, $champZone . "-" . $ci, 1);
                                break;
                            }
                        }
                    }
                    break;
                case "i59eamoov0": // Baihua, Tranquil Lotus: each opponent recovers 1
                    if(!HasNoAbilities($field[$i])) {
                        $opponent = ($turnPlayer == 1) ? 2 : 1;
                        RecoverChampion($opponent, 1);
                    }
                    break;
                case "k5iv040vcq": // Washuru: banish a card from your graveyard
                    if(!HasNoAbilities($field[$i])) {
                        $gyCards = ZoneSearch("myGraveyard");
                        if(!empty($gyCards)) {
                            if(count($gyCards) == 1) {
                                MZMove($turnPlayer, $gyCards[0], "myBanish");
                            } else {
                                DecisionQueueController::AddDecision($turnPlayer, "MZCHOOSE", implode("&", $gyCards), 1, tooltip:"Banish_a_card_from_graveyard_(Washuru)");
                                DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "WashuruBanish", 1);
                            }
                        }
                    }
                    break;
                case "rzk3mjblse": // Nightshade: put a wither counter on target non-champion non-token object you control
                    if(!HasNoAbilities($field[$i])) {
                        $validTargets = [];
                        for($j = 0; $j < count($field); ++$j) {
                            if($field[$j]->removed) continue;
                            $ct = CardType($field[$j]->CardID);
                            if(PropertyContains($ct, "CHAMPION") || PropertyContains($ct, "TOKEN")) continue;
                            $validTargets[] = "myField-" . $j;
                        }
                        if(!empty($validTargets)) {
                            DecisionQueueController::AddDecision($turnPlayer, "MZCHOOSE", implode("&", $validTargets), 1,
                                tooltip:"Put_wither_counter_on_your_non-token_object_(Nightshade)");
                            DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "NightshadeWither", 1);
                        }
                    }
                    break;
                case "w822tmc0yc": // Zhang Liao, Bloodmonger: [CB] if champion has 0 damage, deal 20 unpreventable + draw 3
                    if(!HasNoAbilities($field[$i]) && IsClassBonusActive($turnPlayer, ["CLERIC", "WARRIOR"])) {
                        $champMZ = FindChampionMZ($turnPlayer);
                        if($champMZ !== null) {
                            $champObj = GetZoneObject($champMZ);
                            if($champObj !== null && $champObj->Damage === 0) {
                                DealUnpreventableDamage($turnPlayer, $champMZ, $champMZ, 20);
                                Draw($turnPlayer, 3);
                            }
                        }
                    }
                    break;
                case "prbwzihwyh": // Firebloom Flourish: [CB] deal 1 damage to target champion
                    if(!HasNoAbilities($field[$i]) && IsClassBonusActive($turnPlayer, CardClasses("prbwzihwyh"))) {
                        $allChamps = array_merge(ZoneSearch("myField", ["CHAMPION"]), ZoneSearch("theirField", ["CHAMPION"]));
                        if(!empty($allChamps)) {
                            DecisionQueueController::AddDecision($turnPlayer, "MZCHOOSE", implode("&", $allChamps), 1, "Firebloom:_Deal_1_damage_to_champion");
                            DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "FirebloomRecollation", 1);
                        }
                    }
                    break;
                default: break;
            }
        }
    }

    // Hidden Enclave (1vk8ao8bki): at the beginning of each player's recollection phase,
    // that player puts the top card of their deck into their graveyard.
    foreach(array_merge(GetField(1), GetField(2)) as $heObj) {
        if(!$heObj->removed && $heObj->CardID === "1vk8ao8bki" && !HasNoAbilities($heObj)) {
            MillCards($turnPlayer, "myDeck", "myGraveyard", 1);
            break;
        }
    }

    // Bathe in Light (d9zax2g20h): delayed recover 4 at beginning of next recollection
    if(GlobalEffectCount($turnPlayer, "BATHE_IN_LIGHT_RECOVER") > 0) {
        RecoverChampion($turnPlayer, 4);
        RemoveGlobalEffect($turnPlayer, "BATHE_IN_LIGHT_RECOVER");
    }

    // Planar Abyss (qexcwmx2ug): destroy all non-champion objects,
    // then if SC South, deal 10 to each champion opponent controls
    if(GlobalEffectCount($turnPlayer, "PLANAR_ABYSS_PENDING") > 0) {
        RemoveGlobalEffect($turnPlayer, "PLANAR_ABYSS_PENDING");
        // Destroy all non-champion objects on both fields
        for($p = 1; $p <= 2; ++$p) {
            $field = &GetField($p);
            for($fi = count($field) - 1; $fi >= 0; --$fi) {
                if($field[$fi]->removed) continue;
                if(!PropertyContains(EffectiveCardType($field[$fi]), "CHAMPION")) {
                    $ref = ($p == $turnPlayer ? "myField-" : "theirField-") . $fi;
                    AllyDestroyed($turnPlayer, $ref);
                }
            }
        }
        DecisionQueueController::CleanupRemovedCards();
        // If SC faces South, deal 10 to each champion opponent controls
        if(GetShiftingCurrents($turnPlayer) === "SOUTH") {
            $opponent = ($turnPlayer == 1) ? 2 : 1;
            $oppChampions = ZoneSearch("theirField", ["CHAMPION"]);
            foreach($oppChampions as $champMZ) {
                DealChampionDamage($opponent, 10);
            }
        }
    }

    // Suffocating Miasma (coxpnjvt9y): at the beginning of each opponent's recollection phase,
    // that player puts a debuff counter on an ally they control. If they don't, deal 2 unpreventable.
    $nonTurnPlayer = ($turnPlayer == 1) ? 2 : 1;
    $nonTurnField = GetField($nonTurnPlayer);
    foreach($nonTurnField as $smObj) {
        if(!$smObj->removed && $smObj->CardID === "coxpnjvt9y" && !HasNoAbilities($smObj)) {
            // turnPlayer is the opponent — they must debuff an ally or take 2 unpreventable
            $turnAllies = ZoneSearch("myField", ["ALLY"]);
            if(!empty($turnAllies)) {
                DecisionQueueController::AddDecision($turnPlayer, "MZMAYCHOOSE", implode("&", $turnAllies), 1, "Put_debuff_counter_on_an_ally_(or_take_2_unpreventable)");
                DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "SuffocatingMiasmaRecollection", 1);
            } else {
                $champMZ = FindChampionMZ($turnPlayer);
                if($champMZ !== null) {
                    DealUnpreventableDamage($turnPlayer, $champMZ, $champMZ, 2);
                }
            }
            break;
        }
    }

    // Xuchang, Frozen Citadel (xpb20rar4k): at the beginning of each opponent's recollection phase,
    // that player may banish a card with floating memory from their graveyard. If they don't,
    // the next card they activate this turn costs 2 more.
    foreach($nonTurnField as $xcObj) {
        if(!$xcObj->removed && $xcObj->CardID === "xpb20rar4k" && !HasNoAbilities($xcObj)) {
            $floatingGY = ZoneSearch("myGraveyard", floatingMemoryOnly: true);
            if(!empty($floatingGY)) {
                $floatingStr = implode("&", $floatingGY);
                DecisionQueueController::AddDecision($turnPlayer, "MZMAYCHOOSE", $floatingStr, 1, "Banish_floating-memory_card_or_next_activation_costs_2_more_(Xuchang)");
                DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "XuchangFrozenCitadel", 1);
            } else {
                // No floating memory cards to banish — apply cost increase
                AddGlobalEffects($turnPlayer, "XUCHANG_COST_INCREASE");
            }
            break;
        }
    }
    
    // --- Celestial Calling: check for banished cards tagged for free activation ---
    CelestialCallingRecollectionCheck($turnPlayer);

    // --- Arisanna, Astral Zenith (q3huqj5bba): once per turn free starcalling ---
    // Grant the free starcalling effect at the beginning of each of the player's turns.
    $field = &GetField($turnPlayer);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "q3huqj5bba" && !HasNoAbilities($field[$i])) {
            AddGlobalEffects($turnPlayer, "ARISANNA_FREE_STARCALLING");
            AddGlobalEffects($turnPlayer, "FREE_STARCALLING");
            break;
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
    SetFlashMessage("Main Phase");
    // --- Wither Upkeep ---
    // At the beginning of a player's main phase, if they control objects with wither counters,
    // for each such object, they sacrifice it unless they pay (1) per wither counter, then remove wither counters.
    $turnPlayer = &GetTurnPlayer();
    WitherUpkeep($turnPlayer);
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
    // Fleetfoot Filly (1hrgshgthu): whenever a player suppresses an object, put a buff counter on Fleetfoot Filly
    for($sp = 1; $sp <= 2; ++$sp) {
        $spField = &GetField($sp);
        for($si = 0; $si < count($spField); ++$si) {
            if(!$spField[$si]->removed && $spField[$si]->CardID === "1hrgshgthu" && !HasNoAbilities($spField[$si])) {
                global $playerID;
                $ffZone = $sp == $playerID ? "myField" : "theirField";
                AddCounters($sp, $ffZone . "-" . $si, "buff", 1);
            }
        }
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

    // FREEZING_ROUND_RETURN: return cards banished by Freezing Round to their owner's memory.
    $banish = GetZone("myBanish");
    for($sbi = count($banish) - 1; $sbi >= 0; --$sbi) {
        if(!$banish[$sbi]->removed && in_array("FREEZING_ROUND_RETURN", $banish[$sbi]->TurnEffects)) {
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

    // Cell Converter (eqhj1trn0y): At the beginning of your end phase, summon a Powercell token rested.
    $field = &GetField($turnPlayer);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "eqhj1trn0y" && !HasNoAbilities($field[$i])) {
            $pcObj = MZAddZone($turnPlayer, "myField", "qzzadf9q1v");
            $pcObj->Status = 1;
            $pcObj->Controller = $turnPlayer;
            $pcObj->Owner = $turnPlayer;
            break;
        }
    }

    // Alchemical Scripture (h9v2214upu): At the beginning of your end phase,
    // if you control four or more tokens, draw a card into your memory.
    $field = &GetField($turnPlayer);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "h9v2214upu" && !HasNoAbilities($field[$i])) {
            $tokens = ZoneSearch("myField", ["TOKEN"]);
            if(count($tokens) >= 4) {
                DrawIntoMemory($turnPlayer, 1);
            }
            break;
        }
    }

    // Overlord Mk III (sl7ddcgw05): At beginning of end phase, may banish an
    // Automaton from GY → put a buff counter on CARDNAME and draw a card.
    $field = &GetField($turnPlayer);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "sl7ddcgw05" && !HasNoAbilities($field[$i])) {
            $automatons = [];
            $gy = GetZone("myGraveyard");
            for($gi = 0; $gi < count($gy); ++$gi) {
                if(!$gy[$gi]->removed && PropertyContains(CardSubtypes($gy[$gi]->CardID), "AUTOMATON")) {
                    $automatons[] = "myGraveyard-" . $gi;
                }
            }
            if(!empty($automatons)) {
                $autoStr = implode("&", $automatons);
                DecisionQueueController::AddDecision($turnPlayer, "MZMAYCHOOSE", $autoStr, 1, tooltip:"Banish_an_Automaton_from_GY?_(Overlord_Mk_III)");
                DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "OverlordEndPhase|" . $i, 1);
            }
            break;
        }
    }

    // Master Alchemist (ltv5klryvf): Inherited Effect —
    // At the beginning of your end phase, you may sacrifice two Herbs with the same name to draw a card.
    if(ChampionHasInLineage($turnPlayer, "ltv5klryvf")) {
        $validHerbs = MasterAlchemistGetDuplicateHerbs();
        if(!empty($validHerbs)) {
            DecisionQueueController::AddDecision($turnPlayer, "YESNO", "-", 1, tooltip:"Sacrifice_two_same-name_Herbs_to_draw?_(Inherited:_Arisanna,_Master_Alchemist)");
            DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "MasterAlchemistEndPhaseYesNo", 1);
        }
    }

    // Promising Recruit (h57rcfw46q): [Level 2+] At beginning of end phase, put buff counter on CARDNAME
    $field = &GetField($turnPlayer);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "h57rcfw46q" && !HasNoAbilities($field[$i])) {
            if(PlayerLevel($turnPlayer) >= 2) {
                AddCounters($turnPlayer, "myField-" . $i, "buff", 1);
            }
        }
    }

    // Zhang Fei, Spirited Steel (qxnv0jqeym): [CB] At beginning of end phase,
    // if CARDNAME is the only ally you control, put a buff counter on CARDNAME
    $field = &GetField($turnPlayer);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "qxnv0jqeym" && !HasNoAbilities($field[$i])) {
            if(IsClassBonusActive($turnPlayer, ["WARRIOR"])) {
                $allyCount = count(ZoneSearch("myField", ["ALLY"]));
                if($allyCount == 1) {
                    AddCounters($turnPlayer, "myField-" . $i, "buff", 1);
                }
            }
            break;
        }
    }

    // Direwolf (jev2kkxuq2): At beginning of your end phase, sacrifice Direwolf
    $field = &GetField($turnPlayer);
    for($i = count($field) - 1; $i >= 0; --$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "jev2kkxuq2" && !HasNoAbilities($field[$i])) {
            AllyDestroyed($turnPlayer, "myField-" . $i);
            DecisionQueueController::CleanupRemovedCards();
        }
    }

    // SACRIFICE_NEXT_END_PHASE: sacrifice cards tagged by Incinerated Templar etc.
    // Skip if card also has ENTERED_THIS_TURN (it just entered; sacrifice on the NEXT end phase).
    $field = &GetField($turnPlayer);
    for($i = count($field) - 1; $i >= 0; --$i) {
        if(!$field[$i]->removed && in_array("SACRIFICE_NEXT_END_PHASE", $field[$i]->TurnEffects)
            && !in_array("ENTERED_THIS_TURN", $field[$i]->TurnEffects)) {
            AllyDestroyed($turnPlayer, "myField-" . $i);
            DecisionQueueController::CleanupRemovedCards();
        }
    }

    // Scorching Imperilment (aj7pz79wsp): At beginning of each player's end phase,
    // that player may discard a card. If they do, they draw a card.
    $hasImperilment = false;
    foreach(array_merge(GetField(1), GetField(2)) as $fObj) {
        if(!$fObj->removed && $fObj->CardID === "aj7pz79wsp" && !HasNoAbilities($fObj)) {
            $hasImperilment = true;
            break;
        }
    }
    if($hasImperilment) {
        $tpHand = &GetHand($turnPlayer);
        if(count($tpHand) > 0) {
            DecisionQueueController::AddDecision($turnPlayer, "MZMAYCHOOSE", ZoneMZIndices("myHand"), 1,
                tooltip:"Discard_a_card_to_draw_a_card?_(Scorching_Imperilment)");
            DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "ScorchingImperilmentDiscard", 1);
        }
    }

    // Shifting Currents Mastery: [Kongming Bonus] At beginning of end phase,
    // may change direction to a different direction of your choice.
    if(HasShiftingCurrents($turnPlayer) && IsKongmingBonus($turnPlayer)) {
        QueueShiftingCurrentsChoice($turnPlayer, "any", true);
    }

    // Whirlwind Reaper (x7yc0ije4d): At beginning of end phase, may remove a
    // preparation counter from champion to wake Whirlwind Reaper.
    $field = &GetField($turnPlayer);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "x7yc0ije4d" && !HasNoAbilities($field[$i])) {
            // Check if champion has any prep counters
            $champHasPrep = false;
            foreach($field as $champObj) {
                if(!$champObj->removed && PropertyContains(EffectiveCardType($champObj), "CHAMPION")) {
                    if(GetPrepCounterCount($champObj) >= 1) {
                        $champHasPrep = true;
                    }
                    break;
                }
            }
            if($champHasPrep) {
                DecisionQueueController::AddDecision($turnPlayer, "YESNO", "-", 1,
                    tooltip:"Remove_a_preparation_counter_to_wake_Whirlwind_Reaper?");
                DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "WhirlwindReaperWakeup|" . $i, 1);
            }
            break;
        }
    }

    // Ashwick Cremator (xwtkzqxfab): [CB] At beginning of end phase, if no cards in hand,
    // deal 2 damage to each champion.
    $field = &GetField($turnPlayer);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "xwtkzqxfab" && !HasNoAbilities($field[$i])) {
            if(IsClassBonusActive($turnPlayer, ["CLERIC"])) {
                $hand = &GetHand($turnPlayer);
                if(count($hand) == 0) {
                    DealChampionDamage(1, 2);
                    DealChampionDamage(2, 2);
                }
            }
            break;
        }
    }

    // Diao Chan, Dreaming Wish (pknaxnn0xo): Inherited — at the beginning of your end phase,
    // if glimmer counters < phantasia count, put the difference as glimmer counters
    if(ChampionHasInLineage($turnPlayer, "pknaxnn0xo")) {
        $champObj = GetPlayerChampion($turnPlayer);
        if($champObj !== null && !HasNoAbilities($champObj)) {
            $champMZ = FindChampionMZ($turnPlayer);
            $tpField = &GetField($turnPlayer);
            $phantasiaCount = 0;
            foreach($tpField as $pObj) {
                if(!$pObj->removed && PropertyContains(EffectiveCardType($pObj), "PHANTASIA") && $pObj->Controller == $turnPlayer) {
                    $phantasiaCount++;
                }
            }
            $glimmerCount = GetCounterCount($champObj, "glimmer");
            if($phantasiaCount > $glimmerCount) {
                AddCounters($turnPlayer, $champMZ, "glimmer", $phantasiaCount - $glimmerCount);
            }
        }
    }

    // Firebloom Flourish (prbwzihwyh): [Diao Chan Bonus] At the beginning of your end phase,
    // if your influence is four or less, draw a card into your memory and put a glimmer counter on your champion
    if(IsDiaoChanBonus($turnPlayer)) {
        $tpField2 = &GetField($turnPlayer);
        for($fbi = 0; $fbi < count($tpField2); ++$fbi) {
            if(!$tpField2[$fbi]->removed && $tpField2[$fbi]->CardID === "prbwzihwyh" && !HasNoAbilities($tpField2[$fbi])) {
                $influence = count(GetMemory($turnPlayer));
                if($influence <= 4) {
                    DrawIntoMemory($turnPlayer, 1);
                    $fbChampMZ = FindChampionMZ($turnPlayer);
                    if($fbChampMZ !== null) {
                        AddCounters($turnPlayer, $fbChampMZ, "glimmer", 1);
                    }
                }
            }
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
        case "jyrqgyj9vn": // Beguiling Bandit: [CB][Level 2+] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["ASSASSIN", "WARRIOR"]) && PlayerLevel($obj->Controller) >= 2) {
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
        case "wljhyokktb": // Emergent Dagger: [Class Bonus][Level 2+] +2 POWER
            if(IsClassBonusActive($obj->Controller, ["ASSASSIN"]) && PlayerLevel($obj->Controller) >= 2) {
                $power += 2;
            }
            break;
        case "jF1VuIR7a6": // Warrior's Longsword: [Class Bonus] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["WARRIOR"])) {
                $power += 1;
            }
            break;
        case "2s08hssegf": // Inert Sword: [Class Bonus] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["GUARDIAN"])) {
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
        case "r44lyrzo6o": // Sword Saint's Vow: +1 POWER per durability counter
            $power += GetCounterCount($obj, "durability");
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
        case "uesdu6o6ea": // Powered Bishop: [Memory 4+] +1 POWER
            $memory = &GetMemory($obj->Controller);
            if(count($memory) >= 4) $power += 1;
            break;
        case "v4vm4kj3q2": // Charged Alchemist: [CB] +1 POWER while you control a Powercell
            if(IsClassBonusActive($obj->Controller, ["MAGE"])) {
                global $playerID;
                $zone = $obj->Controller == $playerID ? "myField" : "theirField";
                if(!empty(ZoneSearch($zone, cardSubtypes: ["POWERCELL"]))) {
                    $power += 1;
                }
            }
            break;
        case "2tsn0ye3ae": // Allied Warpriestess: [Class Bonus] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["CLERIC", "GUARDIAN"])) $power += 1;
            break;
        case "1a49w5gmf7": // Intricate Longbow: [CB][Lv2+] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["RANGER"]) && PlayerLevel($obj->Controller) >= 2) {
                $power += 1;
            }
            break;
        case "h7iz4xkbq1": // Crescent Glaive: [CB][Lv2+] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["WARRIOR"]) && PlayerLevel($obj->Controller) >= 2) {
                $power += 1;
            }
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
        case "zk96yd609g": // Armored Valkyrie: [Class Bonus] +2 POWER while retaliating
            if(IsClassBonusActive($obj->Controller, ["GUARDIAN"])
                && DecisionQueueController::GetVariable("CombatRetaliator") !== null) {
                $power += 2;
            }
            break;
        case "z4pyx8bd7o": // Young Peacekeeper: +1 POWER while fostered
            if(IsFostered($obj)) $power += 1;
            break;
        case "oh300z2sns": // Magebane Lash: +1 POWER per lash counter on champion
            {
                $controller = $obj->Controller;
                $champField = &GetField($controller);
                foreach($champField as $champObj) {
                    if(!$champObj->removed && PropertyContains(EffectiveCardType($champObj), "CHAMPION")) {
                        $power += GetCounterCount($champObj, "lash");
                        break;
                    }
                }
            }
            break;
        case "5j36gn1b2s": // Shred to Ribbons: [CB] +3 POWER while attacking ally with 5+ LIFE
            if(IsClassBonusActive($obj->Controller, ["GUARDIAN", "WARRIOR"])) {
                $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
                if($combatTarget != "-" && $combatTarget != "") {
                    $targetObj = GetZoneObject($combatTarget);
                    if($targetObj !== null && PropertyContains(EffectiveCardType($targetObj), "ALLY") && ObjectCurrentHP($targetObj) >= 5) {
                        $power += 3;
                    }
                }
            }
            break;
        case "7lh9v2214u": // Captivating Cutthroat: [CB] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["ASSASSIN"])) {
                $power += 1;
            }
            break;
        case "7nau5sw9f8": // Synthetic Strike: +1 POWER while attacking Automaton unit
            {
                $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
                if($combatTarget != "-" && $combatTarget != "") {
                    $targetObj = GetZoneObject($combatTarget);
                    if($targetObj !== null && PropertyContains(CardSubtypes($targetObj->CardID), "AUTOMATON")) {
                        $power += 1;
                    }
                }
            }
            break;
        case "8iopvc8sug": // Contraband Revolver: [CB] +2 POWER while attacking a champion
            if(IsClassBonusActive($obj->Controller, ["RANGER"])) {
                $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
                if($combatTarget != "-" && $combatTarget != "") {
                    $targetObj = GetZoneObject($combatTarget);
                    if($targetObj !== null && PropertyContains(EffectiveCardType($targetObj), "CHAMPION")) {
                        $power += 2;
                    }
                }
            }
            break;
        case "alegbscxwj": // Charged Mannequin: +1 POWER while you control a Powercell
            {
                global $playerID;
                $zone = $obj->Controller == $playerID ? "myField" : "theirField";
                if(!empty(ZoneSearch($zone, cardSubtypes: ["POWERCELL"]))) {
                    $power += 1;
                }
            }
            break;
        case "chsbalegbs": // Impact Hammer: [CB] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["GUARDIAN"])) {
                $power += 1;
            }
            break;
        case "chnppup4iz": // Defender's Maul: [CB] [Level 2+] +2 POWER
            if(IsClassBonusActive($obj->Controller, ["GUARDIAN"]) && PlayerLevel($obj->Controller) >= 2) {
                $power += 2;
            }
            break;
        case "cxwjbqjdmt": // Krustallan Longsword: [CB] +1 POWER if 4+ water cards in graveyard
            if(IsClassBonusActive($obj->Controller, ["WARRIOR"])) {
                global $playerID;
                $gravZone = $obj->Controller == $playerID ? "myGraveyard" : "theirGraveyard";
                if(count(ZoneSearch($gravZone, cardElements: ["WATER"])) >= 4) {
                    $power += 1;
                }
            }
            break;
        case "ly4wiffei7": // Forgelight Blade: [Class Bonus] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["WARRIOR"])) {
                $power += 1;
            }
            break;
        case "rh0foylxnq": // Atmos Armor Type-Ares: [CB] +1 POWER per Atmos Shield ally you control
            if(IsClassBonusActive($obj->Controller, ["GUARDIAN"])) {
                global $playerID;
                $zone = $obj->Controller == $playerID ? "myField" : "theirField";
                $shieldField = GetZone($zone);
                $shieldCount = 0;
                foreach($shieldField as $fObj) {
                    if(!$fObj->removed && $fObj->CardID === "80yu75k0hl") {
                        $shieldCount++;
                    }
                }
                $power += $shieldCount;
            }
            break;
        case "n1uoy5ttka": // Vicious Slice: [Class Bonus] +1 POWER while attacking a Human
            if(IsClassBonusActive($obj->Controller, ["ASSASSIN"])) {
                $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
                if($combatTarget != "-" && $combatTarget != "") {
                    $targetObj = GetZoneObject($combatTarget);
                    if($targetObj !== null && PropertyContains(CardSubtypes($targetObj->CardID), "HUMAN")) {
                        $power += 1;
                    }
                }
            }
            break;
        case "4le7ehjyxs": // Aqueous Stallion: +3 POWER if 4+ water cards in graveyard
            {
                global $playerID;
                $gravZone = $obj->Controller == $playerID ? "myGraveyard" : "theirGraveyard";
                if(count(ZoneSearch($gravZone, cardElements: ["WATER"])) >= 4) {
                    $power += 3;
                }
            }
            break;
        case "gc18dq28my": // Xia Hou Dun, Gloryseeker: [CB] +1 POWER while you control a Sword or Bow weapon
            if(IsClassBonusActive($obj->Controller, ["WARRIOR", "RANGER"])) {
                global $playerID;
                $zone = $obj->Controller == $playerID ? "myField" : "theirField";
                $field = GetZone($zone);
                foreach($field as $fObj) {
                    if(!$fObj->removed && PropertyContains(CardType($fObj->CardID), "WEAPON")
                        && (PropertyContains(CardSubtypes($fObj->CardID), "SWORD") || PropertyContains(CardSubtypes($fObj->CardID), "BOW"))) {
                        $power += 1;
                        break;
                    }
                }
            }
            break;
        case "51l757wvez": // Royal Bear: [Class Bonus] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["TAMER"])) {
                $power += 1;
            }
            break;
        case "lve1my3486": // Sword Saint of Eventide: [Class Bonus] +1 POWER while 4+ water cards in graveyard
            if(IsClassBonusActive($obj->Controller, ["WARRIOR"])) {
                global $playerID;
                $gravZone = $obj->Controller == $playerID ? "myGraveyard" : "theirGraveyard";
                if(count(ZoneSearch($gravZone, cardElements: ["WATER"])) >= 4) {
                    $power += 1;
                }
            }
            break;
        case "59ueoujs9f": // Flamewing Fowl: [Class Bonus] +1 POWER while attacking a champion
            if(IsClassBonusActive($obj->Controller, ["TAMER"])) {
                $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
                if($combatTarget != "-" && $combatTarget != "") {
                    $targetObj = GetZoneObject($combatTarget);
                    if($targetObj !== null && PropertyContains(EffectiveCardType($targetObj), "CHAMPION")) {
                        $combatAttackerMZ = DecisionQueueController::GetVariable("CombatAttacker");
                        if($combatAttackerMZ !== null && $obj->GetMzID() === $combatAttackerMZ) {
                            $power += 1;
                        }
                    }
                }
            }
            break;
        case "td460e8ig0": // Heated Vengeance: +3 POWER as long as champion took damage this turn
            {
                global $playerID;
                $zone = $obj->Controller == $playerID ? "myField" : "theirField";
                $fld = GetZone($zone);
                foreach($fld as $fObj) {
                    if(!$fObj->removed && PropertyContains(EffectiveCardType($fObj), "CHAMPION") && $fObj->Controller == $obj->Controller) {
                        if(in_array("DAMAGED_SINCE_LAST_TURN", $fObj->TurnEffects)) {
                            $power += 3;
                        }
                        break;
                    }
                }
            }
            break;
        case "384b3yjlhu": // Axis Gale Scholar: +2 POWER while facing North
            if(GetShiftingCurrents($obj->Controller) === "NORTH") {
                $power += 2;
            }
            break;
        case "4kpotk5hvr": // Wushan Sentinel: +3 POWER while facing West and attacking a champion
            if(GetShiftingCurrents($obj->Controller) === "WEST") {
                $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
                $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
                if($combatTarget != "-" && $combatTarget != "" && $combatAttacker !== null && $obj->GetMzID() === $combatAttacker) {
                    $targetObj = GetZoneObject($combatTarget);
                    if($targetObj !== null && PropertyContains(EffectiveCardType($targetObj), "CHAMPION")) {
                        $power += 3;
                    }
                }
            }
            break;
        case "v5ppxyu1jm": // Nanyue Portsman: Equestrian — +1 POWER while you control a Horse ally
            {
                global $playerID;
                $zone = $obj->Controller == $playerID ? "myField" : "theirField";
                if(!empty(ZoneSearch($zone, ["ALLY"], cardSubtypes: ["HORSE"]))) {
                    $power += 1;
                }
            }
            break;
        case "dlvr8wunhg": // War Marshal: [CB] Equestrian — +1 POWER while you control a Horse ally
            if(IsClassBonusActive($obj->Controller, ["GUARDIAN"])) {
                global $playerID;
                $zone = $obj->Controller == $playerID ? "myField" : "theirField";
                if(!empty(ZoneSearch($zone, ["ALLY"], cardSubtypes: ["HORSE"]))) {
                    $power += 1;
                }
            }
            break;
        case "blyb6fd6vy": // Bloodbond Bladesworn: [CB] +1 POWER per 10 damage counters on champion
            if(IsClassBonusActive($obj->Controller, ["WARRIOR"])) {
                $champs = ZoneSearch($obj->Controller == $playerID ? "myField" : "theirField", ["CHAMPION"]);
                if(!empty($champs)) {
                    $champObj = GetZoneObject($champs[0]);
                    if($champObj !== null) {
                        $power += intdiv($champObj->Damage, 10);
                    }
                }
            }
            break;
        case "aws20fsihd": // Fervent Lancer: +2 POWER while a card is banished by it
            if(is_array($obj->Counters) && isset($obj->Counters['banished_card']) && $obj->Counters['banished_card']) {
                $power += 2;
            }
            break;
        case "2lukkhisu5": // Striking Illuminance: +1 POWER per luxem memory reveal
            $revealCount = 0;
            foreach($obj->TurnEffects as $te) {
                if($te === "2lukkhisu5_REVEAL_POWER") $revealCount++;
            }
            $power += $revealCount;
            break;
        case "a3v1ybmvpb": // Sunglory Sentinel: [CB] +2 POWER while fostered and attacking a champion
            if(IsFostered($obj) && IsClassBonusActive($obj->Controller, ["GUARDIAN"])) {
                $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
                $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
                if($combatTarget != "-" && $combatTarget != "" && $combatAttacker !== null && $obj->GetMzID() === $combatAttacker) {
                    $targetObj = GetZoneObject($combatTarget);
                    if($targetObj !== null && PropertyContains(EffectiveCardType($targetObj), "CHAMPION")) {
                        $power += 2;
                    }
                }
            }
            break;
        case "b23a85z88j": // Sun Jian, Wolvesbane: +2 POWER while attacking a Beast unit
            {
                $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
                $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
                if($combatTarget != "-" && $combatTarget != "" && $combatAttacker !== null && $obj->GetMzID() === $combatAttacker) {
                    $targetObj = GetZoneObject($combatTarget);
                    if($targetObj !== null && PropertyContains(CardSubtypes($targetObj->CardID), "BEAST")) {
                        $power += 2;
                    }
                }
            }
            break;
        case "fw8yvhf3mz": // Ma Chao, Lupine Huntress: [CB][Level 2+] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["TAMER"]) && PlayerLevel($obj->Controller) >= 2) {
                $power += 1;
            }
            break;
        case "hbt487eux7": // Maiden of Primal Virtue: +1 POWER per phantasia you control
            {
                global $playerID;
                $zone = $obj->Controller == $playerID ? "myField" : "theirField";
                $phantasiaCount = count(ZoneSearch($zone, ["PHANTASIA"]));
                $power += $phantasiaCount;
            }
            break;
        case "xrbffkghwt": // Ritai Berserker: +1 POWER while Shifting Currents face North
            if(GetShiftingCurrents($obj->Controller) === "NORTH") $power += 1;
            break;
        case "vkqzk1jik7": // Shackled Theurgist: +4 POWER while ephemeral
            if(IsEphemeral($obj)) $power += 4;
            break;
        case "lgl8pux7v9": // Ghost Hunter: +3 POWER while attacking an ephemeral ally
            {
                $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
                $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
                if($combatTarget != "-" && $combatTarget != "" && $combatTarget !== null
                    && $combatAttacker !== null && $obj->GetMzID() === $combatAttacker) {
                    $targetObj = GetZoneObject($combatTarget);
                    if($targetObj !== null && PropertyContains(EffectiveCardType($targetObj), "ALLY") && IsEphemeral($targetObj)) {
                        $power += 3;
                    }
                }
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
        // Seasoned Shieldmaster (qsm4o98vn1): [Class Bonus] fostered allies get +1 POWER
        if(PropertyContains(EffectiveCardType($obj), "ALLY") && IsFostered($obj)) {
            $appliedShieldmaster = false;
            foreach($field as $fieldObj) {
                if(!$fieldObj->removed && $fieldObj->CardID === "qsm4o98vn1" && !HasNoAbilities($fieldObj)
                    && IsClassBonusActive($obj->Controller, ["GUARDIAN"])) {
                    $power += 1;
                    $appliedShieldmaster = true;
                    break;
                }
            }
        }
        // Atmos Armor Type-Hermes (dlx7mdk0xh): [Level 1+] Other Automaton allies get +1 POWER
        if(PropertyContains(EffectiveCardType($obj), "ALLY") && PropertyContains(EffectiveCardSubtypes($obj), "AUTOMATON")) {
            foreach($field as $fieldObj) {
                if(!$fieldObj->removed && $fieldObj->CardID === "dlx7mdk0xh" && !HasNoAbilities($fieldObj)
                   && $obj->CardID !== "dlx7mdk0xh"
                   && PlayerLevel($obj->Controller) >= 1) {
                    $power += 1;
                    break;
                }
            }
        }
        // Phalanx Captain (rPpLwLPGaL): Other Human allies you control get +1 POWER as long as they're attacking
        if(PropertyContains(EffectiveCardType($obj), "ALLY") && PropertyContains(EffectiveCardSubtypes($obj), "HUMAN")) {
            $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
            if($combatAttacker !== null && $combatAttacker != "-" && $combatAttacker != "" && $obj->GetMzID() === $combatAttacker) {
                foreach($field as $fieldObj) {
                    if(!$fieldObj->removed && $fieldObj->CardID === "rPpLwLPGaL" && !HasNoAbilities($fieldObj)
                       && $obj->CardID !== "rPpLwLPGaL") {
                        $power += 1;
                        break;
                    }
                }
            }
        }
        // Halocline Scout (jntoa4h8re): [CB] Other allies get +1 POWER while attacking rested units
        if(PropertyContains(EffectiveCardType($obj), "ALLY")) {
            $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
            if($combatTarget != "-" && $combatTarget != "" && $combatTarget !== null) {
                $targetObj = GetZoneObject($combatTarget);
                if($targetObj !== null && isset($targetObj->Status) && $targetObj->Status == 1) {
                    foreach($field as $fieldObj) {
                        if(!$fieldObj->removed && $fieldObj->CardID === "jntoa4h8re" && !HasNoAbilities($fieldObj)
                           && $obj->CardID !== "jntoa4h8re"
                           && IsClassBonusActive($obj->Controller, ["ASSASSIN", "WARRIOR"])) {
                            $power += 1;
                            break;
                        }
                    }
                }
            }
        }
        // Adept Swordmaster (txgvf6xpkq): [Class Bonus] Weapons you control get +1 POWER
        if(PropertyContains(EffectiveCardType($obj), "WEAPON")) {
            foreach($field as $fieldObj) {
                if(!$fieldObj->removed && $fieldObj->CardID === "txgvf6xpkq" && !HasNoAbilities($fieldObj)
                   && IsClassBonusActive($obj->Controller, ["WARRIOR"])) {
                    $power += 1;
                    break;
                }
            }
        }
        // Sun Ce, Weaponsmaster (lvxsgng1a1): [CB] Warrior weapons you control get +1 POWER
        if(PropertyContains(EffectiveCardType($obj), "WEAPON")) {
            foreach($field as $fieldObj) {
                if(!$fieldObj->removed && $fieldObj->CardID === "lvxsgng1a1" && !HasNoAbilities($fieldObj)
                   && IsClassBonusActive($obj->Controller, ["WARRIOR"])) {
                    $power += 1;
                    break;
                }
            }
        }
        // Lumen Borealis (3ejd9yj9rl): [CB] Animal allies you control get +1 POWER
        if(PropertyContains(EffectiveCardType($obj), "ALLY") && PropertyContains(EffectiveCardSubtypes($obj), "ANIMAL")) {
            foreach($field as $fieldObj) {
                if(!$fieldObj->removed && $fieldObj->CardID === "3ejd9yj9rl" && !HasNoAbilities($fieldObj)
                   && IsClassBonusActive($obj->Controller, ["TAMER"])) {
                    $power += 1;
                    break;
                }
            }
        }
        // Courtside Beastkeeper (o6gy3kq2lc): [CB] Beast allies you control get +1 POWER
        if(PropertyContains(EffectiveCardType($obj), "ALLY") && PropertyContains(EffectiveCardSubtypes($obj), "BEAST")) {
            foreach($field as $fieldObj) {
                if(!$fieldObj->removed && $fieldObj->CardID === "o6gy3kq2lc" && !HasNoAbilities($fieldObj)
                   && IsClassBonusActive($obj->Controller, ["TAMER"])) {
                    $power += 1;
                    break;
                }
            }
        }
        // Direwolf Alpha (5n874ubgai): [CB][Level 2+] Other Wolf objects get +1 POWER
        if(PropertyContains(EffectiveCardSubtypes($obj), "WOLF")) {
            foreach($field as $fieldObj) {
                if(!$fieldObj->removed && $fieldObj->CardID === "5n874ubgai" && !HasNoAbilities($fieldObj)
                   && $obj->CardID !== "5n874ubgai"
                   && IsClassBonusActive($obj->Controller, ["TAMER"])
                   && PlayerLevel($obj->Controller) >= 2) {
                    $power += 1;
                    break;
                }
            }
        }
        // Wingpeak Patriarch (wov58exji1): Other Bird objects you control get +1 POWER
        if(PropertyContains(EffectiveCardSubtypes($obj), "BIRD")) {
            foreach($field as $fieldObj) {
                if(!$fieldObj->removed && $fieldObj->CardID === "wov58exji1" && !HasNoAbilities($fieldObj)
                   && $obj->CardID !== "wov58exji1") {
                    $power += 1;
                    break;
                }
            }
        }
        // Dian Wei, Valorant Fury (h42l1w67ry): [CB] Deluge 6 — other Human allies get +1 POWER
        if(PropertyContains(EffectiveCardType($obj), "ALLY") && PropertyContains(EffectiveCardSubtypes($obj), "HUMAN")) {
            foreach($field as $fieldObj) {
                if(!$fieldObj->removed && $fieldObj->CardID === "h42l1w67ry" && !HasNoAbilities($fieldObj)
                   && $obj->CardID !== "h42l1w67ry"
                   && IsClassBonusActive($obj->Controller, ["WARRIOR"])
                   && DelugeAmount($obj->Controller) >= 6) {
                    $power += 1;
                    break;
                }
            }
        }
        // Sunken Battle Priest (sm68d3we64): [CB] Other ephemeral allies you control get +1 POWER
        if(PropertyContains(EffectiveCardType($obj), "ALLY") && IsEphemeral($obj)) {
            foreach($field as $fieldObj) {
                if(!$fieldObj->removed && $fieldObj->CardID === "sm68d3we64" && !HasNoAbilities($fieldObj)
                   && $obj->CardID !== "sm68d3we64"
                   && IsClassBonusActive($obj->Controller, ["CLERIC", "WARRIOR"])) {
                    $power += 1;
                    break;
                }
            }
        }
        // Blighted Jewel (hbpu4fo8oo): Each ephemeral ally you control gets +1 POWER as long as it entered this turn
        if(PropertyContains(EffectiveCardType($obj), "ALLY") && IsEphemeral($obj)
           && in_array("ENTERED_THIS_TURN", $obj->TurnEffects)) {
            foreach($field as $fieldObj) {
                if(!$fieldObj->removed && $fieldObj->CardID === "hbpu4fo8oo" && !HasNoAbilities($fieldObj)) {
                    $power += 1;
                    break;
                }
            }
        }
    }
    // General at Arms (9m72c8x9oh): [CB] Polearm attack cards get +2 POWER
    if(PropertyContains(EffectiveCardType($obj), "ATTACK") && PropertyContains(CardSubtypes($obj->CardID), "POLEARM")) {
        $controller = $obj->Controller ?? null;
        if($controller !== null && $controller > 0) {
            global $playerID;
            $zone = $controller == $playerID ? "myField" : "theirField";
            $field = GetZone($zone);
            foreach($field as $fieldObj) {
                if(!$fieldObj->removed && $fieldObj->CardID === "9m72c8x9oh" && !HasNoAbilities($fieldObj)
                   && IsClassBonusActive($controller, ["WARRIOR"])) {
                    $power += 2;
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
            case "7cx66hjlgx": // Verdigris Decree: target ally gets +2 POWER until end of turn
                $power += 2;
                break;
            case "ipl6gt7lh9-debuff": // Cerulean Decree: target unit's attacks get -3 POWER until end of turn
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
            case "f8urrqtjot": // Turbulent Bullet: [CB] On Hit: target ally gets +1 POWER until end of turn
                $power += 1;
                break;
            case "6fxxgmuesd": // Icebound Slam: +5 POWER from OnAttack water graveyard condition
                $power += 5;
                break;
            case "yevpmu6gvn_POWER": // Tonoris, Might of Humanity: +3 POWER on next attack
                $power += 3;
                break;
            case "vnta6qsesw_POWER": // Take Aim: +2 POWER on next attack
                $power += 2;
                break;
            case "5ramr16052_POWER": // Jin, Zealous Maverick: +1 POWER on next attack
                $power += 1;
                break;
            case "dfchplzf6m_POWER": // Ingress of Sanguine Ire: +3 POWER on first attack
                $power += 3;
                break;
            case "ATTUNE_FLAMES_BUFF": // Attune with Flames: +5 POWER until end of next turn
                $power += 5;
                break;
            case "1wl8ao8bls": // Carter, Synthetic Reaper: sacrificed ally On Enter -> +2 POWER until end of turn
                $power += 2;
                break;
            case "bscxwjbqjd": // Sharpen Blade: target Dagger +2 POWER until end of turn
                $power += 2;
                break;
            case "0k0p6n5nr7": // Scorching Strafe: target ally +2 POWER until end of turn
                $power += 2;
                break;
            case "lwabipl6gt_POWER": // Calamity Cannon: first Gun attack +10 POWER
                $power += 10;
                break;
            case "o6eanbrfnr": // Reprogram: -1 POWER until end of turn
                $power -= 1;
                break;
            case "pk9xycwz9g-power": // Cell Handler: -1 POWER until end of turn
                $power -= 1;
                break;
            case "qzzadf9q1v": // Powercell: +1 POWER until end of turn
                $power += 1;
                break;
            case "qzzadf9q1v-2": // Powercell: +1 POWER until end of turn (stacks to +2)
                $power += 1;
                break;
            case "ln926ymxdc": // Fraternal Garrison: +1 POWER until end of turn (from ally entering)
                $power += 1;
                break;
            case "5v598k3m1w": // Suzaku's Command: +2 POWER until end of turn
                $power += 2;
                break;
            case "5v598k3m1w-SHENJU": // Suzaku's Command (Shenju): +4 POWER until end of turn
                $power += 4;
                break;
            case "zd8l14052j": // Jin, Fate Defiant (Inherited Effect): +1 POWER until end of turn
                $power += 1;
                break;
            case "o4h8cfo21a": // Scorchfire Assassin: +2 POWER per prep counter removed
                $power += 2;
                break;
            case "smr2rn78qo": // Invective Instruction: +3 POWER until end of turn
                $power += 3;
                break;
            case "spijrps4ny": // Inner Court Schemer: +2 POWER from prep counter removal
                $power += 2;
                break;
            case "si9ux3ak6o": // Razor Broadhead: +3 POWER while attacker is distant
                $power += 3;
                break;
            case "3bxtj3te9i": // Combat Training: +2 POWER (or +3 if unique) until end of turn
                if(PropertyContains(EffectiveCardType($obj), "UNIQUE")) {
                    $power += 3;
                } else {
                    $power += 2;
                }
                break;
            case "welp9q7c5l": // Inundating Clash: [CB] +3 POWER while attacking rested unit
                $power += 3;
                break;
            case "vlno9ankzi": // Oath of the Sakura: +2 POWER until end of turn (exactly 3 unique allies)
                $power += 2;
                break;
            case "wjzg76zofp": // Temper in Flames: +1 POWER until end of turn
                $power += 1;
                break;
            case "lpy7ie4v8n": // Sword Saint of Everflame: +2 POWER until end of turn
                $power += 2;
                break;
            case "9f0nsj62l6-POWER": // Apprentice Aeromancer: [CB] wind spell trigger +1 POWER until EOT
                $power += 1;
                break;
            case "1i2luu7dft": // Wulin Lancer: +2 POWER from Shifting Currents N→W transition
                $power += 2;
                break;
            case "mvgmaalpko": // Flamelash Beastmaster: [CB] +3 POWER until end of turn
                $power += 3;
                break;
            case "xgi39z49tu": // Pluming Crescendo: Animals get +1 POWER until end of turn
                $power += 1;
                break;
            case "y5koddlyv8_POWER": // Undying Dreams: +1 POWER until end of turn
                $power += 1;
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
                // Infusion of Crescent Jade (91jnc6v71t): +X POWER to target Polearm weapon
                if(strpos($effectID, "91jnc6v71t-") === 0) {
                    $power += intval(substr($effectID, strlen("91jnc6v71t-")));
                }
                // Fiery Swing (ijkyboiopv): +X POWER from banished fire graveyard cards
                if(strpos($effectID, "ijkyboiopv-") === 0) {
                    $power += intval(substr($effectID, strlen("ijkyboiopv-")));
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
            case "eanbrfnrow": // Blast Shield: [CB] linked ally gets +2 POWER
                if(IsClassBonusActive($obj->Controller, ["GUARDIAN"])) {
                    $power += 2;
                }
                break;
            case "0cnn1eh85y": // Sheath of Faceted Lapis: [CB][Lv2+] linked weapon gets +2 POWER
                if(IsClassBonusActive($obj->Controller, ["WARRIOR"]) && PlayerLevel($obj->Controller) >= 2) {
                    $power += 2;
                }
                break;
            case "iebo5fu381": // Fang of Dragon's Breath: linked weapon gets +2 POWER
                $power += 2;
                break;
            case "bhhdb7x044": // Tricky Chimps: [CB] On Enter +2 POWER until end of turn
                $power += 2;
                break;
            case "b65hiv400w": // Ash Filcher: [CB] On Attack banish fire from GY +2 POWER
                $power += 2;
                break;
            case "az2b8nfh95": // Primal Whip: [CB][Lv2+] On Attack +1 POWER to non-Human ally
                $power += 1;
                break;
            case "oh5n2sjk0u": // Tailwind's Blessing: allies get +1 POWER until EOT
                $power += 1;
                break;
            case "jgyx38zpl0-east": // Bagua East: allies get +2 POWER until EOT
                $power += 2;
                break;
            case "aKgdkLSBza": // Wilderness Harpist: +1 level (actually power) from Melody/Harmony activation
                $power += 1;
                break;
            case "yrm3xibmoz": // Stolid Vanguard: Equestrian On Enter +2 POWER until end of turn
                $power += 2;
                break;
            case "qry41lw9n0": // Blazing Bowman: On Enter banish fire from GY +2 POWER
                $power += 2;
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
    // Windpiercer (hreqhj1trn): On Attack reveal — if wind element, +2 POWER
    if(in_array("hreqhj1trn-power", $obj->TurnEffects)) {
        $power += 2;
    }
    // Usurp the Winds (ulzrh3pmxq): +1 POWER until end of turn
    if(in_array("ulzrh3pmxq", $obj->TurnEffects)) {
        $power += 1;
    }
    // Ranged N: while this unit is distant, its attacks get +N POWER
    if(IsDistant($obj)) {
        $rangedValue = GetRangedValue($obj);
        if($rangedValue > 0) $power += $rangedValue;
    }
    // Retort N: while this card is retaliating, it gets +N POWER
    if(DecisionQueueController::GetVariable("CombatRetaliator") !== null && HasRetort($obj)) {
        $power += GetRetortValue($obj);
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
            case "yfzk96yd60": // Empowering Prayer: +2 level until end of turn
                $cardLevel += 2;
                break;
            case "INFUSION_STARLIGHT": // Potion Infusion: Starlight — +4 level until end of turn
                $cardLevel += 4;
                break;
            case "9g44vm5kt3": // Empowering Tincture sacrifice: +2 level until end of turn
                $cardLevel += 2;
                break;
            case "a01pyxwo25": // Kongming, Ascetic Vice: Empower 3 (+3 level until end of turn)
                $cardLevel += 3;
                break;
            case "pmx99jrukm": // Ruinous Pillars of Qidao: Empower 2 (+2 level until end of turn)
                $cardLevel += 2;
                break;
            case "9f0nsj62l6": // Apprentice Aeromancer: Empower 2 (+2 level until end of turn)
                $cardLevel += 2;
                break;
            case "zeig1e49wb": // Solar Pinnacle: Empower 2 (+2 level until end of turn)
                $cardLevel += 2;
                break;
            case "n06isycm60": // Pupil of Sacred Flames: Empower 2 (+2 level until end of turn)
                $cardLevel += 2;
                break;
            case "szeb8zzj86": // Fractal of Mana: Empower 1 (+1 level until end of turn)
                $cardLevel += 1;
                break;
            case "sq0ou8vas3": // Tome of Sorcery: Empower 1 (+1 level until end of turn)
                $cardLevel += 1;
                break;
            case "to1pmvo54d": // Mnemonic Charm: Empower 2 (+2 level until end of turn)
                $cardLevel += 2;
                break;
            case "qb6zhphtw6": // Rainweaver Mage: Empower 4 (+4 level until end of turn)
                $cardLevel += 4;
                break;
            case "zmoegdo111": // Sempiternal Sage: Empower 3 (+3 level until end of turn)
                $cardLevel += 3;
                break;
            case "xllhbjr20n": // Lu Xun, Pyre Strategist: Empower 3 (+3 level until end of turn)
                $cardLevel += 3;
                break;
            default:
                // Cloudstone Orb (ygqehvpblj): Empower X, encoded as "ygqehvpblj-N"
                if(strpos($effectID, "ygqehvpblj-") === 0) {
                    $cardLevel += intval(substr($effectID, strlen("ygqehvpblj-")));
                }
                // Erupting Rhapsody (dBAdWMoPEz): +1 level per banished card, encoded as "dBAdWMoPEz-N"
                if(strpos($effectID, "dBAdWMoPEz-") === 0) {
                    $cardLevel += intval(substr($effectID, strlen("dBAdWMoPEz-")));
                }
                // Channel Manifold Desire (kywpjf1b4k): Empower X+2, encoded as "kywpjf1b4k-N"
                if(strpos($effectID, "kywpjf1b4k-") === 0) {
                    $cardLevel += intval(substr($effectID, strlen("kywpjf1b4k-")));
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
        // Acerbica (7ax4ywyv19): Champions you control get -1 level (per instance)
        {
            global $playerID;
            $zone = $obj->Controller == $playerID ? "myField" : "theirField";
            $acerbicaField = GetZone($zone);
            foreach($acerbicaField as $aObj) {
                if(!$aObj->removed && $aObj->CardID === "7ax4ywyv19" && !HasNoAbilities($aObj)) {
                    $cardLevel -= 1;
                }
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
                case "v4vm4kj3q2": // Charged Alchemist: [CB] +1 level while you control a Powercell
                    if(IsClassBonusActive($obj->Controller, ["MAGE"])) {
                        if(!empty(ZoneSearch($zone, cardSubtypes: ["POWERCELL"]))) {
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
        case "z4pyx8bd7o": // Young Peacekeeper: +1 LIFE while fostered
            if(IsFostered($obj)) $cardLife += 1;
            break;
        case "3kwkn38b7v": // Tidebreaker Sentinel: [CB] +2 LIFE while fostered
            if(IsFostered($obj) && IsClassBonusActive($obj->Controller, ["GUARDIAN"])) {
                $cardLife += 2;
            }
            break;
        case "4ilomec3u3": // Sunblessed Gazelle: +X LIFE where X is highest opponent influence
            {
                global $playerID;
                $opponent = ($obj->Controller == 1) ? 2 : 1;
                $oppHand = &GetHand($opponent);
                $oppMem = &GetMemory($opponent);
                $oppInfluence = count($oppHand) + count($oppMem);
                $cardLife += $oppInfluence;
            }
            break;
        case "xhi5jnsl7d": // Embershield Keeper: [Class Bonus] +2 LIFE while fostered
            if(IsClassBonusActive($obj->Controller, ["GUARDIAN"]) && IsFostered($obj)) {
                $cardLife += 2;
            }
            break;
        case "51l757wvez": // Royal Bear: [Class Bonus] +1 LIFE
            if(IsClassBonusActive($obj->Controller, ["TAMER"])) {
                $cardLife += 1;
            }
            break;
        case "c8z5ntioqs": // Determined Spearman: [Level 1+] +1 LIFE
            if(PlayerLevel($obj->Controller) >= 1) {
                $cardLife += 1;
            }
            break;
        case "dlvr8wunhg": // War Marshal: [CB] Equestrian — +1 LIFE while you control a Horse ally
            if(IsClassBonusActive($obj->Controller, ["GUARDIAN"])) {
                global $playerID;
                $zone = $obj->Controller == $playerID ? "myField" : "theirField";
                if(!empty(ZoneSearch($zone, ["ALLY"], cardSubtypes: ["HORSE"]))) {
                    $cardLife += 1;
                }
            }
            break;
        case "hbt487eux7": // Maiden of Primal Virtue: +1 LIFE per phantasia you control
            {
                global $playerID;
                $zone = $obj->Controller == $playerID ? "myField" : "theirField";
                $phantasiaCount = count(ZoneSearch($zone, ["PHANTASIA"]));
                $cardLife += $phantasiaCount;
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
        // Seasoned Shieldmaster (qsm4o98vn1): [Class Bonus] fostered allies get +1 LIFE
        if(PropertyContains(EffectiveCardType($obj), "ALLY") && IsFostered($obj)) {
            foreach($field as $fieldObj) {
                if(!$fieldObj->removed && $fieldObj->CardID === "qsm4o98vn1" && !HasNoAbilities($fieldObj)
                    && IsClassBonusActive($obj->Controller, ["GUARDIAN"])) {
                    $cardLife += 1;
                    break;
                }
            }
        }
        // Axis Gale Scholar (384b3yjlhu): while facing South, allies you control get +1 LIFE
        if(PropertyContains(EffectiveCardType($obj), "ALLY")) {
            foreach($field as $fieldObj) {
                if(!$fieldObj->removed && $fieldObj->CardID === "384b3yjlhu" && !HasNoAbilities($fieldObj)
                    && GetShiftingCurrents($obj->Controller) === "SOUTH") {
                    $cardLife += 1;
                    break;
                }
            }
        }
        // Seaguard Bulwark (acmde97dbu): while facing East, +2 LIFE
        if($obj->CardID === "acmde97dbu" && GetShiftingCurrents($obj->Controller) === "EAST") {
            $cardLife += 2;
        }
        // Lumen Borealis (3ejd9yj9rl): [CB] Animal allies you control get +1 LIFE
        if(PropertyContains(EffectiveCardType($obj), "ALLY") && PropertyContains(EffectiveCardSubtypes($obj), "ANIMAL")) {
            foreach($field as $fieldObj) {
                if(!$fieldObj->removed && $fieldObj->CardID === "3ejd9yj9rl" && !HasNoAbilities($fieldObj)
                   && IsClassBonusActive($obj->Controller, ["TAMER"])) {
                    $cardLife += 1;
                    break;
                }
            }
        }
    }
    // Fractured Crown (suo6gb0op3): [Class Bonus] champion gets +2 LIFE per unique ally card in GY
    if(PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fieldObj) {
            if($fieldObj != null && !$fieldObj->removed && $fieldObj->CardID === "suo6gb0op3") {
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
    // Inherited Effects: check champion lineage for curse effects
    if(PropertyContains(EffectiveCardType($obj), "CHAMPION") && is_array($obj->Subcards)) {
        foreach($obj->Subcards as $lineageCardID) {
            if($lineageCardID === "8tuhuy4xip") { // Load Soul: -2 LIFE
                $cardLife -= 2;
            }
            // Curse cards with Inherited Effect: -2 LIFE
            if($lineageCardID === "oqk2c7wklz" // Shadecursed Hunter
            || $lineageCardID === "vdxi74wa4x" // Violet Haze
            || $lineageCardID === "6g7xgwve1d" // Demon's Aim
            ) {
                $cardLife -= 2;
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
            case "75uhspxqme": // Cavalier Rescue: +3 LIFE until end of turn
                $cardLife += 3;
                break;
            case "ic1ahsmwd0": // Lumbering Steed: +1 LIFE until end of turn
                $cardLife += 1;
                break;
            case "yhu0djqlp8": // Lead with Force: +1 LIFE until end of turn
                $cardLife += 1;
                break;
            case "kyhl7zy5yj_LIFE": // Tidal Tirade: +1 LIFE until end of turn
                $cardLife += 1;
                break;
            case "90i1prp63s": // Evanescent Winds: Phantasia allies get +2 LIFE until end of turn
                $cardLife += 2;
                break;
            case "ysj63dw50a": // Convalescing Mare: Other allies get +1 LIFE until end of turn
                $cardLife += 1;
                break;
            default: break;
        }
    }
    // Keeper of the Wild (9krp8brw64): +2 LIFE until end of turn
    if(in_array("9krp8brw64", $obj->TurnEffects)) {
        $cardLife += 2;
    }
    // Stand Fast (ao1cfkhbp6): +1 LIFE until beginning of next turn
    if(in_array("ao1cfkhbp6", $obj->TurnEffects)) {
        $cardLife += 1;
    }
    // Sage Protection (fqsa372jii): +1 LIFE until end of turn
    if(in_array("fqsa372jii", $obj->TurnEffects)) {
        $cardLife += 1;
    }
    // Genbu's Command (jjwp945rlb): +3 LIFE until end of turn
    if(in_array("jjwp945rlb", $obj->TurnEffects)) {
        $cardLife += 3;
    }
    // Usurp the Winds (ulzrh3pmxq): +1 LIFE until end of turn
    if(in_array("ulzrh3pmxq_LIFE", $obj->TurnEffects)) {
        $cardLife += 1;
    }
    // Lively Chorale (x1c9ob6jva): +3 LIFE until end of turn
    if(in_array("x1c9ob6jva", $obj->TurnEffects)) {
        $cardLife += 3;
    }
    // Shackled Theurgist (vkqzk1jik7): +2 LIFE until end of turn (from On Death return)
    if(in_array("vkqzk1jik7_LIFE", $obj->TurnEffects)) {
        $cardLife += 2;
    }
    // Undying Dreams (y5koddlyv8): +1 LIFE until end of turn
    if(in_array("y5koddlyv8_LIFE", $obj->TurnEffects)) {
        $cardLife += 1;
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
        // Mandate of Honor (5ckzgqa186): if controller has a unique ally, players with influence 8+ can't draw
        $mandateBlocked = false;
        for($mp = 1; $mp <= 2; ++$mp) {
            $mField = GetField($mp);
            $hasMandateOnField = false;
            $hasUniqueAlly = false;
            foreach($mField as $mObj) {
                if($mObj->removed) continue;
                if($mObj->CardID === "5ckzgqa186" && !HasNoAbilities($mObj)) $hasMandateOnField = true;
                if(PropertyContains(EffectiveCardType($mObj), "ALLY") && PropertyContains(EffectiveCardType($mObj), "UNIQUE")) $hasUniqueAlly = true;
            }
            if($hasMandateOnField && $hasUniqueAlly) {
                $pHand = &GetHand($player);
                $pMem = &GetMemory($player);
                $influence = count($pHand) + count($pMem);
                if($influence >= 8) {
                    $mandateBlocked = true;
                    break;
                }
            }
        }
        if($mandateBlocked) {
            return; // Draw prevented by Mandate of Honor
        }
        if(count($zone) == 0) {
            return;
        }
        $card = array_shift($zone);
        array_push($hand, $card);

        // Track per-card draw count for this turn
        $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
        $_ti["Draw"][$player] = ($_ti["Draw"][$player] ?? 0) + 1;
        SetMacroTurnIndex(json_encode($_ti));

        // Creeping Torment (zrplywc08c) Inherited Effect:
        // "Whenever you draw your 2nd card each turn, deal 2 unpreventable to this champion."
        if($_ti["Draw"][$player] == 2) {
            CreepingTormentDrawTrigger($player);
        }
        // Delusional Vapors (2ghdzy9tz7): whenever opponent draws this turn, mill 4
        {
            $pChamps = ZoneSearch($player == $GLOBALS['playerID'] ? "myField" : "theirField", ["CHAMPION"]);
            foreach($pChamps as $champMZ) {
                $champObj = GetZoneObject($champMZ);
                if($champObj !== null && in_array("DELUSIONAL_VAPORS_MILL", $champObj->TurnEffects)) {
                    $pDeck = &GetDeck($player);
                    $pGY = &GetGraveyard($player);
                    for($dm = 0; $dm < 4; ++$dm) {
                        if(count($pDeck) == 0) break;
                        $millCard = array_shift($pDeck);
                        array_push($pGY, $millCard);
                    }
                    break;
                }
            }
        }
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

// --- Starcalling Registry ---
// Maps cardID => starcalling cost (int). Cards with this keyword can be activated
// mid-glimpse by paying their starcalling cost. Other glimpsed cards go to deck bottom.
$starcallingCards = [];
$starcallingCards["zuj68m69iq"] = 0; // Astra Sight: Starcalling (0)
$starcallingCards["4d5vettczb"] = 2; // Cometfall: Starcalling (2)
$starcallingCards["dwavcoxpnj"] = 3; // Meteor Strike: Starcalling (3)

/**
 * Get the effective starcalling cost for a card during a glimpse.
 * Checks: 1) innate starcalling, 2) Scry the Stars dynamic starcalling,
 * 3) free starcalling from Arisanna L3 or Elysian Astrolabe.
 * Returns the cost to pay, or -1 if the card has no starcalling.
 */
function GetStarcallingCost($player, $cardID) {
    global $starcallingCards;
    $cost = -1;
    // Check innate starcalling
    if(isset($starcallingCards[$cardID])) {
        $cost = $starcallingCards[$cardID];
    }
    // Scry the Stars: "cards you look at while glimpsing have Starcalling (X) where X = reserve cost"
    if($cost < 0 && GlobalEffectCount($player, "oz23yfzk96") > 0) {
        $cost = CardCost_reserve($cardID);
        if($cost < 0) $cost = 0;
    }
    if($cost < 0) return -1;
    // Free starcalling: Arisanna L3 or Elysian Astrolabe global effect
    if(GlobalEffectCount($player, "FREE_STARCALLING") > 0) {
        $cost = 0;
    }
    return $cost;
}

/**
 * Glimpse N: show the top N cards of the player's deck and let them choose
 * which cards go back to the top vs. the bottom, in any order.
 * If any glimpsed card has Starcalling, offer the player a chance to starcall first.
 * Queues an MZREARRANGE decision followed by a GlimpseApply custom handler.
 *
 * @param int $player The acting player.
 * @param int $amount Number of cards to glimpse.
 */
function Glimpse($player, $amount) {
    $zone = &GetDeck($player);
    $n = min($amount, count($zone));
    if($n == 0) return;

    // Collect the top N card IDs
    $cardIDs = [];
    for($i = 0; $i < $n; ++$i) {
        $cardIDs[] = $zone[$i]->CardID;
    }

    // Remember how many cards are being glimpsed so the handler knows how many to remove
    DecisionQueueController::StoreVariable("glimpseCount", strval($n));

    // Check for starcalling candidates among glimpsed cards
    $starcallCandidateIndices = [];
    for($i = 0; $i < $n; ++$i) {
        $sc = GetStarcallingCost($player, $cardIDs[$i]);
        if($sc >= 0) {
            // Check if player can afford: sc == 0 is free, otherwise need enough hand cards
            if($sc == 0 || count(GetZone("myHand")) >= $sc) {
                $starcallCandidateIndices[] = $i;
            }
        }
    }

    if(!empty($starcallCandidateIndices)) {
        // Move top N cards to myTempZone so the popup can display them face-up
        // (always use myDeck-0 since each MZMove shifts remaining deck cards down)
        for($i = 0; $i < $n; ++$i) {
            MZMove($player, "myDeck-0", "myTempZone");
        }
        // Store card IDs and tempzone flag for handlers
        DecisionQueueController::StoreVariable("glimpseCardIDs", implode(",", $cardIDs));
        DecisionQueueController::StoreVariable("glimpsedToTempZone", "1");
        // Offer starcalling choice using tempzone refs (face-up cards in popup)
        $candidateStr = implode("&", array_map(fn($i) => "myTempZone-$i", $starcallCandidateIndices));
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $candidateStr, 1, "Starcall_a_card?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "StarcallingOffer", 1);
    } else {
        // No starcalling candidates — cards stay in deck, proceed with normal glimpse
        DecisionQueueController::StoreVariable("glimpsedToTempZone", "0");
        $param = "Top=" . implode(",", $cardIDs) . ";Bottom=";
        DecisionQueueController::AddDecision($player, "MZREARRANGE", $param, 1, "Glimpse:_Top=return_to_top,_Bottom=put_on_bottom");
        DecisionQueueController::AddDecision($player, "CUSTOM", "GlimpseApply", 1);
    }
}

function DoDiscardCard($player, $mzCard) {
    // Brackish Lutist (1clswn3ba2): floating memory cards go to banish instead of graveyard
    $discObj = GetZoneObject($mzCard);
    if($discObj !== null && HasFloatingMemory($discObj) && IsBrackishLutistOnField()) {
        MZMove($player, $mzCard, "myBanish");
    } else {
        MZMove($player, $mzCard, "myGraveyard");
    }
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
    // Striking Illuminance (2lukkhisu5): whenever you reveal a luxem card from memory, +1 POWER
    if(strpos($sourceZone, "Memory") !== false && CardElement($CardID) === "LUXEM") {
        global $playerID;
        $pZone = $player == $playerID ? "myIntent" : "theirIntent";
        $intent = GetZone($pZone);
        foreach($intent as $idx => $iObj) {
            if(!$iObj->removed && $iObj->CardID === "2lukkhisu5") {
                AddTurnEffect($pZone . "-" . $idx, "2lukkhisu5_REVEAL_POWER");
                break;
            }
        }
    }
    return $revealedMZ;
}
$revealAbilities = [];

function DoSacrificeFighter($player, $mzCard) {
    $obj = GetZoneObject($mzCard);
    $isHerb = $obj !== null && PropertyContains(CardSubtypes($obj->CardID), "HERB");
    $controller = $obj !== null ? $obj->Controller : $player;
    DoAllyDestroyed($player, $mzCard);
    // Foretold Bloom (lnhzj43qiw): whenever you sacrifice an Herb, Glimpse 2
    if($isHerb && GlobalEffectCount($controller, "FORETOLD_BLOOM") > 0) {
        Glimpse($controller, 2);
    }
}

// Fight for the Crown (1lij42a9sh): each player sacrifices an ally.
// Uses explicit DQ handlers to correctly handle conditional branching and zone perspective.
// "myField-X" mzIDs are used for the opponent's queue so that when they process with their
// own $playerID, GetZoneObject("myField-X") correctly refers to their own field.
function FightForCrown_Start($player) {
    $myAllies = ZoneSearch("myField", ["ALLY"]);
    if(!empty($myAllies)) {
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $myAllies), 1);
        DecisionQueueController::AddDecision($player, "CUSTOM", "FightForCrown_SacActivator", 1);
    } else {
        FightForCrown_QueueOpponent($player);
    }
}
function FightForCrown_QueueOpponent($activatingPlayer) {
    $opponent = ($activatingPlayer == 1) ? 2 : 1;
    $opField = GetField($opponent);
    $opAllies = [];
    for($i = 0; $i < count($opField); ++$i) {
        if(!$opField[$i]->removed && PropertyContains(EffectiveCardType($opField[$i]), "ALLY")) {
            $opAllies[] = "myField-" . $i; // use myField from the opponent's perspective
        }
    }
    if(!empty($opAllies)) {
        DecisionQueueController::AddDecision($opponent, "MZCHOOSE", implode("&", $opAllies), 1);
        DecisionQueueController::AddDecision($opponent, "CUSTOM", "FightForCrown_SacOpponent", 1);
    }
}

$customDQHandlers["FightForCrown_SacActivator"] = function($player, $parts, $lastDecision) {
    DoSacrificeFighter($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    FightForCrown_QueueOpponent($player);
};
$customDQHandlers["FightForCrown_SacOpponent"] = function($player, $parts, $lastDecision) {
    // $player is the opponent (their queue ran this handler)
    DoSacrificeFighter($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
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
        $obj = GetZoneObject($lastResult);
        OnLeaveField($player, $lastResult);
        $dest = EphemeralRedirectDest($obj, "myHand", $player);
        MZMove($player, $lastResult, $dest);
    }
};

$customDQHandlers["SpellshieldWindBuff"] = function($player, $param, $lastResult) {
    if($lastResult && $lastResult !== "-") {
        AddCounters($player, $lastResult, "buff", 1);
    }
};

// False Step (47o7eanl1g): after paying (2), all allies become distant
$customDQHandlers["FalseStepDistantAllies"] = function($player, $param, $lastResult) {
    $allies = ZoneSearch("myField", ["ALLY"]);
    foreach($allies as $allyMZ) {
        BecomeDistant($player, $allyMZ);
    }
};

// Veiled Dash (08kuz07nk4): choose optional second target, then reveal wind+prevent
$customDQHandlers["VeiledDashContinue"] = function($player, $param, $lastResult) {
    $target1 = DecisionQueueController::GetVariable("target1");
    if($target1 === null || $target1 === "-") return;
    BecomeDistant($player, $target1);
    // Check for second target (optional)
    $units = array_merge(
        ZoneSearch("myField", ["ALLY", "CHAMPION"]),
        ZoneSearch("theirField", ["ALLY", "CHAMPION"])
    );
    $units = FilterSpellshroudTargets($units);
    $remaining = array_values(array_diff($units, [$target1]));
    if(!empty($remaining)) {
        $remainStr = implode("&", $remaining);
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", "$remainStr", 1);
        DecisionQueueController::AddDecision($player, "CUSTOM", "VeiledDashFinish", 1);
    } else {
        // No second target possible — just do wind reveal for target1
        VeiledDashApplyPrevention($player, $target1, null);
    }
};

$customDQHandlers["VeiledDashFinish"] = function($player, $param, $lastResult) {
    $target1 = DecisionQueueController::GetVariable("target1");
    $target2 = ($lastResult !== "-" && $lastResult !== null) ? $lastResult : null;
    if($target2 !== null) {
        BecomeDistant($player, $target2);
    }
    VeiledDashApplyPrevention($player, $target1, $target2);
};

function VeiledDashApplyPrevention($player, $target1, $target2) {
    $windCards = ZoneSearch("myMemory", cardElements: ["WIND"]);
    $windCount = count($windCards);
    if($windCount <= 0) return;
    $revealIDs = [];
    foreach($windCards as $wMZ) {
        $wObj = GetZoneObject($wMZ);
        if($wObj !== null) $revealIDs[] = $wObj->CardID;
    }
    if(!empty($revealIDs)) {
        SetFlashMessage('REVEAL:' . implode('|', $revealIDs));
    }
    AddTurnEffect($target1, "PREVENT_ALL_" . $windCount);
    if($target2 !== null) {
        AddTurnEffect($target2, "PREVENT_ALL_" . $windCount);
    }
}

// --- Foster: Awakened Frostguard (mnu1xhs5jw) On Foster helper ---
// "On Foster: You may banish up to two cards with floating memory from your graveyard.
//  For each card banished this way, put a buff counter on CARDNAME and draw a card."
function FrostguardOnFoster($player, $mzID) {
    $floatingCards = [];
    $graveyard = ZoneSearch("myGraveyard");
    foreach($graveyard as $gMZ) {
        $gObj = GetZoneObject($gMZ);
        if($gObj !== null && HasFloatingMemory($gObj)) $floatingCards[] = $gMZ;
    }
    if(empty($floatingCards)) return;
    $cardStr = implode("&", $floatingCards);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $cardStr, 1, tooltip:"Banish_a_floating_memory_card_from_graveyard?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "FrostguardBanish|$mzID|1", 1);
}

$customDQHandlers["FrostguardBanish"] = function($player, $parts, $lastDecision) {
    $mzID = $parts[0];
    $step = intval($parts[1]);
    if($lastDecision === null || $lastDecision === "-" || $lastDecision === "") {
        return; // Player declined
    }
    // Banish the chosen card
    MZMove($player, $lastDecision, "myBanish");
    NicoOnFloatingMemoryBanished($player);
    AddCounters($player, $mzID, "buff", 1);
    Draw($player, 1);
    if($step >= 2) return; // Already did 2
    // Offer second banish
    $floatingCards = [];
    $graveyard = ZoneSearch("myGraveyard");
    foreach($graveyard as $gMZ) {
        $gObj = GetZoneObject($gMZ);
        if($gObj !== null && HasFloatingMemory($gObj)) $floatingCards[] = $gMZ;
    }
    if(empty($floatingCards)) return;
    $cardStr = implode("&", $floatingCards);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $cardStr, 1, tooltip:"Banish_another_floating_memory_card?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "FrostguardBanish|$mzID|2", 1);
};

// --- Foster: Organize the Alliance (ch2bbmoqk2) ---
// "Target ally becomes fostered."
$customDQHandlers["OrganizeAllianceFoster"] = function($player, $parts, $lastDecision) {
    if($lastDecision === null || $lastDecision === "-" || $lastDecision === "") return;
    BecomeFostered($player, $lastDecision);
    // Seasoned Shieldmaster (qsm4o98vn1): whenever ally becomes fostered → draw into memory
    $field = &GetField($player);
    foreach($field as $fieldObj) {
        if(!$fieldObj->removed && $fieldObj->CardID === "qsm4o98vn1" && !HasNoAbilities($fieldObj)) {
            DrawIntoMemory($player, 1);
            break;
        }
    }
    // Fire OnFoster triggered ability on the target if it has one
    $targetObj = GetZoneObject($lastDecision);
    if($targetObj !== null && HasFoster($targetObj) && !HasNoAbilities($targetObj)) {
        OnFoster($player, $lastDecision);
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
    // Enhance Potency: fire any copy after ability decisions (block 1) but before AbilityOpportunity (block 200)
    DecisionQueueController::AddDecision($player, "CUSTOM", "CheckEnhancePotency", 99);
};

$customDQHandlers["CheckEnhancePotency"] = function($player, $parts, $lastDecision) {
    EnhancePotencyCheck($player);
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
    $fromTempZone = DecisionQueueController::GetVariable("glimpsedToTempZone") === "1";

    // Parse the MZREARRANGE result into piles
    $piles = ["Top" => [], "Bottom" => []];
    $pileStrings = explode(";", $lastDecision);
    foreach($pileStrings as $pileStr) {
        $eqPos = strpos($pileStr, "=");
        if($eqPos === false) continue;
        $pileName = substr($pileStr, 0, $eqPos);
        $cardsStr = trim(substr($pileStr, $eqPos + 1));
        $pidList = ($cardsStr !== "") ? explode(",", $cardsStr) : [];
        $piles[$pileName] = $pidList;
    }

    if($fromTempZone) {
        // Cards were moved to myTempZone by Glimpse() for face-up display
        // Collect their objects and build a cardID→object map
        $tempZone = &GetTempZone($player);
        $tempObjs = [];
        for($i = 0; $i < count($tempZone); ++$i) {
            if(!$tempZone[$i]->removed) {
                $tempObjs[] = $tempZone[$i];
            }
        }
        $tCardMap = [];
        foreach($tempObjs as $obj) {
            $tCardMap[$obj->CardID][] = $obj;
        }
        $popTCard = function($cardID) use (&$tCardMap) {
            if(empty($tCardMap[$cardID])) return null;
            return array_shift($tCardMap[$cardID]);
        };
        // Remove all tempzone objects cleanly
        foreach($tempObjs as $obj) { $obj->Remove(); }
        DecisionQueueController::CleanupRemovedCards();

        // Top pile: add to deck front (reverse iterate preserves original order)
        $topCards = $piles["Top"];
        for($i = count($topCards) - 1; $i >= 0; --$i) {
            $src = $popTCard($topCards[$i]);
            AddDeckTop($player, $topCards[$i], $src);
        }
        // Bottom pile: add to deck back
        foreach($piles["Bottom"] as $cid) {
            $src = $popTCard($cid);
            AddDeck($player, $cid, $src);
        }
        return;
    }

    // Original deck-top path: remove top N cards and re-insert per player choice
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

// Necklace of Hindsight (21g6ldxwrv): apply rearrange result to the opponent's deck
$customDQHandlers["NecklaceHindsightApply"] = function($player, $parts, $lastDecision) {
    $opponent = ($player == 1) ? 2 : 1;
    $zone = &GetDeck($opponent);
    $n = intval(DecisionQueueController::GetVariable("glimpseCount"));

    $piles = ["Top" => [], "Bottom" => []];
    $pileStrings = explode(";", $lastDecision);
    foreach($pileStrings as $pileStr) {
        $eqPos = strpos($pileStr, "=");
        if($eqPos === false) continue;
        $pileName = substr($pileStr, 0, $eqPos);
        $cardsStr = trim(substr($pileStr, $eqPos + 1));
        $pidList = ($cardsStr !== "") ? explode(",", $cardsStr) : [];
        $piles[$pileName] = $pidList;
    }

    $removedCards = [];
    for($i = 0; $i < $n; ++$i) {
        if(count($zone) > 0) {
            $removedCards[] = array_shift($zone);
        }
    }

    $cardMap = [];
    foreach($removedCards as $cardObj) {
        $cardMap[$cardObj->CardID][] = $cardObj;
    }
    $popCard = function($cardID) use (&$cardMap) {
        if(!isset($cardMap[$cardID]) || count($cardMap[$cardID]) === 0) return null;
        return array_shift($cardMap[$cardID]);
    };

    $topCards = $piles["Top"];
    for($i = count($topCards) - 1; $i >= 0; --$i) {
        $obj = $popCard($topCards[$i]);
        if($obj !== null) array_unshift($zone, $obj);
    }
    $bottomCards = $piles["Bottom"];
    foreach($bottomCards as $cardID) {
        $obj = $popCard($cardID);
        if($obj !== null) array_push($zone, $obj);
    }
};

/**
 * Starcalling offer handler: player either chose a card to starcall or passed.
 * If they chose a card, pay its starcalling cost, put ALL other glimpsed cards
 * on the bottom of the deck, then activate the starcalled card.
 * If they passed, fall back to normal Glimpse rearrange.
 */
$customDQHandlers["StarcallingOffer"] = function($player, $parts, $lastDecision) {
    $n = intval(DecisionQueueController::GetVariable("glimpseCount"));
    $cardIDsStr = DecisionQueueController::GetVariable("glimpseCardIDs");
    $cardIDs = explode(",", $cardIDsStr);

    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // Player declined starcalling — cards are in tempzone; GlimpseApply will recover them
        $param = "Top=" . implode(",", $cardIDs) . ";Bottom=";
        DecisionQueueController::StoreVariable("glimpsedToTempZone", "1"); // ensure flag is set
        DecisionQueueController::AddDecision($player, "MZREARRANGE", $param, 1, "Glimpse:_Top=return_to_top,_Bottom=put_on_bottom");
        DecisionQueueController::AddDecision($player, "CUSTOM", "GlimpseApply", 1);
        return;
    }

    // Player chose a card to starcall — lastDecision is e.g. "myTempZone-2"
    $tmpParts = explode("-", $lastDecision);
    $chosenTempIndex = intval($tmpParts[1]);
    $chosenCardID = $cardIDs[$chosenTempIndex];
    $starcallingCost = GetStarcallingCost($player, $chosenCardID);

    // Consume free starcalling if applicable
    $usedFreeStarcalling = false;
    if($starcallingCost == 0 && GlobalEffectCount($player, "FREE_STARCALLING") > 0) {
        // Arisanna L3 (q3huqj5bba): once per turn
        if(GlobalEffectCount($player, "ARISANNA_FREE_STARCALLING") > 0) {
            RemoveGlobalEffect($player, "ARISANNA_FREE_STARCALLING");
            RemoveGlobalEffect($player, "FREE_STARCALLING");
            $usedFreeStarcalling = true;
        }
        // Elysian Astrolabe (4nmxqsm4o9): until end of turn, unlimited
        if(GlobalEffectCount($player, "ASTROLABE_FREE_STARCALLING") > 0) {
            $usedFreeStarcalling = true;
            DecisionQueueController::StoreVariable("astrolabeGlimpseAfterStarcall", "1");
        }
    }

    // Move chosen card from tempzone to hand
    MZMove($player, "myTempZone-" . $chosenTempIndex, "myHand");
    // Move all other tempzone cards to deck BOTTOM (per starcalling rules: others go on bottom)
    // Do NOT call CleanupRemovedCards yet — original indices 0..N-1 remain valid (just some removed)
    for($i = 0; $i < $n; ++$i) {
        if($i === $chosenTempIndex) continue;
        $tempObj = GetZoneObject("myTempZone-" . $i);
        if($tempObj && !$tempObj->removed) {
            MZMove($player, "myTempZone-" . $i, "myDeck");
        }
    }
    DecisionQueueController::CleanupRemovedCards();

    // Find the chosen card in hand (search from end for most-recently-added)
    $hand = GetZone("myHand");
    $handMZ = null;
    for($hi = count($hand) - 1; $hi >= 0; --$hi) {
        if(!$hand[$hi]->removed && $hand[$hi]->CardID === $chosenCardID) {
            $handMZ = "myHand-" . $hi;
            break;
        }
    }
    if($handMZ === null) return;

    // Pay starcalling cost, then activate the card
    if($starcallingCost > 0) {
        for($i = 0; $i < $starcallingCost; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "StarcallingActivate|$handMZ|$chosenCardID", 100);
};

/**
 * After starcalling cost is paid, activate the card from hand.
 */
$customDQHandlers["StarcallingActivate"] = function($player, $parts, $lastDecision) {
    $mzCard = $parts[0];
    $chosenCardID = $parts[1];

    // Find the card in hand (index may have shifted due to reserve payments)
    $hand = GetZone("myHand");
    $actualMZ = null;
    for($i = 0; $i < count($hand); ++$i) {
        if(!$hand[$i]->removed && $hand[$i]->CardID === $chosenCardID) {
            $actualMZ = "myHand-" . $i;
            break;
        }
    }
    if($actualMZ === null) return;

    // Tag as starcalled so the card's ability code can check
    DecisionQueueController::StoreVariable("wasStarcalled", "YES");

    // Move to effect stack
    $obj = MZMove($player, $actualMZ, "EffectStack");
    $obj->Controller = $player;

    // Queue EffectStackOpportunity (opponent may respond)
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);

    // Astrolabe: trigger Glimpse 5 after starcalling completes
    $astrolabeGlimpse = DecisionQueueController::GetVariable("astrolabeGlimpseAfterStarcall");
    if($astrolabeGlimpse === "1") {
        DecisionQueueController::StoreVariable("astrolabeGlimpseAfterStarcall", "0");
        // Queue glimpse 5 after the starcalled card resolves (high block)
        DecisionQueueController::AddDecision($player, "CUSTOM", "AstrolabeStarcallGlimpse", 201);
    }

    // Stargazer's Portent (btjuxztaug): copy the starcalled card's activation
    if(GlobalEffectCount($player, "btjuxztaug") > 0) {
        RemoveGlobalEffect($player, "btjuxztaug");
        DecisionQueueController::AddDecision($player, "CUSTOM", "StargazersPortentCopy|$chosenCardID", 201);
    }
};

$customDQHandlers["AstrolabeStarcallGlimpse"] = function($player, $parts, $lastDecision) {
    Glimpse($player, 5);
};

// --- Stargazer's Portent (btjuxztaug): copy the starcalled card's activation ---
$customDQHandlers["StargazersPortentCopy"] = function($player, $parts, $lastDecision) {
    global $cardActivatedAbilities;
    $cardID = $parts[0];
    $abilityKey = $cardID . ":0";
    if(isset($cardActivatedAbilities[$abilityKey])) {
        DecisionQueueController::StoreVariable("wasStarcalled", "YES");
        DecisionQueueController::StoreVariable("mzID", "COPY");
        $cardActivatedAbilities[$abilityKey]($player);
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

$customDQHandlers["FirebloomRecollation"] = function($player, $parts, $lastDecision) {
    // $lastDecision is the chosen champion mzID
    if($lastDecision !== "-" && $lastDecision !== "") {
        $champObj = &GetZoneObject($lastDecision);
        if($champObj !== null && !$champObj->removed) {
            $champObj->Damage += 1;
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

$customDQHandlers["RelicOfSunkenPastSacrifice"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        $fieldIdx = intval($parts[0]);
        $field = &GetField($player);
        if(isset($field[$fieldIdx]) && !$field[$fieldIdx]->removed && $field[$fieldIdx]->CardID === "dqqwey9xys") {
            DoSacrificeFighter($player, "myField-" . $fieldIdx);
            Draw($player, 1);
        }
    }
};

// Lumen Borealis (3ejd9yj9rl): reveal chosen memory card when animal ally dies
$customDQHandlers["LumenBorealisReveal"] = function($player, $parts, $lastDecision) {
    if($lastDecision == "-" || $lastDecision == "") return;
    DoRevealCard($player, $lastDecision);
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

// Forgetful Concoction (7kr1haizu8): banish 2 random cards from opponent's memory
function ForgetfulConcoctionExecute($player) {
    $opponent = ($player == 1) ? 2 : 1;
    global $playerID;
    $memZone = ($opponent == $playerID) ? "myMemory" : "theirMemory";
    $banishZone = ($opponent == $playerID) ? "myBanish" : "theirBanish";
    $mem = GetZone($memZone);
    $indices = [];
    for($i = 0; $i < count($mem); ++$i) {
        if(!$mem[$i]->removed) $indices[] = $i;
    }
    shuffle($indices);
    $toBanish = array_slice($indices, 0, 2);
    // Sort descending so removals don't shift indices
    rsort($toBanish);
    foreach($toBanish as $idx) {
        $mzCard = $memZone . "-" . $idx;
        $banishedObj = &GetZoneObject($mzCard);
        if($banishedObj !== null) {
            $banishedObj->Counters['MEM_BANISHED'] = $opponent;
        }
        MZMove($opponent, $mzCard, $banishZone);
    }
    DecisionQueueController::CleanupRemovedCards();
}

// Tabula of Salvage (9cy4wipw4k): iteratively choose cards from GY to put on deck bottom
function TabulaSalvageLoop($player, $remaining) {
    if($remaining <= 0) return;
    $gy = ZoneSearch("myGraveyard");
    if(empty($gy)) return;
    $choices = implode("&", $gy);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $choices, 1, tooltip:"Choose_card_to_put_on_deck_bottom");
    DecisionQueueController::AddDecision($player, "CUSTOM", "TabulaSalvagePick|$remaining", 1);
}

$customDQHandlers["TabulaSalvagePick"] = function($player, $parts, $lastDecision) {
    $remaining = intval($parts[0]);
    if($lastDecision == "-" || $lastDecision == "") return; // Player passed
    MZMove($player, $lastDecision, "myDeck");
    if($remaining > 1) {
        TabulaSalvageLoop($player, $remaining - 1);
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

    // Sigil of Budding Embers (g31dg6zl3j): requires Diao Chan Bonus + champion has glimmer
    if($obj->CardID === "g31dg6zl3j") {
        if(!IsDiaoChanBonus($obj->Controller)) return 0;
        $champObj = GetPlayerChampion($obj->Controller);
        if($champObj === null || GetCounterCount($champObj, "glimmer") <= 0) return 0;
    }

    return 1;
}

function CardCurrentEffects($obj) {
    global $doesGlobalEffectApply, $effectAppliesToBoth,$playerID;
    //Start with this object's effects
    $effects = $obj->TurnEffects;
    //Filter out internal effects that shouldn't display in UI
    $effects = array_filter($effects, function($effectID) {
        return $effectID !== "DAMAGED_SINCE_LAST_TURN" && $effectID !== "ENTERED_THIS_TURN";
    });
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

// Virtual property: returns highlight metadata for a graveyard card that can be activated via Ephemerate.
// Returns a gold glow if the current player can pay the ephemerate cost for this card, false otherwise.
function EphemerateMeta($obj) {
    global $playerID;
    $currentPhase = GetCurrentPhase();
    if ($currentPhase !== "MAIN") {
        return json_encode(['highlight' => false]);
    }
    $turnPlayer = GetTurnPlayer();
    if ($playerID != $turnPlayer) {
        return json_encode(['highlight' => false]);
    }
    // Check if decision queue is empty
    $decisionQueue = &GetDecisionQueue($turnPlayer);
    if (count($decisionQueue) > 0) {
        return json_encode(['highlight' => false]);
    }
    global $ephemerateCards;
    if (!isset($ephemerateCards[$obj->CardID])) {
        return json_encode(['highlight' => false]);
    }
    if (!CanPayEphemerate($playerID, $obj->CardID)) {
        return json_encode(['highlight' => false]);
    }
    // Gold glow to distinguish Ephemerate from normal hand playability
    return json_encode(['color' => 'rgba(255, 200, 0, 0.95)']);
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

// --- Ephemeral helpers ---
function MakeEphemeral($mzCard) {
    AddTurnEffect($mzCard, "EPHEMERAL");
}

function IsEphemeral($obj) {
    return $obj !== null && in_array("EPHEMERAL", $obj->TurnEffects);
}

function CountEphemeralObjects($player) {
    $field = &GetField($player);
    $count = 0;
    foreach($field as $obj) {
        if(!$obj->removed && IsEphemeral($obj)) $count++;
    }
    return $count;
}

/**
 * Redirect a leave-field destination to banishment if the object is ephemeral.
 * Call this whenever moving a field object to a non-banish zone (graveyard, hand, deck).
 */
function EphemeralRedirectDest($obj, $defaultDest, $player) {
    if(IsEphemeral($obj) && strpos($defaultDest, "Banish") === false) {
        $controller = $obj->Controller ?? $player;
        return $player == $controller ? "myBanish" : "theirBanish";
    }
    return $defaultDest;
}

/**
 * Return Shackled Theurgist from graveyard/banish to the field with +2 LIFE and ephemeral.
 */
function ShackledTheurgistReturn($player, $fieldZone) {
    global $playerID;
    $gyZone = $player == $playerID ? "myGraveyard" : "theirGraveyard";
    $bnZone = $player == $playerID ? "myBanish" : "theirBanish";
    foreach([$gyZone, $bnZone] as $zone) {
        $contents = GetZone($zone);
        for($i = count($contents) - 1; $i >= 0; $i--) {
            if(!$contents[$i]->removed && $contents[$i]->CardID === "vkqzk1jik7") {
                MZMove($player, $zone . "-" . $i, $fieldZone);
                $field = &GetField($player);
                $newIdx = count($field) - 1;
                $newMZ = $fieldZone . "-" . $newIdx;
                AddTurnEffect($newMZ, "vkqzk1jik7_LIFE");
                MakeEphemeral($newMZ);
                return;
            }
        }
    }
}

// --- Ephemerate registry ---
// Maps cardID => config for cards with the Ephemerate keyword.
// 'cost' = reserve cost for ephemerate activation
// 'costModifier' = optional callback($player) returning int cost reduction
// 'extraCostHandler' = optional string name of DQ handler for non-reserve extra costs
$ephemerateCards = [];
$ephemerateCards["4vjkezn49t"] = ['cost' => 4]; // Vengeful Paramour
$ephemerateCards["sm68d3we64"] = ['cost' => 3, 'extraCostHandler' => 'EphemerateBanishFloating']; // Sunken Battle Priest
$ephemerateCards["v0gu8efq08"] = ['cost' => 6, 'costModifier' => function($player) {
    return CountEphemeralObjects($player) > 0 ? 3 : 0;
}]; // Lingering Banshee

function GetEphemerateCost($player, $cardID) {
    global $ephemerateCards;
    if(!isset($ephemerateCards[$cardID])) return -1;
    $config = $ephemerateCards[$cardID];
    $cost = $config['cost'];
    if(isset($config['costModifier'])) {
        $cost = max(0, $cost - $config['costModifier']($player));
    }
    return $cost;
}

function CanPayEphemerate($player, $cardID) {
    global $ephemerateCards, $playerID;
    if(!isset($ephemerateCards[$cardID])) return false;
    $config = $ephemerateCards[$cardID];
    $cost = GetEphemerateCost($player, $cardID);
    $hand = &GetHand($player);
    $available = count($hand);
    // Add reservable field cards
    $zone = $player == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    foreach($field as $fObj) {
        if(!$fObj->removed && isset($fObj->Status) && $fObj->Status == 2 && HasReservable($fObj)) {
            $available++;
        }
    }
    if($available < $cost) return false;
    // Check extra cost feasibility
    if(isset($config['extraCostHandler'])) {
        if($config['extraCostHandler'] === 'EphemerateBanishFloating') {
            $gravZone = $player == $playerID ? "myGraveyard" : "theirGraveyard";
            $gy = GetZone($gravZone);
            $hasFloating = false;
            foreach($gy as $gObj) {
                if(!$gObj->removed && HasFloatingMemory($gObj)) {
                    $hasFloating = true;
                    break;
                }
            }
            if(!$hasFloating) return false;
        }
    }
    return true;
}

$untilBeginTurnEffects["RYBF1HBTCS"] = true;
$foreverEffects["GMBTMNTM"] = true;
$effectAppliesToBoth["GMBF3HVRKG"] = true;
// Peaceful Reunion: never auto-expire (cleared manually at caster's RecollectionPhase)
$foreverEffects["wr42i6eifn"] = true;
// Freydis permanent distant: Ranger units are always distant for the rest of the game
$foreverEffects["FREYDIS_PERMANENT_DISTANT"] = true;
// Don't display this effect on field cards — it's a global attack-prevention flag
$doesGlobalEffectApply["wr42i6eifn"] = function($obj) { return false; };
// Freydis permanent distant: apply to Ranger units only
$doesGlobalEffectApply["FREYDIS_PERMANENT_DISTANT"] = function($obj) {
    return PropertyContains(EffectiveCardClasses($obj), "RANGER");
};

// Persistent per-card TurnEffects that survive ExpireEffects across turns.
// SKIP_WAKEUP: consumed by WakeUpPhase (one-time skip).
// FROZEN_BY_SNOW_FAIRY: persists as long as opponent controls Snow Fairy.
// SPELLSHROUD_NEXT_TURN / STEALTH_NEXT_TURN: "until beginning of your next turn" effects,
//   consumed by WakeUpPhase of the controller's next turn.
// NO_UPKEEP: Right of Realm exemption — domain permanently skips its upkeep abilities.
$persistentTurnEffects = [];
$persistentTurnEffects["SKIP_WAKEUP"] = true;
$persistentTurnEffects["FROZEN_BY_SNOW_FAIRY"] = true;
$persistentTurnEffects["FROZEN_BY_TORPID"] = true;
$persistentTurnEffects["SPELLSHROUD_NEXT_TURN"] = true;
// Calamity Cannon (lwabipl6gt): champion gets +10 POWER on first Gun attack during next turn
$persistentTurnEffects["CALAMITY_CANNON"] = true;
$persistentTurnEffects["STEALTH_NEXT_TURN"] = true;
$persistentTurnEffects["NO_UPKEEP"] = true;
$persistentTurnEffects["ATTUNE_FLAMES_BUFF"] = true;
$persistentTurnEffects["BLAZING_CHARGE_NEXT_TURN"] = true;
$persistentTurnEffects["TAUNT_NEXT_TURN"] = true;
$persistentTurnEffects["VIGOR_NEXT_TURN"] = true;
$persistentTurnEffects["FREEZING_ROUND_RETURN"] = true;
$persistentTurnEffects["FOSTERED"] = true;
$persistentTurnEffects["DAMAGED_SINCE_LAST_TURN"] = true;
$persistentTurnEffects["IMBUED"] = true;
$persistentTurnEffects["INGRESS_SANGUINE"] = true; // Ingress of Sanguine Ire: +3 POWER on first attack next turn
$persistentTurnEffects["CANT_ATTACK_NEXT_TURN"] = true; // Bring Down the Mighty: ally can't attack until beginning of caster's next turn
$persistentTurnEffects["ao1cfkhbp6"] = true; // Stand Fast: +1 LIFE until beginning of next turn
$persistentTurnEffects["EPHEMERAL"] = true; // Ephemeral: object is banished instead of leaving the field
$persistentTurnEffects["SACRIFICE_NEXT_END_PHASE"] = true; // Incinerated Templar: sacrifice at beginning of next end phase

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

$doesGlobalEffectApply["90i1prp63s"] = function($obj) { // Evanescent Winds: Phantasia allies get +2 LIFE
    return PropertyContains(EffectiveCardType($obj), "ALLY") && PropertyContains(EffectiveCardType($obj), "PHANTASIA");
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

$doesGlobalEffectApply["9g44vm5kt3"] = function($obj) { // Empowering Tincture sacrifice: champion +2 level until end of turn
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["a01pyxwo25"] = function($obj) { // Kongming L2 Empower 3: champion +3 level until end of turn
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["pmx99jrukm"] = function($obj) { // Ruinous Pillars Empower 2: champion +2 level
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["9f0nsj62l6"] = function($obj) { // Apprentice Aeromancer Empower 2: champion +2 level
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["zeig1e49wb"] = function($obj) { // Solar Pinnacle Empower 2: champion +2 level
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["n06isycm60"] = function($obj) { // Pupil of Sacred Flames Empower 2: champion +2 level
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["szeb8zzj86"] = function($obj) { // Fractal of Mana Empower 1: champion +1 level
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["sq0ou8vas3"] = function($obj) { // Tome of Sorcery Empower 1: champion +1 level
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["to1pmvo54d"] = function($obj) { // Mnemonic Charm Empower 2: champion +2 level
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["qb6zhphtw6"] = function($obj) { // Rainweaver Mage Empower 4: champion +4 level
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["zmoegdo111"] = function($obj) { // Sempiternal Sage Empower 3: champion +3 level
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["xllhbjr20n"] = function($obj) { // Lu Xun, Pyre Strategist Empower 3: champion +3 level
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["jgyx38zpl0-east"] = function($obj) { // Bagua East: allies +2 POWER until end of turn
    return PropertyContains(EffectiveCardType($obj), "ALLY");
};

$doesGlobalEffectApply["BREWED_POTION"] = function($obj) { // Flag only — tracks if a potion was brewed this turn
    return false; // No visual effect on cards
};

$doesGlobalEffectApply["FREEZING_STEEL"] = function($obj) { // Flag only — next items enter rested
    return false;
};

$doesGlobalEffectApply["PRIMA_MATERIA_BOOST"] = function($obj) { // Flag only — next astra damage to units +3
    return false;
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

$doesGlobalEffectApply["yfzk96yd60"] = function($obj) { // Empowering Prayer: champion gets +2 level
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

// Starcalling global effect flags — not displayed on field cards
$doesGlobalEffectApply["FREE_STARCALLING"] = function($obj) { return false; };
$doesGlobalEffectApply["ARISANNA_FREE_STARCALLING"] = function($obj) { return false; };
$doesGlobalEffectApply["ASTROLABE_FREE_STARCALLING"] = function($obj) { return false; };
$doesGlobalEffectApply["oz23yfzk96"] = function($obj) { return false; }; // Scry the Stars dynamic starcalling

// Foretold Bloom (lnhzj43qiw): flag only — herb-sacrifice glimpse handled in DoSacrificeFighter/BrewFinalizeHerbs
$doesGlobalEffectApply["FORETOLD_BLOOM"] = function($obj) { return false; };

// Agility N: flag only — triggered ability at beginning of end phase, returns N cards from memory
$doesGlobalEffectApply["AGILITY_3"] = function($obj) { return false; };

// Collapsing Trap (v2214upufo): flag only — next allies enter rested, handled in FieldAfterAdd
$doesGlobalEffectApply["COLLAPSING_TRAP"] = function($obj) { return false; };

// Bathe in Light (d9zax2g20h): flag only — delayed recover 4 at next recollection
$doesGlobalEffectApply["BATHE_IN_LIGHT_RECOVER"] = function($obj) { return false; };
$untilBeginTurnEffects["BATHE_IN_LIGHT_RECOVER"] = true;

// Sudden Snow (dxAEI20h8F): flag only — allies enter rested this turn
$doesGlobalEffectApply["SUDDEN_SNOW_RESTED"] = function($obj) { return false; };

// Tailwind's Blessing (oh5n2sjk0u): allies you control get +1 POWER until EOT
$doesGlobalEffectApply["oh5n2sjk0u"] = function($obj) {
    return PropertyContains(EffectiveCardType($obj), "ALLY");
};

// Pluming Crescendo (xgi39z49tu): Animals you control get +1 POWER until EOT
$doesGlobalEffectApply["xgi39z49tu"] = function($obj) {
    return PropertyContains(EffectiveCardType($obj), "ALLY") && PropertyContains(EffectiveCardSubtypes($obj), "ANIMAL");
};

// Convalescing Mare (ysj63dw50a): Other allies you control get +1 LIFE until EOT
$doesGlobalEffectApply["ysj63dw50a"] = function($obj) {
    return PropertyContains(EffectiveCardType($obj), "ALLY") && $obj->CardID !== "ysj63dw50a";
};

// Planar Abyss (qexcwmx2ug): flag only — delayed destroy-all at next recollection
$doesGlobalEffectApply["PLANAR_ABYSS_PENDING"] = function($obj) { return false; };
$untilBeginTurnEffects["PLANAR_ABYSS_PENDING"] = true;

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
        '6fxxgmuesd' => 2, // Icebound Slam: [Class Bonus] costs 2 less
        '05qzzadf9q' => 2, // Hailstorm Guard: [Class Bonus] costs 2 less
        '0k0p6n5nr7' => 2, // Scorching Strafe: [Class Bonus] costs 2 less
        '16hrusesqi' => 2, // Invigoration: [Class Bonus] costs 2 less
        '1m48260b7b' => 2, // Razorgale Calling: [Class Bonus] costs 2 less
        '3cmrkv3y16' => 2, // Cyclical Breeze: [Class Bonus] costs 2 less
        '6ilt42sehq' => 1, // Slipstream Vault: [Class Bonus] costs 1 less (if targets unique ally)
        'rzsr6aw4hz' => 2, // Burst Asunder: [Class Bonus] costs 2 less
        'aj7pz79wsp' => 2, // Scorching Imperilment: [Class Bonus] costs 2 less
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
            // Nascent Barrier (6bc3ogf0o8): prevent up to N damage (encoded as NASCENT_BARRIER_N)
            foreach($obj->TurnEffects as $te) {
                if(strpos($te, "NASCENT_BARRIER_") === 0) {
                    $preventAmount = intval(substr($te, strlen("NASCENT_BARRIER_")));
                    $prevented = min($preventAmount, $amount);
                    $amount -= $prevented;
                    $obj->TurnEffects = array_values(array_filter($obj->TurnEffects, fn($e) => $e !== $te));
                    break;
                }
            }
            // Water Barrier (xWJND68I8X): prevent all but 1 of next damage to champion
            if(in_array("WATER_BARRIER", $obj->TurnEffects) && $amount > 1) {
                $amount = 1;
                $obj->TurnEffects = array_values(array_filter($obj->TurnEffects, fn($e) => $e !== "WATER_BARRIER"));
            }
            // Blazing Charge (s5jwsl7ded): champion takes +1 damage
            if(in_array("BLAZING_CHARGE_NEXT_TURN", $obj->TurnEffects)) {
                $amount += 1;
            }
            $obj->Damage += $amount;
            // Magebane Lash (oh300z2sns): Nico Bonus — whenever Nico takes non-combat damage, recover 2
            if($amount > 0 && $obj->CardID === "5bbae3z4py") {
                MagebaneNicoBonusCheck($player);
            }
            // Aegis of Dawn (abipl6gt7l): whenever champion dealt 4+ damage, summon Automaton Drone
            if($amount >= 4) {
                AegisOfDawnTrigger($player);
            }
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

function Empower($player, $amount, $sourceID) {
    AddGlobalEffects($player, $sourceID);
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    foreach($field as $i => $obj) {
        if(PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
            AddTurnEffect($zone . "-" . $i, "EMPOWERED");
            break;
        }
    }
}

function IsEmpowered($player) {
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    foreach($field as $obj) {
        if(PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
            return in_array("EMPOWERED", $obj->TurnEffects ?? []);
        }
    }
    return false;
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

/**
 * Get the champion object reference for a player.
 * @param int $player The player number
 * @return object|null The champion field object, or null if not found.
 */
function GetPlayerChampion($player) {
    $field = &GetField($player);
    foreach($field as &$obj) {
        if(!$obj->removed && PropertyContains(EffectiveCardType($obj), "CHAMPION") && $obj->Controller == $player) {
            return $obj;
        }
    }
    return null;
}

/**
 * Attempt a Diao Chan glimmer cast from memory. Called when a player selects a
 * myMemory card during an opportunity window. Returns true if handled, false otherwise.
 *
 * Flow: REST champion → remove glimmer counters → move spell to hand → ActivateCard(ignoreCost=true)
 *
 * @param int    $player  The player attempting the glimmer cast
 * @param string $memoryMZ The mzID of the memory card (e.g. "myMemory-2")
 * @return bool  True if glimmer cast was performed
 */
function TryGlimmerCast($player, $memoryMZ) {
    $parts = explode("-", $memoryMZ);
    if($parts[0] !== "myMemory") return false;

    // Verify Diao Chan inherited ability is available
    if(!ChampionHasInLineage($player, "00xbh8oc00")) return false;

    $champMZ = FindChampionMZ($player);
    if($champMZ === null) return false;
    $champObj = &GetZoneObject($champMZ);
    if($champObj->Status != 2 || HasNoAbilities($champObj)) return false;

    $memObj = GetZoneObject($memoryMZ);
    if($memObj === null || $memObj->removed) return false;

    $spellCost = intval(CardCost_reserve($memObj->CardID));
    $glimmerCount = GetCounterCount($champObj, "glimmer");
    if($spellCost > $glimmerCount) return false;

    // REST the champion
    $champObj->Status = 1;

    // Remove glimmer counters to pay the cost
    RemoveCounters($player, $champMZ, "glimmer", $spellCost);

    // Track that this card was activated from memory
    DecisionQueueController::StoreVariable("activationSourceZone", "myMemory");

    // Move spell from memory to hand, then activate with ignoreCost=true
    MZMove($player, $memoryMZ, "myHand");
    $hand = &GetHand($player);
    $handIdx = count($hand) - 1;
    ActivateCard($player, "myHand-" . $handIdx, true);

    return true;
}

// --- Shifting Currents Mastery ---

/**
 * Check if a player's champion is Kongming (any level).
 */
function IsKongmingBonus($player) {
    return ChampionHasInLineage($player, "346vgwz3y4")  // Kongming, Wayward Maven
        || ChampionHasInLineage($player, "a01pyxwo25")  // Kongming, Ascetic Vice
        || ChampionHasInLineage($player, "7x2v4tdop1") // Kongming, Fel Eidolon
        || ChampionHasInLineage($player, "0i139x5eub"); // Kongming, Erudite Strategist
}

/**
 * Check if a player's champion is Diao Chan (any level).
 */
function IsDiaoChanBonus($player) {
    return ChampionHasInLineage($player, "00xbh8oc00")  // Diao Chan L1
        || ChampionHasInLineage($player, "pknaxnn0xo")  // Diao Chan L2
        || ChampionHasInLineage($player, "d7l6i5thdy"); // Diao Chan L3
}

/**
 * Check if a player currently has the Shifting Currents mastery active.
 */
function HasShiftingCurrents($player) {
    $mastery = &GetMastery($player);
    return !empty($mastery) && $mastery[0]->CardID === "qh5mpkyl60";
}

/**
 * Get the current Shifting Currents direction for a player.
 * Returns "NORTH", "SOUTH", "EAST", "WEST", or "NONE" if no mastery.
 */
function GetShiftingCurrents($player) {
    $mastery = &GetMastery($player);
    if(empty($mastery) || $mastery[0]->CardID !== "qh5mpkyl60") return "NONE";
    return $mastery[0]->Direction;
}

/**
 * Adjacency check for Shifting Currents directions.
 * North/South are adjacent to East/West, but not to each other.
 */
function IsAdjacentDirection($from, $to) {
    $adjacency = [
        "NORTH" => ["EAST", "WEST"],
        "SOUTH" => ["EAST", "WEST"],
        "EAST" => ["NORTH", "SOUTH"],
        "WEST" => ["NORTH", "SOUTH"],
    ];
    return isset($adjacency[$from]) && in_array($to, $adjacency[$from]);
}

/**
 * Get all valid adjacent directions from a given direction.
 */
function GetAdjacentDirections($direction) {
    $adjacency = [
        "NORTH" => ["EAST", "WEST"],
        "SOUTH" => ["EAST", "WEST"],
        "EAST" => ["NORTH", "SOUTH"],
        "WEST" => ["NORTH", "SOUTH"],
    ];
    return $adjacency[$direction] ?? [];
}

/**
 * Grant a player the Shifting Currents mastery, initialized to NORTH.
 * Replaces any existing mastery.
 */
function GainShiftingCurrents($player) {
    $mastery = &GetMastery($player);
    // Clear any existing mastery
    while(count($mastery) > 0) array_splice($mastery, 0, 1);
    // Add the Shifting Currents mastery card with NORTH direction
    $obj = AddMastery($player, CardID:"qh5mpkyl60", Direction:"NORTH");
    return $obj;
}

/**
 * Central function: change a player's Shifting Currents direction and fire transition callbacks.
 * All direction changes MUST go through this function to ensure transition triggers fire.
 */
function ChangeShiftingCurrents($player, $newDirection) {
    global $shiftingCurrentsTransitions;
    $mastery = &GetMastery($player);
    if(empty($mastery) || $mastery[0]->CardID !== "qh5mpkyl60") return;
    $oldDirection = $mastery[0]->Direction;
    if($oldDirection === $newDirection) return;
    $mastery[0]->Direction = $newDirection;

    // Fire transition callbacks for cards on this player's field
    $transitionKey = $oldDirection . "->" . $newDirection;
    if(!isset($shiftingCurrentsTransitions[$transitionKey])) return;

    $field = &GetField($player);
    foreach($shiftingCurrentsTransitions[$transitionKey] as $cardID => $callback) {
        for($i = 0; $i < count($field); ++$i) {
            if($field[$i]->removed) continue;
            if($field[$i]->CardID === $cardID && !HasNoAbilities($field[$i])) {
                $callback($player, "myField-" . $i);
            }
        }
    }

    // Also check inherited effects on the champion's lineage
    if(isset($shiftingCurrentsTransitions["INHERITED:" . $transitionKey])) {
        foreach($shiftingCurrentsTransitions["INHERITED:" . $transitionKey] as $cardID => $callback) {
            if(ChampionHasInLineage($player, $cardID)) {
                $callback($player, null);
            }
        }
    }
}

// --- Shifting Currents Transition Registry ---
// Keyed by "FROM->TO" direction string. Each entry maps cardID => callback($player, $mzID).
// "INHERITED:FROM->TO" entries check champion lineage instead of field presence.
$shiftingCurrentsTransitions = [];

// Kongming, Ascetic Vice (a01pyxwo25): Inherited — N→S: draw a card
$shiftingCurrentsTransitions["INHERITED:NORTH->SOUTH"]["a01pyxwo25"] = function($player, $mzID) {
    Draw($player, 1);
};

// Hydroguard Retainer (0qm7n87o4s): N→W: draw a card
$shiftingCurrentsTransitions["NORTH->WEST"]["0qm7n87o4s"] = function($player, $mzID) {
    Draw($player, 1);
};

// Tailwind's Blessing (oh5n2sjk0u): N→W: allies you control get +1 POWER until EOT
$shiftingCurrentsTransitions["NORTH->WEST"]["oh5n2sjk0u"] = function($player, $mzID) {
    AddGlobalEffects($player, "oh5n2sjk0u");
};

// Gem of Searing Flame (v1jaidvvz2): N→W: deal 2 damage to target champion
$shiftingCurrentsTransitions["NORTH->WEST"]["v1jaidvvz2"] = function($player, $mzID) {
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
        tooltip:"Deal_2_to_your_champion?_(No=opponent)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "GemOfSearingFlameDamage", 1);
};

// Wuji of Lingering Fate (9cef7aknvn): W→E: may sacrifice CARDNAME, if you do, target player mills 3
$shiftingCurrentsTransitions["WEST->EAST"]["9cef7aknvn"] = function($player, $mzID) {
    DecisionQueueController::StoreVariable("WujiMZ", $mzID);
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
        tooltip:"Sacrifice_Wuji_to_mill_3?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "WujiSacrifice", 1);
};

// Ruinous Pillars of Qidao (pmx99jrukm): W→E: sacrifice CARDNAME, destroy target non-champion object opponent controls
$shiftingCurrentsTransitions["WEST->EAST"]["pmx99jrukm"] = function($player, $mzID) {
    // Mandatory sacrifice + destroy
    MZMove($player, $mzID, "myGraveyard");
    DecisionQueueController::CleanupRemovedCards();
    // Target non-champion object opponent controls
    $theirField = GetZone("theirField");
    $targets = [];
    for($i = 0; $i < count($theirField); ++$i) {
        if(!$theirField[$i]->removed && !PropertyContains(EffectiveCardType($theirField[$i]), "CHAMPION")) {
            $targets[] = "theirField-" . $i;
        }
    }
    if(!empty($targets)) {
        $choices = implode("&", $targets);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $choices, 1, "Destroy_target_non-champion_object");
        DecisionQueueController::AddDecision($player, "CUSTOM", "RuinousPillarsDestroy", 1);
    }
};

// Pupil of Sacred Flames (n06isycm60): On Death + East → draw + change direction
// (This is an OnDeath trigger, not a transition trigger. Handled in AllyDestroyed.)

// Wulin Lancer (1i2luu7dft): N→W: CARDNAME gets +2 POWER until end of turn
$shiftingCurrentsTransitions["NORTH->WEST"]["1i2luu7dft"] = function($player, $mzID) {
    AddTurnEffect($mzID, "1i2luu7dft");
};

// Solar Providence (gnj9hi5ult): S→N: deal 3 damage to target champion you don't control
$shiftingCurrentsTransitions["SOUTH->NORTH"]["gnj9hi5ult"] = function($player, $mzID) {
    $opponent = ($player == 1) ? 2 : 1;
    DealChampionDamage($opponent, 3);
};

// --- Shifting Currents Custom DQ Handlers ---

$customDQHandlers["GemOfSearingFlameDamage"] = function($player, $params, $lastDecision) {
    if($lastDecision === "YES") {
        DealChampionDamage($player, 2);
    } else {
        $opponent = ($player == 1) ? 2 : 1;
        DealChampionDamage($opponent, 2);
    }
};

$customDQHandlers["WujiSacrifice"] = function($player, $params, $lastDecision) {
    if($lastDecision !== "YES") return;
    $mzID = DecisionQueueController::GetVariable("WujiMZ");
    $obj = GetZoneObject($mzID);
    if($obj === null || $obj->removed) return;
    MZMove($player, $mzID, "myGraveyard");
    DecisionQueueController::CleanupRemovedCards();
    // Target player mills 3
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
        tooltip:"Mill_yourself?_(No=mill_opponent)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "WujiMill", 1);
};

$customDQHandlers["WujiMill"] = function($player, $params, $lastDecision) {
    $target = ($lastDecision === "YES") ? $player : (($player == 1) ? 2 : 1);
    $deck = &GetDeck($target);
    for($i = 0; $i < 3 && count($deck) > 0; ++$i) {
        MZMove($target, ($target == $player ? "myDeck" : "theirDeck") . "-0", ($target == $player ? "myGraveyard" : "theirGraveyard"));
    }
};

$customDQHandlers["RuinousPillarsDestroy"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-") return;
    AllyDestroyed($player, $lastDecision);
};

$customDQHandlers["ChangeShiftingCurrentsChoice"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-") return;
    ChangeShiftingCurrents($player, $lastDecision);
};

$customDQHandlers["ChangeShiftingCurrentsAdjacentChoice"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-") return;
    $current = GetShiftingCurrents($player);
    if(IsAdjacentDirection($current, $lastDecision)) {
        ChangeShiftingCurrents($player, $lastDecision);
    }
};

$customDQHandlers["UsurpTheWindsDirectionDraw"] = function($player, $params, $lastDecision) {
    // Usurp the Winds Kongming Bonus: if direction changed from West to South, draw a card
    $oldDir = DecisionQueueController::GetVariable("UsurpOldDir");
    $newDir = GetShiftingCurrents($player);
    if($oldDir === "WEST" && $newDir === "SOUTH") {
        Draw($player, 1);
    }
};

// Helper: queue an ICONCHOICE decision for the player to choose a new SC direction.
// $mode = "any" (any different direction) or "adjacent" (only adjacent directions).
// $optional = true for MayChoose (can skip), false for mandatory.
function QueueShiftingCurrentsChoice($player, $mode = "any", $optional = true) {
    $current = GetShiftingCurrents($player);
    if($current === "NONE") return;
    $allDirs = ["NORTH", "SOUTH", "EAST", "WEST"];
    if($mode === "adjacent") {
        $options = GetAdjacentDirections($current);
    } else {
        $options = array_values(array_diff($allDirs, [$current]));
    }
    if(empty($options)) return;
    $param = implode("&", $options) . "|" . $current . "|qh5mpkyl60";
    $tooltip = "Choose_a_new_Shifting_Currents_direction";
    if($optional) {
        DecisionQueueController::AddDecision($player, "ICONCHOICE", $param, 1, $tooltip);
    } else {
        DecisionQueueController::AddDecision($player, "ICONCHOICE", $param, 1, $tooltip);
    }
    $handler = ($mode === "adjacent") ? "ChangeShiftingCurrentsAdjacentChoice" : "ChangeShiftingCurrentsChoice";
    DecisionQueueController::AddDecision($player, "CUSTOM", $handler, 1);
}

// Sage Protection (fqsa372jii): recursive choose up to 3 allies for +1 LIFE
function SageProtectionChoose($player, $count) {
    if($count >= 3) return;
    $allies = ZoneSearch("myField", ["ALLY"]);
    $allies = FilterSpellshroudTargets($allies);
    if(empty($allies)) return;
    $allyStr = implode("&", $allies);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $allyStr, 1, tooltip:"Choose_ally_for_+1_LIFE_(" . ($count + 1) . "/3)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SageProtectionApply|$count", 1);
}

$customDQHandlers["SageProtectionApply"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-") return;
    AddTurnEffect($lastDecision, "fqsa372jii");
    $count = intval($params[0]) + 1;
    if($count < 3) {
        SageProtectionChoose($player, $count);
    }
};

$customDQHandlers["MaChaoBanish"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-") return;
    MZMove($player, $lastDecision, "theirBanish");
};

$customDQHandlers["SolarProvidenceDiscard"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-") return;
    MZMove($player, $lastDecision, "myGraveyard");
};

$customDQHandlers["SolarProvidenceSCChoice"] = function($player, $params, $lastDecision) {
    QueueShiftingCurrentsChoice($player, "any", true);
};

/**
 * Strategem of Myriad Ice (id0ybub247): recursive banish loop.
 * Each iteration: find floating memory cards in graveyard, MayChoose one,
 * banish it and deal 3 damage to target unit opponent controls, then repeat.
 */
function StrategemBanishLoop($player, $sourceMZ) {
    $floatingGrave = ZoneSearch("myGraveyard", floatingMemoryOnly: true);
    if(empty($floatingGrave)) return;
    $targetStr = implode("&", $floatingGrave);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1,
        "Banish_a_floating_memory_card_from_graveyard");
    DecisionQueueController::AddDecision($player, "CUSTOM", "StrategemBanish|" . $sourceMZ, 1);
}

$customDQHandlers["StrategemBanish"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "PASS") return;
    $sourceMZ = $params[0] ?? "";
    // Banish chosen card
    MZMove($player, $lastDecision, "myBanish");
    NicoOnFloatingMemoryBanished($player);
    // Deal 3 damage to target unit opponent controls
    $enemies = array_merge(
        ZoneSearch("theirField", ["ALLY"]),
        ZoneSearch("theirField", ["CHAMPION"])
    );
    $enemies = FilterSpellshroudTargets($enemies);
    if(!empty($enemies)) {
        $choices = implode("&", $enemies);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $choices, 1,
            "Deal_3_damage_to_target_unit");
        DecisionQueueController::AddDecision($player, "CUSTOM", "StrategemDealDamage|" . $sourceMZ, 1);
    }
    // Continue loop
    StrategemBanishLoop($player, $sourceMZ);
};

$customDQHandlers["StrategemDealDamage"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-") return;
    $sourceMZ = $params[0] ?? "";
    DealDamage($player, $sourceMZ, $lastDecision, 3);
};

// Channel Manifold Desire (kywpjf1b4k): process banished preserved card
$customDQHandlers["ChannelManifoldBanish"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-") return;
    // Get the reserve cost of the chosen card before banishing
    $obj = GetZoneObject($lastDecision);
    if($obj === null) return;
    $reserveCost = CardCost_reserve($obj->CardID);
    // Banish the preserved card from material deck
    MZMove($player, $lastDecision, "myBanish");
    // Empower X+2 (X = reserve cost of banished card)
    $empowerAmount = $reserveCost + 2;
    // Encode the empower amount in the effect ID
    $champions = ZoneSearch("myField", ["CHAMPION"]);
    if(!empty($champions)) {
        AddTurnEffect($champions[0], "kywpjf1b4k-" . $empowerAmount);
    }
    // If SC faces North, may put a preserved card from material deck into hand
    if(GetShiftingCurrents($player) === "NORTH") {
        global $Preserve_Cards;
        $material = GetZone("myMaterial");
        $preserved = [];
        for($i = 0; $i < count($material); $i++) {
            if(isset($Preserve_Cards[$material[$i]->CardID])) {
                $preserved[] = "myMaterial-" . $i;
            }
        }
        if(!empty($preserved)) {
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $preserved), 1,
                "Return_a_preserved_card_to_hand?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "ChannelManifoldReturn", 1);
        }
    }
};

$customDQHandlers["ChannelManifoldReturn"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myHand");
};

// Bagua of Cardinal Fate (jgyx38zpl0): North — put a buff counter on chosen ally
$customDQHandlers["BaguaNorthBuff"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-") return;
    AddCounters($player, $lastDecision, "buff", 1);
};

// Bagua of Cardinal Fate (jgyx38zpl0): South — return chosen ally to hand
$customDQHandlers["BaguaSouthReturn"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-") return;
    MZMove($player, $lastDecision, "myHand");
};

// Solar Pinnacle (zeig1e49wb): deal 2 damage to chosen unit
$customDQHandlers["SolarPinnacleDamage"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-") return;
    $sourceMZ = $params[0] ?? "";
    DealDamage($player, $sourceMZ, $lastDecision, 2);
};

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
 * Check if a unit (given by mzID from current player perspective) is the
 * current combat target (defender).
 */
function IsUnitDefending($mzTarget) {
    $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
    if($combatTarget === null || $combatTarget === "" || $combatTarget === "-") return false;

    global $playerID;
    $turnPlayer = GetTurnPlayer();

    // CombatTarget was stored from the turn player's (attacker's) perspective.
    // If current player is NOT the turn player, flip to match perspective.
    $normalizedTarget = $combatTarget;
    if($playerID != $turnPlayer) {
        $normalizedTarget = FlipZonePerspective($combatTarget);
    }

    return $mzTarget === $normalizedTarget;
}

/**
 * Get the combat attacker's mzID from the current player's perspective.
 * Returns null if no combat is active.
 */
function GetCombatAttackerMZ() {
    $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
    if($combatAttacker === null || $combatAttacker === "" || $combatAttacker === "-") return null;

    global $playerID;
    $turnPlayer = GetTurnPlayer();

    if($playerID != $turnPlayer) {
        return FlipZonePerspective($combatAttacker);
    }
    return $combatAttacker;
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
// Cards with [Class Bonus] Fast Activation — cardID => required class(es)
$cbFastActivationCards = [
    "a5igwbsmks" => ["TAMER"], // Spirited Falconer
    "itwys9kf4r" => ["ASSASSIN"], // Cloaked Executioner
    "cqadnk9iz0" => ["TAMER"], // Baby Green Slime
    "2bbmoqk2c7" => ["GUARDIAN"], // Rose, Eternal Paragon
    "f0ht2tsn0y" => ["GUARDIAN"], // Astarte, Celestial Dawn
    "jozihslnhz" => ["ASSASSIN"], // Sinister Mindreaver
];

// Cards with unconditional Fast Activation (no class bonus required)
$unconditionalFastCards = [
    "aljx2ru1w3" => true, // Flashfire Horse
];

function GetPlayableFastCards($player) {
    global $cbFastActivationCards, $unconditionalFastCards;
    $hand = &GetHand($player);
    $fastCards = [];
    for($i = 0; $i < count($hand); $i++) {
        $obj = $hand[$i];
        if(isset($obj->removed) && $obj->removed) continue;
        $speed = CardSpeed($obj->CardID);
        if($speed === true) {
            $fastCards[] = "myHand-" . $i;
        } elseif(isset($cbFastActivationCards[$obj->CardID]) && IsClassBonusActive($player, $cbFastActivationCards[$obj->CardID])) {
            $fastCards[] = "myHand-" . $i;
        } elseif(isset($unconditionalFastCards[$obj->CardID])) {
            $fastCards[] = "myHand-" . $i;
        } elseif($obj->CardID === "zeig1e49wb" && GetShiftingCurrents($player) === "NORTH") {
            // Solar Pinnacle: Fast Activation while SC faces North
            $fastCards[] = "myHand-" . $i;
        } elseif($obj->CardID === "0oyxjld8jh") {
            // Guan Yu, Prime Exemplar: Fast Activation if a Human ally you controlled died this turn
            $deadAllies = AllyDestroyedTurnCards($player);
            foreach($deadAllies as $deadCardID => $deadCount) {
                if(PropertyContains(CardSubtypes($deadCardID), "HUMAN")) {
                    $fastCards[] = "myHand-" . $i;
                    break;
                }
            }
        }
    }

    // Diao Chan inherited ability: include eligible Reaction Spells from memory
    // Only when it's the opponent's turn, champion is awake, and has glimmer
    $turnPlayer = GetTurnPlayer();
    if($player != $turnPlayer && ChampionHasInLineage($player, "00xbh8oc00")) {
        $champObj = GetPlayerChampion($player);
        if($champObj !== null && $champObj->Status == 2 && !HasNoAbilities($champObj)) {
            $glimmerCount = GetCounterCount($champObj, "glimmer");
            if($glimmerCount > 0) {
                $memory = &GetMemory($player);
                for($mi = 0; $mi < count($memory); ++$mi) {
                    if(isset($memory[$mi]->removed) && $memory[$mi]->removed) continue;
                    $memCardID = $memory[$mi]->CardID;
                    if(PropertyContains(CardSubtypes($memCardID), "REACTION")
                        && PropertyContains(CardSubtypes($memCardID), "SPELL")) {
                        $spellCost = intval(CardCost_reserve($memCardID));
                        if($spellCost <= $glimmerCount) {
                            $fastCards[] = "myMemory-" . $mi;
                        }
                    }
                }
            }
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
        if(!TryGlimmerCast($player, $lastDecision)) {
            ActivateCard($player, $lastDecision, false);
        }
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
        if(!TryGlimmerCast($player, $lastDecision)) {
            ActivateCard($player, $lastDecision, false);
        }
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
    if(empty($effectStack)) return;

    $topIndex = count($effectStack) - 1;
    $topObj = $effectStack[$topIndex];
    if($topObj == null) {
        $topIndex = $topIndex - 1;
        if($topIndex < 0) return;
        $topObj = $effectStack[$topIndex];
        if($topObj == null) return;
    }
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
        if(!TryGlimmerCast($player, $lastDecision)) {
            ActivateCard($player, $lastDecision, false);
        }
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
        if(!TryGlimmerCast($player, $lastDecision)) {
            ActivateCard($player, $lastDecision, false);
        }
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
    // Censer of Restful Peace (0nlhgqpckq): cards in graveyards lose all abilities (including floating memory)
    if(ZoneContainsCardID("myField", "0nlhgqpckq") || ZoneContainsCardID("theirField", "0nlhgqpckq")) return false;
    if(HasKeyword_FloatingMemory($obj)) return true;
    // Intrepid Highwayman (WUAOMTZ7P2): [Class Bonus] Floating Memory
    if($obj->CardID === "WUAOMTZ7P2" && IsClassBonusActive($obj->Controller, ["ASSASSIN"])) return true;
    // Firetuned Automaton (lzjmwuir99): [Class Bonus] Floating Memory
    if($obj->CardID === "lzjmwuir99" && IsClassBonusActive($obj->Controller, ["GUARDIAN"])) return true;
    // Freezing Round (r7ch2bbmoq): [Class Bonus] Floating Memory
    if($obj->CardID === "r7ch2bbmoq" && IsClassBonusActive($obj->Controller, ["RANGER"])) return true;
    // Cell Converter (eqhj1trn0y): [Class Bonus] Floating Memory
    if($obj->CardID === "eqhj1trn0y" && IsClassBonusActive($obj->Controller, ["CLERIC"])) return true;
    // Relic of Sunken Past (dqqwey9xys): [Class Bonus] Floating Memory
    if($obj->CardID === "dqqwey9xys" && IsClassBonusActive($obj->Controller, ["TAMER"])) return true;
    // Nanyue Portsman (v5ppxyu1jm): [Class Bonus] Floating Memory
    if($obj->CardID === "v5ppxyu1jm" && IsClassBonusActive($obj->Controller, ["GUARDIAN", "WARRIOR"])) return true;
    // Shu Frontliner (uhaao91ee1): [Class Bonus] Floating Memory
    if($obj->CardID === "uhaao91ee1" && IsClassBonusActive($obj->Controller, ["WARRIOR"])) return true;
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

// Brackish Lutist (1clswn3ba2): check if any Brackish Lutist is on the field with abilities
function IsBrackishLutistOnField() {
    for($p = 1; $p <= 2; ++$p) {
        $field = &GetField($p);
        foreach($field as $fObj) {
            if(!$fObj->removed && $fObj->CardID === "1clswn3ba2" && !HasNoAbilities($fObj)) return true;
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
    // Censer of Restful Peace (0nlhgqpckq): cards in graveyards lose all abilities
    if(isset($obj->Location) && $obj->Location === 'Graveyard') {
        if(ZoneContainsCardID("myField", "0nlhgqpckq") || ZoneContainsCardID("theirField", "0nlhgqpckq")) {
            return true;
        }
    }
    return false;
}

/**
 * Check if a card ID represents a token (has TOKEN in its type).
 */
function IsToken($cardID) {
    return PropertyContains(CardType($cardID), "TOKEN");
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
    // Zone override: cards in memory become NORM while opponent controls Nullifying Mirror (pol1nz0j1n)
    if(isset($obj->Location) && $obj->Location === 'Memory') {
        global $playerID;
        $controller = $obj->Controller ?? 0;
        $opponentZone = ($controller == $playerID) ? "theirField" : "myField";
        if(ZoneContainsCardID($opponentZone, "pol1nz0j1n")) {
            // Check the mirror is not exhausted and has no abilities suppressed
            $mirrorField = GetZone($opponentZone);
            foreach($mirrorField as $mfObj) {
                if(!$mfObj->removed && $mfObj->CardID === "pol1nz0j1n" && !HasNoAbilities($mfObj)) {
                    return "NORM";
                }
            }
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
    // Ally Link: Winbless Kiteshield (uoy5ttkat9): linked unit has vigor
    $linkedCards = GetLinkedCards($obj);
    foreach($linkedCards as $linkedObj) {
        if($linkedObj->CardID === "80mttsvbgl") return true;
        if($linkedObj->CardID === "uoy5ttkat9") return true;
    }
    // Awakened Frostguard (mnu1xhs5jw): vigor while fostered
    if($obj->CardID === "mnu1xhs5jw" && IsFostered($obj)) return true;
    // Zhang Fei, Spirited Steel (qxnv0jqeym): [CB] Vigor
    if($obj->CardID === "qxnv0jqeym" && IsClassBonusActive($obj->Controller, ["WARRIOR"])) return true;
    // Dilu, Auspicious Charger (du4eaktghh): vigor while you control a wind unique Human ally
    if($obj->CardID === "du4eaktghh") {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fObj) {
            if(!$fObj->removed && $fObj->CardID !== "du4eaktghh"
                && PropertyContains(EffectiveCardType($fObj), "ALLY")
                && PropertyContains(CardType($fObj->CardID), "UNIQUE")
                && PropertyContains(EffectiveCardSubtypes($fObj), "HUMAN")
                && CardElement($fObj->CardID) === "WIND") {
                return true;
            }
        }
    }
    // Ritai Guard (jbc30d18ys): [CB] Equestrian — Vigor while you control a Horse ally
    if($obj->CardID === "jbc30d18ys" && IsClassBonusActive($obj->Controller, ["GUARDIAN"])) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        if(!empty(ZoneSearch($zone, ["ALLY"], cardSubtypes: ["HORSE"]))) {
            return true;
        }
    }
    // Brash Defender (i1sh9r9rda): [Level 1+] Vigor — HasKeyword_Vigor uses null PlayerID for level check
    if($obj->CardID === "i1sh9r9rda" && PlayerLevel($obj->Controller) >= 1) return true;
    // Ma Chao, Lupine Huntress (fw8yvhf3mz): [CB][Level 2+] Vigor
    if($obj->CardID === "fw8yvhf3mz" && IsClassBonusActive($obj->Controller, ["TAMER"]) && PlayerLevel($obj->Controller) >= 2) return true;
    // Rilewind Sentinel (y1utsihaxv): while fostered, allies you control have vigor
    if(PropertyContains(EffectiveCardType($obj), "ALLY")) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fObj) {
            if(!$fObj->removed && $fObj->CardID === "y1utsihaxv" && !HasNoAbilities($fObj) && IsFostered($fObj)) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Check whether a field object currently has Steadfast.
 * Steadfast: "This ally can retaliate while rested and doesn't rest to do so."
 */
function HasSteadfast($obj) {
    if(HasNoAbilities($obj)) return false;
    // Generated keyword dictionary (handles Class Bonus conditions automatically)
    if(HasKeyword_Steadfast($obj)) return true;
    // sl7ddcgw05: missed by keyword parser (comma-separated keyword line)
    if($obj->CardID === "sl7ddcgw05") return true;
    // Krustallan Patrol (8sugly4wif): Steadfast while fostered
    if($obj->CardID === "8sugly4wif" && IsFostered($obj)) return true;
    // Jadelight Protector (o18wr3f4ab): allies have Steadfast while Shifting Currents face South
    if(PropertyContains(EffectiveCardType($obj), "ALLY")) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fObj) {
            if(!$fObj->removed && $fObj->CardID === "o18wr3f4ab" && !HasNoAbilities($fObj)
                && GetShiftingCurrents($obj->Controller) === "SOUTH") {
                return true;
            }
        }
    }
    // TurnEffect-based Steadfast (e.g. from Mortal Ambition)
    if(in_array("STEADFAST", $obj->TurnEffects)) return true;
    return false;
}

/**
 * Check whether a field object currently has Retort.
 * Retort N: "As long as this ally is retaliating, it gets +N [POWER]."
 * Sources:
 *   - Static keyword from generated dictionary (HasKeyword_Retort)
 *   - 0oyxjld8jh (Guan Yu, Prime Exemplar): Retort 2 (missed by parser — comma-separated keyword line)
 *   - o18wr3f4ab (Jadelight Protector): allies have Retort 1 while Shifting Currents face South
 *   - TurnEffect "RETORT_N" (e.g. granted by spells/abilities until end of turn)
 */
function HasRetort($obj) {
    if(HasNoAbilities($obj)) return false;
    if(HasKeyword_Retort($obj)) return true;
    // 0oyxjld8jh (Guan Yu, Prime Exemplar): Retort 2 — missed by parser (comma-separated keyword line)
    if($obj->CardID === "0oyxjld8jh") return true;
    // Jadelight Protector (o18wr3f4ab): allies have Retort 1 while Shifting Currents face South
    if(PropertyContains(EffectiveCardType($obj), "ALLY")) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fObj) {
            if(!$fObj->removed && $fObj->CardID === "o18wr3f4ab" && !HasNoAbilities($fObj)
                && GetShiftingCurrents($obj->Controller) === "SOUTH") {
                return true;
            }
        }
    }
    // TurnEffect-based Retort (e.g. granted by spells/abilities until end of turn)
    foreach($obj->TurnEffects as $te) {
        if(strpos($te, "RETORT_") === 0) return true;
    }
    return false;
}

/**
 * Returns the numeric Retort bonus for a field object, or 0 if it has no Retort.
 */
function GetRetortValue($obj) {
    $val = intval(GetKeyword_Retort_Value($obj));
    if($val > 0) return $val;
    if($obj->CardID === "0oyxjld8jh") return 2;
    // Jadelight Protector (o18wr3f4ab): Retort 1 while Shifting Currents face South
    if(PropertyContains(EffectiveCardType($obj), "ALLY")) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fObj) {
            if(!$fObj->removed && $fObj->CardID === "o18wr3f4ab" && !HasNoAbilities($fObj)
                && GetShiftingCurrents($obj->Controller) === "SOUTH") {
                return 1;
            }
        }
    }
    // TurnEffect-based Retort — value encoded as "RETORT_N"
    $maxTE = 0;
    foreach($obj->TurnEffects as $te) {
        if(strpos($te, "RETORT_") === 0) {
            $maxTE = max($maxTE, intval(substr($te, 7)));
        }
    }
    return $maxTE;
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
    // Imperial Spy (l6gt7lh9v2): [Class Bonus] Stealth
    if($obj->CardID === "l6gt7lh9v2") {
        if(IsClassBonusActive($obj->Controller, ["ASSASSIN"])) return true;
    }
    // Sly Songstress (f28y5rn0dt): [Class Bonus] Stealth
    if($obj->CardID === "f28y5rn0dt") {
        if(IsClassBonusActive($obj->Controller, ["TAMER"])) return true;
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
    // Jueying, Shadowmare (c3plbuv3fr): stealth while you control a water unique Human ally
    if($obj->CardID === "c3plbuv3fr") {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fObj) {
            if(!$fObj->removed && $fObj->CardID !== "c3plbuv3fr"
                && PropertyContains(EffectiveCardType($fObj), "ALLY")
                && PropertyContains(CardType($fObj->CardID), "UNIQUE")
                && PropertyContains(EffectiveCardSubtypes($fObj), "HUMAN")
                && CardElement($fObj->CardID) === "WATER") {
                return true;
            }
        }
    }
    // Hidden Longbowman (bx4k3akqx7): stealth while distant
    if($obj->CardID === "bx4k3akqx7" && IsDistant($obj)) return true;
    if(HasKeyword_Stealth($obj)) return true;
    // STEALTH: granted stealth until end of turn (e.g. Vanish from Sight, Sidestep)
    if(in_array("STEALTH", $obj->TurnEffects)) return true;
    // STEALTH_NEXT_TURN: persistent stealth until beginning of controller's next turn (e.g. Zander)
    if(in_array("STEALTH_NEXT_TURN", $obj->TurnEffects)) return true;
    // Check for temporary stealth effects granted by other cards
    // Zhang He, Cloak of Night (09axbotwlz): target ally gains stealth for as long as you control Zhang He
    if(is_array($obj->Counters) && isset($obj->Counters['zhangHeStealth']) && $obj->Counters['zhangHeStealth']) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fieldObj) {
            if(!$fieldObj->removed && $fieldObj->CardID === "09axbotwlz" && !HasNoAbilities($fieldObj)) {
                return true;
            }
        }
    }
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

// Siegeable: domain subtype that allows being attacked. Damage removes durability counters.
function IsSiegeable($obj) {
    return PropertyContains(CardSubtypes($obj->CardID), "SIEGEABLE");
}

function HasTrueSight($obj) {
    if(HasNoAbilities($obj)) return false;
    if(HasKeyword_TrueSight($obj)) return true;
    if(in_array("TRUE_SIGHT", $obj->TurnEffects)) return true;
    if(ObjectHasEffect($obj, "iiZtKTulPg")) return true; // Eye of Argus
    if(ObjectHasEffect($obj, "F1t18omUlx_SIGHT")) return true; // Beastbond Paws
    if(ObjectHasEffect($obj, "i1f0ht2tsn_SIGHT")) return true; // Strategic Warfare
    // Seeking Shot: [Level 2+] True Sight
    if($obj->CardID === "88zq9ox7u6" && PlayerLevel($obj->Controller) >= 2) return true;
    // Jueying, Shadowmare (c3plbuv3fr): true sight while you control a water unique Human ally
    if($obj->CardID === "c3plbuv3fr") {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fObj) {
            if(!$fObj->removed && $fObj->CardID !== "c3plbuv3fr"
                && PropertyContains(EffectiveCardType($fObj), "ALLY")
                && PropertyContains(CardType($fObj->CardID), "UNIQUE")
                && PropertyContains(EffectiveCardSubtypes($fObj), "HUMAN")
                && CardElement($fObj->CardID) === "WATER") {
                return true;
            }
        }
    }
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
    if(function_exists('HasKeyword_Spellshroud') && HasKeyword_Spellshroud($obj)) return true;
    if(in_array("SPELLSHROUD", $obj->TurnEffects)) return true;
    if(in_array("SPELLSHROUD_NEXT_TURN", $obj->TurnEffects)) return true;
    // Innervate Agility: units gain spellshroud until EOT via global effect
    $effects = explode(",", CardCurrentEffects($obj));
    if(in_array("INNERVATE_SPELLSHROUD", $effects)) return true;
    // Rippleback Terrapin (srkomr8ght): [CB] Spellshroud
    if($obj->CardID === "srkomr8ght" && IsClassBonusActive($obj->Controller, ["TAMER"])) return true;
    // Maiden of Shrouded Fog (wum3f33kay): [CB] Spellshroud
    if($obj->CardID === "wum3f33kay" && IsClassBonusActive($obj->Controller, ["CLERIC"])) return true;
    // Twilight Slime (62u1231c0z): [CB] champion and other Slime objects you control have spellshroud
    if($obj->CardID !== "62u1231c0z") {
        $isChampOrSlime = PropertyContains(EffectiveCardType($obj), "CHAMPION")
            || PropertyContains(CardSubtypes($obj->CardID), "SLIME");
        if($isChampOrSlime) {
            global $playerID;
            $zone = $obj->Controller == $playerID ? "myField" : "theirField";
            $field = GetZone($zone);
            foreach($field as $fieldObj) {
                if(!$fieldObj->removed && $fieldObj->CardID === "62u1231c0z" && !HasNoAbilities($fieldObj)
                    && IsClassBonusActive($obj->Controller, ["TAMER"])) {
                    return true;
                }
            }
        }
    }
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
    // Cordelia, Aurous Kaiser (4mwrg35j36): [CB GUARDIAN] Token objects you control have reservable
    if(PropertyContains(EffectiveCardType($obj), "TOKEN")) {
        $controller = $obj->Controller;
        global $playerID;
        $zone = $controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fieldObj) {
            if(!$fieldObj->removed && $fieldObj->CardID === "4mwrg35j36" && !HasNoAbilities($fieldObj)
                && IsClassBonusActive($controller, ["GUARDIAN"])) {
                return true;
            }
        }
    }
    // Dissonant Fractal (2d7rgchttu): reservable while you control 2+ other phantasias
    if($obj->CardID === "2d7rgchttu") {
        $controller = $obj->Controller;
        global $playerID;
        $zone = $controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        $phantasiaCount = 0;
        foreach($field as $fieldObj) {
            if(!$fieldObj->removed && $fieldObj->CardID !== "2d7rgchttu"
                && PropertyContains(EffectiveCardType($fieldObj), "PHANTASIA")) {
                $phantasiaCount++;
            }
        }
        if($phantasiaCount >= 2) return true;
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
    // Claude, Fated Visionary (52215upufy): Automaton allies you control have Taunt
    if(PropertyContains(EffectiveCardType($obj), "ALLY") && PropertyContains(EffectiveCardSubtypes($obj), "AUTOMATON")) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $claudeObj) {
            if(!$claudeObj->removed && $claudeObj->CardID === "52215upufy" && !HasNoAbilities($claudeObj)) {
                return true;
            }
        }
    }
    // Powered Defender (z07nau5sw9): [Class Bonus][Level 2+] Taunt
    if($obj->CardID === "z07nau5sw9") {
        if(IsClassBonusActive($obj->Controller, ["GUARDIAN"]) && PlayerLevel($obj->Controller) >= 2) {
            return true;
        }
    }
    // Ritai Guard (jbc30d18ys): [CB] Equestrian — Taunt while you control a Horse ally
    if($obj->CardID === "jbc30d18ys" && IsClassBonusActive($obj->Controller, ["GUARDIAN"])) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        if(!empty(ZoneSearch($zone, ["ALLY"], cardSubtypes: ["HORSE"]))) {
            return true;
        }
    }
    // Tidebreaker Sentinel (3kwkn38b7v): [CB] taunt while fostered
    if($obj->CardID === "3kwkn38b7v" && IsFostered($obj)) {
        if(IsClassBonusActive($obj->Controller, ["GUARDIAN"])) {
            return true;
        }
    }
    return false;
}

// =============================================================================
// Foster Keyword
// =============================================================================

/**
 * Check whether a field object has the Foster keyword.
 * Foster: "At the beginning of your recollection phase, if this ally hasn't been
 * dealt damage since the end of your previous turn, it becomes fostered."
 */
function HasFoster($obj) {
    if(HasNoAbilities($obj)) return false;
    // Unconditional Foster cards
    static $fosterCards = [
        "z4pyx8bd7o" => true, // Young Peacekeeper
        "kuz07nk45s" => true, // Forgelight Shieldmaiden
        "bqjdmthh88" => true, // City Protector
        "22tk3ir1o0" => true, // Peacekeeper Sentinel (alt)
        "lzsmw3rrii" => true, // Guardian Bulwark
        "xhi5jnsl7d" => true, // Embershield Keeper
        "zihslnhzj4" => true, // Cell Generator
        "8sugly4wif" => true, // Krustallan Patrol
    ];
    if(isset($fosterCards[$obj->CardID])) return true;
    // [Class Bonus] Foster cards
    static $fosterCBCards = [
        "mnu1xhs5jw" => ["GUARDIAN"], // Awakened Frostguard
        "1x97n2jnlt" => ["GUARDIAN"], // Guardian Scout
        "a3v1ybmvpb" => ["GUARDIAN"], // Sunglory Sentinel
        "3kwkn38b7v" => ["GUARDIAN"], // Tidebreaker Sentinel
        "y1utsihaxv" => ["GUARDIAN"], // Rilewind Sentinel
    ];
    if(isset($fosterCBCards[$obj->CardID])) {
        return IsClassBonusActive($obj->Controller, $fosterCBCards[$obj->CardID]);
    }
    return false;
}

/**
 * Check whether a field object is currently in the fostered state.
 */
function IsFostered($obj) {
    return in_array("FOSTERED", $obj->TurnEffects);
}

/**
 * Make an ally become fostered by adding the FOSTERED TurnEffect.
 * @param int    $player The controller.
 * @param string $mzID   The mzID of the ally.
 */
function BecomeFostered($player, $mzID) {
    AddTurnEffect($mzID, "FOSTERED");
}

/**
 * Dispatch OnFoster triggered abilities for a card that just became fostered.
 * Called by RecollectionPhase when a Foster ally transitions to fostered state.
 */
function OnFosterTrigger($player, $mzID) {
    global $onFosterAbilities;
    $obj = GetZoneObject($mzID);
    if($obj === null) return;
    $CardID = $obj->CardID;
    if(HasNoAbilities($obj)) return;
    if(isset($onFosterAbilities) && isset($onFosterAbilities[$CardID . ":0"])) {
        $onFosterAbilities[$CardID . ":0"]($player);
    }
}

/**
 * Check whether a field object is currently distant.
 * Distant: "Units stay distant until the end of their controller's turn."
 * Sources:
 *   - TurnEffect "DISTANT" (until end of turn, standard source)
 *   - Freydis permanent distant: global forever effect makes all Ranger units always distant
 */
function IsDistant($obj) {
    if(HasNoAbilities($obj)) return false;
    if(in_array("DISTANT", $obj->TurnEffects)) return true;
    // Freydis permanent distant: Ranger units are always distant for the rest of the game
    if(GlobalEffectCount($obj->Controller, "FREYDIS_PERMANENT_DISTANT") > 0) {
        if(PropertyContains(EffectiveCardClasses($obj), "RANGER")) return true;
    }
    return false;
}

/**
 * Make a unit become distant by adding the DISTANT TurnEffect.
 * @param int    $player The acting player.
 * @param string $mzID   The mzID of the unit (e.g. "myField-2").
 */
function BecomeDistant($player, $mzID) {
    AddTurnEffect($mzID, "DISTANT");
    // Imperial Scout (nrow8iopvc): when this becomes distant, may mill 2
    $obj = GetZoneObject($mzID);
    if($obj !== null && $obj->CardID === "nrow8iopvc" && !HasNoAbilities($obj)) {
        $deck = ZoneSearch("myDeck");
        if(count($deck) >= 2) {
            DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Put_top_2_cards_of_deck_into_graveyard?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "ImperialScoutMill", 1);
        }
    }
    // Liu Bei, Oathkeeper (a53rqmuqxf): whenever another unit you control becomes distant,
    // Liu Bei becomes distant.
    if($obj === null || $obj->CardID !== "a53rqmuqxf") {
        global $playerID;
        $zone = $player == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fi => $fObj) {
            if(!$fObj->removed && $fObj->CardID === "a53rqmuqxf" && !HasNoAbilities($fObj)) {
                $liuBeiMZ = $zone . "-" . $fi;
                if(!in_array("DISTANT", $fObj->TurnEffects)) {
                    AddTurnEffect($liuBeiMZ, "DISTANT");
                }
            }
        }
    }
}

/**
 * Move all TempZone cards back to the top of the player's deck (preserving order).
 */
function PutTempZoneOnTopOfDeck($player) {
    $deck = &GetDeck($player);
    $tempZone = &GetTempZone($player);
    foreach($tempZone as $obj) {
        if(!$obj->removed) {
            $newDeckObj = new Deck($obj->CardID, 'Deck', $player);
            array_unshift($deck, $newDeckObj);
            $obj->Remove();
        }
    }
    for($i = 0; $i < count($deck); ++$i) {
        $deck[$i]->mzIndex = $i;
    }
}

/**
 * Dahlia OnAttack: finish after YesNo — put water card to GY or back on top of deck.
 */
function DahliaLookFinish($player, $answer) {
    if($answer === "YES") {
        $tempCards = ZoneSearch("myTempZone");
        if(!empty($tempCards)) {
            MZMove($player, $tempCards[0], "myGraveyard");
        }
    } else {
        PutTempZoneOnTopOfDeck($player);
    }
}

/**
 * Misteye Archer (m6c8xy4cje): look at top card of deck.
 * If water, offer to put into GY — if yes, become distant + prevent next 2 damage.
 */
function MisteyeArcherLook($player, $mzID) {
    $tempCards = ZoneSearch("myTempZone");
    if(empty($tempCards)) return;
    $topCard = GetZoneObject($tempCards[count($tempCards) - 1]);
    if($topCard !== null && CardElement($topCard->CardID) === "WATER") {
        DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Put_water_card_into_graveyard?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "MisteyeArcherFinish|" . $mzID, 1);
    } else {
        PutTempZoneOnTopOfDeck($player);
    }
}

// Static Ranged values: cardID => [value, classBonusRequired, classes]
// Class Bonus entries require the champion's class to match for Ranged to apply.
$rangedCardValues = [
    // ALC Distant & Ranged Ally Package
    "m4o98vn1vo" => [2, false, []], // Winbless Arbalest
    "609g44vm5k" => [2, false, []], // Airship Cruiser
    "66pv4n1n3g" => [2, true, ["RANGER"]], // Airship Engineer
    "d53zc9p4lp" => [4, true, ["RANGER"]], // Airship Cannoneer
    "eanl1gxrpx" => [1, false, []], // Lone Gunslinger
    "uhjxhkurfp" => [2, true, ["RANGER"]], // Trained Sharpshooter
    "ygojwk0pw0" => [4, false, []], // Automaton Bomber
    "wc8tuhuy4x" => [2, true, ["RANGER"]], // Fiery Duelist
    "3p6i0iqmyn" => [3, true, ["RANGER"]], // Krustallan Archer
    "xrpx8jypwc" => [2, true, ["RANGER"]], // Gloamspire Wraith
    "por7ch2bbm" => [2, false, []], // Relentless Hexchaser
    "nl1gxrpx8j" => [2, false, []], // Perse, Relentless Raptor
    // Other sets — Ranged allies/champions
    "ryvfq3huqj" => [2, false, []], // Polkhawk, Bombastic Shot
    "ki6fxxgmue" => [2, false, []], // Bertha, Spry Howitzer
    "4e1gqwah01" => [2, false, []], // Corsair Captain
    "iffei7chsb" => [2, false, []], // Charged Hunter
    "7i24g0nbxz" => [2, false, []], // Alacritous Huntress
    "bx4k3akqx7" => [2, false, []], // Hidden Longbowman
    "hohkep3vi9" => [2, false, []], // Dyadic Fletcher
    "9dqou3vgi8" => [2, false, []], // Marksman Captain
    "2nc48s3oqh" => [3, false, []], // Mad Hatter, Morose Heritor
    "gbnvtkm7rf" => [1, false, []], // Renascent Sharpshooter
    "svdv3zb9p4" => [2, false, []], // Charged Gunslinger
    "5yw862q547" => [4, false, []], // Flamebolt Arbalist
    "m3n9yvn1uo" => [2, false, []], // Volatile Fusilier
    "etaebjlwab" => [2, false, []], // Cell Sharpshooter
    "lgl8pux7v9" => [2, false, []], // Ghost Hunter
    "6hjlgx72rf" => [4, false, []], // Gloamspire Sniper
    "a53rqmuqxf" => [2, false, []], // Liu Bei, Oathkeeper
    "3w5wskifp2" => [2, false, []], // Waterlogged Ranger
    "rltyxefm80" => [2, false, []], // Outrider of Waves
    "5qyee9vkp8" => [2, false, []], // Seaside Rangefinder
    "7fqr67duh1" => [4, false, []], // Concealed Marksman
    "8gv9f4a0nk" => [3, false, []], // Galewind Scout
    "nrow8iopvc" => [2, false, []], // Imperial Scout
    "wkz77mbyj0" => [3, false, []], // Poised Bowman
    "fpvw2ifz1n" => [3, false, []], // Powered Armsmaster
    "58xpspudnf" => [2, false, []], // Skilled Aerotheurge
    "1mvv1f83ls" => [4, false, []], // Aethercloak Sentinel
    "oqk2c7wklz" => [5, false, []], // Shadecursed Hunter
    "lwuupowx4p" => [2, false, []], // Reconnaissance Scout
    "gc18dq28my" => [2, false, []], // Xia Hou Dun, Gloryseeker
    // Class Bonus Ranged — other sets
    "17fzcyfrzr" => [2, true, ["RANGER"]], // Imperial Rifleman
    "k6d4367ixj" => [2, true, ["RANGER"]], // Horse Archer (base; +1 from Horse handled dynamically)
    "inQV2nZfdJ" => [3, true, ["RANGER"]], // Alizarin Longbowman
    "fvyhuxzjk8" => [3, true, ["RANGER"]], // Seasoned Archer
    "m6c8xy4cje" => [2, true, ["RANGER"]], // Misteye Archer
    "lClyP34mj6" => [2, true, ["RANGER"]], // Mistral Ranger
    "ann23jkuys" => [2, true, ["RANGER"]], // Yunzhou Cavalry
    "fta5isdgrk" => [2, false, []], // Veteran Aerotheurge (base Ranged 2; CB adds +2 dynamically)
];

/**
 * Get the effective Ranged value for a field object.
 * Combines static lookup, class bonus checks, dynamic card-specific values,
 * TurnEffect-based grants, field-presence passives, and inherited effects.
 *
 * @param object $obj A field zone object.
 * @return int  The total Ranged value (0 if none).
 */
function GetRangedValue($obj) {
    if(HasNoAbilities($obj)) return 0;
    global $rangedCardValues;
    $ranged = 0;
    $cardID = $obj->CardID;

    // 1. Static lookup
    if(isset($rangedCardValues[$cardID])) {
        [$val, $needsCB, $classes] = $rangedCardValues[$cardID];
        if(!$needsCB || IsClassBonusActive($obj->Controller, $classes)) {
            $ranged += $val;
        }
    }

    // 2. Dynamic per-card cases
    switch($cardID) {
        case "7xgwve1d47": // Dahlia: Ranged X where X = water cards in GY
            global $playerID;
            $gravZone = $obj->Controller == $playerID ? "myGraveyard" : "theirGraveyard";
            $ranged += count(ZoneSearch($gravZone, cardElements: ["WATER"]));
            break;
        case "k6d4367ixj": // Horse Archer: Equestrian — Ranged 3 while controlling a Horse ally
            global $playerID;
            $zone = $obj->Controller == $playerID ? "myField" : "theirField";
            if(!empty(ZoneSearch($zone, ["ALLY"], cardSubtypes: ["HORSE"]))) {
                $ranged += 1; // +1 on top of the base 2 from static lookup
            }
            break;
        case "fta5isdgrk": // Veteran Aerotheurge: CB adds +2 Ranged (stacking with base 2)
            if(IsClassBonusActive($obj->Controller, ["RANGER"])) {
                $ranged += 2;
            }
            break;
    }

    // 3. TurnEffect-based Ranged grants (from spells like Ranger Strides, Take Aim, etc.)
    foreach($obj->TurnEffects as $te) {
        if(strpos($te, "RANGED_") === 0) {
            $ranged += intval(substr($te, 7));
        }
    }

    // 4. Field-presence passives
    if($obj->Controller != -1) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        $selfMzID = $obj->GetMzID();
        foreach($field as $fieldObj) {
            if($fieldObj->removed || HasNoAbilities($fieldObj)) continue;
            switch($fieldObj->CardID) {
                case "44vm5kt3q2": // Battlefield Spotter: [Level 2+] Other units get Ranged 1
                    if($fieldObj->GetMzID() !== $selfMzID && PlayerLevel($obj->Controller) >= 2) {
                        $ranged += 1;
                    }
                    break;
                case "43rtqovkti": // Baidi, Oathsworn Palace: Ranger units you control have Ranged 1
                    if(PropertyContains(EffectiveCardClasses($obj), "RANGER")) {
                        $ranged += 1;
                    }
                    break;
            }
        }
    }

    // 5. Champion inherited Ranged (Diana lineage)
    if(PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
        $controller = $obj->Controller;
        if(ChampionHasInLineage($controller, "m7f6r8f3y8")) $ranged += 1; // Diana, Aether Dilettante
        if(ChampionHasInLineage($controller, "7ozuj68m69")) $ranged += 2; // Diana, Deadly Duelist
        if(ChampionHasInLineage($controller, "wiztyu6o24")) $ranged += 1; // Diana, Judgment
    }

    return $ranged;
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
    // Cell Handler (pk9xycwz9g): target loses pride until end of turn
    if(in_array("pk9xycwz9g", $obj->TurnEffects)) return 0;
    // Avatar of Gaia (fqsuo6gb0o): linked ally loses pride
    $linkedCards = GetLinkedCards($obj);
    foreach($linkedCards as $linkedObj) {
        if($linkedObj->CardID === "fqsuo6gb0o") return 0;
    }
    // Jueying, Shadowmare (c3plbuv3fr): loses pride while you control a water unique Human ally
    if($obj->CardID === "c3plbuv3fr") {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fObj) {
            if(!$fObj->removed && $fObj->CardID !== "c3plbuv3fr"
                && PropertyContains(EffectiveCardType($fObj), "ALLY")
                && PropertyContains(CardType($fObj->CardID), "UNIQUE")
                && PropertyContains(EffectiveCardSubtypes($fObj), "HUMAN")
                && CardElement($fObj->CardID) === "WATER") {
                return 0;
            }
        }
    }
    // Dilu, Auspicious Charger (du4eaktghh): loses pride while you control a wind unique Human ally
    if($obj->CardID === "du4eaktghh") {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fObj) {
            if(!$fObj->removed && $fObj->CardID !== "du4eaktghh"
                && PropertyContains(EffectiveCardType($fObj), "ALLY")
                && PropertyContains(CardType($fObj->CardID), "UNIQUE")
                && PropertyContains(EffectiveCardSubtypes($fObj), "HUMAN")
                && CardElement($fObj->CardID) === "WIND") {
                return 0;
            }
        }
    }
    // Sun Quan, Sealbearer (c5hgwip1ik): [Level 2+] allies with buff counter lose pride
    if(PropertyContains(EffectiveCardType($obj), "ALLY") && GetCounterCount($obj, "buff") > 0) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fObj) {
            if(!$fObj->removed && $fObj->CardID === "c5hgwip1ik" && !HasNoAbilities($fObj)
                && PlayerLevel($obj->Controller) >= 2) {
                return 0;
            }
        }
    }
    // Lavasoul Tiger (zq0dvl1m3z): loses pride until end of turn via TurnEffect
    if($obj->CardID === "zq0dvl1m3z" && in_array("zq0dvl1m3z", $obj->TurnEffects)) return 0;
    // Red Hare, Unrivaled Stallion (5du8f077ua): loses pride while you control a fire or tera unique Human ally
    if($obj->CardID === "5du8f077ua") {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fObj) {
            if(!$fObj->removed && $fObj->CardID !== "5du8f077ua"
                && PropertyContains(EffectiveCardType($fObj), "ALLY")
                && PropertyContains(CardType($fObj->CardID), "UNIQUE")
                && PropertyContains(EffectiveCardSubtypes($fObj), "HUMAN")
                && (CardElement($fObj->CardID) === "FIRE" || CardElement($fObj->CardID) === "TERA")) {
                return 0;
            }
        }
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
    // Aegis of Dawn (abipl6gt7l): [Class Bonus] costs 1 less to materialize
    if($obj->CardID === "abipl6gt7l") {
        $turnPlayer = &GetTurnPlayer();
        if(IsClassBonusActive($turnPlayer, ["GUARDIAN"])) {
            $cost = max(0, $cost - 1);
        }
    }
    // Lunar Conduit (0yetaebjlw): [Class Bonus] costs 1 less to materialize
    if($obj->CardID === "0yetaebjlw") {
        $turnPlayer = &GetTurnPlayer();
        if(IsClassBonusActive($turnPlayer, ["CLERIC"])) {
            $cost = max(0, $cost - 1);
        }
    }
    // Winbless Kiteshield (uoy5ttkat9): [Class Bonus] costs 1 less to materialize
    if($obj->CardID === "uoy5ttkat9") {
        $turnPlayer = &GetTurnPlayer();
        if(IsClassBonusActive($turnPlayer, ["GUARDIAN"])) {
            $cost = max(0, $cost - 1);
        }
    }
    // Tideholder Claymore (5iqigcom2r): [Class Bonus] costs 1 less to materialize
    if($obj->CardID === "5iqigcom2r") {
        $turnPlayer = &GetTurnPlayer();
        if(IsClassBonusActive($turnPlayer, ["GUARDIAN"])) {
            $cost = max(0, $cost - 1);
        }
    }
    // Mechanized Smasher (qsm3n9yvn1): [Class Bonus] costs 1 less to materialize
    if($obj->CardID === "qsm3n9yvn1") {
        $turnPlayer = &GetTurnPlayer();
        if(IsClassBonusActive($turnPlayer, ["GUARDIAN"])) {
            $cost = max(0, $cost - 1);
        }
    }
    // Inert Sword (2s08hssegf): additional cost to materialize, pay (2)
    if($obj->CardID === "2s08hssegf") {
        $cost += 2;
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
 * Create a link between a Phantasia card (with Weapon Link keyword) and a weapon.
 * @param int    $player       The acting player.
 * @param string $phantasiaMZ  The mzID of the Phantasia.
 * @param string $weaponMZ     The mzID of the weapon to link to.
 */
function CreateWeaponLink($player, $phantasiaMZ, $weaponMZ) {
    $phantasiaObj = &GetZoneObject($phantasiaMZ);
    $weaponObj = &GetZoneObject($weaponMZ);
    if($phantasiaObj === null || $weaponObj === null) return;

    if(!is_array($weaponObj->Subcards)) $weaponObj->Subcards = [];
    if(!in_array($phantasiaObj->CardID, $weaponObj->Subcards)) {
        array_push($weaponObj->Subcards, $phantasiaObj->CardID);
    }

    if(!is_array($phantasiaObj->Counters)) {
        $phantasiaObj->Counters = [];
    }
    $phantasiaObj->Counters['linkedToWeapon'] = $weaponObj->CardID;
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
            if(!is_array($fObj->Counters)) continue;
            if(!isset($fObj->Counters['linkedToAlly']) && !isset($fObj->Counters['linkedToWeapon'])) continue;
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

    // Case 1: Departing card is a Phantasia — remove it from the linked ally/weapon's Subcards
    if(is_array($obj->Counters) && (isset($obj->Counters['linkedToAlly']) || isset($obj->Counters['linkedToWeapon']))) {
        $hostCardID = $obj->Counters['linkedToAlly'] ?? $obj->Counters['linkedToWeapon'] ?? null;
        if($hostCardID !== null) {
            foreach($field as $idx => $fObj) {
                if($fObj->removed) continue;
                if($fObj->CardID !== $hostCardID) continue;
                if(!is_array($fObj->Subcards)) continue;
                $fObj->Subcards = array_values(array_filter($fObj->Subcards, fn($id) => $id !== $obj->CardID));
                break;
            }
        }
        return;
    }

    // Case 2: Departing card is an ally/weapon with linked Phantasias
    if(!is_array($obj->Subcards) || empty($obj->Subcards)) return;

    $toSacrifice = [];
    foreach($field as $idx => $fObj) {
        if($fObj->removed) continue;
        if(!in_array($fObj->CardID, $obj->Subcards)) continue;
        if(!is_array($fObj->Counters)) continue;
        if(!isset($fObj->Counters['linkedToAlly']) && !isset($fObj->Counters['linkedToWeapon'])) continue;
        // This is a linked Phantasia — its link is broken when the host leaves
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

function GetGlimmerCounterCount($obj) {
    return GetCounterCount($obj, "glimmer");
}

function GetWitherCounterCount($obj) {
    return GetCounterCount($obj, "wither");
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
    // Freydis, Master Tactician: Remove 3 tactic counters → permanent distant
    if($obj->CardID === "7dedg616r0" && GetCounterCount($obj, "tactic") >= 3) {
        $abilities[] = ["name" => "Permanent Distant", "index" => $nextIndex];
        $nextIndex++;
    }
    // Diana, Cursebreaker (o0qtb31x97): Banish all curses from lineage if 4+ curses
    if($obj->CardID === "o0qtb31x97" && $obj->Status == 2 && CountCursesInLineage($obj->Controller) >= 4) {
        $abilities[] = ["name" => "Cursebreaker", "index" => $nextIndex];
        $nextIndex++;
    }
    // Fang of Dragon's Breath (iebo5fu381): [Jin Bonus] linked weapon REST ability — deal 2 damage to a unit
    if(PropertyContains(EffectiveCardType($obj), "WEAPON")) {
        $linkedCards = GetLinkedCards($obj);
        foreach($linkedCards as $linkedObj) {
            if($linkedObj->CardID === "iebo5fu381" && !HasNoAbilities($linkedObj)) {
                if(GetCounterCount($linkedObj, "durability") > 0) {
                    global $playerID;
                    $controller = $obj->Controller;
                    $zone = $controller == $playerID ? "myField" : "theirField";
                    $controllerField = GetZone($zone);
                    $isJin = false;
                    foreach($controllerField as $fObj) {
                        if(!$fObj->removed && PropertyContains(EffectiveCardType($fObj), "CHAMPION")) {
                            if(strpos(CardName($fObj->CardID), "Jin") === 0) $isJin = true;
                            break;
                        }
                    }
                    if($isJin) {
                        $abilities[] = ["name" => "Fang: REST, Deal 2 damage", "index" => $nextIndex];
                        $nextIndex++;
                    }
                }
                break;
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

    // Winbless Forecaster (dm44nt1lyk): if enlighten counters are being put on a champion,
    // and the controller has a Winbless Forecaster on field, add +1
    if($counterType === "enlighten" && PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
        $controller = $obj->Controller ?? $player;
        global $playerID;
        $zone = $controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fObj) {
            if(!$fObj->removed && $fObj->CardID === "dm44nt1lyk" && !HasNoAbilities($fObj)) {
                $amount += 1;
                break;
            }
        }
    }

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
    // Lu Xun, Pyre Strategist (xllhbjr20n): [CB] whenever enlighten counters are removed from champion,
    // you may rest Lu Xun to empower 3.
    if($counterType === "enlighten" && $amount > 0) {
        $mzObj = GetZoneObject($mzCard);
        if($mzObj !== null && PropertyContains(EffectiveCardType($mzObj), "CHAMPION")) {
            // Check if Lu Xun is on the field for this player with CB active
            $field = GetZone("myField");
            $controller = $mzObj->Controller ?? $player;
            global $playerID;
            $fieldZone = ($controller == $playerID) ? "myField" : "theirField";
            $fieldArr = GetZone($fieldZone);
            for($fi = 0; $fi < count($fieldArr); ++$fi) {
                if(!$fieldArr[$fi]->removed && $fieldArr[$fi]->CardID === "xllhbjr20n"
                    && !HasNoAbilities($fieldArr[$fi])
                    && IsClassBonusActive($controller, ["MAGE"])
                    && $fieldArr[$fi]->Status == 2) { // must be ready (can be rested)
                    DecisionQueueController::StoreVariable("LuXunFieldIdx", strval($fi));
                    DecisionQueueController::AddDecision($controller, "YESNO", "-", 1,
                        tooltip:"Rest_Lu_Xun_to_empower_3?");
                    DecisionQueueController::AddDecision($controller, "CUSTOM", "LuXunRestEmpower", 1);
                    break;
                }
            }
        }
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

// =============================================================================
// Decree Card Handlers (Imbue modal pattern)
// =============================================================================

/**
 * Verdigris Decree (7cx66hjlgx) — WIND
 * Modes: A=Suppress ally, B=+2 POWER ally, C=Destroy phantasia
 */
$customDQHandlers["VerdigrisDecree_Process"] = function($player, $parts, $lastDecision) {
    $choices = DecisionQueueController::GetVariable("VD_choices");
    if($choices === "-" || $choices === "") return;
    $modes = explode(",", $choices);
    foreach($modes as $modeIdx) {
        $modeIdx = trim($modeIdx);
        switch($modeIdx) {
            case "0": // Suppress up to one target ally
                $allies = array_merge(
                    ZoneSearch("myField", ["ALLY"]),
                    ZoneSearch("theirField", ["ALLY"])
                );
                $allies = FilterSpellshroudTargets($allies);
                if(!empty($allies)) {
                    $targets = implode("&", $allies);
                    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targets, 1, "Suppress_target_ally");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "VerdigrisDecree_ExecA", 1);
                }
                break;
            case "1": // Target ally gets +2 POWER until end of turn
                $allies = array_merge(
                    ZoneSearch("myField", ["ALLY"]),
                    ZoneSearch("theirField", ["ALLY"])
                );
                $allies = FilterSpellshroudTargets($allies);
                if(!empty($allies)) {
                    $targets = implode("&", $allies);
                    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targets, 1, "Target_ally_+2_power");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "VerdigrisDecree_ExecB", 1);
                }
                break;
            case "2": // Destroy up to one target phantasia
                $phantasias = array_merge(
                    ZoneSearch("myField", ["PHANTASIA"]),
                    ZoneSearch("theirField", ["PHANTASIA"])
                );
                $phantasias = FilterSpellshroudTargets($phantasias);
                if(!empty($phantasias)) {
                    $targets = implode("&", $phantasias);
                    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targets, 1, "Destroy_target_phantasia");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "VerdigrisDecree_ExecC", 1);
                }
                break;
        }
    }
};

$customDQHandlers["VerdigrisDecree_ExecA"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    SuppressAlly($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
};

$customDQHandlers["VerdigrisDecree_ExecB"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    AddTurnEffect($lastDecision, "7cx66hjlgx");
};

$customDQHandlers["VerdigrisDecree_ExecC"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $obj = GetZoneObject($lastDecision);
    if($obj === null) return;
    $owner = $obj->Owner;
    OnLeaveField($player, $lastDecision);
    $gravZone = ($player == $owner) ? "myGraveyard" : "theirGraveyard";
    MZMove($player, $lastDecision, $gravZone);
    DecisionQueueController::CleanupRemovedCards();
};

/**
 * Vermilion Decree (tjej4mcnqs) — FIRE
 * Modes: A=3 dmg to champion, B=2 dmg to ally, C=Each player draws
 */
$customDQHandlers["VermilionDecree_Process"] = function($player, $parts, $lastDecision) {
    $choices = DecisionQueueController::GetVariable("VM_choices");
    if($choices === "-" || $choices === "") return;
    $modes = explode(",", $choices);
    foreach($modes as $modeIdx) {
        $modeIdx = trim($modeIdx);
        switch($modeIdx) {
            case "0": // Deal 3 damage to up to one target champion
                $champions = array_merge(
                    ZoneSearch("myField", ["CHAMPION"]),
                    ZoneSearch("theirField", ["CHAMPION"])
                );
                $champions = FilterSpellshroudTargets($champions);
                if(!empty($champions)) {
                    $targets = implode("&", $champions);
                    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targets, 1, "Deal_3_damage_to_champion");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "VermilionDecree_ExecA", 1);
                }
                break;
            case "1": // Deal 2 damage to up to one target ally
                $allies = array_merge(
                    ZoneSearch("myField", ["ALLY"]),
                    ZoneSearch("theirField", ["ALLY"])
                );
                $allies = FilterSpellshroudTargets($allies);
                if(!empty($allies)) {
                    $targets = implode("&", $allies);
                    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targets, 1, "Deal_2_damage_to_ally");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "VermilionDecree_ExecB", 1);
                }
                break;
            case "2": // Each player draws a card
                Draw($player, 1);
                Draw($player == 1 ? 2 : 1, 1);
                break;
        }
    }
};

$customDQHandlers["VermilionDecree_ExecA"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    DealDamage($player, "tjej4mcnqs", $lastDecision, 3);
};

$customDQHandlers["VermilionDecree_ExecB"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    DealDamage($player, "tjej4mcnqs", $lastDecision, 2);
};

/**
 * Cerulean Decree (ipl6gt7lh9) — WATER
 * Modes: A=-3 POWER on unit attacks, B=Draw into memory
 * NOTE: Negate mode is NOT implemented (engine lacks negate support).
 */
$customDQHandlers["CeruleanDecree_Process"] = function($player, $parts, $lastDecision) {
    $choices = DecisionQueueController::GetVariable("CD_choices");
    if($choices === "-" || $choices === "") return;
    $modes = explode(",", $choices);
    foreach($modes as $modeIdx) {
        $modeIdx = trim($modeIdx);
        switch($modeIdx) {
            case "0": // Target unit's attacks get -3 POWER until end of turn
                $units = array_merge(
                    ZoneSearch("myField", ["ALLY", "CHAMPION", "PHANTASIA"]),
                    ZoneSearch("theirField", ["ALLY", "CHAMPION", "PHANTASIA"])
                );
                $units = FilterSpellshroudTargets($units);
                if(!empty($units)) {
                    $targets = implode("&", $units);
                    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targets, 1, "Target_unit_-3_power_to_attacks");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "CeruleanDecree_ExecA", 1);
                }
                break;
            case "1": // Draw a card into your memory
                DrawIntoMemory($player, 1);
                break;
        }
    }
};

$customDQHandlers["CeruleanDecree_ExecA"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    AddTurnEffect($lastDecision, "ipl6gt7lh9-debuff");
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
        NicoOnFloatingMemoryBanished($player);
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

/**
 * Custom DQ handler: DeclareWeaponLinkTarget — stores the chosen weapon mzID
 * and CardID as DQ variables for Weapon Link resolution.
 */
$customDQHandlers["DeclareWeaponLinkTarget"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "PASS" || $lastDecision === "-" || empty($lastDecision)) {
        DecisionQueueController::StoreVariable("weaponLinkTargetMZ", "");
        DecisionQueueController::StoreVariable("weaponLinkTargetCardID", "");
        return;
    }
    $targetObj = GetZoneObject($lastDecision);
    DecisionQueueController::StoreVariable("weaponLinkTargetMZ", $lastDecision);
    DecisionQueueController::StoreVariable("weaponLinkTargetCardID", $targetObj !== null ? $targetObj->CardID : "");
};

/**
 * Custom DQ handler: RazorgaleCallingDamage — deal 1 damage to target champion.
 * YES = your champion, NO = opponent's champion.
 */
$customDQHandlers["RazorgaleCallingDamage"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        DealChampionDamage($player, 1);
    } else {
        $opponent = ($player == 1) ? 2 : 1;
        DealChampionDamage($opponent, 1);
    }
};

// Fervent Lancer: may banish the exia card as it resolves
$customDQHandlers["FerventLancerBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $lancerIdx = DecisionQueueController::GetVariable("FerventLancerIdx");
    // The card just resolved — it should be on the effect stack or in graveyard.
    // Find the card that just resolved (top of effect stack or last resolved)
    $mzID = DecisionQueueController::GetVariable("mzID");
    if($mzID === null || $mzID === "" || $mzID === "-") return;
    $obj = GetZoneObject($mzID);
    if($obj === null) return;
    // Banish the resolved card
    MZMove($player, $mzID, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    // Mark this Fervent Lancer as having banished a card (for +2 POWER)
    $lancerMZ = "myField-" . $lancerIdx;
    $lancerObj = &GetZoneObject($lancerMZ);
    if($lancerObj !== null && $lancerObj->CardID === "aws20fsihd") {
        if(!is_array($lancerObj->Counters)) $lancerObj->Counters = [];
        $lancerObj->Counters['banished_card'] = true;
    }
};

// Molten Arrow GY ability: banish 3 fire GY cards sequentially, then load into bow
$customDQHandlers["MoltenArrowGYBanish1"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    MZMove($player, $lastDecision, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    $fireGY = [];
    $gy = GetZone("myGraveyard");
    for($gi = 0; $gi < count($gy); ++$gi) {
        if(!$gy[$gi]->removed && CardElement($gy[$gi]->CardID) === "FIRE"
            && $gy[$gi]->CardID !== "mvfcd0ukk6") {
            $fireGY[] = "myGraveyard-" . $gi;
        }
    }
    if(count($fireGY) < 2) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $fireGY), 1, tooltip:"Banish_fire_card_2_of_3");
    DecisionQueueController::AddDecision($player, "CUSTOM", "MoltenArrowGYBanish2", 1);
};

$customDQHandlers["MoltenArrowGYBanish2"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    MZMove($player, $lastDecision, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    $fireGY = [];
    $gy = GetZone("myGraveyard");
    for($gi = 0; $gi < count($gy); ++$gi) {
        if(!$gy[$gi]->removed && CardElement($gy[$gi]->CardID) === "FIRE"
            && $gy[$gi]->CardID !== "mvfcd0ukk6") {
            $fireGY[] = "myGraveyard-" . $gi;
        }
    }
    if(count($fireGY) < 1) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $fireGY), 1, tooltip:"Banish_fire_card_3_of_3");
    DecisionQueueController::AddDecision($player, "CUSTOM", "MoltenArrowGYBanish3", 1);
};

$customDQHandlers["MoltenArrowGYBanish3"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    MZMove($player, $lastDecision, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    // Now load Molten Arrow from GY into an unloaded Bow
    $arrowMZ = DecisionQueueController::GetVariable("MoltenArrowGYMZ");
    $bows = GetUnloadedBows($player);
    if(empty($bows)) return;
    if(count($bows) == 1) {
        LoadArrowIntoBow($player, $arrowMZ, $bows[0]);
    } else {
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $bows), 1, tooltip:"Choose_Bow_to_load");
        DecisionQueueController::AddDecision($player, "CUSTOM", "LoadArrow|" . $arrowMZ, 1);
    }
};

/**
 * Seal the Past helper: banish up to 3 preserved cards from a player's material deck.
 * @param int    $player     The acting player.
 * @param string $targetSelf "YES" to target self, "NO" for opponent.
 */
function SealThePastBanish($player, $targetSelf) {
    global $Preserve_Cards, $playerID;
    $isMyself = ($targetSelf === "YES");
    $matRef = $isMyself ? "myMaterial" : "theirMaterial";
    $banishRef = $isMyself ? "myBanish" : "theirBanish";
    $material = GetZone($matRef);
    $preserved = [];
    for($i = 0; $i < count($material); $i++) {
        if(isset($Preserve_Cards[$material[$i]->CardID])) {
            $preserved[] = $matRef . "-" . $i;
        }
    }
    if(empty($preserved)) return;
    $banishCount = min(3, count($preserved));
    // Sort in reverse order to avoid index shifting issues
    $preserved = array_reverse($preserved);
    $toBanish = array_slice($preserved, 0, $banishCount);
    foreach($toBanish as $mz) {
        MZMove($player, $mz, $banishRef);
    }
}

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
            case "waf8urrqtj": // Gloamspire, Black Market: sacrifice if influence >= 8
                {
                    global $playerID;
                    $memZone = $player == $playerID ? "myMemory" : "theirMemory";
                    $influence = count(GetZone($memZone));
                    if($influence >= 8) {
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
            case "snke7lneo4": // Hulao Gate, Sun's Ascent: may banish fire from GY or sacrifice
                {
                    global $playerID;
                    $gravZone = $player == $playerID ? "myGraveyard" : "theirGraveyard";
                    $fireGY = ZoneSearch($gravZone, cardElements: ["FIRE"]);
                    if(!empty($fireGY)) {
                        $fireStr = implode("&", $fireGY);
                        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $fireStr, 1, "Banish_fire_card_or_sacrifice_Hulao_Gate");
                        DecisionQueueController::AddDecision($player, "CUSTOM", "HulaoGateUpkeep|" . $i, 1);
                    } else {
                        DoSacrificeFighter($player, "myField-" . $i);
                        DecisionQueueController::CleanupRemovedCards();
                    }
                }
                break;
            case "95ynk6lmnf": // Guandu, Theater of War: remove 2 battle counters or sacrifice
                {
                    $battleCount = GetCounterCount($field[$i], "battle");
                    if($battleCount >= 2) {
                        RemoveCounters($player, "myField-" . $i, "battle", 2);
                    } else {
                        DoSacrificeFighter($player, "myField-" . $i);
                        DecisionQueueController::CleanupRemovedCards();
                    }
                }
                break;
            case "gjhv2etytr": // Keep of the Golden Sashes: banish 2 from GY or sacrifice
                {
                    global $playerID;
                    $gravZone = $player == $playerID ? "myGraveyard" : "theirGraveyard";
                    $gyCards = GetZone($gravZone);
                    $gyMZs = [];
                    for($gi = 0; $gi < count($gyCards); ++$gi) {
                        if(!$gyCards[$gi]->removed) $gyMZs[] = $gravZone . "-" . $gi;
                    }
                    if(count($gyMZs) >= 2) {
                        DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Banish_2_cards_from_graveyard?_(Keep_of_the_Golden_Sashes)");
                        DecisionQueueController::AddDecision($player, "CUSTOM", "KeepGoldenSashesUpkeep|" . $i, 1);
                    } else {
                        DoSacrificeFighter($player, "myField-" . $i);
                        DecisionQueueController::CleanupRemovedCards();
                    }
                }
                break;
            case "kmuuqzfvg8": // Changban, Heroic Impasse: sacrifice if no unique ally on field
                {
                    global $playerID;
                    $zone = $player == $playerID ? "myField" : "theirField";
                    $hasUniqueAlly = false;
                    $fieldArr = GetZone($zone);
                    foreach($fieldArr as $fObj) {
                        if(!$fObj->removed && PropertyContains(EffectiveCardType($fObj), "ALLY") && PropertyContains(EffectiveCardType($fObj), "UNIQUE")) {
                            $hasUniqueAlly = true;
                            break;
                        }
                    }
                    if(!$hasUniqueAlly) {
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
 * Count the number of water element cards in a player's graveyard.
 * Centralized for future modifier support (e.g. "your deluge counts as having one more").
 */
function DelugeAmount($player) {
    global $playerID;
    $gravZone = $player == $playerID ? "myGraveyard" : "theirGraveyard";
    return count(ZoneSearch($gravZone, cardElements: ["WATER"]));
}

function SleeyRetreatCheckDefending($player, $target) {
    if(!IsUnitDefending($target)) return;
    $opponent = ($player == 1) ? 2 : 1;
    $oppHand = ZoneSearch("theirHand");
    if(count($oppHand) >= 2) {
        DecisionQueueController::AddDecision($opponent, "YESNO", "-", 1, tooltip:"Pay_2_to_prevent_combat_from_ending?");
        DecisionQueueController::AddDecision($opponent, "CUSTOM", "SleeyRetreatPayment", 1);
    } else {
        EndCombat($player);
    }
}

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
// Paired Minds, Kindred Souls (7qjnqww067) helpers
// ============================================================================

/**
 * Queue the Horse ally choice from TempZone, or finish directly if none found.
 */
function PairedMindsChoose($player) {
    $horseAllies = ZoneSearch("myTempZone", ["ALLY"], cardSubtypes: ["HORSE"]);
    if(!empty($horseAllies)) {
        $horseStr = implode("&", $horseAllies);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $horseStr, 1, "Reveal_a_Horse_ally");
        DecisionQueueController::AddDecision($player, "CUSTOM", "PairedMindsResult", 1);
    } else {
        PairedMindsFinish($player);
    }
}

function PairedMindsFinish($player) {
    // Put remaining TempZone cards on bottom of deck
    $tempRemaining = ZoneSearch("myTempZone");
    $n = count($tempRemaining);
    for($i = 0; $i < $n; ++$i) {
        MZMove($player, "myTempZone-0", "myDeck");
    }
    // [Class Bonus] if unique ally, next Horse ally costs 2 less
    if(IsClassBonusActive($player, ["WARRIOR"])) {
        $myField = GetZone("myField");
        foreach($myField as $fObj) {
            if(!$fObj->removed && PropertyContains(EffectiveCardType($fObj), "ALLY") && PropertyContains(EffectiveCardType($fObj), "UNIQUE")) {
                AddGlobalEffects($player, "7qjnqww067");
                break;
            }
        }
    }
}

$customDQHandlers["PairedMindsResult"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "") {
        DoRevealCard($player, $lastDecision);
        MZMove($player, $lastDecision, "myHand");
    }
    PairedMindsFinish($player);
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
// LevelUpChampion — materialize the top champion card from the material deck for free
// ============================================================================

/**
 * Level up the player's champion by materializing the first champion card in their
 * material deck without paying a cost. Used by effects that say "level up your champion."
 * @param int $player The acting player
 * @return bool True if successful, false if no champion found in material deck
 */
function LevelUpChampion($player) {
    global $playerID;
    $material = GetMaterial($player);
    $zoneName = $player == $playerID ? "myMaterial" : "theirMaterial";
    for($i = 0; $i < count($material); ++$i) {
        if(!$material[$i]->removed && PropertyContains(CardType($material[$i]->CardID), "CHAMPION")) {
            DoMaterialize($player, $zoneName . "-" . $i);
            return true;
        }
    }
    return false;
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
        ReserveCard($player);
        ReserveCard($player);
    } else {
        // Player declined — target doesn't wake up during next wake up phase
        AddTurnEffect($targetMZ, "SKIP_WAKEUP");
    }
};

$customDQHandlers["SleeyRetreatPayment"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        ReserveCard($player);
        ReserveCard($player);
    } else {
        EndCombat($player);
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

$customDQHandlers["SwordSaintGY_Apply"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        AddTurnEffect($lastDecision, "lpy7ie4v8n");
    }
};

// Seaside Rangefinder (5qyee9vkp8): GY activation → target unit becomes distant
$customDQHandlers["SeasideRangefinderGY_Apply"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        BecomeDistant($player, $lastDecision);
    }
};

$customDQHandlers["EternalKingdomUpkeep"] = function($player, $parts, $lastDecision) {
    $fieldIdx = intval($parts[0]);
    $field = &GetField($player);
    if($lastDecision === "YES") {
        // Pay 2 reserve
        $hand = &GetHand($player);
        if(count($hand) >= 2) {
            // Must use block 100 (same as DoActivateCard) so each ReserveCard's
            // MZCHOOSE→Process chain (blocks 1/99) completes before the next fires.
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
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

// --- Portside Pirate (6p3p5iqigc) DQ handler ---
$customDQHandlers["PortsidePirateBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $hadFloating = HasFloatingMemory(GetZoneObject($lastDecision));
    MZMove($player, $lastDecision, "myBanish");
    if($hadFloating) NicoOnFloatingMemoryBanished($player);
};

// --- Smashing Force (88rx6p3p5i) helpers ---
function SmashingForceBanishStart($player) {
    $fireCards = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
    if(count($fireCards) < 2) return;
    $fireStr = implode("&", $fireCards);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $fireStr, 1, "Banish_first_fire_card");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SmashingForceBanish1", 1);
}

$customDQHandlers["SmashingForceBanish1"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $hadFloating = HasFloatingMemory(GetZoneObject($lastDecision));
    MZMove($player, $lastDecision, "myBanish");
    if($hadFloating) NicoOnFloatingMemoryBanished($player);
    $fireCards2 = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
    if(empty($fireCards2)) return;
    $fireStr2 = implode("&", $fireCards2);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $fireStr2, 1, "Banish_second_fire_card");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SmashingForceBanish2", 1);
};

$customDQHandlers["SmashingForceBanish2"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $hadFloating = HasFloatingMemory(GetZoneObject($lastDecision));
    MZMove($player, $lastDecision, "myBanish");
    if($hadFloating) NicoOnFloatingMemoryBanished($player);
    // Now destroy target item or weapon with memory cost 0 or reserve cost <= 4
    $validTargets = [];
    $allItems = array_merge(
        ZoneSearch("myField", ["ITEM", "REGALIA"]),
        ZoneSearch("theirField", ["ITEM", "REGALIA"]),
        ZoneSearch("myField", ["WEAPON"]),
        ZoneSearch("theirField", ["WEAPON"])
    );
    foreach($allItems as $mzI) {
        $iObj = GetZoneObject($mzI);
        if(CardCost_memory($iObj->CardID) == 0 || CardCost_reserve($iObj->CardID) <= 4) {
            $validTargets[] = $mzI;
        }
    }
    if(empty($validTargets)) return;
    $targetStr = implode("&", $validTargets);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, "Destroy_target_item_or_weapon");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SmashingForceDestroy", 1);
};

$customDQHandlers["SmashingForceDestroy"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $targetObj = GetZoneObject($lastDecision);
    if($targetObj === null) return;
    OnLeaveField($player, $lastDecision);
    $dest = $player == $targetObj->Controller ? "myGraveyard" : "theirGraveyard";
    MZMove($player, $lastDecision, $dest);
    DecisionQueueController::CleanupRemovedCards();
};

// --- Meteor Strike (dwavcoxpnj): destroy chosen non-champion object ---
$customDQHandlers["MeteorStrikeDestroy"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $targetObj = GetZoneObject($lastDecision);
    if($targetObj === null) return;
    OnLeaveField($player, $lastDecision);
    $dest = $player == $targetObj->Controller ? "myGraveyard" : "theirGraveyard";
    MZMove($player, $lastDecision, $dest);
    DecisionQueueController::CleanupRemovedCards();
};

// --- Alkahest (xfpk9xycwz): choose Potion for age counter ---
$customDQHandlers["AlkahestAgeCounter"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    AddCounters($player, $lastDecision, "age", 1);
};

// --- Washuru (k5iv040vcq): banish chosen graveyard card ---
$customDQHandlers["WashuruBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    MZMove($player, $lastDecision, "myBanish");
};

$customDQHandlers["NightshadeWither"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    AddCounters($player, $lastDecision, "wither", 1);
};

// --- Misteye Archer (m6c8xy4cje): finish look — put water to GY, become distant, prevent 2 ---
$customDQHandlers["MisteyeArcherFinish"] = function($player, $parts, $lastDecision) {
    $mzID = $parts[0];
    if($lastDecision === "YES") {
        $tempCards = ZoneSearch("myTempZone");
        if(!empty($tempCards)) {
            MZMove($player, $tempCards[count($tempCards) - 1], "myGraveyard");
        }
        BecomeDistant($player, $mzID);
        AddTurnEffect($mzID, "PREVENT_ALL_2");
    } else {
        PutTempZoneOnTopOfDeck($player);
    }
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
    $dest = EphemeralRedirectDest($targetObj, $dest, $player);
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

// --- Claude, Fated Visionary (52215upufy): return up to N Automaton allies from GY to memory ---
function ClaudeReturnAutomatonContinue($player, $remaining) {
    if($remaining <= 0) return;
    $gyAutomatons = ZoneSearch("myGraveyard", ["ALLY"], cardSubtypes: ["AUTOMATON"]);
    if(empty($gyAutomatons)) return;
    $targetStr = implode("&", $gyAutomatons);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1, tooltip:"Return_Automaton_ally_from_graveyard_to_memory?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ClaudeReturnAutomaton|$remaining", 1);
}

$customDQHandlers["ClaudeReturnAutomaton"] = function($player, $parts, $lastDecision) {
    $remaining = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "") return;
    MZMove($player, $lastDecision, "myMemory");
    ClaudeReturnAutomatonContinue($player, $remaining - 1);
};

// --- Winbless Gatekeeper (y5ttkk39i1): On Enter may pay (2) to buff Guardian ally ---

$customDQHandlers["WinblessGatekeeperPay"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $hand = &GetHand($player);
    if(count($hand) < 2) return;
    ReserveCard($player);
    ReserveCard($player);
    $guardianAllies = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["GUARDIAN"]);
    if(empty($guardianAllies)) return;
    $targetStr = implode("&", $guardianAllies);
    // Block 105: must come after the two ReserveCard_Choice(100) chains fully
    // resolve (inner blocks 1/99) before the Guardian ally target is chosen.
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 105, "Put_buff_on_Guardian_ally");
    DecisionQueueController::AddDecision($player, "CUSTOM", "WinblessGatekeeperBuff", 105);
};

$customDQHandlers["WinblessGatekeeperBuff"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddCounters($player, $lastDecision, "buff", 1);
};

// --- Reconstructive Surgery (z308kuz07n): choose one DQ handlers ---

$customDQHandlers["ReconSurgeryBuff"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddCounters($player, $lastDecision, "buff", 1);
};

$customDQHandlers["ReconSurgeryReturn"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myMemory");
};

$customDQHandlers["ReconSurgeryChoice"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        $automatonAllies = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["AUTOMATON"]);
        $automatonAllies = FilterSpellshroudTargets($automatonAllies);
        if(empty($automatonAllies)) return;
        $targetStr = implode("&", $automatonAllies);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, "Put_buff_on_Automaton_ally");
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReconSurgeryBuff", 1);
    } else {
        $gyAutomatons = ZoneSearch("myGraveyard", ["ALLY"], cardSubtypes: ["AUTOMATON"]);
        if(empty($gyAutomatons)) return;
        $targetStr = implode("&", $gyAutomatons);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, "Return_Automaton_to_memory");
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReconSurgeryReturn", 1);
    }
};

// --- Geni, Gifted Mechanist (wuir99sx6q): banish from graveyard DQ handlers ---

$customDQHandlers["GeniMechanistBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $chosenObj = GetZoneObject($lastDecision);
    if($chosenObj === null) return;
    $chosenCardID = $chosenObj->CardID;
    $hadFloating = HasFloatingMemory($chosenObj);
    MZMove($player, $lastDecision, "myBanish");
    // If Automaton ally, summon Automaton Drone
    if(PropertyContains(CardType($chosenCardID), "ALLY") && PropertyContains(CardSubtypes($chosenCardID), "AUTOMATON")) {
        MZAddZone($player, "myField", "mu6gvnta6q");
    }
    // If floating memory, buff an Automaton ally
    if($hadFloating) {
        $automatonAllies = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["AUTOMATON"]);
        if(!empty($automatonAllies)) {
            $targetStr = implode("&", $automatonAllies);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, "Put_2_buff_counters_on_Automaton");
            DecisionQueueController::AddDecision($player, "CUSTOM", "GeniMechanistBuff", 1);
        }
    }
};

$customDQHandlers["GeniMechanistBuff"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddCounters($player, $lastDecision, "buff", 2);
};

// --- Fractal of Creation (x7mnu1xhs5): summon token copy DQ handler ---

$customDQHandlers["FractalCreationCopy"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $targetObj = GetZoneObject($lastDecision);
    if($targetObj === null) return;
    MZAddZone($player, "myField", $targetObj->CardID);
};

// Obelisk of Armaments — summon Aurousteel Greatsword after reserve payment
$customDQHandlers["ObeliskArmamentsSummon"] = function($player, $parts, $lastDecision) {
    MZAddZone($player, "myField", "hkurfp66pv"); // Aurousteel Greatsword token
};

// Obelisk of Fabrication — summon Automaton Drone with buff counter after reserve payment
$customDQHandlers["ObeliskFabricationSummon"] = function($player, $parts, $lastDecision) {
    MZAddZone($player, "myField", "mu6gvnta6q"); // Automaton Drone token
    $field = &GetField($player);
    $newIdx = count($field) - 1;
    AddCounters($player, "myField-" . $newIdx, "buff", 1);
};

// ============================================================================
// Gun & Bullet Infrastructure
// ============================================================================

/**
 * Renewable cards: if banished from field or intent, go to material deck instead.
 */
$Renewable_Cards = [
    "f8urrqtjot" => true, // Turbulent Bullet
    "ywc08c9htu" => true, // Cascading Round
    "ao8bki6fxx" => true, // Steel Slug
    "hreqhj1trn" => true, // Windpiercer
];

/**
 * Check if a weapon (Gun) currently has a bullet loaded.
 * Loaded bullets are stored in the gun's Subcards array.
 */
function IsGunLoaded($obj) {
    return is_array($obj->Subcards) && !empty($obj->Subcards);
}

/**
 * Get mzIDs of unloaded Gun weapons the player controls that are awake.
 */
function GetUnloadedGuns($player) {
    $guns = ZoneSearch("myField", ["WEAPON"], cardSubtypes: ["GUN"]);
    $unloaded = [];
    foreach($guns as $mzID) {
        $obj = GetZoneObject($mzID);
        if($obj !== null && !IsGunLoaded($obj)) {
            $unloaded[] = $mzID;
        }
    }
    return $unloaded;
}

/**
 * Get mzIDs of unloaded Bow weapons the player controls.
 */
function GetUnloadedBows($player) {
    $bows = ZoneSearch("myField", ["WEAPON"], cardSubtypes: ["BOW"]);
    $unloaded = [];
    foreach($bows as $mzID) {
        $obj = GetZoneObject($mzID);
        if($obj !== null && !IsGunLoaded($obj)) {
            $unloaded[] = $mzID;
        }
    }
    return $unloaded;
}

/**
 * Load an arrow into a bow weapon.
 * The arrow is removed from the field (its CardID is stored in the bow's Subcards).
 */
function LoadArrowIntoBow($player, $arrowMZ, $bowMZ) {
    $arrowObj = GetZoneObject($arrowMZ);
    $bowObj = &GetZoneObject($bowMZ);
    if($arrowObj === null || $bowObj === null) return;
    $arrowCardID = $arrowObj->CardID;
    if(!is_array($bowObj->Subcards)) $bowObj->Subcards = [];
    $bowObj->Subcards[] = $arrowCardID;
    $arrowObj->removed = true;
    DecisionQueueController::CleanupRemovedCards();
}

/**
 * Diana, Cursebreaker: materialize N Bullet cards from material deck sequentially.
 * Queues MZCHOOSE + MATERIALIZE for each bullet.
 */
function CursebreakerMaterializeBullets($player, $count) {
    for($b = 0; $b < $count; ++$b) {
        $materialZone = GetZone("myMaterial");
        $bulletMZs = [];
        for($i = 0; $i < count($materialZone); ++$i) {
            $obj = $materialZone[$i];
            if(!$obj->removed && PropertyContains(CardSubtypes($obj->CardID), "BULLET")) {
                $bulletMZs[] = "myMaterial-" . $i;
            }
        }
        if(empty($bulletMZs)) return;
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $bulletMZs), 1, "Materialize_Bullet_" . ($b + 1));
        DecisionQueueController::AddDecision($player, "CUSTOM", "MATERIALIZE", 1);
    }
}

/**
 * Load a bullet into a gun weapon.
 * The bullet is removed from the field (its CardID is stored in the gun's Subcards).
 * The bullet card must be on the field and the gun must be unloaded.
 */
function LoadBulletIntoGun($player, $bulletMZ, $gunMZ) {
    $bulletObj = GetZoneObject($bulletMZ);
    $gunObj = &GetZoneObject($gunMZ);
    if($bulletObj === null || $gunObj === null) return;
    $bulletCardID = $bulletObj->CardID;
    // Store bullet CardID in gun's Subcards
    if(!is_array($gunObj->Subcards)) $gunObj->Subcards = [];
    $gunObj->Subcards[] = $bulletCardID;
    // Remove bullet from field silently (loading is a cost, no LeaveField triggers)
    $bulletObj->removed = true;
    DecisionQueueController::CleanupRemovedCards();

    // Loaded Thoughts (hh88rx6p3p): [CB] Whenever becomes loaded, may put top card of deck into GY
    if($gunObj->CardID === "hh88rx6p3p" && !HasNoAbilities($gunObj)) {
        if(IsClassBonusActive($player, ["RANGER"])) {
            $deck = GetDeck($player);
            if(!empty($deck)) {
                DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Put_top_card_of_deck_into_graveyard?_(Loaded_Thoughts)");
                DecisionQueueController::AddDecision($player, "CUSTOM", "LoadedThoughtsMill", 1);
            }
        }
    }
}

/**
 * Count Curse cards in a player's champion lineage.
 */
function CountCursesInLineage($player) {
    $lineage = GetChampionLineage($player);
    $count = 0;
    foreach($lineage as $cardID) {
        if(PropertyContains(CardSubtypes($cardID), "CURSE")) {
            $count++;
        }
    }
    return $count;
}

/**
 * Creeping Torment (zrplywc08c) inherited trigger:
 * "Whenever you draw your 2nd card each turn, deal 2 unpreventable damage to this champion."
 * Called from DoDrawCard when the 2nd card is drawn.
 */
function CreepingTormentDrawTrigger($player) {
    if(!ChampionHasInLineage($player, "zrplywc08c")) return;
    $champions = ZoneSearch("myField", ["CHAMPION"]);
    if(empty($champions)) return;
    DealUnpreventableDamage($player, $champions[0], $champions[0], 2);
}

/**
 * Get Curse card IDs from a champion's lineage along with their indices.
 * @param int $player The player whose champion to check.
 * @return array Array of ['cardID' => string, 'index' => int] for each curse subcard.
 */
function GetCursesInLineage($player) {
    $field = &GetField($player);
    foreach($field as $obj) {
        if(!$obj->removed && PropertyContains(EffectiveCardType($obj), "CHAMPION") && $obj->Controller == $player) {
            if(!is_array($obj->Subcards)) return [];
            $curses = [];
            foreach($obj->Subcards as $idx => $cardID) {
                if(PropertyContains(CardSubtypes($cardID), "CURSE")) {
                    $curses[] = ['cardID' => $cardID, 'index' => $idx];
                }
            }
            return $curses;
        }
    }
    return [];
}

// --- Exorcise Curses (u1xhs5jwsl): choose up to 2 Curse cards from a champion's lineage ---

/**
 * Set up the Exorcise Curses flow: stage curses from the chosen champion's lineage
 * into TempZone and queue MZMAYCHOOSE decisions.
 * @param int    $player       The acting player.
 * @param int    $targetPlayer The player whose champion's lineage to exorcise.
 */
function ExorciseCursesSetup($player, $targetPlayer) {
    DecisionQueueController::StoreVariable("ExorciseTarget", $targetPlayer);
    $curses = GetCursesInLineage($targetPlayer);
    if(empty($curses)) return;
    $tempZone = &GetTempZone($player);
    // Clear TempZone first
    while(count($tempZone) > 0) array_pop($tempZone);
    foreach($curses as $curse) {
        MZAddZone($player, "myTempZone", $curse['cardID']);
    }
    $tempMZs = [];
    for($i = 0; $i < count($curses); ++$i) {
        $tempMZs[] = "myTempZone-" . $i;
    }
    $options = implode("&", $tempMZs);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $options, 1, "Choose_Curse_to_discard");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ExorciseCursesPick1", 1);
}

$customDQHandlers["ExorciseCursesPick1"] = function($player, $parts, $lastDecision) {
    $targetPlayer = intval(DecisionQueueController::GetVariable("ExorciseTarget"));
    if($lastDecision === "-" || $lastDecision === "") {
        // Player declined — clean up TempZone
        $tempZone = &GetTempZone($player);
        while(count($tempZone) > 0) array_pop($tempZone);
        return;
    }
    // Remove the chosen curse from lineage
    $chosenObj = GetZoneObject($lastDecision);
    $chosenCardID = $chosenObj->CardID;
    RemoveFromChampionLineage($targetPlayer, $chosenCardID, "myGraveyard");
    // Remove from TempZone
    MZRemove($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    // Offer second pick if any curses remain in TempZone
    $tempZone = &GetTempZone($player);
    $remaining = [];
    for($i = 0; $i < count($tempZone); ++$i) {
        if(!$tempZone[$i]->removed) {
            $remaining[] = "myTempZone-" . $i;
        }
    }
    if(!empty($remaining)) {
        $options = implode("&", $remaining);
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $options, 1, "Choose_another_Curse_to_discard");
        DecisionQueueController::AddDecision($player, "CUSTOM", "ExorciseCursesPick2", 1);
    } else {
        while(count($tempZone) > 0) array_pop($tempZone);
    }
};

$customDQHandlers["ExorciseCursesPick2"] = function($player, $parts, $lastDecision) {
    $targetPlayer = intval(DecisionQueueController::GetVariable("ExorciseTarget"));
    $tempZone = &GetTempZone($player);
    if($lastDecision !== "-" && $lastDecision !== "") {
        $chosenObj = GetZoneObject($lastDecision);
        $chosenCardID = $chosenObj->CardID;
        RemoveFromChampionLineage($targetPlayer, $chosenCardID, "myGraveyard");
    }
    // Clean up TempZone
    while(count($tempZone) > 0) array_pop($tempZone);
};

/**
 * Add a card to the bottom of a player's champion lineage (Subcards array).
 * Used by curse spells and abilities that say "put CARDNAME on the bottom
 * of [target] champion's lineage."
 * @param int    $player The player whose champion receives the card.
 * @param string $cardID The card ID to add to the lineage.
 */
function AddToChampionLineage($player, $cardID) {
    $field = &GetField($player);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && PropertyContains(EffectiveCardType($field[$i]), "CHAMPION") && $field[$i]->Controller == $player) {
            if(!is_array($field[$i]->Subcards)) $field[$i]->Subcards = [];
            $field[$i]->Subcards[] = $cardID;
            return;
        }
    }
}

/**
 * Remove a card from a player's champion lineage and return it to a destination zone.
 * Used by Exorcise Curses and similar effects that remove cards from lineage.
 * @param int    $player    The player whose champion's lineage to modify.
 * @param string $cardID    The card ID to remove.
 * @param string $destZone  Destination zone (e.g. "myGraveyard").
 * @return bool  True if the card was found and removed.
 */
function RemoveFromChampionLineage($player, $cardID, $destZone = "myGraveyard") {
    $field = &GetField($player);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && PropertyContains(EffectiveCardType($field[$i]), "CHAMPION") && $field[$i]->Controller == $player) {
            if(!is_array($field[$i]->Subcards)) return false;
            $idx = array_search($cardID, $field[$i]->Subcards);
            if($idx === false) return false;
            array_splice($field[$i]->Subcards, $idx, 1);
            MZAddZone($player, $destZone, $cardID);
            return true;
        }
    }
    return false;
}

/**
 * Check if a card is Renewable.
 */
function IsRenewable($cardID) {
    global $Renewable_Cards;
    return isset($Renewable_Cards[$cardID]);
}

// --- Vanishing Shot: return hit ally to owner's memory ---
$customDQHandlers["VanishingShotReturn"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $targetMZ = DecisionQueueController::GetVariable("VanishingShotTarget");
    if(empty($targetMZ) || $targetMZ === "-") return;
    $targetObj = GetZoneObject($targetMZ);
    if($targetObj === null || $targetObj->removed) return;
    // Return to owner's memory
    global $playerID;
    $dest = $targetObj->Owner == $playerID ? "myMemory" : "theirMemory";
    MZMove($player, $targetMZ, $dest);
};

// --- LoadBullet: custom DQ handler for the REST:Load activated ability ---
$customDQHandlers["LoadBullet"] = function($player, $parts, $lastDecision) {
    // $parts[0] = mzID of the bullet that was rested
    // $lastDecision = mzID of the chosen unloaded gun
    $bulletMZ = $parts[0];
    if($lastDecision === "-" || $lastDecision === "") return;
    LoadBulletIntoGun($player, $bulletMZ, $lastDecision);
};

// --- LoadArrow: custom DQ handler for loading an arrow into a bow ---
$customDQHandlers["LoadArrow"] = function($player, $parts, $lastDecision) {
    // $parts[0] = mzID of the arrow on the field
    // $lastDecision = mzID of the chosen unloaded bow
    $arrowMZ = $parts[0];
    if($lastDecision === "-" || $lastDecision === "") return;
    LoadArrowIntoBow($player, $arrowMZ, $lastDecision);
};

// --- Keep of the Golden Sashes (gjhv2etytr): upkeep — banish 2 from GY or sacrifice ---
$customDQHandlers["KeepGoldenSashesUpkeep"] = function($player, $parts, $lastDecision) {
    // $parts[0] = field index of the Keep
    $fieldIdx = intval($parts[0]);
    if($lastDecision === "YES") {
        // Player chose to banish 2 cards from graveyard
        global $playerID;
        $gravZone = $player == $playerID ? "myGraveyard" : "theirGraveyard";
        $gyCards = GetZone($gravZone);
        $gyMZs = [];
        for($gi = 0; $gi < count($gyCards); ++$gi) {
            if(!$gyCards[$gi]->removed) $gyMZs[] = $gravZone . "-" . $gi;
        }
        if(count($gyMZs) >= 2) {
            $gyStr = implode("&", $gyMZs);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $gyStr, 1, "Choose_first_card_to_banish_(Keep)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "KeepGoldenSashesBanish1|" . $fieldIdx, 1);
        }
    } else {
        // Player declined — sacrifice the Keep
        DoSacrificeFighter($player, "myField-" . $fieldIdx);
        DecisionQueueController::CleanupRemovedCards();
    }
};

$customDQHandlers["KeepGoldenSashesBanish1"] = function($player, $parts, $lastDecision) {
    $fieldIdx = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "") return;
    MZMove($player, $lastDecision, "myBanish");
    global $playerID;
    $gravZone = $player == $playerID ? "myGraveyard" : "theirGraveyard";
    $gyCards = GetZone($gravZone);
    $gyMZs = [];
    for($gi = 0; $gi < count($gyCards); ++$gi) {
        if(!$gyCards[$gi]->removed) $gyMZs[] = $gravZone . "-" . $gi;
    }
    if(!empty($gyMZs)) {
        $gyStr = implode("&", $gyMZs);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $gyStr, 1, "Choose_second_card_to_banish_(Keep)");
        DecisionQueueController::AddDecision($player, "CUSTOM", "KeepGoldenSashesBanish2", 1);
    }
};

$customDQHandlers["KeepGoldenSashesBanish2"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    MZMove($player, $lastDecision, "myBanish");
};

// --- Relic of Dancing Embers (i8g5013x9j): sacrifice to deal 3 damage (after fire ally hit champion) ---
$customDQHandlers["RelicDancingEmbers"] = function($player, $parts, $lastDecision) {
    // $parts[0] = field index of Relic
    // $parts[1] = defender player number
    if($lastDecision !== "YES") return;
    $relicIdx = intval($parts[0]);
    $defenderPlayer = intval($parts[1]);
    $field = &GetField($player);
    if(isset($field[$relicIdx]) && !$field[$relicIdx]->removed && $field[$relicIdx]->CardID === "i8g5013x9j") {
        DoSacrificeFighter($player, "myField-" . $relicIdx);
        DecisionQueueController::CleanupRemovedCards();
        DealChampionDamage($defenderPlayer, 3);
    }
};

// --- Soothing Disillusion (geq18a4f2h): choose mode handler ---
$customDQHandlers["SoothingDisillusionMode"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        // Mode 1: Destroy target phantasia
        $phantasias = array_merge(
            ZoneSearch("myField", ["PHANTASIA"]),
            ZoneSearch("theirField", ["PHANTASIA"])
        );
        $phantasias = FilterSpellshroudTargets($phantasias);
        if(!empty($phantasias)) {
            $pStr = implode("&", $phantasias);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $pStr, 1, "Choose_phantasia_to_destroy");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SoothingDisillusionDestroy", 1);
        }
    } else {
        // Mode 2: Put buff counter on target Animal or Beast ally
        $allies = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["ANIMAL", "BEAST"]);
        $theirAllies = ZoneSearch("theirField", ["ALLY"], cardSubtypes: ["ANIMAL", "BEAST"]);
        $targets = array_merge($allies, $theirAllies);
        $targets = FilterSpellshroudTargets($targets);
        if(!empty($targets)) {
            $tStr = implode("&", $targets);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $tStr, 1, "Choose_Animal/Beast_ally_for_buff_counter");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SoothingDisillusionBuff", 1);
        }
    }
};

$customDQHandlers["SoothingDisillusionDestroy"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    DoAllyDestroyed($player, $lastDecision);
};

$customDQHandlers["SoothingDisillusionBuff"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    AddCounters($player, $lastDecision, "buff", 1);
};

// --- Cone of Frost (i7sbjy86ep): step-based handler for level-conditional damage ---
$customDQHandlers["ConeOfFrostStep"] = function($player, $parts, $lastDecision) {    // $parts[0] = step (1, 2, or 3), $parts[1] = source mzID
    $step = intval($parts[0]);
    $sourceMZ = $parts[1];
    $level = PlayerLevel($player);

    if($step == 1) {
        // Step 1: present first target choice (Level 1+)
        $targets = array_merge(
            ZoneSearch("myField", ["ALLY", "CHAMPION"]),
            ZoneSearch("theirField", ["ALLY", "CHAMPION"])
        );
        $targets = FilterSpellshroudTargets($targets);
        if(!empty($targets)) {
            $tStr = implode("&", $targets);
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $tStr, 1, "Deal_2_damage_to_target_(Cone_of_Frost_1)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "ConeOfFrostStep|2|" . $sourceMZ, 1);
        }
    } elseif($step == 2) {
        // Process first target choice
        if($lastDecision !== "-" && $lastDecision !== "") {
            DealDamage($player, $sourceMZ, $lastDecision, 2);
        }
        // Step 2: present second target choice (Level 3+)
        if($level >= 3) {
            $targets = array_merge(
                ZoneSearch("myField", ["ALLY", "CHAMPION"]),
                ZoneSearch("theirField", ["ALLY", "CHAMPION"])
            );
            $targets = FilterSpellshroudTargets($targets);
            if(!empty($targets)) {
                $tStr = implode("&", $targets);
                DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $tStr, 1, "Deal_2_damage_to_target_(Cone_of_Frost_2)");
                DecisionQueueController::AddDecision($player, "CUSTOM", "ConeOfFrostStep|3|" . $sourceMZ, 1);
            }
        }
    } elseif($step == 3) {
        // Process second target choice
        if($lastDecision !== "-" && $lastDecision !== "") {
            DealDamage($player, $sourceMZ, $lastDecision, 2);
        }
        // Step 3: present third target choice (Level 5+)
        if($level >= 5) {
            $targets = array_merge(
                ZoneSearch("myField", ["ALLY", "CHAMPION"]),
                ZoneSearch("theirField", ["ALLY", "CHAMPION"])
            );
            $targets = FilterSpellshroudTargets($targets);
            if(!empty($targets)) {
                $tStr = implode("&", $targets);
                DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $tStr, 1, "Deal_2_damage_to_target_(Cone_of_Frost_3)");
                DecisionQueueController::AddDecision($player, "CUSTOM", "ConeOfFrostStep|4|" . $sourceMZ, 1);
            }
        }
    } elseif($step == 4) {
        // Process third target choice
        if($lastDecision !== "-" && $lastDecision !== "") {
            DealDamage($player, $sourceMZ, $lastDecision, 2);
        }
    }
};

// --- Fiery Swing (ijkyboiopv): recursive banish handler ---
$customDQHandlers["FierySwingBanish"] = function($player, $parts, $lastDecision) {
    // $parts[0] = source mzID, $parts[1] = count banished so far
    $sourceMZ = $parts[0];
    $count = intval($parts[1]);

    // Process previous choice
    if($lastDecision !== "-" && $lastDecision !== "") {
        MZMove($player, $lastDecision, "myBanish");
        $count++;
    }

    // Check if we can banish more (max 6)
    if($count < 6) {
        $fireGY = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
        if(!empty($fireGY)) {
            $fireStr = implode("&", $fireGY);
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $fireStr, 1, "Banish_fire_card_from_GY?_(" . $count . "/6_banished)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "FierySwingBanish|" . $sourceMZ . "|" . $count, 1);
            return;
        }
    }

    // Done banishing — apply power bonus
    if($count > 0) {
        AddTurnEffect($sourceMZ, "ijkyboiopv-" . $count);
    }
};

// --- Force Load: step 1 — store bullet choice, then choose gun ---
$customDQHandlers["ForceLoadStoreBullet"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $guns = GetUnloadedGuns($player);
    if(empty($guns)) return;
    $gunStr = implode("&", $guns);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $gunStr, 1, "Choose_Gun_to_load");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ForceLoadChooseGun|" . $lastDecision, 1);
};

// --- Force Load: step 2 — materialize bullet to field, load into gun ---
$customDQHandlers["ForceLoadChooseGun"] = function($player, $parts, $lastDecision) {
    // $parts[0] = mzID of the chosen bullet from material deck
    // $lastDecision = mzID of the chosen gun
    $bulletMZ = $parts[0];
    if($lastDecision === "-" || $lastDecision === "") return;
    // Materialize the bullet to field first (paying 0 cost since it's memory cost 0)
    $bulletObj = MZMove($player, $bulletMZ, "myField");
    $bulletObj->Controller = $player;
    DecisionQueueController::CleanupRemovedCards();
    // Now find the bullet on the field and load it into the gun
    $field = GetZone("myField");
    $bulletFieldMZ = null;
    for($i = count($field) - 1; $i >= 0; --$i) {
        if(!$field[$i]->removed && $field[$i] === $bulletObj) {
            $bulletFieldMZ = "myField-" . $i;
            break;
        }
    }
    if($bulletFieldMZ !== null) {
        LoadBulletIntoGun($player, $bulletFieldMZ, $lastDecision);
    }
};

// --- Refresh Chamber: after materialize, may banish floating from GY to put card in memory ---
$customDQHandlers["RefreshChamberBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    // Banish the floating memory card from graveyard
    MZMove($player, $lastDecision, "myBanish");
    // Find Refresh Chamber on the effect stack or graveyard (it just resolved to GY)
    // and move it to memory
    $gy = GetZone("myGraveyard");
    for($i = count($gy) - 1; $i >= 0; --$i) {
        if(!$gy[$i]->removed && $gy[$i]->CardID === "7nk45swaf8") {
            MZMove($player, "myGraveyard-" . $i, "myMemory");
            break;
        }
    }
};

// --- Load Soul: after materialize, may add durability to Gun and put card on lineage ---
$customDQHandlers["LoadSoulDurability"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    // $lastDecision = mzID of the chosen Gun
    AddCounters($player, $lastDecision, "durability", 2);
    // Put Load Soul on bottom of champion's lineage
    $field = &GetField($player);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && PropertyContains(EffectiveCardType($field[$i]), "CHAMPION") && $field[$i]->Controller == $player) {
            if(!is_array($field[$i]->Subcards)) $field[$i]->Subcards = [];
            $field[$i]->Subcards[] = "8tuhuy4xip"; // Load Soul CardID
            break;
        }
    }
    // Remove Load Soul from graveyard (it was just activated and resolved)
    $gy = GetZone("myGraveyard");
    for($gi = count($gy) - 1; $gi >= 0; --$gi) {
        if(!$gy[$gi]->removed && $gy[$gi]->CardID === "8tuhuy4xip") {
            MZRemove($player, "myGraveyard-" . $gi);
            DecisionQueueController::CleanupRemovedCards();
            break;
        }
    }
};

// --- Deploy Gunshield: target Gun gains spellshroud until EOT ---
$customDQHandlers["DeployGunshieldTarget"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    AddTurnEffect($lastDecision, "SPELLSHROUD");
};

// --- Seeker's Rifle: On Kill pay (2) to materialize a Bullet ---
$customDQHandlers["SeekersRifleOnKill"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    // Pay 2 reserve (move 2 cards from hand to memory)
    $hand = GetZone("myHand");
    if(count($hand) < 2) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", array_map(function($i) { return "myHand-" . $i; }, range(0, count($hand) - 1))), 1, "Pay_reserve_(1/2)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SeekersRiflePayReserve|1", 1);
};

$customDQHandlers["SeekersRiflePayReserve"] = function($player, $parts, $lastDecision) {
    $payNum = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "") return;
    MZMove($player, $lastDecision, "myMemory");
    if($payNum < 2) {
        $hand = GetZone("myHand");
        if(!empty($hand)) {
            DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", array_map(function($i) { return "myHand-" . $i; }, range(0, count($hand) - 1))), 1, "Pay_reserve_(2/2)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SeekersRiflePayReserve|2", 1);
        }
    } else {
        // Payment complete — materialize a Bullet
        SupplyDroneMaterialize($player);
    }
};

// --- Mindbreak Bullet: CB On Champion Hit — look at memory, discard a card ---
$customDQHandlers["MindbreakBulletDiscard"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    // Discard the chosen card from opponent's memory
    MZMove($player, $lastDecision, "theirGraveyard");
};

// --- Freezing Round: On Champion Hit — banish random from memory, return at next end phase ---
$customDQHandlers["FreezingRoundReturn"] = function($player, $parts, $lastDecision) {
    // $parts[0] = cardID of the banished card, $parts[1] = opponent player number
    $cardID = $parts[0];
    $opponent = intval($parts[1]);
    // Find the banished card with FREEZING_ROUND_RETURN TurnEffect and return to memory
    $banishZone = $opponent == $player ? "myBanish" : "theirBanish";
    $memoryZone = $opponent == $player ? "myMemory" : "theirMemory";
    $zone = GetZone($banishZone);
    for($i = 0; $i < count($zone); ++$i) {
        if(!$zone[$i]->removed && $zone[$i]->CardID === $cardID && in_array("FREEZING_ROUND_RETURN", $zone[$i]->TurnEffects)) {
            $zone[$i]->TurnEffects = array_values(array_diff($zone[$i]->TurnEffects, ["FREEZING_ROUND_RETURN"]));
            MZMove($player, $banishZone . "-" . $i, $memoryZone);
            break;
        }
    }
};

// --- Turbulent Bullet: CB On Hit — up to 2 allies get +1 power ---
$customDQHandlers["TurbulentBulletBuff"] = function($player, $parts, $lastDecision) {
    $remaining = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "" || $remaining <= 0) return;
    AddTurnEffect($lastDecision, "f8urrqtjot"); // Turbulent Bullet effect ID
    if($remaining > 1) {
        $allies = ZoneSearch("myField", ["ALLY"]);
        if(!empty($allies)) {
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $allies), 1, "Choose_another_ally_for_+1_power?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "TurbulentBulletBuff|0", 1);
        }
    }
};

// --- Supply Drone: materialize a 0-cost Bullet from material deck ---
function SupplyDroneMaterialize($player) {
    $materialZone = GetZone("myMaterial");
    $bulletMZs = [];
    for($i = 0; $i < count($materialZone); ++$i) {
        $obj = $materialZone[$i];
        if(!$obj->removed && PropertyContains(CardSubtypes($obj->CardID), "BULLET") && CardMemoryCost($obj) == 0) {
            $bulletMZs[] = "myMaterial-" . $i;
        }
    }
    if(empty($bulletMZs)) return;
    // The player materializes a 0-cost Bullet (pays memory cost which is 0)
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $bulletMZs), 1, "Materialize_a_0-cost_Bullet");
    DecisionQueueController::AddDecision($player, "CUSTOM", "MATERIALIZE", 1);
}

// Mad Hatter, Morose Heritor (2nc48s3oqh): materialize a Ranger regalia from material deck
function MadHatterMaterialize($player) {
    $materialZone = GetZone("myMaterial");
    $regalias = [];
    for($i = 0; $i < count($materialZone); ++$i) {
        $obj = $materialZone[$i];
        if(!$obj->removed && PropertyContains(CardType($obj->CardID), "REGALIA")
           && PropertyContains(CardClasses($obj->CardID), "RANGER")) {
            $regalias[] = "myMaterial-" . $i;
        }
    }
    if(empty($regalias)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $regalias), 1,
        tooltip:"Choose_a_Ranger_regalia_to_materialize");
    DecisionQueueController::AddDecision($player, "CUSTOM", "MATERIALIZE", 1);
}

// ============================================================================
// Celestial Calling (izm6h38lrj): reveal from deck, banish astra Spell, free activate next recollection
// ============================================================================

/**
 * Reveal cards from the top of the deck until an astra Spell is found.
 * Banish that card (tagged with CELESTIAL_CALLING TurnEffect), put the rest on bottom in random order.
 */
function CelestialCallingReveal($player) {
    $deck = &GetDeck($player);
    $foundIndex = -1;
    // Reveal cards top-down until we find an astra Spell
    for($i = 0; $i < count($deck); ++$i) {
        $cardID = $deck[$i]->CardID;
        if(CardElement($cardID) === "ASTRA" && PropertyContains(CardSubtypes($cardID), "SPELL")) {
            $foundIndex = $i;
            break;
        }
    }
    if($foundIndex < 0) {
        // No astra Spell found — put all revealed cards on bottom in random order
        // (all cards were "revealed" conceptually, but deck stays intact)
        return;
    }

    // Remove the found card and all cards above it from the deck
    $revealed = [];
    for($i = 0; $i <= $foundIndex; ++$i) {
        $revealed[] = array_shift($deck);
    }

    // Show the found card as a reveal
    $foundCard = $revealed[$foundIndex];
    SetFlashMessage('REVEAL:' . $foundCard->CardID);

    // Banish the found astra Spell, tag it for free activation next recollection
    $banishObj = AddBanish($player, $foundCard->CardID);
    $banishObj->AddTurnEffects("CELESTIAL_CALLING");

    // Put remaining revealed cards on the bottom of the deck in random order
    $remaining = [];
    for($i = 0; $i < count($revealed); ++$i) {
        if($i === $foundIndex) continue;
        $remaining[] = $revealed[$i];
    }
    shuffle($remaining);
    foreach($remaining as $cardObj) {
        array_push($deck, $cardObj);
    }
}

/**
 * Process Celestial Calling trigger during RecollectionPhase.
 * Scans banishment for a card with CELESTIAL_CALLING TurnEffect and offers free activation.
 */
function CelestialCallingRecollectionCheck($player) {
    $banish = GetZone("myBanish");
    for($i = 0; $i < count($banish); ++$i) {
        if(!$banish[$i]->removed && in_array("CELESTIAL_CALLING", $banish[$i]->TurnEffects)) {
            $mz = "myBanish-" . $i;
            DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
                tooltip:"Activate_" . CardName($banish[$i]->CardID) . "_for_free?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "CelestialCallingActivate|$mz|" . $banish[$i]->CardID, 1);
            return; // Only one at a time
        }
    }
}

$customDQHandlers["CelestialCallingActivate"] = function($player, $parts, $lastDecision) {
    $targetMZ = $parts[0];
    $targetCardID = $parts[1];
    // Clear the tag regardless of choice
    $banish = GetZone("myBanish");
    for($i = 0; $i < count($banish); ++$i) {
        if(!$banish[$i]->removed && in_array("CELESTIAL_CALLING", $banish[$i]->TurnEffects)) {
            $banish[$i]->TurnEffects = array_values(array_diff($banish[$i]->TurnEffects, ["CELESTIAL_CALLING"]));
            break;
        }
    }
    if($lastDecision !== "YES") return;

    // Find the card in banishment (index may have shifted)
    $banish = GetZone("myBanish");
    $actualMZ = null;
    for($i = 0; $i < count($banish); ++$i) {
        if(!$banish[$i]->removed && $banish[$i]->CardID === $targetCardID) {
            $actualMZ = "myBanish-" . $i;
            break;
        }
    }
    if($actualMZ === null) return;

    // Move to hand then activate for free (ignoreCost = true)
    $handObj = MZMove($player, $actualMZ, "myHand");
    DecisionQueueController::CleanupRemovedCards();
    // Find in hand
    $hand = GetZone("myHand");
    $handMZ = null;
    for($hi = count($hand) - 1; $hi >= 0; --$hi) {
        if(!$hand[$hi]->removed && $hand[$hi]->CardID === $targetCardID) {
            $handMZ = "myHand-" . $hi;
            break;
        }
    }
    if($handMZ === null) return;
    ActivateCard($player, $handMZ, true);
};

// ============================================================================
// Scry the Stars (oz23yfzk96): CB alt-cost banish Scry the Skies from GY
// ============================================================================

$customDQHandlers["PrimordialRitualSacrifice"] = function($player, $parts, $lastDecision) {
    if($lastDecision && $lastDecision !== "-") {
        DoSacrificeFighter($player, $lastDecision);
        DecisionQueueController::CleanupRemovedCards();
    }
};

// Sly Songstress (f28y5rn0dt): whenever you activate Harmony/Melody, may discard→draw
$customDQHandlers["SlySongstressDiscard"] = function($player, $parts, $lastDecision) {
    $fieldIdx = intval($parts[0]);
    if($lastDecision !== "YES") return;
    $field = &GetField($player);
    if(!isset($field[$fieldIdx]) || $field[$fieldIdx]->removed || $field[$fieldIdx]->CardID !== "f28y5rn0dt") return;
    $hand = &GetHand($player);
    if(count($hand) == 0) return;
    $handCards = [];
    for($i = 0; $i < count($hand); ++$i) {
        if(!$hand[$i]->removed) $handCards[] = "myHand-" . $i;
    }
    if(empty($handCards)) return;
    $handStr = implode("&", $handCards);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $handStr, 1, "Discard_a_card");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SlySongstressDrawAfterDiscard", 1);
};

$customDQHandlers["SlySongstressDrawAfterDiscard"] = function($player, $parts, $lastDecision) {
    if($lastDecision && $lastDecision !== "-") {
        DoDiscardCard($player, $lastDecision);
        Draw($player, 1);
    }
};

$customDQHandlers["SquallbindPounceDistant"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-") {
        BecomeDistant($player, $lastDecision);
    }
};

$customDQHandlers["SquallbindPounceSuppress"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-") {
        SuppressAlly($player, $lastDecision);
    }
};

$customDQHandlers["WinblessHurricaneFarmReveal"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        $windMem = ZoneSearch("myMemory", cardElements: ["WIND"]);
        // Reveal 3 wind cards from memory
        $revealIDs = [];
        for($ri = 0; $ri < min(3, count($windMem)); ++$ri) {
            $rObj = GetZoneObject($windMem[$ri]);
            if($rObj !== null) $revealIDs[] = $rObj->CardID;
        }
        if(!empty($revealIDs)) {
            SetFlashMessage('REVEAL:' . implode('|', $revealIDs));
        }
        // Summon a Powercell token
        MZAddZone($player, "myField", "qzzadf9q1v");
    }
};

$customDQHandlers["ScryTheStarsAltCost"] = function($player, $parts, $lastDecision) {
    $reserveCost = intval($parts[0]);
    if($lastDecision === "YES") {
        // Banish Scry the Skies (F9POfB5Nah) from graveyard
        $gy = GetZone("myGraveyard");
        for($i = count($gy) - 1; $i >= 0; --$i) {
            if(!$gy[$i]->removed && $gy[$i]->CardID === "F9POfB5Nah") {
                MZMove($player, "myGraveyard-" . $i, "myBanish");
                break;
            }
        }
    } else {
        // Pay normal reserve cost
        for($i = 0; $i < $reserveCost; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
    }
    DecisionQueueController::StoreVariable("additionalCostPaid", "NO");
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
};

// ============================================================================
// Nico, Whiplash Allure (5bbae3z4py) + Magebane Lash (oh300z2sns) helpers
// ============================================================================

// --- Imperial Apprentice (u6o6eanbrf): may banish floating memory from GY to draw ---
$customDQHandlers["ImperialApprenticeFloating"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myBanish");
    Draw($player, 1);
};

// --- Cell Assembler (vc8sugly4w): summon Powercell rested after paying (2) ---
$customDQHandlers["CellAssemblerSummon"] = function($player, $parts, $lastDecision) {
    $pcObj = MZAddZone($player, "myField", "qzzadf9q1v");
    $pcObj->Status = 1; // rested
};

// --- Tonic of Remembrance (uqrptjej4m): return a card from memory to hand ---
$customDQHandlers["TonicOfRemembranceReturn"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myHand");
};

// Krustallan Ruins (fei7chsbal): pay (1) or rest the entering ally
$customDQHandlers["KrustallanRuinsPayOrRest"] = function($player, $parts, $lastDecision) {
    $fieldIdx = intval($parts[0]);
    $field = &GetField($player);
    if(!isset($field[$fieldIdx]) || $field[$fieldIdx]->removed) return;
    if($lastDecision === "YES") {
        // Pay (1): move a card from hand to memory
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
    } else {
        // Rest the ally
        $field[$fieldIdx]->Status = 1;
    }
};

// --- Fractal of Rain (3zb9p4lgdl): target player mills 1 ---
$customDQHandlers["FractalOfRainMill"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    // Determine which player owns the chosen champion
    $targetObj = GetZoneObject($lastDecision);
    if($targetObj === null) return;
    $targetPlayer = $targetObj->Controller;
    global $playerID;
    $deckRef = ($targetPlayer == $playerID) ? "myDeck" : "theirDeck";
    $gyRef = ($targetPlayer == $playerID) ? "myGraveyard" : "theirGraveyard";
    MillCards($player, $deckRef, $gyRef, 1);
};

// Suffocating Miasma recollection: debuff chosen ally or deal 2 unpreventable to champion
$customDQHandlers["SuffocatingMiasmaRecollection"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        AddCounters($player, $lastDecision, "debuff", 1);
    } else {
        $champMZ = FindChampionMZ($player);
        if($champMZ !== null) {
            DealUnpreventableDamage($player, $champMZ, $champMZ, 2);
        }
    }
};

// Clockwork Musicbox: banish just-activated Harmony/Melody and tag it
$customDQHandlers["MusicboxBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $cardID = DecisionQueueController::GetVariable("MusicboxBanishCardID");
    if(empty($cardID)) return;
    // Find the card — it could be in graveyard (ACTION) or on field (ALLY/PHANTASIA)
    $gy = GetZone("myGraveyard");
    for($i = count($gy) - 1; $i >= 0; --$i) {
        if(!$gy[$i]->removed && $gy[$i]->CardID === $cardID) {
            $banished = MZMove($player, "myGraveyard-" . $i, "myBanish");
            if($banished !== null) $banished->Counters['_musicbox'] = 1;
            return;
        }
    }
    $field = GetZone("myField");
    $musicboxIdx = intval(DecisionQueueController::GetVariable("MusicboxFieldIdx") ?? "-1");
    for($i = count($field) - 1; $i >= 0; --$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === $cardID && $i !== $musicboxIdx) {
            $banished = MZMove($player, "myField-" . $i, "myBanish");
            if($banished !== null) $banished->Counters['_musicbox'] = 1;
            DecisionQueueController::CleanupRemovedCards();
            return;
        }
    }
};

// Clockwork Musicbox REST: activate a card banished by Musicbox
$customDQHandlers["MusicboxActivate"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MusicboxActivateCard($player, $lastDecision);
};

function MusicboxActivateCard($player, $banishedMZ) {
    $handObj = MZMove($player, $banishedMZ, "myHand");
    $hand = &GetHand($player);
    $handIdx = count($hand) - 1;
    ActivateCard($player, "myHand-" . $handIdx, false);
}

// Sinister Mindreaver: continue after first memory pick
function SinisterMindreaverContinue($player, $pick1) {
    if($pick1 === "-" || $pick1 === "" || $pick1 === "PASS") return;
    MZMove($player, $pick1, "theirGraveyard");
    $oppMemory2 = ZoneSearch("theirMemory");
    if(!empty($oppMemory2)) {
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $oppMemory2), 1, "Discard_another_from_memory?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "SinisterMindreaverPick2", 1);
    } else {
        $opponent = ($player == 1) ? 2 : 1;
        DrawIntoMemory($opponent, 1);
    }
}

$customDQHandlers["SinisterMindreaverPick2"] = function($player, $parts, $lastDecision) {
    $opponent = ($player == 1) ? 2 : 1;
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        MZMove($player, $lastDecision, "theirGraveyard");
        DrawIntoMemory($opponent, 2);
    } else {
        DrawIntoMemory($opponent, 1);
    }
};

// Loaded Thoughts (hh88rx6p3p): mill top card of deck when gun becomes loaded
$customDQHandlers["LoadedThoughtsMill"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $deck = GetDeck($player);
    if(empty($deck)) return;
    MZMove($player, "myDeck-0", "myGraveyard");
};

/**
 * Fire whenever a floating memory card is banished from the player's graveyard.
 * If Nico (5bbae3z4py) is on that player's field and has abilities, add a lash counter.
 */
function NicoOnFloatingMemoryBanished($player) {
    global $playerID;
    $zone = ($player == $playerID) ? "myField" : "theirField";
    $field = &GetField($player);
    foreach($field as $fi => $fObj) {
        if($fObj->removed) continue;
        if($fObj->CardID === "5bbae3z4py" && !HasNoAbilities($fObj)) {
            AddCounters($player, "$zone-$fi", "lash", 1);
            return;
        }
    }
}

/**
 * Magebane Lash (oh300z2sns): Nico Bonus — when Nico, Whiplash Allure is the champion
 * and takes non-combat damage, recover 2 if Magebane Lash is on the field with abilities.
 */
function MagebaneNicoBonusCheck($player) {
    $field = &GetField($player);
    foreach($field as $fObj) {
        if($fObj->removed) continue;
        if($fObj->CardID === "oh300z2sns" && !HasNoAbilities($fObj)) {
            RecoverChampion($player, 2);
            return;
        }
    }
}

/**
 * Aegis of Dawn (abipl6gt7l): whenever your champion is dealt 4+ damage,
 * summon an Automaton Drone token. Only fires if Aegis of Dawn is on the field.
 */
function AegisOfDawnTrigger($player) {
    $field = &GetField($player);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "abipl6gt7l" && !HasNoAbilities($field[$i])) {
            MZAddZone($player, "myField", "mu6gvnta6q"); // Automaton Drone token
        }
    }
}

// --- Backup Charger (9gv4vm4kj3): after reserve payment, summon Powercell rested + draw into memory ---
$customDQHandlers["BackupChargerEffect"] = function($player, $parts, $lastDecision) {
    MZAddZone($player, "myField", "qzzadf9q1v"); // Powercell token
    $field = &GetField($player);
    $field[count($field) - 1]->Status = 1; // Rested
    DrawIntoMemory($player, 1);
};

// --- Lunar Conduit (0yetaebjlw): deal damage = charge counters, then remove 1 charge ---
$customDQHandlers["LunarConduitDamage"] = function($player, $parts, $lastDecision) {
    $sourceMZ = $parts[0];
    if($lastDecision === "-" || $lastDecision === "") return;
    $sourceObj = GetZoneObject($sourceMZ);
    if($sourceObj === null) return;
    $chargeCount = GetCounterCount($sourceObj, "charge");
    if($chargeCount > 0) {
        DealDamage($player, $sourceMZ, $lastDecision, $chargeCount);
        RemoveCounters($player, $sourceMZ, "charge", 1);
    }
};

// Spirited Falconer: second buff counter pick after the first ally was chosen
$customDQHandlers["SpiritedFalconerBuff2"] = function($player, $parts, $lastDecision) {
    $chosen2 = $lastDecision;
    if($chosen2 !== "-" && $chosen2 !== "" && $chosen2 !== "PASS") {
        AddCounters($player, $chosen2, "buff", 1);
    }
};

// ============================================================================
// Powercell sacrifice triggers (shared by Turbo Charge, Atmos Armor, Powercell self-sacrifice)
// ============================================================================
function TriggerPowercellSacrifice($player) {
    // Powered Armsmaster (fpvw2ifz1n): [CB] whenever you sacrifice a Powercell, becomes distant
    if(IsClassBonusActive($player, ["RANGER"])) {
        global $playerID;
        $fieldZone = $player == $playerID ? "myField" : "theirField";
        $field = GetZone($fieldZone);
        for($ai = 0; $ai < count($field); ++$ai) {
            if(!$field[$ai]->removed && $field[$ai]->CardID === "fpvw2ifz1n" && !HasNoAbilities($field[$ai])) {
                BecomeDistant($player, $fieldZone . "-" . $ai);
            }
        }
    }

    // Charged Gunslinger (svdv3zb9p4): [CB] whenever you sacrifice a Powercell,
    // draw a card, discard a card. If fire discarded, becomes distant.
    if(IsClassBonusActive($player, ["RANGER"])) {
        global $playerID;
        $fieldZone = $player == $playerID ? "myField" : "theirField";
        $field = GetZone($fieldZone);
        for($ai = 0; $ai < count($field); ++$ai) {
            if(!$field[$ai]->removed && $field[$ai]->CardID === "svdv3zb9p4" && !HasNoAbilities($field[$ai])) {
                Draw($player, 1);
                $hand = ZoneSearch("myHand");
                if(!empty($hand)) {
                    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $hand), 1, tooltip:"Discard_a_card_(Charged_Gunslinger)");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "ChargedGunslingerDiscard|$fieldZone-$ai", 1);
                }
                break;
            }
        }
    }

    // Engineered Slime (kkz07nau5s): [CB] whenever you sacrifice a Powercell, choose one —
    // buff counter or spellshroud until end of turn
    if(IsClassBonusActive($player, ["TAMER"])) {
        global $playerID;
        $fieldZone = $player == $playerID ? "myField" : "theirField";
        $field = GetZone($fieldZone);
        for($ai = 0; $ai < count($field); ++$ai) {
            if(!$field[$ai]->removed && $field[$ai]->CardID === "kkz07nau5s" && !HasNoAbilities($field[$ai])) {
                DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Buff_counter?_(No=Spellshroud)");
                DecisionQueueController::AddDecision($player, "CUSTOM", "EngineeredSlimeChoice|$fieldZone-$ai", 1);
                break;
            }
        }
    }
}

// Charged Gunslinger (svdv3zb9p4): discard handler — if fire element, become distant
$customDQHandlers["ChargedGunslingerDiscard"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $gunslingerMZ = $parts[0];
    $cardObj = GetZoneObject($lastDecision);
    $cardID = $cardObj !== null ? $cardObj->CardID : null;
    DoDiscardCard($player, $lastDecision);
    if($cardID !== null && CardElement($cardID) === "FIRE") {
        BecomeDistant($player, $gunslingerMZ);
    }
};

// Turbo Charge / Atmos Armor Type-Hermes: sacrifice a Powercell
$customDQHandlers["PowercellSacrifice"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    OnLeaveField($player, $lastDecision);
    MZMove($player, $lastDecision, "myGraveyard");
    DecisionQueueController::CleanupRemovedCards();
    TriggerPowercellSacrifice($player);
};

// Overlord Mk III (sl7ddcgw05): iterative sacrifice of 4 Powercells
$customDQHandlers["OverlordSacrifice"] = function($player, $parts, $lastDecision) {
    $remaining = intval($parts[0]);
    if($lastDecision !== "-" && $lastDecision !== "") {
        OnLeaveField($player, $lastDecision);
        MZMove($player, $lastDecision, "myGraveyard");
        DecisionQueueController::CleanupRemovedCards();
        TriggerPowercellSacrifice($player);
    }
    if($remaining > 0) {
        $powercells = ZoneSearch("myField", cardSubtypes: ["POWERCELL"]);
        if(!empty($powercells)) {
            $pcChoices = implode("&", $powercells);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $pcChoices, 100, tooltip:"Sacrifice_a_Powercell_(" . (5 - $remaining) . "_of_4)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "OverlordSacrifice|" . ($remaining - 1), 100);
        }
    }
};

// Overlord Mk III (sl7ddcgw05): end phase — banish Automaton from GY → buff + draw
$customDQHandlers["OverlordEndPhase"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myBanish");
    $fieldIdx = intval($parts[0]);
    AddCounters($player, "myField-" . $fieldIdx, "buff", 1);
    Draw($player, 1);
};

// Whirlwind Reaper (x7yc0ije4d): YesNo handler for end phase wake-up
$customDQHandlers["WhirlwindReaperWakeup"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $fieldIdx = intval($parts[0]);
    // Remove 1 preparation counter from champion
    $field = &GetField($player);
    foreach($field as $fi => $fObj) {
        if(!$fObj->removed && PropertyContains(EffectiveCardType($fObj), "CHAMPION")) {
            RemoveCounters($player, "myField-" . $fi, "preparation", 1);
            break;
        }
    }
    // Wake up Whirlwind Reaper
    $reaperObj = &$field[$fieldIdx];
    if(!$reaperObj->removed) {
        $reaperObj->Status = 2;
    }
};

// ============================================================================
// Kindling Flare (dcgw05qzza): iterative herb sacrifice as additional cost
// ============================================================================
$customDQHandlers["KindlingFlareSacHerb"] = function($player, $parts, $lastDecision) {
    $reserveCost = intval($parts[0]);
    $herbCount = intval(DecisionQueueController::GetVariable("kindlingHerbCount"));

    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // Player declined — done sacrificing, queue reserve payments
        for($i = 0; $i < $reserveCost; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
        return;
    }

    // Sacrifice the chosen herb
    OnLeaveField($player, $lastDecision);
    MZMove($player, $lastDecision, "myGraveyard");
    DecisionQueueController::CleanupRemovedCards();
    $herbCount++;
    DecisionQueueController::StoreVariable("kindlingHerbCount", strval($herbCount));

    // Check for more herbs
    $herbs = ZoneSearch("myField", cardSubtypes: ["HERB"]);
    if(!empty($herbs)) {
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $herbs), 100, tooltip:"Sacrifice_another_Herb?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "KindlingFlareSacHerb|$reserveCost", 100);
    } else {
        // No more herbs — queue reserve payments
        for($i = 0; $i < $reserveCost; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
    }
};

// ============================================================================
// Molten Cinder (df9q1vk8ao): ActivateAbility target champion that leveled up
// ============================================================================
$customDQHandlers["MoltenCinderTarget"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    DealDamage($player, "df9q1vk8ao", $lastDecision, 3);
};

// ============================================================================
// Arcane Blast (pn9gQjV3Rb): deal 11 damage to chosen champion
// ============================================================================
$customDQHandlers["ArcaneBlastTarget"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    DealDamage($player, "pn9gQjV3Rb", $lastDecision, 11);
};

// ============================================================================
// Caretaker Horse (r5uyjq37zh): chosen ally becomes fostered
// ============================================================================
$customDQHandlers["CaretakerHorseFoster"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    BecomeFostered($player, $lastDecision);
    OnFoster($player, $lastDecision);
};

// ============================================================================
// Blazing Bowman (qry41lw9n0): banish fire card from GY for +2 POWER
// ============================================================================
$customDQHandlers["BlazingBowmanBanish"] = function($player, $parts, $lastDecision) {
    $mzID = $parts[0];
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myBanish");
    AddTurnEffect($mzID, "qry41lw9n0");
};

// ============================================================================
// Modulating Cadence (p5p0azskw4): reveal Harmony/Melody from TempZone, rest to bottom
// ============================================================================
$customDQHandlers["ModulatingCadenceReveal"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        Reveal($player, revealedMZ: $lastDecision);
        MZMove($player, $lastDecision, "myHand");
    }
    // Put remaining TempZone cards on bottom of deck
    $remaining = ZoneSearch("myTempZone");
    foreach($remaining as $rmz) {
        MZMove($player, $rmz, "myDeck");
    }
};
$customDQHandlers["ModulatingCadenceBottom"] = function($player, $parts, $lastDecision) {
    // Put all TempZone cards on bottom of deck
    $remaining = ZoneSearch("myTempZone");
    foreach($remaining as $rmz) {
        MZMove($player, $rmz, "myDeck");
    }
};

// ============================================================================
// Lost in Thought (egbscxwjbq): iterative floating memory banish from GY
// ============================================================================
$customDQHandlers["LostInThoughtBanish"] = function($player, $parts, $lastDecision) {
    $banished = intval($parts[0]);

    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // Player declined — draw for all banished so far
        if($banished > 0) Draw($player, $banished);
        return;
    }

    // Banish the chosen card
    MZMove($player, $lastDecision, "myBanish");
    NicoOnFloatingMemoryBanished($player);
    $banished++;

    // Check for more floating memory cards in graveyard
    $floatingGY = ZoneSearch("myGraveyard", floatingMemoryOnly: true);
    if(!empty($floatingGY)) {
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $floatingGY), 1, tooltip:"Banish_another_floating_memory_card?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "LostInThoughtBanish|$banished", 1);
    } else {
        // No more — draw for all banished
        if($banished > 0) Draw($player, $banished);
    }
};

// ============================================================================
// Purified Shot (dcgw05q66h): [CB] On Champion Hit — banish up to X from opponent GY
// ============================================================================
$customDQHandlers["PurifiedShotBanish"] = function($player, $parts, $lastDecision) {
    $remaining = intval($parts[0]);

    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        return; // Player declined
    }

    // Banish the chosen card from opponent's graveyard
    MZMove($player, $lastDecision, "theirBanish");
    $remaining--;

    if($remaining > 0) {
        $oppGY = ZoneSearch("theirGraveyard");
        if(!empty($oppGY)) {
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $oppGY), 1, tooltip:"Banish_another_card_from_opponent_graveyard?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "PurifiedShotBanish|$remaining", 1);
        }
    }
};

// ============================================================================
// Engineered Slime (kkz07nau5s): choose buff counter or spellshroud
// ============================================================================
$customDQHandlers["EngineeredSlimeChoice"] = function($player, $parts, $lastDecision) {
    $slimeMZ = $parts[0];
    $obj = GetZoneObject($slimeMZ);
    if($obj === null || $obj->removed) return;
    if($lastDecision === "YES") {
        AddCounters($player, $slimeMZ, "buff", 1);
    } else {
        AddTurnEffect($slimeMZ, "SPELLSHROUD");
    }
};

// ============================================================================
// Imperial Scout (nrow8iopvc): when becoming distant, may mill 2
// ============================================================================
$customDQHandlers["ImperialScoutMill"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    for($i = 0; $i < 2; ++$i) {
        $deck = ZoneSearch("myDeck");
        if(!empty($deck)) {
            MZMove($player, $deck[0], "myGraveyard");
        }
    }
};

// ============================================================================
// Ash Filcher (b65hiv400w): [CB] On Attack — banish fire from GY for +2 POWER
// ============================================================================
$customDQHandlers["AshFilcherBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myBanish");
    // Find Ash Filcher on the field and add POWER TurnEffect
    $field = GetZone("myField");
    foreach($field as $fi => $fObj) {
        if(!$fObj->removed && $fObj->CardID === "b65hiv400w") {
            AddTurnEffect("myField-" . $fi, "b65hiv400w");
            break;
        }
    }
};

// ============================================================================
// Primal Whip (az2b8nfh95): [CB][Lv2+] On Attack — up to 2 non-Human allies +1 POWER
// ============================================================================
$customDQHandlers["PrimalWhipBuff"] = function($player, $parts, $lastDecision) {
    $iteration = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddTurnEffect($lastDecision, "az2b8nfh95");
    if($iteration >= 2) return;
    // Offer second choice from remaining non-Human allies
    $nonHumans = [];
    $field = GetZone("myField");
    foreach($field as $idx => $fObj) {
        if($fObj->removed) continue;
        if(!PropertyContains(EffectiveCardType($fObj), "ALLY")) continue;
        if(PropertyContains(EffectiveCardSubtypes($fObj), "HUMAN")) continue;
        $mz = "myField-" . $idx;
        if($mz === $lastDecision) continue;
        $nonHumans[] = $mz;
    }
    if(empty($nonHumans)) return;
    $choices = implode("&", $nonHumans);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $choices, 1, tooltip:"Choose_another_non-Human_ally_for_+1_POWER");
    DecisionQueueController::AddDecision($player, "CUSTOM", "PrimalWhipBuff|2", 1);
};

// ============================================================================
// Orb of Regret (BY0E8si926): shuffle up to 3 cards from hand into deck, draw that many
// ============================================================================
$customDQHandlers["OrbOfRegretShuffle"] = function($player, $parts, $lastDecision) {
    $iteration = intval($parts[0]);
    $count = intval(DecisionQueueController::GetVariable("OrbOfRegretCount"));
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // Done choosing — shuffle deck and draw the number shuffled in
        if($count > 0) {
            ShuffleZone("myDeck");
            Draw($player, $count);
        }
        return;
    }
    // Move chosen card from hand to deck (bottom)
    MZMove($player, $lastDecision, "myDeck");
    $count++;
    DecisionQueueController::StoreVariable("OrbOfRegretCount", strval($count));
    if($iteration >= 3) {
        // Reached max — shuffle and draw
        ShuffleZone("myDeck");
        Draw($player, $count);
        return;
    }
    // Offer another choice
    $hand = ZoneSearch("myHand");
    if(empty($hand)) {
        // No cards left in hand — shuffle and draw
        ShuffleZone("myDeck");
        Draw($player, $count);
        return;
    }
    $handStr = implode("&", $hand);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $handStr, 1, tooltip:"Shuffle_another_card_into_deck?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "OrbOfRegretShuffle|" . ($iteration + 1), 1);
};

// ============================================================================
// Creative Shock (BqDw4Mei4C): draw 2 discard 1, CB fire → deal 2 to unit
// ============================================================================
$customDQHandlers["CreativeShockDiscard"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    // Check element before discarding
    $obj = GetZoneObject($lastDecision);
    $isFire = ($obj !== null && CardElement($obj->CardID) === "FIRE");
    DoDiscardCard($player, $lastDecision);
    if(!IsClassBonusActive($player, ["MAGE"])) return;
    if(!$isFire) return;
    // Offer to deal 2 damage to a unit
    $targets = array_merge(
        ZoneSearch("myField", ["ALLY", "CHAMPION"]),
        ZoneSearch("theirField", ["ALLY", "CHAMPION"])
    );
    $targets = FilterSpellshroudTargets($targets);
    if(empty($targets)) return;
    $targetStr = implode("&", $targets);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1, tooltip:"Deal_2_damage_to_a_unit?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "CreativeShockDamage", 1);
};

$customDQHandlers["CreativeShockDamage"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $mzID = DecisionQueueController::GetVariable("mzID");
    DealDamage($player, $mzID, $lastDecision, 2);
};

// ============================================================================
// Expunge (r73opcqtzs): mandatory Curse discard from a champion's lineage
// ============================================================================
$customDQHandlers["ExpungeChosenChampion"] = function($player, $parts, $lastDecision) {
    $reserveCost = intval($parts[0]);
    DecisionQueueController::StoreVariable("expungeChampion", $lastDecision);
    DecisionQueueController::AddDecision($player, "CUSTOM", "ExpungePickCurse|$reserveCost", 1);
};

$customDQHandlers["ExpungePickCurse"] = function($player, $parts, $lastDecision) {
    $reserveCost = intval($parts[0]);
    $champMZ = DecisionQueueController::GetVariable("expungeChampion");
    $champObj = &GetZoneObject($champMZ);
    if($champObj === null) return;

    $curses = [];
    foreach($champObj->Subcards as $scIdx => $scID) {
        if(PropertyContains(CardSubtypes($scID), "CURSE")) {
            $curses[] = ['idx' => $scIdx, 'cardID' => $scID];
        }
    }

    if(count($curses) == 1) {
        $curse = $curses[0];
        $curseCost = CardCost_reserve($curse['cardID']);
        array_splice($champObj->Subcards, $curse['idx'], 1);
        MZAddZone($player, "myGraveyard", $curse['cardID']);
        DecisionQueueController::StoreVariable("expungeCurseCost", strval($curseCost));
        for($i = 0; $i < $reserveCost; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
    } else {
        // Multiple Curses — put into TempZone for UI display
        foreach($curses as $c) {
            MZAddZone($player, "myTempZone", $c['cardID']);
        }
        $tempCards = ZoneSearch("myTempZone");
        $tempStr = implode("&", $tempCards);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $tempStr, 1, tooltip:"Choose_Curse_to_discard");
        DecisionQueueController::AddDecision($player, "CUSTOM", "ExpungeCurseChosen|$reserveCost|$champMZ", 1);
    }
};

$customDQHandlers["ExpungeCurseChosen"] = function($player, $parts, $lastDecision) {
    $reserveCost = intval($parts[0]);
    $champMZ = $parts[1];
    $champObj = &GetZoneObject($champMZ);

    $chosenObj = GetZoneObject($lastDecision);
    $chosenCardID = $chosenObj->CardID;
    $curseCost = CardCost_reserve($chosenCardID);

    // Remove the chosen Curse CardID from the champion's Subcards
    for($i = 0; $i < count($champObj->Subcards); ++$i) {
        if($champObj->Subcards[$i] === $chosenCardID) {
            array_splice($champObj->Subcards, $i, 1);
            break;
        }
    }

    // Clear TempZone
    $tempZone = &GetZone("myTempZone");
    foreach($tempZone as $tObj) {
        $tObj->Remove();
    }

    // Record the discarded Curse in the graveyard
    MZAddZone($player, "myGraveyard", $chosenCardID);

    DecisionQueueController::StoreVariable("expungeCurseCost", strval($curseCost));
    for($i = 0; $i < $reserveCost; ++$i) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
};

// ============================================================================
// Ravishing Finale (jlgx72rfgv): mandatory banish 2 floating memory from GY
// ============================================================================
$customDQHandlers["RavishingFinaleBanish1"] = function($player, $parts, $lastDecision) {
    $reserveCost = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "") return;
    $hadFloating = HasFloatingMemory(GetZoneObject($lastDecision));
    MZMove($player, $lastDecision, "myBanish");
    if($hadFloating) NicoOnFloatingMemoryBanished($player);
    // Second pick
    $floatingGY = [];
    $gy = GetZone("myGraveyard");
    for($gi = 0; $gi < count($gy); ++$gi) {
        if(!$gy[$gi]->removed && HasFloatingMemory($gy[$gi])) {
            $floatingGY[] = "myGraveyard-" . $gi;
        }
    }
    if(empty($floatingGY)) return;
    $floatingStr = implode("&", $floatingGY);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $floatingStr, 100, tooltip:"Banish_floating-memory_card_(2_of_2)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "RavishingFinaleBanish2|$reserveCost", 100);
};

$customDQHandlers["RavishingFinaleBanish2"] = function($player, $parts, $lastDecision) {
    $reserveCost = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "") return;
    $hadFloating = HasFloatingMemory(GetZoneObject($lastDecision));
    MZMove($player, $lastDecision, "myBanish");
    if($hadFloating) NicoOnFloatingMemoryBanished($player);
    // Queue normal reserve payments + opportunity
    DecisionQueueController::StoreVariable("additionalCostPaid", "NO");
    for($i = 0; $i < $reserveCost; ++$i) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
};

// ============================================================================
// Purification (k8ao8bki6f): choose up to 2 Curse cards from lineage to discard
// ============================================================================
function PurificationCursePick($player) {
    $curses = GetCursesInLineage($player);
    if(empty($curses)) return;
    $tempZone = &GetTempZone($player);
    while(count($tempZone) > 0) array_pop($tempZone);
    foreach($curses as $curse) {
        MZAddZone($player, "myTempZone", $curse['cardID']);
    }
    $tempMZs = [];
    for($i = 0; $i < count($curses); ++$i) {
        $tempMZs[] = "myTempZone-" . $i;
    }
    $options = implode("&", $tempMZs);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $options, 1, "Choose_Curse_to_discard");
    DecisionQueueController::AddDecision($player, "CUSTOM", "PurificationCurse1", 1);
}

$customDQHandlers["PurificationCurse1"] = function($player, $parts, $lastDecision) {
    $tempZone = &GetTempZone($player);
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        while(count($tempZone) > 0) array_pop($tempZone);
        return;
    }
    $chosenObj = GetZoneObject($lastDecision);
    $chosenCardID = $chosenObj->CardID;
    MZRemove($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    RemoveFromChampionLineage($player, $chosenCardID, "myGraveyard");
    RecoverChampion($player, 2);
    // Offer second pick
    $tempZone = &GetTempZone($player);
    $remaining = [];
    for($i = 0; $i < count($tempZone); ++$i) {
        if(!$tempZone[$i]->removed) {
            $remaining[] = "myTempZone-" . $i;
        }
    }
    if(!empty($remaining)) {
        $options = implode("&", $remaining);
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $options, 1, "Choose_another_Curse_to_discard");
        DecisionQueueController::AddDecision($player, "CUSTOM", "PurificationCurse2", 1);
    } else {
        while(count($tempZone) > 0) array_pop($tempZone);
    }
};

$customDQHandlers["PurificationCurse2"] = function($player, $parts, $lastDecision) {
    $tempZone = &GetTempZone($player);
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        $chosenObj = GetZoneObject($lastDecision);
        $chosenCardID = $chosenObj->CardID;
        RemoveFromChampionLineage($player, $chosenCardID, "myGraveyard");
        RecoverChampion($player, 2);
    }
    while(count($tempZone) > 0) array_pop($tempZone);
};

// ============================================================================
// Bertha, Spry Howitzer (ki6fxxgmue): look at top 5, may activate Ranger action
// ============================================================================
function BerthaLookFinish($player) {
    $tempCards = ZoneSearch("myTempZone");
    // Find valid Ranger action cards with reserve cost ≤ 2
    $validActions = [];
    foreach($tempCards as $tmz) {
        $tObj = GetZoneObject($tmz);
        if(PropertyContains(CardType($tObj->CardID), "ACTION")
            && PropertyContains(CardClasses($tObj->CardID), "RANGER")
            && CardCost_reserve($tObj->CardID) <= 2) {
            $validActions[] = $tmz;
        }
    }
    if(!empty($validActions)) {
        $actionStr = implode("&", $validActions);
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $actionStr, 1, tooltip:"Activate_a_Ranger_action_for_free?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "BerthaChooseAction", 1);
    } else {
        // No valid actions — shuffle rest to bottom of deck
        BerthaPutRestOnBottom($player);
    }
}

$customDQHandlers["BerthaChooseAction"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        BerthaPutRestOnBottom($player);
        return;
    }
    // Move chosen card to hand, then activate it for free
    $chosenObj = GetZoneObject($lastDecision);
    $chosenCardID = $chosenObj->CardID;
    MZMove($player, $lastDecision, "myHand");
    $hand = &GetHand($player);
    $handIdx = count($hand) - 1;
    // Remove remaining TempZone to bottom of deck first
    BerthaPutRestOnBottom($player);
    // Activate the card for free (ignoreCost = true)
    ActivateCard($player, "myHand-" . $handIdx, true);
};

function BerthaPutRestOnBottom($player) {
    $remaining = ZoneSearch("myTempZone");
    foreach($remaining as $rmz) {
        MZMove($player, $rmz, "myDeck");
    }
}

// ============================================================================
// Liquid Amnesia (k0hliqs2hi): banish random cards from memory
// ============================================================================
function LiquidAmnesiaBanish($player, $memRef, $remaining) {
    if($remaining <= 0) return;
    $memCards = ZoneSearch($memRef);
    if(empty($memCards)) return;
    // Pick random card from memory
    $randomIdx = array_rand($memCards);
    $randomCard = $memCards[$randomIdx];
    // Convert to banish zone reference
    $banishRef = (strpos($memRef, "my") === 0) ? "myBanish" : "theirBanish";
    MZMove($player, $randomCard, $banishRef);
    $remaining--;
    if($remaining > 0) {
        LiquidAmnesiaBanish($player, $memRef, $remaining);
    }
}

// ============================================================================
// Tristan Package — Helper Functions
// ============================================================================

/**
 * Check if a player's champion is a Tristan for Tristan Bonus abilities.
 * Returns true if any Tristan card ID is in the champion's lineage.
 */
function IsTristanBonus($player) {
    $tristanIDs = ["K5luT8aRzc", "he6kd7hocc", "bjlwabipl6", "gt7lh9v221", "4upufooz13"];
    $lineage = GetChampionLineage($player);
    foreach($tristanIDs as $tid) {
        if(in_array($tid, $lineage)) return true;
    }
    return false;
}

/**
 * Find the mzID of a player's champion on the field.
 * @param int $player The player number.
 * @return string|null The mzID (e.g. "myField-0") or null if not found.
 */
function FindChampionMZ($player) {
    global $playerID;
    $zone = ($player == $playerID) ? "myField" : "theirField";
    $champions = ZoneSearch($zone, ["CHAMPION"]);
    return !empty($champions) ? $champions[0] : null;
}

/**
 * Add a preparation counter to a player's champion.
 * @param int $player The player whose champion gets the counter.
 * @return bool True if counter was added, false if no champion found.
 */
function AddPrepCounter($player, $amount = 1) {
    $champMZ = FindChampionMZ($player);
    if($champMZ === null) return false;
    AddCounters($player, $champMZ, "preparation", $amount);
    return true;
}

/**
 * Gain Agility N for this turn. Adds a global effect tracking the agility amount.
 * At the beginning of the end phase, the player returns N cards from memory to hand.
 * @param int $player The player gaining agility.
 * @param int $amount The agility value (e.g. 3).
 */
function GainAgility($player, $amount) {
    AddGlobalEffects($player, "AGILITY_" . $amount);
}

// ============================================================================
// Penumbral Waltz — prep counter removal chain
// ============================================================================

/**
 * Start the YESNO chain for removing prep counters for Penumbral Waltz.
 */
function PenumbralWaltzChooseX($player) {
    $champMZ = FindChampionMZ($player);
    if($champMZ === null) { PenumbralWaltzResolve($player); return; }
    $champObj = GetZoneObject($champMZ);
    if($champObj === null || GetPrepCounterCount($champObj) <= 0) { PenumbralWaltzResolve($player); return; }
    $x = intval(DecisionQueueController::GetVariable("penumbralWaltzX") ?? "0");
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Remove_a_preparation_counter?_(current_X=" . $x . ")");
    DecisionQueueController::AddDecision($player, "CUSTOM", "PenumbralWaltzCost", 1);
}

$customDQHandlers["PenumbralWaltzCost"] = function($player, $parts, $lastDecision) {
    $x = intval(DecisionQueueController::GetVariable("penumbralWaltzX") ?? "0");
    if($lastDecision === "YES") {
        $champMZ = FindChampionMZ($player);
        if($champMZ !== null) {
            RemoveCounters($player, $champMZ, "preparation", 1);
            $x++;
            DecisionQueueController::StoreVariable("penumbralWaltzX", strval($x));
        }
        // Check for more counters
        $champObj = ($champMZ !== null) ? GetZoneObject($champMZ) : null;
        if($champObj !== null && GetPrepCounterCount($champObj) > 0) {
            PenumbralWaltzChooseX($player);
            return;
        }
    }
    PenumbralWaltzResolve($player);
};

/**
 * Resolve Penumbral Waltz after X prep counters have been removed.
 * Prevent X+3 damage to champion. If Tristan Bonus and X >= 3, summon 2 Ominous Shadows.
 */
function PenumbralWaltzResolve($player) {
    $x = intval(DecisionQueueController::GetVariable("penumbralWaltzX") ?? "0");
    $champMZ = FindChampionMZ($player);
    if($champMZ !== null) {
        $preventAmount = $x + 3;
        $champObj = &GetZoneObject($champMZ);
        if($champObj !== null) {
            // Add prevention as per-card TurnEffect (PREVENT_CHAMP_N pattern)
            AddTurnEffect($champMZ, "PREVENT_CHAMP_" . $preventAmount);
        }
    }
    if($x >= 3 && IsTristanBonus($player)) {
        MZAddZone($player, "myField", "gveirpdm44");
        MZAddZone($player, "myField", "gveirpdm44");
    }
}

// ============================================================================
// Sadi, Blood Harvester — ActivateAbility handler
// ============================================================================

$customDQHandlers["SadiReturnToHand"] = function($player, $parts, $lastDecision) {
    $mzID = $parts[0] ?? null;
    if($mzID === null) return;
    $obj = GetZoneObject($mzID);
    if($obj === null || $obj->removed || $obj->CardID !== "ugly4wiffe") return;
    MZMove($player, $mzID, "myHand");
    AddPrepCounter($player);
};

// ============================================================================
// Gloamspire Mantle — Summon Ominous Shadow after payment
// ============================================================================

$customDQHandlers["GloamspireMantleSummon"] = function($player, $parts, $lastDecision) {
    MZAddZone($player, "myField", "gveirpdm44");
};

// ============================================================================
// Extricating Touch (4a8hl5dben) — choose player → hand/memory → pick card → discard
// ============================================================================

$customDQHandlers["ExtricatingTouchZoneChoice"] = function($player, $parts, $lastDecision) {
    // $lastDecision = chosen champion mzID (the target player)
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $targetObj = GetZoneObject($lastDecision);
    if($targetObj === null) return;
    $targetPlayer = $targetObj->Controller;
    DecisionQueueController::StoreVariable("extricatingTarget", strval($targetPlayer));
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Reveal_hand?_(No=Reveal_memory)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ExtricatingTouchReveal", 1);
};

$customDQHandlers["ExtricatingTouchReveal"] = function($player, $parts, $lastDecision) {
    $targetPlayer = intval(DecisionQueueController::GetVariable("extricatingTarget"));
    global $playerID;
    if($lastDecision === "YES") {
        // Reveal hand
        $handZone = $targetPlayer == $playerID ? "myHand" : "theirHand";
        $hand = GetZone($handZone);
        $handMZs = [];
        for($i = 0; $i < count($hand); ++$i) {
            if(!$hand[$i]->removed) $handMZs[] = $handZone . "-" . $i;
        }
        if(empty($handMZs)) return;
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $handMZs), 1, tooltip:"Choose_card_to_discard");
        DecisionQueueController::AddDecision($player, "CUSTOM", "ExtricatingTouchDiscard", 1);
    } else {
        // Reveal memory
        $memZone = $targetPlayer == $playerID ? "myMemory" : "theirMemory";
        $mem = GetZone($memZone);
        $memMZs = [];
        for($i = 0; $i < count($mem); ++$i) {
            if(!$mem[$i]->removed) $memMZs[] = $memZone . "-" . $i;
        }
        if(empty($memMZs)) return;
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $memMZs), 1, tooltip:"Choose_card_to_discard");
        DecisionQueueController::AddDecision($player, "CUSTOM", "ExtricatingTouchDiscard", 1);
    }
};

$customDQHandlers["ExtricatingTouchDiscard"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $targetPlayer = intval(DecisionQueueController::GetVariable("extricatingTarget"));
    DoDiscardCard($targetPlayer, $lastDecision);
};

// ============================================================================
// Zhou Yu, Enlightened Sage (55d7vo62fc) — [CB] On Enter: materialize Book/Scripture from material deck
// ============================================================================

$customDQHandlers["ZhouYuMaterialize"] = function($player, $parts, $lastDecision) {
    global $customDQHandlers;
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $customDQHandlers["MATERIALIZE"]($player, [], $lastDecision);
};

// ============================================================================
// Red Hare (5du8f077ua) — On Attack discard-to-draw flow
// ============================================================================

$customDQHandlers["RedHareDiscardDraw"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $hand = GetZone("myHand");
    if(empty($hand)) return;
    $handMZs = [];
    for($i = 0; $i < count($hand); ++$i) {
        if(!$hand[$i]->removed) $handMZs[] = "myHand-" . $i;
    }
    if(empty($handMZs)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $handMZs), 1, tooltip:"Choose_card_to_discard");
    DecisionQueueController::AddDecision($player, "CUSTOM", "RedHareDiscardFinish", 1);
};

$customDQHandlers["RedHareDiscardFinish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    DoDiscardCard($player, $lastDecision);
    Draw($player, 1);
};

// ============================================================================
// Scorching Imperilment (aj7pz79wsp): discard → draw handler
// ============================================================================
$customDQHandlers["ScorchingImperilmentDiscard"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    DoDiscardCard($player, $lastDecision);
    Draw($player, 1);
};

// ============================================================================
// Wither Upkeep — at start of main phase, each object with wither counters:
// controller sacrifices it unless they pay (1) per wither counter, then remove.
// ============================================================================
function WitherUpkeep($player) {
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    for($i = 0; $i < count($field); $i++) {
        if($field[$i]->removed) continue;
        $witherCount = GetCounterCount($field[$i], "wither");
        if($witherCount <= 0) continue;
        // Skip champions (wither only affects non-champion objects per rules)
        if(PropertyContains(EffectiveCardType($field[$i]), "CHAMPION")) continue;
        $handCount = count(GetZone("myHand"));
        $mz = $zone . "-" . $i;
        if($handCount >= $witherCount) {
            DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
                tooltip:"Pay_" . $witherCount . "_to_keep_withered_object?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "WitherUpkeepProcess|" . $mz . "|" . $witherCount, 1);
        } else {
            // Can't afford — auto-sacrifice
            DoSacrificeFighter($player, $mz);
            DecisionQueueController::CleanupRemovedCards();
        }
    }
}

$customDQHandlers["WitherUpkeepProcess"] = function($player, $params, $lastDecision) {
    $mz = $params[0];
    $witherCount = intval($params[1]);
    if($lastDecision === "YES") {
        // Pay reserve cost (hand→memory) for each wither counter
        for($i = 0; $i < $witherCount; $i++) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
        // After payment, remove wither counters
        DecisionQueueController::AddDecision($player, "CUSTOM", "WitherUpkeepClear|" . $mz, 1);
    } else {
        // Sacrifice the object
        $obj = GetZoneObject($mz);
        if($obj !== null && !$obj->removed) {
            DoSacrificeFighter($player, $mz);
            DecisionQueueController::CleanupRemovedCards();
        }
    }
};

$customDQHandlers["WitherUpkeepClear"] = function($player, $params, $lastDecision) {
    $mz = $params[0];
    $obj = GetZoneObject($mz);
    if($obj !== null && !$obj->removed) {
        ClearCounters($player, $mz, "wither");
    }
};

// ============================================================================
// Diao Chan, Idyll Corsage (d7l6i5thdy): banish destroyed opponent's non-token
// object and summon a Flowerbud token for that opponent
// ============================================================================
$customDQHandlers["DiaoChanIdyllBanish"] = function($player, $params, $lastDecision) {
    if($lastDecision !== "YES") return;
    $cardID = $params[0];
    $destroyedController = intval($params[1]);
    global $playerID;
    // Find the card in the opponent's graveyard (most common destination) and banish it
    $oppGY = $destroyedController == $playerID ? "myGraveyard" : "theirGraveyard";
    $oppBanish = $destroyedController == $playerID ? "myBanish" : "theirBanish";
    $gy = GetZone($oppGY);
    $found = false;
    for($i = count($gy) - 1; $i >= 0; $i--) {
        if($gy[$i]->CardID === $cardID) {
            MZMove($player, $oppGY . "-" . $i, $oppBanish);
            $found = true;
            break;
        }
    }
    if(!$found) {
        // Check material deck (renewable cards go there)
        $oppMat = $destroyedController == $playerID ? "myMaterial" : "theirMaterial";
        $mat = GetZone($oppMat);
        for($i = count($mat) - 1; $i >= 0; $i--) {
            if($mat[$i]->CardID === $cardID) {
                MZMove($player, $oppMat . "-" . $i, $oppBanish);
                $found = true;
                break;
            }
        }
    }
    // Summon a Flowerbud token for the opponent (the destroyed card's controller)
    MZAddZone($destroyedController, "myField", "yn78t73w1p"); // Flowerbud token
};

// ============================================================================
// Diao Chan, Idyll Corsage (d7l6i5thdy): On Enter — choose any amount of
// non-champion objects loop: add wither counter, repeat until pass
// ============================================================================
$customDQHandlers["DiaoChanIdyllWitherLoop"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    AddCounters($player, $lastDecision, "wither", 1);
    // Offer to choose another (exclude already-chosen — filter still-valid targets)
    $allObjects = array_merge(
        ZoneSearch("myField", ["ALLY", "REGALIA"]),
        ZoneSearch("myField", cardSubtypes: ["WEAPON"]),
        ZoneSearch("theirField", ["ALLY", "REGALIA"]),
        ZoneSearch("theirField", cardSubtypes: ["WEAPON"])
    );
    $allObjects = array_filter($allObjects, function($mz) {
        $obj = GetZoneObject($mz);
        return $obj !== null && !PropertyContains(EffectiveCardType($obj), "CHAMPION");
    });
    $allObjects = array_values($allObjects);
    if(empty($allObjects)) return;
    $choices = implode("&", $allObjects);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $choices, 1,
        tooltip:"Choose_another_object_to_put_wither_counter_on");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DiaoChanIdyllWitherLoop", 1);
};

// ============================================================================
// Enthralling Chime (mhc5a9jpi6): gain control of target ally with 3+ wither
// ============================================================================
$customDQHandlers["EnthrallingChimeGainControl"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $obj = &GetZoneObject($lastDecision);
    if($obj === null || $obj->removed) return;
    $obj->Controller = $player;
};

// ============================================================================
// Bloom: Autumn's Fall (pebu7agtcd): choose Acerbica or Washuru for each
// sacrificed Flowerbud, then summon for the opponent
// ============================================================================
function BloomAutumnChooseTokens($player, $opponent, $remaining) {
    if($remaining <= 0) return;
    // YESNO: "Summon_Acerbica?" — YES = Acerbica, NO = Washuru
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
        tooltip:"Summon_Acerbica_for_opponent?_(NO=Washuru)_(" . $remaining . "_remaining)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "BloomAutumnToken|" . $opponent . "|" . $remaining, 1);
}

$customDQHandlers["BloomAutumnToken"] = function($player, $params, $lastDecision) {
    $opponent = intval($params[0]);
    $remaining = intval($params[1]);
    if($lastDecision === "YES") {
        MZAddZone($opponent, "myField", "7ax4ywyv19"); // Acerbica
    } else {
        MZAddZone($opponent, "myField", "k5iv040vcq"); // Washuru
    }
    BloomAutumnChooseTokens($player, $opponent, $remaining - 1);
};

// ============================================================================
// Ameliorating Mantra (b3sm5e7pan): remove wither counters from up to 2 targets
// ============================================================================
function FindWitherTargets($exclude = null) {
    $targets = [];
    foreach(["myField", "theirField"] as $zoneName) {
        $field = GetZone($zoneName);
        for($i = 0; $i < count($field); $i++) {
            if(!$field[$i]->removed && GetCounterCount($field[$i], "wither") > 0) {
                $mz = $zoneName . "-" . $i;
                if($mz !== $exclude) $targets[] = $mz;
            }
        }
    }
    return $targets;
}

function AmelioratingMantraContinue($player, $firstTarget) {
    if($firstTarget !== "-" && $firstTarget !== "") {
        RemoveCounters($player, $firstTarget, "wither", 2);
    }
    $targets = FindWitherTargets($firstTarget);
    $targets = FilterSpellshroudTargets($targets);
    if(empty($targets)) return;
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $targets), 1,
        tooltip:"Remove_2_wither_from_another_target?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "AmelioratingMantraSecond", 1);
}

$customDQHandlers["AmelioratingMantraSecond"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    RemoveCounters($player, $lastDecision, "wither", 2);
};

// ============================================================================
// Stabilizing Capacitance (c4sy8u49sk): memory → deck bottom loop + draw into memory
// ============================================================================
function StabilizingCapacitanceLoop($player, $count) {
    $memCards = ZoneMZIndices("myMemory");
    if(empty($memCards) || $memCards === "") {
        StabilizingCapacitanceFinish($player, $count);
        return;
    }
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $memCards, 1,
        tooltip:"Put_a_card_from_memory_on_bottom_of_deck?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "StabilizingCapacitancePick|$count", 1);
}

function StabilizingCapacitanceFinish($player, $count) {
    if($count > 0) {
        DrawIntoMemory($player, $count);
    }
    // [Class Bonus] [Level 7+] Draw a card
    if(IsClassBonusActive($player, ["MAGE"]) && PlayerLevel($player) >= 7) {
        Draw($player, 1);
    }
}

$customDQHandlers["StabilizingCapacitancePick"] = function($player, $params, $lastDecision) {
    $count = intval($params[0]);
    if($lastDecision === "-" || $lastDecision === "") {
        StabilizingCapacitanceFinish($player, $count);
        return;
    }
    MZMove($player, $lastDecision, "myDeck");
    StabilizingCapacitanceLoop($player, $count + 1);
};

// ============================================================================
// Sylph's Envelopment (c7d7xdy3y9): banish ally, return to field rested, buff if phantasia
// ============================================================================
function SylphEnvelopmentExecute($player, $target) {
    $obj = GetZoneObject($target);
    if($obj === null) return;
    $cardID = $obj->CardID;
    $isPhantasia = PropertyContains(EffectiveCardType($obj), "PHANTASIA");
    OnLeaveField($player, $target);
    MZMove($player, $target, "myBanish");
    // Find the banished card and return it to field
    $banish = GetZone("myBanish");
    for($i = count($banish) - 1; $i >= 0; --$i) {
        if(!$banish[$i]->removed && $banish[$i]->CardID === $cardID) {
            $returnedObj = MZMove($player, "myBanish-" . $i, "myField");
            $returnedObj->Status = 1; // Rested
            if($isPhantasia) {
                $field = &GetField($player);
                AddCounters($player, "myField-" . (count($field) - 1), "buff", 1);
            }
            break;
        }
    }
}

?>