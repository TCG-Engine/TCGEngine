<?php
/**
 * Combat logic for handling attacks, damage calculation, and combat-related effects.
 *
 * Grand Archive combat flow:
 *   1. Attack cards are played from hand -> EffectStack -> resolve -> enter champion's intent (myIntent zone).
 *   2. A player declares an attack by selecting an awake ally or champion on the field.
 *   3. The attacker is rested (exhausted) as a cost.
 *   4. (Optional) The attacking player may choose a weapon to add to the attack (champion only).
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
 * Return an array of mzIDs for awake weapon cards on a player's field.
 * Only returns weapons with durability > 0.
 */
function GetAvailableWeapons($player) {
    $weapons = ZoneSearch("myField", ["WEAPON"]);
    $available = [];
    foreach($weapons as $mzID) {
        $obj = &GetZoneObject($mzID);
        if($obj->Status == 2 && GetCounterCount($obj, "durability") > 0) {
            $available[] = $mzID;
        }
    }
    return $available;
}

/**
 * Return the mzID of the currently selected combat weapon, or null.
 */
function GetCombatWeapon() {
    $mz = DecisionQueueController::GetVariable("CombatWeapon");
    if($mz === null || $mz === "-" || $mz === "") return null;
    return $mz;
}

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
    // Add weapon power if a weapon was selected for this attack
    $weaponMZ = GetCombatWeapon();
    if($weaponMZ !== null) {
        $weaponObj = &GetZoneObject($weaponMZ);
        if($weaponObj !== null) {
            $weaponPower = ObjectCurrentPower($weaponObj);
            if($weaponPower > 0) {
                $totalPower += $weaponPower;
            }
        }
    }
    return $totalPower;
}

/**
 * Check whether the attacking side has True Sight for this combat.
 * True Sight can come from:
 *   - The attacking unit itself ("This unit's attacks can target units with stealth.")
 *   - Attack cards in the attacker's intent zone ("This attack can target units with stealth.")
 *   - Weapons used in the attack
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

    // Check the weapon used in this attack
    $weaponMZ = GetCombatWeapon();
    if($weaponMZ !== null) {
        $weaponObj = &GetZoneObject($weaponMZ);
        if($weaponObj !== null && HasTrueSight($weaponObj)) return true;
    }

    return false;
}

/**
 * Check whether the attacking side has Cleave for this combat.
 * Cleave can come from:
 *   - The attacking unit itself ("Attack all units a chosen opponent controls.")
 *   - Attack cards in the attacker's intent zone (e.g. Hurricane Sweep)
 *   - Weapons used in the attack
 */
function AttackerHasCleave($attackerMZ, $player) {
    // Check the attacking unit
    $attacker = &GetZoneObject($attackerMZ);
    if(!HasNoAbilities($attacker) && HasKeyword_Cleave($attacker)) return true;

    // Check attack cards in intent
    $intentCards = GetIntentCards($player);
    foreach($intentCards as $intentMZ) {
        $intentObj = &GetZoneObject($intentMZ);
        if(HasKeyword_Cleave($intentObj)) return true;
    }

    // Bestial Frenzy (HsaWNAsmAQ): cleave via turn effect
    if(!HasNoAbilities($attacker) && ObjectHasEffect($attacker, "HsaWNAsmAQ_CLEAVE")) return true;

    // Check the weapon used in this attack
    $weaponMZ = GetCombatWeapon();
    if($weaponMZ !== null) {
        $weaponObj = &GetZoneObject($weaponMZ);
        if($weaponObj !== null && !HasNoAbilities($weaponObj) && HasKeyword_Cleave($weaponObj)) return true;
    }

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
    // Strike from the Mist (DHn9J7gX6g): CB if prepared, can't be intercepted
    if(!$bypassIntercept) {
        $intentCards = GetIntentCards($player);
        foreach($intentCards as $intentMZ) {
            $intentObj = &GetZoneObject($intentMZ);
            if($intentObj !== null && $intentObj->CardID === "DHn9J7gX6g"
                && in_array("PREPARED", $intentObj->TurnEffects)
                && IsClassBonusActive($player, explode(",", CardClasses("DHn9J7gX6g")))) {
                $bypassIntercept = true;
                break;
            }
        }
    }
    if(!$bypassIntercept) {
        $interceptTargets = [];
        foreach($opponents as $mzID) {
            $obj = &GetZoneObject($mzID);
            if(!HasNoAbilities($obj) && HasKeyword_Intercept($obj) && !in_array("NO_INTERCEPT", $obj->TurnEffects)) {
                $interceptTargets[] = $mzID;
            }
            // Awakened Deacon (c9p4lpnvx7): intercept while controlling 2+ phantasias
            if(!HasNoAbilities($obj) && $obj->CardID === "c9p4lpnvx7") {
                global $playerID;
                $deaconField = $obj->Controller == $playerID ? "myField" : "theirField";
                if(count(ZoneSearch($deaconField, ["PHANTASIA"])) >= 2) {
                    if(!in_array($mzID, $interceptTargets)) $interceptTargets[] = $mzID;
                }
            }
        }
        // If any opposing unit has Intercept, only those may be targeted
        if(!empty($interceptTargets)) {
            return $interceptTargets;
        }
    }

    // Check for Taunt -- awake units with Taunt must be targeted before other units.
    // Unblockable bypasses Taunt just as it bypasses Intercept (same $bypassIntercept flag).
    // Taunt only applies while the unit is awake (Status == 2).
    if(!$bypassIntercept) {
        $tauntTargets = [];
        foreach($opponents as $mzID) {
            $obj = &GetZoneObject($mzID);
            if(HasTaunt($obj) && $obj->Status == 2) {
                $tauntTargets[] = $mzID;
            }
        }
        // If any opposing unit has Taunt (and is awake), only those may be targeted
        if(!empty($tauntTargets)) {
            return $tauntTargets;
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

    // Peaceful Reunion: attacks are blocked for both players until beginning of caster's next turn.
    // Effect is stored only on the caster; check both sides here.
    $prOpponent = ($player == 1) ? 2 : 1;
    if(GlobalEffectCount($player, "wr42i6eifn") > 0 || GlobalEffectCount($prOpponent, "wr42i6eifn") > 0) {
        SetFlashMessage("Attacks are prevented until the beginning of your next turn.");
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

    // Step 2.b -- Weapon selection: if the attacker is a champion, offer weapon choice
    $availableWeapons = GetAvailableWeapons($player);
    if(!empty($availableWeapons)) {
        $weaponList = implode("&", $availableWeapons);
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $weaponList, 95, "Choose_weapon?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "WeaponSelected", 95);
    } else {
        DecisionQueueController::StoreVariable("CombatWeapon", "-");
    }

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
 *   2.b  Choose additional weapons to add to the attack (champion only).
 *   2.c  Choose attack target (respecting Intercept / Cleave).
 *   2.d  Pay any calculated / additional costs.
 *   2.e  Reconcile restrictions (Taunt, etc.).
 *        If at any point the attack becomes illegal, reverse all steps.
 */
function BeginCombatPhase($actionCard) {
    $turnPlayer = GetTurnPlayer();
    $obj = &GetZoneObject($actionCard);
    $cardType = EffectiveCardType($obj);

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

    // Validate power > 0 (champions with 0 power can still attack if they have a weapon)
    if(ObjectCurrentPower($obj) <= 0) {
        if(!PropertyContains($cardType, "CHAMPION") || empty(GetAvailableWeapons($turnPlayer))) {
            SetFlashMessage("Cannot attack with a unit that has 0 or less power.");
            return false;
        }
    }

    // Rule 1.h -- Players can't declare attacks on their first turn
    $currentTurn = GetTurnNumber();
    if($currentTurn <= 1) {
        SetFlashMessage("Cannot attack on the first turn.");
        return false;
    }

    // Peaceful Reunion: attacks are blocked for both players until beginning of caster's next turn.
    // Effect is stored only on the caster; check both sides here.
    $prOpp = ($turnPlayer == 1) ? 2 : 1;
    if(GlobalEffectCount($turnPlayer, "wr42i6eifn") > 0 || GlobalEffectCount($prOpp, "wr42i6eifn") > 0) {
        SetFlashMessage("Attacks are prevented until the beginning of your next turn.");
        return false;
    }

    // Plea for Peace (ir99sx6q3p): players must pay (1) for each attack declaration.
    // Effect is stored on the caster; check both sides.
    $pleaActive = (GlobalEffectCount($turnPlayer, "ir99sx6q3p") > 0 || GlobalEffectCount($prOpp, "ir99sx6q3p") > 0);
    if($pleaActive) {
        $hand = GetZone("myHand");
        if(count($hand) < 1) {
            SetFlashMessage("Must pay (1) to attack (Plea for Peace). Not enough cards in hand.");
            return false;
        }
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

    // Plea for Peace (ir99sx6q3p): pay (1) reserve for each attack declaration
    if($pleaActive) {
        DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "ReserveCard", 90);
    }

    // Store the attacker location for later handlers
    DecisionQueueController::StoreVariable("CombatAttacker", $actionCard);

    // Step 2.b -- Weapon selection: only for champion attacks, not allies
    if(PropertyContains($cardType, "CHAMPION")) {
        $availableWeapons = GetAvailableWeapons($turnPlayer);
        if(!empty($availableWeapons)) {
            $weaponList = implode("&", $availableWeapons);
            DecisionQueueController::AddDecision($turnPlayer, "MZMAYCHOOSE", $weaponList, 95, "Choose_weapon?");
            DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "WeaponSelected", 95);
        } else {
            DecisionQueueController::StoreVariable("CombatWeapon", "-");
        }
    } else {
        // Allies can't use weapons
        DecisionQueueController::StoreVariable("CombatWeapon", "-");
    }

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
    if($obj !== null && !HasNoAbilities($obj) && isset($onAttackAbilities[$obj->CardID . ":0"])) {
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
    // Weapon OnAttack: if the champion is attacking and a weapon was selected, fire its OnAttack
    if($obj !== null && PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
        $weaponMZ = GetCombatWeapon();
        if($weaponMZ !== null) {
            $weaponObj = GetZoneObject($weaponMZ);
            if($weaponObj !== null && isset($onAttackAbilities[$weaponObj->CardID . ":0"])) {
                $onAttackAbilities[$weaponObj->CardID . ":0"]($player);
            }
        }
    }
    // Majestic Spirit's Crest (Tx6iJQNSA6): TurnEffect on champion — when champion attacks, draw 1
    if($obj !== null && PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
        if(in_array("Tx6iJQNSA6", $obj->TurnEffects)) {
            Draw($player, 1);
        }
    }

    // Fractured Crown (suo6gb0op3): first attack each turn gets +2 POWER
    // Check if the attacking player controls Fractured Crown on field and hasn't fired yet this turn
    $field = &GetField($player);
    $hasFracturedCrown = false;
    for($fci = 0; $fci < count($field); ++$fci) {
        if(!$field[$fci]->removed && $field[$fci]->CardID === "suo6gb0op3") {
            $hasFracturedCrown = true;
            break;
        }
    }
    if($hasFracturedCrown && GlobalEffectCount($player, "FRACTURED_CROWN_FIRED") == 0) {
        AddGlobalEffects($player, "FRACTURED_CROWN_FIRED");
        // Apply +2 POWER to attack cards in intent
        $intentCards = GetIntentCards($player);
        foreach($intentCards as $iMZ) {
            AddTurnEffect($iMZ, "suo6gb0op3");
        }
    }

    // Tonoris, Might of Humanity (yevpmu6gvn): next attack +3 POWER — consume and apply
    if($obj !== null && in_array("yevpmu6gvn", $obj->TurnEffects)) {
        AddTurnEffect($mzID, "yevpmu6gvn_POWER");
        $obj->TurnEffects = array_values(array_diff($obj->TurnEffects, ["yevpmu6gvn"]));
    }
}

/**
 * Dispatch On Hit abilities for a unit whose attack just dealt damage.
 * Called after combat damage is dealt (including after critical resolution).
 * Fires for: the attacking unit, attack cards in intent, and weapons.
 *
 * @param int    $player     The attacking player
 * @param string $attackerMZ The attacker's mzID
 */
function OnHitTrigger($player, $attackerMZ) {
    global $onHitAbilities;
    if(!isset($onHitAbilities) || !is_array($onHitAbilities)) return;

    // Dispatch On Hit for the attacker itself
    $obj = GetZoneObject($attackerMZ);
    if($obj !== null && !HasNoAbilities($obj) && isset($onHitAbilities[$obj->CardID . ":0"])) {
        $onHitAbilities[$obj->CardID . ":0"]($player);
    }

    // Dispatch On Hit for attack cards in intent
    $intentCards = GetIntentCards($player);
    foreach($intentCards as $iMZ) {
        $iObj = GetZoneObject($iMZ);
        if($iObj === null) continue;
        if(isset($onHitAbilities[$iObj->CardID . ":0"])) {
            $onHitAbilities[$iObj->CardID . ":0"]($player);
        }
    }

    // Dispatch On Hit for combat weapon
    $weaponMZ = GetCombatWeapon();
    if($weaponMZ !== null) {
        $weaponObj = GetZoneObject($weaponMZ);
        if($weaponObj !== null && isset($onHitAbilities[$weaponObj->CardID . ":0"])) {
            $onHitAbilities[$weaponObj->CardID . ":0"]($player);
        }
    }

    // Tristan, Grim Stalker (K5luT8aRzc): On Ally Hit passive —
    // when any ally you control hits an enemy ally, you may remove 3 prep counters to destroy the hit ally.
    $attackerObj = GetZoneObject($attackerMZ);
    $isAllyAttacker = $attackerObj !== null && PropertyContains(EffectiveCardType($attackerObj), "ALLY");
    if($isAllyAttacker) {
        $hitTarget = DecisionQueueController::GetVariable("CombatTarget");
        if($hitTarget !== null) {
            $hitObj = GetZoneObject($hitTarget);
            if($hitObj !== null && !$hitObj->removed && PropertyContains(EffectiveCardType($hitObj), "ALLY")) {
                // Look for Tristan on the attacker's field
                $myField = GetZone("myField");
                foreach($myField as $fi => $fObj) {
                    if(!$fObj->removed && $fObj->CardID === "K5luT8aRzc" && !HasNoAbilities($fObj)) {
                        if(GetPrepCounterCount($fObj) >= 3) {
                            $tristanMZ = "myField-" . $fi;
                            DecisionQueueController::StoreVariable("TristanHitTarget", $hitTarget);
                            DecisionQueueController::StoreVariable("TristanChampMZ", $tristanMZ);
                            DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Remove_3_prep_counters_to_destroy_hit_ally?");
                            DecisionQueueController::AddDecision($player, "CUSTOM", "TristanOnAllyHit", 1);
                        }
                        break;
                    }
                }
            }
        }
    }
}

/**
 * Track whether a combat kill occurred during damage resolution.
 * Set to true by OnDealDamage/DealUnpreventableDamage when a unit is killed
 * by combat damage (goes directly from field to graveyard).
 * Reset by combat handlers before dealing damage, read after.
 *
 * Per rules: On Kill only triggers when the unit enters the graveyard
 * directly from play due to combat damage, NOT through secondary effects
 * like On Hit abilities. Therefore OnKillTrigger fires BEFORE OnHitTrigger.
 */
$_combatKillOccurred = false;

/**
 * Mark that a combat kill just occurred. Called from OnDealDamage when
 * damage >= HP and a CombatAttacker variable is set (indicating combat context).
 */
function SetCombatKillOccurred() {
    global $_combatKillOccurred;
    $_combatKillOccurred = true;
}

/**
 * Check and reset the combat kill flag.
 * @return bool True if a kill occurred since last reset.
 */
function ConsumeCombatKill() {
    global $_combatKillOccurred;
    $result = $_combatKillOccurred;
    $_combatKillOccurred = false;
    return $result;
}

/**
 * Reset the combat kill flag (call before dealing combat damage).
 */
function ResetCombatKill() {
    global $_combatKillOccurred;
    $_combatKillOccurred = false;
}

/**
 * Dispatch On Kill abilities for a unit whose attack just killed a defender.
 * Called after combat damage is dealt and the defender has been destroyed,
 * but BEFORE OnHitTrigger fires (per rules: On Kill only triggers from
 * direct combat damage kills, not from secondary On Hit effects).
 *
 * Fires for: the attacking unit, attack cards in intent, and weapons.
 * Mirrors OnHitTrigger's dispatch pattern.
 *
 * @param int    $player     The attacking player
 * @param string $attackerMZ The attacker's mzID
 */
function OnKillTrigger($player, $attackerMZ) {
    global $onKillAbilities;

    // Dispatch On Kill for the attacker itself
    $obj = GetZoneObject($attackerMZ);
    if(isset($onKillAbilities) && is_array($onKillAbilities)) {
        if($obj !== null && !HasNoAbilities($obj) && isset($onKillAbilities[$obj->CardID . ":0"])) {
            $onKillAbilities[$obj->CardID . ":0"]($player);
        }

        // Dispatch On Kill for attack cards in intent
        $intentCards = GetIntentCards($player);
        foreach($intentCards as $iMZ) {
            $iObj = GetZoneObject($iMZ);
            if($iObj === null) continue;
            if(isset($onKillAbilities[$iObj->CardID . ":0"])) {
                $onKillAbilities[$iObj->CardID . ":0"]($player);
            }
        }

        // Dispatch On Kill for combat weapon
        $weaponMZ = GetCombatWeapon();
        if($weaponMZ !== null) {
            $weaponObj = GetZoneObject($weaponMZ);
            if($weaponObj !== null && isset($onKillAbilities[$weaponObj->CardID . ":0"])) {
                $onKillAbilities[$weaponObj->CardID . ":0"]($player);
            }
        }
    }

    // Granted On Kill effects via TurnEffects on the attacker (champion).
    // Lorraine, Blademaster (TJTeWcZnsQ): On Enter grants "On Kill: Draw a card" to attacks.
    // The TurnEffect is placed on the champion; when the champion's attack kills, draw a card.
    if($obj !== null && in_array("TJTeWcZnsQ", $obj->TurnEffects)) {
        Draw($player, amount: 1);
    }
}

// --- DQ handlers ---------------------------------------------------------------

/**
 * Handler: player chose a weapon for the attack (or passed).
 * Stores the weapon mzID so GetTotalAttackPower, TrueSight/Cleave checks, and
 * CombatCleanup (durability loss) can reference it.
 */
$customDQHandlers["WeaponSelected"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") {
        // Player declined to use a weapon
        DecisionQueueController::StoreVariable("CombatWeapon", "-");
    } else {
        DecisionQueueController::StoreVariable("CombatWeapon", $lastDecision);
    }
};

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
        DecisionQueueController::ClearVariable("CombatWeapon");
        return;
    }

    // Fire On Attack triggers (may grant effects like critical)
    OnAttack($player, $attackerMZ);

    // Surveillance Stone (kk46Whz7CJ): opponent may banish it to draw on the attacker's 3rd attack
    if(OnAttackCallCount($player) === 3) {
        $ssOwner = ($player == 1) ? 2 : 1;
        $ssField = GetField($ssOwner);
        for($ssi = count($ssField) - 1; $ssi >= 0; $ssi--) {
            if(!$ssField[$ssi]->removed && $ssField[$ssi]->CardID === "kk46Whz7CJ") {
                DecisionQueueController::AddDecision($ssOwner, "YESNO", "-", 51, tooltip:"Banish_Surveillance_Stone_to_draw?");
                DecisionQueueController::AddDecision($ssOwner, "CUSTOM", "SurveillanceStone", 51);
                break;
            }
        }
    }

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

    // Weapon durability loss: occurs in the damage step regardless of how much damage is dealt.
    // (Per rules: durability is still removed if damage = 0, but NOT if the damage step is skipped.)
    $weaponMZ = GetCombatWeapon();
    if($weaponMZ !== null) {
        $weaponObj = &GetZoneObject($weaponMZ);
        if($weaponObj !== null && !$weaponObj->removed) {
            RemoveCounters($attackerPlayer, $weaponMZ, "durability", 1);
            if(GetCounterCount($weaponObj, "durability") <= 0) {
                DoAllyDestroyed($attackerPlayer, $weaponMZ);
            }
        }
    }

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
                ResetCombatKill();
                DealDamage($attackerPlayer, $attackerMZ, $targetMZ, $totalPower * 2);
                if(ConsumeCombatKill()) OnKillTrigger($attackerPlayer, $attackerMZ);
                OnHitTrigger($attackerPlayer, $attackerMZ);
                DecisionQueueController::AddDecision($player, "CUSTOM", "CombatRetaliationOpportunity", 150);
            }
        } else {
            // No critical — deal normal damage
            ResetCombatKill();
            DealDamage($attackerPlayer, $attackerMZ, $targetMZ, $totalPower);
            if(ConsumeCombatKill()) OnKillTrigger($attackerPlayer, $attackerMZ);
            OnHitTrigger($attackerPlayer, $attackerMZ);
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

    // Check if any intent card has NO_RETALIATE (e.g. Seeking Shot [Class Bonus])
    $attackerIntentCards = GetIntentCards($attackerPlayer);
    foreach($attackerIntentCards as $iMZ) {
        $iObj = GetZoneObject($iMZ);
        if($iObj !== null && in_array("NO_RETALIATE", $iObj->TurnEffects)) {
            DecisionQueueController::AddDecision($defenderPlayer, "CUSTOM", "CombatCleanup|" . $attackerPlayer, 200);
            return;
        }
    }

    // Flip to defender's perspective
    $defenderMZ_fromDefender = FlipZonePerspective($targetMZ);
    $attackerMZ_fromDefender = FlipZonePerspective($attackerMZ);

    // Build the list of valid retaliators: the actual defender plus any unit that
    // can retaliate while not defending (e.g. Lurking Assailant).
    $retaliatorOptions = [$defenderMZ_fromDefender];
    $defenderField = GetField($defenderPlayer);
    foreach($defenderField as $i => $fieldObj) {
        if($fieldObj->removed) continue;
        $mzID = "myField-" . $i; // in defender's perspective
        if($mzID === $defenderMZ_fromDefender) continue; // skip the actual defender
        if($fieldObj->Status != 2) continue; // must be ready (awake)
        // Lurking Assailant (uq2r6v374c): [Level 1+] may retaliate while not defending
        if($fieldObj->CardID === "uq2r6v374c") {
            $retaliatorOptions[] = $mzID;
        }
    }
    $retaliatorOptionStr = implode("&", $retaliatorOptions);

    // Retaliation step: let the defending player choose whether to retaliate
    DecisionQueueController::AddDecision($defenderPlayer, "MZMAYCHOOSE", $retaliatorOptionStr, 100, "Retaliate?");
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

    // Surveillance Stone (kk46Whz7CJ): opponent may banish it to draw on the attacker's 3rd attack
    if(OnAttackCallCount($player) === 3) {
        $ssOwner = ($player == 1) ? 2 : 1;
        $ssField = GetField($ssOwner);
        for($ssi = count($ssField) - 1; $ssi >= 0; $ssi--) {
            if(!$ssField[$ssi]->removed && $ssField[$ssi]->CardID === "kk46Whz7CJ") {
                DecisionQueueController::AddDecision($ssOwner, "YESNO", "-", 51, tooltip:"Banish_Surveillance_Stone_to_draw?");
                DecisionQueueController::AddDecision($ssOwner, "CUSTOM", "SurveillanceStone", 51);
                break;
            }
        }
    }

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
        DecisionQueueController::ClearVariable("CombatWeapon");
        return;
    }

    $totalPower = GetTotalAttackPower($attacker, $attackerPlayer);

    // Weapon durability loss: occurs in the damage step regardless of how much damage is dealt.
    $weaponMZ = GetCombatWeapon();
    if($weaponMZ !== null) {
        $weaponObj = &GetZoneObject($weaponMZ);
        if($weaponObj !== null && !$weaponObj->removed) {
            RemoveCounters($attackerPlayer, $weaponMZ, "durability", 1);
            if(GetCounterCount($weaponObj, "durability") <= 0) {
                DoAllyDestroyed($attackerPlayer, $weaponMZ);
            }
        }
    }

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

    $hitDealt = false;
    ResetCombatKill();
    foreach($opponents as $defenderMZ) {
        if($effectivePower > 0) {
            DealDamage($attackerPlayer, $attackerMZ, $defenderMZ, $effectivePower);
            $hitDealt = true;
        }
    }
    if($hitDealt) {
        if(ConsumeCombatKill()) OnKillTrigger($attackerPlayer, $attackerMZ);
        OnHitTrigger($attackerPlayer, $attackerMZ);
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
    // Track the retaliating unit so passive power bonuses (e.g. Intrepid Highwayman) can check it
    DecisionQueueController::StoreVariable("CombatRetaliator", $lastDecision);
    $defenderPower = ObjectCurrentPower($defender);
    DecisionQueueController::ClearVariable("CombatRetaliator");
    if($defenderPower > 0 && $defender->Damage < ObjectCurrentHP($defender)) {
        DealDamage($player, $lastDecision, $attackerMZ, $defenderPower);
    }
};

/**
 * Handler: Surveillance Stone — banish self and draw if YES.
 */
$customDQHandlers["SurveillanceStone"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $ssField = GetField($player);
    for($ssi = count($ssField) - 1; $ssi >= 0; $ssi--) {
        if(!$ssField[$ssi]->removed && $ssField[$ssi]->CardID === "kk46Whz7CJ") {
            MZMove($player, "myField-" . $ssi, "myBanish");
            Draw($player, amount: 1);
            break;
        }
    }
};

/**
 * Handler: Tristan, Grim Stalker (K5luT8aRzc) On Ally Hit response.
 * $lastDecision = "YES" or "NO" from the preceding YESNO decision.
 */
$customDQHandlers["TristanOnAllyHit"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $champMZ = DecisionQueueController::GetVariable("TristanChampMZ");
    if($champMZ === null) return;
    $champObj = GetZoneObject($champMZ);
    if($champObj === null || $champObj->removed || $champObj->CardID !== "K5luT8aRzc") return;
    if(GetPrepCounterCount($champObj) < 3) return;
    RemoveCounters($player, $champMZ, "preparation", 3);
    $hitTarget = DecisionQueueController::GetVariable("TristanHitTarget");
    if($hitTarget !== null) {
        $targetObj = GetZoneObject($hitTarget);
        if($targetObj !== null && !$targetObj->removed) {
            DoAllyDestroyed($player, $hitTarget);
        }
    }
};

/**
 * Handler: clean up after combat resolves (intent + variables).
 */
$customDQHandlers["CombatCleanup"] = function($player, $parts, $lastDecision) {
    // $parts[0] = attacker player ID (when cleanup is on the defender's queue)
    $attackerPlayer = !empty($parts[0]) ? intval($parts[0]) : $player;

    global $playerID;
    $savedPlayerID = $playerID;
    $playerID = $attackerPlayer;
    ClearIntent($attackerPlayer);
    $playerID = $savedPlayerID;

    DecisionQueueController::ClearVariable("CombatAttacker");
    DecisionQueueController::ClearVariable("CombatTarget");
    DecisionQueueController::ClearVariable("CombatAttackerPlayer");
    DecisionQueueController::ClearVariable("CombatIsCleave");
    DecisionQueueController::ClearVariable("CombatWeapon");
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
        ResetCombatKill();
        DealDamage($attackerPlayer, $attackerMZ, $targetMZ, $totalPower * 2);
        if(ConsumeCombatKill()) OnKillTrigger($attackerPlayer, $attackerMZ);
        OnHitTrigger($attackerPlayer, $attackerMZ);
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
    ResetCombatKill();
    DealDamage($attackerPlayer, $attackerMZ, $targetMZ, $amount);
    if(ConsumeCombatKill()) OnKillTrigger($attackerPlayer, $attackerMZ);
    OnHitTrigger($attackerPlayer, $attackerMZ);
};

// --- damage resolution ---------------------------------------------------------

/**
 * Apply damage to a target unit. If damage >= HP, destroy it.
 * After applying damage, trigger any DealDamage abilities on the target card.
 */
function OnDealDamage($player, $source, $target, $amount) {
    $targetObj = &GetZoneObject($target);

    // Varuck, Smoldering Spire (IyM7IBCQeb): "Damage dealt by fire element sources you
    // control can't be prevented." If the source is fire element and the source's controller
    // has Varuck on the field, bypass all prevention effects → use DealUnpreventableDamage path.
    $sourceObj = GetZoneObject($source);
    if($sourceObj !== null) {
        $sourceElement = CardElement($sourceObj->CardID);
        if($sourceElement === "FIRE") {
            $sourceController = $sourceObj->Controller ?? $player;
            $scField = &GetField($sourceController);
            foreach($scField as $scObj) {
                if(!$scObj->removed && $scObj->CardID === "IyM7IBCQeb") {
                    // Fire source + Varuck present → unpreventable
                    DealUnpreventableDamage($player, $source, $target, $amount);
                    return;
                }
            }
        }
    }

    // Overwhelming Swing (aebjvwbciz): [Class Bonus][Level 2+] combat damage is unpreventable
    $isCombatContext = DecisionQueueController::GetVariable("CombatAttacker") !== null;
    if($isCombatContext) {
        $intentCards = GetIntentCards($player);
        foreach($intentCards as $intentMZ) {
            $intentObj = GetZoneObject($intentMZ);
            if($intentObj !== null && $intentObj->CardID === "aebjvwbciz"
                && IsClassBonusActive($player, explode(",", CardClasses("aebjvwbciz")))
                && PlayerLevel($player) >= 2) {
                DealUnpreventableDamage($player, $source, $target, $amount);
                return;
            }
        }
    }

    // Barrier Servant: prevent next damage if tagged with BARRIER_PREVENT_DAMAGE (one-time)
    if(in_array("BARRIER_PREVENT_DAMAGE", $targetObj->TurnEffects)) {
        $targetObj->TurnEffects = array_values(array_filter($targetObj->TurnEffects, fn($e) => $e !== "BARRIER_PREVENT_DAMAGE"));
        return; // Damage fully prevented
    }

    // Storm of Thorns (39i1f0ht2t): prevent 1 damage to units you control this turn;
    // if the damage source is a unit, deal 1 damage to that source.
    static $stormOfThornsGuard = false;
    if(!$stormOfThornsGuard && $amount > 0) {
        $targetController = $targetObj->Controller ?? $player;
        $isUnit = PropertyContains(EffectiveCardType($targetObj), "ALLY") || PropertyContains(EffectiveCardType($targetObj), "CHAMPION");
        if($isUnit && GlobalEffectCount($targetController, "39i1f0ht2t") > 0) {
            $amount -= 1;
            // Reflect: if source is a unit on the field, deal 1 damage back
            $sourceObj = GetZoneObject($source);
            if($sourceObj !== null && !$sourceObj->removed) {
                $sourceType = EffectiveCardType($sourceObj);
                if(PropertyContains($sourceType, "ALLY") || PropertyContains($sourceType, "CHAMPION")) {
                    $stormOfThornsGuard = true;
                    OnDealDamage($targetController, $target, $source, 1);
                    $stormOfThornsGuard = false;
                }
            }
            if($amount <= 0) return;
        }
    }

    // Protective Fractal: prevent 1 damage per effect
    $protectivePrevention = GetProtectiveFractalPrevention($targetObj);
    if($protectivePrevention > 0) {
        $amount -= 1;
        $targetObj->TurnEffects = array_values(array_filter($targetObj->TurnEffects, fn($e) => $e !== "1lw9n0wpbh"));
        if($amount <= 0) return; // All damage prevented
    }

    // PREVENT_ALL_N: prevent up to N of any damage this turn (Guarded Dissipation)
    if($amount > 0) {
        foreach($targetObj->TurnEffects as $idx => $effect) {
            if(strpos($effect, "PREVENT_ALL_") === 0) {
                $budget = intval(substr($effect, 12));
                $prevented = min($budget, $amount);
                $amount -= $prevented;
                $remaining = $budget - $prevented;
                if($remaining <= 0) {
                    unset($targetObj->TurnEffects[$idx]);
                    $targetObj->TurnEffects = array_values($targetObj->TurnEffects);
                } else {
                    $targetObj->TurnEffects[$idx] = "PREVENT_ALL_" . $remaining;
                }
                break;
            }
        }
        if($amount <= 0) return;
    }

    // PREVENT_COMBAT_N: prevent up to N combat damage this turn (Deflecting Edge)
    $isCombat = DecisionQueueController::GetVariable("CombatAttacker") !== null;
    if($isCombat && $amount > 0) {
        foreach($targetObj->TurnEffects as $idx => $effect) {
            if(strpos($effect, "PREVENT_COMBAT_") === 0) {
                $budget = intval(substr($effect, 15));
                $prevented = min($budget, $amount);
                $amount -= $prevented;
                $remaining = $budget - $prevented;
                if($remaining <= 0) {
                    unset($targetObj->TurnEffects[$idx]);
                    $targetObj->TurnEffects = array_values($targetObj->TurnEffects);
                } else {
                    $targetObj->TurnEffects[$idx] = "PREVENT_COMBAT_" . $remaining;
                }
                break;
            }
        }
        if($amount <= 0) return;
    }

    // PREVENT_EXACT_N: prevent damage only if exactly N (Perfect Repulsion), then draw a card
    if($amount > 0) {
        foreach($targetObj->TurnEffects as $idx => $effect) {
            if(strpos($effect, "PREVENT_EXACT_") === 0) {
                $exactAmount = intval(substr($effect, 14));
                if($amount === $exactAmount) {
                    $amount = 0;
                    unset($targetObj->TurnEffects[$idx]);
                    $targetObj->TurnEffects = array_values($targetObj->TurnEffects);
                    $controller = $targetObj->Controller;
                    Draw($controller, 1);
                }
                break;
            }
        }
        if($amount <= 0) return;
    }

    // Champion-only prevention effects
    $isChampion = PropertyContains(EffectiveCardType($targetObj), "CHAMPION");
    if($isChampion && $amount > 0) {
        // PREVENT_CHAMP_ENLIGHTEN: prevent all of next damage to champion; gain enlighten = amount prevented (Spellshield: Arcane)
        if(in_array("PREVENT_CHAMP_ENLIGHTEN", $targetObj->TurnEffects)) {
            $prevented = $amount;
            $amount = 0;
            $targetObj->TurnEffects = array_values(array_filter($targetObj->TurnEffects, fn($e) => $e !== "PREVENT_CHAMP_ENLIGHTEN"));
            AddCounters($targetObj->Controller, $target, "enlighten", $prevented);
            return;
        }
        // PREVENT_CHAMP_ASTRA_GLIMPSE: prevent all of next damage to champion; Glimpse X where X = amount prevented (Spellshield: Astra)
        if(in_array("PREVENT_CHAMP_ASTRA_GLIMPSE", $targetObj->TurnEffects)) {
            $prevented = $amount;
            $amount = 0;
            $targetObj->TurnEffects = array_values(array_filter($targetObj->TurnEffects, fn($e) => $e !== "PREVENT_CHAMP_ASTRA_GLIMPSE"));
            if($prevented > 0) {
                Glimpse($targetObj->Controller, $prevented);
            }
            return;
        }
        // PREVENT_CHAMP_WIND_BUFF: prevent all of next damage to champion; if 3+ prevented, buff an ally (Spellshield: Wind)
        if(in_array("PREVENT_CHAMP_WIND_BUFF", $targetObj->TurnEffects)) {
            $prevented = $amount;
            $amount = 0;
            $targetObj->TurnEffects = array_values(array_filter($targetObj->TurnEffects, fn($e) => $e !== "PREVENT_CHAMP_WIND_BUFF"));
            if($prevented >= 3) {
                $controller = $targetObj->Controller;
                global $playerID;
                $fieldZone = $controller == $playerID ? "myField" : "theirField";
                $allies = ZoneSearch($fieldZone, ["ALLY"]);
                if(!empty($allies)) {
                    DecisionQueueController::AddDecision($controller, "MZCHOOSE", implode("&", $allies), 1, tooltip:"Put_a_buff_counter_on_an_ally");
                    DecisionQueueController::AddDecision($controller, "CUSTOM", "SpellshieldWindBuff", 1);
                }
            }
            return;
        }
        // PREVENT_CHAMP_N: prevent up to N damage to champion this turn (Veiling Breeze)
        foreach($targetObj->TurnEffects as $idx => $effect) {
            if(strpos($effect, "PREVENT_CHAMP_") === 0 && strpos($effect, "PREVENT_CHAMP_ENLIGHTEN") !== 0 && strpos($effect, "PREVENT_CHAMP_WIND_BUFF") !== 0 && strpos($effect, "PREVENT_CHAMP_ASTRA_GLIMPSE") !== 0) {
                $budget = intval(substr($effect, 14));
                $prevented = min($budget, $amount);
                $amount -= $prevented;
                $remaining = $budget - $prevented;
                if($remaining <= 0) {
                    unset($targetObj->TurnEffects[$idx]);
                    $targetObj->TurnEffects = array_values($targetObj->TurnEffects);
                } else {
                    $targetObj->TurnEffects[$idx] = "PREVENT_CHAMP_" . $remaining;
                }
                break;
            }
        }
        if($amount <= 0) return;
    }

    // Intangible Geist (Zu53izIFTX): CB prevent all combat damage
    if($targetObj->CardID === "Zu53izIFTX") {
        $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
        if($combatAttacker !== null && IsClassBonusActive($targetObj->Controller, CardClasses("Zu53izIFTX"))) {
            return; // Combat damage prevented
        }
    }

    // Morgan, Soul Guide (ka5av43ehj): [Level 1+] prevent all non-combat damage to Morgan
    if($targetObj->CardID === "ka5av43ehj" && !$isCombat) {
        $targetController = $targetObj->Controller ?? $player;
        if(PlayerLevel($targetController) >= 1) {
            return; // Non-combat damage prevented
        }
    }

    if (!$isCombat && $amount > 0 && $targetObj->CardID !== "5k1vt1cn1t") {
        $targetController = $targetObj->Controller ?? $player;
        $controllerField = &GetField($targetController);
        foreach ($controllerField as $blancheObj) {
            if ($blancheObj === null || $blancheObj->removed) continue;
            if ($blancheObj->CardID === "5k1vt1cn1t"
                    && PlayerLevel($targetController) >= 2) {
                $memoryCount = count(GetMemory($targetController));
                $amount = max(0, $amount - $memoryCount);
                break;
            }
        }
        if ($amount <= 0) $amount = 0;
    }

    // Intrepid Spearman (pal7cpvn96): [Level 1+] once per turn replacement effect —
    // when combat damage would be dealt to this card, reveal a random memory card;
    // if it is wind element, prevent 3 of that damage.
    if($isCombat && $amount > 0 && $targetObj->CardID === "pal7cpvn96"
            && !in_array("pal7cpvn96", $targetObj->TurnEffects)) {
        $targetController = $targetObj->Controller ?? $player;
        if(PlayerLevel($targetController) >= 1) {
            $memory = GetMemory($targetController);
            if(!empty($memory)) {
                $randomIdx = array_rand($memory);
                $revealedCard = $memory[$randomIdx];
                // Visual reveal via flash message
                $existing = GetFlashMessage();
                if(is_string($existing) && strpos($existing, 'REVEAL:') === 0) {
                    SetFlashMessage($existing . '|' . $revealedCard->CardID);
                } else {
                    SetFlashMessage('REVEAL:' . $revealedCard->CardID);
                }
                if(CardElement($revealedCard->CardID) === "WIND") {
                    $amount = max(0, $amount - 3);
                }
                $targetObj->TurnEffects[] = "pal7cpvn96";
                if($amount <= 0) return;
            }
        }
    }

    // Bubble Mage class bonus: if target has the amplify effect, it takes +1 damage
    if(ObjectHasEffect($targetObj, "0n0DM1T9gz")) {
        $amount += 1;
    }
    // Blazing Charge (s5jwsl7ded): if target is champion with BLAZING_CHARGE_NEXT_TURN, +1 damage
    if(PropertyContains(EffectiveCardType($targetObj), "CHAMPION") && in_array("BLAZING_CHARGE_NEXT_TURN", $targetObj->TurnEffects)) {
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
        // If we're in combat context, record that a kill occurred from combat damage.
        // This is checked by combat handlers to fire OnKillTrigger BEFORE OnHitTrigger.
        $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
        if($combatAttacker !== null) {
            SetCombatKillOccurred();
        }
        AllyDestroyed($player, $target);
    }
}

/**
 * Apply unpreventable damage to a target unit. Bypasses BARRIER_PREVENT_DAMAGE.
 * Otherwise identical to OnDealDamage: amplify effects still apply, death still triggers.
 */
function DealUnpreventableDamage($player, $source, $target, $amount) {
    $targetObj = &GetZoneObject($target);
    // Bubble Mage class bonus: if target has the amplify effect, it takes +1 damage
    if(ObjectHasEffect($targetObj, "0n0DM1T9gz")) {
        $amount += 1;
    }
    // Blazing Charge (s5jwsl7ded): if target is champion with BLAZING_CHARGE_NEXT_TURN, +1 damage
    if(PropertyContains(EffectiveCardType($targetObj), "CHAMPION") && in_array("BLAZING_CHARGE_NEXT_TURN", $targetObj->TurnEffects)) {
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
        // If we're in combat context, record that a kill occurred from combat damage.
        $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
        if($combatAttacker !== null) {
            SetCombatKillOccurred();
        }
        AllyDestroyed($player, $target);
    }
}

?>
