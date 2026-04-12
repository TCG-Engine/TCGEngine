<?php

$debugMode = true;
$customDQHandlers = [];
$_computingPowerLifeSwap = false;

include_once __DIR__ . '/CardLogic.php';
include_once __DIR__ . '/CombatLogic.php';
include_once __DIR__ . '/OpportunityLogic.php';
include_once __DIR__ . '/MaterializeLogic.php';
include_once __DIR__ . '/PotionLogic.php';
include_once __DIR__ . '/CardDQHandlers.php';

// --- Additional Activation Costs Registry ---
// Cards that offer an optional extra reserve cost at activation time (Grand Archive rule 1.3).
// Each entry maps a cardID to:
//   'prompt'       => string shown in the YesNo dialog
//   'extraReserve' => int, number of extra hand→memory payments
//   'condition'    => callable($player) that returns true if the option should be offered
$additionalActivationCosts = [];

// --- Imbue Cards Registry ---
// Supported config forms:
//   int => default Imbue N (match this card's element identity)
//   ['threshold' => N, 'matcher' => 'card_element'|'element'|'advanced', 'element' => 'FIRE']
//   [ <option1>, <option2>, ... ] => multiple Imbue characteristics; player chooses one
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
$Imbue_Cards["lflzwiiewz"] = 2; // Cataleptic Constellation (ASTRA)
$Imbue_Cards["EPy8OUmPxa"] = 2; // Stardust Oracle (ASTRA) - Imbue 2
$Imbue_Cards["Byx6iokcT4"] = 3; // Topsy Decree (NORM) - Imbue 3
$Imbue_Cards["jH3ZOavGPR"] = 2; // Crystalvein Awakening (WIND) - Imbue 2
$Imbue_Cards["a3pmmloejo"] = 2; // Blessed Clergy (WIND) - Imbue 2
$Imbue_Cards["xpnjvt9y59"] = 2; // Cleansing Reunion (WIND) - Imbue 2
$Imbue_Cards["taug52u81v"] = 2; // Eternal Magistrate (WIND) - Imbue 2

function NormalizeImbueOption($cardID, $config) {
    if(is_int($config)) {
        return [
            'threshold' => $config,
            'matcher' => 'card_element',
            'element' => CardElement($cardID)
        ];
    }
    if(!is_array($config)) return null;
    $threshold = intval($config['threshold'] ?? 0);
    if($threshold <= 0) return null;
    $matcher = $config['matcher'] ?? 'card_element';
    $element = $config['element'] ?? null;
    if($matcher === 'card_element') {
        $element = CardElement($cardID);
    }
    return [
        'threshold' => $threshold,
        'matcher' => $matcher,
        'element' => $element
    ];
}

function GetCardImbueOptions($player, $cardID) {
    global $Imbue_Cards;
    $options = [];
    if(isset($Imbue_Cards[$cardID])) {
        $config = $Imbue_Cards[$cardID];
        $configs = is_array($config) && isset($config[0]) ? $config : [$config];
        foreach($configs as $rawOption) {
            $option = NormalizeImbueOption($cardID, $rawOption);
            if($option !== null) $options[] = $option;
        }
    }
    if(PropertyContains(CardType($cardID), "ALLY") && CardElement($cardID) === "WIND") {
        $myField = GetZone("myField");
        foreach($myField as $fObj) {
            if(!$fObj->removed && $fObj->CardID === "lxnq80yu75" && !HasNoAbilities($fObj)) {
                $options[] = [
                    'threshold' => 2,
                    'matcher' => 'card_element',
                    'element' => CardElement($cardID)
                ];
                break;
            }
        }
    }

    // If multiple Imbue instances define the same characteristic, the lowest N wins.
    $deduped = [];
    foreach($options as $option) {
        $key = $option['matcher'] . "|" . ($option['element'] ?? '');
        if(!isset($deduped[$key]) || $option['threshold'] < $deduped[$key]['threshold']) {
            $deduped[$key] = $option;
        }
    }
    return array_values($deduped);
}

function GetPendingImbueCardID() {
    $effectStack = GetEffectStack();
    for($i = count($effectStack) - 1; $i >= 0; --$i) {
        if(!$effectStack[$i]->removed) {
            return $effectStack[$i]->CardID;
        }
    }
    return null;
}

function GetChosenImbueOption($player) {
    $cardID = GetPendingImbueCardID();
    if($cardID === null || $cardID === "") return null;
    $imbueOptions = GetCardImbueOptions($player, $cardID);
    if(empty($imbueOptions)) return null;
    if(count($imbueOptions) === 1) return $imbueOptions[0];

    $selectedIndex = intval(DecisionQueueController::GetVariable("imbueChoiceIndex") ?? "0");
    if(!isset($imbueOptions[$selectedIndex])) $selectedIndex = 0;
    return $imbueOptions[$selectedIndex];
}

function GetImbueOptionLabel($cardID, $option) {
    switch($option['matcher']) {
        case 'advanced':
            return "Advanced_element_cards";
        case 'element':
            return ($option['element'] ?? CardElement($cardID)) . "_element_cards";
        case 'card_element':
        default:
            return CardElement($cardID) . "_element_cards";
    }
}

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
$Kindle_Cards["hd0sxpu7cp"] = 3; // Intensified Pyre (FIRE) - Kindle 3
$Kindle_Cards["qzv380ujf5"] = 6; // Duchess, Six of Hearts (FIRE) - Kindle 6
$Kindle_Cards["OjOcXBiO0b"] = 7; // Tyrannical Denigration (EXALTED) - Kindle 7
$Kindle_Cards["p7FWS3DA4a"] = 2; // Molten Echo (FIRE) - Kindle 2
$Kindle_Cards["nduIoPhZr1"] = 7; // Seven of Hearts (FIRE) - Kindle 7
$Kindle_Cards["vrK16VZ2zU"] = 2; // Burning Aethercharge (FIRE) - Kindle 2
$Kindle_Cards["4ms1r3hjxp"] = 6; // Jianye, Dawn's Keep (FIRE) - Kindle 6
$Kindle_Cards["FnTT1G4OQg"] = 4; // Restoring Embers (FIRE) - Kindle 4
$Kindle_Cards["znk6g5o8ys"] = 3; // Dazzling Courtesan (FIRE) - Kindle 3

// --- Cardistry Cards Registry ---
// Maps cardID => base reserve cost for the Cardistry activated ability.
// Cardistry (N): costs (1) less per Suited object with different reserve costs. Activate only once.
$Cardistry_Cards = [];
$Cardistry_Cards["rufki4o41y"] = 2; // Two of Hearts
$Cardistry_Cards["e8ygl32jef"] = 2; // Two of Spades
$Cardistry_Cards["o09csnorqv"] = 3; // Three of Spades
$Cardistry_Cards["1db8hz4prm"] = 3; // Three of Hearts
$Cardistry_Cards["8bolq2y5qp"] = 4; // Four of Spades
$Cardistry_Cards["xgax8bbjqj"] = 4; // Four of Hearts
$Cardistry_Cards["i9hf5lhl5f"] = 5; // Five of Spades
$Cardistry_Cards["idq4ih00rq"] = 5; // Five of Hearts
$Cardistry_Cards["qzv380ujf5"] = 6; // Duchess, Six of Hearts
$Cardistry_Cards["tdRR5lQHMN"] = 6; // Six of Spades
$Cardistry_Cards["EIpkYYSP3s"] = 6; // Senaris, Six of Diamonds
$Cardistry_Cards["DKoSnhjX18"] = 7; // Chance, Seven of Spades
$Cardistry_Cards["nduIoPhZr1"] = 7; // Seven of Hearts
$Cardistry_Cards["0mf1ug6yfi"] = 10; // Wonderland's Reign

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
$lineageReleaseAbilities["daip7s9ztd"] = [ // Alice, Golden Queen
    'name' => 'LR: Shield Chessman allies',
    'effect' => function($player) {
        global $playerID;
        $zone = $player == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        for($i = 0; $i < count($field); $i++) {
            $obj = $field[$i];
            if(!$obj->removed && PropertyContains(EffectiveCardType($obj), "ALLY")
                && PropertyContains(EffectiveCardSubtypes($obj), "CHESSMAN")
                && isset($obj->Status) && $obj->Status == 2) {
                AddTurnEffect($zone . "-" . $i, "PREVENT_ALL_3");
            }
        }
    }
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

$lineageReleaseAbilities["o69ogocemo"] = [ // Ciel, Omenbringer
    'name' => 'LR: Activate an omen',
    'condition' => function($player) { return GetOmenCount($player) >= 6; },
    'effect' => function($player) {
        $omens = GetOmenMZIDs($player);
        if(empty($omens)) return;
        $omenStr = implode("&", $omens);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $omenStr, 1, "Choose_an_omen_to_activate");
        DecisionQueueController::AddDecision($player, "CUSTOM", "CielOmenbringerLR", 1);
    }
];

$lineageReleaseAbilities["FUCJA8IAMi"] = [ // Spirit of Purity
    'name' => 'LR: Banish from graveyards',
    'effect' => function($player) {
        // Each player banishes two cards from their graveyard
        SpiritOfPurityBanishLoop($player, $player, 2);
    }
];

$lineageReleaseAbilities["GiQxfpKTUC"] = [ // Alice, Distorted Queen
    'name' => 'LR: Recover 2+X',
    'effect' => function($player) {
        $lineage = GetChampionLineage($player);
        $x = max(0, count($lineage) - 1); // subcards only (exclude current card)
        RecoverChampion($player, 2 + $x);
    }
];

$lineageReleaseAbilities["59ipqa91r2"] = [ // Guo Jia, Blessed Scion
    'name' => 'LR: Negate (Fatestone/Fatebound)',
    'condition' => function($player) {
        $opponent = ($player == 1) ? 2 : 1;
        $targets = GetEffectStackActivationTargets($player, ['controller' => $opponent]);
        return !empty($targets);
    },
    'effect' => function($player) {
        $opponent = ($player == 1) ? 2 : 1;
        QueueNegateActivation($player, ['controller' => $opponent]);
    }
];

function SaveUndoVersion($playerID, $name = "") {
    $zones = Versions::GetSerializedZones();
    global $gRandomCounter;
    $zones .= "<v0>" . $gRandomCounter;

    MZClearZone($playerID, "myVersions");

    $namePrefix = (strlen($name) > 0 ? $name . '<vname>' : '');
    AddVersions($playerID, '0:' . $namePrefix . $zones);
}

function GetStartingChampionChoices($player) {
    $material = GetMaterial($player);
    $levelZeroChampions = [];
    $fallbackChampions = [];
    for($i = 0; $i < count($material); ++$i) {
        $obj = $material[$i];
        if($obj->removed || !PropertyContains(CardType($obj->CardID), "CHAMPION")) continue;
        $mzID = "myMaterial-" . $i;
        $fallbackChampions[] = $mzID;
        if(intval(CardLevel($obj->CardID)) === 0) {
            $levelZeroChampions[] = $mzID;
        }
    }
    return !empty($levelZeroChampions) ? $levelZeroChampions : $fallbackChampions;
}

function QueuePregameStartingChampionChoice($player, $nextPlayer = null) {
    $choices = GetStartingChampionChoices($player);
    if(empty($choices)) return false;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $choices), 1, "Reveal_your_starting_Lv_0_champion");
    $handlerParam = "PREGAME_CHOOSE_STARTING_CHAMPION";
    if($nextPlayer !== null) $handlerParam .= "|" . $nextPlayer;
    DecisionQueueController::AddDecision($player, "CUSTOM", $handlerParam, 1);
    return true;
}

function QueuePregameStartingChampionSetup() {
    $firstPlayer = intval(GetFirstPlayer());
    $secondPlayer = $firstPlayer == 1 ? 2 : 1;
    if(FindChampionMZ($firstPlayer) !== null && FindChampionMZ($secondPlayer) !== null) return false;
    return QueuePregameStartingChampionChoice($firstPlayer, $secondPlayer);
}

function PlacePregameStartingChampion($player, $mzID) {
    $obj = GetZoneObject($mzID);
    if($obj === null || $obj->removed || !PropertyContains(CardType($obj->CardID), "CHAMPION")) return null;

    DecisionQueueController::StoreVariable("SuppressNextEnter", "YES");
    $newObj = MZMove($player, $mzID, "myField");
    if($newObj === null) {
        DecisionQueueController::ClearVariable("SuppressNextEnter");
        return null;
    }

    $newObj->TurnEffects = array_values(array_filter(
        $newObj->TurnEffects ?? [],
        fn($effect) => $effect !== "ENTERED_THIS_TURN"
    ));
    return $newObj->GetMzID();
}

$customDQHandlers["PREGAME_CHOOSE_STARTING_CHAMPION"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;

    $startingChampionMZ = PlacePregameStartingChampion($player, $lastDecision);
    if($startingChampionMZ === null) return;
    DecisionQueueController::StoreVariable("PregameStartingChampion" . $player, $startingChampionMZ);

    $nextPlayer = isset($parts[0]) ? intval($parts[0]) : 0;
    if($nextPlayer > 0) {
        if(!QueuePregameStartingChampionChoice($nextPlayer)) {
            $firstPlayer = intval(GetFirstPlayer());
            $secondPlayer = $firstPlayer == 1 ? 2 : 1;
            DecisionQueueController::AddDecision($firstPlayer, "CUSTOM", "PREGAME_RESOLVE_STARTING_CHAMPION_ENTER|" . $secondPlayer, 250);
            $firstMzID = DecisionQueueController::GetVariable("PregameStartingChampion" . $firstPlayer);
            if($firstMzID !== null && $firstMzID !== "") {
                Enter($firstPlayer, $firstMzID);
            }
        }
        return;
    }

    $firstPlayer = intval(GetFirstPlayer());
    $secondPlayer = $firstPlayer == 1 ? 2 : 1;
    DecisionQueueController::AddDecision($firstPlayer, "CUSTOM", "PREGAME_RESOLVE_STARTING_CHAMPION_ENTER|" . $secondPlayer, 250);
    $firstMzID = DecisionQueueController::GetVariable("PregameStartingChampion" . $firstPlayer);
    if($firstMzID !== null && $firstMzID !== "") {
        Enter($firstPlayer, $firstMzID);
    }
};

$customDQHandlers["PREGAME_RESOLVE_STARTING_CHAMPION_ENTER"] = function($player, $parts, $lastDecision) {
    $resolvePlayer = isset($parts[0]) ? intval($parts[0]) : ($player == 1 ? 2 : 1);
    $storedMzID = DecisionQueueController::GetVariable("PregameStartingChampion" . $resolvePlayer);
    if($storedMzID === null || $storedMzID === "") {
        DecisionQueueController::AddDecision($player, "CUSTOM", "PREGAME_FINISH_STARTING_CHAMPIONS", 250);
        return;
    }

    DecisionQueueController::AddDecision($resolvePlayer, "CUSTOM", "PREGAME_FINISH_STARTING_CHAMPIONS", 250);
    Enter($resolvePlayer, $storedMzID);
};

$customDQHandlers["PREGAME_FINISH_STARTING_CHAMPIONS"] = function($player, $parts, $lastDecision) {
    DecisionQueueController::ClearVariable("PregameStartingChampion1");
    DecisionQueueController::ClearVariable("PregameStartingChampion2");
    SetMacroTurnIndex('{}');
};

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
                if(function_exists("CanActivateCard") && !CanActivateCard($playerID, $actionCard, false)) break;
                SaveUndoVersion($playerID);
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
            if($currentPhase == "MAIN" && $playerID == $turnPlayer) {
                SaveUndoVersion($playerID);
            }
            // Phantasmagoria: Non-Specter cards in your graveyard lose all abilities
            if($currentPhase == "MAIN" && $playerID == $turnPlayer) {
                $gyObj = GetZoneObject($actionCard);
                if($gyObj !== null && !$gyObj->removed && IsPhantasmagoriaGYSuppressed($playerID, $gyObj->CardID)) {
                    break;
                }
            }
            // Rosewinged Hollow (6S1LLrBfBU): [Alice Bonus][Element Bonus] (2), Banish from GY → haunt counter + optional +2 POWER
            if($currentPhase == "MAIN" && $playerID == $turnPlayer) {
                $gyObj = GetZoneObject($actionCard);
                if($gyObj !== null && !$gyObj->removed && $gyObj->CardID === "6S1LLrBfBU") {
                    if(IsAliceBonusActive($playerID) && IsElementBonusActive($playerID, "6S1LLrBfBU")
                        && HasPhantasmagoria($playerID)) {
                        $hand = &GetHand($playerID);
                        if(count($hand) >= 2) {
                            MZMove($playerID, $actionCard, "myBanish");
                            DecisionQueueController::CleanupRemovedCards();
                            DecisionQueueController::AddDecision($playerID, "CUSTOM", "ReserveCard", 100);
                            DecisionQueueController::AddDecision($playerID, "CUSTOM", "ReserveCard", 100);
                            DecisionQueueController::AddDecision($playerID, "CUSTOM", "EffectStackOpportunity", 100);
                            DecisionQueueController::AddDecision($playerID, "CUSTOM", "RosewingedHollowGY_Apply", 1);
                            return "PLAY";
                        }
                    }
                }
            }
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
                } else if(isset($config['extraCostHandler']) && $config['extraCostHandler'] === 'EphemerateDiscard') {
                    DecisionQueueController::StoreVariable("ephemerateCostOverride", "$cost");
                    DecisionQueueController::StoreVariable("ephemerateHandMZ", "myHand-" . $handIdx);
                    // Must discard a card from hand (the ephemerated card is already in hand)
                    $hand = &GetHand($playerID);
                    $handCards = [];
                    for($hi = 0; $hi < count($hand); ++$hi) {
                        if(!$hand[$hi]->removed && $hi !== $handIdx) {
                            $handCards[] = "myHand-" . $hi;
                        }
                    }
                    if(!empty($handCards)) {
                        $handStr = implode("&", $handCards);
                        DecisionQueueController::AddDecision($playerID, "MZCHOOSE", $handStr, 1, tooltip:"Discard_a_card_(Ephemerate_cost)");
                        DecisionQueueController::AddDecision($playerID, "CUSTOM", "EphemerateDiscardProcess", 1);
                    }
                } else if(isset($config['extraCostHandler']) && $config['extraCostHandler'] === 'EphemerateBanishOtherGY') {
                    DecisionQueueController::StoreVariable("ephemerateCostOverride", "$cost");
                    DecisionQueueController::StoreVariable("ephemerateHandMZ", "myHand-" . $handIdx);
                    // Must banish another card from graveyard
                    $gy = GetZone("myGraveyard");
                    $otherGY = [];
                    for($gi = 0; $gi < count($gy); ++$gi) {
                        if(!$gy[$gi]->removed) {
                            $otherGY[] = "myGraveyard-" . $gi;
                        }
                    }
                    if(!empty($otherGY)) {
                        $gyStr = implode("&", $otherGY);
                        DecisionQueueController::AddDecision($playerID, "MZCHOOSE", $gyStr, 1, tooltip:"Banish_a_card_from_GY_(Ephemerate_cost)");
                        DecisionQueueController::AddDecision($playerID, "CUSTOM", "EphemerateBanishOtherGYProcess", 1);
                    }
                } else {
                    ActivateCard($playerID, "myHand-" . $handIdx, false);
                }
                return "PLAY";
            }
            break;
        case "myBanish":
            if($currentPhase == "MAIN" && $playerID == $turnPlayer) {
                SaveUndoVersion($playerID);
            }
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
            // Seiryuu, Azure Dragon (tf5f2n38g0): activate Arcane Blast from banishment (generated by On Attack)
            if($playerID == $turnPlayer) {
                $bObj = GetZoneObject($actionCard);
                if($bObj !== null && !$bObj->removed && in_array("SEIRYUU_BANISHED", $bObj->TurnEffects ?? [])) {
                    SaveUndoVersion($playerID);
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
            // Ignition Draw (RhSPMn8Lix): activate Aethercharge from banishment this turn
            if($currentPhase == "MAIN" && $playerID == $turnPlayer) {
                $bObj = GetZoneObject($actionCard);
                if($bObj !== null && !$bObj->removed && isset($bObj->Counters['_ignitionDraw'])) {
                    unset($bObj->Counters['_ignitionDraw']);
                    $handObj = MZMove($playerID, $actionCard, "myHand");
                    $hand = &GetHand($playerID);
                    $handIdx = count($hand) - 1;
                    ActivateCard($playerID, "myHand-" . $handIdx, false);
                    return "PLAY";
                }
            }
            // Desperate Cavalier (slmer06rku): tagged banished cards may be activated for 2 self-damage.
            if($currentPhase == "MAIN" && $playerID == $turnPlayer) {
                $bObj = GetZoneObject($actionCard);
                if($bObj !== null && !$bObj->removed && isset($bObj->Counters['_desperateCavalier'])) {
                    unset($bObj->Counters['_desperateCavalier']);
                    MZMove($playerID, $actionCard, "myHand");
                    $champMZ = FindChampionMZ($playerID);
                    if($champMZ !== null) {
                        DealUnpreventableDamage($playerID, $actionCard, $champMZ, 2);
                    }
                    $hand = &GetHand($playerID);
                    $handIdx = count($hand) - 1;
                    ActivateCard($playerID, "myHand-" . $handIdx, false);
                    return "PLAY";
                }
            }
            // Recursive Confidant (KfC8fwcF2T): activate tagged Warrior attack from banishment
            if($currentPhase == "MAIN" && $playerID == $turnPlayer) {
                $bObj = GetZoneObject($actionCard);
                if($bObj !== null && !$bObj->removed && isset($bObj->Counters['_recursiveConfidant'])) {
                    unset($bObj->Counters['_recursiveConfidant']);
                    $handObj = MZMove($playerID, $actionCard, "myHand");
                    $hand = &GetHand($playerID);
                    $handIdx = count($hand) - 1;
                    ActivateCard($playerID, "myHand-" . $handIdx, false);
                    return "PLAY";
                }
            }
            // Ashen Riffle (fjpimrl974): activate tagged Suited non-action cards from banishment
            if($currentPhase == "MAIN" && $playerID == $turnPlayer) {
                $bObj = GetZoneObject($actionCard);
                if($bObj !== null && !$bObj->removed && isset($bObj->Counters['_ashenRiffle'])) {
                    $handObj = MZMove($playerID, $actionCard, "myHand");
                    $hand = &GetHand($playerID);
                    $handIdx = count($hand) - 1;
                    ActivateCard($playerID, "myHand-" . $handIdx, false);
                    return "PLAY";
                }
            }
            break;
        case "myMaterial":
            if($currentPhase == "MAIN" && $playerID == $turnPlayer) {
                SaveUndoVersion($playerID);
            }
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
            // Polaris, Twinkling Cauldron (41t71u4bzz): [Arisanna Bonus] may activate from material deck
            if($currentPhase == "MAIN" && $playerID == $turnPlayer) {
                $mObj = GetZoneObject($actionCard);
                if($mObj !== null && !$mObj->removed && $mObj->CardID === "41t71u4bzz"
                    && IsArisannaBonusActive($playerID)) {
                    DecisionQueueController::StoreVariable("polarisFromMaterial", "YES");
                    $handObj = MZMove($playerID, $actionCard, "myHand");
                    $hand = &GetHand($playerID);
                    $handIdx = count($hand) - 1;
                    ActivateCard($playerID, "myHand-" . $handIdx, false);
                    return "PLAY";
                }
            }
            // Lost Providence (DNbIpzVgde): may activate from material deck → enters field ephemeral
            if($currentPhase == "MAIN" && $playerID == $turnPlayer) {
                $mObj = GetZoneObject($actionCard);
                if($mObj !== null && !$mObj->removed && $mObj->CardID === "DNbIpzVgde") {
                    DecisionQueueController::StoreVariable("lostProvidenceFromMaterial", "YES");
                    $handObj = MZMove($playerID, $actionCard, "myHand");
                    $hand = &GetHand($playerID);
                    $handIdx = count($hand) - 1;
                    ActivateCard($playerID, "myHand-" . $handIdx, false);
                    return "PLAY";
                }
            }
            // Gaia's Blessing (ymhDYTPfi1): [Element Bonus] banish 4 Animal/Beast GY allies → activate from material free
            if($currentPhase == "MAIN" && $playerID == $turnPlayer) {
                $mObj = GetZoneObject($actionCard);
                if($mObj !== null && !$mObj->removed && $mObj->CardID === "ymhDYTPfi1"
                    && IsElementBonusActive($playerID, "ymhDYTPfi1")) {
                    $animalBeastGY = ZoneSearch("myGraveyard", ["ALLY"], cardSubtypes: ["ANIMAL", "BEAST"]);
                    if(count($animalBeastGY) >= 4) {
                        SaveUndoVersion($playerID);
                        GaiasBlessingBanishLoop($playerID, 4, $actionCard);
                        return "PLAY";
                    }
                }
            }
            break;
        case "myDeck":
            // Gaia's Blessing (ymhDYTPfi1): activate Animal/Beast ally from top of deck while Gaia's Blessing is on field
            if($currentPhase == "MAIN" && $playerID == $turnPlayer) {
                $cardArr = explode("-", $actionCard);
                $deckIdx = intval($cardArr[1] ?? -1);
                if($deckIdx === 0) {
                    $deckObj = GetZoneObject($actionCard);
                    if($deckObj !== null && !$deckObj->removed) {
                        $gaiaOnField = false;
                        $myField = GetField($playerID);
                        foreach($myField as $gfObj) {
                            if(!$gfObj->removed && $gfObj->CardID === "ymhDYTPfi1" && !HasNoAbilities($gfObj)) {
                                $gaiaOnField = true;
                                break;
                            }
                        }
                        if($gaiaOnField) {
                            $deckCardType = CardType($deckObj->CardID);
                            $deckCardSubtypes = CardSubtypes($deckObj->CardID);
                            if(PropertyContains($deckCardType, "ALLY") &&
                               (PropertyContains($deckCardSubtypes, "ANIMAL") || PropertyContains($deckCardSubtypes, "BEAST"))) {
                                SaveUndoVersion($playerID);
                                $handObj = MZMove($playerID, $actionCard, "myHand");
                                $hand = &GetHand($playerID);
                                $handIdx = count($hand) - 1;
                                ActivateCard($playerID, "myHand-" . $handIdx, false);
                                return "PLAY";
                            }
                        }
                    }
                }
            }
            break;
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

    // Blessed Clergy (a3pmmloejo): restrict to 2 card plays during next turn
    if(GlobalEffectCount($player, "a3pmmloejo-restrict") > 0) {
        if(CardActivatedCallCount($player) >= 2) {
            return; // Already played 2 cards this turn
        }
    }

    // Invoke Dominance (PLljzdiMmq): can't activate non-ally cards this turn
    if(GlobalEffectCount($player, "PLljzdiMmq_NO_NONALLY") > 0) {
        $cardType = CardType($sourceObject->CardID);
        if(!PropertyContains($cardType, "ALLY")) {
            return;
        }
    }

    // 1.5 Ally Link pre-check: if the card has Ally Link, there must be at least
    // one ally on the field to link to. If not, the activation is illegal.
    global $AllyLink_Cards;
    $hasAllyLink = isset($AllyLink_Cards[$sourceObject->CardID]);
    if($hasAllyLink) {
        $allyTargets = ZoneSearch("myField", ["ALLY"]);
        if(empty($allyTargets)) return; // No valid Link target — block activation
    }

    // Nightmare Coil (3fe3c97s71): only while your champion is distant, and only during recollection.
    if($sourceObject->CardID === "3fe3c97s71") {
        if(GetCurrentPhase() !== "RECOLLECTION") return;
        $champMZ = FindChampionMZ($player);
        if($champMZ === null) return;
        $champObj = GetZoneObject($champMZ);
        if($champObj === null || !IsDistant($champObj)) return;
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

    // Command pre-check: COMMAND subtype attack needs a matching ally on field
    if(PropertyContains(CardSubtypes($sourceObject->CardID), "COMMAND")) {
        if(PropertyContains(CardSubtypes($sourceObject->CardID), "CHESSMAN")) {
            $commandAllies = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["CHESSMAN"]);
        } else if(PropertyContains(CardSubtypes($sourceObject->CardID), "AUTOMATON")) {
            $commandAllies = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["AUTOMATON"]);
        } else {
            $commandAllies = [];
        }
        if(empty($commandAllies)) return; // No ally to command — block activation
    }
    //1.1 Announcing Activation: First, the player announces the card they are activating and places it onto the effects stack.
    // Track the source zone so "whenever you activate from memory" triggers can check it in OnCardActivated.
    DecisionQueueController::StoreVariable("activationSourceZone", strtok($mzCard, "-"));
    $obj = MZMove($player, $mzCard, "EffectStack");
    $obj->Controller = $player;
    if($obj->CardID === "3fe3c97s71" && IsClassBonusActive($player, ["RANGER"])) {
        $obj->TurnEffects[] = "CANT_BE_NEGATED";
    }
    TrackEffectStackSourceZone("EffectStack-" . $obj->mzIndex, DecisionQueueController::GetVariable("activationSourceZone"));

    //TODO: 1.2 Checking Elements: Then, the game checks whether the player has the required elements enabled to activate the card. If not, the activation is illegal.
    // Prismatic Codex (czvy67nbin): "Once this turn, activate a card regardless of elemental alignment" — consume the bypass flag
    if(GlobalEffectCount($player, "PRISMATIC_CODEX_IGNORE_ELEMENT") > 0) {
        RemoveGlobalEffect($player, "PRISMATIC_CODEX_IGNORE_ELEMENT");
        // Element requirement is bypassed for this activation (once consumed, subsequent activations must satisfy element)
        // NOTE: When element checking (§1.2) is implemented, skip the element check when this flag was set.
    }

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

    // Unstable Fractal (2o82fwl22v): [Class Bonus] ability costs (3) reserve
    if($obj->CardID === "2o82fwl22v") $reserveCost = 3;

    $reserveCost = ApplyGeneratedReserveLikeCostModifiers($player, $obj, $reserveCost, "activate");

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

    // Expeditious Opening (w1wgpeifd0): consume fast-activation effect when any ally is activated
    if(GlobalEffectCount($player, "w1wgpeifd0") > 0 && PropertyContains(CardType($obj->CardID), "ALLY")) {
        RemoveGlobalEffect($player, "w1wgpeifd0");
    }
    // Bombastic Sprint (t4owmcva0f): consume fast-activation effect on the next Ranger action.
    if(GlobalEffectCount($player, "t4owmcva0f") > 0
        && PropertyContains(CardType($obj->CardID), "ACTION")
        && PropertyContains(CardClasses($obj->CardID), "RANGER")) {
        RemoveGlobalEffect($player, "t4owmcva0f");
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

    // Spectral Haunting (Dtr3jPRAFJ): [Alice Bonus] costs 4 less if targeting a Specter
    if($obj->CardID === "Dtr3jPRAFJ" && IsAliceBonusActive($player)) {
        $specterGY = ZoneSearch("myGraveyard", ["ALLY"], cardSubtypes: ["SPECTER"]);
        if(!empty($specterGY)) {
            $reserveCost = max(0, $reserveCost - 4);
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

    // Fortifying Aroma (doo4sk9q9e): costs 1 less to activate for each Herb you control
    if($obj->CardID === "doo4sk9q9e") {
        $reserveCost = max(0, $reserveCost - count(ZoneSearch("myField", cardSubtypes: ["HERB"])));
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

    // Dorumegian Foundry (CzwVavXMQU): [Class Bonus] costs 2 less per domain (up to 3)
    if($obj->CardID === "CzwVavXMQU" && IsClassBonusActive($player, ["GUARDIAN"])) {
        $domainCount = min(3, count(ZoneSearch("myField", ["DOMAIN"])));
        $reserveCost = max(0, $reserveCost - $domainCount * 2);
    }

    // Spirit Blade: Infusion (CgyJxpEgzk): costs 2 less if champion dealt combat damage this turn
    if($obj->CardID === "CgyJxpEgzk") {
        if(GlobalEffectCount($player, "CHAMP_DEALT_COMBAT_DMG") > 0) {
            $reserveCost = max(0, $reserveCost - 2);
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

    // Gloamspire Prowler (igpck2z4rs): costs 3 less with 2+ Curse cards in lineage
    if($obj->CardID === "igpck2z4rs" && CountCursesInLineage($player) >= 2) {
        $reserveCost = max(0, $reserveCost - 3);
    }

    // Curse Amplification (x9z2k2a5ig): [Diana Bonus] costs 3 less to activate
    if($obj->CardID === "x9z2k2a5ig" && IsDianaBonus($player)) {
        $reserveCost = max(0, $reserveCost - 3);
    }

    // Accursed Strength (j3fkza233s): [Diana Bonus] costs 1 less to activate
    if($obj->CardID === "j3fkza233s" && IsClassBonusActive($player, ["RANGER"])) {
        $reserveCost = max(0, $reserveCost - 1);
    }

    // Sidestep (voy5ttkk39): [Level 2+] costs 1 less
    if($obj->CardID === "voy5ttkk39" && PlayerLevel($player) >= 2) {
        $reserveCost = max(0, $reserveCost - 1);
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

    // Coy Bouclier (vo1qr9bkme): [Level 2+] costs 2 less
    if($obj->CardID === "vo1qr9bkme" && PlayerLevel($player) >= 2) {
        $reserveCost = max(0, $reserveCost - 2);
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

    // Burnished Obelith (mz1dJZExOk): next card controller activates costs 2 more (one-shot)
    if(GlobalEffectCount($player, "mz1dJZExOk_COST") > 0) {
        $reserveCost += 2;
        RemoveGlobalEffect($player, "mz1dJZExOk_COST");
    }

    // Consumption Ring (g8q7imka92): non-ally cards opponents activate cost (4) more until end of turn
    if(!PropertyContains($cardType, "ALLY")) {
        $opponent = ($player == 1) ? 2 : 1;
        if(GlobalEffectCount($opponent, "CONSUMPTION_RING_COST") > 0) {
            $reserveCost += 4;
        }
    }
    $opponent = ($player == 1) ? 2 : 1;
    if(GlobalEffectCount($opponent, "llQe0cg4xJ_COST") > 0) {
        $reserveCost += 1;
    }
    if(GlobalEffectCount($opponent, "CRYSTALLIZED_DESTINY_COST") > 0) {
        $reserveCost += 2;
    }
    // Jianyu, Fate's Premonition (qv0vn6tuow): chosen card name costs 2+X more to activate,
    // where X is the amount of phantasias Jianyu's controller controls.
    foreach(array_merge(GetZone("myField"), GetZone("theirField")) as $fieldObj) {
        if($fieldObj->removed || $fieldObj->CardID !== "qv0vn6tuow" || HasNoAbilities($fieldObj)) continue;
        foreach($fieldObj->TurnEffects ?? [] as $effect) {
            if(strpos($effect, "qv0vn6tuow-") !== 0) continue;
            if($obj->CardID !== substr($effect, strlen("qv0vn6tuow-"))) continue;
            global $playerID;
            $fieldZone = $fieldObj->Controller == $playerID ? "myField" : "theirField";
            $reserveCost += 2 + count(ZoneSearch($fieldZone, ["PHANTASIA"]));
            break 2;
        }
    }
    // Kingdom's Divide (qy34r8gffr): chosen card name costs 2 more to activate until beginning of caster's next turn.
    foreach(array_merge(GetZone("myField"), GetZone("theirField")) as $fieldObj) {
        if($fieldObj->removed || !PropertyContains(EffectiveCardType($fieldObj), "CHAMPION")) continue;
        foreach($fieldObj->TurnEffects ?? [] as $effect) {
            if(strpos($effect, "qy34r8gffr-") !== 0) continue;
            if($obj->CardID === substr($effect, strlen("qy34r8gffr-"))) {
                $reserveCost += 2;
                break 2;
            }
        }
    }

    // Enrage (wcfvrfw35s): [Damage 20+] costs 2 less
    if($obj->CardID === "wcfvrfw35s") {
        $champ = ZoneSearch("myField", ["CHAMPION"]);
        if(!empty($champ)) {
            $champObj = GetZoneObject($champ[0]);
            if($champObj !== null && $champObj->Damage >= 20) {
                $reserveCost = max(0, $reserveCost - 2);
            }
        }
    }

    // Ceasing Edict (4f3bi5lohu): costs 2 less while Shifting Currents face South
    if($obj->CardID === "4f3bi5lohu" && GetShiftingCurrents($player) === "SOUTH") {
        $reserveCost = max(0, $reserveCost - 2);
    }

    // Crystallized Destiny (l36wwe3d5c): costs 2 less while you control 2+ Fatestone/Fatebound objects
    if($obj->CardID === "l36wwe3d5c" && CountFatestoneOrFateboundObjects($player) >= 2) {
        $reserveCost = max(0, $reserveCost - 2);
    }
    // Vainglory Retribution (qtzsekkjn3): [Vanitas Bonus] [Level 2+] costs 2 less.
    if($obj->CardID === "qtzsekkjn3" && IsVanitasBonusActive($player) && PlayerLevel($player) >= 2) {
        $reserveCost = max(0, $reserveCost - 2);
    }
    // Jianyu, Fate's Premonition (qv0vn6tuow): [Class Bonus] costs 2 less.
    if($obj->CardID === "qv0vn6tuow" && IsClassBonusActive($player, ["CLERIC"])) {
        $reserveCost = max(0, $reserveCost - 2);
    }

    // Crackling Incineration (14bepZKPlK): [Sheen 6+] costs 2 less to activate
    if($obj->CardID === "14bepZKPlK" && GetSheenCount($player) >= 6) {
        $reserveCost = max(0, $reserveCost - 2);
    }

    // Merlin L2 (dPP9I4nVn0): activated ability costs X less (X = sheen count on Fractured Memories)
    if($obj->CardID === "dPP9I4nVn0") {
        $sheenDiscount = GetSheenCount($player);
        if($sheenDiscount > 0) {
            $reserveCost = max(0, $reserveCost - $sheenDiscount);
        }
    }

    // Leading Charge (WWnmb1Hjdo): [CB] costs 2 less if you control a unique Warrior ally
    if($obj->CardID === "WWnmb1Hjdo" && IsClassBonusActive($player, CardClasses("WWnmb1Hjdo"))) {
        global $playerID;
        $lcZone = $player == $playerID ? "myField" : "theirField";
        $hasUniqueWarrior = false;
        foreach(GetZone($lcZone) as $lcObj) {
            if(!$lcObj->removed && PropertyContains(EffectiveCardType($lcObj), "ALLY")
                && PropertyContains(EffectiveCardType($lcObj), "UNIQUE")
                && PropertyContains(EffectiveCardClasses($lcObj), "WARRIOR")) {
                $hasUniqueWarrior = true;
                break;
            }
        }
        if($hasUniqueWarrior) {
            $reserveCost = max(0, $reserveCost - 2);
        }
    }

    // Flamewreath Call (c8wwslgbvr): [Class Bonus] costs 3 less if you control a Beast ally
    if($obj->CardID === "c8wwslgbvr" && IsClassBonusActive($player, ["TAMER"])) {
        if(!empty(ZoneSearch("myField", ["ALLY"], cardSubtypes: ["BEAST"]))) {
            $reserveCost = max(0, $reserveCost - 3);
        }
    }

    // Shattered Hope (XOevViFTB3): [Merlin Bonus] costs 1 less to activate
    if($obj->CardID === "XOevViFTB3" && IsMerlinBonusActive($player)) {
        $reserveCost = max(0, $reserveCost - 1);
    }

    // Flickering Afterglow (tng0Gpe9mI): [Merlin Bonus] costs 1 less per sheen counter on Fractured Memories
    if($obj->CardID === "tng0Gpe9mI" && IsMerlinBonusActive($player)) {
        $sheenDiscount = GetSheenCount($player);
        if($sheenDiscount > 0) {
            $reserveCost = max(0, $reserveCost - $sheenDiscount);
        }
    }

    // Protect Her At All Costs (OzNHncAfFJ): [Merlin Bonus] costs 2 less
    if($obj->CardID === "OzNHncAfFJ" && IsMerlinBonusActive($player)) {
        $reserveCost = max(0, $reserveCost - 2);
    }

    // Castling (tFOpmUdi2W): costs 2 less if you control a Chessman Rook, 2 less if Chessman King
    if($obj->CardID === "tFOpmUdi2W") {
        if(!empty(ZoneSearch("myField", ["ALLY"], cardSubtypes: ["CHESSMAN", "ROOK"]))) {
            $reserveCost = max(0, $reserveCost - 2);
        }
        if(!empty(ZoneSearch("myField", ["ALLY"], cardSubtypes: ["CHESSMAN", "KING"]))) {
            $reserveCost = max(0, $reserveCost - 2);
        }
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

    // Gearstride Gloves (lcb6jhxctx): next Reaction card costs 1 less this turn
    if(GlobalEffectCount($player, "lcb6jhxctx_REACTION_DISCOUNT") > 0) {
        if(PropertyContains(CardSubtypes($obj->CardID), "REACTION")) {
            $reserveCost = max(0, $reserveCost - 1);
            RemoveGlobalEffect($player, "lcb6jhxctx_REACTION_DISCOUNT");
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
    if(!AreCurseLineageAbilitiesSuppressed($player) && ChampionHasInLineage($player, "wbsmks4etk") && CardActivatedCallCount($player) == 0) {
        $reserveCost += 1;
    }

    // Waited Accord (xF9phlSAkE): the first advanced element card each player activates each
    // turn costs 2 more for each active Waited Accord on the field.
    if(IsAdvancedElementCard($obj->CardID) && AdvancedElementActivatedCount($player) == 0) {
        foreach(array_merge(GetField(1), GetField(2)) as $fieldObj) {
            if($fieldObj->removed || $fieldObj->CardID !== "xF9phlSAkE" || HasNoAbilities($fieldObj)) continue;
            if(intval($fieldObj->Counters["waitedAccordActive"] ?? 0) > 0) {
                $reserveCost += 2;
            }
        }
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

    // Veteran Aerotheurge (fta5isdgrk): The first Aethercharge card you activate each turn costs 1 less
    if(PropertyContains(CardSubtypes($obj->CardID), "AETHERCHARGE") && AetherchargeActivatedCount($player) == 0) {
        $myField = GetZone("myField");
        foreach($myField as $fObj) {
            if(!$fObj->removed && $fObj->CardID === "fta5isdgrk" && !HasNoAbilities($fObj)) {
                $reserveCost = max(0, $reserveCost - 1);
                break;
            }
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

    // Soutirer Vortex (du4df43ci2): 3+ omens with different reserve costs -> costs 3 less
    if($obj->CardID === "du4df43ci2" && GetOmenDistinctCostCount($player) >= 3) {
        $reserveCost = max(0, $reserveCost - 3);
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

    // Hunt, Weiss King (Y6PZntlVDl): [Alice Bonus] costs 2 less per Pawn ally (up to 2 Pawns)
    if($obj->CardID === "Y6PZntlVDl" && IsAliceBonusActive($player)) {
        $pawnAllies = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["PAWN"]);
        $pawnDiscount = min(2, count($pawnAllies)) * 2;
        $reserveCost = max(0, $reserveCost - $pawnDiscount);
    }

    // Frostlorn Caress (4tqbok1g9w): [Diao Chan Bonus] costs 3 less to activate
    if($obj->CardID === "4tqbok1g9w" && IsDiaoChanBonus($player)) {
        $reserveCost = max(0, $reserveCost - 3);
    }

    // Frostnip Pirouette (x79cuuw5vo): [Diao Chan Bonus] costs 2 less to activate
    if($obj->CardID === "x79cuuw5vo" && IsDiaoChanBonus($player)) {
        $reserveCost = max(0, $reserveCost - 2);
    }

    // Briar's Spindle (9ooAGDhBj7): global effect — next Chessman card costs 2 less
    if(GlobalEffectCount($player, "9ooAGDhBj7_COST") > 0
        && PropertyContains(CardSubtypes($obj->CardID), "CHESSMAN")) {
        $reserveCost = max(0, $reserveCost - 2);
        RemoveGlobalEffect($player, "9ooAGDhBj7_COST");
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

    //1.3 Declaring Costs — Shield Fragmentation (CHU96qWwaS): mandatory sacrifice of a Shield item
    if($obj->CardID === "CHU96qWwaS") {
        $shields = ZoneSearch("myField", ["ITEM"], cardSubtypes: ["SHIELD"]);
        if(!empty($shields)) {
            $shieldChoices = implode("&", $shields);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $shieldChoices, 100, tooltip:"Sacrifice_a_Shield");
            DecisionQueueController::AddDecision($player, "CUSTOM", "ShieldFragmentSacrifice", 100);
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

    //1.3 Declaring Costs — Slime King (f0ymeslfpw): banish 3 Slime allies with different elements from GY
    $hasSlimeKingCost = false;
    if($obj->CardID === "f0ymeslfpw") {
        $hasSlimeKingCost = true;
        $eligible = [];
        $gy = GetZone("myGraveyard");
        for($gi = 0; $gi < count($gy); ++$gi) {
            if($gy[$gi]->removed) continue;
            if(!PropertyContains(CardType($gy[$gi]->CardID), "ALLY")) continue;
            if(!PropertyContains(CardSubtypes($gy[$gi]->CardID), "SLIME")) continue;
            $eligible[] = "myGraveyard-" . $gi;
        }
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $eligible), 100, tooltip:"Banish_a_Slime_ally_with_a_new_element_(1_of_3)");
        DecisionQueueController::AddDecision($player, "CUSTOM", "SlimeKingCostBanish|3|$reserveCost|", 100);
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

    //1.3 Declaring Costs — Obscured Offering (S3ODMQ0V0o): banish 2 from material deck
    if($obj->CardID === "S3ODMQ0V0o") {
        $mat = ZoneSearch("myMaterial");
        if(count($mat) >= 2) {
            $matStr = implode("&", $mat);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $matStr, 100, tooltip:"Banish_from_material_(1_of_2)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "ObscuredOfferingBanishMat|1", 100);
        }
    }

    //1.3 Declaring Costs — Dusklight Communion (5upufyoz23): banish astra/umbra from material deck
    if($obj->CardID === "5upufyoz23") {
        $mat = ZoneSearch("myMaterial", cardElements: ["ASTRA", "UMBRA"]);
        if(!empty($mat)) {
            $matStr = implode("&", $mat);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $matStr, 100, tooltip:"Banish_an_astra_or_umbra_card_from_material_deck");
            DecisionQueueController::AddDecision($player, "CUSTOM", "DusklightCommunionCost", 100);
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

    //1.3 Declaring Costs — Decompose (3JWk1jxX5u): mandatory sacrifice of an ally; stores life stat for effect
    if($obj->CardID === "3JWk1jxX5u") {
        $allies = ZoneSearch("myField", ["ALLY"]);
        if(!empty($allies)) {
            $allyChoices = implode("&", $allies);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $allyChoices, 100, tooltip:"Sacrifice_an_ally");
            DecisionQueueController::AddDecision($player, "CUSTOM", "Decompose_Sacrifice", 100);
        }
    }

    //1.3 Declaring Costs — Undeniable Truth (UaUfw7yFTW): mandatory sacrifice of an ally
    if($obj->CardID === "UaUfw7yFTW") {
        $allies = ZoneSearch("myField", ["ALLY"]);
        if(!empty($allies)) {
            $allyChoices = implode("&", $allies);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $allyChoices, 100, tooltip:"Sacrifice_an_ally");
            DecisionQueueController::AddDecision($player, "CUSTOM", "UndeniableTruthSacrifice", 100);
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

    //1.3 Declaring Costs — Converge Reflections (TBVLLRPiwP): sacrifice a non-token item or weapon
    if($obj->CardID === "TBVLLRPiwP") {
        $myItemsWeapons = array_merge(
            ZoneSearch("myField", ["ITEM", "REGALIA"]),
            ZoneSearch("myField", ["WEAPON"])
        );
        $sacTargets = [];
        foreach($myItemsWeapons as $mz) {
            $sObj = GetZoneObject($mz);
            if($sObj !== null && !IsToken($sObj->CardID)) $sacTargets[] = $mz;
        }
        if(!empty($sacTargets)) {
            $sacStr = implode("&", $sacTargets);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $sacStr, 100, tooltip:"Sacrifice_a_non-token_item_or_weapon");
            DecisionQueueController::AddDecision($player, "CUSTOM", "ConvergeReflectionsSacrifice", 100);
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

    //1.3 Declaring Costs — Dichroic Scorch (TlhsnnRhGK): mandatory discard fire element card
    if($obj->CardID === "TlhsnnRhGK") {
        $fireCards = [];
        $hand = GetZone("myHand");
        foreach($hand as $hi => $hObj) {
            if(!$hObj->removed && CardElement($hObj->CardID) === "FIRE") {
                $fireCards[] = "myHand-" . $hi;
            }
        }
        if(!empty($fireCards)) {
            $fireStr = implode("&", $fireCards);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $fireStr, 100, tooltip:"Discard_a_fire_element_card_(Dichroic_Scorch)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "DichroicScorchDiscard", 100);
        }
    }

    //1.3 Declaring Costs — Chessman ally sacrifice (Golden Gambit, Queen's Gambit, Freezing Gambit, Veiled Gambit)
    $chessmanSacCards = ["B1EbF6jcYF", "NGAy4rNwUo", "fgBpQZe0js", "hxdfyA0eP1"];
    if(in_array($obj->CardID, $chessmanSacCards)) {
        $chessmanAllies = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["CHESSMAN"]);
        if(!empty($chessmanAllies)) {
            $sacStr = implode("&", $chessmanAllies);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $sacStr, 100, tooltip:"Sacrifice_a_Chessman_ally");
            DecisionQueueController::AddDecision($player, "CUSTOM", "ChessmanSacrifice|" . $obj->CardID, 100);
        }
    }

    //1.3 Declaring Costs — Unmake Duality (uWLKGJz1GY): sacrifice a regalia with divine relic
    if($obj->CardID === "uWLKGJz1GY") {
        $divineRelicIDs = ["df594Qoszn","TL19V7lU6A","cxyky280mt","fjne9ri261","7ddcgw05qz",
                           "DNbIpzVgde","by8145w2u2","fln04uv297","h23qu7d6so","2gv7DC0KID"];
        $divineRelics = [];
        $myField = GetZone("myField");
        for($fi = 0; $fi < count($myField); ++$fi) {
            if(!$myField[$fi]->removed && PropertyContains(CardType($myField[$fi]->CardID), "REGALIA")
               && in_array($myField[$fi]->CardID, $divineRelicIDs)) {
                $divineRelics[] = "myField-" . $fi;
            }
        }
        if(!empty($divineRelics)) {
            if(count($divineRelics) == 1) {
                DecisionQueueController::StoreVariable("unmakeDualitySacrifice", $divineRelics[0]);
                DecisionQueueController::AddDecision($player, "CUSTOM", "UnmakeDualitySacrifice", 100);
            } else {
                $sacStr = implode("&", $divineRelics);
                DecisionQueueController::AddDecision($player, "MZCHOOSE", $sacStr, 100, tooltip:"Sacrifice_a_divine_relic_regalia");
                DecisionQueueController::AddDecision($player, "CUSTOM", "UnmakeDualitySacrifice", 100);
            }
        }
    }

    //1.3 Declaring Costs — Sacrifice Play (1jmQ9XSLph): sacrifice up to two awake Chessman allies
    if($obj->CardID === "1jmQ9XSLph") {
        $awakeChessman = [];
        $myField = GetZone("myField");
        for($fi = 0; $fi < count($myField); ++$fi) {
            if(!$myField[$fi]->removed && PropertyContains(EffectiveCardType($myField[$fi]), "ALLY")
               && PropertyContains(EffectiveCardSubtypes($myField[$fi]), "CHESSMAN")
               && isset($myField[$fi]->Status) && $myField[$fi]->Status == 2) {
                $awakeChessman[] = "myField-" . $fi;
            }
        }
        if(!empty($awakeChessman)) {
            $sacStr = implode("&", $awakeChessman);
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $sacStr, 100, tooltip:"Sacrifice_an_awake_Chessman_ally?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SacrificePlayCost1", 100);
        } else {
            DecisionQueueController::StoreVariable("sacrificePlayCount", "0");
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

    //1.3 Declaring Costs — Dominating Strike (svd53zc9p4): [Vanitas Bonus] may reveal 3 wind from memory instead of reserve
    $hasDominatingStrikeAltCost = false;
    if($obj->CardID === "svd53zc9p4" && IsVanitasBonusActive($player)) {
        $windMemory = ZoneSearch("myMemory", cardElements: ["WIND"]);
        if(count($windMemory) >= 3 && $reserveCost > 0) {
            $hasDominatingStrikeAltCost = true;
            DecisionQueueController::AddDecision($player, "YESNO", "-", 100, tooltip:"Reveal_3_wind_cards_from_memory_instead_of_paying_reserve?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "DominatingStrikeAltCost|" . $reserveCost, 100);
        }
    }

    // --- Omen-based cost reductions ---
    // Butler's Augury (5u5ic64930): [Ciel Bonus] costs 1 less per omen, up to 2
    if($obj->CardID === "5u5ic64930" && IsCielBonusActive($player)) {
        $omenDiscount = min(2, GetOmenCount($player));
        $reserveCost = max(0, $reserveCost - $omenDiscount);
    }
    // Pristine Scourge (kugriwszxr): [Ciel Bonus] costs 1 less per omen, up to 2
    if($obj->CardID === "kugriwszxr" && IsCielBonusActive($player)) {
        $omenDiscount = min(2, GetOmenCount($player));
        $reserveCost = max(0, $reserveCost - $omenDiscount);
    }
    // Obsequious Blow (macqlgvqo3): [Ciel Bonus] costs 1 less per omen (no cap)
    if($obj->CardID === "macqlgvqo3" && IsCielBonusActive($player)) {
        $reserveCost = max(0, $reserveCost - GetOmenCount($player));
    }
    // Baleful Oblation (oye74ibwo8): [Ciel Bonus] costs 2 less
    if($obj->CardID === "oye74ibwo8" && IsCielBonusActive($player)) {
        $reserveCost = max(0, $reserveCost - 2);
    }
    // Inverted Pyroslash (X5XONoPY6Z): [Ciel Bonus] costs 2 less per fire omen, up to 2
    if($obj->CardID === "X5XONoPY6Z" && IsCielBonusActive($player)) {
        $fireOmenDiscount = 2 * min(2, GetOmenCountByElement($player, "FIRE"));
        $reserveCost = max(0, $reserveCost - $fireOmenDiscount);
    }
    // Harrow the Saved (mLoz5CAeSU): [Alice Bonus] costs 3 less if curse in lineage
    if($obj->CardID === "mLoz5CAeSU" && IsAliceBonusActive($player)) {
        if(CountCursesInLineage($player) > 0) {
            $reserveCost = max(0, $reserveCost - 3);
        }
    }
    // Chance, Seven of Spades (DKoSnhjX18): [Level 1+] costs 3 less
    if($obj->CardID === "DKoSnhjX18" && PlayerLevel($player) >= 1) {
        $reserveCost = max(0, $reserveCost - 3);
    }
    // Flowing Oubli (vcxw3yh2t4): [Level 1+] costs 1 less
    if($obj->CardID === "vcxw3yh2t4" && PlayerLevel($player) >= 1) {
        $reserveCost = max(0, $reserveCost - 1);
    }
    // Annul Spell (u817uqlk1j): [Level 2+] costs 1 less
    if($obj->CardID === "u817uqlk1j" && PlayerLevel($player) >= 2) {
        $reserveCost = max(0, $reserveCost - 1);
    }
    // Tidal Lock (c4poa10ezw): costs 2 less with three or more water cards in graveyard
    if($obj->CardID === "c4poa10ezw" && count(ZoneSearch("myGraveyard", cardElements: ["WATER"])) >= 3) {
        $reserveCost = max(0, $reserveCost - 2);
    }
    // Blossoming Denial (1nnpbddblx): [Class Bonus] costs 3 less if opponent has five or more memory
    if($obj->CardID === "1nnpbddblx" && IsClassBonusActive($player, ["CLERIC"]) && count(GetMemory($opponent)) >= 5) {
        $reserveCost = max(0, $reserveCost - 3);
    }
    // Imperial Accord (1S7Q5fqX5u): [Class Bonus] costs 2 less
    if($obj->CardID === "1S7Q5fqX5u" && IsClassBonusActive($player, ["CLERIC"])) {
        $reserveCost = max(0, $reserveCost - 2);
    }
    // Next-turn cost increase from Obsequious Blow
    if(GlobalEffectCount($player, "OBSEQUIOUS_BLOW_COST") > 0) {
        $reserveCost += 2;
        RemoveGlobalEffect($player, "OBSEQUIOUS_BLOW_COST");
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

    //1.3 Declaring Costs — Resolute Stand (o6gb0op3nq): [Level 2+] may activate without paying reserve (skip next draw phase)
    $hasResoluteStandFree = false;
    if($obj->CardID === "o6gb0op3nq" && PlayerLevel($player) >= 2 && !$ignoreCost && $reserveCost > 0) {
        $hasResoluteStandFree = true;
        DecisionQueueController::AddDecision($player, "YESNO", "-", 100, tooltip:"Activate_without_paying_reserve_(skip_next_draw_phase)?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "ResoluteStandFreeCost|" . $reserveCost, 100);
    }

    //1.3 Declaring Costs — Verita, Queen of Hearts (4qc47amgpp): may banish 3+ Suited ally GY cards with total cost >= 10
    $hasVeritaAltCost = false;
    if($obj->CardID === "4qc47amgpp" && !$ignoreCost && $reserveCost > 0) {
        $suitedGY = [];
        $suitedGYTotal = 0;
        $gy = GetZone("myGraveyard");
        for($gi = 0; $gi < count($gy); ++$gi) {
            if(!$gy[$gi]->removed && PropertyContains(CardType($gy[$gi]->CardID), "ALLY")
               && PropertyContains(CardSubtypes($gy[$gi]->CardID), "SUITED")) {
                $suitedGY[] = "myGraveyard-" . $gi;
                $suitedGYTotal += intval(CardCost_reserve($gy[$gi]->CardID));
            }
        }
        if(count($suitedGY) >= 3 && $suitedGYTotal >= 10) {
            $hasVeritaAltCost = true;
            DecisionQueueController::AddDecision($player, "YESNO", "-", 100, tooltip:"Banish_3+_Suited_allies_from_GY_(total_cost_10)_instead_of_reserve?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "VeritaAltCostChoice|" . $reserveCost, 100);
        }
    }

    //1.3 Declaring Costs — Edelstein, Queen of Diamonds (AxHzxEHBHZ): may banish 3+ Suited Spell GY cards with total cost >= 10
    $hasEdelsteinAltCost = false;
    if($obj->CardID === "AxHzxEHBHZ" && !$ignoreCost && $reserveCost > 0) {
        $suitedSpellGY = [];
        $suitedSpellGYTotal = 0;
        $gy = GetZone("myGraveyard");
        for($gi = 0; $gi < count($gy); ++$gi) {
            if($gy[$gi]->removed) continue;
            if(!PropertyContains(CardSubtypes($gy[$gi]->CardID), "SUITED")) continue;
            if(!PropertyContains(CardSubtypes($gy[$gi]->CardID), "SPELL")) continue;
            $suitedSpellGY[] = "myGraveyard-" . $gi;
            $suitedSpellGYTotal += intval(CardCost_reserve($gy[$gi]->CardID));
        }
        if(count($suitedSpellGY) >= 3 && $suitedSpellGYTotal >= 10) {
            $hasEdelsteinAltCost = true;
            DecisionQueueController::AddDecision($player, "YESNO", "-", 100, tooltip:"Banish_3+_Suited_Spell_cards_from_GY_(total_cost_10)_instead_of_reserve?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "EdelsteinAltCostChoice|" . $reserveCost, 100);
        }
    }

    //1.3 Declaring Costs — Brusque Neige (irt72g89zc): may sacrifice an ally instead of paying reserve
    $hasBrusqueNeigeAltCost = false;
    if($obj->CardID === "irt72g89zc" && !$ignoreCost && $reserveCost > 0) {
        $allies = ZoneSearch("myField", ["ALLY"]);
        if(!empty($allies)) {
            $hasBrusqueNeigeAltCost = true;
            DecisionQueueController::AddDecision($player, "YESNO", "-", 100, tooltip:"Sacrifice_an_ally_instead_of_paying_reserve?");
            DecisionQueueController::AddDecision($player, "CUSTOM",
                "BrusqueNeigeAltCost|" . $reserveCost, 100);
        }
    }

        //1.3 Declaring Costs — Refabrication (cri23mf3vs): may sacrifice two tokens rather than pay reserve
        $hasRefabricationAltCost = false;
        if($obj->CardID === "cri23mf3vs" && !$ignoreCost && $reserveCost > 0) {
            $tokens = ZoneSearch("myField", ["TOKEN"]);
            if(count($tokens) >= 2) {
                $hasRefabricationAltCost = true;
                DecisionQueueController::AddDecision($player, "YESNO", "-", 100, tooltip:"Sacrifice_2_tokens_instead_of_paying_reserve?");
                DecisionQueueController::AddDecision($player, "CUSTOM",
                    "RefabricationAltCostChoice|" . $reserveCost, 100);
            }
        }

    //1.3 Declaring Costs — Awaken Ombre (OVoHxVwodU): pay X+X additional reserve
    $hasAwakenOmbreCost = false;
    if($obj->CardID === "OVoHxVwodU" && !$ignoreCost) {
        $allyOmenCount = GetOmenCountByType($player, "ALLY");
        $handCount = count(GetZone("myHand"));
        // maxX limited by ally omens and available hand cards after base cost: floor((hand - base) / 2)
        $maxX = min($allyOmenCount, max(0, intdiv($handCount - $reserveCost, 2)));
        if($maxX > 0) {
            $hasAwakenOmbreCost = true;
            DecisionQueueController::AddDecision($player, "NUMBERCHOOSE", "1-" . $maxX, 100, tooltip:"Choose_X_(pay_2X_additional_reserve)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "AwakenOmbreCost|" . $reserveCost, 100);
        }
    }

    //1.3 Declaring Costs — Furnace Drone (cbNF64gCsS): mandatory banish 3 fire and/or Automaton cards from graveyard
    $hasFurnaceDroneCost = false;
    if($obj->CardID === "cbNF64gCsS" && !$ignoreCost) {
        $eligible = [];
        $gy = GetZone("myGraveyard");
        for($gi = 0; $gi < count($gy); ++$gi) {
            if($gy[$gi]->removed) continue;
            if(CardElement($gy[$gi]->CardID) === "FIRE" || PropertyContains(CardSubtypes($gy[$gi]->CardID), "AUTOMATON")) {
                $eligible[] = "myGraveyard-" . $gi;
            }
        }
        if(count($eligible) >= 3) {
            $hasFurnaceDroneCost = true;
            DecisionQueueController::StoreVariable("additionalCostPaid", "NO");
            DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $eligible), 100, tooltip:"Banish_a_fire_or_Automaton_card_(1_of_3)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "FurnaceDroneCostBanish|3|" . $reserveCost, 100);
        }
    }

    // 1.3 Declaring Costs — Devotion's Price (ri955ygd5v): mandatory discard two cards
    $hasDevotionsPriceCost = false;
    if($obj->CardID === "ri955ygd5v" && !$ignoreCost) {
        $handChoices = ZoneSearch("myHand");
        if(count($handChoices) < 2) {
            SetFlashMessage("Devotion's Price requires two cards to discard.");
            return;
        }
        $hasDevotionsPriceCost = true;
        DecisionQueueController::StoreVariable("additionalCostPaid", "NO");
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $handChoices), 100, tooltip:"Discard_a_card_(1_of_2)");
        DecisionQueueController::AddDecision($player, "CUSTOM", "DevotionsPriceDiscard1|" . $reserveCost, 100);
    }

    //1.3 Declaring Costs — Clash of Fates (9rbziyasag): [Guo Jia Bonus] may remove a quest counter instead of reserve
    $hasClashOfFatesAltCost = false;
    if($obj->CardID === "9rbziyasag" && IsGuoJiaBonus($player) && !$ignoreCost && $reserveCost > 0) {
        if(GetQuestCounterCount($player) >= 1) {
            $hasClashOfFatesAltCost = true;
            DecisionQueueController::AddDecision($player, "YESNO", "-", 100, tooltip:"Remove_a_quest_counter_instead_of_paying_reserve?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "ClashOfFatesAltCost|" . $reserveCost, 100);
        }
    }

    //1.3 Declaring Costs — Winds of Destiny (nhk5d19n82): may rest 2 Fatestones instead of reserve
    $hasWindsOfDestinyAltCost = false;
    if($obj->CardID === "nhk5d19n82" && !$ignoreCost && $reserveCost > 0) {
        global $playerID;
        $zone = $player == $playerID ? "myField" : "theirField";
        $awakeFatestones = [];
        $field = GetZone($zone);
        for($fi = 0; $fi < count($field); ++$fi) {
            if(!$field[$fi]->removed && PropertyContains(CardSubtypes($field[$fi]->CardID), "FATESTONE")
                && isset($field[$fi]->Status) && $field[$fi]->Status == 2) {
                $awakeFatestones[] = $zone . "-" . $fi;
            }
        }
        if(count($awakeFatestones) >= 2) {
            $hasWindsOfDestinyAltCost = true;
            DecisionQueueController::AddDecision($player, "YESNO", "-", 100, tooltip:"Rest_2_Fatestones_instead_of_paying_reserve?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "WindsOfDestinyAltCost|" . $reserveCost, 100);
        }
    }

    if(!$hasAdditionalCost && !$hasSongOfFrostAltCost && !$hasBrewAltCost && !$hasScryAltCost && !$hasDominatingStrikeAltCost && !$hasKindlingFlareCost && !$hasRavishingFinaleCost && !$hasExpungeCost && !$hasInterventionCost && !$hasBreakApartCost && !$hasCoronationCost && !$hasResoluteStandFree && !$hasVeritaAltCost && !$hasEdelsteinAltCost && !$hasBrusqueNeigeAltCost && !$hasRefabricationAltCost && !$hasAwakenOmbreCost && !$hasFurnaceDroneCost && !$hasDevotionsPriceCost && !$hasSlimeKingCost && !$hasClashOfFatesAltCost && !$hasWindsOfDestinyAltCost) {
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
            $imbueOptions = GetCardImbueOptions($player, $obj->CardID);
            $hasImbue = !empty($imbueOptions);
            if($hasImbue) {
                $memoryBefore = count(GetMemory($player));
                DecisionQueueController::StoreVariable("imbueMemoryBefore", "$memoryBefore");
                DecisionQueueController::ClearVariable("imbueChoiceIndex");
                DecisionQueueController::StoreVariable("isImbued", "NO");
            }

            if($hasImbue && !$ignoreCost && $reserveCost > 0) {
                if(count($imbueOptions) > 1) {
                    $labels = array_map(fn($option) => GetImbueOptionLabel($obj->CardID, $option), $imbueOptions);
                    DecisionQueueController::AddDecision($player, "MZMODAL", "1|1|" . implode("&", $labels), 100, "Choose_Imbue_characteristic");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "ChooseImbueCharacteristic|" . $reserveCost, 100);
                } else {
                    DecisionQueueController::AddDecision($player, "YESNO", "-", 100, tooltip:"Reveal_reserved_cards_for_Imbue?");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "DeclareImbue|" . $reserveCost, 100);
                }
            } else {
                //1.8 Paying Costs
                if(!$ignoreCost) {
                    for($i = 0; $i < $reserveCost; ++$i) {
                        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
                    }
                }

                if(!$hasImbue) {
                    DecisionQueueController::StoreVariable("isImbued", "NO");
                }

                //1.9 Activation — grant Opportunity to the opponent before resolving
                DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
            }
        } // end if(!$hasKindle)
    }
    // When $hasDominatingStrikeAltCost is true, the DominatingStrikeAltCost handler
    // takes over queuing reveal/no-reserve or normal reserve + EffectStackOpportunity.
    // When $hasAdditionalCost is true, the DeclareAdditionalCost handler takes over
    // queuing reserve payments and EffectStackOpportunity after the player answers.
    // When $hasSongOfFrostAltCost is true, SongOfFrostAltCost handler queues its own
    // reserve/banish + EffectStackOpportunity.
    // When $hasBrewAltCost is true, DeclareBrew handler queues herb sacrifice or
    // normal reserve + EffectStackOpportunity.
    // When $hasScryAltCost is true, ScryTheStarsAltCost handler queues banish or
    // normal reserve + EffectStackOpportunity.
    // When $hasVeritaAltCost is true, VeritaAltCostChoice handler queues iterative
    // GY banish or normal reserve + EffectStackOpportunity.
    // When $hasEdelsteinAltCost is true, EdelsteinAltCostChoice handler queues iterative
    // GY banish or normal reserve + EffectStackOpportunity.
}

$customDQHandlers["ReserveCard"] = function($player, $parts, $lastDecision) {
    $source = GetReservePaymentChoiceSource($player);
    $tooltip = "Choose_a_card_to_pay_reserve_cost";
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $source, 1, $tooltip);
    DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard_Process", 99);
};

$customDQHandlers["DeclareImbue"] = function($player, $parts, $lastDecision) {
    $reserveCost = intval($parts[0] ?? 0);
    $revealReservedCards = $lastDecision === "YES";
    if($revealReservedCards) {
        DecisionQueueController::StoreVariable("imbueRevealQueue", "");
        DecisionQueueController::StoreVariable("imbueReserveRemaining", strval($reserveCost));
    }
    for($i = 0; $i < $reserveCost; ++$i) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
    }
    if(!$revealReservedCards) {
        DecisionQueueController::StoreVariable("isImbued", "NO");
        ClearImbueSetupVariables();
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100, "", 1);
};

$customDQHandlers["ChooseImbueCharacteristic"] = function($player, $parts, $lastDecision) {
    $reserveCost = intval($parts[0] ?? 0);
    $cardID = GetPendingImbueCardID();
    if($cardID === null || $cardID === "") {
        DecisionQueueController::StoreVariable("isImbued", "NO");
        ClearImbueSetupVariables();
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
        return;
    }
    $imbueOptions = GetCardImbueOptions($player, $cardID);
    if(empty($imbueOptions)) {
        DecisionQueueController::StoreVariable("isImbued", "NO");
        ClearImbueSetupVariables();
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
        return;
    }
    $selectedIndex = intval(explode(",", $lastDecision)[0] ?? 0);
    if(!isset($imbueOptions[$selectedIndex])) $selectedIndex = 0;
    DecisionQueueController::StoreVariable("imbueChoiceIndex", strval($selectedIndex));
    DecisionQueueController::AddDecision($player, "YESNO", "-", 100, tooltip:"Reveal_reserved_cards_for_Imbue?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DeclareImbue|" . $reserveCost, 100);
};

$customDQHandlers["IngredientPouchGather"] = function($player, $parts, $lastDecision) {
    Gather($player);
};

$customDQHandlers["DominatingStrikeAltCost"] = function($player, $parts, $lastDecision) {
    $baseReserve = intval($parts[0]);
    if($lastDecision === "YES") {
        $windMemory = ZoneSearch("myMemory", cardElements: ["WIND"]);
        $revealed = 0;
        foreach($windMemory as $mz) {
            Reveal($player, $mz);
            ++$revealed;
            if($revealed >= 3) break;
        }
    } else {
        for($i = 0; $i < $baseReserve; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
    }
    DecisionQueueController::StoreVariable("isImbued", "NO");
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
};

// Clash of Fates (9rbziyasag): [Guo Jia Bonus] may remove quest counter instead of reserve
$customDQHandlers["ClashOfFatesAltCost"] = function($player, $parts, $lastDecision) {
    $baseReserve = intval($parts[0]);
    if($lastDecision === "YES") {
        RemoveQuestCounters($player, 1);
    } else {
        for($i = 0; $i < $baseReserve; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
    }
    DecisionQueueController::StoreVariable("isImbued", "NO");
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
};

$customDQHandlers["WindsOfDestinyAltCost"] = function($player, $parts, $lastDecision) {
    $baseReserve = intval($parts[0]);
    if($lastDecision === "YES") {
        // Rest 2 Fatestones chosen by the player
        global $playerID;
        $zone = $player == $playerID ? "myField" : "theirField";
        $awakeFatestones = [];
        $field = GetZone($zone);
        for($fi = 0; $fi < count($field); ++$fi) {
            if(!$field[$fi]->removed && PropertyContains(CardSubtypes($field[$fi]->CardID), "FATESTONE")
                && isset($field[$fi]->Status) && $field[$fi]->Status == 2) {
                $awakeFatestones[] = $zone . "-" . $fi;
            }
        }
        if(count($awakeFatestones) >= 2) {
            $targetStr = implode("&", $awakeFatestones);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 100, "Rest_first_Fatestone");
            DecisionQueueController::AddDecision($player, "CUSTOM", "WindsOfDestinyRestStep|1", 100);
        }
    } else {
        for($i = 0; $i < $baseReserve; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
    }
    DecisionQueueController::StoreVariable("isImbued", "NO");
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
};

$customDQHandlers["WindsOfDestinyRestStep"] = function($player, $parts, $lastDecision) {
    $step = intval($parts[0]);
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        ExhaustCard($player, $lastDecision);
    }
    if($step < 2) {
        // Need to rest one more Fatestone
        global $playerID;
        $zone = $player == $playerID ? "myField" : "theirField";
        $awakeFatestones = [];
        $field = GetZone($zone);
        for($fi = 0; $fi < count($field); ++$fi) {
            if(!$field[$fi]->removed && PropertyContains(CardSubtypes($field[$fi]->CardID), "FATESTONE")
                && isset($field[$fi]->Status) && $field[$fi]->Status == 2) {
                $awakeFatestones[] = $zone . "-" . $fi;
            }
        }
        if(!empty($awakeFatestones)) {
            $targetStr = implode("&", $awakeFatestones);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 100, "Rest_second_Fatestone");
            DecisionQueueController::AddDecision($player, "CUSTOM", "WindsOfDestinyRestStep|2", 100);
        }
    }
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

// Ephemerate extra cost: discard a card from hand, then activate (Treacle, Drowned Mouse)
$customDQHandlers["EphemerateDiscardProcess"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "PASS" || empty($lastDecision)) return;
    MZMove($player, $lastDecision, "myGraveyard");
    DecisionQueueController::CleanupRemovedCards();
    $hand = &GetHand($player);
    $handIdx = count($hand) - 1;
    ActivateCard($player, "myHand-" . $handIdx, false);
};

// Ephemerate extra cost: banish another card from graveyard, then activate (Maledictum Vitae)
$customDQHandlers["EphemerateBanishOtherGYProcess"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "PASS" || empty($lastDecision)) return;
    MZMove($player, $lastDecision, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    $hand = &GetHand($player);
    $handIdx = count($hand) - 1;
    ActivateCard($player, "myHand-" . $handIdx, false);
};

// Return to the Depths [Ciel Bonus]: banish GY card with omen counter
$customDQHandlers["ReturnToDepthsOmen"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "PASS" || empty($lastDecision)) return;
    BanishWithOmenCounter($player, $lastDecision);
};

$customDQHandlers["Byx6iokcT4:0:CardActivated-1"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    DecisionQueueController::StoreVariable("Topsy_choices", $lastDecision);
    DecisionQueueController::AddDecision($player, "CUSTOM", "TopsyDecree_Process", 1);
};

$customDQHandlers["TopsyDecree_Process"] = function($player, $parts, $lastDecision) {
    $choices = DecisionQueueController::GetVariable("Topsy_choices");
    if($choices === "-" || $choices === "" || $choices === null) return;
    $modes = explode(",", $choices);
    foreach($modes as $modeIdx) {
        $modeIdx = trim($modeIdx);
        switch($modeIdx) {
            case "0": // Champion gains spellshroud until end of turn
                global $playerID;
                $zone = $player == $playerID ? "myField" : "theirField";
                $field = GetZone($zone);
                foreach($field as $fObj) {
                    if(!$fObj->removed && PropertyContains(EffectiveCardType($fObj), "CHAMPION")) {
                        AddTurnEffect($fObj->GetMzID(), "SPELLSHROUD");
                        break;
                    }
                }
                break;
            case "1": // Up to one target opponent discards a card from hand or memory
                $oppPlayer = ($player == 1) ? 2 : 1;
                $oppHand = &GetHand($oppPlayer);
                $oppMemory = &GetMemory($oppPlayer);
                $targets = [];
                for($i = 0; $i < count($oppHand); ++$i) {
                    if(!$oppHand[$i]->removed) $targets[] = "theirHand-" . $i;
                }
                for($i = 0; $i < count($oppMemory); ++$i) {
                    if(!$oppMemory[$i]->removed) $targets[] = "theirMemory-" . $i;
                }
                if(!empty($targets)) {
                    $targetStr = implode("&", $targets);
                    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1, tooltip:"Discard_a_card_(Topsy_Decree)");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "TopsyDecreeDiscard", 1);
                }
                break;
            case "2": // Choose up to 2 cards from a single graveyard and banish them
                $myGY = GetZone("myGraveyard");
                $theirGY = GetZone("theirGraveyard");
                $targets = [];
                for($gi = 0; $gi < count($myGY); ++$gi) {
                    if(!$myGY[$gi]->removed) $targets[] = "myGraveyard-" . $gi;
                }
                for($gi = 0; $gi < count($theirGY); ++$gi) {
                    if(!$theirGY[$gi]->removed) $targets[] = "theirGraveyard-" . $gi;
                }
                if(!empty($targets)) {
                    DecisionQueueController::StoreVariable("topsyBanishCount", "0");
                    $targetStr = implode("&", $targets);
                    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1, tooltip:"Banish_card_from_GY_(1_of_2)");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "TopsyDecreeBanish", 1);
                }
                break;
        }
    }
    DecisionQueueController::ClearVariable("Topsy_choices");
};

$customDQHandlers["TopsyDecreeDiscard"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "PASS" || empty($lastDecision)) return;
    MZMove($player, $lastDecision, "myGraveyard");
};

$customDQHandlers["TopsyDecreeBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "PASS" || empty($lastDecision)) return;
    // Determine which GY was chosen so the second pick must be from the same
    $chosenGY = (strpos($lastDecision, "myGraveyard") === 0) ? "myGraveyard" : "theirGraveyard";
    MZMove($player, $lastDecision, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    $banishCount = intval(DecisionQueueController::GetVariable("topsyBanishCount")) + 1;
    if($banishCount < 2) {
        // Offer second banish from the SAME graveyard
        $gy = GetZone($chosenGY);
        $targets = [];
        for($gi = 0; $gi < count($gy); ++$gi) {
            if(!$gy[$gi]->removed) $targets[] = $chosenGY . "-" . $gi;
        }
        if(!empty($targets)) {
            DecisionQueueController::StoreVariable("topsyBanishCount", "$banishCount");
            $targetStr = implode("&", $targets);
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1, tooltip:"Banish_card_from_GY_(2_of_2)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "TopsyDecreeBanish", 1);
        }
    }
};

// --- Perilous Mend: recursive Curse selection ---
// PerilousMendChooseCurse: offer MZMayChoose for Curse in hand/memory, recursive
function PerilousMendChooseCurse($player, $count) {
    global $playerID;
    $handZone = $player == $playerID ? "myHand" : "theirHand";
    $memZone = $player == $playerID ? "myMemory" : "theirMemory";
    $hand = &GetHand($player);
    $memory = &GetMemory($player);
    $curseTargets = [];
    for($i = 0; $i < count($hand); ++$i) {
        if(!$hand[$i]->removed && PropertyContains(CardSubtypes($hand[$i]->CardID), "CURSE")) {
            $curseTargets[] = $handZone . "-" . $i;
        }
    }
    for($i = 0; $i < count($memory); ++$i) {
        if(!$memory[$i]->removed && PropertyContains(CardSubtypes($memory[$i]->CardID), "CURSE")) {
            $curseTargets[] = $memZone . "-" . $i;
        }
    }
    if(empty($curseTargets)) {
        // No more curses available — finalize: recover 3*count, draw if distant
        PerilousMendFinalize($player, $count);
        return;
    }
    DecisionQueueController::StoreVariable("perilousMendCount", "$count");
    $targetStr = implode("&", $curseTargets);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1, tooltip:"Put_Curse_on_lineage?_(Perilous_Mend)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "PerilousMendChooseCurse", 1);
}

function PerilousMendFinalize($player, $count) {
    if($count > 0) {
        RecoverChampion($player, 3 * $count);
    }
    // If champion is distant, draw a card
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    foreach($field as $fObj) {
        if(!$fObj->removed && PropertyContains(EffectiveCardType($fObj), "CHAMPION")) {
            if(IsDistant($fObj)) {
                Draw($player, amount: 1);
            }
            break;
        }
    }
}

$customDQHandlers["PerilousMendChooseCurse"] = function($player, $parts, $lastDecision) {
    $count = intval(DecisionQueueController::GetVariable("perilousMendCount"));
    if($lastDecision === "-" || $lastDecision === "PASS" || empty($lastDecision)) {
        // Done choosing — finalize
        PerilousMendFinalize($player, $count);
        return;
    }
    // Move chosen curse to bottom of champion's lineage
    AddToChampionLineage($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    $count++;
    // Recurse for more
    PerilousMendChooseCurse($player, $count);
};

// Rosewinged Hollow (6S1LLrBfBU): GY activation → haunt counter + optional +2 POWER to Specter ally
$customDQHandlers["RosewingedHollowGY_Apply"] = function($player, $parts, $lastDecision) {
    AddHauntToMastery($player, 1);
    if(GetHauntCount($player) >= 6) {
        global $playerID;
        $zone = $player == $playerID ? "myField" : "theirField";
        $specterAllies = ZoneSearch($zone, ["ALLY"], cardSubtypes: ["SPECTER"]);
        if(!empty($specterAllies)) {
            $targetStr = implode("&", $specterAllies);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, tooltip:"Choose_Specter_ally_to_get_+2_POWER");
            DecisionQueueController::AddDecision($player, "CUSTOM", "RosewingedHollowGY_Buff", 1);
        }
    }
};

$customDQHandlers["RosewingedHollowGY_Buff"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        AddTurnEffect($lastDecision, "6S1LLrBfBU");
    }
};

// Phantasmagoria (D3rexaXCBo): End phase — put all GY on bottom of deck, then mill X = haunt counters
$customDQHandlers["PhantasmagoriaEndPhase"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    // Put all graveyard cards on bottom of deck
    $gy = &GetGraveyard($player);
    $deck = &GetDeck($player);
    for($i = count($gy) - 1; $i >= 0; --$i) {
        if(!$gy[$i]->removed) {
            array_push($deck, $gy[$i]);
            $gy[$i]->removed = true;
        }
    }
    DecisionQueueController::CleanupRemovedCards();
    // Mill X where X is haunt counters
    $hauntCount = GetHauntCount($player);
    for($i = 0; $i < $hauntCount; ++$i) {
        $deck = &GetDeck($player);
        if(empty($deck)) break;
        MZMove($player, "myDeck-0", "myGraveyard");
    }
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
        $restedObj = GetZoneObject($lastDecision);
        ExhaustCard($player, $lastDecision);

        // Wildheart Lyre (50pcescfpw): [CB] whenever rested to pay for Harmony/Melody reserve,
        // put a buff counter on an Animal or Beast ally you control
        if($restedObj !== null && $restedObj->CardID === "50pcescfpw" && !HasNoAbilities($restedObj)
            && IsClassBonusActive($player, ["TAMER"])) {
            // Check if the card being activated is Harmony or Melody
            $es = GetZone("EffectStack");
            $isHarmonyMelody = false;
            for($esi = count($es) - 1; $esi >= 0; --$esi) {
                if(!$es[$esi]->removed) {
                    $esSubtypes = CardSubtypes($es[$esi]->CardID);
                    if(PropertyContains($esSubtypes, "HARMONY") || PropertyContains($esSubtypes, "MELODY")) {
                        $isHarmonyMelody = true;
                    }
                    break;
                }
            }
            if($isHarmonyMelody) {
                $animalBeast = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["ANIMAL", "BEAST"]);
                if(!empty($animalBeast)) {
                    if(count($animalBeast) == 1) {
                        AddCounters($player, $animalBeast[0], "buff", 1);
                    } else {
                        $choices = implode("&", $animalBeast);
                        DecisionQueueController::AddDecision($player, "MZCHOOSE", $choices, 1,
                            tooltip:"Choose_Animal/Beast_ally_for_buff_counter_(Wildheart_Lyre)");
                        DecisionQueueController::AddDecision($player, "CUSTOM", "WildheartLyreBuff", 1);
                    }
                }
            }
        }
    } else {
        // Hand card: move to memory as normal
        $newObj = OnCardReserved($player, $lastDecision);
        if($newObj !== null && DecisionQueueController::GetVariable("imbueRevealQueue") !== null) {
            $newMzID = "myMemory-" . $newObj->mzIndex;
            $queued = DecisionQueueController::GetVariable("imbueRevealQueue");
            if($queued === "" || $queued === null) {
                DecisionQueueController::StoreVariable("imbueRevealQueue", $newMzID);
            } else {
                DecisionQueueController::StoreVariable("imbueRevealQueue", $queued . "|" . $newMzID);
            }
        }
    }

    $imbueRemaining = DecisionQueueController::GetVariable("imbueReserveRemaining");
    if($imbueRemaining !== null && $imbueRemaining !== "") {
        $remaining = max(0, intval($imbueRemaining) - 1);
        DecisionQueueController::StoreVariable("imbueReserveRemaining", strval($remaining));
        if($remaining === 0) {
            RevealImbueReserved($player);
        }
    }
};

// Wildheart Lyre (50pcescfpw): DQ handler for choosing Animal/Beast ally to buff
$customDQHandlers["WildheartLyreBuff"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    AddCounters($player, $lastDecision, "buff", 1);
};

$customDQHandlers["MeteoricSlimeAfterGlimpse"] = function($player, $parts, $lastDecision) {
    $sourceMZ = $parts[0];
    $sourceObj = GetZoneObject($sourceMZ);
    if($sourceObj === null || $sourceObj->removed || $sourceObj->CardID !== "5ybMub985n" || HasNoAbilities($sourceObj)) return;

    $deck = GetDeck($player);
    $revealCount = min(2, count($deck));
    if($revealCount <= 0) return;

    $lowestReserve = null;
    for($i = 0; $i < $revealCount; ++$i) {
        DoRevealCard($player, "myDeck-" . $i);
        $cardID = $deck[$i]->CardID;
        $reserve = intval(CardReserveCost($cardID));
        if($lowestReserve === null || $reserve < $lowestReserve) $lowestReserve = $reserve;
    }
    if($lowestReserve === null || $lowestReserve <= 0) return;

    $targets = array_merge(
        ZoneSearch("myField", ["ALLY", "CHAMPION"]),
        ZoneSearch("theirField", ["ALLY", "CHAMPION"])
    );
    $targets = FilterSpellshroudTargets($targets);
    if(empty($targets)) return;

    DecisionQueueController::StoreVariable("MeteoricSlimeDamage", strval($lowestReserve));
    DecisionQueueController::StoreVariable("MeteoricSlimeSource", $sourceMZ);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1,
        tooltip:"Choose_target_unit_for_Meteoric_Slime");
    DecisionQueueController::AddDecision($player, "CUSTOM", "MeteoricSlimeDeal", 1);
};

$customDQHandlers["MeteoricSlimeDeal"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $damage = intval(DecisionQueueController::GetVariable("MeteoricSlimeDamage"));
    $sourceMZ = DecisionQueueController::GetVariable("MeteoricSlimeSource");
    if($damage <= 0 || $sourceMZ === null || $sourceMZ === "") return;
    DealDamage($player, $sourceMZ, $lastDecision, $damage);
    DecisionQueueController::ClearVariable("MeteoricSlimeDamage");
    DecisionQueueController::ClearVariable("MeteoricSlimeSource");
};

/**
 * Reveal the reserved cards for Imbue, evaluate the chosen Imbue condition,
 * then store the shared isImbued result and clear the temporary setup vars.
 */
function RevealImbueReserved($player) {
    $selectedOption = GetChosenImbueOption($player);
    if($selectedOption === null) return;

    $memoryBefore = intval(DecisionQueueController::GetVariable("imbueMemoryBefore"));
    $threshold = intval($selectedOption['threshold'] ?? 0);
    $matcher = $selectedOption['matcher'] ?? "card_element";
    $element = $selectedOption['element'] ?? null;

    $queued = DecisionQueueController::GetVariable("imbueRevealQueue");
    if($queued !== null && $queued !== "") {
        foreach(explode("|", $queued) as $mzID) {
            if($mzID === "") continue;
            DoRevealCard($player, $mzID);
        }
    }

    $memory = GetMemory($player);
    $elementMatches = 0;
    $advancedElements = ["CRUX", "EXALTED", "ASTRA", "LUXEM", "UMBRA", "TERA"];
    // Count matching cards among the newly added memory entries for the chosen Imbue characteristic.
    for($i = $memoryBefore; $i < count($memory); ++$i) {
        if($memory[$i]->removed) continue;
        $memoryElement = EffectiveCardElement($memory[$i]);
        switch($matcher) {
            case "advanced":
                if(in_array($memoryElement, $advancedElements)) $elementMatches++;
                break;
            case "element":
            case "card_element":
                if($memoryElement === $element) $elementMatches++;
                break;
            default:
                $elementMatches++;
                break;
        }
    }
    $isImbued = $elementMatches >= $threshold ? "YES" : "NO";
    DecisionQueueController::StoreVariable("isImbued", $isImbued);
    ClearImbueSetupVariables();
}

$customDQHandlers["RevealImbueReserved"] = function($player, $parts, $lastDecision) {
    RevealImbueReserved($player);
};

function StonescaleBandEnter($player) {
    $choices = array_merge(ZoneSearch("myHand", ["ALLY"]), ZoneSearch("myMemory", ["ALLY"]));
    if(empty($choices)) return;
    DecisionQueueController::StoreVariable("StonescaleBandDiscardCount", "0");
    $targetStr = implode("&", $choices);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1, tooltip:"Discard_an_ally_card?_(1_of_3)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "StonescaleBandDiscard", 1);
}

$customDQHandlers["StonescaleBandDiscard"] = function($player, $parts, $lastDecision) {
    $count = intval(DecisionQueueController::GetVariable("StonescaleBandDiscardCount"));
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        if($count > 0) Draw($player, $count);
        return;
    }
    MZMove($player, $lastDecision, "myGraveyard");
    DecisionQueueController::CleanupRemovedCards();
    ++$count;
    DecisionQueueController::StoreVariable("StonescaleBandDiscardCount", strval($count));
    if($count >= 3) {
        Draw($player, $count);
        return;
    }
    $choices = array_merge(ZoneSearch("myHand", ["ALLY"]), ZoneSearch("myMemory", ["ALLY"]));
    if(empty($choices)) {
        Draw($player, $count);
        return;
    }
    $targetStr = implode("&", $choices);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1, tooltip:"Discard_an_ally_card?_(" . ($count + 1) . "_of_3)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "StonescaleBandDiscard", 1);
};

function RendingFlamesOnAttack($player, $mzID) {
    if(!IsClassBonusActive($player, ["ASSASSIN", "WARRIOR"])) return;
    $fireGY = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
    if(count($fireGY) < 3) return;
    DecisionQueueController::StoreVariable("RendingFlamesSource", $mzID);
    DecisionQueueController::StoreVariable("RendingFlamesCount", "0");
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Banish_3_fire_cards_from_your_graveyard_for_double_damage?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "RendingFlamesConfirm", 1);
}

$customDQHandlers["RendingFlamesConfirm"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $fireGY = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
    if(count($fireGY) < 3) return;
    $targetStr = implode("&", $fireGY);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, tooltip:"Banish_a_fire_card_(1_of_3)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "RendingFlamesProcess", 1);
};

$customDQHandlers["RendingFlamesProcess"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $count = intval(DecisionQueueController::GetVariable("RendingFlamesCount"));
    $source = DecisionQueueController::GetVariable("RendingFlamesSource");
    MZMove($player, $lastDecision, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    ++$count;
    DecisionQueueController::StoreVariable("RendingFlamesCount", strval($count));
    if($count >= 3) {
        AddTurnEffect($source, "soO3hjaVfN_DOUBLE");
        return;
    }
    $fireGY = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
    if(count($fireGY) < (3 - $count)) return;
    $targetStr = implode("&", $fireGY);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, tooltip:"Banish_a_fire_card_(" . ($count + 1) . "_of_3)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "RendingFlamesProcess", 1);
};

// --- Slate Whetstone (a8a0v4njrt) Handler ---
$customDQHandlers["SlateWhetstoneBuffTarget"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        $targetObj = GetZoneObject($lastDecision);
        if($targetObj !== null) {
            AddTurnEffect($lastDecision, "a8a0v4njrt");
        }
    }
    Draw($player, 1);
};

// --- Return to the Archive (aIbBhTilEN) Handler ---
$customDQHandlers["ReturnToArchiveProcess"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        // Player chose a regalia to sacrifice
        MZMove($player, $lastDecision, "myGraveyard");
        RecoverChampion($player, 2);
        Draw($player, 1);
    }
};

// --- Scathing Seminary (aL5pGBcr7i) Handler ---
$customDQHandlers["ScathingSeminaryDamage"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    
    $targetObj = GetZoneObject($lastDecision);
    if($targetObj === null) return;
    
    // Get the source: Scathing Seminary
    $mzID = DecisionQueueController::GetVariable("mzID");
    
    // Deal 2 unpreventable damage to target unit
    DealUnpreventableDamage($player, $mzID, $lastDecision, 2);
};

// --- Ritai Stablemaster (ba0tqvwlp1) Handler ---
$customDQHandlers["RitaiStablemasterProcess"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // No cards discarded
        return;
    }
    
    // Player chose a fire card to discard
    MZMove($player, $lastDecision, "myGraveyard");
    DrawIntoMemory($player, 1);
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
        $kindledObj = GetZoneObject($lastDecision);
        $kindledCardID = $kindledObj !== null ? $kindledObj->CardID : null;
        MZMove($player, $lastDecision, "myBanish");
        // March Hare, Mottled Host (7w4v1hgl3e): [Element Bonus] if banished from GY
        // to pay reserve cost, put it onto the field instead.
        if($kindledCardID === "7w4v1hgl3e" && IsElementBonusActive($player, "7w4v1hgl3e")) {
            $banishZone = GetZone("myBanish");
            for($mhi = count($banishZone) - 1; $mhi >= 0; --$mhi) {
                if(!$banishZone[$mhi]->removed && $banishZone[$mhi]->CardID === "7w4v1hgl3e") {
                    MZMove($player, "myBanish-" . $mhi, "myField");
                    break;
                }
            }
        }
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
 * DQ handler: Slime King (f0ymeslfpw) mandatory activation cost.
 * Banish 3 Slime allies with different elements from your graveyard, then pay reserve.
 * Parts: [remainingCount, reserveCost, usedElementsCSV].
 */
$customDQHandlers["SlimeKingCostBanish"] = function($player, $parts, $lastDecision) {
    $remainingBefore = intval($parts[0]);
    $reserveCost = intval($parts[1]);
    $usedElements = empty($parts[2]) ? [] : array_filter(explode(",", $parts[2]));
    if($lastDecision === "-" || $lastDecision === "") return;

    $chosenObj = GetZoneObject($lastDecision);
    if($chosenObj === null) return;
    $chosenElement = CardElement($chosenObj->CardID);
    if(in_array($chosenElement, $usedElements)) return;

    $banishedObj = MZMove($player, $lastDecision, "myBanish");
    if($banishedObj !== null) {
        if(!is_array($banishedObj->Counters)) $banishedObj->Counters = [];
        $banishedObj->Counters['_slimeKing'] = 1;
    }

    $usedElements[] = $chosenElement;
    $remaining = $remainingBefore - 1;
    if($remaining > 0) {
        $eligible = [];
        $gy = GetZone("myGraveyard");
        for($gi = 0; $gi < count($gy); ++$gi) {
            if($gy[$gi]->removed) continue;
            if(!PropertyContains(CardType($gy[$gi]->CardID), "ALLY")) continue;
            if(!PropertyContains(CardSubtypes($gy[$gi]->CardID), "SLIME")) continue;
            $element = CardElement($gy[$gi]->CardID);
            if(in_array($element, $usedElements)) continue;
            $eligible[] = "myGraveyard-" . $gi;
        }
        if(count($eligible) < $remaining) return;
        $pickNumber = 4 - $remaining;
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $eligible), 100, tooltip:"Banish_a_Slime_ally_with_a_new_element_(" . $pickNumber . "_of_3)");
        DecisionQueueController::AddDecision($player, "CUSTOM", "SlimeKingCostBanish|" . $remaining . "|" . $reserveCost . "|" . implode(",", $usedElements), 100);
        return;
    }

    DecisionQueueController::StoreVariable("isImbued", "NO");
    for($i = 0; $i < $reserveCost; ++$i) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
};

/**
 * DQ handler: Furnace Drone (cbNF64gCsS) mandatory activation cost.
 * Banish 3 fire element and/or Automaton cards from your graveyard, then pay reserve.
 * Parts: [remainingCount, reserveCost].
 */
$customDQHandlers["FurnaceDroneCostBanish"] = function($player, $parts, $lastDecision) {
    $remainingBefore = intval($parts[0]);
    $reserveCost = intval($parts[1]);
    if($lastDecision === "-" || $lastDecision === "") return;

    MZMove($player, $lastDecision, "myBanish");

    $remaining = $remainingBefore - 1;
    if($remaining > 0) {
        $eligible = [];
        $gy = GetZone("myGraveyard");
        for($gi = 0; $gi < count($gy); ++$gi) {
            if($gy[$gi]->removed) continue;
            if(CardElement($gy[$gi]->CardID) === "FIRE" || PropertyContains(CardSubtypes($gy[$gi]->CardID), "AUTOMATON")) {
                $eligible[] = "myGraveyard-" . $gi;
            }
        }
        if(count($eligible) < $remaining) return;
        $pickNumber = 4 - $remaining;
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $eligible), 100, tooltip:"Banish_a_fire_or_Automaton_card_(" . $pickNumber . "_of_3)");
        DecisionQueueController::AddDecision($player, "CUSTOM", "FurnaceDroneCostBanish|" . $remaining . "|" . $reserveCost, 100);
        return;
    }

    DecisionQueueController::StoreVariable("isImbued", "NO");
    for($i = 0; $i < $reserveCost; ++$i) {
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
 * DQ handler: Resolute Stand (o6gb0op3nq) — if YES, skip reserve payment and mark skip-next-draw.
 * Parts: [baseReserve].
 */
$customDQHandlers["ResoluteStandFreeCost"] = function($player, $parts, $lastDecision) {
    $baseReserve = intval($parts[0]);
    if($lastDecision === "YES") {
        // Free activation — skip reserve, but mark next draw phase to be skipped
        AddGlobalEffects($player, "SKIP_NEXT_DRAW");
        // Queue no ReserveCard calls — free
    } else {
        // Pay normal cost
        for($i = 0; $i < $baseReserve; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
};

// Verita (4qc47amgpp): Alt cost — YESNO choice handler
$customDQHandlers["VeritaAltCostChoice"] = function($player, $parts, $lastDecision) {
    $baseReserve = intval($parts[0]);
    if($lastDecision === "YES") {
        // Start iterative GY banish — track count and running cost total
        DecisionQueueController::StoreVariable("veritaBanishCount", "0");
        DecisionQueueController::StoreVariable("veritaBanishTotal", "0");
        DecisionQueueController::AddDecision($player, "CUSTOM", "VeritaAltCostPick", 100);
    } else {
        // Normal reserve payment
        DecisionQueueController::StoreVariable("additionalCostPaid", "NO");
        for($i = 0; $i < $baseReserve; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
        DecisionQueueController::StoreVariable("isImbued", "NO");
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
    }
};

// Verita (4qc47amgpp): Alt cost — iterative Suited ally GY pick
$customDQHandlers["VeritaAltCostPick"] = function($player, $parts, $lastDecision) {
    $count = intval(DecisionQueueController::GetVariable("veritaBanishCount"));
    $total = intval(DecisionQueueController::GetVariable("veritaBanishTotal"));

    // Check if conditions met (3+ cards, total >= 10)
    if($count >= 3 && $total >= 10) {
        // Alt cost fully paid — skip reserve, proceed
        DecisionQueueController::StoreVariable("additionalCostPaid", "NO");
        DecisionQueueController::StoreVariable("isImbued", "NO");
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
        return;
    }

    // Build available Suited ally GY list
    $suitedGY = [];
    $gy = GetZone("myGraveyard");
    for($gi = 0; $gi < count($gy); ++$gi) {
        if(!$gy[$gi]->removed && PropertyContains(CardType($gy[$gi]->CardID), "ALLY")
           && PropertyContains(CardSubtypes($gy[$gi]->CardID), "SUITED")) {
            $suitedGY[] = "myGraveyard-" . $gi;
        }
    }

    if(empty($suitedGY)) return; // Should not happen if pre-check was correct

    $remaining = 10 - $total;
    $needed = max(0, 3 - $count);
    $tooltip = "Banish_Suited_ally_from_GY_(need_" . $needed . "_more_cards,_" . $remaining . "_more_cost)";
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $suitedGY), 100, tooltip:$tooltip);
    DecisionQueueController::AddDecision($player, "CUSTOM", "VeritaAltCostProcess", 100);
};

// Verita (4qc47amgpp): Alt cost — process each picked card
$customDQHandlers["VeritaAltCostProcess"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $obj = GetZoneObject($lastDecision);
    $cardCost = intval(CardCost_reserve($obj->CardID));
    MZMove($player, $lastDecision, "myBanish");

    $count = intval(DecisionQueueController::GetVariable("veritaBanishCount")) + 1;
    $total = intval(DecisionQueueController::GetVariable("veritaBanishTotal")) + $cardCost;
    DecisionQueueController::StoreVariable("veritaBanishCount", "$count");
    DecisionQueueController::StoreVariable("veritaBanishTotal", "$total");

    // Continue picking
    DecisionQueueController::AddDecision($player, "CUSTOM", "VeritaAltCostPick", 100);
};

// Edelstein (AxHzxEHBHZ): Alt cost — YESNO choice handler
$customDQHandlers["EdelsteinAltCostChoice"] = function($player, $parts, $lastDecision) {
    $baseReserve = intval($parts[0]);
    if($lastDecision === "YES") {
        DecisionQueueController::StoreVariable("edelsteinBanishCount", "0");
        DecisionQueueController::StoreVariable("edelsteinBanishTotal", "0");
        DecisionQueueController::AddDecision($player, "CUSTOM", "EdelsteinAltCostPick", 100);
    } else {
        DecisionQueueController::StoreVariable("additionalCostPaid", "NO");
        for($i = 0; $i < $baseReserve; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
        DecisionQueueController::StoreVariable("isImbued", "NO");
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
    }
};

// Edelstein (AxHzxEHBHZ): Alt cost — iterative Suited Spell GY pick
$customDQHandlers["EdelsteinAltCostPick"] = function($player, $parts, $lastDecision) {
    $count = intval(DecisionQueueController::GetVariable("edelsteinBanishCount"));
    $total = intval(DecisionQueueController::GetVariable("edelsteinBanishTotal"));

    if($count >= 3 && $total >= 10) {
        DecisionQueueController::StoreVariable("additionalCostPaid", "NO");
        DecisionQueueController::StoreVariable("isImbued", "NO");
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
        return;
    }

    $suitedSpellGY = [];
    $gy = GetZone("myGraveyard");
    for($gi = 0; $gi < count($gy); ++$gi) {
        if($gy[$gi]->removed) continue;
        if(!PropertyContains(CardSubtypes($gy[$gi]->CardID), "SUITED")) continue;
        if(!PropertyContains(CardSubtypes($gy[$gi]->CardID), "SPELL")) continue;
        $suitedSpellGY[] = "myGraveyard-" . $gi;
    }

    if(empty($suitedSpellGY)) return;

    $remaining = 10 - $total;
    $needed = max(0, 3 - $count);
    $tooltip = "Banish_Suited_Spell_from_GY_(need_" . $needed . "_more_cards,_" . $remaining . "_more_cost)";
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $suitedSpellGY), 100, tooltip:$tooltip);
    DecisionQueueController::AddDecision($player, "CUSTOM", "EdelsteinAltCostProcess", 100);
};

// Edelstein (AxHzxEHBHZ): Alt cost — process each picked card
$customDQHandlers["EdelsteinAltCostProcess"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $obj = GetZoneObject($lastDecision);
    if($obj === null || $obj->removed) return;
    $cardCost = intval(CardCost_reserve($obj->CardID));
    MZMove($player, $lastDecision, "myBanish");

    $count = intval(DecisionQueueController::GetVariable("edelsteinBanishCount")) + 1;
    $total = intval(DecisionQueueController::GetVariable("edelsteinBanishTotal")) + $cardCost;
    DecisionQueueController::StoreVariable("edelsteinBanishCount", "$count");
    DecisionQueueController::StoreVariable("edelsteinBanishTotal", "$total");

    DecisionQueueController::AddDecision($player, "CUSTOM", "EdelsteinAltCostPick", 100);
};

/**
 * DQ handler: Recurring Invocation (iyhlctxcrq) level-up trigger — if YES, pay (1) and banish to Empower 2.
 * Parts: [mzGY].
 */
$customDQHandlers["RecurringInvocationLevelUp"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $mzGY = $parts[0];
    // Pay (1) reserve
    DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 1);
    DecisionQueueController::AddDecision($player, "CUSTOM", "RecurringInvocationLevelUpBanish|$mzGY", 1);
};

/**
 * DQ handler: after paying (1), banish Recurring Invocation from GY and Empower 2.
 * Parts: [mzGY].
 */
$customDQHandlers["RecurringInvocationLevelUpBanish"] = function($player, $parts, $lastDecision) {
    $mzGY = $parts[0];
    global $playerID;
    $banishZone = ($player == $playerID) ? "myBanish" : "theirBanish";
    MZMove($player, $mzGY, $banishZone);
    Empower($player, 2, "iyhlctxcrq");
};

/**
 * DQ handler: Devoted Martyr (p16w5j93mk) level-up trigger — if YES, banish from GY to Recover 2.
 * Parts: [mzGY].
 */
$customDQHandlers["DevotedMartyrLevelUp"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $mzGY = $parts[0];
    global $playerID;
    $banishZone = ($player == $playerID) ? "myBanish" : "theirBanish";
    MZMove($player, $mzGY, $banishZone);
    RecoverChampion($player, 2);
};

/**
 * DQ handler: Suspicious Concoction (5tphi6xl26) level-up trigger — if YES, banish from field to draw into memory + recover 2.
 * Parts: [mzField].
 */
$customDQHandlers["SuspiciousConcoctionLevelUp"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $mzField = $parts[0];
    $obj = GetZoneObject($mzField);
    if($obj === null || $obj->removed || $obj->CardID !== "5tphi6xl26") return;
    OnLeaveField($player, $mzField);
    global $playerID;
    $banishZone = ($player == $playerID) ? "myBanish" : "theirBanish";
    MZMove($player, $mzField, $banishZone);
    DecisionQueueController::CleanupRemovedCards();
    DrawIntoMemory($player, 1);
    RecoverChampion($player, 2);
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

// Crackling Incineration: controller puts a sheen counter on a unit they control
$customDQHandlers["CracklingIncinerationSheen"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddCounters($player, $lastDecision, "sheen", 1);
};

// Merlin L1: summon Memorite Blade after paying (2)
$customDQHandlers["MerlinL1SummonBlade"] = function($player, $parts, $lastDecision) {
    SummonMemorite($player, "nZFkDcvpaY");
};

// Merlin L1 Inherited: opponent chooses a unit they control to put a sheen counter on
$customDQHandlers["MerlinInheritedSheen"] = function($player, $parts, $lastDecision) {
    // $player here is the opponent (recollecting player) — parts[0] is the count of sheen to place
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $sheenCount = isset($parts[0]) ? intval($parts[0]) : 1;
    AddCounters($player, $lastDecision, "sheen", $sheenCount);
};

// Merlin L3: banish a card from opponent's memory and let them activate it until end of next turn
$customDQHandlers["MerlinL3BanishMemory"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $opponent = intval($parts[0]);
    global $playerID;
    $oppBanish = $opponent == $playerID ? "myBanish" : "theirBanish";
    $banishedObj = MZMove($player, $lastDecision, $oppBanish);
    if($banishedObj !== null) {
        // Tag the banished card so it can be activated until end of opponent's next turn
        if(!is_array($banishedObj->TurnEffects)) $banishedObj->TurnEffects = [];
        $banishedObj->TurnEffects[] = "MERLIN_L3_ACTIVATE";
    }
};

// Dichroic Scorch: discard handler (additional cost)
$customDQHandlers["DichroicScorchDiscard"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    DoDiscardCard($player, $lastDecision);
};

$customDQHandlers["FacetTogetherSacrifice"] = function($player, $parts, $lastDecision) {
    $count = intval(DecisionQueueController::GetVariable("FacetTogetherCount"));
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        // Sacrifice chosen Memorite
        MZMove($player, $lastDecision, "myGraveyard");
        $count++;
        DecisionQueueController::StoreVariable("FacetTogetherCount", strval($count));
        // Check for more Memorites
        $remaining = ZoneSearch("myField", cardSubtypes: ["MEMORITE"]);
        if(!empty($remaining)) {
            $remStr = implode("&", $remaining);
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $remStr, 1, tooltip:"Sacrifice_another_Memorite?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "FacetTogetherSacrifice", 1);
            return;
        }
    }
    // Done sacrificing — if count > 0, target a weapon for +X POWER
    if($count <= 0) return;
    global $playerID;
    $myZone = $player == $playerID ? "myField" : "theirField";
    $weapons = ZoneSearch($myZone, ["WEAPON"]);
    if(empty($weapons)) {
        // No weapon target, but still put sheen on mastery
        AddSheenToMastery($player, $count);
        return;
    }
    DecisionQueueController::StoreVariable("FacetTogetherCount", strval($count));
    $wepStr = implode("&", $weapons);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $wepStr, 1, tooltip:"Target_weapon_for_+POWER");
    DecisionQueueController::AddDecision($player, "CUSTOM", "FacetTogetherApply", 1);
};

$customDQHandlers["FacetTogetherApply"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $count = intval(DecisionQueueController::GetVariable("FacetTogetherCount"));
    AddTurnEffect($lastDecision, "FACET_POWER_" . $count);
    AddSheenToMastery($player, $count);
};

function OnCardReserved($player, $mzCard) {
    return MZMove($player, $mzCard, "myMemory");
}

function ClearImbueSetupVariables() {
    DecisionQueueController::ClearVariable("imbueMemoryBefore");
    DecisionQueueController::ClearVariable("imbueChoiceIndex");
    DecisionQueueController::ClearVariable("imbueRevealQueue");
    DecisionQueueController::ClearVariable("imbueReserveRemaining");
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

$customDQHandlers["CielMiragesGraveDamage"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "") {
        DealUnpreventableDamage($player, "-", $lastDecision, 2);
    }
};

$customDQHandlers["CielEndPhaseOmen"] = function($player, $parts, $lastDecision) {
    // Player chose a card from hand or graveyard to banish with omen counter
    if($lastDecision === "-" || $lastDecision === "") return;
    BanishWithOmenCounter($player, $lastDecision);
};

$customDQHandlers["CielOmenbringerEnterLoop"] = function($player, $parts, $lastDecision) {
    // For each omen, discard from hand or memory, then draw into memory.
    // $parts[0] = remaining omen count
    $remaining = intval($parts[0]);
    if($remaining <= 0) return;
    $handAndMem = array_merge(ZoneSearch("myHand"), ZoneSearch("myMemory"));
    if(empty($handAndMem)) return;
    $targetStr = implode("&", $handAndMem);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1);
    DecisionQueueController::AddDecision($player, "CUSTOM", "CielOmenbringerDiscard|" . ($remaining - 1), 1);
};

$customDQHandlers["CielOmenbringerDiscard"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $remaining = intval($parts[0]);
    // Discard the chosen card
    MZMove($player, $lastDecision, "myGraveyard");
    // Draw into memory
    DrawIntoMemory($player, 1);
    // Continue loop if remaining > 0
    if($remaining > 0) {
        $handAndMem = array_merge(ZoneSearch("myHand"), ZoneSearch("myMemory"));
        if(!empty($handAndMem)) {
            $targetStr = implode("&", $handAndMem);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1);
            DecisionQueueController::AddDecision($player, "CUSTOM", "CielOmenbringerDiscard|" . ($remaining - 1), 1);
        }
    }
};

$customDQHandlers["CielOmenbringerLR"] = function($player, $parts, $lastDecision) {
    // Lineage Release: activate the chosen omen from banishment by moving to hand
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $omenObj = GetZoneObject($lastDecision);
    if($omenObj === null) return;
    $cardID = $omenObj->CardID;
    // Move the omen from banishment to hand so it can be activated normally
    MZMove($player, $lastDecision, "myHand");
};

// Flowing Oubli (vcxw3yh2t4): look at top 2, choose 1 to banish with omen, other to bottom
function FlowingOubliResolve($player) {
    $deck = &GetDeck($player);
    $n = min(2, count($deck));
    if($n == 0) return;
    for($i = 0; $i < $n; ++$i) {
        MZAddZone($player, "myTempZone", $deck[0]->CardID);
        array_shift($deck);
    }
    for($i = 0; $i < count($deck); ++$i) $deck[$i]->mzIndex = $i;
    $choices = ZoneSearch("myTempZone");
    if(count($choices) <= 1) {
        if(count($choices) == 1) BanishWithOmenCounter($player, $choices[0]);
        return;
    }
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $choices), 1, tooltip:"Banish_with_omen_counter");
    DecisionQueueController::AddDecision($player, "CUSTOM", "FlowingOubliChoose", 1);
}

$customDQHandlers["FlowingOubliChoose"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // Put all back on bottom
        $remaining = ZoneSearch("myTempZone");
        foreach($remaining as $rmz) MZMove($player, $rmz, "myDeck");
        return;
    }
    BanishWithOmenCounter($player, $lastDecision);
    $remaining = ZoneSearch("myTempZone");
    foreach($remaining as $rmz) MZMove($player, $rmz, "myDeck");
};

// Pristine Scourge (kugriwszxr): look at opponent memory, discard 1 (or 2 if 5+ distinct costs)
function PristineScourgeResolve($player) {
    $oppMemory = ZoneSearch("theirMemory");
    if(empty($oppMemory)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $oppMemory), 1, tooltip:"Discard_from_opponent_memory");
    DecisionQueueController::AddDecision($player, "CUSTOM", "PristineScourgeDiscard", 1);
}

$customDQHandlers["PristineScourgeDiscard"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "theirGraveyard");
    DecisionQueueController::CleanupRemovedCards();
    // Check for additional discard: 5+ distinct omen reserve costs
    if(GetOmenDistinctCostCount($player) < 5) return;
    $oppMemory = ZoneSearch("theirMemory");
    if(empty($oppMemory)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $oppMemory), 1, tooltip:"Discard_additional_card");
    DecisionQueueController::AddDecision($player, "CUSTOM", "PristineScourgeDiscard2", 1);
};

$customDQHandlers["PristineScourgeDiscard2"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "theirGraveyard");
};

$customDQHandlers["DevotionsPriceDiscard1"] = function($player, $parts, $lastDecision) {
    if(empty($parts)) return;
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $reserveCost = intval($parts[0]);
    DoDiscardCard($player, $lastDecision);
    $remainingHand = ZoneSearch("myHand");
    if(empty($remainingHand)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $remainingHand), 1, tooltip:"Discard_a_card_(2_of_2)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DevotionsPriceDiscard2|" . $reserveCost, 1);
};

$customDQHandlers["DevotionsPriceDiscard2"] = function($player, $parts, $lastDecision) {
    if(empty($parts)) return;
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $reserveCost = intval($parts[0]);
    DoDiscardCard($player, $lastDecision);
    for($i = 0; $i < $reserveCost; ++$i) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 1);
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 1);
};

$customDQHandlers["VeilarasPromiseBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES" || empty($parts)) return;
    $mzID = $parts[0];
    $obj = GetZoneObject($mzID);
    if($obj === null || $obj->removed || $obj->CardID !== "rcwr60wa5b") return;
    OnLeaveField($player, $mzID);
    MZMove($player, $mzID, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    Draw($player, 1);
};

// Lacuna's Grasp (w7annwvl5q): On Attack pay X reserve for +X POWER
function LacunasGraspOnAttack($player) {
    if(!IsCielBonusActive($player)) return;
    $intentCards = GetIntentCards($player);
    if(!empty($intentCards)) return; // "no cards in the attacker's intent"
    $maxX = min(GetOmenCount($player), count(GetZone("myHand")));
    if($maxX <= 0) return;
    DecisionQueueController::AddDecision($player, "NUMBERCHOOSE", "0-" . $maxX, 1, tooltip:"Pay_X_reserve_for_+X_POWER");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LacunasGraspPay", 1);
}

$customDQHandlers["LacunasGraspPay"] = function($player, $parts, $lastDecision) {
    $x = intval($lastDecision);
    if($x <= 0) return;
    for($r = 0; $r < $x; ++$r) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 1);
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "LacunasGraspBuff|" . $x, 1);
};

$customDQHandlers["LacunasGraspBuff"] = function($player, $parts, $lastDecision) {
    $amount = intval($parts[0]);
    if($amount <= 0) return;
    $weaponMZ = GetCombatWeapon();
    if($weaponMZ === null) return;
    AddTurnEffect($weaponMZ, "w7annwvl5q-" . $amount);
};

$customDQHandlers["CardActivated"] = function($player, $parts, $lastDecision) {
    CardActivated($player, $parts[0]);
};

function OnCardActivated($player, $mzCard) {
    global $cardActivatedAbilities;
    $obj = GetZoneObject($mzCard);
    $cardType = CardType($obj->CardID);
    if(PropertyContains($cardType, "ALLY")) {
        $obj = MoveEffectStackCardToField($player, $mzCard);
        $obj->Controller = $player;
    } else if(PropertyContains($cardType, "WEAPON")) {
        // Weapons enter the field like allies (main-deck weapons with reserve cost)
        $obj = MoveEffectStackCardToField($player, $mzCard);
        $obj->Controller = $player;
    }  else if(PropertyContains($cardType, "REGALIA")) {
        // Regalia enter the field like allies (main-deck regalia with reserve cost)
        $obj = MoveEffectStackCardToField($player, $mzCard);
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
        $obj = MoveEffectStackCardToField($player, $mzCard);
        $obj->Controller = $player;
    } else if(PropertyContains($cardType, "DOMAIN")) {
        // Domains enter the field like allies/regalia — they are objects that persist
        $obj = MoveEffectStackCardToField($player, $mzCard);
        $obj->Controller = $player;
    } else if(PropertyContains($cardType, "ITEM")) {
        // Items (e.g. Potions, Accessories) enter the field as persistent objects
        $obj = MoveEffectStackCardToField($player, $mzCard);
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
        $field = GetField($player);
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

    $champMZ = FindChampionMZ($player);
    if($champMZ !== null) {
        if(PropertyContains($cardType, "ACTION")) {
            AddTurnEffect($champMZ, "vz4kc558yx-ACTION_ACTIVATED");
            $champObj = GetZoneObject($champMZ);
            if($champObj !== null && in_array("vz4kc558yx-ACTION_PENDING", $champObj->TurnEffects ?? [])) {
                $champObj->TurnEffects = array_values(array_filter(
                    $champObj->TurnEffects,
                    fn($e) => $e !== "vz4kc558yx-ACTION_PENDING"
                ));
            }
        }
        if(PropertyContains($cardType, "ALLY")) {
            AddTurnEffect($champMZ, "vz4kc558yx-ALLY_ACTIVATED");
            $champObj = GetZoneObject($champMZ);
            if($champObj !== null && in_array("vz4kc558yx-ALLY_PENDING", $champObj->TurnEffects ?? [])) {
                $champObj->TurnEffects = array_values(array_filter(
                    $champObj->TurnEffects,
                    fn($e) => $e !== "vz4kc558yx-ALLY_PENDING"
                ));
            }
        }
    }

    // Insignia of the Corhazi (52u81v4c0z): [CB] whenever you activate a prepared card while influence ≤ 6, draw into memory
    $wasPrepared = DecisionQueueController::GetVariable("wasPrepared");
    if($wasPrepared === "YES" && GetInfluence($player) <= 6) {
        $field = GetField($player);
        for($ici = 0; $ici < count($field); ++$ici) {
            if(!$field[$ici]->removed && $field[$ici]->CardID === "52u81v4c0z"
                && !HasNoAbilities($field[$ici])
                && IsClassBonusActive($player, ["ASSASSIN"])) {
                DrawIntoMemory($player, 1);
                break;
            }
        }
    }

    // "Whenever you activate" triggers — check field for listening cards
    $field = GetField($player);
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
                    $hand = GetHand($player);
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
            case "5av43ehjdu": // Ventus, Staff of Zephyrs: whenever you activate a wind Mage Spell, put a refinement counter
                if($activatedElement === "WIND" && PropertyContains($subtypes, "SPELL")
                    && PropertyContains(CardClasses($obj->CardID), "MAGE")
                    && !HasNoAbilities($field[$fi])) {
                    AddCounters($player, "myField-" . $fi, "refinement", 1);
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
            case "84e2rfex54": // Quadrille's Gryphon: whenever you activate a Melody or Harmony, put a buff counter
                if((PropertyContains($subtypes, "MELODY") || PropertyContains($subtypes, "HARMONY"))
                    && !HasNoAbilities($field[$fi])) {
                    AddCounters($player, "myField-" . $fi, "buff", 1);
                }
                break;
            case "5LoOprBJay": // Discordia, Harp of Malice: whenever you activate a Harmony or Melody, put a music counter
                if((PropertyContains($subtypes, "HARMONY") || PropertyContains($subtypes, "MELODY"))
                    && !HasNoAbilities($field[$fi])) {
                    AddCounters($player, "myField-" . $fi, "music", 1);
                }
                break;
            case "nZFkDcvpaY": // Memorite Blade: whenever you activate a Spell, +1 POWER (once per turn)
                if(PropertyContains($subtypes, "SPELL") && !HasNoAbilities($field[$fi])
                    && !in_array("nZFkDcvpaY_POWER", $field[$fi]->TurnEffects)) {
                    AddTurnEffect("myField-" . $fi, "nZFkDcvpaY_POWER");
                }
                break;
            case "IBXLKkBUe1": // Weiss Knight: whenever you activate a Chessman Command card, gain unblockable until EOT
                if(PropertyContains($subtypes, "COMMAND") && PropertyContains($subtypes, "CHESSMAN")
                    && !HasNoAbilities($field[$fi])) {
                    AddTurnEffect("myField-" . $fi, "UNBLOCKABLE");
                }
                break;
            case "W0WfIEDs3n": // Field of Ranks and Files: first Chessman Command each of your turns → +2 POWER to intent card
                if(PropertyContains($subtypes, "COMMAND") && PropertyContains($subtypes, "CHESSMAN")
                    && !HasNoAbilities($field[$fi])
                    && $player === $turnPlayer
                    && GlobalEffectCount($player, "W0WfIEDs3n_CMD") == 0) {
                    AddGlobalEffects($player, "W0WfIEDs3n_CMD");
                    // Add +2 POWER to the attack card on the effect stack
                    $es = GetZone("EffectStack");
                    for($esi = count($es) - 1; $esi >= 0; --$esi) {
                        if(!$es[$esi]->removed && $es[$esi]->CardID === $obj->CardID) {
                            AddTurnEffect("EffectStack-" . $esi, "W0WfIEDs3n-CMD");
                            break;
                        }
                    }
                }
                break;
            case "6SXL09rEzS": // Conduit of the Mad Mage: whenever you activate a Mage Spell action, wake up + +1 POWER until EOT
                if(PropertyContains($cardType, "ACTION") && PropertyContains($subtypes, "SPELL")
                    && PropertyContains(CardClasses($obj->CardID), "MAGE")
                    && !HasNoAbilities($field[$fi])) {
                    WakeupCard($player, "myField-" . $fi);
                    AddTurnEffect("myField-" . $fi, "6SXL09rEzS-POWER");
                }
                break;
            case "rcwr60wa5b": // Veilara's Promise: whenever you activate a Spell card, refine; at 3+, may banish to draw
                if(PropertyContains($subtypes, "SPELL") && !HasNoAbilities($field[$fi])) {
                    $promiseMZ = "myField-" . $fi;
                    AddCounters($player, $promiseMZ, "refinement", 1);
                    $promiseObj = GetZoneObject($promiseMZ);
                    if($promiseObj !== null && GetCounterCount($promiseObj, "refinement") >= 3) {
                        DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Banish_Veilara's_Promise_to_draw_a_card?");
                        DecisionQueueController::AddDecision($player, "CUSTOM", "VeilarasPromiseBanish|" . $promiseMZ, 1);
                    }
                }
                break;
        }
    }

    // Alice, Phantom Monarch (emqOANitoD) — Inherited Effect:
    // Whenever you play an advanced element card while no Curse in Alice's lineage, deal 7 unpreventable to Alice.
    // Soutirer Vortex (du4df43ci2): your omens trigger on matching reserve costs opponents activate
    $opponent = ($player == 1) ? 2 : 1;
    $oppField = GetField($opponent);
    foreach($oppField as $vortexObj) {
        if($vortexObj->removed || $vortexObj->CardID !== "du4df43ci2" || HasNoAbilities($vortexObj)) continue;
        $matchingOmens = 0;
        $activatedReserveCost = intval(CardCost_reserve($obj->CardID));
        foreach(GetOmens($opponent) as $omenMZ) {
            $omenObj = GetZoneObject($omenMZ);
            if($omenObj === null || $omenObj->removed) continue;
            if(intval(CardCost_reserve($omenObj->CardID)) === $activatedReserveCost) {
                ++$matchingOmens;
            }
        }
        for($i = 0; $i < $matchingOmens; ++$i) {
            RecoverChampion($opponent, 1);
            DealChampionDamage($player, 1);
        }
        break;
    }

    if(ChampionHasInLineage($player, "emqOANitoD")) {
        if(IsAdvancedElementCard($obj->CardID) && CountCursesInLineage($player) === 0) {
            $champField = &GetField($player);
            for($ci = 0; $ci < count($champField); ++$ci) {
                if(!$champField[$ci]->removed && PropertyContains(EffectiveCardType($champField[$ci]), "CHAMPION")) {
                    $champField[$ci]->Damage += 7;
                    break;
                }
            }
        }
    }

    if(IsAdvancedElementCard($obj->CardID)) {
        IncrementAdvancedElementActivatedCount($player);
    }

    // Rai, Archmage (zdIhSL5RhK) — Inherited Effect:
    // Whenever you activate your first Mage action card each turn, put an enlighten counter on your champion.
    if(PropertyContains($cardType, "ACTION") && PropertyContains(CardClasses($obj->CardID), "MAGE")) {
        if(ChampionHasInLineage($player, "zdIhSL5RhK") && GlobalEffectCount($player, "RAI_ARCHMAGE_TRIGGERED") == 0) {
            AddGlobalEffects($player, "RAI_ARCHMAGE_TRIGGERED");
            // Find champion and add enlighten counter
            $champField = GetField($player);
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
        $oppField = GetField($opponent);
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

    // Fatestone of Balance (v4gtq1ibth): [Guo Jia Bonus] whenever opponent activates a card
    // and they have exactly 3 cards in memory, transform
    FatestoneOfBalanceOnOpponentActivated($player);

    // Kongming, Fel Eidolon (7x2v4tdop1): Inherited — whenever you activate a Spell card,
    // may change SC to an adjacent direction.
    if(PropertyContains($subtypes, "SPELL") && HasShiftingCurrents($player)
        && ChampionHasInLineage($player, "7x2v4tdop1")) {
        QueueShiftingCurrentsChoice($player, "adjacent", true);
    }

    // Aethercharge activation tracking
    if(PropertyContains($subtypes, "AETHERCHARGE")) {
        IncrementAetherchargeCount($player);
        $aethCount = AetherchargeActivatedCount($player);

        // Dyadic Fletcher (hohkep3vi9): [CB] Whenever you activate an Aethercharge card
        // for the second time each turn, Dyadic Fletcher becomes distant.
        if($aethCount == 2) {
            $myField = GetField($player);
            for($dfi = 0; $dfi < count($myField); ++$dfi) {
                if(!$myField[$dfi]->removed && $myField[$dfi]->CardID === "hohkep3vi9"
                   && !HasNoAbilities($myField[$dfi])
                   && IsClassBonusActive($player, ["RANGER"])) {
                    global $playerID;
                    $dMZ = ($player == $playerID ? "myField" : "theirField") . "-" . $dfi;
                    BecomeDistant($player, $dMZ);
                }
            }
        }
    }

    TriggerNightmareCoilPunish($player);

    // After an attack card enters intent and its abilities resolve, declare the attack
    if(PropertyContains($cardType, "ATTACK")) {
        // Command: an ally performs the attack instead of champion
        if(PropertyContains($subtypes, "COMMAND")) {
            if(PropertyContains($subtypes, "AUTOMATON")) {
                DecisionQueueController::AddDecision($player, "CUSTOM", "CommandAutomatonChooseAttacker", 100);
            } else {
                DecisionQueueController::AddDecision($player, "CUSTOM", "CommandChessmanChooseAttacker", 100);
            }
        } else {
            DecisionQueueController::AddDecision($player, "CUSTOM", "DeclareChampionAttack", 100);
        }
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
        case "u7d6soporh": // Ingredient Pouch — (1), REST
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            break;
        case "nd8dy77ikm": // Shadeblood Coating â€” banish self
        case "nxm05jkjxg": // Rousing Rattle Drum â€” banish self
        case "pgysz2zfji": // Leporine Masque - banish self
            MZMove($player, $mzCard, "myBanish");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "n67ghdh1t6": // Naga's Fang â€” remove a preparation counter from your champion
            $champMZ = FindChampionMZ($player);
            if($champMZ !== null) {
                RemoveCounters($player, $champMZ, "preparation", 1);
            }
            break;
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
        case "g8q7imka92": // Consumption Ring — banish self
        case "idpdon8f0h": // Enfeebled Dagger — banish self
        case "K15jWbHAMY": // Teardrop Diadem — banish self
        case "fm894uc4ij": // Manxome Armoire — banish self
        case "LeyUk5auEP": // Purifying Thurible — banish self
        case "T3cx65VM3D": // Enfeebling Orb — banish self
        case "473gyf0w3v": // Duxal Proclamation — banish self
        case "4864k12no2": // Scepter of Fascination — banish self
        case "4dys05p49w": // Gem of Sorority — banish self
        case "IC3OU6vCnF": // Mana Limiter — banish self
        case "lcb6jhxctx": // Gearstride Gloves — banish self
        case "llQe0cg4xJ": // Orb of Choking Fumes — banish self
        case "mgesApvmwS": // Prismspire Scepter — banish self
        case "mnz5kgifhd": // Sanguine Goblet — banish self
        case "me0xxw0plq": // Refracted Twilight — banish self
        case "qqq8j5fxym": // Shard of Empowerment — banish self
        case "xl3tzqhlt1": // Hairpin of Transience — banish self
        case "xnrw8qq1uw": // Tariff Ring — banish self
        case "czvy67nbin": // Prismatic Codex — banish self
        case "yxk7e8opr6": // Spectral Beacon — banish self
        case "df594Qoszn": // Apotheosis Rite — banish self
            MZMove($player, $mzCard, "myBanish");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "DNbIpzVgde": { // Lost Providence — REST + banish self
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1; // REST
            MZMove($player, $mzCard, "myBanish");
            DecisionQueueController::CleanupRemovedCards();
            break;
        }
        case "TL19V7lU6A": { // Sacramental Rite — store banished card ID, then banish self
            $riteObj = &GetZoneObject($mzCard);
            $sacBanishedID = (is_array($riteObj->Counters) && isset($riteObj->Counters['sacBanishedID']))
                ? $riteObj->Counters['sacBanishedID'] : "";
            DecisionQueueController::StoreVariable("sacBanishedCardID", $sacBanishedID);
            MZMove($player, $mzCard, "myBanish");
            DecisionQueueController::CleanupRemovedCards();
            break;
        }
        case "41t71u4bzz": { // Polaris, Twinkling Cauldron — REST + store age count + banish self
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1; // REST
            $ageCount = GetCounterCount($sourceObj, "age");
            DecisionQueueController::StoreVariable("polarisAgeCount", $ageCount);
            MZMove($player, $mzCard, "myBanish");
            DecisionQueueController::CleanupRemovedCards();
            break;
        }
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
        case "4moumzcx9z": // Staff of Blossoming Will — REST
        case "E09lX95cb9": // Ticket to the Afterlife — REST
        case "qj5bbae3z4": // Cosmic Astroscope — REST
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            break;
        case "r1o0qtb31x": // Worn Gearblade — REST, remove a durability counter
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            RemoveCounters($player, $mzCard, "durability", 1);
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
        case "2bzajcZZRD": // Map of Hidden Passage — REST + Banish self
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            MZMove($player, $mzCard, "myBanish");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "x7mnu1xhs5": // Fractal of Creation — sacrifice self
            DoSacrificeFighter($player, $mzCard);
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "2o82fwl22v": // Unstable Fractal — REST + sacrifice self (+ pay 3 handled by framework)
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
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
        case "0Z1r8GC8a8": // Speed Potion: sacrifice self
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
        case "wtHBZAdTSv": // Nether Dodobird: sacrifice self — store ephemeral state
        case "xF9phlSAkE": // Waited Accord: sacrifice self
            {
                $sourceObj = GetZoneObject($mzCard);
                if($cardID === "wtHBZAdTSv") {
                    DecisionQueueController::StoreVariable(
                        "NetherDodobirdWasEphemeral",
                        ($sourceObj !== null && IsEphemeral($sourceObj)) ? "YES" : "NO"
                    );
                }
            }
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
        case "0sVdvpQKXq": // Heirloom of Spectra: banish self; second ability also pays (3)
            MZMove($player, $mzCard, "myBanish");
            DecisionQueueController::CleanupRemovedCards();
            if($abilityIndex == 1) {
                for($i = 0; $i < 3; ++$i) {
                    DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 1);
                }
            }
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
        case "nlufjh84vm": // Confidant's Oath: REST + remove 2 refinement counters
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            RemoveCounters($player, $mzCard, "refinement", 2);
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
            $pField = GetField($player);
            for($ci = 0; $ci < count($pField); ++$ci) {
                if(!$pField[$ci]->removed && PropertyContains(EffectiveCardType($pField[$ci]), "CHAMPION")) {
                    RemoveCounters($player, "myField-" . $ci, "preparation", 2);
                    break;
                }
            }
            break;
        case "0ejcyuvuxn": // Corhazi Arsonist: remove 1 prep counter from champion
            $pField = GetField($player);
            for($ci = 0; $ci < count($pField); ++$ci) {
                if(!$pField[$ci]->removed && PropertyContains(EffectiveCardType($pField[$ci]), "CHAMPION")) {
                    RemoveCounters($player, "myField-" . $ci, "preparation", 1);
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
        case "3qu7d6sopo": // Incendiary Shot
        case "4x7e22tk3i": // Tasershot
        case "XgzTexcCSA": // Punishing Cartridge
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
        case "TvugEkGGVd": // Hua Xiong: REST + discard a Polearm attack card
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1; // REST
            $hxHand = GetZone("myHand");
            $polearmCards = [];
            for($hx = 0; $hx < count($hxHand); ++$hx) {
                if(!$hxHand[$hx]->removed
                    && PropertyContains(CardType($hxHand[$hx]->CardID), "ATTACK")
                    && PropertyContains(CardSubtypes($hxHand[$hx]->CardID), "POLEARM")) {
                    $polearmCards[] = "myHand-" . $hx;
                }
            }
            if(!empty($polearmCards)) {
                $plStr = implode("&", $polearmCards);
                DecisionQueueController::AddDecision($player, "MZCHOOSE", $plStr, 1, "Discard_a_Polearm_attack_card");
                DecisionQueueController::AddDecision($player, "CUSTOM", "HuaXiongDiscard", 1);
            }
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
        case "jetWcli3ZL": // Balmshot Nurse — REST
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            break;
        case "lcCGyyNGuM": // Wool Brook — REST + remove refinement counter
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            RemoveCounters($player, $mzCard, "refinement", 1);
            break;
        case "qFwqqT0XWo": // Ducal Seal — banish self
            MZMove($player, $mzCard, "myBanish");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "qM9yzxQbfF": // Orbiting Cosmos — banish self
            MZMove($player, $mzCard, "myBanish");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "ex6AXz6IhB": // Vacuous Call — sacrifice self + discard an ally card
            {
                // Sacrifice self
                DoSacrificeFighter($player, $mzCard);
                DecisionQueueController::CleanupRemovedCards();
                // Discard an ally card from hand
                $allyHand = [];
                $hand = GetZone("myHand");
                for($hi = 0; $hi < count($hand); ++$hi) {
                    if(!$hand[$hi]->removed && PropertyContains(CardType($hand[$hi]->CardID), "ALLY")) {
                        $allyHand[] = "myHand-" . $hi;
                    }
                }
                if(!empty($allyHand)) {
                    $choices = implode("&", $allyHand);
                    DecisionQueueController::AddDecision($player, "MZCHOOSE", $choices, 1, "Discard_an_ally_card");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "VacuousCallDiscardAlly", 1);
                }
            }
            break;
        case "9ooAGDhBj7": // Briar's Spindle
            if($abilityIndex == 0) {
                // Ability 0: Banish self (wake Chessman allies — effect queued by ability body)
                MZMove($player, $mzCard, "myBanish");
                DecisionQueueController::CleanupRemovedCards();
            } else if($abilityIndex == 1) {
                // Ability 1: REST (next Chessman -2 cost)
                $sourceObj = &GetZoneObject($mzCard);
                $sourceObj->Status = 1;
            }
            break;
        case "JwgigfOaG8": // Lagomorph Piece: (3) reserve + banish (reserve queued by ability body)
            MZMove($player, $mzCard, "myBanish");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "K5qIbjeqQd": // Dropped Band: banish self
            MZMove($player, $mzCard, "myBanish");
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "cMixAGt8zv": // Gustmark Gauge: (2) reserve + REST (reserve queued by ability body)
        case "pv4n1n3gyg": // Cleric Robes - REST
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            break;
        case "sphwpjsznn": // Stonescale Band: (2), REST
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 1);
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 1);
            break;
        case "sqGcyYocLW": // Bairui: sacrifice self
            DoSacrificeFighter($player, $mzCard);
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "tJAIMX3C4R": // Misty Whispertail: sacrifice self
            DecisionQueueController::StoreVariable("mistyWhispertailWasEphemeral", IsEphemeral($mzCard) ? "YES" : "NO");
            DoSacrificeFighter($player, $mzCard);
            DecisionQueueController::CleanupRemovedCards();
            break;
        case "m31WVJ9F04": // Clarent, Sword of Peace — remove a durability counter
            RemoveCounters($player, $mzCard, "durability", 1);
            break;
        case "0D6AfZyKXh": { // Poisoned Dagger — REST + banish self
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            MZMove($player, $mzCard, "myBanish");
            DecisionQueueController::CleanupRemovedCards();
            break;
        }
        case "782mm2tq5l": { // Amorphous Missile — REST + (1) reserve
            $sourceObj = &GetZoneObject($mzCard);
            $sourceObj->Status = 1;
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 1);
            break;
        }
        case "zqw6ms798w": { // Marksman's Charm — banish self
            MZMove($player, $mzCard, "myBanish");
            DecisionQueueController::CleanupRemovedCards();
            break;
        }
        case "yfid3xuxax": { // Winged Talaria — banish self + (2) reserve
            MZMove($player, $mzCard, "myBanish");
            DecisionQueueController::CleanupRemovedCards();
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 1);
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 1);
            break;
        }
    }
}

function DoActivatedAbility($player, $mzCard, $abilityIndex = 0) {
    global $customDQHandlers;
    $sourceObject = &GetZoneObject($mzCard);
    // Capture cardID now — the card may be moved to banishment as a cost below.
    $cardID = $sourceObject->CardID;
    if(GetCounterCount($sourceObject, "frenzy") > 0) return;

    // Cardistry: block activation if already used (per-card, per-game)
    global $Cardistry_Cards;
    $isCardistry = isset($Cardistry_Cards[$cardID]);
    if($isCardistry && isset($sourceObject->Counters['cardistry_used'])) return;

    // Ability index is now passed directly from the frontend button click
    $selectedAbilityIndex = intval($abilityIndex);
    // Exhaust the unit as the REST cost — only for static abilities, not dynamic ones (which have their own costs)
    // Cardistry abilities do NOT rest the card (no REST in their cost)
    $cardType = CardType($cardID);
    $staticAbilityCount = CardActivateAbilityCount($cardID);
    $refractedTwilightCopies = 0;
    if(PropertyContains(CardSubtypes($cardID), "POTION") && $selectedAbilityIndex < $staticAbilityCount) {
        foreach($sourceObject->TurnEffects as $rtIdx => $rtEffect) {
            if($rtEffect === "me0xxw0plq_COPY2") {
                unset($sourceObject->TurnEffects[$rtIdx]);
                $sourceObject->TurnEffects = array_values($sourceObject->TurnEffects);
                $refractedTwilightCopies = 2;
                break;
            }
        }
    }
    $skipAutoRest = in_array($cardID, ["sqGcyYocLW", "tJAIMX3C4R"]);
    if($selectedAbilityIndex < $staticAbilityCount && !$isCardistry && !$skipAutoRest
        && (PropertyContains($cardType, "ALLY") || PropertyContains($cardType, "CHAMPION") || PropertyContains($cardType, "PHANTASIA"))) {
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
        // Enlighten check (blocked by Mana Limiter IC3OU6vCnF)
        $manaLimiterBlocks = false;
        if(PropertyContains($cardType, "CHAMPION")) {
            global $playerID;
            $limiterZone = ($player == $playerID) ? "myField" : "theirField";
            $limiterField = GetZone($limiterZone);
            foreach($limiterField as $lObj) {
                if(!$lObj->removed && $lObj->CardID === "IC3OU6vCnF") {
                    $manaLimiterBlocks = true;
                    break;
                }
            }
        }
        if(!$manaLimiterBlocks && PropertyContains($cardType, "CHAMPION") && GetCounterCount($sourceObject, "enlighten") >= 3) {
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
                    $lrEntry = $lineageReleaseAbilities[$subcardID];
                    if(isset($lrEntry['condition']) && !$lrEntry['condition']($player)) { continue; }
                    if($selectedAbilityIndex == $dynIndex) {
                        // Cost: banish the subcard from the inner lineage
                        array_splice($sourceObject->Subcards, $scIdx, 1);
                        MZAddZone($player, "myBanish", $subcardID);
                        // Effect: execute the registered LR ability
                        $lrEntry['effect']($player);
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
        // Tonoris, Creation's Will (n2jnltv5kl): token weapons gain a sacrifice buff ability.
        if(!$handledDynamic && IsToken($cardID) && PropertyContains(CardType($cardID), "WEAPON")
            && TonorisCreationsWillActive($sourceObject->Controller)) {
            $weaponTargets = array_merge(ZoneSearch("myField", ["WEAPON"]), ZoneSearch("theirField", ["WEAPON"]));
            $weaponTargets = array_values(array_filter($weaponTargets, fn($mz) => $mz !== $mzCard));
            if(!empty($weaponTargets)) {
                if($selectedAbilityIndex == $dynIndex) {
                    $buffAmount = ObjectCurrentPower($sourceObject);
                    DoSacrificeFighter($player, $mzCard);
                    DecisionQueueController::CleanupRemovedCards();
                    $remainingWeapons = array_merge(ZoneSearch("myField", ["WEAPON"]), ZoneSearch("theirField", ["WEAPON"]));
                    if(!empty($remainingWeapons)) {
                        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $remainingWeapons), 1,
                            tooltip:"Target_weapon_gets_+" . $buffAmount . "_POWER");
                        DecisionQueueController::AddDecision($player, "CUSTOM", "TonorisTokenWeaponBuff|" . $buffAmount, 1);
                    }
                    $handledDynamic = true;
                }
                $dynIndex++;
            }
        }
    }
    if(!$isDynamic) {
        // Candlelight Hourglass (fhomy86084): On Charge 2 → opponent's ally activated abilities cost (2) more
        if(PropertyContains(CardType($cardID), "ALLY")) {
            $opponent = ($player == 1) ? 2 : 1;
            global $playerID;
            $oppField = $opponent == $playerID ? "myField" : "theirField";
            $oppZone = GetZone($oppField);
            foreach($oppZone as $chlObj) {
                if(!$chlObj->removed && $chlObj->CardID === "fhomy86084" && !HasNoAbilities($chlObj)
                    && isset($chlObj->Counters['on_charge_triggered'])) {
                    for($ri = 0; $ri < 2; $ri++) {
                        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 1);
                    }
                    break;
                }
            }
        }
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
    if($refractedTwilightCopies > 0) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "RefractedTwilightCopy|" . $cardID . "|" . $selectedAbilityIndex . "|" . $refractedTwilightCopies, 99);
    }

    // Queue Opportunity for the opponent to respond after the ability resolves.
    // Block 200 ensures it runs after all ability decisions (block 1-100).
    DecisionQueueController::AddDecision($player, "CUSTOM", "AbilityOpportunity", 200);

    $dqController = new DecisionQueueController();
    $dqController->ExecuteStaticMethods($player, "-");
}

function OnLeaveField($player, $mzID) {
    global $leaveFieldAbilities, $Renewable_Cards;
    $obj = GetZoneObject($mzID);
    if($obj === null) return;
    $controller = $obj->Controller;
    // Weapons leaving the field: any loaded cards (Subcards) must go to the graveyard
    // as independent cards. Rule: "Cards Loaded into an object will still be Loaded as
    // long as that object remains on the field." Once it leaves, loaded cards are released.
    // Renewable ammo goes to the material zone instead of the graveyard.
    if(PropertyContains(EffectiveCardType($obj), "WEAPON")
       && is_array($obj->Subcards) && !empty($obj->Subcards)) {
        foreach($obj->Subcards as $loadedCardID) {
            $dest = isset($Renewable_Cards[$loadedCardID]) ? "myMaterial" : "myGraveyard";
            MZAddZone($controller, $dest, $loadedCardID);
        }
        $obj->Subcards = [];
    }
    // Check and break any Link connections involving the departing card
    CheckAndBreakLinks($player, $mzID);
    DecisionQueueController::CleanupRemovedCards();
    if(!HasNoAbilities($obj) && isset($leaveFieldAbilities[$obj->CardID . ":0"])) $leaveFieldAbilities[$obj->CardID . ":0"]($controller);
}

function MoveEffectStackCardToField($player, $mzCard) {
    $stackObj = GetZoneObject($mzCard);
    $cardID = $stackObj !== null ? $stackObj->CardID : "";
    DecisionQueueController::StoreVariable("EffectStackEnterCardID", $cardID);
    DecisionQueueController::StoreVariable("EffectStackEnterController", strval($player));
    $obj = MZMove($player, $mzCard, "myField");
    DecisionQueueController::StoreVariable("EffectStackEnterCardID", "");
    DecisionQueueController::StoreVariable("EffectStackEnterController", "");
    return $obj;
}

function DoAllyDestroyed($player, $mzCard) {
    global $allyDestroyedAbilities;
    $obj = GetZoneObject($mzCard);
    // Immortality: ally survives instead of being destroyed, remove all damage
    if(HasImmortality($obj)) {
        $obj->Damage = 0;
        return;
    }
    $controller = $obj->Controller;
    $suppressed = HasNoAbilities($obj);
    if($obj->CardID === "ejvddohjdu") {
        DecisionQueueController::StoreVariable("LustrousSlimeBuffCount", strval(GetCounterCount($obj, "buff")));
    }
    OnLeaveField($player, $mzCard);
    // Xiao Qiao, Cinderkeeper (3hgldrogit): if unit was hit by Xiao Qiao this turn, banish instead
    $xiaoQiaoBanish = in_array("HIT_BY_3hgldrogit", $obj->TurnEffects);
    // Corhazi Arsonist (0ejcyuvuxn): if hit by Corhazi Arsonist this turn, banish instead
    $corhaziArsonistBanish = in_array("HIT_BY_0ejcyuvuxn", $obj->TurnEffects);
    // Fireworks Display (sx6q3p6i0i): banish instead of graveyard
    $fireworksBanish = GlobalEffectCount($controller, "FIREWORKS_BANISH") > 0;
    // Ephemeral: object is banished instead of leaving the field
    $isEphemeral = IsEphemeral($obj);
    if(IsRenewable($obj->CardID) && !$fireworksBanish && !$xiaoQiaoBanish && !$corhaziArsonistBanish && !$isEphemeral) {
        // Renewable: goes to material deck instead of graveyard/banish
        $dest = $player == $controller ? "myMaterial" : "theirMaterial";
    } else if($fireworksBanish || $xiaoQiaoBanish || $corhaziArsonistBanish || $isEphemeral) {
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
    if(DecisionQueueController::GetVariable("CombatTarget") == $mzCard) {
        DecisionQueueController::StoreVariable("CombatTarget", null);
    }
    MZMove($player, $mzCard, $dest);
    if(!$suppressed && isset($allyDestroyedAbilities[$obj->CardID . ":0"])) {
        $allyDestroyedAbilities[$obj->CardID . ":0"]($controller);
    }
    // Glimmer Essence Amulet (dy4urpjbjm): if a phantasia you control is destroyed on an opponent's turn,
    // you may banish each Amulet you control to draw a card.
    if(PropertyContains(CardType($obj->CardID), "PHANTASIA")) {
        $turnPlayer = GetTurnPlayer();
        if($turnPlayer != $controller) {
            global $playerID;
            $controllerField = $controller == $playerID ? "myField" : "theirField";
            $field = GetZone($controllerField);
            for($gi = 0; $gi < count($field); ++$gi) {
                if(!$field[$gi]->removed && $field[$gi]->CardID === "dy4urpjbjm" && !HasNoAbilities($field[$gi])) {
                    DecisionQueueController::AddDecision($controller, "YESNO", "-", 1,
                        tooltip:"Banish_Glimmer_Essence_Amulet_to_draw_a_card?");
                    DecisionQueueController::AddDecision($controller, "CUSTOM", "GlimmerEssenceAmuletChoice|$gi", 1);
                }
            }
        }
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
    // Worn Gearblade (r1o0qtb31x): whenever an Automaton ally you control dies, put a durability counter on it.
    if(PropertyContains(CardSubtypes($obj->CardID), "AUTOMATON")) {
        global $playerID;
        $controllerField = $controller == $playerID ? "myField" : "theirField";
        $field = GetZone($controllerField);
        for($wi = 0; $wi < count($field); ++$wi) {
            if(!$field[$wi]->removed && $field[$wi]->CardID === "r1o0qtb31x" && !HasNoAbilities($field[$wi])) {
                AddCounters($controller, $controllerField . "-" . $wi, "durability", 1);
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
    // Siphoning Fractal (j43qiwqjt0): whenever an ally dies, deal 1 to the opposing champion and recover 1
    {
        global $playerID;
        for($fractPlayer = 1; $fractPlayer <= 2; ++$fractPlayer) {
            $fractFieldName = $fractPlayer == $playerID ? "myField" : "theirField";
            $fractField = GetZone($fractFieldName);
            foreach($fractField as $fractObj) {
                if(!$fractObj->removed && $fractObj->CardID === "j43qiwqjt0" && !HasNoAbilities($fractObj)) {
                    $opponent = $fractPlayer == 1 ? 2 : 1;
                    DealChampionDamage($opponent, 1);
                    RecoverChampion($fractPlayer, 1);
                }
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
    // Perdition (nlf619svrr): granted On Death effect.
    if(in_array("nlf619svrr_ONDEATH", $obj->TurnEffects ?? [])) {
        $theirUnits = array_merge(ZoneSearch("theirField", ["ALLY", "CHAMPION"]), []);
        foreach($theirUnits as $unitMZ) {
            DealDamage($controller, $mzCard, $unitMZ, 1);
        }
    }
    // Crest of the Alliance (ojwk0pw0y6): fostered ally death can banish Crest to draw.
    if(IsFostered($obj)) {
        global $playerID;
        $controllerField = $controller == $playerID ? "myField" : "theirField";
        $field = GetZone($controllerField);
        foreach($field as $ci => $crestObj) {
            if(!$crestObj->removed && $crestObj->CardID === "ojwk0pw0y6" && !HasNoAbilities($crestObj)) {
                DecisionQueueController::AddDecision($controller, "YESNO", "-", 1, tooltip:"Banish_Crest_of_the_Alliance_to_draw_a_card?");
                DecisionQueueController::AddDecision($controller, "CUSTOM", "CrestAllianceChoice|" . $controllerField . "-" . $ci, 1);
                break;
            }
        }
    }
    // Jianye, Dawn's Keep (4ms1r3hjxp): [CB] Fire element allies have On Death: if influence <= 6, draw a card
    if(CardElement($obj->CardID) === "FIRE" && PropertyContains(EffectiveCardType($obj), "ALLY") && !$suppressed) {
        global $playerID;
        $controllerField = $controller == $playerID ? "myField" : "theirField";
        $field = GetZone($controllerField);
        foreach($field as $jianyeObj) {
            if(!$jianyeObj->removed && $jianyeObj->CardID === "4ms1r3hjxp" && !HasNoAbilities($jianyeObj)
                && IsClassBonusActive($controller, ["TAMER", "WARRIOR"])) {
                if(GetInfluence($controller) <= 6) {
                    Draw($controller, 1);
                }
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
    // Death Essence Amulet (ddag7ue0k7): whenever an ally you control dies while it's not your turn,
    // you may banish Death Essence Amulet to look at opponent's hand or memory and discard a card.
    {
        $turnPlayer = GetTurnPlayer();
        if($turnPlayer != $controller && PropertyContains(EffectiveCardType($obj), "ALLY")) {
            global $playerID;
            $controllerField = $controller == $playerID ? "myField" : "theirField";
            $deaField = GetZone($controllerField);
            for($dei = 0; $dei < count($deaField); ++$dei) {
                if(!$deaField[$dei]->removed && $deaField[$dei]->CardID === "ddag7ue0k7" && !HasNoAbilities($deaField[$dei])) {
                    DecisionQueueController::AddDecision($controller, "YESNO", "-", 1, tooltip:"Banish_Death_Essence_Amulet?");
                    DecisionQueueController::AddDecision($controller, "CUSTOM", "DeathEssenceAmuletBanish|$dei", 1);
                    break;
                }
            }
        }
    }
    // Phantasmagoria: [Alice Bonus] whenever a Specter ally you control dies, put a haunt counter
    if(PropertyContains(EffectiveCardSubtypes($obj), "SPECTER") && PropertyContains(EffectiveCardType($obj), "ALLY")) {
        if(IsAliceBonusActive($controller) && HasPhantasmagoria($controller)) {
            AddHauntToMastery($controller, 1);
        }
    }
    // Etherealys' Promise (7n0bv1sqgb): whenever an ally you control dies,
    // put a refinement counter on it. If 3+, may banish to draw.
    {
        global $playerID;
        $controllerField = $controller == $playerID ? "myField" : "theirField";
        $field = GetZone($controllerField);
        for($epi = 0; $epi < count($field); ++$epi) {
            if(!$field[$epi]->removed && $field[$epi]->CardID === "7n0bv1sqgb" && !HasNoAbilities($field[$epi])) {
                AddCounters($controller, $controllerField . "-" . $epi, "refinement", 1);
                if(GetCounterCount($field[$epi], "refinement") >= 3) {
                    DecisionQueueController::AddDecision($controller, "YESNO", "-", 1,
                        tooltip:"Banish_Etherealys'_Promise_to_draw_a_card?");
                    DecisionQueueController::AddDecision($controller, "CUSTOM", "EtherealysPromiseBanish|$epi", 1);
                }
            }
        }
    }
    // Companion Fatestone (izf4wdsbz9): whenever a Fatebound ally you control dies, you may transform CARDNAME
    if(PropertyContains(EffectiveCardSubtypes($obj), "FATEBOUND") && PropertyContains(EffectiveCardType($obj), "ALLY")) {
        global $playerID;
        $controllerField = $controller == $playerID ? "myField" : "theirField";
        $field = GetZone($controllerField);
        for($cfi = 0; $cfi < count($field); ++$cfi) {
            if(!$field[$cfi]->removed && $field[$cfi]->CardID === "izf4wdsbz9" && !HasNoAbilities($field[$cfi])) {
                DecisionQueueController::AddDecision($controller, "YESNO", "-", 1,
                    tooltip:"Transform_Companion_Fatestone_into_Fatebound_Caracal?");
                DecisionQueueController::AddDecision($controller, "CUSTOM", "CompanionFatestoneTransform|$cfi", 1);
                break;
            }
        }
    }
}

function WakeUpPhase() {
    $currentTurn = intval(GetTurnNumber());
    if($currentTurn === 1) return;

    // Wake Up phase — ready all cards on the turn player's field
    SetFlashMessage("Wake Up Phase");
    $turnPlayer = &GetTurnPlayer();
    $otherPlayer = ($turnPlayer == 1) ? 2 : 1;
    $field = GetField($turnPlayer);

    // Check if opponent controls Snow Fairy (4s0c9XgLg7)
    $opponentField = GetField($otherPlayer);
    $opponentHasSnowFairy = false;
    foreach($opponentField as $opp) {
        if(!$opp->removed && $opp->CardID === "4s0c9XgLg7") {
            $opponentHasSnowFairy = true;
            break;
        }
    }

    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed) {
            // Briar, Schwartz King (r1zd9ys1qc): can't wake up
            if($field[$i]->CardID === "r1zd9ys1qc") {
                continue;
            }
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
            // Vainglory Retribution (qtzsekkjn3): next-turn weaponless attack buff becomes active.
            if(in_array("VAINGLORY_NEXT_TURN", $field[$i]->TurnEffects)) {
                $field[$i]->TurnEffects = array_values(array_diff($field[$i]->TurnEffects, ["VAINGLORY_NEXT_TURN"]));
                $field[$i]->TurnEffects[] = "VAINGLORY_ACTIVE";
            }
            // TAUNT_NEXT_TURN / VIGOR_NEXT_TURN: expire at beginning of controller's next turn
            if(in_array("TAUNT_NEXT_TURN", $field[$i]->TurnEffects)) {
                $field[$i]->TurnEffects = array_values(array_diff($field[$i]->TurnEffects, ["TAUNT_NEXT_TURN"]));
            }
            if(in_array("VIGOR_NEXT_TURN", $field[$i]->TurnEffects)) {
                $field[$i]->TurnEffects = array_values(array_diff($field[$i]->TurnEffects, ["VIGOR_NEXT_TURN"]));
            }
            if(in_array("IMMORTALITY_NEXT_TURN", $field[$i]->TurnEffects)) {
                $field[$i]->TurnEffects = array_values(array_diff($field[$i]->TurnEffects, ["IMMORTALITY_NEXT_TURN"]));
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
    // Verita (4qc47amgpp) On Death: convert PENDING global effect to active (expires at end of this turn)
    if(GlobalEffectCount($turnPlayer, "VERITA_POWER_PENDING") > 0) {
        while(RemoveGlobalEffect($turnPlayer, "VERITA_POWER_PENDING")) {}
        AddGlobalEffects($turnPlayer, "VERITA_POWER");
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
    // Tonoris, Creation's Will (n2jnltv5kl): token summons become Aurousteel Greatswords.
    // This currently auto-replaces instead of prompting for the optional "may" choice.
    if($added->CardID !== "hkurfp66pv" && IsToken($added->CardID) && TonorisCreationsWillActive($player)) {
        $added->CardID = "hkurfp66pv";
    }

    // Full Bloom: whenever an opponent summons a Flowerbud token, deal 2 to each champion
    // that opponent controls and recover 2.
    if($added->CardID === "yn78t73w1p") {
        $opponent = ($player == 1) ? 2 : 1;
        $oppField = GetField($opponent);
        foreach($oppField as $fbObj) {
            if($fbObj->removed || $fbObj->CardID !== "5WP1TXJo9E" || HasNoAbilities($fbObj)) continue;
            if(!IsDiaoChanBonus($opponent)) continue;
            $sumPlayerField = &GetField($player);
            for($ci = 0; $ci < count($sumPlayerField); ++$ci) {
                if($sumPlayerField[$ci]->removed) continue;
                if(PropertyContains(EffectiveCardType($sumPlayerField[$ci]), "CHAMPION")) {
                    $sumPlayerField[$ci]->Damage += 2;
                }
            }
            RecoverChampion($opponent, 2);
            break;
        }
    }

    // Diablerie: next opposing divine relic regalia enters under Diablerie's controller.
    if(PropertyContains(CardType($added->CardID), "REGALIA") && $added->CardID === "fjne9ri261") {
        $opponent = ($player == 1) ? 2 : 1;
        if(GlobalEffectCount($opponent, "0plqbtjuxz") > 0) {
            $added->Controller = $opponent;
            RemoveGlobalEffect($opponent, "0plqbtjuxz");
        }
    }

    $effectStackEnterCardID = DecisionQueueController::GetVariable("EffectStackEnterCardID");
    $effectStackEnterController = intval(DecisionQueueController::GetVariable("EffectStackEnterController") ?? "0");
    $cameFromEffectStack = ($effectStackEnterCardID === $added->CardID && $effectStackEnterController === $player);

    // Astarte, Celestial Dawn (f0ht2tsn0y): if an opponent's object would enter the field
    // from anywhere except the effect stack, banish it instead.
    if(!$cameFromEffectStack) {
        $opponent = $player == 1 ? 2 : 1;
        $oppField = GetField($opponent);
        foreach($oppField as $oppObj) {
            if(!$oppObj->removed && $oppObj->CardID === "f0ht2tsn0y" && !HasNoAbilities($oppObj)) {
                MZMove($player, "myField-" . (count($field) - 1), "myBanish");
                DecisionQueueController::CleanupRemovedCards();
                return;
            }
        }
    }

    // Track that this card entered the field this turn (for Tempest Downfall etc.)
    $added->TurnEffects[] = "ENTERED_THIS_TURN";

    // Ephemerate: mark the entering card as ephemeral before Enter triggers fire,
    // so that enter abilities (e.g. Vengeful Paramour) can see IsEphemeral() == true.
    $wasEph = DecisionQueueController::GetVariable("wasEphemerated");
    $addedCardType = CardType($added->CardID);
    if($wasEph === "YES" && !PropertyContains($addedCardType, "ACTION") && !PropertyContains($addedCardType, "ATTACK")) {
        MakeEphemeral("myField-" . (count($field) - 1));
    }

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
    // Map of Hidden Passage (2bzajcZZRD): enters the field rested (card text)
    if($added->CardID == "2bzajcZZRD") {
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

    // Wool Brook (lcCGyyNGuM): enters with refinement counter
    if($added->CardID === "lcCGyyNGuM") {
        AddCounters($player, "myField-" . (count($field) - 1), "refinement", 1);
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

    // Key Slime Pudding (4wuq20gvcg): Slime allies entering get additional buff counter
    if(PropertyContains(CardType($added->CardID), "ALLY") || (PropertyContains(CardType($added->CardID), "TOKEN") && PropertyContains(CardSubtypes($added->CardID), "ALLY"))) {
        if(PropertyContains(CardSubtypes($added->CardID), "SLIME")) {
            if(GlobalEffectCount($player, "4wuq20gvcg") > 0) {
                AddCounters($player, "myField-" . (count($field) - 1), "buff", 1);
            }
            if(GlobalEffectCount($player, "mdwbkuhtjm") > 0) {
                AddCounters($player, "myField-" . (count($field) - 1), "buff", 2);
                RemoveGlobalEffect($player, "mdwbkuhtjm");
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

    // Forest Cake (bjx6yo7mm5): [Class Bonus] whenever an Animal or Beast ally enters,
    // you may sacrifice Forest Cake. If you do, put a buff counter on that ally.
    if(PropertyContains(CardType($added->CardID), "ALLY")) {
        $subtypes = CardSubtypes($added->CardID);
        if(PropertyContains($subtypes, "ANIMAL") || PropertyContains($subtypes, "BEAST")) {
            $enteredMZ = "myField-" . (count($field) - 1);
            for($fc = 0; $fc < count($field); ++$fc) {
                if(!$field[$fc]->removed && $field[$fc]->CardID === "bjx6yo7mm5"
                    && !HasNoAbilities($field[$fc])
                    && IsClassBonusActive($player, ["TAMER"])) {
                    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Sacrifice_Forest_Cake_to_put_buff_counter_on_entered_ally?");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "ForestCakeEnterTrigger|myField-" . $fc . "|" . $enteredMZ, 1);
                }
            }
        }
    }

    // Meadowbloom Dryad (cVRIUJdTW5): [Class Bonus] whenever this or another ally enters,
    // put a buff counter on target ally.
    if(PropertyContains(CardType($added->CardID), "ALLY")) {
        $allyTargets = array_merge(ZoneSearch("myField", ["ALLY"]), ZoneSearch("theirField", ["ALLY"]));
        if(!empty($allyTargets)) {
            for($md = 0; $md < count($field); ++$md) {
                if(!$field[$md]->removed && $field[$md]->CardID === "cVRIUJdTW5"
                    && !HasNoAbilities($field[$md])
                    && IsClassBonusActive($player, ["TAMER"])) {
                    if(count($allyTargets) === 1) {
                        AddCounters($player, $allyTargets[0], "buff", 1);
                    } else {
                        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $allyTargets), 1, tooltip:"Choose_target_ally_for_buff_counter_(Meadowbloom_Dryad)");
                        DecisionQueueController::AddDecision($player, "CUSTOM", "MeadowbloomDryadEnter", 1);
                    }
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

    // Gearstride Academy (lxnq80yu75): imbued wind allies get +1 POWER on Enter
    if(PropertyContains(CardType($added->CardID), "ALLY") && CardElement($added->CardID) === "WIND") {
        $isImbued = DecisionQueueController::GetVariable("isImbued");
        if($isImbued === "YES") {
            foreach($field as $academyObj) {
                if(!$academyObj->removed && $academyObj->CardID === "lxnq80yu75" && !HasNoAbilities($academyObj)) {
                    AddTurnEffect("myField-" . (count($field) - 1), "lxnq80yu75");
                    break;
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

    // Lunete, Frostbinder Priest (TqCo3xlf93): allies your opponent controls enter the field rested
    if(PropertyContains(CardType($added->CardID), "ALLY")) {
        $opponent = ($player == 1) ? 2 : 1;
        global $playerID;
        $oppLuneteZone = $opponent == $playerID ? "myField" : "theirField";
        $oppField = GetZone($oppLuneteZone);
        foreach($oppField as $lnObj) {
            if(!$lnObj->removed && $lnObj->CardID === "TqCo3xlf93" && !HasNoAbilities($lnObj)) {
                $added->Status = 1;
                break;
            }
        }
    }

    // Shattered Hope (XOevViFTB3): allies that enter under your control get sheen counter
    if(PropertyContains(CardType($added->CardID), "ALLY")) {
        if(GlobalEffectCount($player, "SHATTERED_HOPE_SHEEN") > 0) {
            AddCounters($player, "myField-" . (count($field) - 1), "sheen", 1);
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

    // Brusque Neige (irt72g89zc): allies enter the field rested this turn
    if(PropertyContains(CardType($added->CardID), "ALLY")) {
        for($bnp = 1; $bnp <= 2; ++$bnp) {
            if(GlobalEffectCount($bnp, "BRUSQUE_NEIGE_RESTED") > 0) {
                $added->Status = 1;
                break;
            }
        }
    }

    if(PropertyContains(CardType($added->CardID), "ALLY")) {
        for($ebp = 1; $ebp <= 2; ++$ebp) {
            if(GlobalEffectCount($ebp, "k1l75tlzsm_RESTED") > 0) {
                $added->Status = 1;
                break;
            }
        }
    }

    // Polkhawk, Boisterous Riot (8eyeqhc37y): next Ranger ally enters distant
    if(PropertyContains(CardType($added->CardID), "ALLY")
       && PropertyContains(EffectiveCardClasses($added), "RANGER")) {
        for($pkp = 1; $pkp <= 2; ++$pkp) {
            if(GlobalEffectCount($pkp, "POLKHAWK_NEXT_RANGER_DISTANT") > 0) {
                BecomeDistant($added);
                RemoveGlobalEffect($pkp, "POLKHAWK_NEXT_RANGER_DISTANT");
                break;
            }
        }
    }

    // Unmoored Call (etobC7HEHw): objects with chosen reserve cost enter rested
    for($ucp = 1; $ucp <= 2; ++$ucp) {
        for($ucn = 0; $ucn <= 15; ++$ucn) {
            if(GlobalEffectCount($ucp, "UNMOORED_CALL_" . $ucn) > 0) {
                $addedCost = CardCost_reserve($added->CardID);
                if($addedCost == $ucn) {
                    $added->Status = 1;
                }
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
            $kField = GetField($kp);
            $hasRuins = false;
            foreach($kField as $kObj) {
                if(!$kObj->removed && $kObj->CardID === "fei7chsbal" && !HasNoAbilities($kObj)) {
                    $hasRuins = true;
                    break;
                }
            }
            if($hasRuins) {
                // Check if player has cards in hand to pay (1)
                $hand = GetHand($player);
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

    // Mad Tea Party (eaxvvj7hz3): whenever an ally enters the field, controller recovers 1
    if(PropertyContains(CardType($added->CardID), "ALLY")
        || (PropertyContains(CardType($added->CardID), "TOKEN") && PropertyContains(CardSubtypes($added->CardID), "ALLY"))) {
        for($mtpP = 1; $mtpP <= 2; ++$mtpP) {
            $mtpField = GetField($mtpP);
            foreach($mtpField as $mtpObj) {
                if(!$mtpObj->removed && $mtpObj->CardID === "eaxvvj7hz3" && !HasNoAbilities($mtpObj)) {
                    RecoverChampion($mtpP, 1);
                    break;
                }
            }
        }
    }

    // Wildgrowth Fatestone (x2oydmfcre): [Guo Jia Bonus] whenever another wind element card
    // enters the field under your control, put a buff counter; 6+ may transform
    WildgrowthFatestoneOnEnterCheck($player, "myField-" . (count($field) - 1));

    if(DecisionQueueController::GetVariable("SuppressNextEnter") === "YES") {
        DecisionQueueController::ClearVariable("SuppressNextEnter");
        return;
    }

    Enter($player, $field[count($field)-1]->GetMzID());
}

// Airship Captain (t9hreqhj1t): deal 2 damage to chosen champion
$customDQHandlers["AirshipCaptainDamage"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    DealDamage($player, "t9hreqhj1t", $lastDecision, 2);
};

// Forest Cake (bjx6yo7mm5): resolve class bonus enter trigger
$customDQHandlers["ForestCakeEnterTrigger"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $forestMZ = $parts[0] ?? "";
    $enteredMZ = $parts[1] ?? "";
    if($forestMZ === "" || $enteredMZ === "") return;

    $forestObj = GetZoneObject($forestMZ);
    if($forestObj === null || $forestObj->removed || $forestObj->CardID !== "bjx6yo7mm5") return;
    AllyDestroyed($player, $forestMZ);
    DecisionQueueController::CleanupRemovedCards();

    $enteredObj = GetZoneObject($enteredMZ);
    if($enteredObj === null || $enteredObj->removed) return;
    AddCounters($player, $enteredMZ, "buff", 1);
};

// Meadowbloom Dryad (cVRIUJdTW5): choose target ally and put a buff counter
$customDQHandlers["MeadowbloomDryadEnter"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    AddCounters($player, $lastDecision, "buff", 1);
};

// Flamewreath Call (c8wwslgbvr): first optional ally target
$customDQHandlers["FlamewreathCallFirstTarget"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $sourceMZ = DecisionQueueController::GetVariable("flamewreathSource");
    if(empty($sourceMZ)) $sourceMZ = "c8wwslgbvr";

    DealDamage($player, $sourceMZ, $lastDecision, 3);

    $allies = array_merge(ZoneSearch("myField", ["ALLY"]), ZoneSearch("theirField", ["ALLY"]));
    $remaining = array_values(array_filter($allies, fn($mz) => $mz !== $lastDecision));
    if(empty($remaining)) return;

    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $remaining), 1, tooltip:"Deal_3_damage_to_target_ally_(2/2)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "FlamewreathCallSecondTarget", 1);
};

// Flamewreath Call (c8wwslgbvr): second optional ally target
$customDQHandlers["FlamewreathCallSecondTarget"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $sourceMZ = DecisionQueueController::GetVariable("flamewreathSource");
    if(empty($sourceMZ)) $sourceMZ = "c8wwslgbvr";
    DealDamage($player, $sourceMZ, $lastDecision, 3);
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

// Refabrication (cri23mf3vs): optional alternate activation cost (sacrifice two tokens)
$customDQHandlers["RefabricationAltCostChoice"] = function($player, $parts, $lastDecision) {
    $reserveCost = intval($parts[0]);
    if($lastDecision !== "YES") {
        DecisionQueueController::StoreVariable("additionalCostPaid", "NO");
        for($i = 0; $i < $reserveCost; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
        return;
    }

    $tokens = ZoneSearch("myField", ["TOKEN"]);
    if(count($tokens) < 2) {
        DecisionQueueController::StoreVariable("additionalCostPaid", "NO");
        for($i = 0; $i < $reserveCost; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
        return;
    }

    DecisionQueueController::StoreVariable("additionalCostPaid", "YES");
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $tokens), 100, tooltip:"Sacrifice_token_(1_of_2)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "RefabricationAltCostSac1", 100);
};

$customDQHandlers["RefabricationAltCostSac1"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    DoSacrificeFighter($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();

    $tokens = ZoneSearch("myField", ["TOKEN"]);
    if(empty($tokens)) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
        return;
    }
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $tokens), 100, tooltip:"Sacrifice_token_(2_of_2)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "RefabricationAltCostSac2", 100);
};

$customDQHandlers["RefabricationAltCostSac2"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    DoSacrificeFighter($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
};

// Fractal of Refreshment (cxqf8rr452): reveal up to three water memory cards,
// put them on the bottom of the deck, then draw that many cards into memory.
$customDQHandlers["FractalRefreshPick"] = function($player, $parts, $lastDecision) {
    $remaining = intval($parts[0]);
    $selectedCount = intval(DecisionQueueController::GetVariable("fractalRefreshCount"));

    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        $chosenObj = GetZoneObject($lastDecision);
        if($chosenObj !== null && !$chosenObj->removed && CardElement($chosenObj->CardID) === "WATER") {
            MZMove($player, $lastDecision, "myDeck");
            ++$selectedCount;
            DecisionQueueController::StoreVariable("fractalRefreshCount", strval($selectedCount));
        }
        --$remaining;
    } else {
        $remaining = 0;
    }

    if($remaining <= 0) {
        if($selectedCount > 0) {
            DrawIntoMemory($player, $selectedCount);
        }
        return;
    }

    $waterMemory = ZoneSearch("myMemory", cardElements: ["WATER"]);
    if(empty($waterMemory)) {
        if($selectedCount > 0) {
            DrawIntoMemory($player, $selectedCount);
        }
        return;
    }

    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $waterMemory), 1, tooltip:"Reveal_and_bottom_a_water_card?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "FractalRefreshPick|" . $remaining, 1);
};

function RecollectionPhase() {
    $currentTurn = intval(GetTurnNumber());
    if($currentTurn === 1) return;

    // Recollection phase
    SetFlashMessage("Recollection Phase");
    $turnPlayer = &GetTurnPlayer();
    
    // --- Domain Recollection Upkeep ---
    // Process domain upkeep checks that trigger "at the beginning of your recollection phase".
    // Must run BEFORE memory is returned to hand, since the checks reveal memory cards.
    DomainRecollectionUpkeep($turnPlayer);

    // Incandescent Reliquary (wsycqp2l90): if you have the least influence, draw a card.
    $otherPlayer = ($turnPlayer == 1) ? 2 : 1;
    $turnInfluence = GetInfluence($turnPlayer);
    $otherInfluence = GetInfluence($otherPlayer);
    $field = &GetField($turnPlayer);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "wsycqp2l90" && !HasNoAbilities($field[$i])) {
            if($turnInfluence <= $otherInfluence) {
                Draw($turnPlayer, 1);
            }
        }
    }

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

    // Suited Trickery: clear champion attack tax at the beginning of the caster's next turn
    if(GlobalEffectCount($turnPlayer, "uxhmucm8si") > 0) {
        RemoveGlobalEffect($turnPlayer, "uxhmucm8si");
    }

    // Eminent Lethargy: clear attack tax at the beginning of the caster's next turn
    if(GlobalEffectCount($turnPlayer, "GGRtLQgaYU") > 0) {
        RemoveGlobalEffect($turnPlayer, "GGRtLQgaYU");
    }
    // Kingdom's Divide (qy34r8gffr): clear chosen-name activation tax at beginning of caster's next turn.
    $champMZ = FindChampionMZ($turnPlayer);
    if($champMZ !== null) {
        $champObj = GetZoneObject($champMZ);
        if($champObj !== null && is_array($champObj->TurnEffects)) {
            $champObj->TurnEffects = array_values(array_filter(
                $champObj->TurnEffects,
                fn($e) => strpos($e, "qy34r8gffr-") !== 0
            ));
        }
    }

    // Submerged Fatestone (zfb0pzm6qp): [Guo Jia Bonus] at recollection phase,
    // may banish a floating memory from GY to transform
    SubmergedFatestoneRecollectionTrigger($turnPlayer);

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

    // --- On Charge N System ---
    // "At the beginning of your recollection phase, put a charge counter on each object you control
    // with an untriggered on charge ability. Trigger the first time N charge counters are on it."
    $onChargeCards = [
        "fhomy86084" => 2, // Candlelight Hourglass: On Charge 2
        "uqICHZa3Wz" => 2, // Biding Cinquedea: [Class Bonus] On Charge 2
        "f0jbv5n196" => 3, // Memento Pocketwatch: On Charge 3
    ];
    $field = &GetField($turnPlayer);
    for($i = 0; $i < count($field); ++$i) {
        if($field[$i]->removed) continue;
        $ocCardID = $field[$i]->CardID;
        if(!isset($onChargeCards[$ocCardID])) continue;
        if(HasNoAbilities($field[$i])) continue;
        if(!is_array($field[$i]->Counters)) $field[$i]->Counters = [];
        if(isset($field[$i]->Counters['on_charge_triggered'])) continue; // Already triggered — no more charge counters
        $ocThreshold = $onChargeCards[$ocCardID];
        AddCounters($turnPlayer, "myField-" . $i, "charge", 1);
        if(GetCounterCount($field[$i], "charge") >= $ocThreshold) {
            $field[$i]->Counters['on_charge_triggered'] = 1;
            switch($ocCardID) {
                case "fhomy86084": // Candlelight Hourglass: On Charge 2 → flag that ally activation tax is active
                    $field[$i]->Counters['candlelight_active'] = 1;
                    break;
                case "uqICHZa3Wz": // Biding Cinquedea: [Class Bonus] → +1 POWER until EOT + preparation counter
                    if(IsClassBonusActive($turnPlayer, explode(",", CardClasses("uqICHZa3Wz")))) {
                        AddTurnEffect("myField-" . $i, "uqICHZa3Wz_POWER");
                        $champMZ = FindChampionMZ($turnPlayer);
                        if($champMZ !== null) {
                            AddCounters($turnPlayer, $champMZ, "preparation", 1);
                        }
                    }
                    break;
                case "f0jbv5n196": // Memento Pocketwatch: On Charge 3 → banish self, draw 1, next attack +3 POWER
                    MZMove($turnPlayer, "myField-" . $i, "myBanish");
                    DecisionQueueController::CleanupRemovedCards();
                    Draw($turnPlayer, 1);
                    AddGlobalEffects($turnPlayer, "f0jbv5n196_NEXT_ATTACK");
                    break;
            }
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
                case "rz1bqry41l": // Merlin, Kingslayer: add a level counter; even total -> draw and attacks +2
                    if(!HasNoAbilities($field[$i])) {
                        AddCounters($turnPlayer, "myField-" . $i, "level", 1);
                        $merlinObj = GetZoneObject("myField-" . $i);
                        if($merlinObj !== null && GetCounterCount($merlinObj, "level") % 2 === 0) {
                            Draw($turnPlayer, 1);
                            AddTurnEffect("myField-" . $i, "rz1bqry41l");
                        }
                    }
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
                case "jlAc0wWlDZ": // Eager Page: if you haven't materialized this turn, put a buff counter on it
                    if(!HasNoAbilities($field[$i]) && MaterializeCallCount($turnPlayer) === 0) {
                        AddCounters($turnPlayer, "myField-" . $i, "buff", 1);
                    }
                    break;
                case "s7a4tm04ll": // Sage's Urn: add an age counter up to four
                    if(!HasNoAbilities($field[$i]) && GetCounterCount($field[$i], "age") < 4) {
                        AddCounters($turnPlayer, "myField-" . $i, "age", 1);
                    }
                    break;
                case "ci00l7pqcx": // Berserker Plate: deal 3 unpreventable to your champion, then draw a card
                    if(!HasNoAbilities($field[$i])) {
                        for($ci = 0; $ci < count($field); ++$ci) {
                            if(!$field[$ci]->removed && PropertyContains(EffectiveCardType($field[$ci]), "CHAMPION")) {
                                $field[$ci]->Damage += 3;
                                break;
                            }
                        }
                        Draw($turnPlayer, 1);
                    }
                    break;
                case "dIEAN4J4YS": // Arcanist's Prism: wheel memory into deck bottom, then draw that many cards
                    if(!HasNoAbilities($field[$i])) {
                        $memoryCount = count(GetZone("myMemory"));
                        for($mi = $memoryCount - 1; $mi >= 0; --$mi) {
                            MZMove($turnPlayer, "myMemory-" . $mi, "myDeck");
                        }
                        if($memoryCount > 0) {
                            Draw($turnPlayer, $memoryCount);
                        }
                    }
                    break;
                case "c7wklzjmwu": // Palatial Concourse: glimpse 1 at beginning of recollection phase
                    if(!HasNoAbilities($field[$i])) {
                        Glimpse($turnPlayer, 1);
                    }
                    break;
                case "qktid6zlyt": // Kaleidoscope Barrette: empower X, then preserve top card if X >= 4
                    if(!HasNoAbilities($field[$i])) {
                        $phantasiaCount = count(ZoneSearch("myField", ["PHANTASIA"]));
                        Empower($turnPlayer, $phantasiaCount, "qktid6zlyt");
                        if($phantasiaCount >= 4) {
                            PutTopDeckCardIntoMaterialPreserved($turnPlayer);
                        }
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
                case "9Kgr2prI9E": // Slimecall Cyclone: [Class Bonus] summon Baby Slime token
                    if(!HasNoAbilities($field[$i]) && IsClassBonusActive($turnPlayer, ["TAMER"])) {
                        MZAddZone($turnPlayer, "myField", "bdPYKwzWt5");
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
                case "0z2snsdwmx": // Scale of Souls: [CB] Balance — if hand == memory count, recover 2
                    if(!HasNoAbilities($field[$i]) && IsClassBonusActive($turnPlayer, ["CLERIC"])) {
                        $hand = &GetHand($turnPlayer);
                        $mem = &GetMemory($turnPlayer);
                        if(count($hand) == count($mem)) {
                            RecoverChampion($turnPlayer, 2);
                        }
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
                case "w6OqqsfEso": // Sovereign Sanctuary: sacrifice self and draw into memory
                    if(!HasNoAbilities($field[$i])) {
                        MZMove($turnPlayer, "myField-" . $i, "myGraveyard");
                        DecisionQueueController::CleanupRemovedCards();
                        DrawIntoMemory($turnPlayer, 1);
                    }
                    break;
                case "WWlknyTxGA": // Wavekeeper's Bond: [Level 3+] may sacrifice to draw into memory
                    if(!HasNoAbilities($field[$i]) && PlayerLevel($turnPlayer) >= 3) {
                        DecisionQueueController::AddDecision($turnPlayer, "YESNO", "-", 1, tooltip:"Sacrifice_Wavekeeper's_Bond_to_draw_into_memory?");
                        DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "WavekeepersBondSacrifice|$i", 1);
                    }
                    break;
                case "6gN5KjqRW5": // Weaponsmith: [CB] put durability counter on target weapon you control
                    if(!HasNoAbilities($field[$i]) && IsClassBonusActive($turnPlayer, ["WARRIOR"])) {
                        $weapons = [];
                        for($w = 0; $w < count($field); ++$w) {
                            if($field[$w]->removed) continue;
                            if(PropertyContains(EffectiveCardType($field[$w]), "WEAPON")) {
                                $weapons[] = "myField-" . $w;
                            }
                        }
                        if(count($weapons) == 1) {
                            AddCounters($turnPlayer, $weapons[0], "durability", 1);
                        } elseif(count($weapons) > 1) {
                            DecisionQueueController::AddDecision($turnPlayer, "MZCHOOSE", implode("&", $weapons), 1,
                                tooltip:"Put_durability_counter_on_weapon_(Weaponsmith)");
                            DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "WeaponsmithDurability", 1);
                        }
                    }
                    break;
                case "czvy67nbin": // Prismatic Codex: put an age counter at beginning of recollection phase
                    if(!HasNoAbilities($field[$i])) {
                        AddCounters($turnPlayer, "myField-" . $i, "age", 1);
                    }
                    break;
                default: break;
            }
        }
    }

    // Magnificent Banquet (TQoTD8eGQH): if preserved in material deck, recover 1 at recollection
    {
        global $playerID;
        $matDeckZone = $turnPlayer == $playerID ? "myMaterial" : "theirMaterial";
        $matDeck = GetZone($matDeckZone);
        for($mbi = 0; $mbi < count($matDeck); ++$mbi) {
            if(!$matDeck[$mbi]->removed && $matDeck[$mbi]->CardID === "TQoTD8eGQH") {
                RecoverChampion($turnPlayer, 1);
                break; // Only one Magnificent Banquet trigger per recollection
            }
        }
    }

    // Zander, Blinding Steel (UAF6Nr7GUE): at beginning of your recollection, reveal memory,
    // for each luxem card, opponent puts a card from hand into memory
    if(ChampionHasInLineage($turnPlayer, "UAF6Nr7GUE")) {
        global $playerID;
        $memZone = $turnPlayer == $playerID ? "myMemory" : "theirMemory";
        $mem = GetZone($memZone);
        $luxemCount = 0;
        foreach($mem as $mObj) {
            if(!$mObj->removed && CardElement($mObj->CardID) === "LUXEM") {
                $luxemCount++;
            }
        }
        if($luxemCount > 0) {
            $opponent = ($turnPlayer == 1) ? 2 : 1;
            ZanderBlindingSteelStep($turnPlayer, $opponent, $luxemCount);
        }
    }

    // Haunting Demise (v0buu5y0ub): inherited recollection damage
    if(ChampionHasInLineage($turnPlayer, "v0buu5y0ub") && !AreCurseLineageAbilitiesSuppressed($turnPlayer)) {
        $champZone = $turnPlayer == $playerID ? "myField" : "theirField";
        $champField = GetZone($champZone);
        for($ci = 0; $ci < count($champField); ++$ci) {
            if(PropertyContains(EffectiveCardType($champField[$ci]), "CHAMPION")) {
                DealUnpreventableDamage($turnPlayer, $champZone . "-" . $ci, $champZone . "-" . $ci, 1);
                break;
            }
        }
    }

    // Curse Amplification (x9z2k2a5ig): curse cards in lineage gain recollection damage.
    if(GlobalEffectCount($turnPlayer, "x9z2k2a5ig") > 0 && !AreCurseLineageAbilitiesSuppressed($turnPlayer)) {
        $champions = ZoneSearch($turnPlayer == $playerID ? "myField" : "theirField", ["CHAMPION"]);
        if(!empty($champions)) {
            foreach(GetCursesInLineage($turnPlayer) as $curse) {
                DealUnpreventableDamage($turnPlayer, $champions[0], $champions[0], 1);
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
    
    // --- Treacle, Drowned Mouse (6emPe9OEUn): [Alice Bonus] At beginning of recollection phase,
    // recover X where X is the amount of Specter allies you control.
    if(IsAliceBonusActive($turnPlayer)) {
        $field = &GetField($turnPlayer);
        for($ti = 0; $ti < count($field); ++$ti) {
            if(!$field[$ti]->removed && $field[$ti]->CardID === "6emPe9OEUn" && !HasNoAbilities($field[$ti])) {
                global $playerID;
                $tZone = $turnPlayer == $playerID ? "myField" : "theirField";
                $specterAllies = ZoneSearch($tZone, ["ALLY"], cardSubtypes: ["SPECTER"]);
                $x = count($specterAllies);
                if($x > 0) RecoverChampion($turnPlayer, $x);
                break;
            }
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

    // Crystallized Anthem (XfAJlQt9hH): at beginning of your next recollection,
    // Memorite objects you control get +1 POWER per 6 sheen until end of turn.
    if(GlobalEffectCount($turnPlayer, "CRYSTALLIZED_ANTHEM_RECOLLECTION") > 0) {
        RemoveGlobalEffect($turnPlayer, "CRYSTALLIZED_ANTHEM_RECOLLECTION");
        $sheenCount = GetSheenCount($turnPlayer);
        $bonus = intval(floor($sheenCount / 6));
        if($bonus > 0) {
            global $playerID;
            $myZone = $turnPlayer == $playerID ? "myField" : "theirField";
            $memorites = ZoneSearch($myZone, cardSubtypes: ["MEMORITE"]);
            foreach($memorites as $memMZ) {
                AddTurnEffect($memMZ, "CRYSTALLIZED_ANTHEM_POWER_" . $bonus);
            }
        }
    }

    // Merlin L1 Inherited Effect: Whenever an opponent recollects 3+ cards,
    // for every 3 cards recollected, they put a sheen counter on a unit they control.
    $opponent = ($turnPlayer == 1) ? 2 : 1;
    if(IsMerlinBonusActive($opponent) && count($memory) >= 3) {
        $sheenSets = intval(floor(count($memory) / 3));
        global $playerID;
        $oppZone = $turnPlayer == $playerID ? "myField" : "theirField";
        $oppUnits = ZoneSearch($oppZone, ["ALLY", "CHAMPION"]);
        if(!empty($oppUnits)) {
            for($s = 0; $s < $sheenSets; ++$s) {
                $unitStr = implode("&", $oppUnits);
                DecisionQueueController::AddDecision($turnPlayer, "MZCHOOSE", $unitStr, 1, tooltip:"Put_a_sheen_counter_on_a_unit_you_control_(Merlin_Inherited)");
                DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "MerlinInheritedSheen|1", 1);
            }
        }
    }

    // Spark Fairy (FWnxKjSeB1): objects tagged with _sparkFairy deal 1 unpreventable damage to controller's champion
    $tpField = GetZone("myField");
    for($sfi = 0; $sfi < count($tpField); ++$sfi) {
        if($tpField[$sfi]->removed) continue;
        if(!isset($tpField[$sfi]->Counters['_sparkFairy'])) continue;
        $sfController = $tpField[$sfi]->Counters['_sparkFairy'];
        global $playerID;
        $sfZone = ($sfController == $playerID) ? "myField" : "theirField";
        $sfField = GetZone($sfZone);
        $sfExists = false;
        foreach($sfField as $sfObj) {
            if(!$sfObj->removed && $sfObj->CardID === "FWnxKjSeB1" && !HasNoAbilities($sfObj)) {
                $sfExists = true;
                break;
            }
        }
        if($sfExists) {
            DealChampionDamage($turnPlayer, 1);
        }
    }

    $recollectReduce = 0;
    $opponent = ($turnPlayer == 1) ? 2 : 1;
    $oppField = GetField($opponent);
    foreach($oppField as $fObj) {
        if($fObj->removed || $fObj->CardID !== "DNe5dvCNA1" || HasNoAbilities($fObj)) continue;
        $recollectReduce += GetCounterCount($fObj, "frost");
    }
    $recollectCount = max(0, count($memory) - $recollectReduce);
    for($i=count($memory)-1; $i>=count($memory)-$recollectCount; --$i) {
        MZMove($turnPlayer, "myMemory-" . $i, "myHand");
    }
}

function DrawPhase() {
    // Draw phase - player draws a card
    $currentTurn = &GetTurnNumber();
    $turnPlayer = &GetTurnPlayer();
    $firstPlayer = &GetFirstPlayer();
    if($currentTurn == 1 && $turnPlayer == $firstPlayer) return;//Starting player skips first draw phase
    // Resolute Stand (o6gb0op3nq): skip this draw phase if effect is active
    if(GlobalEffectCount($turnPlayer, "SKIP_NEXT_DRAW") > 0) {
        RemoveGlobalEffect($turnPlayer, "SKIP_NEXT_DRAW");
        return;
    }
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
function SuppressAlly($player, $mzCard, $skipReplacementCheck = false) {
    $obj = GetZoneObject($mzCard);
    if($obj === null) return;
    // Poisonous Breezecap (e3ldc3r8j7): if would be suppressed, may sacrifice instead
    if(!$skipReplacementCheck && $obj->CardID === "e3ldc3r8j7" && !HasNoAbilities($obj)) {
        $controller = $obj->Controller;
        DecisionQueueController::AddDecision($controller, "YESNO", "-", 1, tooltip:"Sacrifice_Poisonous_Breezecap_instead_of_suppressing?");
        DecisionQueueController::AddDecision($controller, "CUSTOM", "PoisonousBreezecapSuppressReplace|" . $mzCard, 1);
        return;
    }
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
    // Buffeting Hurricane (CjL1WPvWHw): [CB] whenever you suppress an object, deal 2 to target champion
    global $playerID;
    $bhField = &GetField($player);
    $bhZone = $player == $playerID ? "myField" : "theirField";
    for($bhi = 0; $bhi < count($bhField); $bhi++) {
        if(!$bhField[$bhi]->removed && $bhField[$bhi]->CardID === "CjL1WPvWHw" && !HasNoAbilities($bhField[$bhi])
            && IsClassBonusActive($player, ["CLERIC"])) {
            $champTargets = array_merge(
                ZoneSearch("myField", ["CHAMPION"]),
                ZoneSearch("theirField", ["CHAMPION"])
            );
            $champTargets = FilterSpellshroudTargets($champTargets);
            if(!empty($champTargets)) {
                $targetStr = implode("&", $champTargets);
                DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, tooltip:"Buffeting_Hurricane:_deal_2_to_champion");
                DecisionQueueController::AddDecision($player, "CUSTOM", "BuffetingHurricaneDamage", 1);
            }
            break; // Only one Buffeting Hurricane triggers
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

    // Frozen Divinity: sacrifice at end phase if your champion didn't level up this turn.
    if(GlobalEffectCount($turnPlayer, "LEVELED_UP_THIS_TURN") == 0) {
        $field = &GetField($turnPlayer);
        for($i = count($field) - 1; $i >= 0; --$i) {
            if(!$field[$i]->removed && $field[$i]->CardID === "1PrDQ1EX0F" && !HasNoAbilities($field[$i])) {
                DoSacrificeFighter($turnPlayer, "myField-" . $i);
                DecisionQueueController::CleanupRemovedCards();
                break;
            }
        }
    }

    // Agility 3: return up to three cards from memory to hand at beginning of end phase.
    if(GlobalEffectCount($turnPlayer, "AGILITY_3") > 0) {
        for($agi = 0; $agi < 3; ++$agi) {
            $memory = GetMemory($turnPlayer);
            if(empty($memory)) break;
            MZMove($turnPlayer, "myMemory-0", "myHand");
        }
    }

    // Devious Welcome (vz4kc558yx): next end phase discard if chosen type wasn't activated.
    $champMZ = FindChampionMZ($turnPlayer);
    if($champMZ !== null) {
        $champObj = GetZoneObject($champMZ);
        if($champObj !== null) {
            if(in_array("vz4kc558yx-ACTION_PENDING", $champObj->TurnEffects ?? [])) {
                DiscardRandomFromHandAndMemory($turnPlayer);
                $champObj->TurnEffects = array_values(array_filter(
                    $champObj->TurnEffects,
                    fn($e) => $e !== "vz4kc558yx-ACTION_PENDING"
                ));
            }
            if(in_array("vz4kc558yx-ALLY_PENDING", $champObj->TurnEffects ?? [])) {
                DiscardRandomFromHandAndMemory($turnPlayer);
                $champObj->TurnEffects = array_values(array_filter(
                    $champObj->TurnEffects,
                    fn($e) => $e !== "vz4kc558yx-ALLY_PENDING"
                ));
            }
        }
    }

    // Stardust Oracle (EPy8OUmPxa): [Class Bonus] At beginning of end phase, summon Astral Shard token
    $field = &GetField($turnPlayer);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "EPy8OUmPxa" && !HasNoAbilities($field[$i])) {
            if(IsClassBonusActive($turnPlayer, ["CLERIC"])) {
                MZAddZone($turnPlayer, "myField", "eP07Xxscuq");
            }
            break;
        }
    }

    // Meteoric Slime: [CB] beginning of end phase, glimpse 2 then reveal top two and deal X.
    $field = &GetField($turnPlayer);
    for($i = 0; $i < count($field); ++$i) {
        if($field[$i]->removed || $field[$i]->CardID !== "5ybMub985n" || HasNoAbilities($field[$i])) continue;
        if(!IsClassBonusActive($turnPlayer, ["TAMER"])) continue;
        $mz = "myField-" . $i;
        Glimpse($turnPlayer, 2);
        DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "MeteoricSlimeAfterGlimpse|" . $mz, 1);
        break;
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

    // Ciel, Loyal Valet (nn48ne8a05): Inherited Effect —
    // At the beginning of your end phase, you may banish a card from your graveyard or hand
    // and put an omen counter on it.
    if(ChampionHasInLineage($turnPlayer, "nn48ne8a05")) {
        $graveCards = ZoneSearch("myGraveyard");
        $handCards = ZoneSearch("myHand");
        $banishTargets = array_merge($graveCards, $handCards);
        if(!empty($banishTargets)) {
            $targetStr = implode("&", $banishTargets);
            DecisionQueueController::AddDecision($turnPlayer, "MZMAYCHOOSE", $targetStr, 1, tooltip:"Banish_a_card_with_omen_counter?_(Inherited:_Ciel)");
            DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "CielEndPhaseOmen", 1);
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

    // Fabled Azurite Fatestone (6ce5rzrjd9): [Guo Jia Bonus] At beginning of your end phase,
    // you may banish a card at random from your memory. If you do, draw a card.
    $field = &GetField($turnPlayer);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "6ce5rzrjd9" && !HasNoAbilities($field[$i])) {
            if(IsGuoJiaBonus($turnPlayer)) {
                $memory = &GetMemory($turnPlayer);
                if(count($memory) > 0) {
                    DecisionQueueController::AddDecision($turnPlayer, "YESNO", "-", 1, tooltip:"Banish_random_memory_card_to_draw?_(Fabled_Azurite_Fatestone)");
                    DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "FabledAzuriteFatestoneEndPhase", 1);
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

    // Conduit of the Mad Mage (6SXL09rEzS): At beginning of your end phase, sacrifice CARDNAME
    $field = &GetField($turnPlayer);
    for($i = count($field) - 1; $i >= 0; --$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "6SXL09rEzS" && !HasNoAbilities($field[$i])) {
            AllyDestroyed($turnPlayer, "myField-" . $i);
            DecisionQueueController::CleanupRemovedCards();
        }
    }

    FirebloodedOathEndPhase($turnPlayer);

    // Shriveling Vines (6gt6zkly69): At beginning of your end phase, put 2 wither counters
    // on target non-champion non-token object you don't control.
    $field = &GetField($turnPlayer);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "6gt6zkly69" && !HasNoAbilities($field[$i])) {
            $opponentField = GetZone("theirField");
            $validTargets = [];
            for($j = 0; $j < count($opponentField); ++$j) {
                if($opponentField[$j]->removed) continue;
                $ct = EffectiveCardType($opponentField[$j]);
                if(PropertyContains($ct, "CHAMPION") || PropertyContains($ct, "TOKEN")) continue;
                $validTargets[] = "theirField-" . $j;
            }
            if(!empty($validTargets)) {
                if(count($validTargets) == 1) {
                    AddCounters($turnPlayer, $validTargets[0], "wither", 2);
                } else {
                    DecisionQueueController::AddDecision($turnPlayer, "MZCHOOSE", implode("&", $validTargets), 1,
                        tooltip:"Put_2_wither_counters_on_opponent_object_(Shriveling_Vines)");
                    DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "ShrivelingVinesWither", 1);
                }
            }
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

    // Arcane Disposition (blq7qXGvWH): at the beginning of the next end phase, discard your hand.
    for($adp = 1; $adp <= 2; ++$adp) {
        if(GlobalEffectCount($adp, "blq7qXGvWH_DISCARD_NEXT_END") > 0) {
            $hand = &GetHand($adp);
            for($hi = count($hand) - 1; $hi >= 0; --$hi) {
                if(!$hand[$hi]->removed) {
                    DoDiscardCard($adp, "myHand-" . $hi);
                }
            }
            while(RemoveGlobalEffect($adp, "blq7qXGvWH_DISCARD_NEXT_END")) {}
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

    // Nocturnal Blossom (39srnovht1): At the beginning of your end phase, recover 1.
    $field = &GetField($turnPlayer);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "39srnovht1" && !HasNoAbilities($field[$i])) {
            RecoverChampion($turnPlayer, 1);
        }
    }
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

    // Mirrorbound Covenant (PKnOTdQJJ1): each player's max influence is 7.
    // Check both fields for the Unique/Phantasia; if present, enforce cap on turn player.
    $hasMirrorbound = false;
    foreach(array_merge(GetField(1), GetField(2)) as $mbObj) {
        if(!$mbObj->removed && $mbObj->CardID === "PKnOTdQJJ1") { $hasMirrorbound = true; break; }
    }
    if($hasMirrorbound) {
        $tpInfluence = GetInfluence($turnPlayer);
        if($tpInfluence > 7) {
            $excess = $tpInfluence - 7;
            MirrorboundInfluenceDiscard($turnPlayer, $excess);
        }
    }

    // Phantasmagoria (D3rexaXCBo): [Alice Bonus] At beginning of end phase, may put all GY cards
    // on bottom of deck, then mill X where X is haunt counters.
    if(HasPhantasmagoria($turnPlayer) && IsAliceBonusActive($turnPlayer)) {
        $gy = &GetGraveyard($turnPlayer);
        $hasGYCards = false;
        foreach($gy as $g) { if(!$g->removed) { $hasGYCards = true; break; } }
        if($hasGYCards && GetHauntCount($turnPlayer) > 0) {
            DecisionQueueController::AddDecision($turnPlayer, "YESNO", "-", 1,
                tooltip:"Put_all_graveyard_cards_on_bottom_of_deck,_then_mill_X_(haunt_counters)?");
            DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "PhantasmagoriaEndPhase", 1);
        }
    }

    // Merlin L3 (2TCyILvBYa): [Sheen 24+] At beginning of end phase, look at opp memory,
    // banish a card from it. Until end of opponent's next turn, they may activate that card.
    if(GetSheenCount($turnPlayer) >= 24) {
        // Check if champion IS Merlin L3
        $field = &GetField($turnPlayer);
        for($mli = 0; $mli < count($field); ++$mli) {
            if(!$field[$mli]->removed && $field[$mli]->CardID === "2TCyILvBYa" && !HasNoAbilities($field[$mli])) {
                $opponent = ($turnPlayer == 1) ? 2 : 1;
                global $playerID;
                $oppMemZone = $opponent == $playerID ? "myMemory" : "theirMemory";
                $oppMem = GetZone($oppMemZone);
                $memCards = [];
                for($mi = 0; $mi < count($oppMem); ++$mi) {
                    if(!$oppMem[$mi]->removed) {
                        $memCards[] = $oppMemZone . "-" . $mi;
                    }
                }
                if(!empty($memCards)) {
                    $memStr = implode("&", $memCards);
                    DecisionQueueController::AddDecision($turnPlayer, "MZCHOOSE", $memStr, 1, tooltip:"Banish_a_card_from_opponent's_memory_(Merlin_L3)");
                    DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "MerlinL3BanishMemory|" . $opponent, 1);
                }
                break;
            }
        }
    }

    // Tidefate Brooch (vubaywkr69): at end of turn, put refinement counters equal to
    // Fatestone/Fatebound count on the field
    {
        $tpField = &GetField($turnPlayer);
        for($ti = 0; $ti < count($tpField); ++$ti) {
            if(!$tpField[$ti]->removed && $tpField[$ti]->CardID === "vubaywkr69" && !HasNoAbilities($tpField[$ti])) {
                $fsCount = CountFatestoneOrFateboundObjects($turnPlayer);
                if($fsCount > 0) {
                    global $playerID;
                    $tZone = $turnPlayer == $playerID ? "myField" : "theirField";
                    AddCounters($turnPlayer, $tZone . "-" . $ti, "refinement", $fsCount);
                }
            }
        }
    }

    // Huaji of Heaven's Rise (v1iyt8rugx): [Guo Jia Bonus] at end of turn,
    // if champion is exia element, may pay (3) to transform
    if(IsGuoJiaBonus($turnPlayer)) {
        $tpField = &GetField($turnPlayer);
        for($hi = 0; $hi < count($tpField); ++$hi) {
            if(!$tpField[$hi]->removed && $tpField[$hi]->CardID === "v1iyt8rugx" && !HasNoAbilities($tpField[$hi])) {
                // Check if champion is exia element
                $champObj = null;
                foreach($tpField as $cObj) {
                    if(!$cObj->removed && PropertyContains(EffectiveCardType($cObj), "CHAMPION")) {
                        $champObj = $cObj;
                        break;
                    }
                }
                if($champObj !== null && EffectiveCardElement($champObj) === "EXIA") {
                    $hand = &GetHand($turnPlayer);
                    if(count($hand) >= 3) {
                        DecisionQueueController::AddDecision($turnPlayer, "YESNO", "-", 1,
                            tooltip:"Pay_(3)_to_transform_Huaji_of_Heaven's_Rise?");
                        DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "HuajiEndPhaseTransform|" . $hi, 1);
                    }
                }
                break;
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
        // Facet Together (XmsEbk19Iu): clear FACET_POWER_* at end of controller's turn
        foreach($field[$i]->TurnEffects as $te) {
            if(strpos($te, "FACET_POWER_") === 0) {
                $field[$i]->TurnEffects = array_values(array_diff($field[$i]->TurnEffects, [$te]));
                break;
            }
        }
        // DISTANT: expires at end of the controller's turn (not at end of every turn)
        if(in_array("DISTANT", $field[$i]->TurnEffects)) {
            $field[$i]->TurnEffects = array_values(array_diff($field[$i]->TurnEffects, ["DISTANT"]));
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
    // Tweedledee, Contrarian Poet (EwUKdNL4bk): -3 POWER per hit (indefinite)
    $power -= GetCounterCount($obj, "power_loss");
    foreach($obj->TurnEffects ?? [] as $effect) {
        if(strpos($effect, "TONORIS_TOKEN_WEAPON_") === 0) {
            $power += intval(substr($effect, strlen("TONORIS_TOKEN_WEAPON_")));
        }
        if($effect === "CyiA6N2geQ") {
            $power += 1;
        }
        if(strpos($effect, "AxHzxEHBHZ_EMPOWER_") === 0) {
            $power += intval(substr($effect, strlen("AxHzxEHBHZ_EMPOWER_")));
        }
    }
    switch($obj->CardID) { //Self power modifiers
        case "XFWU8KTVW9": // Ghastly Slime: +2 POWER while ephemeral
            if(IsEphemeral($obj)) $power += 2;
            break;
        case "fdnlbJm3hr": // Memorite Obelith: +1 POWER per sheen counter (cap 5)
            $power += min(5, GetCounterCount($obj, "sheen"));
            break;
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
        case "vw2ifz1nr5": // Andronika, Eternal Herald: +1 POWER while imbued
            if(in_array("IMBUED", $obj->TurnEffects ?? [])) {
                $power += 1;
            }
            break;
        case "y1tyo32voa": // Shuang Ji of Sacrifice: +1 POWER per five damage on your champion
            $champion = GetPlayerChampion($obj->Controller);
            if($champion !== null) {
                $power += intdiv(intval($champion->Damage ?? 0), 5);
            }
            break;
        case "csMiEObm2l": // Strapping Conscript: [Class Bonus][Level 2+] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["WARRIOR"]) && PlayerLevel($obj->Controller) >= 2) {
                $power += 1;
            }
            break;
        case "3ppahdhe7g": // Resonant Aether: [Level 2+] +1 POWER
            if(PlayerLevel($obj->Controller) >= 2) {
                $power += 1;
            }
            break;
        case "fvnvknj4dd": // Steel Halberd: [Class Bonus] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["WARRIOR"])) {
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
        case "Q2ugqVm04E": // Curved Dagger: [CB] +1 POWER while attacking an ally
            if(IsClassBonusActive($obj->Controller, ["ASSASSIN"])) {
                $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
                if($combatTarget !== null && $combatTarget !== "-" && $combatTarget !== "") {
                    $targetObj = GetZoneObject($combatTarget);
                    if($targetObj !== null && PropertyContains(EffectiveCardType($targetObj), "ALLY")) {
                        $power += 1;
                    }
                }
            }
            break;
        case "NfbZ0nouSQ": // Lorraine, Crux Knight: attacks get +1 POWER per regalia weapon in banishment
            {
                $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
                if($combatAttacker !== null && $combatAttacker !== "-" && $combatAttacker !== ""
                    && $obj->GetMzID() === $combatAttacker) {
                    global $playerID;
                    $banishZone = $obj->Controller == $playerID ? "myBanish" : "theirBanish";
                    $banishWeapons = ZoneSearch($banishZone, ["WEAPON"]);
                    foreach($banishWeapons as $bwMZ) {
                        $bwObj = GetZoneObject($bwMZ);
                        if($bwObj !== null && PropertyContains(CardType($bwObj->CardID), "REGALIA")) {
                            $power += 1;
                        }
                    }
                }
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
        case "eyvxonorcs": // Deadly Opportunist: +3 POWER while attacking a rested ally
            $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
            if($combatTarget != "-" && $combatTarget != "") {
                $targetObj = GetZoneObject($combatTarget);
                if($targetObj !== null && PropertyContains(EffectiveCardType($targetObj), "ALLY")
                    && isset($targetObj->Status) && $targetObj->Status == 1) {
                    $power += 3;
                }
            }
            break;
        case "vjdbqgku4z": // Horned Knight: +1 POWER while attacking an ally
            {
                $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
                $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
                if($combatAttacker !== null && $combatAttacker !== "-" && $combatAttacker !== ""
                    && $combatTarget !== null && $combatTarget !== "-" && $combatTarget !== ""
                    && $obj->GetMzID() === $combatAttacker) {
                    $targetObj = GetZoneObject($combatTarget);
                    if($targetObj !== null && PropertyContains(EffectiveCardType($targetObj), "ALLY")) {
                        $power += 1;
                    }
                }
            }
            break;
        case "iCgcAFU458": // Golden Rook: +1 POWER while attacking unit with even life stat
            {
                $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
                $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
                if($combatAttacker !== null && $combatAttacker !== "-" && $combatAttacker !== ""
                    && $combatTarget !== null && $combatTarget !== "-" && $combatTarget !== ""
                    && $obj->GetMzID() === $combatAttacker) {
                    $targetObj = GetZoneObject($combatTarget);
                    if($targetObj !== null && ObjectCurrentHP($targetObj) % 2 === 0) {
                        $power += 1;
                    }
                }
            }
            break;
        case "l83tuzrl2a": // Lily, Marine Castellan: On Attack if attacking even-life unit, +1 POWER
            $power += 1;
            break;
        case "zcvq77mdgd": // Sword of Shadows: [CB] +1 POWER; -1 while opponent controls stealth ally
            if(IsClassBonusActive($obj->Controller, ["WARRIOR"])) {
                $power += 1;
                global $playerID;
                $oppZone = $obj->Controller == $playerID ? "theirField" : "myField";
                $oppField = GetZone($oppZone);
                foreach($oppField as $oppObj) {
                    if(!$oppObj->removed && PropertyContains(EffectiveCardType($oppObj), "ALLY") && HasStealth($oppObj)) {
                        $power -= 1;
                        break;
                    }
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
        case "8kmoi0a5uh": // Bulwark Sword: [Class Bonus] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["GUARDIAN"])) $power += 1;
            break;
        case "8n4zw4gq5w": // Sable Remnant: [Class Bonus] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["ASSASSIN"])) $power += 1;
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
        case "pyx8bd7ozu": // Archon Broadsword: [Class Bonus] +1 POWER per token you control
            if(IsClassBonusActive($obj->Controller, ["GUARDIAN"])) {
                global $playerID;
                $zone = $obj->Controller == $playerID ? "myField" : "theirField";
                $power += count(ZoneSearch($zone, ["TOKEN"]));
            }
            break;
        case "m4c8ljyevp": // Academy Attendant: [Class Bonus][Memory 4+] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["CLERIC"])) {
                $memory = &GetMemory($obj->Controller);
                if(count($memory) >= 4) $power += 1;
            }
            break;
        case "tu7jvjf2gh": // Sablier Guard: +1 POWER per distinct omen reserve cost
            $power += GetOmenDistinctCostCount($obj->Controller);
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
        case "lzsmw3rrii": // Imperial Recruit: +1 POWER while fostered
            if(IsFostered($obj)) $power += 1;
            break;
        case "46neis2lho": // Imperial Panzer: [CB] +1 POWER while fostered
            if(IsFostered($obj) && IsClassBonusActive($obj->Controller, ["GUARDIAN"])) $power += 1;
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
        case "pc3zpkw43o": // Vigil Rempart: [CB] +2 POWER
            if(IsClassBonusActive($obj->Controller, ["GUARDIAN"])) $power += 2;
            break;
        case "izgiu216l2": // Torch Marshal: [CB] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["GUARDIAN"])) $power += 1;
            break;
        case "e7782pjg1d": // Armed Squallguard: +1 POWER if you have attack omens
            if(GetOmenCountByType($obj->Controller, "ATTACK") > 0) $power += 1;
            break;
        case "r3bmriltuw": // Fumant Shieldmaiden: +2 POWER if influence < omen count
            if(GetInfluence($obj->Controller) < GetOmenCount($obj->Controller)) $power += 2;
            break;
        case "bfg5ubeczk": // Extorting Blackjack: +10 POWER if total omen cost == 21
            if(GetTotalOmenCost($obj->Controller) === 21) $power += 10;
            break;
        case "vke9gsgfdm": // Conflagrative Trounce: [Ciel Bonus] +2 POWER if 2+ omens with same cost
            if(IsCielBonusActive($obj->Controller) && HasOmensWithSameCost($obj->Controller)) $power += 2;
            break;
        case "L67r0GlRHR": // Vacuous Servant: [Ciel Bonus] +1 POWER per attack omen
            if(IsCielBonusActive($obj->Controller)) $power += GetOmenCountByType($obj->Controller, "ATTACK");
            break;
        case "cworak5y4y": // Whimsy's Warden: +2 POWER while you have two or more omens
            if(GetOmenCount($obj->Controller) >= 2) $power += 2;
            break;
        case "OjOcXBiO0b": // Tyrannical Denigration: [CB] +4 POWER
            if(IsClassBonusActive($obj->Controller, ["WARRIOR"])) $power += 4;
            break;
        case "bsuO8TVe7p": // Siege Mauler: +2 POWER while attacking a domain
            {
                $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
                $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
                if($combatAttacker !== null && $combatAttacker !== "-" && $combatAttacker !== ""
                    && $combatTarget !== null && $combatTarget !== "-" && $combatTarget !== ""
                    && $obj->GetMzID() === $combatAttacker) {
                    $targetObj = GetZoneObject($combatTarget);
                    if($targetObj !== null && IsSiegeable($targetObj)) {
                        $power += 2;
                    }
                }
            }
            break;
        case "dJlNMQ5rWP": // Golden Knight: [Alice Bonus] +1 POWER while attacking unit with intercept or taunt
            {
                $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
                $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
                if($combatAttacker !== null && $combatAttacker !== "-" && $combatAttacker !== ""
                    && $combatTarget !== null && $combatTarget !== "-" && $combatTarget !== ""
                    && $obj->GetMzID() === $combatAttacker
                    && ChampionHasInLineage($obj->Controller, "daip7s9ztd")) {
                    $targetObj = GetZoneObject($combatTarget);
                    if($targetObj !== null && (HasKeyword_Intercept($targetObj) || HasTaunt($targetObj))) {
                        $power += 1;
                    }
                }
            }
            break;
        case "deO56qXfbP": // Off With Her Head: +3 POWER while attacker is attacking a unique ally
            {
                $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
                if($combatTarget !== null && $combatTarget !== "-" && $combatTarget !== "") {
                    $targetObj = GetZoneObject($combatTarget);
                    if($targetObj !== null && PropertyContains(EffectiveCardType($targetObj), "ALLY")
                        && PropertyContains(EffectiveCardType($targetObj), "UNIQUE")) {
                        $power += 3;
                    }
                }
            }
            break;
        case "payjps7DkB": // Fellowship's Gale: +1 POWER per ally you control
            {
                global $playerID;
                $zone = $obj->Controller == $playerID ? "myField" : "theirField";
                $allies = ZoneSearch($zone, ["ALLY"]);
                $power += count($allies);
            }
            break;
        case "IBXLKkBUe1": // Weiss Knight: Commanded Will 1
        case "bGmutHfgMl": // Rowland, Schwartz Knight: Commanded Will 1
            {
                $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
                if($combatAttacker !== null && $combatAttacker !== "-" && $obj->GetMzID() === $combatAttacker) {
                    $intentCards = GetIntentCards($obj->Controller);
                    foreach($intentCards as $iMZ) {
                        $iObj = GetZoneObject($iMZ);
                        if($iObj !== null && PropertyContains(CardSubtypes($iObj->CardID), "COMMAND")) {
                            $power += 1;
                            break;
                        }
                    }
                }
            }
            break;
        case "Rpr6yCQKU6": // Pawn Piece: [Alice Bonus] Commanded Will 1
            if(IsAliceBonusActive($obj->Controller)) {
                $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
                if($combatAttacker !== null && $combatAttacker !== "-" && $obj->GetMzID() === $combatAttacker) {
                    $intentCards = GetIntentCards($obj->Controller);
                    foreach($intentCards as $iMZ) {
                        $iObj = GetZoneObject($iMZ);
                        if($iObj !== null && PropertyContains(CardSubtypes($iObj->CardID), "COMMAND")) {
                            $power += 1;
                            break;
                        }
                    }
                }
            }
            break;
        case "m69XrVkaVh": // Queen Piece: [Alice Bonus] Commanded Will 6
            if(IsAliceBonusActive($obj->Controller)) {
                $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
                if($combatAttacker !== null && $combatAttacker !== "-" && $obj->GetMzID() === $combatAttacker) {
                    $intentCards = GetIntentCards($obj->Controller);
                    foreach($intentCards as $iMZ) {
                        $iObj = GetZoneObject($iMZ);
                        if($iObj !== null && PropertyContains(CardSubtypes($iObj->CardID), "COMMAND")) {
                            $power += 6;
                            break;
                        }
                    }
                }
            }
            break;
        case "Dgtim99eB5": // Weiss Bishop: [Alice Bonus] +1 POWER while attacking unit with odd life stat
            if(IsAliceBonusActive($obj->Controller)) {
                $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
                $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
                if($combatAttacker !== null && $combatAttacker !== "-" && $obj->GetMzID() === $combatAttacker
                    && $combatTarget !== null && $combatTarget !== "-" && $combatTarget !== "") {
                    $targetObj = GetZoneObject($combatTarget);
                    if($targetObj !== null) {
                        $targetLife = CardLife($targetObj->CardID);
                        if($targetLife !== null && $targetLife > 0 && $targetLife % 2 != 0) {
                            $power += 1;
                        }
                    }
                }
            }
            break;
        case "1jmQ9XSLph": // Sacrifice Play: +2 POWER per ally sacrificed (stored as counter)
            {
                $sacCount = 0;
                if(is_array($obj->Counters) && isset($obj->Counters['sacPlayCount'])) {
                    $sacCount = intval($obj->Counters['sacPlayCount']);
                }
                $power += $sacCount * 2;
            }
            break;
        case "1t3dvor61i": // Lamentation's Toll: [Level 2+] +X POWER where X = highest power among omens
            if(PlayerLevel($obj->Controller) >= 2) {
                $omens = GetOmens($obj->Controller);
                $maxPower = 0;
                foreach($omens as $omenObj) {
                    $op = CardPower($omenObj->CardID);
                    if($op !== null && $op > $maxPower) $maxPower = $op;
                }
                $power += $maxPower;
            }
            break;
        case "23ag70F2uz": // Crowdguard's Slash: [CB] +2 POWER if opponent controls three or more units
            if(IsClassBonusActive($obj->Controller, ["GUARDIAN"])) {
                $opponent = ($obj->Controller == 1) ? 2 : 1;
                if(count(GetFieldUnits($opponent)) >= 3) {
                    $power += 2;
                }
            }
            break;
        case "2jgiM0p4dt": // Elyan, Lustre Loyalty: +X POWER for each recover trigger amount this turn
            foreach($obj->TurnEffects as $effect) {
                if(strpos($effect, "2jgiM0p4dt_RECOVER_") === 0) {
                    $power += intval(substr($effect, strlen("2jgiM0p4dt_RECOVER_")));
                }
            }
            break;
        case "29xxoo7dl5": // Arondight, Azure Blade: +2 POWER per refinement counter
            $power += GetCounterCount($obj, "refinement") * 2;
            break;
        case "6ihv6hbvye": // Grande Aiguille: [Ciel Bonus] +1 POWER if 2+ ally omens
            if(IsCielBonusActive($obj->Controller) && GetOmenCountByType($obj->Controller, "ALLY") >= 2) $power += 1;
            break;
        case "67CIhG8hmG": // Avatar of Genbu: [Guo Jia Bonus][Deluge 12] +2 POWER
            if(IsGuoJiaBonus($obj->Controller) && DelugeAmount($obj->Controller) >= 12) $power += 2;
            break;
        case "8677jq0hfm": // Sundering Moon: [Jin Bonus] On Enter can grant +1 POWER until EOT
            if(in_array("8677jq0hfm-POWER", $obj->TurnEffects ?? [])) $power += 1;
            break;
        case "s4b2mkh1xm": // Grande Sonnerie: [Ciel Bonus] +2 POWER while total omen reserve cost is 10+
            if(IsCielBonusActive($obj->Controller) && GetTotalOmenCost($obj->Controller) >= 10) $power += 2;
            break;
        case "At1UNRG7F0": // Devastating Blow: [CB][Level 3+] +4 POWER
            if(IsClassBonusActive($obj->Controller, ["GUARDIAN", "WARRIOR"]) && PlayerLevel($obj->Controller) >= 3) {
                $power += 4;
            }
            break;
        case "s4vxfy51ec": // Limitless Slime: [Class Bonus][Level 2+] +1 POWER
            if(IsClassBonusActive($obj->Controller, ["TAMER"]) && PlayerLevel($obj->Controller) >= 2) {
                $power += 1;
            }
            break;
        case "9f92917r84": // Dragon's Dawn: On Attack +2 POWER when fire card discarded
            if(in_array("9f92917r84-POWER", $obj->TurnEffects ?? [])) {
                $power += 2;
            }
            break;
        case "XsxmnGZxKz": // Spirit Blade: Terminus: +1 POWER per 4 sheen counters on Fractured Memories
            {
                $sheenCount = GetSheenCount($obj->Controller);
                $power += intdiv($sheenCount, 4);
            }
            break;
        case "Y34Imzlr0n": // Shardforged Blade: [Merlin Bonus] +2 POWER while attacking an ally with sheen counter
            if(IsMerlinBonusActive($obj->Controller)) {
                $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
                if($combatTarget != "-" && $combatTarget != "") {
                    $targetObj = GetZoneObject($combatTarget);
                    if($targetObj !== null && PropertyContains(EffectiveCardType($targetObj), "ALLY")
                        && GetCounterCount($targetObj, "sheen") > 0) {
                        $power += 2;
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
        // Gustmark Gauge (cMixAGt8zv): [Level 2+] while rested, Chessman allies you control get +1 POWER
        if(PropertyContains(EffectiveCardType($obj), "ALLY") && PropertyContains(EffectiveCardSubtypes($obj), "CHESSMAN")) {
            foreach($field as $fieldObj) {
                if(!$fieldObj->removed && $fieldObj->CardID === "cMixAGt8zv" && !HasNoAbilities($fieldObj)
                   && isset($fieldObj->Status) && $fieldObj->Status == 1
                   && PlayerLevel($obj->Controller) >= 2) {
                    $power += 1;
                    break;
                }
            }
        }
        // Chance, Seven of Spades (DKoSnhjX18): Cardistry — other Suited allies get +1 POWER
        if(PropertyContains(EffectiveCardType($obj), "ALLY") && PropertyContains(EffectiveCardSubtypes($obj), "SUITED")) {
            foreach($field as $fieldObj) {
                if(!$fieldObj->removed && $fieldObj->CardID === "DKoSnhjX18" && !HasNoAbilities($fieldObj)
                   && $fieldObj !== $obj && in_array("DKoSnhjX18", $fieldObj->TurnEffects)) {
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
            case "japulzj7gv_POWER": // Fauna Friend: target ally gets +1 POWER until end of turn
                $power += 1;
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
            case "SERVILE_POSSESSIONS_POWER_1": $power += 1; break;
            case "SERVILE_POSSESSIONS_POWER_2": $power += 2; break;
            case "SERVILE_POSSESSIONS_POWER_3": $power += 3; break;
            case "yevpmu6gvn_POWER": // Tonoris, Might of Humanity: +3 POWER on next attack
                $power += 3;
                break;
            case "vnta6qsesw_POWER": // Take Aim: +2 POWER on next attack
                $power += 2;
                break;
            case "wXsHpcrH3P_POWER": // Herd of the Hearth: Animal/Beast allies get +1 POWER
                $power += 1;
                break;
            case "w7g91ru45w_POWER": // Trump Set: redirected ally gets +3 POWER
                $power += 3;
                break;
            case "5ramr16052_POWER": // Jin, Zealous Maverick: +1 POWER on next attack
                $power += 1;
                break;
            case "uqICHZa3Wz_POWER": // Biding Cinquedea: On Charge 2 [Class Bonus] → +1 POWER until end of turn
                $power += 1;
                break;
            case "f0jbv5n196_POWER": // Memento Pocketwatch: On Charge 3 → next attack +3 POWER
                $power += 3;
                break;
            case "dfchplzf6m_POWER": // Ingress of Sanguine Ire: +3 POWER on first attack
                $power += 3;
                break;
            case "f00cEmu6Ql_POWER": // Galvanizing Gale: +3 POWER on the next attack this turn
                $power += 3;
                break;
            case "ATTUNE_FLAMES_BUFF": // Attune with Flames: +5 POWER until end of next turn
                $power += 5;
                break;
            case "1wl8ao8bls": // Carter, Synthetic Reaper: sacrificed ally On Enter -> +2 POWER until end of turn
                $power += 2;
                break;
            case "p00ghqhcpb-2": // Chill to the Bone: -2 POWER until end of turn
                $power -= 2;
                break;
            case "p00ghqhcpb-4": // Chill to the Bone: -4 POWER until end of turn
                $power -= 4;
                break;
            case "bscxwjbqjd": // Sharpen Blade: target Dagger +2 POWER until end of turn
                $power += 2;
                break;
            case "clgolelsra": // Bellona's Runestone: target weapon gets +2 POWER until end of turn
                $power += 2;
                break;
            case "frzrplywc0": // Prototype Pistol: [Class Bonus] On Enter +1 POWER until end of turn
                $power += 1;
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
            case "lxnq80yu75": // Gearstride Academy: imbued wind ally gets +1 POWER until end of turn
                $power += 1;
                break;
            case "mwfrfo3wzq": // Zhao Yun, Dragonsblood: attack gets +2 POWER
                $power += 2;
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
            case "6S1LLrBfBU": // Rosewinged Hollow: +2 POWER until end of turn
                $power += 2;
                break;
            case "9f0nsj62l6-POWER": // Apprentice Aeromancer: [CB] wind spell trigger +1 POWER until EOT
                $power += 1;
                break;
            case "1i2luu7dft": // Wulin Lancer: +2 POWER from Shifting Currents N→W transition
                $power += 2;
                break;
            case "FwPdj4PkSS": // Venerable Sage: +1 POWER when Shifting Currents change direction
                $power += 1;
                break;
            case "mvgmaalpko": // Flamelash Beastmaster: [CB] +3 POWER until end of turn
                $power += 3;
                break;
            case "6g7x7tja9h_ATTACK_POWER": // Trivariate Dream: [CB] +3 POWER with exactly 3 Aethercharge in intent
                $power += 3;
                break;
            case "xgi39z49tu": // Pluming Crescendo: Animals get +1 POWER until end of turn
                $power += 1;
                break;
            case "y5koddlyv8_POWER": // Undying Dreams: +1 POWER until end of turn
                $power += 1;
                break;
            case "VERITA_POWER_PENDING": // Verita On Death: Suited allies get +1 POWER (pending conversion)
                $power += 1;
                break;
            case "VERITA_POWER": // Verita On Death: Suited allies get +1 POWER (active, expires end of turn)
                $power += 1;
                break;
            case "4yqL9xtzVi_POWER": // Bandersnatch, Frumious Foe: +2 POWER until end of turn
                $power += 2;
                break;
            case "nZFkDcvpaY_POWER": // Memorite Blade: +1 POWER from spell activation this turn
                $power += 1;
                break;
            case "W0WfIEDs3n": // Field of Ranks and Files: first Chessman ally enter +2 POWER until EOT
                $power += 2;
                break;
            case "W0WfIEDs3n-CMD": // Field of Ranks and Files: first Chessman Command +2 POWER
                $power += 2;
                break;
            case "NGAy4rNwUo": // Queen's Gambit: [Alice Bonus] Chessman ally On Enter +1 POWER until EOT
                $power += 1;
                break;
            case "fgBpQZe0js-debuff": // Freezing Gambit: target unit's attacks get -3 POWER until EOT
                $power -= 3;
                break;
            case "473gyf0w3v": // Duxal Proclamation: allies get +1 POWER until end of turn
                $power += 1;
                break;
            case "4hnf1yyx1q": // Grim Foreboding: Phantasia allies get +1 POWER until end of turn
                $power += 1;
                break;
            case "CgyJxpEgzk-POWER3": // Spirit Blade: Infusion: +3 POWER until end of turn
                $power += 3;
                break;
            case "yicNKtzC3H-POWER_BUFF": // Jabberwocky, Calamity's Call: +2 POWER until end of turn
                $power += 2;
                break;
            case "a8a0v4njrt": // Slate Whetstone: +1 POWER until end of turn
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
                // Lacuna's Grasp (w7annwvl5q): +X POWER from paying X reserve on attack
                if(strpos($effectID, "w7annwvl5q-") === 0) {
                    $power += intval(substr($effectID, strlen("w7annwvl5q-")));
                }
                // Enrage (wcfvrfw35s): next attack gets +X POWER
                if(strpos($effectID, "wcfvrfw35s_POWER_") === 0) {
                    $power += intval(substr($effectID, strlen("wcfvrfw35s_POWER_")));
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
    // Merlin, Kingslayer (rz1bqry41l): if champion has rz1bqry41l TurnEffect,
    // all attack cards get +2 POWER until end of turn.
    if(PropertyContains(EffectiveCardType($obj), "ATTACK")) {
        $controller = $obj->Controller ?? null;
        if($controller !== null && $controller > 0) {
            $field = GetField($controller);
            foreach($field as $fieldObj) {
                if(PropertyContains(EffectiveCardType($fieldObj), "CHAMPION") && in_array("rz1bqry41l", $fieldObj->TurnEffects)) {
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
        // Dissuading Aether (bx25s7kiln): target unit's attacks get -3 POWER
        if($obj !== null && in_array("bx25s7kiln-debuff", $obj->TurnEffects)) {
            $power -= 3;
        }
        // Dissuading Halt (y7wbtbasch): target unit's attacks get -3 POWER
        if($obj !== null && in_array("y7wbtbasch-debuff", $obj->TurnEffects)) {
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
            case "8asbierp5k": // Beastsoul Visage: linked ally gets +2 POWER
                $power += 2;
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
            case "3jg01o26b4": // Slice and Dice: copy from additional attack gets +3 POWER
                if(in_array("3jg01o26b4-COPY_POWER", $obj->TurnEffects)) {
                    $power += 3;
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
    // Alice, Golden Queen (daip7s9ztd) — Inherited Effect:
    // Chessman Command attack cards get +1 POWER.
    if(PropertyContains(EffectiveCardType($obj), "ATTACK")) {
        $controller = $obj->Controller ?? null;
        if($controller !== null && $controller > 0 && ChampionHasInLineage($controller, "daip7s9ztd")) {
            $subtypes = CardSubtypes($obj->CardID);
            if(PropertyContains($subtypes, "CHESSMAN") && PropertyContains($subtypes, "COMMAND")) {
                $power += 1;
            }
        }
    }
    // Hua Xiong, Insurgent's Fang (TvugEkGGVd): [Jin Bonus] Polearm attacks in intent get +2 POWER
    if(PropertyContains(EffectiveCardType($obj), "ATTACK")
        && PropertyContains(CardSubtypes($obj->CardID), "POLEARM")) {
        $controller = $obj->Controller ?? null;
        if($controller !== null && $controller > 0) {
            global $playerID;
            $hxZone = $controller == $playerID ? "myField" : "theirField";
            $hxField = GetZone($hxZone);
            $hasHuaXiong = false;
            foreach($hxField as $hxObj) {
                if(!$hxObj->removed && $hxObj->CardID === "TvugEkGGVd" && !HasNoAbilities($hxObj)) {
                    $hasHuaXiong = true;
                    break;
                }
            }
            if($hasHuaXiong) {
                $isJin = false;
                foreach($hxField as $hxCObj) {
                    if(!$hxCObj->removed && PropertyContains(EffectiveCardType($hxCObj), "CHAMPION")) {
                        if(strpos(CardName($hxCObj->CardID), "Jin") === 0) $isJin = true;
                        break;
                    }
                }
                if($isJin) {
                    $power += 2;
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
    // Throne Sentinel (RP37sLrsxr): +1 POWER until end of turn (token allies)
    if(in_array("RP37sLrsxr", $obj->TurnEffects)) {
        $power += 1;
    }
    // Lawsur, the Carpenter (aenquoed10): +X POWER from Enter (X = Specter allies)
    foreach($obj->TurnEffects as $te) {
        if(strpos($te, "aenquoed10-POWER_") === 0) {
            $power += intval(substr($te, strlen("aenquoed10-POWER_")));
        }
    }
    // Windpiercer (hreqhj1trn): On Attack reveal — if wind element, +2 POWER
    if(in_array("hreqhj1trn-power", $obj->TurnEffects)) {
        $power += 2;
    }
    // Facet Together (XmsEbk19Iu): +X POWER until end of your next turn
    foreach($obj->TurnEffects as $te) {
        if(strpos($te, "FACET_POWER_") === 0) {
            $power += intval(substr($te, strlen("FACET_POWER_")));
        }
    }
    // Crystallized Anthem (XfAJlQt9hH): +X POWER for Memorite objects at recollection
    foreach($obj->TurnEffects as $te) {
        if(strpos($te, "CRYSTALLIZED_ANTHEM_POWER_") === 0) {
            $power += intval(substr($te, strlen("CRYSTALLIZED_ANTHEM_POWER_")));
        }
    }
    // Peppered Chef (lcy0lw1veb): On Enter sacrifice ally → +2 POWER until end of turn
    if(in_array("lcy0lw1veb", $obj->TurnEffects)) {
        $power += 2;
    }
    // Usurp the Winds (ulzrh3pmxq): +1 POWER until end of turn
    if(in_array("ulzrh3pmxq", $obj->TurnEffects)) {
        $power += 1;
    }
    // Flamebreak Chorus (yky280mtts): +2 POWER until end of turn
    if(in_array("yky280mtts_POWER", $obj->TurnEffects)) {
        $power += 2;
    }
    // Flamebreak Chorus (yky280mtts): +LV POWER if was defending
    foreach($obj->TurnEffects as $te) {
        if(strpos($te, "yky280mtts_LV_") === 0) {
            $power += intval(substr($te, strlen("yky280mtts_LV_")));
            break;
        }
    }
    // Legion's Wingspan (iuixf9rdmu): +1 POWER per application
    foreach($obj->TurnEffects as $te) {
        if($te === "iuixf9rdmu_POWER") $power += 1;
    }
    // Salamander's Breath (mob9nu6lal): +1 POWER per fire card banished
    foreach($obj->TurnEffects as $te) {
        if($te === "mob9nu6lal_POWER") $power += 1;
    }
    // Mana's Cascade (xywyzv14iv): +1 POWER
    if(in_array("xywyzv14iv_POWER", $obj->TurnEffects)) {
        $power += 1;
    }
    // Aether's Embrace (wd7nuab7f3): +2 POWER
    if(in_array("wd7nuab7f3-POWER", $obj->TurnEffects)) {
        $power += 2;
    }
    // Shadow's Twin (5vettczb14): +2 POWER when loaded
    if(in_array("5vettczb14_POWER", $obj->TurnEffects)) {
        $power += 2;
    }
    // Quietus Blade: [CB] while your material deck is empty, +4 POWER.
    if($obj->CardID === "4c7XZeezka" && $obj->Controller != -1 && IsClassBonusActive($obj->Controller, ["WARRIOR"])) {
        $material = GetMaterial($obj->Controller);
        $remaining = 0;
        foreach($material as $mObj) {
            if(!$mObj->removed) $remaining++;
        }
        if($remaining === 0) $power += 4;
    }
    // Floodborne Swing: [CB] Deluge 3 gives +4 POWER.
    if($obj->CardID === "5wLtoxd4Wc" && $obj->Controller != -1
        && IsClassBonusActive($obj->Controller, ["WARRIOR"]) && DelugeAmount($obj->Controller) >= 3) {
        $power += 4;
    }
    // Conduit of the Mad Mage (6SXL09rEzS): +1 POWER per Mage Spell activation this turn
    foreach($obj->TurnEffects as $te) {
        if($te === "6SXL09rEzS-POWER") $power += 1;
    }
    if(in_array("yguf3aw2ct_POWER", $obj->TurnEffects ?? [])) $power += 2;
    foreach($obj->TurnEffects ?? [] as $te) {
        if(strpos($te, "o191zv86la_POWER_") === 0) {
            $power += intval(substr($te, strlen("o191zv86la_POWER_")));
        }
    }
    // Band of Burning Verdict (7mmve2l328): +1 POWER until end of turn
    if(in_array("7mmve2l328", $obj->TurnEffects)) {
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
    // Two of Hearts (rufki4o41y): Cardistry +2 POWER until end of turn
    if(in_array("rufki4o41y", $obj->TurnEffects)) {
        $power += 2;
    }
    // Five of Spades (i9hf5lhl5f): Cardistry +5 POWER until end of turn
    if(in_array("i9hf5lhl5f", $obj->TurnEffects)) {
        $power += 5;
    }
    if(in_array("r7oifozaog", $obj->TurnEffects)) {
        $power += 1;
    }
    // Righteous Retribution (TO9qqKHakv): cross-turn power boost — champion's first attack gets +X POWER
    if(PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
        foreach($obj->TurnEffects as $te) {
            if(strpos($te, "TO9qqKHakv-") === 0) {
                $power += intval(substr($te, strlen("TO9qqKHakv-")));
                break;
            }
            if(strpos($te, "qtzsekkjn3-") === 0) {
                $power += intval(substr($te, strlen("qtzsekkjn3-")));
                break;
            }
        }
    }
    if(PropertyContains(EffectiveCardType($obj), "ALLY") && $obj->Controller != -1) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        foreach(GetZone($zone) as $fObj) {
            if($fObj->removed || HasNoAbilities($fObj)) continue;
            if($fObj->CardID === "GjM8b5fxqj" && $fObj !== $obj && intval($fObj->Status ?? 0) === 1) {
                $power += 1;
                break;
            }
        }
    }
    // Resonating Fugue (optpu3fubb): POWER_LIFE_SWAP — return life value instead of power
    global $_computingPowerLifeSwap;
    if(!$_computingPowerLifeSwap && in_array("POWER_LIFE_SWAP", $obj->TurnEffects ?? [])) {
        $_computingPowerLifeSwap = true;
        $swappedValue = ObjectCurrentHP($obj);
        $_computingPowerLifeSwap = false;
        return $swappedValue;
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
            case "gyk90s0hst": // Gossamer Staff: Empower 1
                $cardLevel += 1;
                break;
            case "PLljzdiMmq": // Invoke Dominance: +3 level until end of turn
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
                // Sage's Urn: Empower X, encoded as "s7a4tm04ll-N"
                if(strpos($effectID, "s7a4tm04ll-") === 0) {
                    $cardLevel += intval(substr($effectID, strlen("s7a4tm04ll-")));
                }
                // Ebbing Tide: Empower X, encoded as "s7pmqsl3jw-N"
                if(strpos($effectID, "s7pmqsl3jw-") === 0) {
                    $cardLevel += intval(substr($effectID, strlen("s7pmqsl3jw-")));
                }
                // Discordia, Harp of Malice (5LoOprBJay): -X level to target champion
                if(strpos($effectID, "DISCORDIA_MINUS_") === 0) {
                    $cardLevel -= intval(substr($effectID, strlen("DISCORDIA_MINUS_")));
                }
                // Discordia, Harp of Malice (5LoOprBJay): +X level to own champion
                if(strpos($effectID, "DISCORDIA_PLUS_") === 0) {
                    $cardLevel += intval(substr($effectID, strlen("DISCORDIA_PLUS_")));
                }
                // Power Overwhelming (AnEPyfFfHj): +N level per enlighten counter removed
                if(strpos($effectID, "AnEPyfFfHj-") === 0) {
                    $cardLevel += intval(substr($effectID, strlen("AnEPyfFfHj-")));
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
        // Dusklight Communion (5upufyoz23): "Champions get -1 level" when umbra mode active
        {
            $allFields = [GetZone("myField"), GetZone("theirField")];
            foreach($allFields as $dcField) {
                foreach($dcField as $dcObj) {
                    if(!$dcObj->removed && $dcObj->CardID === "5upufyoz23" && !HasNoAbilities($dcObj)
                       && GetCounterCount($dcObj, "umbra_mode") > 0) {
                        $cardLevel -= 1;
                    }
                }
            }
        }
        // Tome of Ignorance (dz4qd82liq): [CB] opponents' champions get -1 level
        {
            global $playerID;
            $oppZone = $obj->Controller == $playerID ? "theirField" : "myField";
            $tomeField = GetZone($oppZone);
            foreach($tomeField as $tObj) {
                if(!$tObj->removed && $tObj->CardID === "dz4qd82liq" && !HasNoAbilities($tObj)) {
                    if(IsClassBonusActive($tObj->Controller, ["MAGE"])) {
                        $cardLevel -= 1;
                    }
                }
            }
        }
        // Submerged Fatestone (zfb0pzm6qp): [Guo Jia Bonus] opponent's champions get -1 level
        {
            global $playerID;
            $oppZone = $obj->Controller == $playerID ? "theirField" : "myField";
            $subField = GetZone($oppZone);
            foreach($subField as $sObj) {
                if(!$sObj->removed && $sObj->CardID === "zfb0pzm6qp" && !HasNoAbilities($sObj)) {
                    if(IsGuoJiaBonus($sObj->Controller)) {
                        $cardLevel -= 1;
                    }
                    break;
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
                case "t203gysyp8": // Cowl of the Wild: +1 level while you control a non-Human Tamer ally
                    foreach($field as $allyObj) {
                        if($allyObj->removed || !PropertyContains(EffectiveCardType($allyObj), "ALLY")) continue;
                        if(!PropertyContains(EffectiveCardClasses($allyObj), "TAMER")) continue;
                        if(PropertyContains(EffectiveCardSubtypes($allyObj), "HUMAN")) continue;
                        $cardLevel += 1;
                        break;
                    }
                    $appliedPassives[$fID] = true;
                    break;
                case "8c9htu9agw": // Prototype Staff: [Class Bonus][Memory 4+] champion gets +1 level
                    if(IsClassBonusActive($obj->Controller, ["CLERIC"])) {
                        $memory = &GetMemory($obj->Controller);
                        if(count($memory) >= 4) {
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
    if($obj->CardID === "g92bHLtTNl") { // Rai, Storm Seer
        global $playerID;
        $banishZone = $obj->Controller == $playerID ? "myBanish" : "theirBanish";
        $banish = GetZone($banishZone);
        foreach($banish as $bObj) {
            if($bObj->removed) continue;
            if(CardElement($bObj->CardID) !== "ARCANE") continue;
            if(!PropertyContains(CardClasses($bObj->CardID), "MAGE")) continue;
            if(!PropertyContains(CardSubtypes($bObj->CardID), "SPELL")) continue;
            ++$cardLevel;
        }
    }
    return $cardLevel;
}

function ObjectCurrentHP($obj) {
    $cardLife = CardLife($obj->CardID);
    // Humpty Dumpty (aou4be9z82): when becomes ally, base life = 0 + buff counters
    if(($cardLife === null || $cardLife < 0) && in_array("HUMPTY_ALLY", $obj->TurnEffects ?? [])) {
        $cardLife = 0;
    } elseif($cardLife === null || $cardLife < 0) {
        return 0; // No life stat — buff counters do not generate one
    }
    // Buff counter modifier: +1 life per buff counter (applied before other modifiers)
    $cardLife += GetCounterCount($obj, "buff");
    if(in_array("ymuarq5tv0-LIFE", $obj->TurnEffects ?? [])) {
        $cardLife += 1;
    }
    // Fluvial Fatestone (3h93tgm72l): target ally gets +2 LIFE until end of turn
    if(in_array("3h93tgm72l", $obj->TurnEffects ?? [])) {
        $cardLife += 2;
    }
    if(in_array("9urNxU7SZw_HP3", $obj->TurnEffects ?? [])) {
        $cardLife += 3;
    }
    switch($obj->CardID) { //Self hp modifiers
        case "fdnlbJm3hr": // Memorite Obelith: +1 LIFE per sheen counter (cap 5)
            $cardLife += min(5, GetCounterCount($obj, "sheen"));
            break;
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
        case "vw2ifz1nr5": // Andronika, Eternal Herald: +1 LIFE while imbued
            if(in_array("IMBUED", $obj->TurnEffects ?? [])) {
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
        case "s4vxfy51ec": // Limitless Slime: [Class Bonus][Level 2+] +2 LIFE
            if(IsClassBonusActive($obj->Controller, ["TAMER"]) && PlayerLevel($obj->Controller) >= 2) {
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
        case "46neis2lho": // Imperial Panzer: [CB] +2 LIFE while fostered
            if(IsFostered($obj) && IsClassBonusActive($obj->Controller, ["GUARDIAN"])) $cardLife += 2;
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
        case "e7782pjg1d": // Armed Squallguard: +1 LIFE if you have ally omens
            if(GetOmenCountByType($obj->Controller, "ALLY") > 0) $cardLife += 1;
            break;
        case "L67r0GlRHR": // Vacuous Servant: [Ciel Bonus] +1 LIFE per ally omen
            if(IsCielBonusActive($obj->Controller)) $cardLife += GetOmenCountByType($obj->Controller, "ALLY");
            break;
        case "67CIhG8hmG": // Avatar of Genbu: [Guo Jia Bonus][Deluge 12] +2 LIFE
            if(IsGuoJiaBonus($obj->Controller) && DelugeAmount($obj->Controller) >= 12) $cardLife += 2;
            break;
        case "9ggfiy38t2": // Baby Blue Slime: [Class Bonus] +1 LIFE
            if(IsClassBonusActive($obj->Controller, ["TAMER"])) {
                $cardLife += 1;
            }
            break;
        case "TqCo3xlf93": // Lunete, Frostbinder Priest: [Balance] +3 LIFE
            {
                $memory = &GetMemory($obj->Controller);
                $hand = &GetHand($obj->Controller);
                if(count($memory) == count($hand)) {
                    $cardLife += 3;
                }
            }
            break;
        case "tu7jvjf2gh": // Sablier Guard: +1 LIFE per distinct omen reserve cost
            $cardLife += GetOmenDistinctCostCount($obj->Controller);
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
        // Gustmark Gauge (cMixAGt8zv): while awake, Chessman allies you control get +1 LIFE
        if(PropertyContains(EffectiveCardType($obj), "ALLY") && PropertyContains(EffectiveCardSubtypes($obj), "CHESSMAN")) {
            foreach($field as $fieldObj) {
                if(!$fieldObj->removed && $fieldObj->CardID === "cMixAGt8zv" && !HasNoAbilities($fieldObj)
                   && isset($fieldObj->Status) && $fieldObj->Status == 2) {
                    $cardLife += 1;
                    break;
                }
            }
        }
        // Plage aux Homards (s25QNTvfem): [Deluge 4] Animal/Beast allies you control get +1 LIFE
        if(PropertyContains(EffectiveCardType($obj), "ALLY")
           && (PropertyContains(EffectiveCardSubtypes($obj), "ANIMAL") || PropertyContains(EffectiveCardSubtypes($obj), "BEAST"))) {
            foreach($field as $fieldObj) {
                if(!$fieldObj->removed && $fieldObj->CardID === "s25QNTvfem" && !HasNoAbilities($fieldObj)
                   && DelugeAmount($fieldObj->Controller) >= 4) {
                    $cardLife += 1;
                    break;
                }
            }
        }
        // Silvie, Loved by All (GKEpAulogu): Animal/Beast allies get +1 LIFE
        if(PropertyContains(EffectiveCardType($obj), "ALLY")
           && (PropertyContains(EffectiveCardSubtypes($obj), "ANIMAL") || PropertyContains(EffectiveCardSubtypes($obj), "BEAST"))) {
            foreach($field as $fieldObj) {
                if(!$fieldObj->removed && $fieldObj->CardID === "GKEpAulogu" && !HasNoAbilities($fieldObj)) {
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
    // Berserker Plate (ci00l7pqcx): [Class Bonus] your champion gets +7 LIFE
    if(PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fieldObj) {
            if($fieldObj != null && !$fieldObj->removed && $fieldObj->CardID === "ci00l7pqcx" && !HasNoAbilities($fieldObj)) {
                if(IsClassBonusActive($obj->Controller, ["WARRIOR"])) {
                    $cardLife += 7;
                }
                break;
            }
        }
    }
    // Inherited Effects: check champion lineage for curse effects
    if(PropertyContains(EffectiveCardType($obj), "CHAMPION") && is_array($obj->Subcards)) {
        $curseSuppressed = AreCurseLineageAbilitiesSuppressed($obj->Controller);
        foreach($obj->Subcards as $lineageCardID) {
            if($curseSuppressed && PropertyContains(CardSubtypes($lineageCardID), "CURSE")) continue;
            if($lineageCardID === "8tuhuy4xip") { // Load Soul: -2 LIFE
                $cardLife -= 2;
            }
            if($lineageCardID === "up6fw61vf1") { // Malevolent Vow: -2 LIFE
                $cardLife -= 2;
            }
            // Curse cards with Inherited Effect: -2 LIFE
            if($lineageCardID === "oqk2c7wklz" // Shadecursed Hunter
            || $lineageCardID === "vdxi74wa4x" // Violet Haze
            || $lineageCardID === "6g7xgwve1d" // Demon's Aim
            || $lineageCardID === "igpck2z4rs" // Gloamspire Prowler
            ) {
                $cardLife -= 2;
            }
            if($lineageCardID === "e1xj8mqr2o") { // Sinistre Stab: -3 LIFE
                $cardLife -= 3;
            }
            if($lineageCardID === "7h2k6p8fss") { // Profane Bindings: -5 LIFE
                $cardLife -= 5;
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
            case "i1j4gvwbjo": // Protector's Plate: linked ally gets +1 LIFE
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
            case "qmj9q5gmsp_LIFE": // Beastbond Claws: +2 LIFE until end of turn
                $cardLife += 2;
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
            case "v0yuddp71s": // Castling Boon: allies get +1 LIFE until end of turn
                $cardLife += 1;
                break;
            case "v0yuddp71s-ROOK": // Castling Boon (Rook): allies get +3 LIFE until end of turn
                $cardLife += 3;
                break;
            case "tqy0rwvxgs": // Favorable Omens: allies get +1 LIFE per wind omen
                $cardLife += 1;
                break;
            case "acmde97dbu": // Formidable Youxia: +2 LIFE while Shifting Currents faces East
                if(GetShiftingCurrents($obj->Controller) === "EAST") {
                    $cardLife += 2;
                }
                break;
            case "p00ghqhcpb-2": // Chill to the Bone: -2 LIFE until end of turn
                $cardLife -= 2;
                break;
            case "p00ghqhcpb-4": // Chill to the Bone: -4 LIFE until end of turn
                $cardLife -= 4;
                break;
            default: break;
        }
    }
    // Longtail Grovesward (jn4mwv930y): [Level 1+] +1 LIFE
    if($obj->CardID === "jn4mwv930y" && PlayerLevel($obj->Controller) >= 1) {
        $cardLife += 1;
    }
    // Imperial Sentry (plywc08c9h): [Level 2+] +1 LIFE
    if($obj->CardID === "plywc08c9h" && PlayerLevel($obj->Controller) >= 2) {
        $cardLife += 1;
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
    // Venerable Sage (FwPdj4PkSS): +1 LIFE when Shifting Currents change direction
    if(in_array("FwPdj4PkSS", $obj->TurnEffects)) {
        $cardLife += 1;
    }
    // Trump Set (w7g91ru45w): redirected ally gets +3 LIFE
    if(in_array("w7g91ru45w_LIFE", $obj->TurnEffects ?? [])) {
        $cardLife += 3;
    }
    // Aether's Embrace (wd7nuab7f3): +2 LIFE
    if(in_array("wd7nuab7f3-LIFE", $obj->TurnEffects)) {
        $cardLife += 2;
    }
    // Drown in Aether (gnfbp3g8iw): -3 LIFE until end of turn
    if(in_array("gnfbp3g8iw-debuff", $obj->TurnEffects)) {
        $cardLife -= 3;
    }
    // Three of Spades (o09csnorqv): Cardistry +2 LIFE until end of turn
    if(in_array("o09csnorqv", $obj->TurnEffects)) {
        $cardLife += 2;
    }
    // Six of Spades (tdRR5lQHMN): Cardistry +2 LIFE until end of turn
    if(in_array("tdRR5lQHMN", $obj->TurnEffects)) {
        $cardLife += 2;
    }
    // Lucenia's Reign (zrvvwz3ww9): target Chessman ally +1 LIFE until end of turn
    if(in_array("zrvvwz3ww9_LIFE", $obj->TurnEffects)) {
        $cardLife += 1;
    }
    // Rose, Eternal Paragon (2bbmoqk2c7): +1 LIFE until end of turn (from On Enter redirection)
    if(in_array("2bbmoqk2c7-LIFE", $obj->TurnEffects)) {
        $cardLife += 1;
    }
    // Hunt, Weiss King (Y6PZntlVDl): Rook option — target Chessman Rook +2 LIFE until end of turn
    if(in_array("Y6PZntlVDl_LIFE", $obj->TurnEffects)) {
        $cardLife += 2;
    }
    // Castling (tFOpmUdi2W): target Chessman ally +2 LIFE until end of turn
    if(in_array("tFOpmUdi2W_HP", $obj->TurnEffects)) {
        $cardLife += 2;
    }
    // Resonating Fugue (optpu3fubb): POWER_LIFE_SWAP — return power value instead of life
    global $_computingPowerLifeSwap;
    if(!$_computingPowerLifeSwap && in_array("POWER_LIFE_SWAP", $obj->TurnEffects ?? [])) {
        $_computingPowerLifeSwap = true;
        $swappedValue = ObjectCurrentPower($obj);
        $_computingPowerLifeSwap = false;
        return $swappedValue;
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
        if(GlobalEffectCount($player, "ri955ygd5v_NO_DRAW") > 0) {
            return;
        }
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
        if(GlobalEffectCount($player, "ri955ygd5v_NO_DRAW") > 0) {
            return;
        }
        if(count($zone) == 0) return;
        $card = array_shift($zone);
        array_push($memory, $card);
    }
}

function IsTonorisBonusActive($player) {
    return ChampionHasInLineage($player, "zb14m4c8lj")
        || ChampionHasInLineage($player, "yevpmu6gvn")
        || ChampionHasInLineage($player, "ta6qsesw2u")
        || ChampionHasInLineage($player, "n2jnltv5kl");
}

function GainCrowdsFavor($player) {
    $opponent = ($player == 1) ? 2 : 1;
    RemoveGlobalEffect($opponent, "gpmJdGYqoC");
    if(GlobalEffectCount($player, "gpmJdGYqoC") === 0) AddGlobalEffects($player, "gpmJdGYqoC");
}

function CountDomainsControlled($player) {
    $count = 0;
    foreach(GetField($player) as $obj) {
        if(!$obj->removed && PropertyContains(EffectiveCardType($obj), "DOMAIN")) $count++;
    }
    return $count;
}

function AutomataGenesisResolve($player) {
    for($i = 0; $i < 3; ++$i) MZAddZone($player, "myField", "r79VgzA3W4");
    if(!IsTonorisBonusActive($player)) return;
    $tokens = ZoneSearch("myField", ["TOKEN"]);
    $amount = count($tokens);
    foreach($tokens as $tokenMZ) {
        $obj = GetZoneObject($tokenMZ);
        if($obj !== null && PropertyContains(EffectiveCardType($obj), "ALLY")) AddCounters($player, $tokenMZ, "buff", $amount);
    }
}

function TenderheartGuardEnter($player) {
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Discard_a_card_to_draw_into_memory?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "TenderheartGuardDiscard", 1);
    $opponent = ($player == 1) ? 2 : 1;
    DecisionQueueController::AddDecision($opponent, "YESNO", "-", 1, tooltip:"Discard_a_card_to_draw_into_memory?");
    DecisionQueueController::AddDecision($opponent, "CUSTOM", "TenderheartGuardDiscard", 1);
    GainCrowdsFavor($player);
}

$customDQHandlers["TenderheartGuardDiscard"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $hand = ZoneSearch("myHand");
    if(empty($hand)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $hand), 1, tooltip:"Discard_a_card");
    DecisionQueueController::AddDecision($player, "CUSTOM", "TenderheartGuardDrawMemory", 1);
};

$customDQHandlers["TenderheartGuardDrawMemory"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    DoDiscardCard($player, $lastDecision);
    DrawIntoMemory($player, 1);
};

function SpeedPotionEnter($player) {
    if(DecisionQueueController::GetVariable("wasBrewed") === "YES") Draw($player, 1);
}

function SpeedPotionActivated($player) {
    GainAgility($player, 3);
}

function MalignantAthameOnHit($player, $sourceMZ) {
    if(!IsClassBonusActive($player, ["ASSASSIN"])) return;
    $target = DecisionQueueController::GetVariable("CombatTarget");
    if($target === null || $target === "-" || $target === "") return;
    $targetObj = GetZoneObject($target);
    if($targetObj === null || $targetObj->removed || !PropertyContains(EffectiveCardType($targetObj), "CHAMPION")) return;
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Have_opponent_swap_hand_and_memory?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "MalignantAthameSwap|" . $targetObj->Controller . "|" . $target . "|" . $sourceMZ, 1);
}

$customDQHandlers["MalignantAthameSwap"] = function($player, $parts, $lastDecision) {
    $opponent = intval($parts[0] ?? (($player == 1) ? 2 : 1));
    $target = $parts[1] ?? "";
    $sourceMZ = $parts[2] ?? "";
    if($lastDecision === "YES") {
        $hand = &GetHand($opponent);
        $memory = &GetMemory($opponent);
        $oldHand = $hand;
        $hand = $memory;
        $memory = $oldHand;
    }
    if(count(GetMemory($opponent)) >= 4 && $target !== "") DealUnpreventableDamage($player, $sourceMZ, $target, 2);
};

function HiddenSecretsResolve($player) {
    $allies = FilterSpellshroudTargets(ZoneSearch("myField", ["ALLY"]));
    if(CountDomainsControlled($player) > 0) {
        if(!empty($allies)) {
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $allies), 1, tooltip:"Choose_ally_to_gain_stealth");
            DecisionQueueController::AddDecision($player, "CUSTOM", "HiddenSecretsGrantStealth", 1);
        }
        DrawIntoMemory($player, 1);
        return;
    }
    if(empty($allies)) {
        DrawIntoMemory($player, 1);
        return;
    }
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Grant_stealth_instead_of_drawing?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "HiddenSecretsChooseMode", 1);
}

$customDQHandlers["HiddenSecretsChooseMode"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") {
        DrawIntoMemory($player, 1);
        return;
    }
    $allies = FilterSpellshroudTargets(ZoneSearch("myField", ["ALLY"]));
    if(empty($allies)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $allies), 1, tooltip:"Choose_ally_to_gain_stealth");
    DecisionQueueController::AddDecision($player, "CUSTOM", "HiddenSecretsGrantStealth", 1);
};

$customDQHandlers["HiddenSecretsGrantStealth"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "") AddTurnEffect($lastDecision, "STEALTH");
};

function HeirloomOfSpectraMemorySuppress($player) {
    AddGlobalEffects($player, "0sVdvpQKXq_MEMORY");
}

function HeirloomOfSpectraBanishCurse($player) {
    $temp = &GetTempZone($player);
    while(count($temp) > 0) array_pop($temp);
    $targets = [];
    foreach([1, 2] as $p) {
        foreach(GetCursesInLineage($p) as $curse) {
            $targets[] = $p . ":" . $curse['cardID'];
            MZAddZone($player, "myTempZone", $curse['cardID']);
        }
    }
    if(empty($targets)) return;
    DecisionQueueController::StoreVariable("HeirloomCurseTargets", implode(",", $targets));
    $choices = [];
    for($i = 0; $i < count($targets); ++$i) $choices[] = "myTempZone-" . $i;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $choices), 1, tooltip:"Choose_Curse_to_banish");
    DecisionQueueController::AddDecision($player, "CUSTOM", "HeirloomBanishCurse", 1);
}

$customDQHandlers["HeirloomBanishCurse"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "") {
        $idx = intval(explode("-", $lastDecision)[1] ?? -1);
        $targets = explode(",", DecisionQueueController::GetVariable("HeirloomCurseTargets") ?? "");
        if(isset($targets[$idx])) {
            $targetParts = explode(":", $targets[$idx]);
            if(count($targetParts) === 2) RemoveFromChampionLineage(intval($targetParts[0]), $targetParts[1], "myBanish");
        }
    }
    $temp = &GetTempZone($player);
    while(count($temp) > 0) array_pop($temp);
};

function WindyLeapResolve($player) {
    $allies = FilterSpellshroudTargets(ZoneSearch("myField", ["ALLY"]));
    if(empty($allies)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $allies), 1, tooltip:"Choose_ally_to_banish_and_return_rested");
    DecisionQueueController::AddDecision($player, "CUSTOM", "WindyLeapReturn", 1);
}

$customDQHandlers["WindyLeapReturn"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $obj = GetZoneObject($lastDecision);
    if($obj === null) return;
    $isRanger = PropertyContains(EffectiveCardClasses($obj), "RANGER");
    $owner = $obj->Owner;
    OnLeaveField($player, $lastDecision);
    $banishZone = ($owner == $player) ? "myBanish" : "theirBanish";
    $banished = MZMove($player, $lastDecision, $banishZone);
    if($banished === null) return;
    $dest = ($owner == $player) ? "myField" : "theirField";
    $returned = MZMove($player, $banished->GetMzID(), $dest);
    if($returned !== null) {
        $returned->Status = 1;
        if($isRanger) AddTurnEffect($returned->GetMzID(), "DISTANT");
    }
};

function NocturnesOblivionResolve($player) {
    $targets = array_values(array_filter(array_merge(ZoneSearch("myField"), ZoneSearch("theirField")), function($mz) {
        $obj = GetZoneObject($mz);
        return $obj !== null && !$obj->removed && !PropertyContains(EffectiveCardType($obj), "CHAMPION");
    }));
    $targets = FilterSpellshroudTargets($targets);
    if(empty($targets)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1, tooltip:"Destroy_target_non-champion_object");
    DecisionQueueController::AddDecision($player, "CUSTOM", "NocturnesOblivionDestroy", 1);
}

$customDQHandlers["NocturnesOblivionDestroy"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "") {
        $obj = GetZoneObject($lastDecision);
        if($obj !== null && !$obj->removed) DoSacrificeFighter($obj->Controller, $lastDecision);
    }
    $source = DecisionQueueController::GetVariable("mzID");
    if($source !== null && $source !== "-" && $source !== "") MZMove($player, $source, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
};

// --- Starcalling Registry ---
// Maps cardID => starcalling cost (int). Cards with this keyword can be activated
// mid-glimpse by paying their starcalling cost. Other glimpsed cards go to deck bottom.
$starcallingCards = [];
$starcallingCards["zuj68m69iq"] = 0; // Astra Sight: Starcalling (0)
$starcallingCards["4d5vettczb"] = 2; // Cometfall: Starcalling (2)
$starcallingCards["dwavcoxpnj"] = 3; // Meteor Strike: Starcalling (3)
$starcallingCards["xmtjrvfpuc"] = 1; // Stellaria Shower: Starcalling (1)
$starcallingCards["dWgPzoEbIE"] = 0; // Cosmic Focus: Starcalling (0)

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

function IsAdvancedElementCard($cardID) {
    $advancedElements = ["CRUX", "EXALTED", "ASTRA", "LUXEM", "UMBRA", "TERA"];
    return in_array(CardElement($cardID), $advancedElements);
}

function AdvancedElementActivatedCount($player) {
    $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    return $_ti["AdvancedElementActivated"][$player] ?? 0;
}

function IncrementAdvancedElementActivatedCount($player) {
    $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    $_ti["AdvancedElementActivated"][$player] = ($_ti["AdvancedElementActivated"][$player] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti));
}

// Aethercalling: cards with [Element Bonus] Aethercalling
$aethercallingCards = [
    "nypwwnirjk" => true, // Constellation's Blessing (ASTRA)
    "b0iz7wm7ow" => true, // Guided Starlight (ASTRA)
    "xwwkxq0vp3" => true, // Sidereal Spellshot (ASTRA)
    "gamylrj1fc" => true, // (WIND)
];

/**
 * Check if a card has Aethercalling during a glimpse.
 * Requires: player controls an Aetherwing weapon.
 * Sources: innate [EB] Aethercalling, global effect (gwWociEfxb).
 */
function HasAethercalling($player, $cardID) {
    global $aethercallingCards;
    // Innate [EB] Aethercalling
    if(isset($aethercallingCards[$cardID]) && IsElementBonusActive($player, $cardID)) {
        return true;
    }
    // ud8s2Kjuyr: [Diana Bonus][EB] Aethercalling
    if($cardID === "ud8s2Kjuyr" && IsElementBonusActive($player, $cardID)) {
        $champ = ZoneSearch("myField", ["CHAMPION"]);
        if(!empty($champ)) {
            $champObj = GetZoneObject($champ[0]);
            if($champObj !== null && strpos(CardName($champObj->CardID), "Diana") === 0) {
                return true;
            }
        }
    }
    // gwWociEfxb global effect: Aethercharge cards have aethercalling
    if(GlobalEffectCount($player, "gwWociEfxb_AETHERCALLING") > 0
       && PropertyContains(CardSubtypes($cardID), "AETHERCHARGE")) {
        return true;
    }
    return false;
}

/**
 * Glimpse N: show the top N cards of the player's deck and let them choose
 * which cards go back to the top vs. the bottom, in any order.
 * If any glimpsed card has Starcalling, offer the player a chance to starcall first.
 * If any glimpsed card has Aethercalling, offer to load into Aetherwing.
 * Queues an MZREARRANGE decision followed by a GlimpseApply custom handler.
 *
 * @param int $player The acting player.
 * @param int $amount Number of cards to glimpse.
 */
function Glimpse($player, $amount, $allowAstroscope = true) {
    foreach(GetField($player) as $i => $fObj) {
        if(!$fObj->removed && $fObj->CardID === "c34iTVRS8h" && !HasNoAbilities($fObj)
            && IsClassBonusActive($player, ["ASSASSIN", "CLERIC"])) {
            AddTurnEffect("myField-" . $i, "STEALTH");
        }
    }
    // Orbiting Cosmos (qM9yzxQbfF): if you would glimpse X, glimpse X+1 instead
    $field = GetField($player);
    foreach($field as $fObj) {
        if(!$fObj->removed && $fObj->CardID === "qM9yzxQbfF" && !HasNoAbilities($fObj)) {
            $amount += 1;
            break;
        }
    }
    // Cosmic Astroscope (qj5bbae3z4): if an opponent would glimpse, you may have that player glimpse 3 instead.
    if($allowAstroscope) {
        $opponent = ($player == 1) ? 2 : 1;
        $opponentField = GetField($opponent);
        foreach($opponentField as $fObj) {
            if($fObj->removed || $fObj->CardID !== "qj5bbae3z4" || HasNoAbilities($fObj)) continue;
            if(!IsClassBonusActive($opponent, ["CLERIC"])) continue;
            DecisionQueueController::AddDecision($opponent, "YESNO", "-", 1, tooltip:"Have_that_player_glimpse_3_instead?");
            DecisionQueueController::AddDecision($opponent, "CUSTOM", "CosmicAstroscopeGlimpse|" . $player . "|" . $amount, 1);
            return;
        }
    }
    // Cosmic Alignment (b2buhbediq): next glimpse this turn draws that many instead
    if(GlobalEffectCount($player, "COSMIC_ALIGNMENT") > 0) {
        RemoveGlobalEffect($player, "COSMIC_ALIGNMENT");
        Draw($player, $amount);
        return;
    }
    // Myopic Lens (dZ30oXwi3l): next glimpse this turn becomes glimpse 2 instead
    $champMZ = FindChampionMZ($player);
    if($champMZ !== null) {
        $champObj = GetZoneObject($champMZ);
        if($champObj !== null && in_array("MYOPIC_LENS", $champObj->TurnEffects)) {
            $champObj->TurnEffects = array_values(array_filter($champObj->TurnEffects, fn($e) => $e !== "MYOPIC_LENS"));
            $amount = 2;
        }
    }
    $zone = &GetDeck($player);
    $n = min($amount, count($zone));
    if($n == 0) return;

    // Starstrung Reading (gwWociEfxb): consume one aethercalling charge per glimpse
    if(GlobalEffectCount($player, "gwWociEfxb_AETHERCALLING") > 0) {
        RemoveGlobalEffect($player, "gwWociEfxb_AETHERCALLING");
    }

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

    // Check for aethercalling candidates among glimpsed cards
    $aethercallCandidateIndices = [];
    $wings = GetAetherwingWeapons($player);
    if(!empty($wings)) {
        for($i = 0; $i < $n; ++$i) {
            if(HasAethercalling($player, $cardIDs[$i])) {
                $aethercallCandidateIndices[] = $i;
            }
        }
    }
    DecisionQueueController::StoreVariable("aethercallCandidates",
        !empty($aethercallCandidateIndices) ? implode(",", $aethercallCandidateIndices) : "");

    if(!empty($starcallCandidateIndices) || !empty($aethercallCandidateIndices)) {
        // Move top N cards to myTempZone so the popup can display them face-up
        // (always use myDeck-0 since each MZMove shifts remaining deck cards down)
        for($i = $n-1; $i >= 0; --$i) {
            MZMove($player, "myDeck-" . $i, "myTempZone");
        }
        // Store card IDs and tempzone flag for handlers
        DecisionQueueController::StoreVariable("glimpseCardIDs", implode(",", $cardIDs));
        DecisionQueueController::StoreVariable("glimpsedToTempZone", "1");

        if(!empty($starcallCandidateIndices)) {
            // Offer starcalling choice using tempzone refs (face-up cards in popup)
            $candidateStr = implode("&", array_map(fn($i) => "myTempZone-$i", $starcallCandidateIndices));
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $candidateStr, 1, "Starcall_a_card?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "StarcallingOffer", 1);
        } else if(!empty($aethercallCandidateIndices)) {
            // Only aethercalling candidates — offer to load into Aetherwing
            $candidateStr = implode("&", array_map(fn($i) => "myTempZone-$i", $aethercallCandidateIndices));
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $candidateStr, 1, "Load_into_Aetherwing?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "AethercallingOffer", 1);
        }
    } else {
        // No starcalling or aethercalling candidates — cards stay in deck, proceed with normal glimpse
        DecisionQueueController::StoreVariable("glimpsedToTempZone", "0");
        $param = "Top=" . implode(",", $cardIDs) . ";Bottom=";
        DecisionQueueController::AddDecision($player, "MZREARRANGE", $param, 1, "Glimpse:_Top=return_to_top,_Bottom=put_on_bottom");
        DecisionQueueController::AddDecision($player, "CUSTOM", "GlimpseApply", 1);
    }
}

function DoDiscardCard($player, $mzCard) {
    // Purging Tempest (yuo7dbge3b): cards that would enter this player's GY are banished instead
    if(GlobalEffectCount($player, "yuo7dbge3b") > 0) {
        MZMove($player, $mzCard, "myBanish");
        return;
    }
    // Sasha, Purifying Acolyte (GRlUlcYRmV): while fostered, cards entering your GY are banished instead
    $field = GetField($player);
    foreach($field as $fObj) {
        if(!$fObj->removed && $fObj->CardID === "GRlUlcYRmV" && !HasNoAbilities($fObj) && IsFostered($fObj)) {
            MZMove($player, $mzCard, "myBanish");
            return;
        }
    }
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
    // Polaris, Twinkling Cauldron (41t71u4bzz): whenever you sacrifice an Herb → age counter
    if($isHerb) {
        $polFieldZone = &GetField($controller);
        for($pi = 0; $pi < count($polFieldZone); ++$pi) {
            if(!$polFieldZone[$pi]->removed && $polFieldZone[$pi]->CardID === "41t71u4bzz" && !HasNoAbilities($polFieldZone[$pi])) {
                $polFieldName = (GetTurnPlayer() == $controller) ? "myField" : "theirField";
                global $playerID;
                $polFieldName = ($controller == $playerID) ? "myField" : "theirField";
                AddCounters($controller, $polFieldName . "-" . $pi, "age", 1);
                break;
            }
        }
    }
}

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
    TriggerNightmareCoilPunish($player);
    // Enhance Potency: fire any copy after ability decisions (block 1) but before AbilityOpportunity (block 200)
    DecisionQueueController::AddDecision($player, "CUSTOM", "CheckEnhancePotency", 99);
};

function TriggerNightmareCoilPunish($activatingPlayer) {
    $opponent = ($activatingPlayer == 1) ? 2 : 1;
    if(GlobalEffectCount($opponent, "3fe3c97s71") <= 0) return;
    $champMZ = FindChampionMZ($activatingPlayer);
    if($champMZ === null) return;
    $champObj = GetZoneObject($champMZ);
    if($champObj === null || $champObj->removed) return;
    DealUnpreventableDamage($activatingPlayer, $champMZ, $champMZ, 8);
}

function ScavengeForSubtype($player, $amount, $subtype) {
    global $playerID;
    $deckZone = $player == $playerID ? "myDeck" : "theirDeck";
    $handZone = $player == $playerID ? "myHand" : "theirHand";

    $deck = GetZone($deckZone);
    if(empty($deck)) return;

    $revealCount = min(intval($amount), count($deck));
    $foundIdx = -1;
    $revealedIDs = [];
    for($i = 0; $i < $revealCount; ++$i) {
        if($deck[$i]->removed) continue;
        $revealedIDs[] = $deck[$i]->CardID;
        if($foundIdx < 0 && PropertyContains(CardSubtypes($deck[$i]->CardID), $subtype)) {
            $foundIdx = $i;
        }
    }
    if(!empty($revealedIDs)) {
        $existing = GetFlashMessage();
        if(is_string($existing) && strpos($existing, 'REVEAL:') === 0) {
            SetFlashMessage($existing . '|' . implode('|', $revealedIDs));
        } else {
            SetFlashMessage('REVEAL:' . implode('|', $revealedIDs));
        }
    }

    if($foundIdx >= 0) {
        MZMove($player, $deckZone . "-" . $foundIdx, $handZone);
    }

    $remaining = $revealCount - ($foundIdx >= 0 ? 1 : 0);
    if($remaining <= 0) return;

    $remainingIDs = [];
    for($i = 0; $i < $remaining; ++$i) {
        $deckNow = &GetZone($deckZone);
        if(empty($deckNow)) break;
        $remainingIDs[] = $deckNow[0]->CardID;
        $deckNow[0]->Remove();
    }
    DecisionQueueController::CleanupRemovedCards();
    if(empty($remainingIDs)) return;

    EngineShuffle($remainingIDs);
    foreach($remainingIDs as $cardID) {
        MZAddZone($player, $deckZone, $cardID);
    }
}

function ScavengeForType($player, $amount, $type) {
    global $playerID;
    $deckZone = $player == $playerID ? "myDeck" : "theirDeck";
    $handZone = $player == $playerID ? "myHand" : "theirHand";

    $deck = GetZone($deckZone);
    if(empty($deck)) return;

    $revealCount = min(intval($amount), count($deck));
    $foundIdx = -1;
    $revealedIDs = [];
    for($i = 0; $i < $revealCount; ++$i) {
        if($deck[$i]->removed) continue;
        $revealedIDs[] = $deck[$i]->CardID;
        if($foundIdx < 0 && PropertyContains(CardType($deck[$i]->CardID), $type)) {
            $foundIdx = $i;
        }
    }
    if(!empty($revealedIDs)) {
        $existing = GetFlashMessage();
        if(is_string($existing) && strpos($existing, 'REVEAL:') === 0) {
            SetFlashMessage($existing . '|' . implode('|', $revealedIDs));
        } else {
            SetFlashMessage('REVEAL:' . implode('|', $revealedIDs));
        }
    }

    if($foundIdx >= 0) {
        MZMove($player, $deckZone . "-" . $foundIdx, $handZone);
    }

    $remaining = $revealCount - ($foundIdx >= 0 ? 1 : 0);
    if($remaining <= 0) return;

    $remainingIDs = [];
    for($i = 0; $i < $remaining; ++$i) {
        $deckNow = &GetZone($deckZone);
        if(empty($deckNow)) break;
        $remainingIDs[] = $deckNow[0]->CardID;
        $deckNow[0]->Remove();
    }
    DecisionQueueController::CleanupRemovedCards();
    if(empty($remainingIDs)) return;

    EngineShuffle($remainingIDs);
    foreach($remainingIDs as $cardID) {
        MZAddZone($player, $deckZone, $cardID);
    }
}

function TableStraightResolve($player) {
    $reserveCosts = [];

    $memory = &GetMemory($player);
    for($i = 0; $i < count($memory); ++$i) {
        if($memory[$i]->removed) continue;
        if(!PropertyContains(CardSubtypes($memory[$i]->CardID), "SUITED")) continue;
        DoRevealCard($player, "myMemory-" . $i);
        $reserveCosts[] = intval(CardReserveCost($memory[$i]->CardID));
    }

    $suitedAllies = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["SUITED"]);
    foreach($suitedAllies as $mz) {
        $obj = GetZoneObject($mz);
        if($obj === null || $obj->removed) continue;
        $reserveCosts[] = intval(CardReserveCost($obj->CardID));
    }

    if(empty($reserveCosts)) return;
    $unique = array_values(array_unique($reserveCosts));
    sort($unique);

    $longest = 1;
    $current = 1;
    for($i = 1; $i < count($unique); ++$i) {
        if($unique[$i] === $unique[$i - 1] + 1) {
            $current++;
        } else {
            $current = 1;
        }
        if($current > $longest) $longest = $current;
    }

    if($longest >= 7) {
        Draw($player, 3);
    } elseif($longest >= 5) {
        Draw($player, 2);
    } elseif($longest >= 2) {
        Draw($player, 1);
    }
}

function InfernoSlimeOnDeath($player, $mzID) {
    if(!IsClassBonusActive($player, ["TAMER"])) return;
    global $playerID;
    $graveyardZone = $player == $playerID ? "myGraveyard" : "theirGraveyard";
    $fires = ZoneSearch($graveyardZone, cardElements: ["FIRE"]);
    if(count($fires) < 2) return;
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Banish_two_fire_cards_to_deal_4_damage_to_each_champion?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "InfernoSlimeDeathChoice", 1);
}

$customDQHandlers["InfernoSlimeDeathChoice"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    global $playerID;
    $graveyardZone = $player == $playerID ? "myGraveyard" : "theirGraveyard";
    $fires = ZoneSearch($graveyardZone, cardElements: ["FIRE"]);
    if(count($fires) < 2) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $fires), 1, tooltip:"Choose_first_fire_card_to_banish");
    DecisionQueueController::AddDecision($player, "CUSTOM", "InfernoSlimeDeathFirst", 1);
};

$customDQHandlers["InfernoSlimeDeathFirst"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    DecisionQueueController::StoreVariable("InfernoSlimeFirst", $lastDecision);
    global $playerID;
    $graveyardZone = $player == $playerID ? "myGraveyard" : "theirGraveyard";
    $fires = array_values(array_filter(
        ZoneSearch($graveyardZone, cardElements: ["FIRE"]),
        fn($mz) => $mz !== $lastDecision
    ));
    if(empty($fires)) {
        DecisionQueueController::ClearVariable("InfernoSlimeFirst");
        return;
    }
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $fires), 1, tooltip:"Choose_second_fire_card_to_banish");
    DecisionQueueController::AddDecision($player, "CUSTOM", "InfernoSlimeDeathSecond", 1);
};

$customDQHandlers["InfernoSlimeDeathSecond"] = function($player, $parts, $lastDecision) {
    $first = DecisionQueueController::GetVariable("InfernoSlimeFirst");
    DecisionQueueController::ClearVariable("InfernoSlimeFirst");
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS" || $first === null || $first === "") return;

    global $playerID;
    $banishZone = $player == $playerID ? "myBanish" : "theirBanish";
    $firstObj = GetZoneObject($first);
    if($firstObj !== null && !$firstObj->removed) MZMove($player, $first, $banishZone);
    $secondObj = GetZoneObject($lastDecision);
    if($secondObj !== null && !$secondObj->removed) MZMove($player, $lastDecision, $banishZone);

    $myChamp = FindChampionMZ(1);
    $theirChamp = FindChampionMZ(2);
    if($myChamp !== null) DealDamage($player, "2vQVsdHJqI", $myChamp, 4);
    if($theirChamp !== null) DealDamage($player, "2vQVsdHJqI", $theirChamp, 4);
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
            $newObj = new Deck($topCards[$i], 'Deck', $player);
            array_unshift($zone, $newObj);
        }
        // Bottom pile: add to deck back
        foreach($piles["Bottom"] as $cid) {
            $newObj = new Deck($cid, 'Deck', $player);
            $zone[] = $newObj;
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

$customDQHandlers["CosmicAstroscopeGlimpse"] = function($player, $parts, $lastDecision) {
    $targetPlayer = intval($parts[0] ?? 0);
    $originalAmount = intval($parts[1] ?? 0);
    if($targetPlayer < 1) return;
    Glimpse($targetPlayer, $lastDecision === "YES" ? 3 : $originalAmount, false);
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
        // Player declined starcalling — check for aethercalling candidates before rearranging
        $aethercallStr = DecisionQueueController::GetVariable("aethercallCandidates");
        if(!empty($aethercallStr)) {
            $aethercallIndices = array_map('intval', explode(",", $aethercallStr));
            // Filter to indices that are still valid (cards still in tempzone)
            $validIndices = [];
            foreach($aethercallIndices as $idx) {
                $tObj = GetZoneObject("myTempZone-" . $idx);
                if($tObj !== null && !$tObj->removed) $validIndices[] = $idx;
            }
            if(!empty($validIndices)) {
                $candidateStr = implode("&", array_map(fn($i) => "myTempZone-$i", $validIndices));
                DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $candidateStr, 1, "Load_into_Aetherwing?");
                DecisionQueueController::AddDecision($player, "CUSTOM", "AethercallingOffer", 1);
                return;
            }
        }
        // No aethercalling — cards are in tempzone; GlimpseApply will recover them
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

/**
 * Aethercalling offer handler: player either chose a card to load into Aetherwing or passed.
 * If chosen, load the card into an Aetherwing weapon, then rearrange remaining glimpsed cards.
 * If passed, fall back to normal Glimpse rearrange.
 */
$customDQHandlers["AethercallingOffer"] = function($player, $parts, $lastDecision) {
    $n = intval(DecisionQueueController::GetVariable("glimpseCount"));
    $cardIDsStr = DecisionQueueController::GetVariable("glimpseCardIDs");
    $cardIDs = explode(",", $cardIDsStr);

    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // Declined — proceed to rearrange all tempzone cards
        $param = "Top=" . implode(",", $cardIDs) . ";Bottom=";
        DecisionQueueController::StoreVariable("glimpsedToTempZone", "1");
        DecisionQueueController::AddDecision($player, "MZREARRANGE", $param, 1, "Glimpse:_Top=return_to_top,_Bottom=put_on_bottom");
        DecisionQueueController::AddDecision($player, "CUSTOM", "GlimpseApply", 1);
        return;
    }

    // Accepted — load chosen card into Aetherwing
    $tmpParts = explode("-", $lastDecision);
    $chosenTempIndex = intval($tmpParts[1]);
    $chosenCardID = $cardIDs[$chosenTempIndex];

    // Remove chosen card from tempzone
    $tempObj = GetZoneObject($lastDecision);
    if($tempObj !== null) {
        $tempObj->removed = true;
        DecisionQueueController::CleanupRemovedCards();
    }

    // Update cardIDs for rearrange (remove loaded card)
    array_splice($cardIDs, $chosenTempIndex, 1);
    $newN = $n - 1;
    DecisionQueueController::StoreVariable("glimpseCount", strval($newN));
    DecisionQueueController::StoreVariable("glimpseCardIDs", implode(",", $cardIDs));

    // Load into Aetherwing weapon
    $wings = GetAetherwingWeapons($player);
    if(!empty($wings)) {
        if(count($wings) === 1) {
            $wingObj = &GetZoneObject($wings[0]);
            if($wingObj !== null) {
                if(!is_array($wingObj->Subcards)) $wingObj->Subcards = [];
                $wingObj->Subcards[] = $chosenCardID;
            }
        } else {
            DecisionQueueController::StoreVariable("AethercallCardID", $chosenCardID);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $wings), 1, "Choose_Aetherwing_weapon");
            DecisionQueueController::AddDecision($player, "CUSTOM", "AethercallingLoadSelect", 1);
        }
    }

    // Rearrange remaining cards
    if(!empty($cardIDs)) {
        $param = "Top=" . implode(",", $cardIDs) . ";Bottom=";
        DecisionQueueController::StoreVariable("glimpsedToTempZone", "1");
        DecisionQueueController::AddDecision($player, "MZREARRANGE", $param, 1, "Glimpse:_Top=return_to_top,_Bottom=put_on_bottom");
        DecisionQueueController::AddDecision($player, "CUSTOM", "GlimpseApply", 1);
    }
};

/**
 * Aethercalling weapon select handler: player chose which Aetherwing to load into.
 */
$customDQHandlers["AethercallingLoadSelect"] = function($player, $parts, $lastDecision) {
    $chosenCardID = DecisionQueueController::GetVariable("AethercallCardID");
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        $wingObj = &GetZoneObject($lastDecision);
        if($wingObj !== null) {
            if(!is_array($wingObj->Subcards)) $wingObj->Subcards = [];
            $wingObj->Subcards[] = $chosenCardID;
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

    // Corhazi Arsonist (0ejcyuvuxn): requires 1+ prep counter on champion
    if($obj->CardID === "0ejcyuvuxn") {
        $pField = &GetField($obj->Controller);
        foreach($pField as $fCard) {
            if(!$fCard->removed && PropertyContains(EffectiveCardType($fCard), "CHAMPION")) {
                if(GetCounterCount($fCard, "preparation") < 1) return 0;
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

    // Duxal Proclamation (473gyf0w3v): activate only if each opponent controls no allies
    if($obj->CardID === "473gyf0w3v") {
        if(!empty(ZoneSearch("theirField", ["ALLY"]))) return 0;
    }

    // Scepter of Fascination (4864k12no2): [Diao Chan Bonus] banish
    if($obj->CardID === "4864k12no2") {
        if(!IsDiaoChanBonus($obj->Controller)) return 0;
    }

    // Staff of Blossoming Will (4moumzcx9z): [Diao Chan Bonus] (1), REST
    if($obj->CardID === "4moumzcx9z") {
        if(!IsDiaoChanBonus($obj->Controller)) return 0;
        if($obj->Status != 2) return 0;
        $hand = &GetHand($obj->Controller);
        if(count($hand) < 1) return 0;
    }

    // Mana Limiter (IC3OU6vCnF): requires champion has 6+ enlighten counters
    if($obj->CardID === "IC3OU6vCnF") {
        $champObj = GetPlayerChampion($obj->Controller);
        if($champObj === null || GetCounterCount($champObj, "enlighten") < 6) return 0;
    }

    // Ticket to the Afterlife (E09lX95cb9): [Alice Bonus] + Specter in graveyard + REST
    if($obj->CardID === "E09lX95cb9") {
        if(!IsAliceBonusActive($obj->Controller)) return 0;
        if($obj->Status != 2) return 0;
        global $playerID;
        $gyZone = ($obj->Controller == $playerID) ? "myGraveyard" : "theirGraveyard";
        if(empty(ZoneSearch($gyZone, cardSubtypes: ["SPECTER"]))) return 0;
    }

    return 1;
}

// Internal tracking effects that are backend-only and should never render in the UI
$backendOnlyTurnEffects = [
    "DAMAGED_SINCE_LAST_TURN",
    "ENTERED_THIS_TURN",
    "WAS_ATTACKED",
    "CHAMP_DMG_BY_P1",
    "vz4kc558yx-ACTION_PENDING",
    "vz4kc558yx-ALLY_PENDING",
    "vz4kc558yx-ACTION_ACTIVATED",
    "vz4kc558yx-ALLY_ACTIVATED",
];

function CardCurrentEffects($obj) {
    global $doesGlobalEffectApply, $effectAppliesToBoth,$playerID;
    //Start with this object's effects (all of them, unfiltered — used by game logic)
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

function CardDisplayEffects($obj) {
    global $backendOnlyTurnEffects;
    // Same as CardCurrentEffects but strips backend-only tracking effects before returning
    $raw = CardCurrentEffects($obj);
    if($raw === "") return "";
    $effects = explode(",", $raw);
    $effects = array_values(array_filter($effects, fn($e) => !in_array($e, $backendOnlyTurnEffects)));
    return implode(",", $effects);
}

function SelectionMetadataMzID($obj) {
    global $playerID;
    if(!isset($obj->Location) || !isset($obj->PlayerID) || !isset($obj->mzIndex)) {
        return null;
    }
    $prefix = $playerID == $obj->PlayerID ? "my" : "their";
    return $prefix . $obj->Location . "-" . $obj->mzIndex;
}

function CanActivateCardForSelection($player, $obj) {
    if(!function_exists("CanActivateCard")) return true;
    $mzID = SelectionMetadataMzID($obj);
    if($mzID === null) return true;
    $existingFlash = GetFlashMessage();
    $canActivate = CanActivateCard($player, $mzID, false);
    SetFlashMessage($existingFlash);
    return $canActivate;
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
    $theirDecisionQueue = &GetDecisionQueue($turnPlayer == 1 ? 2 : 1);
    if (count($decisionQueue) > 0 || count($theirDecisionQueue) > 0) {
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
    // Return red color if there's an activation restriction
    if(!CanActivateCardForSelection($turnPlayer, $obj)) {
        return json_encode(['color' => 'rgba(255, 64, 64, 0.95)']);
    }
    // Hand reserve affordability is advisory only; rare cost branches can still make it playable.
    if(isset($obj->Location) && $obj->Location === "Hand" && !CanAffordActivationReserve($turnPlayer, $obj)) {
        return json_encode(['color' => 'rgba(255, 170, 0, 0.95)']);
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

function CombatTargetIndicator($obj) {
    if(!isset($obj->Location) || $obj->Location !== "Field") return "";
    if(!method_exists($obj, "GetMzID")) return "";

    $mzTarget = $obj->GetMzID();
    if($mzTarget === null || $mzTarget === "" || $mzTarget === "-") return "";

    return IsUnitDefending($mzTarget) ? "TARGET" : "";
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
        if(!$fieldObj->removed && PropertyContains(EffectiveCardType($fieldObj), "CHAMPION")
            && isset($fieldObj->Counters["_champDamageThisTurn"])) {
            unset($fieldObj->Counters["_champDamageThisTurn"]);
        }
        $newEffects = [];
        foreach($fieldObj->TurnEffects as $effect) {
            if(strpos($effect, "3iG6h4jAPl_CTRL_") === 0) {
                $fieldObj->Controller = intval(substr($effect, strlen("3iG6h4jAPl_CTRL_")));
                continue;
            }
            if(isset($persistentTurnEffects[$effect])) {
                $newEffects[] = $effect;
            }
            // Nia, Mistveiled Scout (PZM9uvCFai): named-card lock persists while Nia is on the field
            if(strpos($effect, "PZM9uvCFai-") === 0) {
                $newEffects[] = $effect;
            }
            // Facet Together (XmsEbk19Iu): +X POWER until end of your next turn
            if(strpos($effect, "FACET_POWER_") === 0) {
                $newEffects[] = $effect;
            }
            // Jianyu and Kingdom's Divide store chosen names as prefixed TurnEffects.
            if(strpos($effect, "qv0vn6tuow-") === 0 || strpos($effect, "qy34r8gffr-") === 0) {
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
$ephemerateCards["0r7j97g2zh"] = ['cost' => 2]; // Evercurrent Raider
$ephemerateCards["4vjkezn49t"] = ['cost' => 4]; // Vengeful Paramour
$ephemerateCards["sm68d3we64"] = ['cost' => 3, 'extraCostHandler' => 'EphemerateBanishFloating']; // Sunken Battle Priest
$ephemerateCards["v0gu8efq08"] = ['cost' => 6, 'costModifier' => function($player) {
    return CountEphemeralObjects($player) > 0 ? 3 : 0;
}]; // Lingering Banshee
$ephemerateCards["6emPe9OEUn"] = ['cost' => 2, 'extraCostHandler' => 'EphemerateDiscard']; // Treacle, Drowned Mouse
$ephemerateCards["FQigf17dCr"] = ['cost' => 1]; // Grave Gateau
$ephemerateCards["ULHGVVpQoH"] = ['cost' => 5]; // Indissoluble Fractal
$ephemerateCards["XFWU8KTVW9"] = ['cost' => 2]; // Ghastly Slime
$ephemerateCards["XK3NiQ5MdR"] = ['cost' => 1]; // Remnant of Will
$ephemerateCards["YFCfIOwNQ5"] = ['cost' => 2]; // Singeing Leap
$ephemerateCards["p7FWS3DA4a"] = ['cost' => 2]; // Molten Echo
$ephemerateCards["t2lW0Q5KJS"] = ['cost' => 2, 'condition' => function($player) {
    return IsMerlinBonusActive($player) && (GetSheenCount($player) >= 10 || PlayerLevel($player) >= 5);
}]; // Flared Iridescence
$ephemerateCards["Dtr3jPRAFJ"] = ['cost' => 6]; // Spectral Haunting
$ephemerateCards["3zvDCFRaoH"] = ['cost' => 1, 'condition' => function($player) {
    $champMZ = FindChampionMZ($player);
    if($champMZ === null) return false;
    $champObj = GetZoneObject($champMZ);
    if($champObj === null) return false;
    return intval($champObj->Damage) >= 20;
}]; // Bloodseeker Magus
$ephemerateCards["s9ICPMYPNx"] = ['cost' => 5, 'extraCostHandler' => 'EphemerateDiscard',
    'condition' => function($player) {
        return IsAliceBonusActive($player);
    }
]; // Bill, Chimney Sweep
$ephemerateCards["24I0xn0OQ1"] = ['cost' => 2, 'extraCostHandler' => 'EphemerateBanishOtherGY',
    'condition' => function($player) {
        return IsAliceBonusActive($player) && IsElementBonusActive($player, "24I0xn0OQ1");
    }
]; // Maledictum Vitae
$ephemerateCards["vA5ZmzZL9I"] = ['cost' => 2, 'condition' => function($player) {
    return IsAliceBonusActive($player);
}]; // Drown in Sorrow
$ephemerateCards["tizLamFGPS"] = ['cost' => 3, 'condition' => function($player) {
    return IsMerlinBonusActive($player) && GetSheenCount($player) >= 6;
}]; // Luster's Shroud

function GetEphemerateCost($player, $cardID) {
    global $ephemerateCards;
    if(!isset($ephemerateCards[$cardID])) return -1;
    $config = $ephemerateCards[$cardID];
    $cost = $config['cost'];
    if(isset($config['costModifier'])) {
        $cost = max(0, $cost - $config['costModifier']($player));
    }
    // Ticket to the Afterlife (E09lX95cb9): Specter cards from graveyard cost 1 less
    if(PropertyContains(CardSubtypes($cardID), "SPECTER")) {
        global $playerID;
        $ticketZone = ($player == $playerID) ? "myField" : "theirField";
        $ticketField = GetZone($ticketZone);
        foreach($ticketField as $tObj) {
            if(!$tObj->removed && $tObj->CardID === "E09lX95cb9" && !HasNoAbilities($tObj)) {
                $cost = max(0, $cost - 1);
                break;
            }
        }
    }
    return $cost;
}

function ReservePaymentSourceZoneName($player, $zoneSuffix) {
    global $playerID;
    $prefix = $player == $playerID ? "my" : "their";
    return $prefix . $zoneSuffix;
}

function GetReservablePaymentSources($player) {
    $sources = [];
    $fieldZone = ReservePaymentSourceZoneName($player, "Field");
    $field = GetField($player);
    foreach($field as $i => $fieldObj) {
        if($fieldObj->removed) continue;
        if(!isset($fieldObj->Status) || $fieldObj->Status != 2) continue;
        if(HasReservable($fieldObj)) {
            $sources[] = $fieldZone . "-" . $i;
        }
    }
    return $sources;
}

function GetReservePaymentChoiceSource($player, $includeHand = true) {
    $sources = [];
    if($includeHand) {
        $sources[] = ReservePaymentSourceZoneName($player, "Hand");
    }
    return implode("&", array_merge($sources, GetReservablePaymentSources($player)));
}

function CanPayEphemerate($player, $cardID) {
    global $ephemerateCards, $playerID;
    if(!isset($ephemerateCards[$cardID])) return false;
    // Phantasmagoria: Non-Specter cards in your graveyard lose all abilities
    if(IsPhantasmagoriaGYSuppressed($player, $cardID)) return false;
    // Condition check (e.g. Alice Bonus + Element Bonus for Maledictum Vitae)
    $config = $ephemerateCards[$cardID];
    if(isset($config['condition']) && !$config['condition']($player)) return false;
    $cost = GetEphemerateCost($player, $cardID);
    $available = CountAvailableReservePayments($player);
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
        if($config['extraCostHandler'] === 'EphemerateDiscard') {
            // Need at least 1 extra card in hand (beyond those needed for reserve cost)
            // The card is moved to hand first, so hand count includes it
            if($available < $cost + 1) return false;
        }
        if($config['extraCostHandler'] === 'EphemerateBanishOtherGY') {
            // Need at least 1 other card in graveyard (besides the ephemerated card itself)
            $gravZone = $player == $playerID ? "myGraveyard" : "theirGraveyard";
            $gy = GetZone($gravZone);
            $otherGYCount = 0;
            foreach($gy as $gObj) {
                if(!$gObj->removed && $gObj->CardID !== $cardID) $otherGYCount++;
            }
            if($otherGYCount < 1) return false;
        }
    }
    return true;
}

function CountAvailableReservePayments($player, $excludedMzID = null) {
    $available = 0;
    $hand = GetHand($player);
    $handZone = ReservePaymentSourceZoneName($player, "Hand");
    foreach($hand as $i => $handObj) {
        if(!$handObj->removed) {
            if($excludedMzID !== null && $excludedMzID === ($handZone . "-" . $i)) continue;
            ++$available;
        }
    }
    return $available + count(GetReservablePaymentSources($player));
}

function PreviewActivateReserveCost($player, $obj) {
    if($obj === null || !isset($obj->CardID)) return 0;

    $reserveCost = CardCost_reserve($obj->CardID);

    // Unstable Fractal (2o82fwl22v): [Class Bonus] ability costs (3) reserve
    if($obj->CardID === "2o82fwl22v") $reserveCost = 3;

    $reserveCost = ApplyGeneratedReserveLikeCostModifiers($player, $obj, $reserveCost, "activate");

    // Class Bonus: reduce cost if champion's class matches card's class
    $classBonusDiscount = ClassBonusActivateCostReduction($obj->CardID);
    if($classBonusDiscount > 0 && IsClassBonusActive($player, explode(",", CardClasses($obj->CardID)))) {
        $reserveCost = max(0, $reserveCost - $classBonusDiscount);
    }

    return max(0, $reserveCost);
}

function CanAffordActivationReserve($player, $obj) {
    if($obj === null || !isset($obj->CardID)) return false;
    $excludedMzID = null;
    if(isset($obj->Location) && $obj->Location === "Hand" && isset($obj->mzIndex)) {
        $excludedMzID = SelectionMetadataMzID($obj);
    }
    return CountAvailableReservePayments($player, $excludedMzID) >= PreviewActivateReserveCost($player, $obj);
}

$untilBeginTurnEffects["RYBF1HBTCS"] = true;
// Vanitas, Dominus Rex (3vkxrw9462): On Champion Hit — opponent materializations cost 1 more
$untilBeginTurnEffects["3vkxrw9462"] = true;
// Tasershot (4x7e22tk3i): On Champion Hit — level-up triggers 4 unpreventable damage
$untilBeginTurnEffects["4x7e22tk3i"] = true;
$foreverEffects["GMBTMNTM"] = true;
$effectAppliesToBoth["GMBF3HVRKG"] = true;
// Peaceful Reunion: never auto-expire (cleared manually at caster's RecollectionPhase)
$foreverEffects["wr42i6eifn"] = true;
// Freydis permanent distant: Ranger units are always distant for the rest of the game
$foreverEffects["FREYDIS_PERMANENT_DISTANT"] = true;
// Ignis Deus: for the rest of the game, non-Spirit champions you control can't level up
$foreverEffects["IGNIS_DEUS_LOCK"] = true;
// Obsequious Blow (macqlgvqo3): first card opponent activates costs +2
$foreverEffects["OBSEQUIOUS_BLOW_COST"] = true;
// Verita (4qc47amgpp) On Death: Suited allies get +1 POWER until end of next turn
// PENDING survives end-of-turn cleanup; converted to VERITA_POWER in WakeUpPhase
$foreverEffects["VERITA_POWER_PENDING"] = true;
// Don't display this effect on field cards — it's a global attack-prevention flag
$doesGlobalEffectApply["gwWociEfxb_AETHERCALLING"] = function($obj) { return false; }; // Starstrung Reading: counter only, not a field effect
$doesGlobalEffectApply["wr42i6eifn"] = function($obj) { return false; };
// Freydis permanent distant: apply to Ranger units only
$doesGlobalEffectApply["FREYDIS_PERMANENT_DISTANT"] = function($obj) {
    return PropertyContains(EffectiveCardClasses($obj), "RANGER");
};
// Verita On Death: +1 POWER to Suited allies (both PENDING and ACTIVE phases)
$doesGlobalEffectApply["VERITA_POWER_PENDING"] = function($obj) {
    return PropertyContains(EffectiveCardType($obj), "ALLY") && PropertyContains(EffectiveCardSubtypes($obj), "SUITED");
};
$doesGlobalEffectApply["VERITA_POWER"] = function($obj) {
    return PropertyContains(EffectiveCardType($obj), "ALLY") && PropertyContains(EffectiveCardSubtypes($obj), "SUITED");
};
// Castling Boon (v0yuddp71s): allies get +1/+3 LIFE until end of turn
$doesGlobalEffectApply["v0yuddp71s"] = function($obj) {
    return PropertyContains(EffectiveCardType($obj), "ALLY");
};
// Shardwing Searchlight (8bRp3n2IAn): Memorite objects gain On Hit sheen
$doesGlobalEffectApply["SHARDWING_SEARCHLIGHT_ONHIT"] = function($obj) {
    return PropertyContains(EffectiveCardSubtypes($obj), "MEMORITE");
};
// Shattered Hope (XOevViFTB3): allies enter with sheen (counter applied in FieldAfterAdd, not a field effect)
$doesGlobalEffectApply["SHATTERED_HOPE_SHEEN"] = function($obj) { return false; };
$doesGlobalEffectApply["v0yuddp71s-ROOK"] = function($obj) {
    return PropertyContains(EffectiveCardType($obj), "ALLY");
};
// Embrace Noir (pw9b6IJWEr): allies gain stealth until end of turn
$doesGlobalEffectApply["pw9b6IJWEr"] = function($obj) {
    return PropertyContains(EffectiveCardType($obj), "ALLY");
};
// Favorable Omens (tqy0rwvxgs): allies get +1 LIFE for each wind omen you have
$doesGlobalEffectApply["tqy0rwvxgs"] = function($obj) {
    return PropertyContains(EffectiveCardType($obj), "ALLY");
};
// Eminent Lethargy (GGRtLQgaYU): global attack tax — no visual card effect needed
$doesGlobalEffectApply["GGRtLQgaYU"] = function($obj) { return false; };

// Ducal Seal (qFwqqT0XWo): global attack tax (3) — no visual card effect needed
$doesGlobalEffectApply["DUCAL_SEAL_ATTACK_TAX"] = function($obj) { return false; };

// Unmoored Call (etobC7HEHw): objects with chosen reserve cost enter rested — no visual card effect
for($ucIdx = 0; $ucIdx <= 15; ++$ucIdx) {
    $doesGlobalEffectApply["UNMOORED_CALL_" . $ucIdx] = function($obj) { return false; };
}

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
$persistentTurnEffects["DISTANT"] = true; // Cleared at end of controller's turn (not every turn) in EndPhase
$persistentTurnEffects["BLAZING_CHARGE_NEXT_TURN"] = true;
$persistentTurnEffects["TAUNT_NEXT_TURN"] = true;
$persistentTurnEffects["VIGOR_NEXT_TURN"] = true;
$persistentTurnEffects["IMMORTALITY_NEXT_TURN"] = true;
$persistentTurnEffects["FREEZING_ROUND_RETURN"] = true;
$persistentTurnEffects["FOSTERED"] = true;
$persistentTurnEffects["DAMAGED_SINCE_LAST_TURN"] = true;
$persistentTurnEffects["IMBUED"] = true;
$persistentTurnEffects["VAINGLORY_NEXT_TURN"] = true;
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

$doesGlobalEffectApply["CHAMP_DEALT_COMBAT_DMG"] = function($obj) { //Flag only — tracks champion combat damage this turn
    return false;
};
$doesGlobalEffectApply["FIREBLOODED_OATH_DELEVEL"] = function($obj) { return false; };

$doesGlobalEffectApply["RAI_ARCHMAGE_TRIGGERED"] = function($obj) { //Flag only — tracks first Mage action this turn for Rai, Archmage inherited effect
    return false;
};

$doesGlobalEffectApply["RfPP8h16Wv"] = function($obj) { //Flag only — next Animal/Beast ally gets buff counter, no visual effect
    return false;
};

$doesGlobalEffectApply["4wuq20gvcg"] = function($obj) { // Key Slime Pudding: flag only — Slime allies enter with buff counter
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

$doesGlobalEffectApply["gyk90s0hst"] = function($obj) { // Gossamer Staff: champion +1 level
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

$doesGlobalEffectApply["jgyx38zpl0-east"] = function($obj) { // Bagua East: allies +2 POWER until end of turn
    return PropertyContains(EffectiveCardType($obj), "ALLY");
};

$doesGlobalEffectApply["BREWED_POTION"] = function($obj) { // Flag only — tracks if a potion was brewed this turn
    return false; // No visual effect on cards
};

$doesGlobalEffectApply["Bt9xeTum94_IGNORE_POTION_ELEMENT"] = function($obj) { // Flag only — non-advanced Potions ignore elemental requirements
    return false;
};

$doesGlobalEffectApply["EIpkYYSP3s"] = function($obj) { // Flag only — next suited spell damage +3
    return false;
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

$doesGlobalEffectApply["473gyf0w3v"] = function($obj) { //Duxal Proclamation: allies get +1 POWER
    return PropertyContains(EffectiveCardType($obj), "ALLY");
};

$doesGlobalEffectApply["4hnf1yyx1q"] = function($obj) { //Grim Foreboding: Phantasia allies get +1 POWER
    return PropertyContains(EffectiveCardType($obj), "ALLY") && PropertyContains(EffectiveCardType($obj), "PHANTASIA");
};

$doesGlobalEffectApply["i1f0ht2tsn"] = function($obj) { //Strategic Warfare: allies get +1 POWER
    return PropertyContains(EffectiveCardType($obj), "ALLY");
};

$doesGlobalEffectApply["i0a5uhjxhk"] = function($obj) { //Blightroot: champion gets +1 level
    return PropertyContains(EffectiveCardType($obj), "CHAMPION");
};

// Plea for Peace (ir99sx6q3p): flag only — attack tax handled in BeginCombatPhase
$foreverEffects["gpmJdGYqoC"] = true;
$doesGlobalEffectApply["gpmJdGYqoC"] = function($obj) { return false; };
$doesGlobalEffectApply["0sVdvpQKXq_MEMORY"] = function($obj) { return false; };
$doesGlobalEffectApply["0plqbtjuxz"] = function($obj) { return false; };

$foreverEffects["ir99sx6q3p"] = true;
$doesGlobalEffectApply["ir99sx6q3p"] = function($obj) { return false; };

// Suited Trickery (uxhmucm8si): flag only — champion attack tax handled in BeginCombatPhase
$foreverEffects["uxhmucm8si"] = true;
$doesGlobalEffectApply["uxhmucm8si"] = function($obj) { return false; };

// Eminent Lethargy (GGRtLQgaYU): flag only — attack tax handled in BeginCombatPhase
$foreverEffects["GGRtLQgaYU"] = true;

// Expeditious Opening (w1wgpeifd0): flag only — fast ally activation handled in GetPlayableFastCards
$doesGlobalEffectApply["w1wgpeifd0"] = function($obj) { return false; };

// Purging Tempest (yuo7dbge3b): flag only — GY redirect handled in DoDiscardCard/MillCards
$doesGlobalEffectApply["yuo7dbge3b"] = function($obj) { return false; };

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
$doesGlobalEffectApply["isxy5lh23q"] = function($obj) { return false; }; // Flash Grenade: prevention handled in CombatLogic

// Collapsing Trap (v2214upufo): flag only — next allies enter rested, handled in FieldAfterAdd
$doesGlobalEffectApply["COLLAPSING_TRAP"] = function($obj) { return false; };

// Bathe in Light (d9zax2g20h): flag only — delayed recover 4 at next recollection
$doesGlobalEffectApply["BATHE_IN_LIGHT_RECOVER"] = function($obj) { return false; };
$untilBeginTurnEffects["BATHE_IN_LIGHT_RECOVER"] = true;

// Cosmic Alignment (b2buhbediq): flag only — next glimpse becomes draw
$doesGlobalEffectApply["COSMIC_ALIGNMENT"] = function($obj) { return false; };

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

// Fiery Interference (gt2zqtgs42): flag only — controller can't recover until end of turn
$doesGlobalEffectApply["CANT_RECOVER"] = function($obj) { return false; };

// Invoke Dominance (PLljzdiMmq): flag only — can't activate non-ally cards this turn
$doesGlobalEffectApply["PLljzdiMmq_NO_NONALLY"] = function($obj) { return false; };

// Tasershot (4x7e22tk3i): flag only — level-up deal 4 unpreventable
$doesGlobalEffectApply["4x7e22tk3i"] = function($obj) { return false; };

// Consumption Ring (g8q7imka92): flag only — non-ally cards opponents activate cost (4) more
$doesGlobalEffectApply["CONSUMPTION_RING_COST"] = function($obj) { return false; };
$doesGlobalEffectApply["lcb6jhxctx_REACTION_DISCOUNT"] = function($obj) { return false; };
$doesGlobalEffectApply["llQe0cg4xJ_COST"] = function($obj) { return false; };

// Duplicitous Replication (owq8s5fefw): flag only — next regalia opponent materializes, summon token copy
$doesGlobalEffectApply["owq8s5fefw"] = function($obj) { return false; };

// Resolute Stand (o6gb0op3nq): flag only — skip next draw phase
$doesGlobalEffectApply["SKIP_NEXT_DRAW"] = function($obj) { return false; };
$foreverEffects["SKIP_NEXT_DRAW"] = true;

// Crystallized Anthem (XfAJlQt9hH): delayed recollection trigger
$doesGlobalEffectApply["CRYSTALLIZED_ANTHEM_RECOLLECTION"] = function($obj) { return false; };
$foreverEffects["CRYSTALLIZED_ANTHEM_RECOLLECTION"] = true;

// Arcane Disposition (blq7qXGvWH): delayed discard at next end phase
$doesGlobalEffectApply["blq7qXGvWH_DISCARD_NEXT_END"] = function($obj) { return false; };
$foreverEffects["blq7qXGvWH_DISCARD_NEXT_END"] = true;

// Curse Amplification (x9z2k2a5ig): permanent curse-lineage damage grant
$doesGlobalEffectApply["x9z2k2a5ig"] = function($obj) { return false; };
$foreverEffects["x9z2k2a5ig"] = true;

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

function ParseModifierResult($result) {
    $parsed = [
        'delta' => 0,
        'consume' => false,
        'applied' => false,
    ];
    if(is_array($result)) {
        $parsed['delta'] = intval($result['delta'] ?? 0);
        $parsed['consume'] = !empty($result['consume']);
        $parsed['applied'] = array_key_exists('applied', $result)
            ? !empty($result['applied'])
            : ($parsed['delta'] !== 0);
        return $parsed;
    }
    $parsed['delta'] = intval($result);
    $parsed['applied'] = ($parsed['delta'] !== 0);
    return $parsed;
}

function ConsumeModifierSource($sourceObj) {
    if($sourceObj === null) return false;
    if(($sourceObj->_sourceZone ?? null) === "GlobalEffects") {
        $controller = $sourceObj->Controller ?? null;
        if($controller === null) return false;
        return RemoveGlobalEffect($controller, $sourceObj->CardID);
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
            $cardLevel = ObjectCurrentLevel($obj);
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
        'nlf619svrr' => 2, // Perdition: [Class Bonus] costs 2 less
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
        'w3rrii17fz' => 2, // Flash Freeze: [Class Bonus] costs 2 less
        'a6h0rcs8sw' => 2, // Redirect Flow: [Class Bonus] costs 2 less
        'yd609g44vm' => 2, // The Constellatory Spire: [Class Bonus] costs 2 less
        'rzsr6aw4hz' => 2, // Burst Asunder: [Class Bonus] costs 2 less
        'aj7pz79wsp' => 2, // Scorching Imperilment: [Class Bonus] costs 2 less
        '6Rb25k7OjY' => 2, // Tempestuous Conviction: [Class Bonus] costs 2 less
        'QvQhg1EOBR' => 2, // Sacred Engulfment: [Class Bonus] costs 2 less
        'TO9qqKHakv' => 2, // Righteous Retribution: [Class Bonus] costs 2 less
        '3bS1Y9OQrF' => 2, // Nature's Insight: [Class Bonus] costs 2 less
        '44eld1c5ac' => 2, // Surging Undertow: [Class Bonus] costs 2 less
        '6gt6zkly69' => 2, // Shriveling Vines: [Class Bonus] costs 2 less
        'kvoqk1l75t' => 2, // Heavy Swing: [Class Bonus] costs 2 less
    ];
    return isset($reductions[$cardID]) ? $reductions[$cardID] : 0;
}

function ApplyCrystallizedDestinyPrevention($championMZ, $amount) {
    $champion = GetZoneObject($championMZ);
    if($champion === null || !in_array("CRYSTALLIZED_DESTINY", $champion->TurnEffects ?? [])) {
        return $amount;
    }
    $champion->TurnEffects = array_values(array_filter($champion->TurnEffects, fn($e) => $e !== "CRYSTALLIZED_DESTINY"));
    if($amount >= 7) {
        $opponent = ($champion->Controller == 1) ? 2 : 1;
        AddGlobalEffects($opponent, "CRYSTALLIZED_DESTINY_COST");
    }
    return 0;
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
            // PREVENT_NONCOMBAT_N: prevent up to N non-combat damage (Safeguard Paragon, etc.)
            foreach($obj->TurnEffects as $pncIdx => $pncEffect) {
                if(strpos($pncEffect, "PREVENT_NONCOMBAT_") === 0) {
                    $pncBudget = intval(substr($pncEffect, 18));
                    $pncPrevented = min($pncBudget, $amount);
                    $amount -= $pncPrevented;
                    $pncRemaining = $pncBudget - $pncPrevented;
                    if($pncRemaining <= 0) {
                        unset($obj->TurnEffects[$pncIdx]);
                        $obj->TurnEffects = array_values($obj->TurnEffects);
                    } else {
                        $obj->TurnEffects[$pncIdx] = "PREVENT_NONCOMBAT_" . $pncRemaining;
                    }
                    break;
                }
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
            // Calming Breeze (XgJ72Ot13P): if 3 or less damage, prevent it entirely
            if($amount > 0 && $amount <= 3 && in_array("CALMING_BREEZE", $obj->TurnEffects)) {
                $amount = 0;
            }
            // Righteous Retribution (TO9qqKHakv): prevent up to 5 of next damage, store prevented for power boost
            foreach($obj->TurnEffects as $rrIdx => $rrEffect) {
                if(strpos($rrEffect, "RIGHTEOUS_RETRIBUTION_") === 0) {
                    $rrBudget = intval(substr($rrEffect, strlen("RIGHTEOUS_RETRIBUTION_")));
                    $rrPrevented = min($rrBudget, $amount);
                    $amount -= $rrPrevented;
                    unset($obj->TurnEffects[$rrIdx]);
                    $obj->TurnEffects = array_values($obj->TurnEffects);
                    if($rrPrevented > 0) {
                        if(!is_array($obj->Counters)) $obj->Counters = [];
                        $obj->Counters['retribution_power'] = $rrPrevented;
                    }
                    break;
                }
            }
            $amount = ApplyCrystallizedDestinyPrevention($zone . "-" . $i, $amount);
            if($amount <= 0) {
                return $obj;
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
            TrackChampionDamageThisTurn($obj, $amount);
            TriggerSanguineGoblet($obj->Controller, $amount);
            // Assassin's Mantle (3tcs0axa03): if damage was dealt, offer banish to prevent 1 + add prep counter
            if($amount > 0) {
                $mantleZone = $player == $playerID ? "myField" : "theirField";
                $mantleArr = GetZone($mantleZone);
                for($ami = 0; $ami < count($mantleArr); ++$ami) {
                    $mantleObj = $mantleArr[$ami];
                    if(!isset($mantleObj->removed) || !$mantleObj->removed) {
                        if($mantleObj->CardID === "3tcs0axa03" && !HasNoAbilities($mantleObj)) {
                            $mantleMZ = $mantleZone . "-" . $ami;
                            DecisionQueueController::AddDecision($player, "YESNO", "-", 1, "Banish_Assassin_Mantle_prevent_1_damage?");
                            DecisionQueueController::AddDecision($player, "CUSTOM", "AssassinsMantlePrevent|" . $mantleMZ, 1);
                            break;
                        }
                    }
                }
            }
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

function DealChampionDamageAmount($player, $amount=1) {
    $champMZ = FindChampionMZ($player);
    if($champMZ === null) return 0;
    $champObj = GetZoneObject($champMZ);
    if($champObj === null) return 0;
    $beforeDamage = intval($champObj->Damage);
    DealChampionDamage($player, $amount);
    $champObj = GetZoneObject($champMZ);
    if($champObj === null) return 0;
    return max(0, intval($champObj->Damage) - $beforeDamage);
}

function ChillToTheBoneResolve($player) {
    global $playerID;
    $attackerMZ = DecisionQueueController::GetVariable("CombatAttacker");
    if($attackerMZ === null || $attackerMZ === "-" || $attackerMZ === "") return;
    $attackerObj = GetZoneObject($attackerMZ);
    if($attackerObj === null || $attackerObj->removed || !PropertyContains(EffectiveCardType($attackerObj), "ALLY")) return;
    $targets = FilterSpellshroudTargets([$attackerMZ]);
    if(empty($targets)) return;
    $fieldZone = $player == $playerID ? "myField" : "theirField";
    $phantasiaCount = count(ZoneSearch($fieldZone, ["PHANTASIA"]));
    AddTurnEffect($attackerMZ, $phantasiaCount >= 2 ? "p00ghqhcpb-4" : "p00ghqhcpb-2");
}

function PossessedReapingOnKill($player) {
    global $playerID;
    $killedCardID = DecisionQueueController::GetVariable("CombatKilledCardID");
    if($killedCardID === null || $killedCardID === "-" || $killedCardID === "") return;
    if(!PropertyContains(CardType($killedCardID), "ALLY")) return;

    $opponentGraveyard = $player == $playerID ? "theirGraveyard" : "myGraveyard";
    $ownGraveyard = $player == $playerID ? "myGraveyard" : "theirGraveyard";
    $graveyardCandidates = [];
    $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
    $targetObj = $combatTarget !== null ? GetZoneObject($combatTarget) : null;
    if($targetObj !== null && !$targetObj->removed) {
        $graveyardCandidates[] = $targetObj->Owner == $playerID ? "myGraveyard" : "theirGraveyard";
    }
    $graveyardCandidates[] = $opponentGraveyard;
    $graveyardCandidates[] = $ownGraveyard;
    $graveyardCandidates = array_values(array_unique($graveyardCandidates));

    $graveyardMZ = null;
    foreach($graveyardCandidates as $graveyardZone) {
        $graveyard = GetZone($graveyardZone);
        for($i = count($graveyard) - 1; $i >= 0; --$i) {
            if(!$graveyard[$i]->removed && $graveyard[$i]->CardID === $killedCardID) {
                $graveyardMZ = $graveyardZone . "-" . $i;
                break 2;
            }
        }
    }
    if($graveyardMZ === null) return;

    $newObj = MZMove($player, $graveyardMZ, "myField");
    if($newObj === null) return;
    $newObj->Controller = $player;
    $newObj->Status = 1;
    $subtypes = EffectiveCardSubtypes($newObj);
    if(!PropertyContains($subtypes, "SPIRIT")) {
        ApplyPersistentOverride($newObj->GetMzID(), ["subtypes" => $subtypes . ",SPIRIT"]);
    }
}

function AnnihilationResolve($player, $amount) {
    $amount = max(0, min(7, intval($amount)));
    $damageDealt = DealChampionDamageAmount($player, $amount);

    $targets = array_merge(ZoneSearch("myField"), ZoneSearch("theirField"));
    foreach($targets as $mzID) {
        $obj = GetZoneObject($mzID);
        if($obj === null || $obj->removed || PropertyContains(EffectiveCardType($obj), "CHAMPION")) continue;
        if(intval(CardCost_reserve($obj->CardID)) === $damageDealt) {
            DoAllyDestroyed($player, $mzID);
        }
    }
    DecisionQueueController::CleanupRemovedCards();
}

function RecoverChampion($player, $amount=1) {
    global $playerID;

    // Fiery Interference (gt2zqtgs42): controller can't recover until end of turn
    if(GlobalEffectCount($player, "CANT_RECOVER") > 0) {
        return null;
    }

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

    // Infernal Vessel (vgWgu1DUYv): if any player controls it, reduce recover amount by 3
    $allField = array_merge(GetZone("myField"), GetZone("theirField"));
    foreach($allField as $ivObj) {
        if(!$ivObj->removed && $ivObj->CardID === "vgWgu1DUYv" && !HasNoAbilities($ivObj)) {
            $amount = max(0, $amount - 3);
            break;
        }
    }
    if($amount <= 0) return null;

    // Transfusive Aura (7qWYuRNoYI): [Damage 20+] recover 2+X instead of X.
    // Stacking is additive per active copy (+2 each).
    $champMZ = FindChampionMZ($player);
    if($champMZ !== null) {
        $champObj = GetZoneObject($champMZ);
        if($champObj !== null && intval($champObj->Damage ?? 0) >= 20) {
            $bonusCopies = 0;
            $field = &GetField($player);
            for($i = 0; $i < count($field); ++$i) {
                if(!$field[$i]->removed && $field[$i]->CardID === "7qWYuRNoYI" && !HasNoAbilities($field[$i])) {
                    $bonusCopies++;
                }
            }
            if($bonusCopies > 0) $amount += 2 * $bonusCopies;
        }
    }

    for($i = 0; $i < count($zoneArr); ++$i) {
        $obj = &$zoneArr[$i];
        if(PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
            $obj->Damage = max(0, $obj->Damage - $amount);

            $field = GetField($player);
            $fieldZone = $player == $playerID ? "myField" : "theirField";
            for($fi = 0; $fi < count($field); ++$fi) {
                if($field[$fi]->removed || $field[$fi]->CardID !== "2jgiM0p4dt" || HasNoAbilities($field[$fi])) continue;
                if(!IsClassBonusActive($player, ["ASSASSIN"])) continue;
                AddTurnEffect($fieldZone . "-" . $fi, "2jgiM0p4dt_RECOVER_" . intval($amount));
                if(intval($amount) >= 4) AddTurnEffect($fieldZone . "-" . $fi, "UNBLOCKABLE");
            }

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
    foreach($field as $i => $obj) {
        if($obj->removed || $obj->CardID !== "l94wp7qjwb" || HasNoAbilities($obj)) continue;
        if($amount > GetCounterCount($obj, "root")) {
            AddCounters($player, $zone . "-" . $i, "root", 1);
        }
    }
    foreach($field as $i => $obj) {
        if($obj->removed || $obj->CardID !== "AxHzxEHBHZ" || HasNoAbilities($obj)) continue;
        AddTurnEffect($zone . "-" . $i, "AxHzxEHBHZ_EMPOWER_" . intval($amount));
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
 * Diana, Haunt Reminiscence (qp2r93Bgpj): [CB] Curse cards in your champion's lineage lose all abilities.
 * Returns true if curse lineage abilities should be suppressed for this player.
 */
function AreCurseLineageAbilitiesSuppressed($player) {
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    foreach($field as $fObj) {
        if(!$fObj->removed && $fObj->CardID === "qp2r93Bgpj" && !HasNoAbilities($fObj)
            && IsClassBonusActive($player, ["RANGER"])) {
            return true;
        }
    }
    return false;
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

function IsVanitasBonusActive($player) {
    return ChampionHasInLineage($player, "x8bd7ozuj6")  // Vanitas, Obliviate Schemer
        || ChampionHasInLineage($player, "8m69iq4d5v")  // Vanitas, Convergent Ruin
        || ChampionHasInLineage($player, "3vkxrw9462"); // Vanitas, Dominus Rex
}

function IsPolkhawkBonusActive($player) {
    return ChampionHasInLineage($player, "ryvfq3huqj")  // Polkhawk, Bombastic Shot
        || ChampionHasInLineage($player, "8eyeqhc37y"); // Polkhawk, Boisterous Riot
}

// --- Alice Chessman Helpers ---

function IsAliceBonusActive($player) {
    return ChampionHasInLineage($player, "daip7s9ztd")  // Alice, Golden Queen (L1)
        || ChampionHasInLineage($player, "9K4etFOi4M") // Alice, Whim's Monarch (L2)
        || ChampionHasInLineage($player, "GiQxfpKTUC") // Alice, Distorted Queen (L1)
        || ChampionHasInLineage($player, "emqOANitoD"); // Alice, Phantom Monarch (L2)
}

// Make $player's champion additionally an ASCENDANT type (persistent override).
function MakeChampionAscendant($player) {
    $champMZ = FindChampionMZ($player);
    if($champMZ === null) return;
    $champObj = &GetZoneObject($champMZ);
    if($champObj === null) return;
    $currentType = EffectiveCardType($champObj);
    if(!PropertyContains($currentType, "ASCENDANT")) {
        ApplyPersistentOverride($champMZ, ['type' => $currentType . ",ASCENDANT"]);
    }
}

// Returns true if $player's champion has been made an ASCENDANT.
function IsChampionAscendant($player) {
    $champMZ = FindChampionMZ($player);
    if($champMZ === null) return false;
    $champObj = GetZoneObject($champMZ);
    if($champObj === null) return false;
    return PropertyContains(EffectiveCardType($champObj), "ASCENDANT");
}

function SummonPawnPieceToken($player, $count = 1) {
    global $playerID;
    for($i = 0; $i < $count; ++$i) {
        MZAddZone($player, "myField", "Rpr6yCQKU6");
        $zone = $player == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        $newIdx = count($field) - 1;
        OnChessmanAllyEntered($player, $zone . "-" . $newIdx);
    }
}

function SummonQueenPieceToken($player) {
    global $playerID;
    MZAddZone($player, "myField", "m69XrVkaVh");
    $zone = $player == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    $newIdx = count($field) - 1;
    OnChessmanAllyEntered($player, $zone . "-" . $newIdx);
}

function OnChessmanAllyEntered($player, $mzID) {
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    // Field of Ranks and Files (W0WfIEDs3n): first Chessman ally enter each of your turns → +2 POWER until EOT
    $turnPlayer = &GetTurnPlayer();
    if($player === $turnPlayer && GlobalEffectCount($player, "W0WfIEDs3n_ALLY") == 0) {
        foreach($field as $fieldObj) {
            if(!$fieldObj->removed && $fieldObj->CardID === "W0WfIEDs3n") {
                AddGlobalEffects($player, "W0WfIEDs3n_ALLY");
                AddTurnEffect($mzID, "W0WfIEDs3n");
                break;
            }
        }
    }
    // Queen's Gambit (NGAy4rNwUo): [Alice Bonus] Chessman allies "On Enter: +1 POWER until EOT"
    if(IsAliceBonusActive($player)) {
        foreach($field as $fieldObj) {
            if(!$fieldObj->removed && $fieldObj->CardID === "NGAy4rNwUo" && !HasNoAbilities($fieldObj)) {
                AddTurnEffect($mzID, "NGAy4rNwUo");
                break;
            }
        }
    }
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

function GainServilePossessions($player) {
    $mastery = &GetMastery($player);
    // Clear any existing mastery
    while(count($mastery) > 0) array_splice($mastery, 0, 1);
    AddMastery($player, CardID:"0d93t7bfwc");
}

function HasServilePossessionsMastery($player) {
    $mastery = &GetMastery($player);
    return !empty($mastery) && $mastery[0]->CardID === "0d93t7bfwc";
}

// --- Fractured Memories Mastery (UAJGQFbXjs) ---

function HasFracturedMemories($player) {
    $mastery = &GetMastery($player);
    return !empty($mastery) && $mastery[0]->CardID === "UAJGQFbXjs";
}

function GainFracturedMemories($player) {
    $mastery = &GetMastery($player);
    while(count($mastery) > 0) array_splice($mastery, 0, 1);
    return AddMastery($player, CardID:"UAJGQFbXjs", Direction:"NONE", Counters:[]);
}

function GetSheenCount($player) {
    $mastery = &GetMastery($player);
    if(empty($mastery) || $mastery[0]->CardID !== "UAJGQFbXjs") return 0;
    return GetCounterCount($mastery[0], "sheen");
}

function AddSheenToMastery($player, $amount) {
    if($amount <= 0) return;
    $mastery = &GetMastery($player);
    if(empty($mastery) || $mastery[0]->CardID !== "UAJGQFbXjs") return;
    if(!isset($mastery[0]->Counters) || !is_array($mastery[0]->Counters)) $mastery[0]->Counters = [];
    if(!isset($mastery[0]->Counters["sheen"])) $mastery[0]->Counters["sheen"] = 0;
    $mastery[0]->Counters["sheen"] += $amount;
}

function RemoveSheenFromMastery($player, $amount) {
    if($amount <= 0) return 0;
    $mastery = &GetMastery($player);
    if(empty($mastery) || $mastery[0]->CardID !== "UAJGQFbXjs") return 0;
    $current = GetCounterCount($mastery[0], "sheen");
    $removed = min($amount, $current);
    $mastery[0]->Counters["sheen"] = $current - $removed;
    if($mastery[0]->Counters["sheen"] <= 0) unset($mastery[0]->Counters["sheen"]);
    return $removed;
}

// --- Phantasmagoria Mastery (D3rexaXCBo) ---

function HasPhantasmagoria($player) {
    $mastery = &GetMastery($player);
    return !empty($mastery) && $mastery[0]->CardID === "D3rexaXCBo";
}

function GainPhantasmagoria($player) {
    $mastery = &GetMastery($player);
    while(count($mastery) > 0) array_splice($mastery, 0, 1);
    return AddMastery($player, CardID:"D3rexaXCBo", Direction:"NONE", Counters:[]);
}

function GetHauntCount($player) {
    $mastery = &GetMastery($player);
    if(empty($mastery) || $mastery[0]->CardID !== "D3rexaXCBo") return 0;
    return GetCounterCount($mastery[0], "haunt");
}

function AddHauntToMastery($player, $amount) {
    if($amount <= 0) return;
    $mastery = &GetMastery($player);
    if(empty($mastery) || $mastery[0]->CardID !== "D3rexaXCBo") return;
    if(!isset($mastery[0]->Counters) || !is_array($mastery[0]->Counters)) $mastery[0]->Counters = [];
    if(!isset($mastery[0]->Counters["haunt"])) $mastery[0]->Counters["haunt"] = 0;
    $mastery[0]->Counters["haunt"] += $amount;
}

function GetHauntCounterCount($obj) {
    return GetCounterCount($obj, "haunt");
}

/**
 * Phantasmagoria: Non-Specter cards in your graveyard lose all abilities.
 * Returns true if the card's GY abilities should be suppressed.
 */
function IsPhantasmagoriaGYSuppressed($player, $cardID) {
    if(!HasPhantasmagoria($player)) return false;
    if(PropertyContains(CardSubtypes($cardID), "SPECTER")) return false;
    return true;
}

/**
 * Virtual property callback for Mastery zone: sheen counter display badge.
 */
function GetSheenCounterCount($obj) {
    return GetCounterCount($obj, "sheen");
}

// --- Merlin Bonus ---

function IsMerlinBonusActive($player) {
    return ChampionHasInLineage($player, "6R8XmWoKLn")  // Merlin, Memorite Vassal (L1)
        || ChampionHasInLineage($player, "dPP9I4nVn0")  // Merlin, Amethyst's Glow (L2)
        || ChampionHasInLineage($player, "2TCyILvBYa"); // Merlin, Brilliant Vestige (L3)
}

function TriggerSanguineGoblet($player, $amount) {
    if($amount <= 0) return;
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    foreach($field as $fi => $fObj) {
        if(!$fObj->removed && $fObj->CardID === "mnz5kgifhd" && !HasNoAbilities($fObj)) {
            AddCounters($player, $zone . "-" . $fi, "blood", $amount);
        }
    }
}

// --- Memorite token summoning ---

function SummonMemorite($player, $cardID) {
    MZAddZone($player, "myField", $cardID);
}

$customDQHandlers["TonorisTokenWeaponBuff"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $amount = isset($parts[0]) ? intval($parts[0]) : 0;
    if($amount <= 0) return;
    AddTurnEffect($lastDecision, "TONORIS_TOKEN_WEAPON_" . $amount);
};

$customDQHandlers["CrestAllianceChoice"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $crestMZ = $parts[0] ?? "";
    $crestObj = GetZoneObject($crestMZ);
    if($crestObj === null || $crestObj->removed) return;
    MZMove($player, $crestMZ, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    Draw($player, 1);
};

function HymnOfGaiasGraceMaybeRedirect($player, $allyMZ) {
    $champMZ = FindChampionMZ($player);
    if($champMZ === null) return;
    $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
    if($combatTarget === null || $combatTarget === "-" || $combatTarget === "") return;
    if($combatTarget !== $champMZ) return;
    DecisionQueueController::StoreVariable("HymnGaiaGraceAlly", $allyMZ);
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Redirect_attack_to_the_entered_ally?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "HymnGaiaGraceRedirect", 1);
}

$customDQHandlers["HymnGaiaGraceRedirect"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $allyMZ = DecisionQueueController::GetVariable("HymnGaiaGraceAlly");
    if($allyMZ === null || $allyMZ === "-" || $allyMZ === "") return;
    $allyObj = GetZoneObject($allyMZ);
    if($allyObj === null || $allyObj->removed) return;
    DecisionQueueController::StoreVariable("CombatTarget", $allyMZ);
};

function ApplyCrystallineRealityMode($player, $mode) {
    switch($mode) {
        case 0:
            SummonMemorite($player, "nZFkDcvpaY");
            break;
        case 1:
            $champMZ = FindChampionMZ($player);
            if($champMZ !== null) {
                AddTurnEffect($champMZ, "TRUE_SIGHT");
            }
            break;
        case 2:
            DrawIntoMemory($player, 1);
            break;
    }
}

function CrystallineRealityAskMode($player, $mode) {
    $chosen = intval(DecisionQueueController::GetVariable("CrystallineRealityChosen"));
    $needed = intval(DecisionQueueController::GetVariable("CrystallineRealityNeeded"));
    if($chosen >= $needed || $mode >= 3) return;

    $remainingModes = 3 - $mode;
    if($chosen + $remainingModes <= $needed) {
        for($applyMode = $mode; $applyMode < 3; ++$applyMode) {
            ApplyCrystallineRealityMode($player, $applyMode);
        }
        DecisionQueueController::StoreVariable("CrystallineRealityChosen", strval($needed));
        return;
    }

    $tooltips = [
        "Choose_Crystalline_Reality_mode:_Summon_a_Memorite_Blade?",
        "Choose_Crystalline_Reality_mode:_Champion_gains_true_sight?",
        "Choose_Crystalline_Reality_mode:_Draw_a_card_into_memory?"
    ];
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:$tooltips[$mode]);
    DecisionQueueController::AddDecision($player, "CUSTOM", "CrystallineRealityMode|" . $mode, 1);
}

function CrystallineRealityStart($player) {
    if(IsMerlinBonusActive($player)) {
        AddPrepCounter($player, 1);
    }
    $needed = DecisionQueueController::GetVariable("wasPrepared") === "YES" ? 2 : 1;
    DecisionQueueController::StoreVariable("CrystallineRealityChosen", "0");
    DecisionQueueController::StoreVariable("CrystallineRealityNeeded", strval($needed));
    CrystallineRealityAskMode($player, 0);
}

$customDQHandlers["CrystallineRealityMode"] = function($player, $parts, $lastDecision) {
    $mode = isset($parts[0]) ? intval($parts[0]) : 0;
    $chosen = intval(DecisionQueueController::GetVariable("CrystallineRealityChosen"));
    if($lastDecision === "YES") {
        ApplyCrystallineRealityMode($player, $mode);
        ++$chosen;
        DecisionQueueController::StoreVariable("CrystallineRealityChosen", strval($chosen));
    }
    CrystallineRealityAskMode($player, $mode + 1);
};

function SlimesBlessingAskTarget($player) {
    $chosenRaw = DecisionQueueController::GetVariable("SlimesBlessingChosen");
    $chosen = $chosenRaw === null || $chosenRaw === "" ? [] : explode("|", $chosenRaw);
    if(count($chosen) >= 3) return;

    $units = array_merge(
        ZoneSearch("myField", ["ALLY", "CHAMPION"]),
        ZoneSearch("theirField", ["ALLY", "CHAMPION"])
    );
    $units = FilterSpellshroudTargets($units);
    $available = array_values(array_diff($units, $chosen));
    if(empty($available)) return;

    $pickNumber = count($chosen) + 1;
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $available), 1,
        tooltip:"Choose_up_to_three_units_(" . $pickNumber . "_of_3)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SlimesBlessingChoose", 1);
}

function SlimesBlessingResolve($player) {
    $chosenRaw = DecisionQueueController::GetVariable("SlimesBlessingChosen");
    if($chosenRaw === null || $chosenRaw === "") return;
    $chosen = explode("|", $chosenRaw);
    foreach($chosen as $targetMZ) {
        $targetObj = GetZoneObject($targetMZ);
        if($targetObj === null || $targetObj->removed) continue;
        if(PropertyContains(EffectiveCardType($targetObj), "CHAMPION")) {
            AddCounters($player, $targetMZ, "level", 1);
        }
        if(PropertyContains(EffectiveCardType($targetObj), "ALLY")
            && PropertyContains(EffectiveCardSubtypes($targetObj), "SLIME")) {
            AddCounters($player, $targetMZ, "buff", 1);
        }
    }
}

function SlimesBlessingStart($player) {
    DecisionQueueController::StoreVariable("SlimesBlessingChosen", "");
    SlimesBlessingAskTarget($player);
}

$customDQHandlers["SlimesBlessingChoose"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        SlimesBlessingResolve($player);
        return;
    }

    $chosenRaw = DecisionQueueController::GetVariable("SlimesBlessingChosen");
    $chosen = $chosenRaw === null || $chosenRaw === "" ? [] : explode("|", $chosenRaw);
    if(!in_array($lastDecision, $chosen)) {
        $chosen[] = $lastDecision;
        DecisionQueueController::StoreVariable("SlimesBlessingChosen", implode("|", $chosen));
    }

    if(count($chosen) >= 3) {
        SlimesBlessingResolve($player);
        return;
    }
    SlimesBlessingAskTarget($player);
};

function CompanionFatestoneEnter($player, $mzID) {
    $field = GetField($player);
    $mzParts = explode("-", $mzID);
    $selfIndex = isset($mzParts[1]) ? intval($mzParts[1]) : -1;
    $selfMZ = (isset($field[$selfIndex]) && !$field[$selfIndex]->removed && $field[$selfIndex]->CardID === "izf4wdsbz9")
        ? "myField-" . $selfIndex
        : $mzID;
    $choices = [$selfMZ];
    foreach($field as $fi => $fieldObj) {
        if($fieldObj->removed) continue;
        if(!PropertyContains(EffectiveCardType($fieldObj), "ALLY")) continue;
        if(!PropertyContains(EffectiveCardSubtypes($fieldObj), "FATEBOUND")) continue;
        $targetMZ = "myField-" . $fi;
        if(!in_array($targetMZ, $choices)) {
            $choices[] = $targetMZ;
        }
    }
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $choices), 1,
        tooltip:"Put_a_buff_counter_on_Companion_Fatestone_or_a_Fatebound_ally");
    DecisionQueueController::AddDecision($player, "CUSTOM", "CompanionFatestoneEnter", 1);
}

$customDQHandlers["CompanionFatestoneEnter"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddCounters($player, $lastDecision, "buff", 1);
};

function CountFatestoneOrFateboundObjects($player) {
    $count = 0;
    foreach(GetField($player) as $obj) {
        if($obj->removed) continue;
        $subtypes = EffectiveCardSubtypes($obj);
        if(PropertyContains($subtypes, "FATESTONE") || PropertyContains($subtypes, "FATEBOUND")) {
            ++$count;
        }
    }
    return $count;
}

function FatestoneOfRevelationsRevealChoices($exclude = []) {
    $choices = [];
    foreach(["myHand", "myMemory"] as $zoneName) {
        $zone = GetZone($zoneName);
        for($i = 0; $i < count($zone); ++$i) {
            if($zone[$i]->removed) continue;
            $mzID = $zoneName . "-" . $i;
            if(in_array($mzID, $exclude)) continue;
            $subtypes = CardSubtypes($zone[$i]->CardID);
            if(PropertyContains($subtypes, "FATESTONE") || PropertyContains($subtypes, "FATEBOUND")) {
                $choices[] = $mzID;
            }
        }
    }
    return $choices;
}

function FatestoneOfRevelationsEnter($player) {
    $choices = FatestoneOfRevelationsRevealChoices();
    if(count($choices) < 2) return;
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $choices), 1,
        tooltip:"Reveal_a_Fatestone_or_Fatebound_card_from_hand_or_memory");
    DecisionQueueController::AddDecision($player, "CUSTOM", "FatestoneOfRevelationsEnterFirst", 1);
}

$customDQHandlers["FatestoneOfRevelationsEnterFirst"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $choices = FatestoneOfRevelationsRevealChoices();
    if(!in_array($lastDecision, $choices)) return;
    $remaining = FatestoneOfRevelationsRevealChoices([$lastDecision]);
    if(empty($remaining)) return;
    DecisionQueueController::StoreVariable("FatestoneOfRevelationsFirst", $lastDecision);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $remaining), 1,
        tooltip:"Reveal_a_second_Fatestone_or_Fatebound_card_from_hand_or_memory");
    DecisionQueueController::AddDecision($player, "CUSTOM", "FatestoneOfRevelationsEnterSecond", 1);
};

$customDQHandlers["FatestoneOfRevelationsEnterSecond"] = function($player, $parts, $lastDecision) {
    $first = DecisionQueueController::GetVariable("FatestoneOfRevelationsFirst");
    if($first === null || $first === "" || $lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    if($first === $lastDecision) return;
    $choices = FatestoneOfRevelationsRevealChoices();
    if(!in_array($first, $choices) || !in_array($lastDecision, $choices)) return;
    DoRevealCard($player, $first);
    DoRevealCard($player, $lastDecision);
    Draw($player, 1);
};

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

    // Venerable Sage (FwPdj4PkSS): [Kongming Bonus] when Shifting Currents change,
    // CARDNAME gets +1 POWER and +1 LIFE until end of turn.
    if(IsKongmingBonus($player)) {
        for($i = 0; $i < count($field); ++$i) {
            if(!$field[$i]->removed && $field[$i]->CardID === "FwPdj4PkSS" && !HasNoAbilities($field[$i])) {
                AddTurnEffect("myField-" . $i, "FwPdj4PkSS");
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

$shiftingCurrentsTransitions["SOUTH->EAST"]["l17uc67eaq"] = function($player, $mzID) {
    $obj = GetZoneObject($mzID);
    if($obj === null || $obj->removed || $obj->Status != 2) return;
    DecisionQueueController::StoreVariable("TaijiCrystalStrategemsMZ", $mzID);
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Rest_Taiji_of_Crystal_Strategems?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "TaijiCrystalStrategemsRest", 1);
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
    if($obj !== null && $obj->CardID === "r1zd9ys1qc") {
        return;
    }
    // Cataleptic Constellation (lflzwiiewz): can't wake up while controller controls it
    if(isset($obj->Counters['_catcon_controller'])) {
        $lockerPlayer = $obj->Counters['_catcon_controller'];
        global $playerID;
        $lockerZone = ($lockerPlayer == $playerID) ? "myField" : "theirField";
        $lockerField = GetZone($lockerZone);
        foreach($lockerField as $fObj) {
            if(!$fObj->removed && $fObj->CardID === "lflzwiiewz") {
                return; // Can't wake up — Cataleptic Constellation still on field
            }
        }
        // Cataleptic Constellation no longer on field — clear the lock
        unset($obj->Counters['_catcon_controller']);
    }
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

function TonorisCreationsWillActive($player) {
    $field = &GetField($player);
    foreach($field as $obj) {
        if(!$obj->removed && $obj->CardID === "n2jnltv5kl" && !HasNoAbilities($obj)) {
            return true;
        }
    }
    return false;
}

function HasFloatingMemory($obj) {
    // Censer of Restful Peace (0nlhgqpckq): cards in graveyards lose all abilities (including floating memory)
    if(ZoneContainsCardID("myField", "0nlhgqpckq") || ZoneContainsCardID("theirField", "0nlhgqpckq")) return false;
    // Phantasmagoria: Non-Specter cards in your graveyard lose all abilities
    if(isset($obj->Controller) && IsPhantasmagoriaGYSuppressed($obj->Controller, $obj->CardID)) return false;
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
    // Deadly Opportunist (eyvxonorcs): [Class Bonus] Floating Memory
    if($obj->CardID === "eyvxonorcs" && IsClassBonusActive($obj->Controller, ["ASSASSIN"])) return true;
    // Martial Guard (nsdwmxz1vd): [Class Bonus][Level 2+] Floating Memory
    if($obj->CardID === "nsdwmxz1vd" && IsClassBonusActive($obj->Controller, ["GUARDIAN"]) && PlayerLevel($obj->Controller) >= 2) return true;
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
    // Dredging Streams (wmt0x5zado): [Level 2+] Floating Memory
    if($obj->CardID === "wmt0x5zado" && PlayerLevel($obj->Controller) >= 2) return true;
    // Weaving Manastream (wi4f59furp): [Class Bonus] Floating Memory
    if($obj->CardID === "wi4f59furp" && IsClassBonusActive($obj->Controller, ["RANGER"])) return true;
    // Mire Reparation (7imoz7vrlr): [Class Bonus] Floating Memory
    if($obj->CardID === "7imoz7vrlr" && IsClassBonusActive($obj->Controller, ["GUARDIAN"])) return true;
    // Spark Link (PUgqk3lxq6): [Level 1+] Floating Memory
    if($obj->CardID === "PUgqk3lxq6" && PlayerLevel($obj->Controller) >= 1) return true;
    // Limitless Slime (s4vxfy51ec): [Class Bonus] [Level 1+] Floating Memory
    if($obj->CardID === "s4vxfy51ec" && IsClassBonusActive($obj->Controller, ["TAMER"]) && PlayerLevel($obj->Controller) >= 1) return true;
    // Undercurrent Vantage (xicxo661ly): [Class Bonus] Floating Memory
    if($obj->CardID === "xicxo661ly" && IsClassBonusActive($obj->Controller, ["RANGER"])) return true;
    // Diablerie (0plqbtjuxz): [Vanitas Bonus] Floating Memory
    if($obj->CardID === "0plqbtjuxz" && IsVanitasBonusActive($obj->Controller)) return true;
    // Art of War (fjne9ri261): Divine Relic
    if($obj->CardID === "fjne9ri261") return true;
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
    if(isset($obj->Location) && $obj->Location === 'Memory' && isset($obj->Controller)) {
        $opponent = ($obj->Controller == 1) ? 2 : 1;
        if(GlobalEffectCount($opponent, "0sVdvpQKXq_MEMORY") > 0) return true;
    }
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
        if(isset($obj->Controller)) {
            $opponent = ($obj->Controller == 1) ? 2 : 1;
            if(GlobalEffectCount($opponent, "0sVdvpQKXq_MEMORY") > 0) return "NORM";
        }
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
    // Humpty Dumpty (aou4be9z82): becomes ally in addition to its other types until EOT
    if(in_array("HUMPTY_ALLY", $obj->TurnEffects ?? [])) {
        $base = CardType($obj->CardID);
        if(!PropertyContains($base, "ALLY")) {
            return $base . ",ALLY";
        }
        return $base;
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
    // Ally Link: Beastsoul Visage (8asbierp5k) linked ally becomes a Beast
    $subtypes = CardSubtypes($obj->CardID);
    $linkedCards = GetLinkedCards($obj);
    foreach($linkedCards as $linkedObj) {
        if($linkedObj->CardID === "8asbierp5k" && !PropertyContains($subtypes, "BEAST")) {
            $subtypes = $subtypes ? $subtypes . ",BEAST" : "BEAST";
            break;
        }
    }
    return $subtypes;
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
    $classes = CardClasses($obj->CardID);

    // Lesser Boon of Apollo (9hA48XL1xV): your champion is Ranger in addition to its other classes.
    if(PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fieldObj) {
            if($fieldObj->removed || $fieldObj->CardID !== "9hA48XL1xV" || HasNoAbilities($fieldObj)) continue;
            if(!PropertyContains($classes, "RANGER")) {
                $classes = $classes === null || $classes === "" ? "RANGER" : $classes . ",RANGER";
            }
            break;
        }

        // Lesser Boon of Veilara (GHS9GraLDo): your champion is Cleric in addition
        // to its other classes.
        foreach($field as $fieldObj) {
            if($fieldObj->removed || $fieldObj->CardID !== "GHS9GraLDo" || HasNoAbilities($fieldObj)) continue;
            if(!PropertyContains($classes, "CLERIC")) {
                $classes = $classes === null || $classes === "" ? "CLERIC" : $classes . ",CLERIC";
            }
            break;
        }
    }

    return $classes;
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
    if($obj->CardID === "0v8zzzb83i" && GetCounterCount($obj, "buff") >= 2) return true;
    if(HasKeyword_Vigor($obj)) return true;
    // VIGOR_EOT TurnEffect: granted vigor until end of turn (e.g. Assemble the Ancients)
    if(in_array("VIGOR_EOT", $obj->TurnEffects)) return true;
    // VIGOR_NEXT_TURN: granted vigor until beginning of controller's next turn (e.g. Rousing Slam)
    if(in_array("VIGOR_NEXT_TURN", $obj->TurnEffects)) return true;
    // Uther, Illustrious King (5h8asbierp): always has Vigor
    if($obj->CardID === "5h8asbierp") return true;
    // Avatar of Genbu (67CIhG8hmG): [Guo Jia Bonus][Deluge 12] has vigor
    if($obj->CardID === "67CIhG8hmG" && IsGuoJiaBonus($obj->Controller) && DelugeAmount($obj->Controller) >= 12) return true;
    // Andronika, Eternal Herald (vw2ifz1nr5): has vigor while imbued
    if($obj->CardID === "vw2ifz1nr5" && in_array("IMBUED", $obj->TurnEffects ?? [])) return true;
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
    // Imperial Panzer (46neis2lho): [CB] vigor while fostered
    if($obj->CardID === "46neis2lho" && IsFostered($obj) && IsClassBonusActive($obj->Controller, ["GUARDIAN"])) return true;
    // Zhang Fei, Spirited Steel (qxnv0jqeym): [CB] Vigor
    if($obj->CardID === "qxnv0jqeym" && IsClassBonusActive($obj->Controller, ["WARRIOR"])) return true;
    // Slime Totem (jwanjcy453): Slime allies you control have vigor
    if(PropertyContains(EffectiveCardType($obj), "ALLY") && PropertyContains(EffectiveCardSubtypes($obj), "SLIME")) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fObj) {
            if(!$fObj->removed && $fObj->CardID === "jwanjcy453" && !HasNoAbilities($fObj)) {
                return true;
            }
        }
    }
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
    // Effluve Guard (5tz8bwcoel): [Ciel Bonus] as omen, Vacuous Servants get vigor
    if($obj->CardID === "L67r0GlRHR" && IsCielBonusActive($obj->Controller)) {
        $omens = GetOmens($obj->Controller);
        foreach($omens as $oObj) {
            if($oObj->CardID === "5tz8bwcoel") return true;
        }
    }
    // Chance, Seven of Spades (DKoSnhjX18): Cardistry — other Suited allies have vigor
    if(PropertyContains(EffectiveCardType($obj), "ALLY") && PropertyContains(EffectiveCardSubtypes($obj), "SUITED")) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fObj) {
            if(!$fObj->removed && $fObj->CardID === "DKoSnhjX18" && !HasNoAbilities($fObj)
               && $fObj !== $obj && in_array("DKoSnhjX18", $fObj->TurnEffects)) {
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
    if($obj->CardID === "0v8zzzb83i" && GetCounterCount($obj, "buff") >= 2) return true;
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
    if($obj->CardID === "0v8zzzb83i" && GetCounterCount($obj, "buff") >= 2) return true;
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
    // Sworn Windhand (9ewgUjy34b): always has Retort (value is 2+omen count)
    if($obj->CardID === "9ewgUjy34b") return true;
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
    if($obj->CardID === "0v8zzzb83i" && GetCounterCount($obj, "buff") >= 2) return 2;
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
    // Sworn Windhand (9ewgUjy34b): Retort 2+X where X = omen count
    if($obj->CardID === "9ewgUjy34b") return 2 + GetOmenCount($obj->Controller);
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
    // Expose Darkness (991ovfr8o0): loses stealth until end of turn
    if(in_array("LOSE_STEALTH", $obj->TurnEffects)) return false;
    // Reveal the Hidden (rHccTUUWou): can't gain stealth until end of turn
    if(in_array("CANT_GAIN_STEALTH", $obj->TurnEffects)) return false;
    // Lawsur, the Carpenter (aenquoed10): Specter allies have stealth while awake
    if(PropertyContains(EffectiveCardSubtypes($obj), "SPECTER")
        && PropertyContains(EffectiveCardType($obj), "ALLY")
        && isset($obj->Status) && $obj->Status == 2) {
        global $playerID;
        $lawsurZone = $obj->Controller == $playerID ? "myField" : "theirField";
        $lawsurField = GetZone($lawsurZone);
        foreach($lawsurField as $lfObj) {
            if(!$lfObj->removed && $lfObj->CardID === "aenquoed10" && !HasNoAbilities($lfObj)) {
                return true;
            }
        }
    }
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
    // Tweedledum, Rattled Dancer (UmZpK4rt2M): [Class Bonus] Stealth
    if($obj->CardID === "UmZpK4rt2M") {
        if(IsClassBonusActive($obj->Controller, ["ASSASSIN"])) return true;
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
    // Stardust Oracle (EPy8OUmPxa): stealth while imbued
    if($obj->CardID === "EPy8OUmPxa" && in_array("IMBUED", $obj->TurnEffects)) return true;
    // Folded Shadows (HL4Q3UBoH8): [Ciel Bonus] champion has stealth while total omen cost >= 33
    if(PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
        global $playerID;
        $fsZone = $obj->Controller == $playerID ? "myField" : "theirField";
        $fsField = GetZone($fsZone);
        foreach($fsField as $fsObj) {
            if(!$fsObj->removed && $fsObj->CardID === "HL4Q3UBoH8" && !HasNoAbilities($fsObj)
                && IsCielBonusActive($obj->Controller) && GetTotalOmenCost($obj->Controller) >= 33) {
                return true;
            }
        }
    }
    // Weiss Bishop (Dgtim99eB5): stealth while you control one or more Pawn allies
    if($obj->CardID === "Dgtim99eB5") {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fObj) {
            if(!$fObj->removed && $fObj !== $obj
                && PropertyContains(EffectiveCardType($fObj), "ALLY")
                && PropertyContains(EffectiveCardSubtypes($fObj), "PAWN")) {
                return true;
            }
        }
    }
    // Noire, Ace of Spades (wbjc9t8ycp): stealth while you control another Suited ally
    if($obj->CardID === "wbjc9t8ycp") {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fObj) {
            if(!$fObj->removed && $fObj !== $obj
                && PropertyContains(EffectiveCardType($fObj), "ALLY")
                && PropertyContains(EffectiveCardSubtypes($fObj), "SUITED")) {
                return true;
            }
        }
    }
    if(HasKeyword_Stealth($obj)) return true;
    // Treacle, Drowned Mouse (6emPe9OEUn): stealth while ephemeral
    if($obj->CardID === "6emPe9OEUn" && IsEphemeral($obj)) return true;
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
            case "pw9b6IJWEr": // Embrace Noir: allies gain stealth until end of turn
                return true;
        }
    }
    return false;
}

// Siegeable: domain subtype that allows being attacked. Damage removes durability counters.
function IsSiegeable($obj) {
    return PropertyContains(CardSubtypes($obj->CardID), "SIEGEABLE");
}

function HasIntercept($obj) {
    if($obj === null || HasNoAbilities($obj)) return false;
    if(in_array("NO_INTERCEPT", $obj->TurnEffects ?? [])) return false;
    if(HasKeyword_Intercept($obj)) return true;
    if(in_array("INTERCEPT_EOT", $obj->TurnEffects ?? [])) return true;
    if($obj->CardID === "c9p4lpnvx7") {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        if(count(ZoneSearch($zone, ["PHANTASIA"])) >= 2) return true;
    }
    if($obj->CardID === "92mnQJPfR8" && in_array("IMBUED", $obj->TurnEffects ?? [])) return true;
    if(PropertyContains(EffectiveCardType($obj), "ALLY")
       && (PropertyContains(EffectiveCardSubtypes($obj), "ANIMAL") || PropertyContains(EffectiveCardSubtypes($obj), "BEAST"))) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        foreach(GetZone($zone) as $fObj) {
            if(!$fObj->removed && $fObj->CardID === "GKEpAulogu" && !HasNoAbilities($fObj)) return true;
        }
    }
    foreach(GetLinkedCards($obj) as $linkedObj) {
        if($linkedObj->CardID === "i1j4gvwbjo") return true;
    }
    return false;
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
    // Seeker's Aetherwing (bf7yzaqes4): [CB] True Sight
    if($obj->CardID === "bf7yzaqes4" && IsClassBonusActive($obj->Controller, ["RANGER"])) return true;
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
    if(in_array("NO_SPELLSHROUD", $obj->TurnEffects ?? [])) return false;
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
    // Seeker's Aetherwing (bf7yzaqes4): [CB] Spellshroud
    if($obj->CardID === "bf7yzaqes4" && IsClassBonusActive($obj->Controller, ["RANGER"])) return true;
    // Folded Shadows (HL4Q3UBoH8): [Ciel Bonus] champion has spellshroud while total omen cost >= 33
    if(PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
        global $playerID;
        $fsZone = $obj->Controller == $playerID ? "myField" : "theirField";
        $fsField = GetZone($fsZone);
        foreach($fsField as $fsObj) {
            if(!$fsObj->removed && $fsObj->CardID === "HL4Q3UBoH8" && !HasNoAbilities($fsObj)
                && IsCielBonusActive($obj->Controller) && GetTotalOmenCost($obj->Controller) >= 33) {
                return true;
            }
        }
    }
    // The Majestic Spirit (tsvbgl6ffq): champions you control have spellshroud
    if(PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
        global $playerID;
        $msZone = $obj->Controller == $playerID ? "myField" : "theirField";
        $msField = GetZone($msZone);
        foreach($msField as $msObj) {
            if(!$msObj->removed && $msObj->CardID === "tsvbgl6ffq" && !HasNoAbilities($msObj)) {
                return true;
            }
        }
    }
    // Dusksoul Stone (u25fuv184p): phantasias you control have spellshroud
    if(PropertyContains(EffectiveCardType($obj), "PHANTASIA")) {
        global $playerID;
        $dsZone = $obj->Controller == $playerID ? "myField" : "theirField";
        $dsField = GetZone($dsZone);
        foreach($dsField as $dsObj) {
            if(!$dsObj->removed && $dsObj->CardID === "u25fuv184p" && !HasNoAbilities($dsObj)) {
                return true;
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
    if($obj->CardID === "0v8zzzb83i" && GetCounterCount($obj, "buff") >= 2) return true;
    // Avatar of Genbu (67CIhG8hmG): [Guo Jia Bonus][Deluge 12] has taunt
    if($obj->CardID === "67CIhG8hmG" && IsGuoJiaBonus($obj->Controller) && DelugeAmount($obj->Controller) >= 12) return true;
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
    // Servant's Obligation (f4wqesifxk): [Ciel Bonus] champion has taunt while not attacked this turn
    if(PropertyContains(EffectiveCardType($obj), "CHAMPION") && !in_array("WAS_ATTACKED", $obj->TurnEffects)) {
        $controller = $obj->Controller;
        global $playerID;
        $zone = $controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $soObj) {
            if(!$soObj->removed && $soObj->CardID === "f4wqesifxk" && !HasNoAbilities($soObj)) {
                if(strpos(CardName($obj->CardID), "Ciel") === 0) {
                    return true;
                }
                break;
            }
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
    // Coy Bouclier (vo1qr9bkme): [CB] Taunt while you control another ally
    if($obj->CardID === "vo1qr9bkme" && IsClassBonusActive($obj->Controller, ["GUARDIAN"])) {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $allies = ZoneSearch($zone, ["ALLY"]);
        foreach($allies as $aMZ) {
            $aObj = GetZoneObject($aMZ);
            if($aObj !== null && $aObj->CardID !== "vo1qr9bkme") return true;
        }
    }
    // Aquifer Seneschal (8mrn8at13c): [Class Bonus] Taunt
    if($obj->CardID === "8mrn8at13c" && IsClassBonusActive($obj->Controller, ["GUARDIAN"])) return true;
    // Rivulet Adjutant (y547d3iixm): [Class Bonus] Taunt
    if($obj->CardID === "y547d3iixm" && IsClassBonusActive($obj->Controller, ["GUARDIAN"])) return true;
    // Golden Pawn (Lewf9sfv9m): Taunt while you control another Chessman unit
    if($obj->CardID === "Lewf9sfv9m") {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fObj) {
            if(!$fObj->removed && $fObj !== $obj
                && (PropertyContains(EffectiveCardType($fObj), "ALLY") || PropertyContains(EffectiveCardType($fObj), "CHAMPION"))
                && PropertyContains(EffectiveCardSubtypes($fObj), "CHESSMAN")) {
                return true;
            }
        }
    }
    // Burnished Obelith (mz1dJZExOk): [Sheen 8+] Taunt
    if($obj->CardID === "mz1dJZExOk") {
        if(GetSheenCount($obj->Controller) >= 8) return true;
    }
    // Sworn Windhand (9ewgUjy34b): [Class Bonus] Taunt
    if($obj->CardID === "9ewgUjy34b" && IsClassBonusActive($obj->Controller, ["GUARDIAN"])) return true;
    // Baby Silver Slime (62lVDTOToR): has taunt while you control another Slime ally
    if($obj->CardID === "62lVDTOToR") {
        global $playerID;
        $zone = $obj->Controller == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fObj) {
            if(!$fObj->removed && $fObj !== $obj && PropertyContains(CardSubtypes($fObj->CardID), "SLIME")
                && PropertyContains(EffectiveCardType($fObj), "ALLY")) {
                return true;
            }
        }
    }
    return false;
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
        case "qp2r93Bgpj": // Diana, Haunt Reminiscence: [CB] Ranged 3+3*curses in lineage
            if(IsClassBonusActive($obj->Controller, ["RANGER"])) {
                $ranged += 3 + 3 * CountCursesInLineage($obj->Controller);
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
    // Ally Link: Beastsoul Visage (8asbierp5k) grants pride 3 to linked ally
    if($prideValue === null) {
        $linkedCards = GetLinkedCards($obj);
        foreach($linkedCards as $linkedObj) {
            if($linkedObj->CardID === "8asbierp5k") {
                $prideValue = 3;
                break;
            }
        }
    }
    if(PropertyContains(EffectiveCardType($obj), "ALLY") && PropertyContains(CardType($obj->CardID), "UNIQUE")) {
        foreach(GetField(1) as $fObj) {
            if($fObj->removed || $fObj->CardID !== "x8o84m37ti" || HasNoAbilities($fObj)) continue;
            if($obj->Controller !== $fObj->Controller) $prideValue = max($prideValue ?? 0, 2);
        }
        foreach(GetField(2) as $fObj) {
            if($fObj->removed || $fObj->CardID !== "x8o84m37ti" || HasNoAbilities($fObj)) continue;
            if($obj->Controller !== $fObj->Controller) $prideValue = max($prideValue ?? 0, 2);
        }
    }
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
    $turnPlayer = &GetTurnPlayer();

    // Generated value modifiers let us migrate scalar cost logic out of this switchboard incrementally.
    $cost += EvaluateMemoryCostModifier($obj->CardID, $turnPlayer, $obj, $cost, null);
    global $playerID;
    $zone = $turnPlayer == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    foreach($field as $fieldObj) {
        if($fieldObj->removed || HasNoAbilities($fieldObj)) continue;
        $cost += EvaluateMemoryCostModifier($fieldObj->CardID, $turnPlayer, $obj, $cost, $fieldObj);
    }
    $opponent = ($turnPlayer == 1) ? 2 : 1;
    $oppEffectsZone = ($opponent == $playerID) ? "myGlobalEffects" : "theirGlobalEffects";
    foreach(GetZone($oppEffectsZone) as $effectObj) {
        if(!empty($effectObj->removed)) continue;
        $cost += EvaluateMemoryCostModifier($effectObj->CardID, $turnPlayer, $obj, $cost, $effectObj);
    }
    return max(0, $cost);
}

function ApplyGeneratedReserveLikeCostModifiers($player, $subjectObj, $currentCost, $mode = "reserve") {
    $evaluators = ["EvaluateReserveCostModifier"];
    if($mode === "play") $evaluators[] = "EvaluatePlayCostModifier";
    if($mode === "activate") $evaluators[] = "EvaluateActivationCostModifier";

    foreach($evaluators as $evaluator) {
        if(!function_exists($evaluator)) continue;
        $currentCost += $evaluator($subjectObj->CardID, $player, $subjectObj, $currentCost, null);

        foreach([1, 2] as $fieldPlayer) {
            foreach(GetField($fieldPlayer) as $fieldObj) {
                if($fieldObj->removed || HasNoAbilities($fieldObj)) continue;
                $currentCost += $evaluator($fieldObj->CardID, $player, $subjectObj, $currentCost, $fieldObj);
            }
        }

        foreach([1, 2] as $effectPlayer) {
            $globalEffects = GetGlobalEffects($effectPlayer);
            foreach($globalEffects as $effectObj) {
                if(!empty($effectObj->removed)) continue;
                $effectSource = clone $effectObj;
                $effectSource->Controller = $effectPlayer;
                $effectSource->_sourceZone = "GlobalEffects";
                $currentCost += $evaluator($effectObj->CardID, $player, $subjectObj, $currentCost, $effectSource);
            }
        }
    }

    return max(0, $currentCost);
}

function NefariousTimepieceEnter($player, $timepieceMZ) {
    $choices = [];
    $seen = [];
    foreach(["myMaterial", "myBanish", "myGraveyard", "myField"] as $zoneName) {
        foreach(ZoneSearch($zoneName, ["REGALIA"]) as $mz) {
            $obj = GetZoneObject($mz);
            if($obj === null || isset($seen[$obj->CardID])) continue;
            $seen[$obj->CardID] = true;
            $choices[] = $mz;
        }
    }
    if(empty($choices)) return;
    DecisionQueueController::StoreVariable("NefariousTimepieceMZ", $timepieceMZ);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $choices), 1, tooltip:"Choose_a_regalia_card_name");
    DecisionQueueController::AddDecision($player, "CUSTOM", "NefariousTimepieceChoose", 1);
}

function EchoicGuardResolve($player) {
    $allies = FilterSpellshroudTargets(ZoneSearch("myField", ["ALLY"]));
    if(empty($allies)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $allies), 1, tooltip:"Choose_target_ally");
    DecisionQueueController::AddDecision($player, "CUSTOM", "EchoicGuardTarget", 1);
}

function ShiftingMirageResolve($player) {
    $champMZ = FindChampionMZ($player);
    if($champMZ !== null) {
        $opponent = ($player == 1) ? 2 : 1;
        if(count(GetHand($opponent)) >= 2) {
            DecisionQueueController::StoreVariable("ShiftingMirageChampion", $champMZ);
            DecisionQueueController::AddDecision($opponent, "YESNO", "-", 1, tooltip:"Pay_2_to_prevent_stealth?");
            DecisionQueueController::AddDecision($opponent, "CUSTOM", "ShiftingMiragePay", 1);
        } else {
            AddTurnEffect($champMZ, "STEALTH");
        }
    }
    if(IsTristanBonus($player)) MZAddZone($player, "myField", "gveirpdm44");
}

function BlastshotPumpOnHit($player) {
    $weaponMZ = GetCombatWeapon();
    $weaponObj = ($weaponMZ !== null && $weaponMZ !== "-") ? GetZoneObject($weaponMZ) : null;
    if($weaponObj === null || $weaponObj->CardID !== "gmnmp5af09" || HasNoAbilities($weaponObj)) return;
    if(!IsClassBonusActive($player, ["RANGER"])) return;
    $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
    $targetObj = $combatTarget ? GetZoneObject($combatTarget) : null;
    if($targetObj === null) return;
    $targetType = EffectiveCardType($targetObj);
    if(!PropertyContains($targetType, "ALLY") && !PropertyContains($targetType, "CHAMPION")) return;
    $targets = [];
    foreach(FilterSpellshroudTargets(ZoneSearch("theirField", ["ALLY", "CHAMPION"])) as $mz) {
        if($mz !== $combatTarget) $targets[] = $mz;
    }
    $damage = intval(DecisionQueueController::GetVariable("CombatDamageAmount") ?? "0");
    if($damage <= 0 || empty($targets)) return;
    DecisionQueueController::StoreVariable("BlastshotPumpDamage", strval($damage));
    DecisionQueueController::StoreVariable("BlastshotPumpSource", $weaponMZ);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1, tooltip:"Choose_additional_unit_for_Blastshot_Pump");
    DecisionQueueController::AddDecision($player, "CUSTOM", "BlastshotPumpChoose", 1);
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

function GetLinkedAllyMZ($player, $phantasiaObj) {
    if($phantasiaObj === null || $phantasiaObj->removed) return null;
    if(!is_array($phantasiaObj->Counters) || !isset($phantasiaObj->Counters['linkedToAlly'])) return null;
    $linkedCardID = $phantasiaObj->Counters['linkedToAlly'];
    global $playerID;
    $zoneRef = $player == $playerID ? "myField" : "theirField";
    $field = GetZone($zoneRef);
    foreach($field as $idx => $obj) {
        if($obj->removed || !PropertyContains(EffectiveCardType($obj), "ALLY")) continue;
        if($obj->CardID !== $linkedCardID) continue;
        if(!is_array($obj->Subcards) || !in_array($phantasiaObj->CardID, $obj->Subcards)) continue;
        return $zoneRef . "-" . $idx;
    }
    return null;
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
// Cardistry & Suited Helpers
// =============================================================================

/**
 * Count distinct reserve costs among Suited objects a player controls on the field.
 * Used to compute Cardistry cost reduction: ability costs (1) less per distinct cost.
 * @param int $player The player whose field to check.
 * @return int Number of distinct reserve costs among their Suited objects.
 */
function GetCardistryDiscount($player) {
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    $distinctCosts = [];
    foreach($field as $obj) {
        if($obj->removed) continue;
        if(!PropertyContains(EffectiveCardSubtypes($obj), "SUITED")) continue;
        $cost = CardCost_reserve($obj->CardID);
        if($cost !== null) $distinctCosts[$cost] = true;
    }
    return count($distinctCosts);
}

/**
 * Check if an ally has immortality (death prevention).
 * Currently granted by Verita, Queen of Hearts (4qc47amgpp) to other Suited allies.
 * @param object $obj A field zone object.
 * @return bool
 */
function HasImmortality($obj) {
    if(HasNoAbilities($obj)) return false;
    if(HasGrantedKeyword($obj, 'Immortality')) return true;
    if(in_array("IMMORTALITY_NEXT_TURN", $obj->TurnEffects ?? [])) return true;
    if(!PropertyContains(EffectiveCardSubtypes($obj), "SUITED")) return false;
    if(!PropertyContains(EffectiveCardType($obj), "ALLY")) return false;
    global $playerID;
    $zone = $obj->Controller == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    foreach($field as $fObj) {
        if(!$fObj->removed && $fObj->CardID === "4qc47amgpp" && !HasNoAbilities($fObj)
            && $fObj !== $obj && $fObj->Controller == $obj->Controller) {
            return true;
        }
    }
    return false;
}

// =============================================================================
// Counter System — generic add/remove/query for card-level counters
// =============================================================================

// Card-specific helper implementations.
function CountUniqueAllies($player) {
    $count = 0;
    foreach(GetField($player) as $obj) {
        if($obj->removed || HasNoAbilities($obj)) continue;
        if(!PropertyContains(EffectiveCardType($obj), "ALLY")) continue;
        if(PropertyContains(EffectiveCardType($obj), "UNIQUE")) ++$count;
    }
    return $count;
}

function CountChampionCombatDamageDealtThisTurn($player) {
    $champion = GetPlayerChampion($player);
    if($champion === null) return 0;
    return intval($champion->Counters["_champCombatDamageDealtThisTurn"] ?? 0);
}

function MortalAmbitionResolve($player) {
    $field = &GetField($player);
    for($i = 0; $i < count($field); ++$i) {
        if($field[$i]->removed || HasNoAbilities($field[$i])) continue;
        if(!PropertyContains(EffectiveCardType($field[$i]), "ALLY")) continue;
        $subtypes = EffectiveCardSubtypes($field[$i]);
        if(!PropertyContains($subtypes, "HUMAN") && !PropertyContains($subtypes, "HORSE")) continue;
        AddTurnEffect("myField-" . $i, "ymuarq5tv0-LIFE");
        AddTurnEffect("myField-" . $i, "STEADFAST");
        AddTurnEffect("myField-" . $i, "AMBUSH");
    }
}

function ArthurYoungHeirEnter($player, $mzID) {
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Rest_Arthur_for_immortality_until_your_next_turn?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ArthurYoungHeirEnter|" . $mzID, 1);
}

$customDQHandlers["ArthurYoungHeirEnter"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $mzID = $parts[0] ?? "";
    $obj = GetZoneObject($mzID);
    if($obj === null || $obj->removed || $obj->CardID !== "GjM8b5fxqj") return;
    $obj->Status = 1;
    AddTurnEffect($mzID, "IMMORTALITY_NEXT_TURN");
};

function StrengthenTheBondsStart($player) {
    $targets = [];
    foreach(["myField", "theirField"] as $zone) {
        foreach(GetZone($zone) as $i => $obj) {
            if($obj->removed) continue;
            $subtypes = EffectiveCardSubtypes($obj);
            if(PropertyContains($subtypes, "FATESTONE") || PropertyContains($subtypes, "FATEBOUND")) {
                $targets[] = $zone . "-" . $i;
            }
        }
    }
    if(empty($targets)) return;
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $targets), 1, tooltip:"Put_a_buff_counter_on_up_to_two_Fatestone_or_Fatebound_objects");
    DecisionQueueController::AddDecision($player, "CUSTOM", "StrengthenTheBondsFirst", 1);
}

$customDQHandlers["StrengthenTheBondsFirst"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddCounters($player, $lastDecision, "buff", 1);
    $targets = [];
    foreach(["myField", "theirField"] as $zone) {
        foreach(GetZone($zone) as $i => $obj) {
            if($obj->removed) continue;
            $mzID = $zone . "-" . $i;
            if($mzID === $lastDecision) continue;
            $subtypes = EffectiveCardSubtypes($obj);
            if(PropertyContains($subtypes, "FATESTONE") || PropertyContains($subtypes, "FATEBOUND")) {
                $targets[] = $mzID;
            }
        }
    }
    if(empty($targets)) return;
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $targets), 1, tooltip:"Put_a_second_buff_counter?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "StrengthenTheBondsSecond", 1);
};

$customDQHandlers["StrengthenTheBondsSecond"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddCounters($player, $lastDecision, "buff", 1);
};

function DaQiaoCinderbinderAbility($player) {
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Put_a_frenzy_counter_on_target_ally?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DaQiaoMode", 1);
}

$customDQHandlers["DaQiaoMode"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        $targets = array_merge(ZoneSearch("myField", ["ALLY"]), ZoneSearch("theirField", ["ALLY"]));
        if(!empty($targets)) {
            DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1, tooltip:"Choose_ally_for_frenzy_counter");
            DecisionQueueController::AddDecision($player, "CUSTOM", "DaQiaoFrenzyCounter", 1);
        }
        return;
    }
    $count = 0;
    foreach(array_merge(GetZone("myField"), GetZone("theirField")) as $obj) {
        if(!$obj->removed && PropertyContains(EffectiveCardType($obj), "ALLY") && GetCounterCount($obj, "frenzy") > 0) ++$count;
    }
    if($count > 0) Empower($player, $count, "ugl6g5znia");
};

$customDQHandlers["DaQiaoFrenzyCounter"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddCounters($player, $lastDecision, "frenzy", 1);
};

function KraalStonescaleOnAttack($player) {
    if(!IsClassBonusActive($player, ["TAMER"])) return;
    PutTopDeckCardIntoMaterialPreserved($player);
    PutTopDeckCardIntoMaterialPreserved($player);
}

function LuBuIndomitableTitanOnAttack($player, $mzID) {
    if(OnAttackCallCount($player) !== 1) return;
    if(CountAvailableReservePayments($player) < 2) return;
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Pay_2_to_wake_up_Lu_Bu?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LuBuIndomitableTitanOnAttack|" . $mzID, 1);
}

$customDQHandlers["LuBuIndomitableTitanOnAttack"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $mzID = $parts[0] ?? "";
    $obj = GetZoneObject($mzID);
    if($obj === null || $obj->removed || $obj->CardID !== "xyan7zbtxi") return;
    DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 1);
    DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 1);
    WakeupCard($player, $mzID);
};

function FirebloodedOathResolve($player) {
    LevelUpChampion($player);
    AddGlobalEffects($player, "FIREBLOODED_OATH_DELEVEL");
}

function FirebloodedOathEndPhase($player) {
    if(GlobalEffectCount($player, "FIREBLOODED_OATH_DELEVEL") <= 0) return;
    while(RemoveGlobalEffect($player, "FIREBLOODED_OATH_DELEVEL")) {}
    Delevel($player);
}

function LuBuDiaoChanChampionReplacement($player, $championMZ) {
    $champObj = GetZoneObject($championMZ);
    if($champObj === null || $champObj->removed) return false;
    if(!PropertyContains(EffectiveCardType($champObj), "CHAMPION")) return false;
    $controller = $champObj->Controller ?? $player;
    if(!IsDiaoChanBonus($controller)) return false;
    if(intval($champObj->Damage ?? 0) !== 32 || intval($champObj->Damage ?? 0) < ObjectCurrentHP($champObj)) return false;

    $field = &GetField($controller);
    $luBuMZ = null;
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "xyan7zbtxi" && !HasNoAbilities($field[$i])) {
            $luBuMZ = "myField-" . $i;
            break;
        }
    }
    if($luBuMZ === null) return false;

    MZMove($controller, $championMZ, "myBanish");
    $luBuObj = GetZoneObject($luBuMZ);
    if($luBuObj !== null) {
        ApplyPersistentOverride($luBuMZ, ["type" => "UNIQUE,CHAMPION"]);
        $luBuObj->Damage = 0;
    }
    for($i = count($field) - 1; $i >= 0; --$i) {
        if($field[$i]->removed) continue;
        $mzID = "myField-" . $i;
        if($mzID === $luBuMZ) continue;
        DoAllyDestroyed($controller, $mzID);
    }
    DecisionQueueController::CleanupRemovedCards();
    return true;
}

function HarbingerOfLightningOnDeath($player) {
    if(!IsClassBonusActive($player, ["MAGE", "TAMER"])) return;
    $totalCost = 0;
    $gy = GetGraveyard($player);
    for($i = count($gy) - 1; $i >= 0; --$i) {
        if($gy[$i]->removed || $gy[$i]->CardID !== "1i5z6r7s9k") continue;
        $totalCost += max(0, intval(CardCost_reserve($gy[$i]->CardID)));
        MZMove($player, "myGraveyard-" . $i, "myBanish");
        break;
    }
    $memory = GetMemory($player);
    $memoryTargets = [];
    for($i = 0; $i < count($memory); ++$i) {
        if(!$memory[$i]->removed) $memoryTargets[] = "myMemory-" . $i;
    }
    if(!empty($memoryTargets)) {
        $chosen = $memoryTargets[array_rand($memoryTargets)];
        $memObj = GetZoneObject($chosen);
        if($memObj !== null) $totalCost += max(0, intval(CardCost_reserve($memObj->CardID)));
        MZMove($player, $chosen, "myBanish");
    }
    if($totalCost > 0) {
        $opponent = ($player == 1) ? 2 : 1;
        DealChampionDamage($opponent, $totalCost);
    }
}

function ForagingFoxOnEnter($player) {
    $deck = GetDeck($player);
    $lookCount = min(5, count($deck));
    if($lookCount <= 0) return;
    for($i = 0; $i < $lookCount; ++$i) {
        MZMove($player, "myDeck-0", "myTempZone");
    }
    $candidates = ZoneSearch("myTempZone", cardSubtypes: ["FATESTONE"]);
    if(empty($candidates)) {
        ForagingFoxChooseBottom($player);
        return;
    }
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $candidates), 1,
        tooltip: "Reveal_a_Fatestone_card_to_put_into_memory?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ForagingFoxReveal", 1);
}

$customDQHandlers["ForagingFoxReveal"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        $chosenObj = GetZoneObject($lastDecision);
        if($chosenObj !== null && PropertyContains(CardSubtypes($chosenObj->CardID), "FATESTONE")) {
            Reveal($player, $lastDecision);
            MZMove($player, $lastDecision, "myMemory");
        }
    }
    ForagingFoxChooseBottom($player);
};

function ForagingFoxChooseBottom($player) {
    $remaining = ZoneSearch("myTempZone");
    if(empty($remaining)) return;
    if(count($remaining) === 1) {
        MZMove($player, $remaining[0], "myDeck");
        return;
    }
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $remaining), 1,
        tooltip: "Choose_card_to_put_on_bottom_next");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ForagingFoxBottom", 1);
}

$customDQHandlers["ForagingFoxBottom"] = function($player, $parts, $lastDecision) {
    $remaining = ZoneSearch("myTempZone");
    if(in_array($lastDecision, $remaining)) {
        MZMove($player, $lastDecision, "myDeck");
    }
    ForagingFoxChooseBottom($player);
};

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

function GetQuestCounterCountForObject($obj) {
    return GetCounterCount($obj, "quest");
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

// --- Omen Counter Helpers ---

function GetOmenCounterCount($obj) {
    return GetCounterCount($obj, "omen");
}

function IsCielBonusActive($player) {
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    foreach($field as $obj) {
        if(!$obj->removed && PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
            return strpos(CardName($obj->CardID), "Ciel") === 0;
        }
    }
    return false;
}

function IsArisannaBonusActive($player) {
    return ChampionHasInLineage($player, "b31x97n2jn")  // Arisanna, Herbalist Prodigy (L1)
        || ChampionHasInLineage($player, "ltv5klryvf")  // Arisanna, Master Alchemist (L2)
        || ChampionHasInLineage($player, "q3huqj5bba")  // Arisanna, Astral Zenith (L3)
        || ChampionHasInLineage($player, "7e22tk3ir1"); // Arisanna, Lucent Arbiter (L3)
}

function GetOmens($player) {
    $banish = GetBanish($player);
    $omens = [];
    foreach($banish as $obj) {
        if(!$obj->removed && GetCounterCount($obj, "omen") > 0) {
            $omens[] = $obj;
        }
    }
    return $omens;
}

function GetOmenMZIDs($player) {
    global $playerID;
    $banish = GetBanish($player);
    $prefix = ($player == $playerID) ? "myBanish" : "theirBanish";
    $mzIDs = [];
    for($i = 0; $i < count($banish); $i++) {
        if(!$banish[$i]->removed && GetCounterCount($banish[$i], "omen") > 0) {
            $mzIDs[] = $prefix . "-" . $i;
        }
    }
    return $mzIDs;
}

function GetOmenCount($player) {
    return count(GetOmens($player));
}

function GetOmenDistinctCostCount($player) {
    $omens = GetOmens($player);
    $costs = [];
    foreach($omens as $obj) {
        $cost = CardCost_reserve($obj->CardID);
        if($cost === null) $cost = 0;
        $costs[$cost] = true;
    }
    return count($costs);
}

function HasOmensWithSameCost($player) {
    $omens = GetOmens($player);
    $costs = [];
    foreach($omens as $obj) {
        $cost = CardCost_reserve($obj->CardID);
        if($cost === null) $cost = 0;
        if(isset($costs[$cost])) return true;
        $costs[$cost] = true;
    }
    return false;
}

function GetLowestOmenCost($player) {
    $omens = GetOmens($player);
    if(empty($omens)) return 0;
    $min = PHP_INT_MAX;
    foreach($omens as $obj) {
        $cost = CardCost_reserve($obj->CardID);
        if($cost === null) $cost = 0;
        if($cost < $min) $min = $cost;
    }
    return $min === PHP_INT_MAX ? 0 : $min;
}

function GetTotalOmenCost($player) {
    $omens = GetOmens($player);
    $total = 0;
    foreach($omens as $obj) {
        $cost = CardCost_reserve($obj->CardID);
        if($cost !== null) $total += $cost;
    }
    return $total;
}

function GetOmenCountByType($player, $type) {
    $omens = GetOmens($player);
    $count = 0;
    foreach($omens as $obj) {
        if(PropertyContains(CardType($obj->CardID), $type)) {
            $count++;
        }
    }
    return $count;
}

function GetOmenCountByElement($player, $element) {
    $omens = GetOmens($player);
    $count = 0;
    foreach($omens as $obj) {
        if(CardElement($obj->CardID) === $element) {
            $count++;
        }
    }
    return $count;
}

function GetInfluence($player) {
    return count(GetHand($player)) + count(GetMemory($player));
}

function DiscardRandomFromHandAndMemory($player) {
    $targets = [];
    $hand = GetHand($player);
    for($i = 0; $i < count($hand); ++$i) {
        if(!$hand[$i]->removed) {
            $targets[] = "myHand-" . $i;
        }
    }
    $memory = GetMemory($player);
    for($i = 0; $i < count($memory); ++$i) {
        if(!$memory[$i]->removed) {
            $targets[] = "myMemory-" . $i;
        }
    }
    if(empty($targets)) return;
    $chosen = $targets[array_rand($targets)];
    DoDiscardCard($player, $chosen);
}

function CountNonHumanAlliesInGraveyard($player) {
    $graveyard = GetGraveyard($player);
    $count = 0;
    foreach($graveyard as $obj) {
        if($obj->removed) continue;
        if(!PropertyContains(CardType($obj->CardID), "ALLY")) continue;
        if(PropertyContains(CardSubtypes($obj->CardID), "HUMAN")) continue;
        ++$count;
    }
    return $count;
}

function HasOmenWithReserveCost($player, $reserveCost) {
    $omens = GetOmens($player);
    foreach($omens as $obj) {
        if(intval(CardCost_reserve($obj->CardID)) === intval($reserveCost)) {
            return true;
        }
    }
    return false;
}

function HighestFireAllyPower($player) {
    $allies = ZoneSearch("myField", ["ALLY"], cardElements: ["FIRE"]);
    $highest = 0;
    foreach($allies as $mzID) {
        $obj = GetZoneObject($mzID);
        if($obj === null || $obj->removed) continue;
        $highest = max($highest, ObjectCurrentPower($obj));
    }
    return $highest;
}

function TrackChampionDamageThisTurn(&$champObj, $amount) {
    if($champObj === null || $amount <= 0) return;
    if(!isset($champObj->Counters) || !is_array($champObj->Counters)) $champObj->Counters = [];
    $current = intval($champObj->Counters["_champDamageThisTurn"] ?? 0);
    $champObj->Counters["_champDamageThisTurn"] = $current + intval($amount);
}

function GetChampionDamageTakenThisTurn($player) {
    $champion = GetPlayerChampion($player);
    if($champion === null) return 0;
    return intval($champion->Counters["_champDamageThisTurn"] ?? 0);
}

function GetOppressivePresenceAttackTax($player) {
    $highestTax = 0;
    for($p = 1; $p <= 2; ++$p) {
        $globalEffects = GetGlobalEffects($p);
        foreach($globalEffects as $obj) {
            if($obj->removed) continue;
            if(strpos($obj->CardID, "j9hjjvkyyr_") !== 0) continue;
            $highestTax = max($highestTax, intval(substr($obj->CardID, strlen("j9hjjvkyyr_"))));
        }
    }
    return $highestTax;
}

function GetYudiAttackTax() {
    $tax = 0;
    for($p = 1; $p <= 2; ++$p) {
        foreach(GetField($p) as $obj) {
            if($obj->removed || $obj->CardID !== "l94wp7qjwb" || HasNoAbilities($obj)) continue;
            if(!IsClassBonusActive($obj->Controller, ["CLERIC", "MAGE"])) continue;
            $tax += GetCounterCount($obj, "root");
        }
    }
    return $tax;
}

function PutOmenCounter($player, $mzCard) {
    AddCounters($player, $mzCard, "omen", 1);
    OnOmenCounterPlaced($player, $mzCard);
}

function BanishWithOmenCounter($player, $mzCard) {
    global $playerID;
    $cardID = GetZoneObject($mzCard)->CardID;
    MZMove($player, $mzCard, "myBanish");
    $banish = GetBanish($player);
    $prefix = ($player == $playerID) ? "myBanish" : "theirBanish";
    for($i = count($banish) - 1; $i >= 0; --$i) {
        if(!$banish[$i]->removed && $banish[$i]->CardID === $cardID) {
            PutOmenCounter($player, $prefix . "-" . $i);
            break;
        }
    }
}

function SlimeKingLeaveStart($player) {
    $choices = [];
    $banish = GetZone("myBanish");
    for($bi = 0; $bi < count($banish); ++$bi) {
        if($banish[$bi]->removed) continue;
        if(isset($banish[$bi]->Counters['_slimeKing'])) {
            $choices[] = "myBanish-" . $bi;
        }
    }
    if(empty($choices)) return;
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $choices), 1, tooltip:"Put_a_banished_Slime_ally_onto_the_field?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SlimeKingLeaveChoose", 1);
}

$customDQHandlers["SlimeKingLeaveChoose"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myField");
    SlimeKingLeaveStart($player);
};

function AshenRiffleStart($player) {
    $deck = &GetDeck($player);
    $n = min(4, count($deck));
    if($n == 0) return;
    for($i = 0; $i < $n; ++$i) {
        MZMove($player, "myDeck-0", "myTempZone");
    }
    AshenRiffleChoose($player, 2);
}

function AshenRiffleChoose($player, $remainingChoices) {
    if($remainingChoices <= 0) {
        AshenRiffleCleanup($player);
        return;
    }
    $eligible = [];
    $tempCards = ZoneSearch("myTempZone");
    foreach($tempCards as $tMZ) {
        $tObj = GetZoneObject($tMZ);
        if($tObj === null) continue;
        if(!PropertyContains(CardSubtypes($tObj->CardID), "SUITED")) continue;
        if(PropertyContains(CardType($tObj->CardID), "ACTION")) continue;
        $eligible[] = $tMZ;
    }
    if(empty($eligible)) {
        AshenRiffleCleanup($player);
        return;
    }
    $pickNumber = 3 - $remainingChoices;
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $eligible), 1, tooltip:"Banish_a_Suited_non-action_card?_(" . $pickNumber . "_of_2)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "AshenRiffleChoose|" . $remainingChoices, 1);
}

$customDQHandlers["AshenRiffleChoose"] = function($player, $parts, $lastDecision) {
    $remainingChoices = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        AshenRiffleCleanup($player);
        return;
    }
    $banishedObj = MZMove($player, $lastDecision, "myBanish");
    if($banishedObj !== null) {
        if(!is_array($banishedObj->Counters)) $banishedObj->Counters = [];
        $banishedObj->Counters['_ashenRiffle'] = 1;
    }
    AshenRiffleChoose($player, $remainingChoices - 1);
};

function AshenRiffleCleanup($player) {
    $remaining = ZoneSearch("myTempZone");
    foreach($remaining as $rmz) {
        MZMove($player, $rmz, "myDeck");
    }
}

function OnOmenCounterPlaced($player, $mzCard) {
    global $playerID;
    // Ciel, Mirage's Grave (zhh43i1eaa): deal 2 unpreventable to up to one target unit
    $champField = ($player == $playerID) ? "myField" : "theirField";
    $field = GetZone($champField);
    foreach($field as $obj) {
        if(!$obj->removed && PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
            $subcards = is_array($obj->Subcards) ? $obj->Subcards : [];
            if($obj->CardID === "zhh43i1eaa" || in_array("zhh43i1eaa", $subcards)) {
                // Queue: choose up to one target unit, deal 2 unpreventable
                $allUnits = array_merge(
                    ZoneSearch("myField", ["ALLY", "CHAMPION"]),
                    ZoneSearch("theirField", ["ALLY", "CHAMPION"])
                );
                $allUnits = FilterSpellshroudTargets($allUnits);
                if(!empty($allUnits)) {
                    $targetStr = implode("&", $allUnits);
                    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1);
                    DecisionQueueController::AddDecision($player, "CUSTOM", "CielMiragesGraveDamage", 1);
                }
            }
            break;
        }
    }
    // Confidant's Oath (nlufjh84vm): put a refinement counter on it
    $fieldArr = GetZone($champField);
    foreach($fieldArr as $fi => $fObj) {
        if(!$fObj->removed && $fObj->CardID === "nlufjh84vm" && !HasNoAbilities($fObj)) {
            if(IsCielBonusActive($player)) {
                AddCounters($player, $champField . "-" . $fi, "refinement", 1);
            }
        }
    }
}

function HoarfrostHoldEnter($player, $mzID) {
    DecisionQueueController::StoreVariable("hoarfrostHoldMZ", $mzID);
    DecisionQueueController::StoreVariable("hoarfrostHoldCount", "0");

    $choices = array_merge(
        ZoneSearch("myHand", cardSubtypes: ["SUITED", "SPELL"]),
        ZoneSearch("myMemory", cardSubtypes: ["SUITED", "SPELL"])
    );
    if(empty($choices)) return;

    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $choices), 1, tooltip:"Banish_a_Suited_Spell_from_hand_or_memory?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "HoarfrostHoldChoose", 1);
}

$customDQHandlers["HoarfrostHoldChoose"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        $mzID = DecisionQueueController::GetVariable("hoarfrostHoldMZ");
        $count = intval(DecisionQueueController::GetVariable("hoarfrostHoldCount"));
        if($count > 0) {
            AddCounters($player, $mzID, "frost", $count);
        }
        return;
    }

    MZMove($player, $lastDecision, "myBanish");
    $count = intval(DecisionQueueController::GetVariable("hoarfrostHoldCount")) + 1;
    DecisionQueueController::StoreVariable("hoarfrostHoldCount", strval($count));

    $choices = array_merge(
        ZoneSearch("myHand", cardSubtypes: ["SUITED", "SPELL"]),
        ZoneSearch("myMemory", cardSubtypes: ["SUITED", "SPELL"])
    );
    if(empty($choices)) {
        $mzID = DecisionQueueController::GetVariable("hoarfrostHoldMZ");
        AddCounters($player, $mzID, "frost", $count);
        return;
    }

    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $choices), 1, tooltip:"Banish_another_Suited_Spell?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "HoarfrostHoldChoose", 1);
};

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
    // Mana Limiter (IC3OU6vCnF): blocks enlighten counter removal for costs
    $manaLimiterBlocks = false;
    if(PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
        global $playerID;
        $limiterZone = ($obj->Controller == $playerID) ? "myField" : "theirField";
        $limiterField = GetZone($limiterZone);
        foreach($limiterField as $lObj) {
            if(!$lObj->removed && $lObj->CardID === "IC3OU6vCnF") {
                $manaLimiterBlocks = true;
                break;
            }
        }
    }
    if(!$manaLimiterBlocks && PropertyContains(EffectiveCardType($obj), "CHAMPION") && GetCounterCount($obj, "enlighten") >= 3) {
        $abilities[] = ["name" => "Enlighten", "index" => $nextIndex];
        $nextIndex++;
    }
    // Tonoris, Creation's Will (n2jnltv5kl): token weapons gain a sacrifice buff ability.
    if(IsToken($obj->CardID) && PropertyContains(EffectiveCardType($obj), "WEAPON") && TonorisCreationsWillActive($obj->Controller)) {
        $weaponTargets = array_merge(ZoneSearch("myField", ["WEAPON"]), ZoneSearch("theirField", ["WEAPON"]));
        $weaponTargets = array_values(array_filter($weaponTargets, fn($mz) => $mz !== $obj->GetMzID()));
        if(!empty($weaponTargets)) {
            $abilities[] = ["name" => "Sacrifice: Buff target weapon", "index" => $nextIndex];
            $nextIndex++;
        }
    }
    // Lineage Release: show a button for each subcard that has a registered LR ability
    if(PropertyContains(EffectiveCardType($obj), "CHAMPION") && $obj->Status == 2) {
        $subcards = is_array($obj->Subcards) ? $obj->Subcards : [];
        foreach($subcards as $subcardID) {
            if(isset($lineageReleaseAbilities[$subcardID])) {
                $lrEntry = $lineageReleaseAbilities[$subcardID];
                if(isset($lrEntry['condition']) && !$lrEntry['condition']($obj->Controller)) continue;
                $abilities[] = ["name" => $lrEntry['name'], "index" => $nextIndex];
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

$customDQHandlers["GearstrideAcademyUpkeep"] = function($player, $parts, $lastDecision) {
    $mzRef = $parts[0] ?? "";
    $obj = GetZoneObject($mzRef);
    if($obj === null || $obj->removed) return;
    if($lastDecision === "YES" && count(GetHand($player)) > 0) {
        MZMove($player, "myHand-0", "myMemory");
        return;
    }
    DoSacrificeFighter($player, $mzRef);
    DecisionQueueController::CleanupRemovedCards();
};

$customDQHandlers["DongZhouRestPrompt"] = function($player, $parts, $lastDecision) {
    $mzID = $parts[0] ?? "";
    $obj = GetZoneObject($mzID);
    if($lastDecision !== "YES" || $obj === null || $obj->removed) return;
    $obj->Status = 1;
    DecisionQueueController::AddDecision($player, "NUMBERCHOOSE", "1|3", 1, tooltip:"Choose_mode_to_skip_(1=Damage,_2=Empower,_3=Vigor)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DongZhouResolve|" . $mzID, 1);
};

$customDQHandlers["DongZhouResolve"] = function($player, $parts, $lastDecision) {
    $mzID = $parts[0] ?? "";
    $skipMode = intval($lastDecision);
    if($skipMode !== 1) {
        $allies = array_merge(ZoneSearch("myField", ["ALLY"]), ZoneSearch("theirField", ["ALLY"]));
        foreach($allies as $allyMZ) {
            if($allyMZ !== $mzID) {
                DealDamage($player, $mzID, $allyMZ, 3);
            }
        }
    }
    if($skipMode !== 2) {
        Empower($player, 3, "lrbcgpny3d");
    }
    if($skipMode !== 3) {
        AddTurnEffect($mzID, "VIGOR");
    }
};

function SlimeEruptionStart($player, $sourceMZ) {
    DecisionQueueController::StoreVariable("slimeEruptionSource", $sourceMZ);
    DecisionQueueController::StoreVariable("slimeEruptionCount", "0");
    DecisionQueueController::StoreVariable("slimeEruptionNonFire", "0");
    SlimeEruptionBanishLoop($player);
}

function SlimeEruptionBanishLoop($player) {
    $count = intval(DecisionQueueController::GetVariable("slimeEruptionCount"));
    $nonFire = intval(DecisionQueueController::GetVariable("slimeEruptionNonFire"));
    $choices = [];
    $graveyard = GetZone("myGraveyard");
    for($gi = 0; $gi < count($graveyard); ++$gi) {
        $gObj = $graveyard[$gi];
        if($gObj->removed) continue;
        if(!PropertyContains(CardType($gObj->CardID), "ALLY")) continue;
        if(!PropertyContains(CardSubtypes($gObj->CardID), "SLIME")) continue;
        if(CardElement($gObj->CardID) !== "FIRE" && $nonFire >= 2) continue;
        $choices[] = "myGraveyard-" . $gi;
    }
    if(empty($choices)) {
        SlimeEruptionDamageStep($player, $count);
        return;
    }
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $choices), 1, tooltip:"Banish_a_Slime_from_graveyard?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SlimeEruptionBanishPick", 1);
}

$customDQHandlers["SlimeEruptionBanishPick"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        $count = intval(DecisionQueueController::GetVariable("slimeEruptionCount"));
        SlimeEruptionDamageStep($player, $count);
        return;
    }
    $obj = GetZoneObject($lastDecision);
    if($obj === null) {
        $count = intval(DecisionQueueController::GetVariable("slimeEruptionCount"));
        SlimeEruptionDamageStep($player, $count);
        return;
    }
    if(CardElement($obj->CardID) !== "FIRE") {
        $nonFire = intval(DecisionQueueController::GetVariable("slimeEruptionNonFire"));
        DecisionQueueController::StoreVariable("slimeEruptionNonFire", strval($nonFire + 1));
    }
    $count = intval(DecisionQueueController::GetVariable("slimeEruptionCount"));
    DecisionQueueController::StoreVariable("slimeEruptionCount", strval($count + 1));
    MZMove($player, $lastDecision, "myBanish");
    SlimeEruptionBanishLoop($player);
};

function SlimeEruptionDamageStep($player, $remaining) {
    if($remaining <= 0) return;
    $allUnits = array_merge(
        ZoneSearch("myField", ["ALLY", "CHAMPION"]),
        ZoneSearch("theirField", ["ALLY", "CHAMPION"])
    );
    $allUnits = FilterSpellshroudTargets($allUnits);
    if(empty($allUnits)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $allUnits), 1, tooltip:"Choose_a_unit_to_deal_1_damage");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SlimeEruptionDeal|" . $remaining, 1);
}

$customDQHandlers["SlimeEruptionDeal"] = function($player, $parts, $lastDecision) {
    $remaining = intval($parts[0] ?? 0);
    if($lastDecision !== "-" && $lastDecision !== "") {
        $sourceMZ = DecisionQueueController::GetVariable("slimeEruptionSource");
        DealDamage($player, $sourceMZ, $lastDecision, 1);
    }
    SlimeEruptionDamageStep($player, $remaining - 1);
};

$customDQHandlers["RefractedTwilightCopy"] = function($player, $parts, $lastDecision) {
    global $activateAbilityAbilities;
    $cardID = $parts[0] ?? "";
    $abilityIndex = intval($parts[1] ?? 0);
    $copies = intval($parts[2] ?? 0);
    $abilityKey = $cardID . ":" . $abilityIndex;
    if(!isset($activateAbilityAbilities[$abilityKey])) return;
    for($i = 0; $i < $copies; ++$i) {
        $activateAbilityAbilities[$abilityKey]($player);
    }
};

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

    // Chateau de Coeurs (vxc8u5zz08): buff counters can't be placed on opponent's objects
    if($counterType === "buff") {
        $objController = $obj->Controller ?? $player;
        for($cp = 1; $cp <= 2; $cp++) {
            $cpField = GetField($cp);
            foreach($cpField as $cpObj) {
                if(!$cpObj->removed && $cpObj->CardID === "vxc8u5zz08"
                   && !HasNoAbilities($cpObj) && $cpObj->Controller == $cp
                   && $objController != $cp) {
                    return; // Buff counter blocked by Chateau de Coeurs
                }
            }
        }
    }

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

function PutTopDeckCardIntoMaterialPreserved($player) {
    global $playerID, $Preserve_Cards;
    $deckRef = $player == $playerID ? "myDeck" : "theirDeck";
    $matRef = $player == $playerID ? "myMaterial" : "theirMaterial";
    $deck = GetZone($deckRef);
    if(empty($deck)) return;
    $cardID = $deck[0]->CardID;
    SetFlashMessage("REVEAL:" . $cardID);
    MZMove($player, $deckRef . "-0", $matRef);
    if(!isset($Preserve_Cards)) $Preserve_Cards = [];
    $Preserve_Cards[$cardID] = true;
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
            case "lxnq80yu75": // Gearstride Academy: pay (1) or sacrifice
                {
                    $hand = GetHand($player);
                    if(count($hand) > 0) {
                        DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Pay_(1)_for_Gearstride_Academy?");
                        DecisionQueueController::AddDecision($player, "CUSTOM", "GearstrideAcademyUpkeep|myField-" . $i, 1);
                    } else {
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
        // Purging Tempest (yuo7dbge3b): redirect GY destination to banish
        $effectiveDest = $gyRef;
        if(strpos($gyRef, "Graveyard") !== false) {
            $gyOwner = (strpos($gyRef, "my") === 0) ? $player : (($player == 1) ? 2 : 1);
            if(GlobalEffectCount($gyOwner, "yuo7dbge3b") > 0) {
                $effectiveDest = (strpos($gyRef, "my") === 0) ? "myBanish" : "theirBanish";
            }

            // Sasha, Purifying Acolyte (GRlUlcYRmV): while fostered, cards entering that
            // player's graveyard are banished instead.
            if($effectiveDest === $gyRef) {
                $gyOwnerField = GetField($gyOwner);
                foreach($gyOwnerField as $fObj) {
                    if(!$fObj->removed && $fObj->CardID === "GRlUlcYRmV" && !HasNoAbilities($fObj) && IsFostered($fObj)) {
                        $effectiveDest = (strpos($gyRef, "my") === 0) ? "myBanish" : "theirBanish";
                        break;
                    }
                }
            }
        }
        MZMove($player, "$deckRef-0", $effectiveDest);
    }
}

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
// Transform — flip a card on the field to its other orientation
// ============================================================================

/**
 * Transform a card on the field to its reverse side using CardOtherOrientation.
 * The card keeps its zone position, counters, status, subcards, and all other state.
 * @param int    $player The acting player.
 * @param string $mzCard The mzID of the card to transform (e.g. "myField-3").
 * @return bool True if transformed, false if card has no other orientation.
 */
function TransformCard($player, $mzCard) {
    $obj = &GetZoneObject($mzCard);
    $otherSide = CardOtherOrientation($obj->CardID);
    if($otherSide === null) return false;
    $obj->CardID = $otherSide;
    return true;
}

// ============================================================================
// Guo Jia Bonus — check if champion name starts with "Guo Jia"
// ============================================================================

/**
 * Check if the given player's champion is a Guo Jia champion (name starts with "Guo Jia").
 * @param int $player The player to check.
 * @return bool True if the player's champion is Guo Jia.
 */
function IsGuoJiaBonus($player) {
    $champMZ = FindChampionMZ($player);
    if($champMZ === null) return false;
    $champObj = GetZoneObject($champMZ);
    return strpos(CardName($champObj->CardID), "Guo Jia") === 0;
}

/**
 * Check if the given player's champion is a Jin champion (name starts with "Jin").
 * @param int $player The player to check.
 * @return bool True if the player's champion is Jin.
 */
function IsJinBonus($player) {
    $champMZ = FindChampionMZ($player);
    if($champMZ === null) return false;
    $champObj = GetZoneObject($champMZ);
    return strpos(CardName($champObj->CardID), "Jin") === 0;
}

/**
 * Add quest counters to the player's champion.
 * @param int $player The player whose champion gets quest counters.
 * @param int $amount Number of quest counters to add.
 * @return bool True if counters were added, false if no champion found.
 */
function AddQuestCounters($player, $amount = 1) {
    $champMZ = FindChampionMZ($player);
    if($champMZ === null) return false;
    AddCounters($player, $champMZ, "quest", $amount);
    return true;
}

/**
 * Get the number of quest counters on a player's champion.
 * @param int $player The player to check.
 * @return int The number of quest counters (0 if none or no champion).
 */
function GetQuestCounterCount($player) {
    $champMZ = FindChampionMZ($player);
    if($champMZ === null) return 0;
    $obj = GetZoneObject($champMZ);
    return isset($obj->Counters["quest"]) ? intval($obj->Counters["quest"]) : 0;
}

/**
 * Remove quest counters from the player's champion.
 * @param int $player The player whose champion loses quest counters.
 * @param int $amount Number of quest counters to remove.
 * @return bool True if counters were removed, false if not enough or no champion.
 */
function RemoveQuestCounters($player, $amount) {
    $champMZ = FindChampionMZ($player);
    if($champMZ === null) return false;
    $obj = &GetZoneObject($champMZ);
    $current = isset($obj->Counters["quest"]) ? intval($obj->Counters["quest"]) : 0;
    if($current < $amount) return false;
    $obj->Counters["quest"] = $current - $amount;
    if($obj->Counters["quest"] <= 0) unset($obj->Counters["quest"]);
    return true;
}

/**
 * Think Deep (xw9w6y7vtz): put up to two cards from the top of your deck into your graveyard.
 * Queues a decision loop that lets the player choose to mill 0, 1, or 2 cards.
 */
function ThinkDeepMill($player) {
    $deck = GetDeck($player);
    if(empty($deck)) return;
    $maxMill = min(2, count($deck));
    DecisionQueueController::StoreVariable("thinkDeepRemaining", strval($maxMill));
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Put_top_card_of_deck_into_graveyard?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ThinkDeepMillStep", 1);
}

function AvatarOfGenbuResolve($player) {
    if(!IsGuoJiaBonus($player)) return;
    AddQuestCounters($player, 2);
    $targets = [];
    foreach(ZoneSearch("myMaterial") as $mzID) {
        $obj = GetZoneObject($mzID);
        if($obj !== null && !$obj->removed && $obj->CardID === "6ce5rzrjd9") $targets[] = $mzID;
    }
    foreach(ZoneSearch("myBanish") as $mzID) {
        $obj = GetZoneObject($mzID);
        if($obj !== null && !$obj->removed && $obj->CardID === "6ce5rzrjd9") $targets[] = $mzID;
    }
    if(empty($targets)) return;
    if(count($targets) === 1) {
        MZMove($player, $targets[0], "myField");
        return;
    }
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $targets), 1,
        tooltip:"Put_Fabled_Sapphire_Fatestone_onto_the_field?_(Avatar_of_Genbu)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "AvatarGenbuFatestonePut", 1);
}

function ChimeOfEndlessDreamsRecycle($player) {
    $gy = &GetGraveyard($player);
    if(empty($gy)) return;
    $cardIDs = [];
    for($i = 0; $i < count($gy); ++$i) {
        if($gy[$i]->removed) continue;
        $cardIDs[] = $gy[$i]->CardID;
        $gy[$i]->Remove();
    }
    DecisionQueueController::CleanupRemovedCards();
    if(empty($cardIDs)) return;
    EngineShuffle($cardIDs);
    foreach($cardIDs as $cardID) {
        MZAddZone($player, "myDeck", $cardID);
    }
}

function LavastormDestroyAllAllies($player) {
    $targets = array_merge(ZoneSearch("myField", ["ALLY"]), ZoneSearch("theirField", ["ALLY"]));
    usort($targets, function($a, $b) {
        $ai = intval(explode("-", $a)[1] ?? 0);
        $bi = intval(explode("-", $b)[1] ?? 0);
        return $bi <=> $ai;
    });
    foreach($targets as $mzID) {
        $obj = GetZoneObject($mzID);
        if($obj === null || $obj->removed) continue;
        DoAllyDestroyed($player, $mzID);
    }
    DecisionQueueController::CleanupRemovedCards();
}

function ProfaneBindingsResolve($player) {
    QueueNegateActivation($player, [], "banish", -1, "ProfaneBindingsNegateResolve");
}

function SummonRetinueResolve($player) {
    for($i = 0; $i < 2; ++$i) {
        MZAddZone($player, "myField", "L67r0GlRHR");
        $field = &GetField($player);
        $field[count($field) - 1]->Status = 1;
    }
    if(GetOmenCount($player) < 3) return;
    $field = &GetField($player);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "L67r0GlRHR") {
            WakeupCard($player, "myField-" . $i);
        }
    }
}

function SunderingMoonEnterResolve($player) {
    if(!IsJinBonus($player)) return;
    $windAllies = ZoneSearch("myField", ["ALLY"], cardElements: ["WIND"]);
    if(count($windAllies) < 2) return;
    $mzID = DecisionQueueController::GetVariable("currentMZID");
    if($mzID === null || $mzID === "") return;
    AddTurnEffect($mzID, "8677jq0hfm-POWER");
}

function SunderingMoonPreventResolve($player) {
    if(!IsJinBonus($player)) return;
    $targets = ZoneSearch("myField", ["ALLY", "CHAMPION"]);
    if(empty($targets)) return;
    if(count($targets) === 1) {
        AddTurnEffect($targets[0], "PREVENT_ALL_1");
        return;
    }
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1,
        tooltip:"Prevent_the_next_1_damage_to_target_unit_(Sundering_Moon)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SunderingMoonPreventTarget", 1);
}

function FoundPowerResolve($player) {
    Empower($player, 3, "8pIXnuI1Df");
    if(!ZoneContainsCardID("myField", "k5wrAxBbF9")) return;
    $hand = ZoneSearch("myHand");
    if(empty($hand)) return;
    DecisionQueueController::StoreVariable("FoundPowerDiscarded", "0");
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $hand), 1,
        tooltip:"Discard_a_card?_(Found_Power_1/2)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "FoundPowerDiscard1", 1);
}

function DynasticWhirlpoolResolve($player) {
    $opponent = $player == 1 ? 2 : 1;
    global $playerID;
    $dest = $opponent == $playerID ? "myGraveyard" : "theirGraveyard";
    for($i = 0; $i < 15; ++$i) {
        $deck = &GetDeck($opponent);
        if(empty($deck)) break;
        MZMove($opponent, "myDeck-0", $dest);
    }
}

$customDQHandlers["CompanionFatestoneTransform"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $fieldIdx = intval($parts[0]);
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $mzCard = $zone . "-" . $fieldIdx;
    $obj = GetZoneObject($mzCard);
    if($obj === null || $obj->removed || $obj->CardID !== "izf4wdsbz9") return;
    TransformCard($player, $mzCard);
};

$customDQHandlers["ThinkDeepMillStep"] = function($player, $parts, $lastDecision) {
    $remaining = intval(DecisionQueueController::GetVariable("thinkDeepRemaining"));
    if($lastDecision !== "YES" || $remaining <= 0) return;
    $deck = GetDeck($player);
    if(empty($deck)) return;
    MZMove($player, "myDeck-0", "myGraveyard");
    $remaining--;
    if($remaining > 0) {
        $deck2 = GetDeck($player);
        if(!empty($deck2)) {
            DecisionQueueController::StoreVariable("thinkDeepRemaining", strval($remaining));
            DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Put_another_card_into_graveyard?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "ThinkDeepMillStep", 1);
        }
    }
};

// ============================================================================
// Fabled Azurite Fatestone (6ce5rzrjd9) — end-phase DQ handler + memory-banish hook
// ============================================================================

$customDQHandlers["FabledAzuriteFatestoneEndPhase"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $memory = &GetMemory($player);
    if(count($memory) === 0) return;
    // Banish a random card from memory
    $randIdx = EngineRandomInt(0, count($memory) - 1);
    MZMove($player, "myMemory-" . $randIdx, "myBanish");
    Draw($player, 1);
    // Trigger the "whenever you banish a card from your memory" quest counter ability
    OnBanishFromMemory($player);
};

/**
 * Hook called whenever a player banishes a card from their memory.
 * Fabled Azurite Fatestone (6ce5rzrjd9): [Guo Jia Bonus] put a quest counter on your champion.
 */
function OnBanishFromMemory($player) {
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    foreach($field as $fObj) {
        if(!$fObj->removed && $fObj->CardID === "6ce5rzrjd9" && !HasNoAbilities($fObj)) {
            if(IsGuoJiaBonus($player)) {
                AddQuestCounters($player, 1);
            }
            break;
        }
    }
}

?>
