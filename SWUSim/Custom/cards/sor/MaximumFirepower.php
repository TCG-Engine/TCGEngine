<?php
// SOR_234
// Cost 4 - Maximum Firepower - [Villainy]
// Text: A friendly Imperial unit deals damage equal to its power to a unit. / Then, another friendly Imperial unit deals damage equal to its power to the same unit.

// SOR_234 Maximum Firepower — step 1: first Imperial ($lastDecision) chosen; pick the target.
$customDQHandlers["SOR_234#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $imp1Mz = $lastDecision;
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    // The damage is the chosen Imperial's CURRENT power. "Choose the target unit" gave the player no way
    // to know whether this kills what they are aiming at — the whole point of the card.
    $imp1      = GetZoneObject($imp1Mz);
    $imp1Name  = SWUObjGone($imp1) ? 'that_unit' : str_replace(' ', '_', SWUObjectTitle($imp1));
    $imp1Power = SWUObjGone($imp1) ? 0 : intval(ObjectCurrentPower($imp1));
    SWUQueueChooseTarget(intval($player), $targets,
        "Choose_the_unit_for_{$imp1Name}_to_deal_{$imp1Power}_damage_to",
        "SOR_234#1|" . $imp1Mz, 0);
};

// Step 2: imp1 deals its power to the target ($lastDecision); then pick a SECOND Imperial.
$customDQHandlers["SOR_234#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $imp1Mz   = $parts[0] ?? '';
    $imp1     = GetZoneObject($imp1Mz);
    $target   = GetZoneObject($lastDecision);
    if (SWUObjGone($imp1) || SWUObjGone($target)) return;
    $imp1UID   = intval($imp1->UniqueID ?? -1);
    $targetUID = intval($target->UniqueID ?? -1);
    SWUDealDamageToUnit($lastDecision, intval(ObjectCurrentPower($imp1)), intval($player));
    // Another friendly Imperial (≠ imp1, re-resolved after possible index shifts).
    $imp2 = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->UniqueID ?? -2) === $imp1UID) continue;        // exclude the first Imperial
        if (HasTrait($o->CardID, 'Imperial')) $imp2[] = $mz;
    }
    if (empty($imp2)) return;                                          // no second Imperial → done
    // Target already defeated by the first hit → the second clause has no legal effect: skip the
    // chooser entirely (pointless-prompt doctrine; the damage step would fizzle anyway).
    if (SWUFindMzByUID($targetUID) === null) return;
    // Step 2 picks the SECOND dealer, so the amount depends on which one is picked — state the rule and
    // name the target that is about to be hit again (it has already taken the first Imperial's power).
    $tName = str_replace(' ', '_', SWUObjectTitle($target));
    SWUQueueChooseTarget(intval($player), $imp2,
        "Choose_another_Imperial_unit_to_deal_ITS_power_to_{$tName}",
        "SOR_234#2|" . $targetUID, 0);
};

// Step 3: the second Imperial ($lastDecision) deals its power to the same target (by UID).
$customDQHandlers["SOR_234#2"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $imp2 = GetZoneObject($lastDecision);
    if (SWUObjGone($imp2)) return;
    $targetMz = SWUFindMzByUID(intval($parts[0] ?? -1));              // same unit — may already be defeated
    if ($targetMz === null) return;
    SWUDealDamageToUnit($targetMz, intval(ObjectCurrentPower($imp2)), intval($player));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_234:0"] = function($player, $mzID = '') {
// Maximum Firepower — two friendly Imperial units each deal their power to the same unit.
            global $playerID;
            $playerID = intval($player);
            $imperials = [];
            foreach (SWUFriendlyUnits() as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if (HasTrait($o->CardID, 'Imperial')) $imperials[] = $mz;
            }
            if (empty($imperials)) return;
            SWUQueueChooseTarget(intval($player), $imperials, "Choose_first_Imperial_unit_to_deal_damage", "SOR_234#0");
            return;
};
