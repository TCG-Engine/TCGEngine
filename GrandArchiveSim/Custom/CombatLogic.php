<?php
/**
 * Combat logic for handling attacks, damage calculation, and combat-related effects.
 *
 * Grand Archive combat flow:
 *   1. Attack cards are played from hand -> EffectStack -> resolve -> enter champion's intent (myIntent zone).
 *   2. A player declares an attack by selecting an awake ally or champion on the field.
 *   3. The attacker is rested (exhausted) as a cost.
 *   4. (Optional) The attacking player may choose weapons/objects to add to the attack (step 2.b).
 *   5. The attacking player chooses an attack target -- enemy ally or champion (step 2.c).
 *      - Stealth: units with Stealth can't be targeted unless the attacker has True Sight.
 *      - Intercept: if any non-Stealth defender has Intercept, it must be targeted first.
 *      - Cleave: if the attacker has Cleave, all eligible enemy units become defenders.
 *   6. Additional costs are paid (step 2.d).
 *   7. Restrictions (e.g. Taunt) are reconciled (step 2.e).
 *   8. Combat damage is dealt:
 *      - Attacker's total power = unit power + sum of attack-card powers in intent.
 *      - Damage is dealt to the defender; defender retaliates.
 *   9. After combat, attack cards in intent go to graveyard.
 */

// --- helpers -------------------------------------------------------------------

/**
 * Return an array of mzIDs for attack cards currently in a player's intent zone.
 */
function GetIntentCards($player) {
    global $playerID;
    $zone = $player == $playerID ? "myIntent" : "theirIntent";
    $zoneArr = &GetZone($zone);
    $results = [];
    for($i = 0; $i < count($zoneArr); ++$i) {
        $results[] = $zone . "-" . $i;
    }
    return $results;
}

/**
 * Calculate the total attack power for a combat:
 *   base unit power  +  sum of power from all attack cards in the attacker's intent.
 */
function GetTotalAttackPower($attackerObj, $player) {
    $totalPower = ObjectCurrentPower($attackerObj);
    $intentCards = GetIntentCards($player);
    foreach($intentCards as $mzID) {
        $intentObj = &GetZoneObject($mzID);
        $intentPower = ObjectCurrentPower($intentObj);
        if($intentPower > 0) {
            $totalPower += $intentPower;
        }
    }
    return $totalPower;
}

/**
 * Check whether the attacking side has True Sight for this combat.
 * True Sight can come from:
 *   - The attacking unit itself ("This unit's attacks can target units with stealth.")
 *   - Attack cards in the attacker's intent zone ("This attack can target units with stealth.")
 *   - Weapons used in the attack ("Attacks using this weapon can target units with stealth.")
 *     (Weapon selection is not yet implemented; will be checked here once it is.)
 */
function AttackerHasTrueSight($attackerMZ, $player) {
    // Check the attacking unit
    $attacker = &GetZoneObject($attackerMZ);
    if(HasTrueSight($attacker)) return true;

    // Check attack cards in intent
    $intentCards = GetIntentCards($player);
    foreach($intentCards as $intentMZ) {
        $intentObj = &GetZoneObject($intentMZ);
        if(HasTrueSight($intentObj)) return true;
    }

    //TODO: Check weapons once weapon selection is implemented

    return false;
}

/**
 * Check whether the attacking side has Cleave for this combat.
 * Cleave can come from:
 *   - The attacking unit itself ("Attack all units a chosen opponent controls.")
 *   - Attack cards in the attacker's intent zone (e.g. Hurricane Sweep)
 *   - Weapons used in the attack (not yet implemented)
 */
function AttackerHasCleave($attackerMZ, $player) {
    // Check the attacking unit
    $attacker = &GetZoneObject($attackerMZ);
    if(HasKeyword_Cleave($attacker)) return true;

    // Check attack cards in intent
    $intentCards = GetIntentCards($player);
    foreach($intentCards as $intentMZ) {
        $intentObj = &GetZoneObject($intentMZ);
        if(HasKeyword_Cleave($intentObj)) return true;
    }

    // Bestial Frenzy (HsaWNAsmAQ): cleave via turn effect
    if(ObjectHasEffect($attacker, "HsaWNAsmAQ_CLEAVE")) return true;

    return false;
}

/**
 * Get valid attack targets on the opponent's field.
 * Enforces:
 *   - Stealth: units with Stealth can't be targeted unless the attacker has True Sight.
 *   - Intercept: if any remaining opposing unit has Intercept, only those may be targeted.
 */
function GetValidAttackTargets($attackerMZ) {
    global $playerID;
    $player = (strpos($attackerMZ, "my") === 0) ? $playerID : (($playerID == 1) ? 2 : 1);

    $opponents = ZoneSearch("theirField", ["ALLY", "CHAMPION"]);
    if(empty($opponents)) return $opponents;

    // Stealth: filter out units with Stealth unless the attacker has True Sight
    $hasTrueSight = AttackerHasTrueSight($attackerMZ, $player);
    if(!$hasTrueSight) {
        $nonStealthOpponents = [];
        foreach($opponents as $mzID) {
            $obj = &GetZoneObject($mzID);
            if(!HasStealth($obj)) {
                $nonStealthOpponents[] = $mzID;
            }
        }
        $opponents = $nonStealthOpponents;
        if(empty($opponents)) return $opponents;
    }

    // Check for Intercept -- units with Intercept must be targeted first
    // Port Smuggler (uCIEMgGjWe): CB attacks can't be intercepted
    $attacker = &GetZoneObject($attackerMZ);
    $bypassIntercept = false;
    if($attacker !== null && $attacker->CardID === "uCIEMgGjWe" && IsClassBonusActive($player, CardClasses("uCIEMgGjWe"))) {
        $bypassIntercept = true;
    }
    if(!$bypassIntercept) {
        $interceptTargets = [];
        foreach($opponents as $mzID) {
            $obj = &GetZoneObject($mzID);
            if(HasKeyword_Intercept($obj)) {
                $interceptTargets[] = $mzID;
            }
        }
        // If any opposing unit has Intercept, only those may be targeted
        if(!empty($interceptTargets)) {
            return $interceptTargets;
        }
    }
    return $opponents;
}

/**
 * Flip a zone mzID between player perspectives.
 * e.g. "myField-2" becomes "theirField-2" and vice versa.
 */
function FlipZonePerspective($mzID) {
    if(strpos($mzID, "my") === 0) {
        return "their" . substr($mzID, 2);
    } else if(strpos($mzID, "their") === 0) {
        return "my" . substr($mzID, 5);
    }
    return $mzID; // global zones like EffectStack don't flip
}

/**
 * Send all attack cards from intent zone to graveyard after combat resolves.
 */
function ClearIntent($player) {
    $intentCards = GetIntentCards($player);
    // Work backwards so index removal doesn't shift remaining cards
    for($i = count($intentCards) - 1; $i >= 0; --$i) {
        MZMove($player, $intentCards[$i], "myGraveyard");
    }
}

// --- attack declaration (from intent) ------------------------------------------

/**
 * Declare an attack with the player's champion after an attack card enters intent.
 * Called from the DQ after an ATTACK card resolves. Finds the champion, validates,
 * exhausts it, and queues target selection + combat resolution.
 * Does NOT call ExecuteStaticMethods (we are already inside the DQ loop).
 */
function DeclareChampionAttack($player) {
    // Find the player's champion on the field
    $champions = ZoneSearch("myField", ["CHAMPION"]);
    if(empty($champions)) {
        SetFlashMessage("No champion on field to attack with.");
        ClearIntent($player);
        return false;
    }

    // Use the first champion found
    //TODO: If multiple champions, let player choose
    $championMZ = $champions[0];
    $champion = &GetZoneObject($championMZ);

    // Champion must be awake to attack
    if($champion->Status != 2) {
        SetFlashMessage("Champion must be awake to attack.");
        // Intent cards stay until end of turn
        return false;
    }

    // First turn restriction
    $currentTurn = GetTurnNumber();
    if($currentTurn <= 1) {
        SetFlashMessage("Cannot attack on the first turn.");
        return false;
    }

    // Check valid targets (Cleave can come from the champion OR an attack card in intent)
    $hasCleave = AttackerHasCleave($championMZ, $player);
    if(!$hasCleave) {
        $validTargets = GetValidAttackTargets($championMZ);
        if(empty($validTargets)) {
            SetFlashMessage("No valid attack targets.");
            return false;
        }
    }

    // Rest the champion as cost (Grand Archive: "rest" = exhaust)
    RestCard($player, $championMZ);

    // Store attacker for resolution
    DecisionQueueController::StoreVariable("CombatAttacker", $championMZ);

    // Choose target and resolve
    if($hasCleave) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "CleaveAttack|" . $championMZ, 100);
    } else {
        ChooseAttackTarget($player, $championMZ);
    }

    return true;
}

$customDQHandlers["DeclareChampionAttack"] = function($player, $parts, $lastDecision) {
    DeclareChampionAttack($player);
};

// --- attack declaration (from field click) -------------------------------------

/**
 * Entry point: player clicked an ally/champion on their field to declare an attack.
 *
 * Steps (per rules):
 *   2.a  Select attacker -- must be awake (Status == 2) or granted permission by an effect.
 *   2.a' Rest the attacker as a cost.
 *   2.b  (Future) Choose additional weapons / objects.
 *   2.c  Choose attack target (respecting Intercept / Cleave).
 *   2.d  Pay any calculated / additional costs.
 *   2.e  Reconcile restrictions (Taunt, etc.).
 *        If at any point the attack becomes illegal, reverse all steps.
 */
function BeginCombatPhase($actionCard) {
    $turnPlayer = GetTurnPlayer();
    $obj = &GetZoneObject($actionCard);
    $cardType = CardType($obj->CardID);

    // Only allies and champions can declare attacks as the attacking unit
    if(!PropertyContains($cardType, "ALLY") && !PropertyContains($cardType, "CHAMPION")) {
        SetFlashMessage("Only allies and champions can declare attacks.");
        return false;
    }

    // Step 2.a -- The object must be able to attack (must be awake, Status == 2)
    if($obj->Status != 2) {
        SetFlashMessage("This unit must be awake to attack.");
        return false;
    }

    // Validate power > 0
    if(ObjectCurrentPower($obj) <= 0) {
        SetFlashMessage("Cannot attack with a unit that has 0 or less power.");
        return false;
    }

    // Rule 1.h -- Players can't declare attacks on their first turn
    $currentTurn = GetTurnNumber();
    if($currentTurn <= 1) {
        SetFlashMessage("Cannot attack on the first turn.");
        return false;
    }

    // Step 2.c (pre-check) -- Must have at least one valid target, unless attacker has Cleave
    // Cleave can come from the attacking unit itself OR from an attack card already in intent
    $hasCleave = AttackerHasCleave($actionCard, $turnPlayer);
    if(!$hasCleave) {
        $validTargets = GetValidAttackTargets($actionCard);
        if(empty($validTargets)) {
            SetFlashMessage("No valid attack targets.");
            return false;
        }
    }

    // Step 2.a' -- Rest the attacker as a cost to attack
    RestCard($turnPlayer, $actionCard);

    // Store the attacker location for later handlers
    DecisionQueueController::StoreVariable("CombatAttacker", $actionCard);

    // Step 2.b -- (Future) Choose additional weapons to add to the attack
    //TODO: Add weapon selection step here (REGALIA,WEAPON cards)

    // Step 2.c -- Choose attack target
    if($hasCleave) {
        // Cleave: all opposing units become defenders automatically
        DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "CleaveAttack|" . $actionCard, 100);
    } else {
        ChooseAttackTarget($turnPlayer, $actionCard);
    }

    // Execute the decision queue
    $dqController = new DecisionQueueController();
    $dqController->ExecuteStaticMethods($turnPlayer, "-");

    return true;
}

/**
 * Queue interactive target selection for the attack.
 */
function ChooseAttackTarget($player, $attackerMZ) {
    $validTargets = GetValidAttackTargets($attackerMZ);
    $targetList = implode("&", $validTargets);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetList, 100, "Choose_attack_target");
    DecisionQueueController::AddDecision($player, "CUSTOM", "AttackTargetChosen|" . $attackerMZ, 100);
}

// --- On Attack trigger dispatch -------------------------------------------------

/**
 * Dispatch On Attack abilities for a unit that just declared an attack.
 * Called via the generated OnAttack() macro wrapper.
 */
function OnAttackTrigger($player, $mzID) {
    global $onAttackAbilities;
    // Dispatch OnAttack for the attacker itself (ally or champion attacking directly)
    $obj = GetZoneObject($mzID);
    if($obj !== null && isset($onAttackAbilities[$obj->CardID . ":0"])) {
        $onAttackAbilities[$obj->CardID . ":0"]($player);
    }
    // Also dispatch OnAttack for any attack cards currently in the player's intent zone
    $intentCards = GetIntentCards($player);
    foreach($intentCards as $iMZ) {
        $iObj = GetZoneObject($iMZ);
        if($iObj === null) continue;
        if(isset($onAttackAbilities[$iObj->CardID . ":0"])) {
            $onAttackAbilities[$iObj->CardID . ":0"]($player);
        }
    }
    // Weapon OnAttack: if the champion is attacking, also fire OnAttack for weapons on the field
    if($obj !== null && PropertyContains(CardType($obj->CardID), "CHAMPION")) {
        $field = &GetField($player);
        for($fi = 0; $fi < count($field); ++$fi) {
            if($field[$fi]->removed) continue;
            $fCardType = CardType($field[$fi]->CardID);
            if(PropertyContains($fCardType, "WEAPON") || PropertyContains($fCardType, "REGALIA")) {
                if(isset($onAttackAbilities[$field[$fi]->CardID . ":0"])) {
                    $onAttackAbilities[$field[$fi]->CardID . ":0"]($player);
                }
            }
        }
    }
    // Majestic Spirit's Crest (Tx6iJQNSA6): TurnEffect on champion — when champion attacks, draw 1
    if($obj !== null && PropertyContains(CardType($obj->CardID), "CHAMPION")) {
        if(in_array("Tx6iJQNSA6", $obj->TurnEffects)) {
            Draw($player, 1);
        }
    }
}

// --- DQ handlers ---------------------------------------------------------------

/**
 * Handler: player chose an attack target.
 * Stores combat state and grants Opportunity before the damage step.
 */
$customDQHandlers["AttackTargetChosen"] = function($player, $parts, $lastDecision) {
    $attackerMZ = $parts[0];
    if($lastDecision === "-" || $lastDecision === "") {
        // No target chosen / fizzled -- clean up intent and return
        ClearIntent($player);
        DecisionQueueController::ClearVariable("CombatAttacker");
        return;
    }

    // Fire On Attack triggers (may grant effects like critical)
    OnAttack($player, $attackerMZ);

    // Store combat state for the damage and retaliation handlers
    DecisionQueueController::StoreVariable("CombatTarget", $lastDecision);
    DecisionQueueController::StoreVariable("CombatAttackerPlayer", strval($player));
    DecisionQueueController::StoreVariable("CombatIsCleave", "0");

    // Grant Opportunity window before damage step (turn player gets priority first)
    $turnPlayer = GetTurnPlayer();
    GrantOpportunityWindow($turnPlayer, "CombatDealDamage", $player);
};

/**
 * Handler: Deal single-target combat damage after the damage-step Opportunity.
 * $playerID is the attacker (swapped by ResolveOpportunityWindow).
 */
$customDQHandlers["CombatDealDamage"] = function($player, $parts, $lastDecision) {
    $attackerMZ = DecisionQueueController::GetVariable("CombatAttacker");
    $targetMZ   = DecisionQueueController::GetVariable("CombatTarget");
    $attackerPlayer = intval(DecisionQueueController::GetVariable("CombatAttackerPlayer"));
    $defenderPlayer = ($attackerPlayer == 1) ? 2 : 1;

    if($attackerMZ === null || $targetMZ === null) return;

    // Validate attacker still exists (may have been removed during Opportunity)
    $attacker = &GetZoneObject($attackerMZ);
    if($attacker === null) {
        ClearIntent($attackerPlayer);
        DecisionQueueController::ClearVariable("CombatAttacker");
        DecisionQueueController::ClearVariable("CombatTarget");
        DecisionQueueController::ClearVariable("CombatAttackerPlayer");
        DecisionQueueController::ClearVariable("CombatIsCleave");
        return;
    }

    $totalPower = GetTotalAttackPower($attacker, $attackerPlayer);

    // Flip mzIDs to defender's perspective for critical / retaliation handlers
    $defenderMZ_fromDefender = FlipZonePerspective($targetMZ);
    $attackerMZ_fromDefender = FlipZonePerspective($attackerMZ);

    if($totalPower > 0) {
        $criticalAmount = GetCriticalAmount($attacker, $attackerPlayer);

        if($criticalAmount > 0) {
            $opponentHand = &GetZone("theirHand");
            if(count($opponentHand) >= $criticalAmount) {
                // Critical: ask defender to discard to prevent double damage
                DecisionQueueController::AddDecision($defenderPlayer, "YESNO", "critical", 100,
                    "Discard_" . $criticalAmount . "_to_prevent_critical?");
                DecisionQueueController::AddDecision($defenderPlayer, "CUSTOM",
                    "CriticalResolve|" . $attackerMZ_fromDefender . "|" . $defenderMZ_fromDefender . "|" . $totalPower . "|" . $criticalAmount, 100);
                // Retaliation Opportunity on defender's queue after critical resolves
                DecisionQueueController::AddDecision($defenderPlayer, "CUSTOM", "CombatRetaliationOpportunity", 150);
            } else {
                // Defender can't pay — damage automatically doubled
                DealDamage($attackerPlayer, $attackerMZ, $targetMZ, $totalPower * 2);
                DecisionQueueController::AddDecision($player, "CUSTOM", "CombatRetaliationOpportunity", 150);
            }
        } else {
            // No critical — deal normal damage
            DealDamage($attackerPlayer, $attackerMZ, $targetMZ, $totalPower);
            DecisionQueueController::AddDecision($player, "CUSTOM", "CombatRetaliationOpportunity", 150);
        }
    } else {
        // Zero power — no damage, proceed to retaliation
        DecisionQueueController::AddDecision($player, "CUSTOM", "CombatRetaliationOpportunity", 150);
    }
};

/**
 * Handler: Grant Opportunity before the retaliation step (shared by single/cleave).
 */
$customDQHandlers["CombatRetaliationOpportunity"] = function($player, $parts, $lastDecision) {
    $turnPlayer = GetTurnPlayer();
    $attackerPlayer = intval(DecisionQueueController::GetVariable("CombatAttackerPlayer") ?? "1");
    $defenderPlayer = ($attackerPlayer == 1) ? 2 : 1;
    $isCleave = DecisionQueueController::GetVariable("CombatIsCleave") === "1";
    $nextHandler = $isCleave ? "CleaveProceedToRetaliation" : "CombatProceedToRetaliation";
    GrantOpportunityWindow($turnPlayer, $nextHandler, $defenderPlayer);
};

/**
 * Handler: Proceed to single-target retaliation after Opportunity.
 * $playerID is the defender (swapped by ResolveOpportunityWindow).
 */
$customDQHandlers["CombatProceedToRetaliation"] = function($player, $parts, $lastDecision) {
    $attackerMZ = DecisionQueueController::GetVariable("CombatAttacker");
    $targetMZ   = DecisionQueueController::GetVariable("CombatTarget");
    $attackerPlayer = intval(DecisionQueueController::GetVariable("CombatAttackerPlayer") ?? "1");
    $defenderPlayer = ($attackerPlayer == 1) ? 2 : 1;

    if($attackerMZ === null || $targetMZ === null) {
        DecisionQueueController::AddDecision($defenderPlayer, "CUSTOM", "CombatCleanup|" . $attackerPlayer, 200);
        return;
    }

    // Flip to defender's perspective
    $defenderMZ_fromDefender = FlipZonePerspective($targetMZ);
    $attackerMZ_fromDefender = FlipZonePerspective($attackerMZ);

    // Retaliation step: let the defending player choose whether to retaliate
    DecisionQueueController::AddDecision($defenderPlayer, "MZMAYCHOOSE", $defenderMZ_fromDefender, 100, "Retaliate?");
    DecisionQueueController::AddDecision($defenderPlayer, "CUSTOM", "Retaliate|" . $attackerMZ_fromDefender . "|" . $defenderMZ_fromDefender, 100);

    // Cleanup on defender's queue after retaliation (block 200)
    DecisionQueueController::AddDecision($defenderPlayer, "CUSTOM", "CombatCleanup|" . $attackerPlayer, 200);
};

/**
 * Handler: Cleave attack -- damages all opposing units.
 */
$customDQHandlers["CleaveAttack"] = function($player, $parts, $lastDecision) {
    $attackerMZ = $parts[0];

    // Fire On Attack triggers (may grant effects like critical)
    OnAttack($player, $attackerMZ);

    // Store combat state
    DecisionQueueController::StoreVariable("CombatAttackerPlayer", strval($player));
    DecisionQueueController::StoreVariable("CombatIsCleave", "1");

    // Grant Opportunity window before damage step
    $turnPlayer = GetTurnPlayer();
    GrantOpportunityWindow($turnPlayer, "CleaveDealDamage", $player);
};

/**
 * Handler: Deal Cleave damage to all opponents after Opportunity.
 * $playerID is the attacker (swapped by ResolveOpportunityWindow).
 */
$customDQHandlers["CleaveDealDamage"] = function($player, $parts, $lastDecision) {
    $attackerMZ = DecisionQueueController::GetVariable("CombatAttacker");
    $attackerPlayer = intval(DecisionQueueController::GetVariable("CombatAttackerPlayer"));

    if($attackerMZ === null) return;

    $attacker = &GetZoneObject($attackerMZ);
    if($attacker === null) {
        ClearIntent($attackerPlayer);
        DecisionQueueController::ClearVariable("CombatAttacker");
        DecisionQueueController::ClearVariable("CombatAttackerPlayer");
        DecisionQueueController::ClearVariable("CombatIsCleave");
        return;
    }

    $totalPower = GetTotalAttackPower($attacker, $attackerPlayer);
    $opponents = ZoneSearch("theirField", ["ALLY", "CHAMPION"]);

    // Stealth: filter out units with Stealth unless the attacker has True Sight
    $hasTrueSight = AttackerHasTrueSight($attackerMZ, $attackerPlayer);
    if(!$hasTrueSight) {
        $opponents = array_values(array_filter($opponents, function($mzID) {
            $obj = &GetZoneObject($mzID);
            return !HasStealth($obj);
        }));
    }

    // Cleave critical: doubles all damage (no per-target discard choice)
    $criticalAmount = GetCriticalAmount($attacker, $attackerPlayer);
    $effectivePower = ($criticalAmount > 0) ? $totalPower * 2 : $totalPower;

    foreach($opponents as $defenderMZ) {
        if($effectivePower > 0) {
            DealDamage($attackerPlayer, $attackerMZ, $defenderMZ, $effectivePower);
        }
    }

    // Queue CombatRetaliationOpportunity (shared with single-target)
    DecisionQueueController::AddDecision($player, "CUSTOM", "CombatRetaliationOpportunity", 150);
};

/**
 * Handler: Proceed to Cleave retaliation after Opportunity.
 * Each surviving defender independently retaliates.
 * $playerID is the defender (swapped by ResolveOpportunityWindow).
 */
$customDQHandlers["CleaveProceedToRetaliation"] = function($player, $parts, $lastDecision) {
    $attackerMZ = DecisionQueueController::GetVariable("CombatAttacker");
    $attackerPlayer = intval(DecisionQueueController::GetVariable("CombatAttackerPlayer") ?? "1");
    $defenderPlayer = ($attackerPlayer == 1) ? 2 : 1;

    if($attackerMZ === null) {
        DecisionQueueController::AddDecision($defenderPlayer, "CUSTOM", "CombatCleanup|" . $attackerPlayer, 200);
        return;
    }

    $attackerMZ_fromDefender = FlipZonePerspective($attackerMZ);

    // Each surviving defender may retaliate independently
    // ($playerID = defender, so myField = defender's field)
    $survivingOpponents = ZoneSearch("myField", ["ALLY", "CHAMPION"]);
    foreach($survivingOpponents as $survivorMZ) {
        DecisionQueueController::AddDecision($defenderPlayer, "MZMAYCHOOSE", $survivorMZ, 100, "Retaliate_with_" . $survivorMZ . "?");
        DecisionQueueController::AddDecision($defenderPlayer, "CUSTOM", "Retaliate|" . $attackerMZ_fromDefender, 100);
    }

    // Cleanup on defender's queue after all retaliation decisions (block 200)
    DecisionQueueController::AddDecision($defenderPlayer, "CUSTOM", "CombatCleanup|" . $attackerPlayer, 200);
};

/**
 * Handler: defending player chose whether to retaliate.
 * $parts[0] = attacker mzID (from defender's perspective, i.e. theirField-X)
 * $parts[1] = defender mzID (from defender's perspective, i.e. myField-X) -- only for single-target
 * $lastDecision = the card they clicked (myField-X) or "-" if they passed.
 */
$customDQHandlers["Retaliate"] = function($player, $parts, $lastDecision) {
    $attackerMZ = $parts[0]; // from defender's perspective: theirField-X
    if($lastDecision === "-" || $lastDecision === "") {
        // Defender chose not to retaliate
        return;
    }

    // $lastDecision is the defender's unit from their perspective (myField-X)
    $defender = &GetZoneObject($lastDecision);
    $defenderPower = ObjectCurrentPower($defender);
    if($defenderPower > 0 && $defender->Damage < ObjectCurrentHP($defender)) {
        DealDamage($player, $lastDecision, $attackerMZ, $defenderPower);
    }
};

/**
 * Handler: clean up after combat resolves (intent + variables).
 */
$customDQHandlers["CombatCleanup"] = function($player, $parts, $lastDecision) {
    // $parts[0] = attacker player ID (when cleanup is on the defender's queue)
    $attackerPlayer = !empty($parts[0]) ? intval($parts[0]) : $player;

    // Swap $playerID to attacker so ClearIntent resolves my/their correctly
    global $playerID;
    $savedPlayerID = $playerID;
    $playerID = $attackerPlayer;
    ClearIntent($attackerPlayer);
    $playerID = $savedPlayerID;

    DecisionQueueController::ClearVariable("CombatAttacker");
    DecisionQueueController::ClearVariable("CombatTarget");
    DecisionQueueController::ClearVariable("CombatAttackerPlayer");
    DecisionQueueController::ClearVariable("CombatIsCleave");
};

// --- critical resolution -------------------------------------------------------

/**
 * Handler: defender chose whether to discard to prevent critical doubling.
 * Runs in the DEFENDER's player context.
 * $parts[0] = attacker mzID (from defender's perspective: theirField-X)
 * $parts[1] = target mzID  (from defender's perspective: myField-X)
 * $parts[2] = base total power
 * $parts[3] = critical discard amount
 * $lastDecision = "YES" or "NO"
 */
$customDQHandlers["CriticalResolve"] = function($player, $parts, $lastDecision) {
    $attackerMZ = $parts[0]; // from defender's perspective: theirField-X
    $targetMZ = $parts[1];   // from defender's perspective: myField-X
    $totalPower = intval($parts[2]);
    $critAmount = intval($parts[3]);

    $attackerPlayer = ($player == 1) ? 2 : 1;

    if($lastDecision === "YES") {
        // Defender pays: discard N cards, then deal normal damage
        DiscardCards($player, $critAmount);
        // Queue damage after discards resolve (block 50 < retaliation block 100)
        // Keep mzIDs in defender's perspective for correct interpretation in handler
        DecisionQueueController::AddDecision($player, "CUSTOM",
            "FinishCombatDamage|" . $attackerMZ . "|" . $targetMZ . "|" . $totalPower, 50);
    } else {
        // Defender refuses: deal doubled damage
        // mzIDs are in defender's perspective; GetZoneObject will interpret them with defender's $playerID
        DealDamage($attackerPlayer, $attackerMZ, $targetMZ, $totalPower * 2);
    }
};

/**
 * Handler: deal combat damage after critical discard resolves.
 * Runs in the DEFENDER's player context.
 * $parts[0] = attacker mzID (from defender's perspective: theirField-X)
 * $parts[1] = target mzID  (from defender's perspective: myField-X)
 * $parts[2] = damage amount
 */
$customDQHandlers["FinishCombatDamage"] = function($player, $parts, $lastDecision) {
    $attackerMZ = $parts[0];   // from defender's perspective: theirField-X
    $targetMZ = $parts[1];     // from defender's perspective: myField-X
    $amount = intval($parts[2]);
    $attackerPlayer = ($player == 1) ? 2 : 1;
    // Keep mzIDs in defender's perspective; GetZoneObject interprets them with defender's $playerID
    DealDamage($attackerPlayer, $attackerMZ, $targetMZ, $amount);
};

// --- damage resolution ---------------------------------------------------------

/**
 * Apply damage to a target unit. If damage >= HP, destroy it.
 * After applying damage, trigger any DealDamage abilities on the target card.
 */
function OnDealDamage($player, $source, $target, $amount) {
    $targetObj = &GetZoneObject($target);
    // Barrier Servant: prevent next damage if tagged with BARRIER_PREVENT_DAMAGE (one-time)
    if(in_array("BARRIER_PREVENT_DAMAGE", $targetObj->TurnEffects)) {
        $targetObj->TurnEffects = array_values(array_filter($targetObj->TurnEffects, fn($e) => $e !== "BARRIER_PREVENT_DAMAGE"));
        return; // Damage fully prevented
    }
    // Intangible Geist (Zu53izIFTX): CB prevent all combat damage
    if($targetObj->CardID === "Zu53izIFTX") {
        $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
        if($combatAttacker !== null && IsClassBonusActive($targetObj->Controller, CardClasses("Zu53izIFTX"))) {
            return; // Combat damage prevented
        }
    }
    // Bubble Mage class bonus: if target has the amplify effect, it takes +1 damage
    if(ObjectHasEffect($targetObj, "0n0DM1T9gz")) {
        $amount += 1;
    }
    $targetObj->Damage += $amount;

    // Trigger per-card DealDamage abilities on the target card
    global $dealDamageAbilities;
    if(isset($dealDamageAbilities) && isset($dealDamageAbilities[$targetObj->CardID . ":0"])) {
        $dealDamageAbilities[$targetObj->CardID . ":0"]($player);
    }

    $currentHp = ObjectCurrentHP($targetObj);
    if($targetObj->Damage >= $currentHp) {
        AllyDestroyed($player, $target);
    }
}

?>
