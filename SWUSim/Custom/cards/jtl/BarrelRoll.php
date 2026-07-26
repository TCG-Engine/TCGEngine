<?php
// JTL_228
// Cost 1 - Barrel Roll - [Cunning]
// Text: Attack with a space unit. After completing this attack, you may exhaust a space unit.

// ── JTL_228 Barrel Roll — chosen space unit attacks; after, may exhaust a space unit. ─────────────────
$customDQHandlers["JTL_228#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    BeginSWUAttack(intval($player), $lastDecision);
    // After completing the attack: may exhaust a space unit (EXHAUST_UNIT validates the chosen target).
    $spaceUnits = array_values(array_merge(
        ZoneSearch('mySpaceArena',    AnyUnitFilter),
        ZoneSearch('theirSpaceArena', AnyUnitFilter)
    ));
    if (!empty($spaceUnits)) {
        SWUQueueMayChooseTarget(intval($player), $spaceUnits,
            "Exhaust_a_space_unit", "Choose_a_space_unit_to_exhaust", "EXHAUST_UNIT");
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_228:0"] = function($player, $mzID = '') {
// Barrel Roll — "Attack with a space unit. After completing this attack, you
                          // may exhaust a space unit."
            global $playerID;
            $playerID = intval($player);
            $units = [];
            $arr = GetZone('mySpaceArena');
            for ($i = 0; $i < count($arr); $i++) {
                $u = $arr[$i];
                if (SWUObjGone($u) || intval($u->Status) !== 1) continue;
                $units[] = "mySpaceArena-{$i}";
            }
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Choose_a_space_unit_to_attack_with", "JTL_228#0");
            return;
};
