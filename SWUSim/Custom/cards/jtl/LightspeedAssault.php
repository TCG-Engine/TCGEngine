<?php
// JTL_127
// Cost 2 - Lightspeed Assault - [Command]
// Text: Defeat a friendly space unit and deal damage equal to its power to an enemy space unit. If you do, deal indirect damage equal to the enemy unit's power to its controller.

// ── JTL_127 Lightspeed Assault — friendly space unit chosen (to defeat); capture its power, then choose
// the enemy space unit to receive that damage. ───────────────────────────────────────────────────────
$customDQHandlers["JTL_127#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $f = GetZoneObject($lastDecision);
    if (SWUObjGone($f)) return;
    $fuid = intval($f->UniqueID ?? 0);
    $fpow = intval(ObjectCurrentPower($f));
    $enemy = ZoneSearch('theirSpaceArena', AnyUnitFilter);
    if (empty($enemy)) {
        // "Defeat a friendly space unit AND deal damage … to an enemy space unit." The defeat is NOT
        // conditional on an enemy existing — the ability resolves as much of itself as it can, so with
        // no enemy space unit the chosen friendly still dies and simply nothing else happens (no damage,
        // hence no "if you do" indirect either).
        SWUDefeatUnit(intval($player), $lastDecision);
        return;
    }
    SWUQueueChooseTarget(intval($player), $enemy,
        "Deal_{$fpow}_damage_to_an_enemy_space_unit", "JTL_127#1|{$fuid}|{$fpow}");
};

// Defeat the friendly, deal its power to the chosen enemy, then "if you do" deal indirect = the enemy
// unit's power to its controller.
$customDQHandlers["JTL_127#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $fuid = intval($parts[0] ?? 0);
    $fpow = intval($parts[1] ?? 0);
    $e = GetZoneObject($lastDecision);
    if (SWUObjGone($e)) return;
    $euid        = intval($e->UniqueID ?? 0);
    $epow        = intval(ObjectCurrentPower($e));           // captured before any defeat (power ≠ HP)
    // Controller is the answer; the fallback only covers an unset field and must still name the RIGHT
    // seat above two seats, so derive it from the mzID rather than OtherPlayer()/GetOpponent().
    $econtroller = intval($e->Controller ?? 0);
    if ($econtroller <= 0) $econtroller = SWUMzOwner((string)$lastDecision, intval($player));
    $fmz = SWUFindMzByUID($fuid);
    if ($fmz === null) return;                               // friendly already gone → can't complete
    SWUDefeatUnit(intval($player), $fmz);                    // defeat the friendly space unit
    $emz = SWUFindMzByUID($euid);                            // re-resolve enemy after the cleanup
    if ($emz !== null && $fpow > 0) SWUDealDamageToUnit($emz, $fpow, intval($player));
    if ($epow > 0) SWUDealIndirectDamage(intval($player), $epow, $econtroller);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_127:0"] = function($player, $mzID = '') {
// Lightspeed Assault — "Defeat a friendly space unit and deal damage equal to
                          // its power to an enemy space unit. If you do, deal indirect damage equal to the
                          // enemy unit's power to its controller."
                          // Only a FRIENDLY space unit is required: the defeat is the first half of the
                          // sentence and is not conditional on an enemy being available (JTL_127#0 defeats
                          // it and stops when the enemy arena is empty). With no friendly space unit the
                          // event is still playable but does nothing at all.
            global $playerID;
            $playerID = intval($player);
            $friendly = ZoneSearch('mySpaceArena', AnyUnitFilter);
            if (empty($friendly)) return; // nothing to defeat → the whole ability does nothing
            SWUQueueChooseTarget(intval($player), $friendly, "Defeat_a_friendly_space_unit", "JTL_127#0");
            return;
};
