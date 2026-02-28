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
 *      - Intercept: if any defender has Intercept, it must be targeted first.
 *      - Cleave: if the attacker has Cleave, all enemy units become defenders.
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
 * Get valid attack targets on the opponent's field.
 * Enforces Intercept: if any opposing unit has the Intercept keyword,
 * only Intercept units may be targeted.
 */
function GetValidAttackTargets($attackerMZ) {
    $opponents = ZoneSearch("theirField", ["ALLY", "CHAMPION"]);
    if(empty($opponents)) return $opponents;

    // Check for Intercept -- units with Intercept must be targeted first
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
    return $opponents;
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

    // Check valid targets
    $hasCleave = HasKeyword_Cleave($champion);
    if(!$hasCleave) {
        $validTargets = GetValidAttackTargets($championMZ);
        if(empty($validTargets)) {
            SetFlashMessage("No valid attack targets.");
            return false;
        }
    }

    // Exhaust the champion as cost
    ExhaustCard($player, $championMZ);

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
    $hasCleave = HasKeyword_Cleave($obj);
    if(!$hasCleave) {
        $validTargets = GetValidAttackTargets($actionCard);
        if(empty($validTargets)) {
            SetFlashMessage("No valid attack targets.");
            return false;
        }
    }

    // Step 2.a' -- Rest (exhaust) the attacker as a cost to attack
    ExhaustCard($turnPlayer, $actionCard);

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

// --- DQ handlers ---------------------------------------------------------------

/**
 * Handler: player chose an attack target.
 * Resolves single-target combat.
 */
$customDQHandlers["AttackTargetChosen"] = function($player, $parts, $lastDecision) {
    $attackerMZ = $parts[0];
    if($lastDecision === "-" || $lastDecision === "") {
        // No target chosen / fizzled -- clean up intent and return
        ClearIntent($player);
        DecisionQueueController::ClearVariable("CombatAttacker");
        return;
    }

    $attacker = &GetZoneObject($attackerMZ);
    $target = &GetZoneObject($lastDecision);

    //TODO: On Attack triggers would fire here -- place them on the EffectStack
    //TODO: Step 2.d -- Pay additional costs
    //TODO: Step 2.e -- Reconcile restrictions (Taunt, etc.)

    // Calculate total attack power (unit + intent cards)
    $totalPower = GetTotalAttackPower($attacker, $player);

    // Deal damage to defender
    if($totalPower > 0) {
        DealDamage($player, $attackerMZ, $lastDecision, $totalPower);
    }

    // Retaliation: defender deals damage back to the attacker (if defender is still alive)
    $defenderPower = ObjectCurrentPower($target);
    if($defenderPower > 0 && $target->Damage < ObjectCurrentHP($target)) {
        DealDamage($player, $lastDecision, $attackerMZ, $defenderPower);
    }

    // After combat: move all intent cards to graveyard
    ClearIntent($player);
    DecisionQueueController::ClearVariable("CombatAttacker");
};

/**
 * Handler: Cleave attack -- damages all opposing units.
 */
$customDQHandlers["CleaveAttack"] = function($player, $parts, $lastDecision) {
    $attackerMZ = $parts[0];
    $attacker = &GetZoneObject($attackerMZ);

    //TODO: On Attack triggers would fire here

    $totalPower = GetTotalAttackPower($attacker, $player);
    $opponents = ZoneSearch("theirField", ["ALLY", "CHAMPION"]);

    // Deal damage to each defending unit
    foreach($opponents as $defenderMZ) {
        if($totalPower > 0) {
            DealDamage($player, $attackerMZ, $defenderMZ, $totalPower);
        }
    }

    // Retaliation: each surviving defender retaliates
    // Re-fetch list since some may have been destroyed
    $survivingOpponents = ZoneSearch("theirField", ["ALLY", "CHAMPION"]);
    foreach($survivingOpponents as $defenderMZ) {
        $defender = &GetZoneObject($defenderMZ);
        $defPower = ObjectCurrentPower($defender);
        if($defPower > 0) {
            DealDamage($player, $defenderMZ, $attackerMZ, $defPower);
        }
    }

    // After combat: clear intent
    ClearIntent($player);
    DecisionQueueController::ClearVariable("CombatAttacker");
};

// --- damage resolution ---------------------------------------------------------

/**
 * Apply damage to a target unit. If damage >= HP, destroy it.
 */
function OnDealDamage($player, $source, $target, $amount) {
    $targetObj = &GetZoneObject($target);
    $targetObj->Damage += $amount;
    $currentHp = ObjectCurrentHP($targetObj);
    if($targetObj->Damage >= $currentHp) {
        AllyDestroyed($player, $target);
    }
}

?>
