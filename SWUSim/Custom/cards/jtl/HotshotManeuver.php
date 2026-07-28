<?php
// JTL_174
// Cost 1 - Hotshot Maneuver - [Aggression]
// Text: Choose a friendly unit. For each of its "On Attack" abilities, deal 2 damage to a different enemy unit. Then, attack with the chosen unit.

// ── JTL_174 Hotshot Maneuver — friendly unit chosen; count its On Attack abilities (the same set
// CollectCombatStep1Triggers fires: printed windows + upgrade-granted, each gated on its activation
// condition), deal 2 to that many DIFFERENT enemy units, then attack with the chosen unit. ─────────────
$customDQHandlers["JTL_174#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID, $onAttackAbilities;
    $playerID = intval($player);
    $u = GetZoneObject($lastDecision);
    if (SWUObjGone($u)) return;
    $uid = intval($u->UniqueID ?? 0);
    // Count On Attack abilities that would ACTUALLY fire: printed windows for this CardID + upgrade-granted
    // on-attack, each gated on its activation condition (_SWUOnAttackAbilityActive) — a structurally-present
    // but condition-unmet ability (Coordinate inactive, non-Force host) does NOT count.
    $n = 0;
    foreach (array_keys($onAttackAbilities) as $k) {
        if (preg_match('/^' . preg_quote($u->CardID, '/') . ':\d+$/', $k)
            && _SWUOnAttackAbilityActive($u->CardID, $u, intval($player))) $n++;
    }
    foreach (GetUpgradesOnUnit($u) as $up) {
        $ucid = $up->CardID ?? '';
        if (isset($onAttackAbilities[$ucid . ':0'])
            && _SWUOnAttackAbilityActive($ucid, $u, intval($player))) $n++;
    }
    $enemies = array_values(array_merge(
        ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)));
    $k = min($n, count($enemies));
    if ($k <= 0) {
        // No On Attack abilities (or no enemy units) → skip damage, just attack.
        $attMz = SWUFindMzByUID($uid);
        if ($attMz !== null) BeginSWUAttack(intval($player), $attMz);
        return;
    }
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "{$k}|{$k}|" . implode("&", $enemies), 1,
        tooltip: "Deal_2_to_{$k}_different_enemy_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_174#1|{$uid}", 1);
};

$customDQHandlers["JTL_174#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $uid = intval($parts[0] ?? 0);
    // Snapshot chosen enemy UIDs, deal 2 to each (index-shift safe).
    $targetUids = [];
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        foreach (explode('&', $lastDecision) as $mz) {
            $mz = trim($mz);
            if ($mz === '' || $mz === '-') continue;
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targetUids[] = intval($o->UniqueID ?? 0);
        }
    }
    foreach ($targetUids as $tuid) {
        $mz = SWUFindMzByUID($tuid);
        if ($mz !== null) SWUDealDamageToUnit($mz, 2, intval($player));
    }
    // Then attack with the chosen unit.
    $attMz = SWUFindMzByUID($uid);
    if ($attMz !== null) BeginSWUAttack(intval($player), $attMz);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_174:0"] = function($player, $mzID = '') {
// Hotshot Maneuver — "Choose a friendly unit. For each of its 'On Attack'
                          // abilities, deal 2 damage to a different enemy unit. Then, attack with the
                          // chosen unit."
            global $playerID;
            $playerID = intval($player);
            $friendly = array_values(array_merge(
                ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)));
            if (empty($friendly)) return;
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_unit", "JTL_174#0");
            return;
};
