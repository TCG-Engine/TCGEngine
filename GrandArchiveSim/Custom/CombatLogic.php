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
    $intentCards = GetIntentCards($player);
    $available = [];
    foreach($weapons as $mzID) {
        $obj = &GetZoneObject($mzID);
        if($obj->Status == 2 && GetCounterCount($obj, "durability") > 0) {
            // Mechanized Smasher (qsm3n9yvn1): can't be used with attack cards
            if($obj->CardID === "qsm3n9yvn1" && !empty($intentCards)) continue;
            // Mechanized Smasher: requires 4 wind element cards in memory
            if($obj->CardID === "qsm3n9yvn1") {
                $windMem = ZoneSearch("myMemory", cardElements: ["WIND"]);
                if(count($windMem) < 4) continue;
            }
            // Tideholder Claymore (5iqigcom2r): requires enough hand cards to pay additional cost
            if($obj->CardID === "5iqigcom2r") {
                $waterGY = ZoneSearch("myGraveyard", cardElements: ["WATER"]);
                $cost = max(0, 10 - count($waterGY));
                $hand = &GetHand($player);
                if(count($hand) < $cost) continue;
            }
            // Defender's Maul (chnppup4iz): additional cost to attack — pay (2)
            if($obj->CardID === "chnppup4iz") {
                $hand = &GetHand($player);
                if(count($hand) < 2) continue;
            }
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
        if(!$zoneArr[$i]->removed) {
            $results[] = $zone . "-" . $i;
        }
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

    // Bandersnatch, Frumious Foe (4yqL9xtzVi): cleave via activated ability turn effect
    if(!HasNoAbilities($attacker) && ObjectHasEffect($attacker, "4yqL9xtzVi_CLEAVE")) return true;

    // Hemorrhaging Rend (xiazfnm292): [Damage 20+] Cleave — check if intent has this card and champion has 20+ damage
    foreach($intentCards as $intentMZ) {
        $intentObj = &GetZoneObject($intentMZ);
        if($intentObj->CardID === "xiazfnm292") {
            $champMZ = FindChampionMZ($player);
            if($champMZ !== null) {
                $champObj = GetZoneObject($champMZ);
                if($champObj !== null && $champObj->Damage >= 20) return true;
            }
        }
    }

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

    // Ominous Shadow (gveirpdm44): can only attack units your champion dealt combat damage to this turn
    $attacker = &GetZoneObject($attackerMZ);
    if($attacker !== null && $attacker->CardID === "gveirpdm44" && !HasNoAbilities($attacker)) {
        $champDamageTargets = GetChampionCombatDamageTargets($player);
        if(empty($champDamageTargets)) return []; // No valid targets
        // Filter to only units still on opponent field
        $opponents = ZoneSearch("theirField", ["ALLY", "CHAMPION"]);
        $validTargets = [];
        foreach($opponents as $mzID) {
            if(in_array($mzID, $champDamageTargets)) {
                $validTargets[] = $mzID;
            }
        }
        return $validTargets;
    }

    $opponents = ZoneSearch("theirField", ["ALLY", "CHAMPION"]);

    // Siegeable domains are valid attack targets (not units, but can be attacked)
    $oppField = GetZone("theirField");
    foreach($oppField as $oi => $oObj) {
        if(!$oObj->removed && IsSiegeable($oObj)) {
            $siegeMZ = "theirField-" . $oi;
            if(!in_array($siegeMZ, $opponents)) {
                $opponents[] = $siegeMZ;
            }
        }
    }

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

    // Unblockable: Ominous Shadow bypasses intercept and taunt
    $bypassIntercept = false;
    if($attacker !== null && $attacker->CardID === "gveirpdm44" && !HasNoAbilities($attacker)) {
        $bypassIntercept = true;
    }
    // Map of Hidden Passage (2bzajcZZRD): while its effect is active, units with stealth can't be intercepted
    if(!$bypassIntercept && $attacker !== null && HasStealth($attacker)) {
        $attackerGlobalEffectsZone = ($player === $GLOBALS['playerID']) ? "myGlobalEffects" : "theirGlobalEffects";
        if(ZoneContainsCardID($attackerGlobalEffectsZone, "2bzajcZZRD_STEALTH_FREE")) {
            $bypassIntercept = true;
        }
    }

    // Check for Intercept -- units with Intercept must be targeted first
    // Port Smuggler (uCIEMgGjWe): CB attacks can't be intercepted
    if(!$bypassIntercept && $attacker !== null && $attacker->CardID === "uCIEMgGjWe" && IsClassBonusActive($player, CardClasses("uCIEMgGjWe"))) {
        $bypassIntercept = true;
    }
    // Demon's Aim (6g7xgwve1d): champion ignores intercept and taunt this turn
    if(!$bypassIntercept && $attacker !== null && in_array("6g7xgwve1d", $attacker->TurnEffects)) {
        $bypassIntercept = true;
    }
    // Suzaku's Command (5v598k3m1w): target Beast ally can't be intercepted this turn
    if(!$bypassIntercept && $attacker !== null && (in_array("5v598k3m1w", $attacker->TurnEffects) || in_array("5v598k3m1w-SHENJU", $attacker->TurnEffects))) {
        $bypassIntercept = true;
    }
    // Guided Starlight (b0iz7wm7ow): champion's next attack is unblockable unless opponent pays (3)
    if(!$bypassIntercept && $attacker !== null && in_array("b0iz7wm7ow_UNBLOCKABLE", $attacker->TurnEffects)) {
        $bypassIntercept = true;
    }
    // Generic unblockable TurnEffect (e.g. Weiss Knight gaining unblockable from Chessman Command activation)
    if(!$bypassIntercept && $attacker !== null && in_array("UNBLOCKABLE", $attacker->TurnEffects)) {
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
            // INTERCEPT_EOT TurnEffect: dynamically granted intercept until end of turn (e.g. Felicitous Flock tokens)
            if(!HasNoAbilities($obj) && in_array("INTERCEPT_EOT", $obj->TurnEffects) && !in_array("NO_INTERCEPT", $obj->TurnEffects)) {
                if(!in_array($mzID, $interceptTargets)) $interceptTargets[] = $mzID;
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

    // Beguiling Bandit (jyrqgyj9vn): can't be attacked unless attacker pays (1)
    // Filter out BB if attacker has no available reserve sources
    $reservableSources = count(GetZone("myHand"));
    $myFieldCards = GetZone("myField");
    foreach($myFieldCards as $fObj) {
        if(!$fObj->removed && isset($fObj->Status) && $fObj->Status == 2 && HasReservable($fObj)) {
            $reservableSources++;
        }
    }
    if($reservableSources == 0) {
        $opponents = array_values(array_filter($opponents, function($mzID) {
            $obj = GetZoneObject($mzID);
            return $obj === null || $obj->CardID !== "jyrqgyj9vn" || HasNoAbilities($obj);
        }));
        if(empty($opponents)) return $opponents;
    }

    // Hailfinch (3XV4QlQXfy): can't be attacked unless attacker pays (2)
    // Filter out Hailfinch if attacker has fewer than 2 reserve sources
    if($reservableSources < 2) {
        $opponents = array_values(array_filter($opponents, function($mzID) {
            $obj = GetZoneObject($mzID);
            return $obj === null || $obj->CardID !== "3XV4QlQXfy" || HasNoAbilities($obj);
        }));
        if(empty($opponents)) return $opponents;
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
    global $Renewable_Cards, $playerID;
    $intentCards = GetIntentCards($player);
    // Work backwards so index removal doesn't shift remaining cards
    for($i = count($intentCards) - 1; $i >= 0; --$i) {
        $iObj = GetZoneObject($intentCards[$i]);
        $intentCard = $intentCards[$i];
        $zone = "myGraveyard";
        if($iObj !== null && in_array("CURSE_TO_LINEAGE", $iObj->TurnEffects)) {
            // Card was put into champion's lineage by OnHit — banish the physical copy
            $zone = "myBanish";
        } else if($iObj !== null && isset($Renewable_Cards[$iObj->CardID])) {
            // Renewable: goes to material deck instead of graveyard
            $zone = "myMaterial";
        }
        if($player != $playerID) {
            $intentCard = FlipZonePerspective($intentCards[$i]);
            $zone = FlipZonePerspective($zone);
        }
        MZMove($player, $intentCard, $zone);
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

// Command Chessman: player chooses a Chessman ally to perform the attack
$customDQHandlers["CommandChessmanChooseAttacker"] = function($player, $parts, $lastDecision) {
    $chessmanAllies = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["CHESSMAN"]);
    // Filter to awake Chessman allies only
    $awakeChessman = [];
    foreach($chessmanAllies as $mz) {
        $obj = GetZoneObject($mz);
        if($obj !== null && !$obj->removed && $obj->Status == 2) {
            $awakeChessman[] = $mz;
        }
    }
    if(empty($awakeChessman)) {
        SetFlashMessage("No awake Chessman ally to perform the attack.");
        ClearIntent($player);
        return;
    }
    $allyStr = implode("&", $awakeChessman);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $allyStr, 100, "Choose_Chessman_ally_to_attack");
    DecisionQueueController::AddDecision($player, "CUSTOM", "CommandChessmanAttack", 100);
};

// Command Chessman: perform the attack with the chosen ally
$customDQHandlers["CommandChessmanAttack"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    BeginCombatPhase($lastDecision);
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

    // Bring Down the Mighty (ybds1rkgnp): target ally can't attack until beginning of caster's next turn
    if(in_array("CANT_ATTACK_NEXT_TURN", $obj->TurnEffects)) {
        SetFlashMessage("This unit can't attack (Bring Down the Mighty).");
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

    // Suited Trickery (uxhmucm8si): champions must pay (2) for each attack declaration.
    $suitedTrickeryActive = (GlobalEffectCount($turnPlayer, "uxhmucm8si") > 0 || GlobalEffectCount($prOpp, "uxhmucm8si") > 0);
    if($suitedTrickeryActive && PropertyContains($cardType, "CHAMPION")) {
        $hand = GetZone("myHand");
        if(count($hand) < 2) {
            SetFlashMessage("Must pay (2) to attack with a champion (Suited Trickery). Not enough cards in hand.");
            return false;
        }
    }

    // Eminent Lethargy (GGRtLQgaYU): players must pay (2) for each attack declaration.
    $lethActive = (GlobalEffectCount($turnPlayer, "GGRtLQgaYU") > 0 || GlobalEffectCount($prOpp, "GGRtLQgaYU") > 0);
    if($lethActive) {
        $hand = GetZone("myHand");
        if(count($hand) < 2) {
            SetFlashMessage("Must pay (2) to attack (Eminent Lethargy). Not enough cards in hand.");
            return false;
        }
    }

    // Ducal Seal (qFwqqT0XWo): players must pay (3) for each attack declaration.
    $ducalActive = (GlobalEffectCount($turnPlayer, "DUCAL_SEAL_ATTACK_TAX") > 0 || GlobalEffectCount($prOpp, "DUCAL_SEAL_ATTACK_TAX") > 0);
    if($ducalActive) {
        $hand = GetZone("myHand");
        $neededTotal = 3 + ($pleaActive ? 1 : 0) + ($lethActive ? 2 : 0);
        if(count($hand) < $neededTotal) {
            SetFlashMessage("Must pay (3) to attack (Ducal Seal). Not enough cards in hand.");
            return false;
        }
    }

    // Chibi, Battle of Red Cliffs (881gacexpv): players can't declare attacks with allies
    // unless they pay (1) for each attack declaration. Check both players' fields.
    $chibiActive = false;
    if(PropertyContains($cardType, "ALLY")) {
        foreach(array_merge(GetField(1), GetField(2)) as $cObj) {
            if(!$cObj->removed && $cObj->CardID === "881gacexpv" && !HasNoAbilities($cObj)) {
                $chibiActive = true;
                break;
            }
        }
        if($chibiActive) {
            $hand = GetZone("myHand");
            if(count($hand) < 1) {
                SetFlashMessage("Must pay (1) to attack with allies (Chibi, Battle of Red Cliffs). Not enough cards in hand.");
                return false;
            }
        }
    }

    // Torch Marshal (izgiu216l2): additional cost (2) to declare attack with this ally
    $torchMarshalCost = 0;
    if($obj->CardID === "izgiu216l2" && !HasNoAbilities($obj)) {
        $torchMarshalCost = 2;
        $neededHand = $torchMarshalCost + ($chibiActive ? 1 : 0);
        $hand = GetZone("myHand");
        if(count($hand) < $neededHand) {
            SetFlashMessage("Must pay (2) to attack with Torch Marshal. Not enough cards in hand.");
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

    // Suited Trickery (uxhmucm8si): pay (2) reserve for champion attack declaration
    if($suitedTrickeryActive && PropertyContains($cardType, "CHAMPION")) {
        DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "ReserveCard", 90);
        DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "ReserveCard", 90);
    }

    // Chibi, Battle of Red Cliffs (881gacexpv): pay (1) reserve for ally attack declaration
    if($chibiActive) {
        DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "ReserveCard", 90);
    }

    // Eminent Lethargy (GGRtLQgaYU): pay (2) reserve for each attack declaration
    if($lethActive) {
        DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "ReserveCard", 90);
        DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "ReserveCard", 90);
    }

    // Ducal Seal (qFwqqT0XWo): pay (3) reserve for each attack declaration
    if($ducalActive) {
        for($ds = 0; $ds < 3; ++$ds) {
            DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "ReserveCard", 90);
        }
    }

    // Torch Marshal (izgiu216l2): additional cost to declare attack — pay (2)
    if($obj->CardID === "izgiu216l2" && !HasNoAbilities($obj)) {
        DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "ReserveCard", 90);
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
    if(empty($validTargets)) return;
    $targetList = implode("&", $validTargets);

    // Tweedledum, Rattled Dancer (UmZpK4rt2M): opponent chooses the attack target
    $atkObj = GetZoneObject($attackerMZ);
    if($atkObj !== null && $atkObj->CardID === "UmZpK4rt2M" && !HasNoAbilities($atkObj)) {
        $opponent = ($player == 1) ? 2 : 1;
        // Flip targets to opponent perspective so MZCHOOSE highlights the right cards
        $flipped = [];
        foreach($validTargets as $t) $flipped[] = FlipZonePerspective($t);
        $flippedList = implode("&", $flipped);
        DecisionQueueController::AddDecision($opponent, "MZCHOOSE", $flippedList, 100, "Tweedledum:_choose_attack_target");
        DecisionQueueController::AddDecision($opponent, "CUSTOM", "TweedledumTargetChosen|" . $attackerMZ . "|" . $player, 100);
        return;
    }

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

    // Take Aim (vnta6qsesw): next attack this turn gets +2 POWER — consume and apply
    if($obj !== null && in_array("vnta6qsesw", $obj->TurnEffects)) {
        AddTurnEffect($mzID, "vnta6qsesw_POWER");
        $obj->TurnEffects = array_values(array_diff($obj->TurnEffects, ["vnta6qsesw"]));
    }

    // Diana, Cursebreaker (o0qtb31x97): "On Attack: Wake up Diana" granted turn effect
    if($obj !== null && in_array("CURSEBREAKER_ON_ATTACK", $obj->TurnEffects)) {
        WakeupCard($player, $mzID);
    }

    // Mechanical Hare (j3q2svdv3z): 2+ buff counters → "On Attack: Banish up to two target cards in a single graveyard"
    if($obj !== null && $obj->CardID === "j3q2svdv3z" && !HasNoAbilities($obj)) {
        if(GetCounterCount($obj, "buff") >= 2) {
            MechanicalHareOnAttack($player);
        }
    }

    // Calamity Cannon (lwabipl6gt): first Gun attack this turn gets +10 POWER — consume and apply
    if($obj !== null && PropertyContains(EffectiveCardType($obj), "CHAMPION") && in_array("CALAMITY_CANNON_ACTIVE", $obj->TurnEffects)) {
        $weaponMZ = GetCombatWeapon();
        if($weaponMZ !== null) {
            $weaponObj = GetZoneObject($weaponMZ);
            if($weaponObj !== null && PropertyContains(CardSubtypes($weaponObj->CardID), "GUN")) {
                $obj->TurnEffects = array_values(array_diff($obj->TurnEffects, ["CALAMITY_CANNON_ACTIVE"]));
                AddTurnEffect($mzID, "lwabipl6gt_POWER");
            }
        }
    }

    // Jin, Zealous Maverick (5ramr16052): next attack +1 POWER + On Attack wake up — consume and apply
    if($obj !== null && in_array("5ramr16052", $obj->TurnEffects)) {
        AddTurnEffect($mzID, "5ramr16052_POWER");
        $obj->TurnEffects = array_values(array_diff($obj->TurnEffects, ["5ramr16052"]));
        WakeupCard($player, $mzID);
    }

    // Ingress of Sanguine Ire (dfchplzf6m): first attack this turn gets +3 POWER — consume active marker
    if($obj !== null && in_array("INGRESS_ACTIVE", $obj->TurnEffects)) {
        AddTurnEffect($mzID, "dfchplzf6m_POWER");
        $obj->TurnEffects = array_values(array_diff($obj->TurnEffects, ["INGRESS_ACTIVE"]));
    }

    // Guandu, Theater of War (95ynk6lmnf): whenever you declare an attack with an ally
    if($obj !== null && PropertyContains(EffectiveCardType($obj), "ALLY")) {
        $field = &GetField($player);
        for($gi = 0; $gi < count($field); ++$gi) {
            if(!$field[$gi]->removed && $field[$gi]->CardID === "95ynk6lmnf" && !HasNoAbilities($field[$gi])) {
                AddCounters($player, "myField-" . $gi, "battle", 1);
                AddGlobalEffects($player, "GUANDU_ATTACK_TRIGGER");
                $guanduCount = GlobalEffectCount($player, "GUANDU_ATTACK_TRIGGER");
                if($guanduCount == 3) {
                    DrawIntoMemory($player, 1);
                }
                Glimpse($player, 1);
                break;
            }
        }
    }

    // Hulao Gate, Sun's Ascent (snke7lneo4): whenever a unit declares an attack, deal 2 damage to it
    foreach(array_merge(GetField(1), GetField(2)) as $hgObj) {
        if(!$hgObj->removed && $hgObj->CardID === "snke7lneo4" && !HasNoAbilities($hgObj)) {
            DealDamage($player, $hgObj->GetMzID(), $mzID, 2);
            break;
        }
    }

    // Jin, Fate Defiant (zd8l14052j): Inherited Effect —
    // When Jin attacks with a Polearm weapon or Polearm attack card,
    // target Horse or Human ally gets +1 POWER until end of turn.
    if($obj !== null && PropertyContains(EffectiveCardType($obj), "CHAMPION")
            && ChampionHasInLineage($player, "zd8l14052j")) {
        $isPolearmAttack = false;
        $weaponMZ = GetCombatWeapon();
        if($weaponMZ !== null) {
            $weaponObj = GetZoneObject($weaponMZ);
            if($weaponObj !== null && PropertyContains(CardSubtypes($weaponObj->CardID), "POLEARM")) {
                $isPolearmAttack = true;
            }
        }
        if(!$isPolearmAttack) {
            $intentCards = GetIntentCards($player);
            foreach($intentCards as $iMZ) {
                $iObj = GetZoneObject($iMZ);
                if($iObj !== null && PropertyContains(CardSubtypes($iObj->CardID), "POLEARM")) {
                    $isPolearmAttack = true;
                    break;
                }
            }
        }
        if($isPolearmAttack) {
            $horseTargets = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["HORSE"]);
            $humanTargets = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["HUMAN"]);
            $targets = array_values(array_unique(array_merge($horseTargets, $humanTargets)));
            if(!empty($targets)) {
                DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $targets), 1,
                    tooltip:"Choose_Horse_or_Human_ally_for_+1_POWER_(Jin_Fate_Defiant)");
                DecisionQueueController::AddDecision($player, "CUSTOM", "JinFateDefiantBuff", 1);
            }
        }
    }

    // Righteous Retribution (TO9qqKHakv): champion's first attack gets +X POWER from stored prevention
    if($obj !== null && PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
        $retPower = (is_array($obj->Counters) && isset($obj->Counters['retribution_power'])) ? intval($obj->Counters['retribution_power']) : 0;
        if($retPower > 0) {
            AddTurnEffect($mzID, "TO9qqKHakv-" . $retPower);
            unset($obj->Counters['retribution_power']);
        }
    }

    // Servile Possessions mastery (0d93t7bfwc): [Ciel Bonus] champion attack bonus based on omen count
    if($obj !== null && PropertyContains(EffectiveCardType($obj), "CHAMPION")
            && HasServilePossessionsMastery($player) && IsCielBonusActive($player)) {
        $omenCount = GetOmenCount($player);
        if($omenCount >= 1) {
            // Apply power bonus to attack cards in intent
            $intentCards = GetIntentCards($player);
            $bonusPower = 0;
            if($omenCount >= 5) $bonusPower = 3;
            else if($omenCount >= 3) $bonusPower = 2;
            else $bonusPower = 1;
            foreach($intentCards as $iMZ) {
                AddTurnEffect($iMZ, "SERVILE_POSSESSIONS_POWER_" . $bonusPower);
            }
            // Also apply to champion for direct attacks without intent
            AddTurnEffect($mzID, "SERVILE_POSSESSIONS_POWER_" . $bonusPower);
            // 5+ omens: draw a card into memory
            if($omenCount >= 5) {
                DrawIntoMemory($player, 1);
            }
        }
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
function OnHitTrigger($player, $attackerMZ, $isExtraRepeat = false) {
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

    // Mana's Cascade (xywyzv14iv): On Champion Hit — opponent banishes random memory
    $attackerObjHit = GetZoneObject($attackerMZ);
    if($attackerObjHit !== null && in_array("xywyzv14iv_ON_HIT", $attackerObjHit->TurnEffects ?? [])) {
        $hitTarget = DecisionQueueController::GetVariable("CombatTarget");
        if($hitTarget !== null && $hitTarget !== "-" && $hitTarget !== "") {
            $hitObj = GetZoneObject($hitTarget);
            if($hitObj !== null && !$hitObj->removed && PropertyContains(EffectiveCardType($hitObj), "CHAMPION")) {
                $opponent = ($player == 1) ? 2 : 1;
                $oppMemory = GetZone("theirMemory");
                if(!empty($oppMemory)) {
                    $validIndices = [];
                    for($mi = 0; $mi < count($oppMemory); ++$mi) {
                        if(!$oppMemory[$mi]->removed) $validIndices[] = $mi;
                    }
                    if(!empty($validIndices)) {
                        $randIdx = $validIndices[array_rand($validIndices)];
                        MZMove($player, "theirMemory-" . $randIdx, "theirBanish");
                    }
                }
            }
        }
    }

    // Vanitas, Dominus Rex (3vkxrw9462): On Champion Hit — opponent's materializations cost 1 more
    // until beginning of your next turn
    $attackerObjVanitas = GetZoneObject($attackerMZ);
    if($attackerObjVanitas !== null && $attackerObjVanitas->CardID === "3vkxrw9462"
       && !HasNoAbilities($attackerObjVanitas)
       && PropertyContains(EffectiveCardType($attackerObjVanitas), "CHAMPION")) {
        $hitTarget = DecisionQueueController::GetVariable("CombatTarget");
        if($hitTarget !== null && $hitTarget !== "-" && $hitTarget !== "") {
            $hitObj = GetZoneObject($hitTarget);
            if($hitObj !== null && !$hitObj->removed && PropertyContains(EffectiveCardType($hitObj), "CHAMPION")) {
                AddGlobalEffects($player, "3vkxrw9462");
            }
        }
    }

    // Pleiades (rsps1qnzfl): intent cards with rsps1qnzfl-ONHIT TurnEffect
    // summon an Astral Shard token for each one on hit
    foreach($intentCards as $iMZ) {
        $pObj = GetZoneObject($iMZ);
        if($pObj !== null && in_array("rsps1qnzfl-ONHIT", $pObj->TurnEffects ?? [])) {
            MZAddZone($player, "myField", "eP07Xxscuq");
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

    // Relic of Dancing Embers (i8g5013x9j): Whenever a fire element ally you control
    // deals combat damage to a champion while CARDNAME is awake, may sacrifice to deal 3.
    $attackerObj = GetZoneObject($attackerMZ);
    if($attackerObj !== null && PropertyContains(EffectiveCardType($attackerObj), "ALLY")
       && EffectiveCardElement($attackerObj) === "FIRE") {
        $hitTarget = DecisionQueueController::GetVariable("CombatTarget");
        if($hitTarget !== null && $hitTarget !== "-" && $hitTarget !== "") {
            $hitObj = GetZoneObject($hitTarget);
            if($hitObj !== null && !$hitObj->removed && PropertyContains(EffectiveCardType($hitObj), "CHAMPION")) {
                $defenderPlayer = ($player == 1) ? 2 : 1;
                $myField = GetZone("myField");
                foreach($myField as $fi => $fObj) {
                    if(!$fObj->removed && $fObj->CardID === "i8g5013x9j" && !HasNoAbilities($fObj)
                       && $fObj->Status == 2) { // Must be awake
                        DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
                            tooltip:"Sacrifice_Relic_of_Dancing_Embers_to_deal_3_damage?");
                        DecisionQueueController::AddDecision($player, "CUSTOM",
                            "RelicDancingEmbers|" . $fi . "|" . $defenderPlayer, 1);
                        break;
                    }
                }
            }
        }
    }

    // Innocuous Disposer (pd2aigr677): [Class Bonus] On Ally Hit:
    // If the hit ally is a Human, you may remove a preparation counter from your champion.
    // If you do, destroy the hit ally.
    $attackerObj = GetZoneObject($attackerMZ);
    if($attackerObj !== null && $attackerObj->CardID === "pd2aigr677" && !HasNoAbilities($attackerObj)
       && IsClassBonusActive($player, ["ASSASSIN"])) {
        $hitTarget = DecisionQueueController::GetVariable("CombatTarget");
        if($hitTarget !== null && $hitTarget !== "-" && $hitTarget !== "") {
            $hitObj = GetZoneObject($hitTarget);
            if($hitObj !== null && !$hitObj->removed && PropertyContains(EffectiveCardType($hitObj), "ALLY")
               && PropertyContains(EffectiveCardSubtypes($hitObj), "HUMAN")) {
                // Check if champion has prep counters
                $myField = GetZone("myField");
                foreach($myField as $fi => $fObj) {
                    if(!$fObj->removed && PropertyContains(EffectiveCardType($fObj), "CHAMPION")) {
                        if(GetPrepCounterCount($fObj) >= 1) {
                            DecisionQueueController::StoreVariable("InnocuousDisposerTarget", $hitTarget);
                            DecisionQueueController::StoreVariable("InnocuousDisposerChampMZ", "myField-" . $fi);
                            DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
                                tooltip:"Remove_prep_counter_to_destroy_hit_Human_ally?");
                            DecisionQueueController::AddDecision($player, "CUSTOM", "InnocuousDisposerOnHit", 1);
                        }
                        break;
                    }
                }
            }
        }
    }

    // Exploit Vulnerability (hy83sghwfi): granted On Ally Hit — if attacking ally has
    // EXPLOIT_VULNERABILITY_ON_HIT TurnEffect & hit target is an ally, destroy the hit ally.
    $attackerObj = GetZoneObject($attackerMZ);
    if($attackerObj !== null && in_array("EXPLOIT_VULNERABILITY_ON_HIT", $attackerObj->TurnEffects)) {
        $hitTarget = DecisionQueueController::GetVariable("CombatTarget");
        if($hitTarget !== null && $hitTarget !== "-" && $hitTarget !== "") {
            $hitObj = GetZoneObject($hitTarget);
            if($hitObj !== null && !$hitObj->removed && PropertyContains(EffectiveCardType($hitObj), "ALLY")) {
                AllyDestroyed($player, $hitTarget);
            }
        }
    }

    // Shardwing Searchlight (8bRp3n2IAn): Memorite objects with SHARDWING_SEARCHLIGHT_ONHIT
    // On Hit: put a sheen counter on the hit object
    $swAttacker = GetZoneObject($attackerMZ);
    if($swAttacker !== null && !HasNoAbilities($swAttacker)
       && PropertyContains(EffectiveCardSubtypes($swAttacker), "MEMORITE")
       && in_array("SHARDWING_SEARCHLIGHT_ONHIT", CardCurrentEffects($swAttacker))) {
        $hitTarget = DecisionQueueController::GetVariable("CombatTarget");
        if($hitTarget !== null && $hitTarget !== "-" && $hitTarget !== "") {
            $hitObj = GetZoneObject($hitTarget);
            if($hitObj !== null && !$hitObj->removed) {
                AddCounters($player, $hitTarget, "sheen", 1);
            }
        }
    }

    // Shadow's Twin (5vettczb14): [CB] On Hit abilities trigger an additional time
    if(!$isExtraRepeat) {
        $weaponMZST = GetCombatWeapon();
        if($weaponMZST !== null) {
            $stObj = GetZoneObject($weaponMZST);
            if($stObj !== null && $stObj->CardID === "5vettczb14" && !HasNoAbilities($stObj)
               && IsClassBonusActive($player, ["RANGER"])) {
                OnHitTrigger($player, $attackerMZ, true);
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
        // Gun weapons: move loaded bullet from weapon's Subcards into intent
        $weaponObj = &GetZoneObject($lastDecision);
        if($weaponObj !== null && PropertyContains(CardSubtypes($weaponObj->CardID), "GUN") && is_array($weaponObj->Subcards) && !empty($weaponObj->Subcards)) {
            foreach($weaponObj->Subcards as $bulletCardID) {
                MZAddZone($player, "myIntent", $bulletCardID);
                $intentZone = &GetZone("myIntent");
                $newIdx = count($intentZone) - 1;
                $intentZone[$newIdx]->Controller = $player;
            }
            $weaponObj->Subcards = []; // Gun is now unloaded
        }
        // Bow weapons: move loaded arrow from weapon's Subcards into intent
        if($weaponObj !== null && PropertyContains(CardSubtypes($weaponObj->CardID), "BOW") && is_array($weaponObj->Subcards) && !empty($weaponObj->Subcards)) {
            foreach($weaponObj->Subcards as $arrowCardID) {
                MZAddZone($player, "myIntent", $arrowCardID);
                $intentZone = &GetZone("myIntent");
                $newIdx = count($intentZone) - 1;
                $intentZone[$newIdx]->Controller = $player;
            }
            $weaponObj->Subcards = []; // Bow is now unloaded
        }
        // Aetherwing weapons: move loaded Aethercharge cards from weapon's Subcards into intent
        if($weaponObj !== null && PropertyContains(CardSubtypes($weaponObj->CardID), "AETHERWING") && is_array($weaponObj->Subcards) && !empty($weaponObj->Subcards)) {
            foreach($weaponObj->Subcards as $aetherCardID) {
                MZAddZone($player, "myIntent", $aetherCardID);
                $intentZone = &GetZone("myIntent");
                $newIdx = count($intentZone) - 1;
                $intentZone[$newIdx]->Controller = $player;
            }
            $weaponObj->Subcards = [];
        }

        // Tideholder Claymore (5iqigcom2r): additional cost to attack — pay (10) reduced by (1) per water GY card
        if($weaponObj !== null && $weaponObj->CardID === "5iqigcom2r") {
            $waterGY = ZoneSearch("myGraveyard", cardElements: ["WATER"]);
            $cost = max(0, 10 - count($waterGY));
            for($wc = 0; $wc < $cost; ++$wc) {
                DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 97);
            }
        }

        // Defender's Maul (chnppup4iz): additional cost to attack — pay (2)
        if($weaponObj !== null && $weaponObj->CardID === "chnppup4iz") {
            for($dmc = 0; $dmc < 2; ++$dmc) {
                DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 97);
            }
        }

        // Bulwark Sword (8kmoi0a5uh): additional cost to attack — pay (2)
        if($weaponObj !== null && $weaponObj->CardID === "8kmoi0a5uh") {
            for($bsc = 0; $bsc < 2; ++$bsc) {
                DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 97);
            }
        }

        // Mechanized Smasher (qsm3n9yvn1): additional cost to attack — reveal 4 wind cards from memory
        if($weaponObj !== null && $weaponObj->CardID === "qsm3n9yvn1") {
            $windMem = ZoneSearch("myMemory", cardElements: ["WIND"]);
            if(count($windMem) >= 4) {
                for($wmi = 0; $wmi < 4; ++$wmi) {
                    $remaining = ZoneSearch("myMemory", cardElements: ["WIND"]);
                    if(!empty($remaining)) {
                        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $remaining), 97, "Reveal_wind_card_from_memory_(" . ($wmi+1) . "/4)");
                        DecisionQueueController::AddDecision($player, "CUSTOM", "MechanizedSmasherReveal", 97);
                    }
                }
            }
        }
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

    // Guided Starlight (b0iz7wm7ow): consume unblockable after attack declaration
    $gsObj = &GetZoneObject($attackerMZ);
    if($gsObj !== null) {
        $gsKey = array_search("b0iz7wm7ow_UNBLOCKABLE", $gsObj->TurnEffects);
        if($gsKey !== false) {
            array_splice($gsObj->TurnEffects, $gsKey, 1);
        }
    }

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

    // Track that the champion was attacked this turn (for Servant's Obligation)
    $wasAttackedObj = GetZoneObject($lastDecision);
    if($wasAttackedObj !== null && PropertyContains(EffectiveCardType($wasAttackedObj), "CHAMPION")) {
        if(!in_array("WAS_ATTACKED", $wasAttackedObj->TurnEffects)) {
            AddTurnEffect($lastDecision, "WAS_ATTACKED");
        }
    }

    // Atmos Shield (80yu75k0hl): whenever another neos unit you control is targeted
    // for an attack, you may change the target to Atmos Shield
    $asTargetObj = GetZoneObject($lastDecision);
    if($asTargetObj !== null && EffectiveCardElement($asTargetObj) === "NEOS"
       && $asTargetObj->CardID !== "80yu75k0hl") {
        $defPlayer = ($player == 1) ? 2 : 1;
        $defField = GetField($defPlayer);
        global $playerID;
        $prefix = ($defPlayer == $playerID) ? "myField" : "theirField";
        foreach($defField as $dfIdx => $dfObj) {
            if(!$dfObj->removed && $dfObj->CardID === "80yu75k0hl" && !HasNoAbilities($dfObj)) {
                $shieldMZ = $prefix . "-" . $dfIdx;
                DecisionQueueController::AddDecision($defPlayer, "YESNO", "-", 98,
                    tooltip:"Redirect_attack_to_Atmos_Shield?");
                DecisionQueueController::AddDecision($defPlayer, "CUSTOM",
                    "AtmosShieldRedirect|" . $shieldMZ, 98);
                break;
            }
        }
    }

    // Beguiling Bandit (jyrqgyj9vn): attacker must pay (1) to attack it
    $bbTargetObj = GetZoneObject($lastDecision);
    if($bbTargetObj !== null && $bbTargetObj->CardID === "jyrqgyj9vn" && !HasNoAbilities($bbTargetObj)) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 97);
    }

    // Hailfinch (3XV4QlQXfy): attacker must pay (2) to attack it
    $hfTargetObj = GetZoneObject($lastDecision);
    if($hfTargetObj !== null && $hfTargetObj->CardID === "3XV4QlQXfy" && !HasNoAbilities($hfTargetObj)) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 97);
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 97);
    }

    // Grant Opportunity window before damage step (turn player gets priority first)
    $turnPlayer = GetTurnPlayer();
    GrantOpportunityWindow($turnPlayer, "CombatDealDamage", $player);
};

/**
 * Atmos Shield (80yu75k0hl): redirect attack to Atmos Shield.
 * $parts[0] = shield mzID (in $playerID perspective).
 */
$customDQHandlers["AtmosShieldRedirect"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $shieldMZ = $parts[0];
    $shieldObj = GetZoneObject($shieldMZ);
    if($shieldObj === null || $shieldObj->removed) return;
    DecisionQueueController::StoreVariable("CombatTarget", $shieldMZ);
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
                // Track champion combat damage for Ominous Shadow
                if(PropertyContains(EffectiveCardType($attacker), "CHAMPION")) TrackChampionCombatDamage($attackerPlayer, $targetMZ);
                if(ConsumeCombatKill()) OnKillTrigger($attackerPlayer, $attackerMZ);
                OnHitTrigger($attackerPlayer, $attackerMZ);
                DecisionQueueController::AddDecision($player, "CUSTOM", "CombatRetaliationOpportunity", 150);
            }
        } else {
            // No critical — deal normal damage
            ResetCombatKill();
            DealDamage($attackerPlayer, $attackerMZ, $targetMZ, $totalPower);
            // Track champion combat damage for Ominous Shadow
            if(PropertyContains(EffectiveCardType($attacker), "CHAMPION")) TrackChampionCombatDamage($attackerPlayer, $targetMZ);
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
        DecisionQueueController::AddDecision($defenderPlayer, "CUSTOM", "CombatCleanup|" . $attackerPlayer, 200, dontSkipOnPass:1);
        return;
    }

    // Flip attacker/target mzIDs to the defender's perspective up-front.
    // CombatAttacker/CombatTarget are stored from the attacker's perspective, but this
    // handler runs with $playerID = defenderPlayer (swapped by ResolveOpportunityWindow),
    // so GetZoneObject() must use the flipped names to reach the correct objects.
    $defenderMZ_fromDefender = FlipZonePerspective($targetMZ);
    $attackerMZ_fromDefender = FlipZonePerspective($attackerMZ);

    $oObj = GetZoneObject($defenderMZ_fromDefender);
    if(IsSiegeable($oObj)) {
        // Siegeables can't retaliate, skip retaliation and go to cleanup
        DecisionQueueController::AddDecision($defenderPlayer, "CUSTOM", "CombatCleanup|" . $attackerPlayer, 200, dontSkipOnPass:1);
        return;
    }

    // Check if any intent card has NO_RETALIATE (e.g. Seeking Shot [Class Bonus])
    $attackerIntentCards = GetIntentCards($attackerPlayer);
    foreach($attackerIntentCards as $iMZ) {
        $iObj = GetZoneObject($iMZ);
        if($iObj !== null && in_array("NO_RETALIATE", $iObj->TurnEffects)) {
            DecisionQueueController::AddDecision($defenderPlayer, "CUSTOM", "CombatCleanup|" . $attackerPlayer, 200, dontSkipOnPass:1);
            return;
        }
    }

    // Xiao Qiao, Cinderkeeper (3hgldrogit): [Class Bonus] attacks can't be retaliated
    $attackerObj = GetZoneObject($attackerMZ_fromDefender);
    if($attackerObj !== null && $attackerObj->CardID === "3hgldrogit" && !HasNoAbilities($attackerObj)
        && IsClassBonusActive($attackerPlayer, ["ASSASSIN", "TAMER"])) {
        DecisionQueueController::AddDecision($defenderPlayer, "CUSTOM", "CombatCleanup|" . $attackerPlayer, 200, dontSkipOnPass:1);
        return;
    }

    // Sun Jian, Wolvesbane (b23a85z88j): attacks can't be retaliated while attacking a Beast unit
    if($attackerObj !== null && $attackerObj->CardID === "b23a85z88j" && !HasNoAbilities($attackerObj)) {
        $targetObj = GetZoneObject($defenderMZ_fromDefender);
        if($targetObj !== null && PropertyContains(CardSubtypes($targetObj->CardID), "BEAST")) {
            DecisionQueueController::AddDecision($defenderPlayer, "CUSTOM", "CombatCleanup|" . $attackerPlayer, 200, dontSkipOnPass:1);
            return;
        }
    }

    // Blazing Bowman (qry41lw9n0): attacks can't be retaliated
    if($attackerObj !== null && $attackerObj->CardID === "qry41lw9n0" && !HasNoAbilities($attackerObj)) {
        DecisionQueueController::AddDecision($defenderPlayer, "CUSTOM", "CombatCleanup|" . $attackerPlayer, 200, dontSkipOnPass:1);
        return;
    }

    // (perspective flip already computed above)

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
        // Sinister Mindreaver (jozihslnhz): Ambush — may retaliate while not defending
        if($fieldObj->CardID === "jozihslnhz" && !HasNoAbilities($fieldObj)) {
            $retaliatorOptions[] = $mzID;
        }
        // Guan Yu, Prime Exemplar (0oyxjld8jh): Ambush
        if($fieldObj->CardID === "0oyxjld8jh" && !HasNoAbilities($fieldObj)) {
            if(!in_array($mzID, $retaliatorOptions)) $retaliatorOptions[] = $mzID;
        }
        // Gloamspire Mantle (fooz13xfpk): Umbra element Phantasia allies have Ambush
        // (may retaliate while not defending)
        if(!HasNoAbilities($fieldObj)
            && PropertyContains(EffectiveCardType($fieldObj), "PHANTASIA")
            && EffectiveCardElement($fieldObj) === "UMBRA") {
            // Check if the defender controls Gloamspire Mantle on their field
            $hasMantleOnField = false;
            foreach($defenderField as $mi => $mantleObj) {
                if(!$mantleObj->removed && $mantleObj->CardID === "fooz13xfpk" && !HasNoAbilities($mantleObj)) {
                    $hasMantleOnField = true;
                    break;
                }
            }
            if($hasMantleOnField && !in_array($mzID, $retaliatorOptions)) {
                $retaliatorOptions[] = $mzID;
            }
        }
        // Changban, Heroic Impasse (kmuuqzfvg8): allies with buff counters have Ambush
        if(PropertyContains(EffectiveCardType($fieldObj), "ALLY") && GetCounterCount($fieldObj, "buff") > 0) {
            $hasChangbanOnField = false;
            foreach($defenderField as $mi => $chObj) {
                if(!$chObj->removed && $chObj->CardID === "kmuuqzfvg8" && !HasNoAbilities($chObj)) {
                    $hasChangbanOnField = true;
                    break;
                }
            }
            if($hasChangbanOnField && !in_array($mzID, $retaliatorOptions)) {
                $retaliatorOptions[] = $mzID;
            }
        }
    }
    // Innocuous Disposer (pd2aigr677): attacks can't be retaliated by Human allies
    if($attackerObj !== null && $attackerObj->CardID === "pd2aigr677" && !HasNoAbilities($attackerObj)) {
        $retaliatorOptions = array_filter($retaliatorOptions, function($mzID) {
            $obj = GetZoneObject($mzID);
            if($obj === null) return true;
            if(PropertyContains(EffectiveCardType($obj), "ALLY") && PropertyContains(EffectiveCardSubtypes($obj), "HUMAN")) {
                return false; // filter out Human allies
            }
            return true;
        });
        $retaliatorOptions = array_values($retaliatorOptions);
    }
    if(empty($retaliatorOptions)) {
        DecisionQueueController::AddDecision($defenderPlayer, "CUSTOM", "CombatCleanup|" . $attackerPlayer, 200, dontSkipOnPass:1);
        return;
    }
    $retaliatorOptionStr = implode("&", $retaliatorOptions);

    // Retaliation step: let the defending player choose whether to retaliate
    DecisionQueueController::AddDecision($defenderPlayer, "MZMAYCHOOSE", $retaliatorOptionStr, 100, "Retaliate?");
    DecisionQueueController::AddDecision($defenderPlayer, "CUSTOM", "Retaliate|" . $attackerMZ_fromDefender . "|" . $defenderMZ_fromDefender, 100);

    // Cleanup on defender's queue after retaliation (block 200)
    DecisionQueueController::AddDecision($defenderPlayer, "CUSTOM", "CombatCleanup|" . $attackerPlayer, 200, dontSkipOnPass:1);
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

    // Cleave includes siegeable domains in the set of defending objects
    $oppField = GetZone("theirField");
    foreach($oppField as $oi => $oObj) {
        if(!$oObj->removed && IsSiegeable($oObj)) {
            $siegeMZ = "theirField-" . $oi;
            if(!in_array($siegeMZ, $opponents)) {
                $opponents[] = $siegeMZ;
            }
        }
    }

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
        DecisionQueueController::AddDecision($defenderPlayer, "CUSTOM", "CombatCleanup|" . $attackerPlayer, 200, dontSkipOnPass:1);
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
    DecisionQueueController::AddDecision($defenderPlayer, "CUSTOM", "CombatCleanup|" . $attackerPlayer, 200, dontSkipOnPass:1);
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
        // Rest the retaliator after dealing retaliation damage, unless it has Steadfast
        if(!HasSteadfast($defender)) {
            OnRestCard($player, $lastDecision);
        }
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
 * Handler: Innocuous Disposer (pd2aigr677) [CB] On Ally Hit: remove prep counter to destroy Human ally.
 */
$customDQHandlers["InnocuousDisposerOnHit"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $champMZ = DecisionQueueController::GetVariable("InnocuousDisposerChampMZ");
    if($champMZ === null) return;
    $champObj = GetZoneObject($champMZ);
    if($champObj === null || $champObj->removed) return;
    if(GetPrepCounterCount($champObj) < 1) return;
    RemoveCounters($player, $champMZ, "preparation", 1);
    $hitTarget = DecisionQueueController::GetVariable("InnocuousDisposerTarget");
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

    // Per rules, End of Combat Step ordering:
    //   1. Attacking and defending objects are removed from combat.
    //   2. Any cards in the intent are placed into the graveyard.
    // So clear combat state variables first, then clear the intent.
    DecisionQueueController::ClearVariable("CombatAttacker");
    DecisionQueueController::ClearVariable("CombatTarget");
    DecisionQueueController::ClearVariable("CombatAttackerPlayer");
    DecisionQueueController::ClearVariable("CombatIsCleave");
    DecisionQueueController::ClearVariable("CombatWeapon");

    global $playerID;
    $savedPlayerID = $playerID;
    $playerID = $attackerPlayer;
    ClearIntent($attackerPlayer);
    $playerID = $savedPlayerID;
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
        // Track champion combat damage for Ominous Shadow
        $critAttacker = GetZoneObject($attackerMZ);
        if($critAttacker !== null && PropertyContains(EffectiveCardType($critAttacker), "CHAMPION")) TrackChampionCombatDamage($attackerPlayer, $targetMZ);
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
    // Track champion combat damage for Ominous Shadow
    $finishAttacker = GetZoneObject($attackerMZ);
    if($finishAttacker !== null && PropertyContains(EffectiveCardType($finishAttacker), "CHAMPION")) TrackChampionCombatDamage($attackerPlayer, $targetMZ);
    if(ConsumeCombatKill()) OnKillTrigger($attackerPlayer, $attackerMZ);
    OnHitTrigger($attackerPlayer, $attackerMZ);
};

// --- damage resolution ---------------------------------------------------------

/**
 * Mechanical Hare (j3q2svdv3z): On Attack with 2+ buff counters — banish up to 2 cards
 * from a single graveyard. Choose which graveyard, then pick up to 2 cards.
 */
function MechanicalHareOnAttack($player) {
    $myGY = ZoneSearch("myGraveyard");
    $theirGY = ZoneSearch("theirGraveyard");
    if(empty($myGY) && empty($theirGY)) return;
    if(!empty($myGY) && !empty($theirGY)) {
        DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Target_your_own_graveyard?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "MechanicalHareGYChoice", 1);
    } else if(!empty($myGY)) {
        MechanicalHareBanishStart($player, "myGraveyard");
    } else {
        MechanicalHareBanishStart($player, "theirGraveyard");
    }
}

function MechanicalHareBanishStart($player, $gyRef) {
    $gyCards = ZoneSearch($gyRef);
    if(empty($gyCards)) return;
    $gyStr = implode("&", $gyCards);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $gyStr, 1, tooltip:"Banish_a_card?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "MechanicalHareBanish1|$gyRef", 1);
}

$customDQHandlers["MechanicalHareGYChoice"] = function($player, $parts, $lastDecision) {
    $gyRef = ($lastDecision === "YES") ? "myGraveyard" : "theirGraveyard";
    MechanicalHareBanishStart($player, $gyRef);
};

$customDQHandlers["MechanicalHareBanish1"] = function($player, $parts, $lastDecision) {
    $gyRef = $parts[0];
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myBanish");
    // Second pick
    $gyCards = ZoneSearch($gyRef);
    if(empty($gyCards)) return;
    $gyStr = implode("&", $gyCards);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $gyStr, 1, tooltip:"Banish_another_card?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "MechanicalHareBanish2", 1);
};

$customDQHandlers["MechanicalHareBanish2"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myBanish");
};

/**
 * Apply damage to a target unit. If damage >= HP, destroy it.
 * After applying damage, trigger any DealDamage abilities on the target card.
 */
function OnDealDamage($player, $source, $target, $amount) {
    $targetObj = &GetZoneObject($target);

    // Siegeable domains: damage removes durability counters instead of adding Damage.
    // Destroyed when durability reaches 0. On Hit still triggers via the combat flow.
    if(IsSiegeable($targetObj)) {
        if($amount > 0) {
            $targetController = $targetObj->Controller ?? $player;
            $currentDurability = GetCounterCount($targetObj, "durability");
            $toRemove = min($amount, $currentDurability);
            if($toRemove > 0) {
                RemoveCounters($targetController, $target, "durability", $toRemove);
            }
            if(GetCounterCount($targetObj, "durability") <= 0) {
                AllyDestroyed($targetController, $target);
            }
        }
        return;
    }

    // Potion Infusion: Frostbite — next water damage to this unit +4
    $sourceObj = GetZoneObject($source);
    if($sourceObj !== null && CardElement($sourceObj->CardID) === "WATER") {
        if(in_array("FROSTBITE_WATER_VULN", $targetObj->TurnEffects)) {
            $amount += 4;
            $targetObj->TurnEffects = array_values(array_filter($targetObj->TurnEffects, fn($e) => $e !== "FROSTBITE_WATER_VULN"));
        }
    }

    // Enfeebled Dagger (idpdon8f0h): [CB] source unit deals that much damage minus 3
    $sourceObj2 = GetZoneObject($source);
    if($sourceObj2 !== null && in_array("ENFEEBLED_DAGGER_REDUCE", $sourceObj2->TurnEffects)) {
        $amount = max(0, $amount - 3);
        if($amount <= 0) return;
    }

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

    // Blazing Lunge (wewvlfkfp7): [CB] banished 2 fire cards in intent — combat damage is unpreventable
    if($isCombatContext) {
        $intentCards = GetIntentCards($player);
        foreach($intentCards as $intentMZ) {
            $intentObj = GetZoneObject($intentMZ);
            if($intentObj !== null && $intentObj->CardID === "wewvlfkfp7"
               && in_array("wewvlfkfp7_UNPREVENTABLE", $intentObj->TurnEffects ?? [])) {
                DealUnpreventableDamage($player, $source, $target, $amount);
                return;
            }
        }
    }

    // Piercing Aetherfuel (vo5q7letxz): champion's next Aetherwing attack is unpreventable
    if($isCombatContext) {
        $sourceObj = GetZoneObject($source);
        if($sourceObj !== null && in_array("PIERCING_AETHERFUEL", $sourceObj->TurnEffects ?? [])) {
            // Verify this is an Aetherwing attack (Aethercharge cards in intent)
            if(CountAetherchargesInIntent($player) > 0) {
                // Consume the effect (next attack only)
                $sourceObj->TurnEffects = array_values(array_diff($sourceObj->TurnEffects, ["PIERCING_AETHERFUEL"]));
                DealUnpreventableDamage($player, $source, $target, $amount);
                return;
            }
        }
    }

    // Suzaku's Command Shenju (5v598k3m1w-SHENJU): combat damage is unpreventable
    if($isCombatContext) {
        $sourceObj = GetZoneObject($source);
        if($sourceObj !== null && in_array("5v598k3m1w-SHENJU", $sourceObj->TurnEffects)) {
            DealUnpreventableDamage($player, $source, $target, $amount);
            return;
        }
    }

    // Ominous Shadow (gveirpdm44): prevent 3 of any damage dealt to it
    if($amount > 0 && $targetObj->CardID === "gveirpdm44" && !HasNoAbilities($targetObj)) {
        $amount -= 3;
        if($amount <= 0) return;
    }

    // Froglet Footman (fbvt9rdhkj): prevent 1 damage while it has a buff counter
    if($amount > 0 && $targetObj->CardID === "fbvt9rdhkj" && !HasNoAbilities($targetObj)) {
        if(GetCounterCount($targetObj, "buff") > 0) {
            $amount -= 1;
            if($amount <= 0) return;
        }
    }

    // Queen Piece (m69XrVkaVh): [Alice Bonus] prevent all damage while you control a Chessman Pawn ally
    if($amount > 0 && $targetObj->CardID === "m69XrVkaVh" && !HasNoAbilities($targetObj)) {
        $targetController = $targetObj->Controller ?? $player;
        if(IsAliceBonusActive($targetController)) {
            global $playerID;
            $pawnZone = $targetController == $playerID ? "myField" : "theirField";
            $pawnAllies = ZoneSearch($pawnZone, ["ALLY"], cardSubtypes: ["PAWN", "CHESSMAN"]);
            // Filter to only Chessman Pawn allies (both subtypes)
            $hasPawn = false;
            foreach($pawnAllies as $pMZ) {
                $pObj = GetZoneObject($pMZ);
                if($pObj !== null && PropertyContains(EffectiveCardSubtypes($pObj), "CHESSMAN")
                    && PropertyContains(EffectiveCardSubtypes($pObj), "PAWN")
                    && $pObj->GetMzID() !== $target) {
                    $hasPawn = true;
                    break;
                }
            }
            if($hasPawn) return; // All damage prevented
        }
    }

    // Shangxiang, Fierce Princess (s2tzwv1uw3): if imbued, prevent 2 damage from non-norm sources
    if($amount > 0 && $targetObj->CardID === "s2tzwv1uw3" && !HasNoAbilities($targetObj)
       && in_array("IMBUED", $targetObj->TurnEffects)) {
        $sourceObj = GetZoneObject($source);
        if($sourceObj !== null && EffectiveCardElement($sourceObj) !== "NORM") {
            $amount -= 2;
            if($amount <= 0) return;
        }
    }

    // Conduit of Seasons (nm77bnz4cc): prevent 2 damage while SC faces West
    if($amount > 0 && $targetObj->CardID === "nm77bnz4cc" && !HasNoAbilities($targetObj)) {
        $targetController = $targetObj->Controller ?? $player;
        if(GetShiftingCurrents($targetController) === "WEST") {
            $amount -= 2;
            if($amount <= 0) return;
        }
    }

    // Floodward Sergeant (64xGWbG9Xf): prevent damage once per turn
    if($amount > 0 && $targetObj->CardID === "64xGWbG9Xf" && !HasNoAbilities($targetObj)
        && !in_array("64xGWbG9Xf", $targetObj->TurnEffects)) {
        $targetObj->TurnEffects[] = "64xGWbG9Xf";
        return; // All damage prevented
    }

    // Barrier Servant: prevent next damage if tagged with BARRIER_PREVENT_DAMAGE (one-time)
    if(in_array("BARRIER_PREVENT_DAMAGE", $targetObj->TurnEffects)) {
        $targetObj->TurnEffects = array_values(array_filter($targetObj->TurnEffects, fn($e) => $e !== "BARRIER_PREVENT_DAMAGE"));
        return; // Damage fully prevented
    }

    // Veiled Gambit (hxdfyA0eP1): prevent next 4 damage until EOT
    foreach($targetObj->TurnEffects as $te) {
        if(strpos($te, "VEILED_GAMBIT_") === 0) {
            $preventAmount = intval(substr($te, strlen("VEILED_GAMBIT_")));
            $prevented = min($preventAmount, $amount);
            $amount -= $prevented;
            $remaining = $preventAmount - $prevented;
            $targetObj->TurnEffects = array_values(array_filter($targetObj->TurnEffects, fn($e) => $e !== $te));
            if($remaining > 0) {
                $targetObj->TurnEffects[] = "VEILED_GAMBIT_" . $remaining;
            }
            if($amount <= 0) return;
            break;
        }
    }

    // Evasive Maneuvers (1n3gygojwk): prevent next 2 damage to target unit this turn
    foreach($targetObj->TurnEffects as $te) {
        if(strpos($te, "EVASIVE_MANEUVERS_") === 0) {
            $preventAmount = intval(substr($te, strlen("EVASIVE_MANEUVERS_")));
            $prevented = min($preventAmount, $amount);
            $amount -= $prevented;
            $remaining = $preventAmount - $prevented;
            $targetObj->TurnEffects = array_values(array_filter($targetObj->TurnEffects, fn($e) => $e !== $te));
            if($remaining > 0) {
                $targetObj->TurnEffects[] = "EVASIVE_MANEUVERS_" . $remaining;
            }
            if($amount <= 0) return;
            break;
        }
    }

    // Alice Lineage Release (daip7s9ztd): prevent next 3 damage to each awake Chessman ally this turn
    foreach($targetObj->TurnEffects as $te) {
        if(strpos($te, "ALICE_LR_PREVENT_") === 0) {
            $preventAmount = intval(substr($te, strlen("ALICE_LR_PREVENT_")));
            $prevented = min($preventAmount, $amount);
            $amount -= $prevented;
            $remaining = $preventAmount - $prevented;
            $targetObj->TurnEffects = array_values(array_filter($targetObj->TurnEffects, fn($e) => $e !== $te));
            if($remaining > 0) {
                $targetObj->TurnEffects[] = "ALICE_LR_PREVENT_" . $remaining;
            }
            if($amount <= 0) return;
            break;
        }
    }

    // Golden Bishop (s4oelWMRJE): prevent next 2 damage to target Chessman unit this turn
    if(in_array("GOLDEN_BISHOP_PREVENT_2", $targetObj->TurnEffects)) {
        $prevented = min(2, $amount);
        $amount -= $prevented;
        $targetObj->TurnEffects = array_values(array_filter($targetObj->TurnEffects, fn($e) => $e !== "GOLDEN_BISHOP_PREVENT_2"));
        if($amount <= 0) return;
    }

    // Pang Tong, Young Phoenix (0mz09ojy0t): prevent all but 3 damage when hand count == memory count
    if($targetObj->CardID === "0mz09ojy0t" && !HasNoAbilities($targetObj) && $amount > 3) {
        $ptController = $targetObj->Controller ?? $player;
        $handCount = count(array_filter(GetZone($ptController == 1 ? "myHand" : "theirHand"), fn($c) => !$c->removed));
        $memCount = count(array_filter(GetZone($ptController == 1 ? "myMemory" : "theirMemory"), fn($c) => !$c->removed));
        if($handCount === $memCount) {
            $amount = 3;
        }
    }

    // Fleeting Guard (2la6uk1qvl): prevent X damage where X = caster's ephemeral object count + 1
    foreach($targetObj->TurnEffects as $te) {
        if(strpos($te, "FLEETING_GUARD_") === 0) {
            $caster = intval(substr($te, strlen("FLEETING_GUARD_")));
            $prevention = CountEphemeralObjects($caster) + 1;
            $amount -= $prevention;
            if($amount <= 0) return;
            break;
        }
    }

    // Nascent Barrier (6bc3ogf0o8): prevent up to N damage to champion (encoded as NASCENT_BARRIER_N)
    if(PropertyContains(EffectiveCardType($targetObj), "CHAMPION")) {
        // Water Barrier (xWJND68I8X): prevent all but 1 of next damage to champion
        if(in_array("WATER_BARRIER", $targetObj->TurnEffects) && $amount > 1) {
            $amount = 1;
            $targetObj->TurnEffects = array_values(array_filter($targetObj->TurnEffects, fn($e) => $e !== "WATER_BARRIER"));
        }
        // Return to the Depths [Ciel Bonus] (fNlJ0MaxiI): prevent all but 1; if 3+ prevented, may banish GY with omen
        if(in_array("RETURN_TO_DEPTHS_CIEL", $targetObj->TurnEffects) && $amount > 1) {
            $prevented = $amount - 1;
            $amount = 1;
            $targetObj->TurnEffects = array_values(array_filter($targetObj->TurnEffects, fn($e) => $e !== "RETURN_TO_DEPTHS_CIEL"));
            if($prevented >= 3) {
                $targetPlayer = $targetObj->Controller;
                global $playerID;
                $gravZone = $targetPlayer == $playerID ? "myGraveyard" : "theirGraveyard";
                $gy = GetZone($gravZone);
                $gyTargets = [];
                for($gi = 0; $gi < count($gy); ++$gi) {
                    if(!$gy[$gi]->removed) {
                        $gyTargets[] = $gravZone . "-" . $gi;
                    }
                }
                if(!empty($gyTargets)) {
                    $gyStr = implode("&", $gyTargets);
                    DecisionQueueController::AddDecision($targetPlayer, "MZMAYCHOOSE", $gyStr, 1, tooltip:"Banish_from_GY_with_omen_counter?_(Return_to_the_Depths)");
                    DecisionQueueController::AddDecision($targetPlayer, "CUSTOM", "ReturnToDepthsOmen", 1);
                }
            }
        }
        foreach($targetObj->TurnEffects as $te) {
            if(strpos($te, "NASCENT_BARRIER_") === 0) {
                $preventAmount = intval(substr($te, strlen("NASCENT_BARRIER_")));
                $prevented = min($preventAmount, $amount);
                $amount -= $prevented;
                $targetObj->TurnEffects = array_values(array_filter($targetObj->TurnEffects, fn($e) => $e !== $te));
                break;
            }
        }
        // Calming Breeze (XgJ72Ot13P): if 3 or less damage, prevent it entirely
        if($amount > 0 && $amount <= 3 && in_array("CALMING_BREEZE", $targetObj->TurnEffects)) {
            $amount = 0;
        }
        // Righteous Retribution (TO9qqKHakv): prevent up to 5 of next damage, store prevented for power boost
        foreach($targetObj->TurnEffects as $rrIdx => $rrEffect) {
            if(strpos($rrEffect, "RIGHTEOUS_RETRIBUTION_") === 0) {
                $rrBudget = intval(substr($rrEffect, strlen("RIGHTEOUS_RETRIBUTION_")));
                $rrPrevented = min($rrBudget, $amount);
                $amount -= $rrPrevented;
                unset($targetObj->TurnEffects[$rrIdx]);
                $targetObj->TurnEffects = array_values($targetObj->TurnEffects);
                if($rrPrevented > 0) {
                    if(!is_array($targetObj->Counters)) $targetObj->Counters = [];
                    $targetObj->Counters['retribution_power'] = $rrPrevented;
                }
                break;
            }
        }
        if($amount <= 0) return;
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

    // Linked Shield damage prevention (Unit Link items)
    if($amount > 0) {
        $linkedCards = GetLinkedCards($targetObj);
        foreach($linkedCards as $linkedObj) {
            if(HasNoAbilities($linkedObj)) continue;
            switch($linkedObj->CardID) {
                case "y208kkz07n": // Vaporjet Shield: [CB] prevent 1 damage to linked unit
                    if(IsClassBonusActive($linkedObj->Controller, ["GUARDIAN"])) {
                        $amount -= 1;
                    }
                    break;
                case "zadf9q1vk8": // Prototype Shield: [CB] prevent 3 while linked unit is attacking
                    if(IsClassBonusActive($linkedObj->Controller, ["GUARDIAN"])) {
                        $combatAttackerCheck = DecisionQueueController::GetVariable("CombatAttacker");
                        if($combatAttackerCheck !== null && $combatAttackerCheck === $target) {
                            $amount -= 3;
                        }
                    }
                    break;
                case "7lr2jiu66i": // Forged Scalemail: may banish to prevent 2 and draw a card
                    {
                        $prevented = min(2, $amount);
                        $amount -= $prevented;
                        $smController = $linkedObj->Controller;
                        $linkedMZ = $linkedObj->GetMzID();
                        MZMove($smController, $linkedMZ, ($smController == $playerID) ? "myBanish" : "theirBanish");
                        DecisionQueueController::CleanupRemovedCards();
                        Draw($smController, 1);
                    }
                    break;
                case "l2ipxnctse": // Protective Helm: prevent 1 damage from distant source
                    {
                        $phSourceObj = GetZoneObject($source);
                        if($phSourceObj !== null && IsDistant($phSourceObj)) {
                            $amount -= 1;
                        }
                    }
                    break;
            }
        }
        if($amount <= 0) return;
    }

    // Enthralling Visage (ycwz9gv4vm): prevent 2 damage, banish target graveyard card
    if($amount > 0) {
        foreach($targetObj->TurnEffects as $idx => $effect) {
            if(strpos($effect, "ycwz_") === 0) {
                $evParts = explode("_", substr($effect, 5));
                $evOwner = intval($evParts[0]);
                $evCardID = $evParts[1];
                $budget = 2;
                $prevented = min($budget, $amount);
                $amount -= $prevented;
                unset($targetObj->TurnEffects[$idx]);
                $targetObj->TurnEffects = array_values($targetObj->TurnEffects);
                if($prevented > 0) {
                    global $playerID;
                    $gravZone = ($evOwner == $playerID) ? "myGraveyard" : "theirGraveyard";
                    $banishDest = ($evOwner == $playerID) ? "myBanish" : "theirBanish";
                    $gravCards = GetZone($gravZone);
                    for($gi = count($gravCards) - 1; $gi >= 0; --$gi) {
                        if(!$gravCards[$gi]->removed && $gravCards[$gi]->CardID === $evCardID) {
                            MZMove($evOwner, $gravZone . "-" . $gi, $banishDest);
                            break;
                        }
                    }
                }
                break;
            }
        }
        if($amount <= 0) return;
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

    // Crystallized Anthem (XfAJlQt9hH): prevent up to 2 damage to each unit this turn,
    // and when damage is prevented this way, put 2 sheen on caster's Fractured Memories.
    if($amount > 0) {
        foreach($targetObj->TurnEffects as $idx => $effect) {
            if(strpos($effect, "CRYSTALLIZED_ANTHEM_") === 0) {
                $budget = intval(substr($effect, strlen("CRYSTALLIZED_ANTHEM_")));
                $prevented = min($budget, $amount);
                $amount -= $prevented;
                $remaining = $budget - $prevented;
                if($remaining <= 0) {
                    unset($targetObj->TurnEffects[$idx]);
                    $targetObj->TurnEffects = array_values($targetObj->TurnEffects);
                } else {
                    $targetObj->TurnEffects[$idx] = "CRYSTALLIZED_ANTHEM_" . $remaining;
                }
                if($prevented > 0) {
                    // Put 2 sheen on the caster's Fractured Memories
                    $casterPlayer = $targetObj->Controller ?? $player;
                    AddSheenToMastery($casterPlayer, 2);
                }
                break;
            }
        }
        if($amount <= 0) return;
    }

    // PREVENT_NONCOMBAT_N: prevent up to N non-combat damage this turn (Dodge Roll)
    $isCombat = DecisionQueueController::GetVariable("CombatAttacker") !== null;
    if(!$isCombat && $amount > 0) {
        foreach($targetObj->TurnEffects as $idx => $effect) {
            if(strpos($effect, "PREVENT_NONCOMBAT_") === 0) {
                $budget = intval(substr($effect, 18));
                $prevented = min($budget, $amount);
                $amount -= $prevented;
                $remaining = $budget - $prevented;
                if($remaining <= 0) {
                    unset($targetObj->TurnEffects[$idx]);
                    $targetObj->TurnEffects = array_values($targetObj->TurnEffects);
                } else {
                    $targetObj->TurnEffects[$idx] = "PREVENT_NONCOMBAT_" . $remaining;
                }
                break;
            }
        }
        if($amount <= 0) return;
    }

    // PREVENT_COMBAT_N: prevent up to N combat damage this turn (Deflecting Edge)
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
        // SURGE_PROTECTOR_SHIELD: prevent all champion damage and sacrifice the tagged shield
        $controller = $targetObj->Controller;
        $shieldField = GetField($controller);
        global $playerID;
        $shieldZone = ($controller == $playerID) ? "myField" : "theirField";
        foreach($shieldField as $si => $sObj) {
            if($sObj->removed) continue;
            if(in_array("SURGE_PROTECTOR_SHIELD", $sObj->TurnEffects)) {
                $amount = 0;
                MZMove($controller, $shieldZone . "-" . $si, ($controller == $playerID) ? "myGraveyard" : "theirGraveyard");
                DecisionQueueController::CleanupRemovedCards();
                return;
            }
        }
        // PREVENT_CHAMP_ENLIGHTEN: prevent all of next damage to champion; gain enlighten = amount prevented (Spellshield: Arcane)
        if(in_array("PREVENT_CHAMP_ENLIGHTEN", $targetObj->TurnEffects)) {
            $prevented = $amount;
            $amount = 0;
            $targetObj->TurnEffects = array_values(array_filter($targetObj->TurnEffects, fn($e) => $e !== "PREVENT_CHAMP_ENLIGHTEN"));
            AddCounters($targetObj->Controller, $target, "enlighten", $prevented);
            return;
        }
        // PREVENT_CHAMP_MILL: prevent all of next damage to champion; mill X where X = amount prevented (Hailstorm Guard)
        if(in_array("PREVENT_CHAMP_MILL", $targetObj->TurnEffects)) {
            $prevented = $amount;
            $amount = 0;
            $targetObj->TurnEffects = array_values(array_filter($targetObj->TurnEffects, fn($e) => $e !== "PREVENT_CHAMP_MILL"));
            if($prevented > 0) {
                MillCards($targetObj->Controller, "myDeck", "myGraveyard", $prevented);
            }
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
        // PREVENT_CHAMP_TERA_PRESERVE: prevent all damage to champion; reveal X from deck, put into material deck preserved
        // (Spellshield: Tera)
        if(in_array("PREVENT_CHAMP_TERA_PRESERVE", $targetObj->TurnEffects)) {
            $prevented = $amount;
            $amount = 0;
            $targetObj->TurnEffects = array_values(array_filter($targetObj->TurnEffects, fn($e) => $e !== "PREVENT_CHAMP_TERA_PRESERVE"));
            if($prevented > 0) {
                $controller = $targetObj->Controller;
                global $playerID;
                $deckRef = $controller == $playerID ? "myDeck" : "theirDeck";
                $matRef = $controller == $playerID ? "myMaterial" : "theirMaterial";
                $deck = GetZone($deckRef);
                $revealCount = min($prevented, count($deck));
                $revealIDs = [];
                for($ri = 0; $ri < $revealCount; ++$ri) {
                    if(!$deck[$ri]->removed) {
                        $revealIDs[] = $deck[$ri]->CardID;
                    }
                }
                if(!empty($revealIDs)) {
                    SetFlashMessage('REVEAL:' . implode('|', $revealIDs));
                }
                // Move top $revealCount cards from deck to material deck (preserved)
                global $Preserve_Cards;
                for($ri = 0; $ri < $revealCount; ++$ri) {
                    $deckZone = GetZone($deckRef);
                    if(empty($deckZone)) break;
                    $cardID = $deckZone[0]->CardID;
                    MZMove($controller, $deckRef . "-0", $matRef);
                    if(!isset($Preserve_Cards)) $Preserve_Cards = [];
                    $Preserve_Cards[$cardID] = true;
                }
            }
            return;
        }
        // PREVENT_BY_NAME_*: prevent damage from any source whose CardID matches the named card
        // (Crimson Prescience)
        foreach($targetObj->TurnEffects as $idx => $effect) {
            if(strpos($effect, "PREVENT_BY_NAME_") === 0) {
                $namedCardID = substr($effect, 16);
                $sourceObj = GetZoneObject($source);
                if($sourceObj !== null && $sourceObj->CardID === $namedCardID) {
                    $amount = 0;
                    return;
                }
            }
        }
        // PREVENT_CHAMP_N: prevent up to N damage to champion this turn (Veiling Breeze)
        foreach($targetObj->TurnEffects as $idx => $effect) {
            if(strpos($effect, "PREVENT_CHAMP_") === 0 && strpos($effect, "PREVENT_CHAMP_ENLIGHTEN") !== 0 && strpos($effect, "PREVENT_CHAMP_WIND_BUFF") !== 0 && strpos($effect, "PREVENT_CHAMP_ASTRA_GLIMPSE") !== 0 && strpos($effect, "PREVENT_CHAMP_TERA_PRESERVE") !== 0) {
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
    // Prima Materia (vt9y597fqr): next astra source damage to units +3
    $sourceObj = GetZoneObject($source);
    if($sourceObj !== null && $amount > 0) {
        $isUnit = PropertyContains(EffectiveCardType($targetObj), "ALLY") || PropertyContains(EffectiveCardType($targetObj), "CHAMPION");
        if($isUnit && EffectiveCardElement($sourceObj) === "ASTRA") {
            $srcCtrl = $sourceObj->Controller ?? $player;
            if(GlobalEffectCount($srcCtrl, "PRIMA_MATERIA_BOOST") > 0) {
                $amount += 3;
                RemoveGlobalEffect($srcCtrl, "PRIMA_MATERIA_BOOST");
            }
        }
    }
    // Weaken Resistance (bb3oeup7oq): next Spell source damage to this unit +LV
    if($amount > 0) {
        $srcObjWR = GetZoneObject($source);
        if($srcObjWR !== null && PropertyContains(CardSubtypes($srcObjWR->CardID), "SPELL")) {
            foreach($targetObj->TurnEffects as $wrIdx => $wrEffect) {
                if(strpos($wrEffect, "WEAKEN_RES_") === 0) {
                    $wrBonus = intval(substr($wrEffect, 11));
                    $amount += $wrBonus;
                    unset($targetObj->TurnEffects[$wrIdx]);
                    $targetObj->TurnEffects = array_values($targetObj->TurnEffects);
                    break;
                }
            }
        }
    }

    // Sovereign Sanctuary (w6OqqsfEso): prevent 2 damage to any unit you control
    if($amount > 0) {
        $isUnitCheck = PropertyContains(EffectiveCardType($targetObj), "ALLY") || PropertyContains(EffectiveCardType($targetObj), "CHAMPION");
        if($isUnitCheck) {
            $targetController = $targetObj->Controller ?? $player;
            global $playerID;
            $ctrlFieldZone = ($targetController == $playerID) ? "myField" : "theirField";
            $ctrlField = GetZone($ctrlFieldZone);
            foreach($ctrlField as $ssObj) {
                if(!$ssObj->removed && $ssObj->CardID === "w6OqqsfEso" && !HasNoAbilities($ssObj)) {
                    $amount = max(0, $amount - 2);
                    break;
                }
            }
            if($amount <= 0) return;
        }
    }

    $targetObj->Damage += $amount;

    // Foster tracking: mark that this unit received damage and remove fostered state
    if(!in_array("DAMAGED_SINCE_LAST_TURN", $targetObj->TurnEffects)) {
        $targetObj->TurnEffects[] = "DAMAGED_SINCE_LAST_TURN";
    }
    $targetObj->TurnEffects = array_values(array_filter($targetObj->TurnEffects, fn($e) => $e !== "FOSTERED"));

    // Trigger per-card DealDamage abilities on the target card
    global $dealDamageAbilities;
    if(isset($dealDamageAbilities) && isset($dealDamageAbilities[$targetObj->CardID . ":0"])) {
        $dealDamageAbilities[$targetObj->CardID . ":0"]($player);
    }

    // Everflame Staff (nrvth9vyz1): whenever a fire Spell source you control deals damage,
    // put a refinement counter on Everflame Staff
    $sourceObj2 = GetZoneObject($source);
    if($sourceObj2 !== null && CardElement($sourceObj2->CardID) === "FIRE"
        && PropertyContains(CardSubtypes($sourceObj2->CardID), "SPELL")) {
        $sourceController = $sourceObj2->Controller ?? $player;
        global $playerID;
        $staffZone = $sourceController == $playerID ? "myField" : "theirField";
        $staffField = GetZone($staffZone);
        foreach($staffField as $si => $sObj) {
            if(!$sObj->removed && $sObj->CardID === "nrvth9vyz1" && !HasNoAbilities($sObj)) {
                AddCounters($sourceController, $staffZone . "-" . $si, "refinement", 1);
            }
        }
    }

    // Magebane Lash (oh300z2sns): Nico Bonus — whenever Nico takes non-combat damage, recover 2
    if(!$isCombat && $amount > 0
            && PropertyContains(EffectiveCardType($targetObj), "CHAMPION")
            && $targetObj->CardID === "5bbae3z4py") {
        MagebaneNicoBonusCheck($targetObj->Controller ?? $player);
    }

    // Aegis of Dawn (abipl6gt7l): whenever champion dealt 4+ damage, summon Automaton Drone
    if($amount >= 4 && PropertyContains(EffectiveCardType($targetObj), "CHAMPION")) {
        AegisOfDawnTrigger($targetObj->Controller ?? $player);
    }

    // Jin, Undying Resolve (c4yrrtv7o1): Immortality — can't die except during Jin's controller's end phase
    if($amount > 0 && PropertyContains(EffectiveCardType($targetObj), "CHAMPION")) {
        $champController = $targetObj->Controller ?? $player;
        $isJinUndying = ($targetObj->CardID === "c4yrrtv7o1")
            || ChampionHasInLineage($champController, "c4yrrtv7o1");
        if($isJinUndying) {
            $isJinEndPhase = (GetCurrentPhase() === "END" && GetTurnPlayer() == $champController);
            if(!$isJinEndPhase) {
                $hpNow = ObjectCurrentHP($targetObj);
                if($targetObj->Damage >= $hpNow) {
                    $targetObj->Damage = $hpNow - 1; // Immortality: prevent lethal damage outside Jin's end phase
                }
            }
        }
    }

    $currentHp = ObjectCurrentHP($targetObj);
    if($targetObj->Damage >= $currentHp) {
        // Wrathful Slime (wjaq7t8vbf): immortality while it has 6+ buff counters
        if($targetObj->CardID === "wjaq7t8vbf" && !HasNoAbilities($targetObj)
           && GetCounterCount($targetObj, "buff") >= 6) {
            $targetObj->Damage = $currentHp - 1;
        } else {
            // If we're in combat context, record that a kill occurred from combat damage.
            // This is checked by combat handlers to fire OnKillTrigger BEFORE OnHitTrigger.
            $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
            if($combatAttacker !== null) {
                SetCombatKillOccurred();
                // Store the killed card's ID so OnKill abilities can reference it
                DecisionQueueController::StoreVariable("CombatKilledCardID", $targetObj->CardID);
            }
            AllyDestroyed($player, $target);
        }
    }
}

/**
 * Apply unpreventable damage to a target unit. Bypasses BARRIER_PREVENT_DAMAGE.
 * Otherwise identical to OnDealDamage: amplify effects still apply, death still triggers.
 */
function DealUnpreventableDamage($player, $source, $target, $amount) {
    $targetObj = &GetZoneObject($target);

    // Siegeable domains: same handling as OnDealDamage (unpreventable doesn't change the mechanic)
    if(IsSiegeable($targetObj)) {
        if($amount > 0) {
            $targetController = $targetObj->Controller ?? $player;
            $currentDurability = GetCounterCount($targetObj, "durability");
            $toRemove = min($amount, $currentDurability);
            if($toRemove > 0) {
                RemoveCounters($targetController, $target, "durability", $toRemove);
            }
            if(GetCounterCount($targetObj, "durability") <= 0) {
                AllyDestroyed($targetController, $target);
            }
        }
        return;
    }

    // Enfeebled Dagger (idpdon8f0h): [CB] source unit deals that much damage minus 3
    $sourceObjED = GetZoneObject($source);
    if($sourceObjED !== null && in_array("ENFEEBLED_DAGGER_REDUCE", $sourceObjED->TurnEffects)) {
        $amount = max(0, $amount - 3);
        if($amount <= 0) return;
    }

    // Bubble Mage class bonus: if target has the amplify effect, it takes +1 damage
    if(ObjectHasEffect($targetObj, "0n0DM1T9gz")) {
        $amount += 1;
    }
    // Blazing Charge (s5jwsl7ded): if target is champion with BLAZING_CHARGE_NEXT_TURN, +1 damage
    if(PropertyContains(EffectiveCardType($targetObj), "CHAMPION") && in_array("BLAZING_CHARGE_NEXT_TURN", $targetObj->TurnEffects)) {
        $amount += 1;
    }
    // Prima Materia (vt9y597fqr): next astra source damage to units +3
    $sourceObj2 = GetZoneObject($source);
    if($sourceObj2 !== null && $amount > 0) {
        $isUnit = PropertyContains(EffectiveCardType($targetObj), "ALLY") || PropertyContains(EffectiveCardType($targetObj), "CHAMPION");
        if($isUnit && EffectiveCardElement($sourceObj2) === "ASTRA") {
            $srcCtrl = $sourceObj2->Controller ?? $player;
            if(GlobalEffectCount($srcCtrl, "PRIMA_MATERIA_BOOST") > 0) {
                $amount += 3;
                RemoveGlobalEffect($srcCtrl, "PRIMA_MATERIA_BOOST");
            }
        }
    }
    // Weaken Resistance (bb3oeup7oq): next Spell source damage to this unit +LV
    if($amount > 0) {
        $srcObjWR2 = GetZoneObject($source);
        if($srcObjWR2 !== null && PropertyContains(CardSubtypes($srcObjWR2->CardID), "SPELL")) {
            foreach($targetObj->TurnEffects as $wrIdx => $wrEffect) {
                if(strpos($wrEffect, "WEAKEN_RES_") === 0) {
                    $wrBonus = intval(substr($wrEffect, 11));
                    $amount += $wrBonus;
                    unset($targetObj->TurnEffects[$wrIdx]);
                    $targetObj->TurnEffects = array_values($targetObj->TurnEffects);
                    break;
                }
            }
        }
    }
    $targetObj->Damage += $amount;

    // Foster tracking: mark that this unit received damage and remove fostered state
    if(!in_array("DAMAGED_SINCE_LAST_TURN", $targetObj->TurnEffects)) {
        $targetObj->TurnEffects[] = "DAMAGED_SINCE_LAST_TURN";
    }
    $targetObj->TurnEffects = array_values(array_filter($targetObj->TurnEffects, fn($e) => $e !== "FOSTERED"));

    // Trigger per-card DealDamage abilities on the target card
    global $dealDamageAbilities;
    if(isset($dealDamageAbilities) && isset($dealDamageAbilities[$targetObj->CardID . ":0"])) {
        $dealDamageAbilities[$targetObj->CardID . ":0"]($player);
    }

    // Magebane Lash (oh300z2sns): Nico Bonus — whenever Nico takes non-combat damage, recover 2
    $isNonCombat = DecisionQueueController::GetVariable("CombatAttacker") === null;
    if($isNonCombat && $amount > 0
            && PropertyContains(EffectiveCardType($targetObj), "CHAMPION")
            && $targetObj->CardID === "5bbae3z4py") {
        MagebaneNicoBonusCheck($targetObj->Controller ?? $player);
    }

    // Aegis of Dawn (abipl6gt7l): whenever champion dealt 4+ damage, summon Automaton Drone
    if($amount >= 4 && PropertyContains(EffectiveCardType($targetObj), "CHAMPION")) {
        AegisOfDawnTrigger($targetObj->Controller ?? $player);
    }

    // Jin, Undying Resolve (c4yrrtv7o1): Immortality — can't die except during Jin's controller's end phase
    if($amount > 0 && PropertyContains(EffectiveCardType($targetObj), "CHAMPION")) {
        $champController = $targetObj->Controller ?? $player;
        $isJinUndying = ($targetObj->CardID === "c4yrrtv7o1")
            || ChampionHasInLineage($champController, "c4yrrtv7o1");
        if($isJinUndying) {
            $isJinEndPhase = (GetCurrentPhase() === "END" && GetTurnPlayer() == $champController);
            if(!$isJinEndPhase) {
                $hpNow = ObjectCurrentHP($targetObj);
                if($targetObj->Damage >= $hpNow) {
                    $targetObj->Damage = $hpNow - 1; // Immortality: prevent lethal damage outside Jin's end phase
                }
            }
        }
    }

    $currentHp = ObjectCurrentHP($targetObj);
    if($targetObj->Damage >= $currentHp) {
        // Wrathful Slime (wjaq7t8vbf): immortality while it has 6+ buff counters
        if($targetObj->CardID === "wjaq7t8vbf" && !HasNoAbilities($targetObj)
           && GetCounterCount($targetObj, "buff") >= 6) {
            $targetObj->Damage = $currentHp - 1;
        } else {
            // If we're in combat context, record that a kill occurred from combat damage.
            $combatAttacker = DecisionQueueController::GetVariable("CombatAttacker");
            if($combatAttacker !== null) {
                SetCombatKillOccurred();
                DecisionQueueController::StoreVariable("CombatKilledCardID", $targetObj->CardID);
            }
            AllyDestroyed($player, $target);
        }
    }
}

// ============================================================================
// Ominous Shadow — Champion Combat Damage Target Tracking
// ============================================================================

/**
 * Track that a champion dealt combat damage to a target unit this turn.
 * Stored as TurnEffects on the target: "CHAMP_DMG_BY_P1" or "CHAMP_DMG_BY_P2".
 * Called from CombatDealDamage after combat damage is dealt by a champion.
 * @param int    $player   The player whose champion dealt damage.
 * @param string $targetMZ The mzID of the target that was damaged.
 */
function TrackChampionCombatDamage($player, $targetMZ) {
    $targetObj = &GetZoneObject($targetMZ);
    if($targetObj === null || $targetObj->removed) return;
    $tag = "CHAMP_DMG_BY_P" . $player;
    if(!in_array($tag, $targetObj->TurnEffects)) {
        $targetObj->TurnEffects[] = $tag;
    }
}

/**
 * Get all unit mzIDs on the opponent's field that this player's champion dealt combat damage to this turn.
 * Used by Ominous Shadow's attack restriction.
 * @param int $player The attacking player (Ominous Shadow's controller).
 * @return array mzIDs (in "theirField-N" form) of valid targets.
 */
function GetChampionCombatDamageTargets($player) {
    $tag = "CHAMP_DMG_BY_P" . $player;
    $opponents = ZoneSearch("theirField", ["ALLY", "CHAMPION"]);
    $targets = [];
    foreach($opponents as $mzID) {
        $obj = GetZoneObject($mzID);
        if($obj !== null && in_array($tag, $obj->TurnEffects)) {
            $targets[] = $mzID;
        }
    }
    return $targets;
}

// Mechanized Smasher: reveal a wind card from memory as additional attack cost
$customDQHandlers["MechanizedSmasherReveal"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "") {
        DoRevealCard($player, $lastDecision);
    }
};

// Slice and Dice (3jg01o26b4): execute the additional attack
// Runs at block 250 (after first combat's retaliation/cleanup flow completes on player 1's queue).
// Removes the stale CombatCleanup from the opponent's queue, adds a +3 POWER copy of Slice and Dice
// to the attacker's intent, restores combat state, and queues target selection for the second attack.
$customDQHandlers["SliceAndDiceNewAttack"] = function($player, $parts, $lastDecision) {
    $attackerMZ = $parts[0];
    $attackerObj = GetZoneObject($attackerMZ);
    if($attackerObj === null || (isset($attackerObj->removed) && $attackerObj->removed)) return;

    // Remove the stale CombatCleanup from the opponent's queue to prevent double-cleanup
    $opponent = ($player == 1) ? 2 : 1;
    $opponentQueue = &GetDecisionQueue($opponent);
    for($qi = 0; $qi < count($opponentQueue); $qi++) {
        if(strpos($opponentQueue[$qi]->Param, "CombatCleanup") === 0) {
            array_splice($opponentQueue, $qi, 1);
            break;
        }
    }

    // Add the Slice and Dice copy to intent WITHOUT PREPARED, WITH +3 POWER
    MZAddZone($player, "myIntent", "3jg01o26b4");
    $intentArr = GetZone("myIntent");
    $newIntentIdx = count($intentArr) - 1;
    AddTurnEffect("myIntent-" . $newIntentIdx, "3jg01o26b4-COPY_POWER");

    // Restore combat variables for the new attack
    DecisionQueueController::StoreVariable("CombatAttacker", $attackerMZ);
    DecisionQueueController::StoreVariable("CombatAttackerPlayer", strval($player));
    DecisionQueueController::StoreVariable("CombatIsCleave", "0");
    DecisionQueueController::StoreVariable("CombatWeapon", "-");

    // Queue target selection for the second attack
    ChooseAttackTarget($player, $attackerMZ);
};

?>
