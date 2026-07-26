<?php
// SOR_151
// Cost 2 - Karabast - [Aggression,Heroism]
// Text: A friendly unit deals damage to an enemy unit equal to the amount of damage on the friendly unit plus 1.

// SOR_151 Karabast — step 1: friendly dealer chosen; pick the enemy target.
$customDQHandlers["SOR_151#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $friendlyMz = $lastDecision;
    $enemy = SWUAllUnits('their');
    if (empty($enemy)) return;
    if (count($enemy) === 1) DecisionQueueController::AddDecision($player, "PASSPARAMETER", $enemy[0], 0);
    else DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $enemy), 0, "Choose_an_enemy_unit");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_151#1|" . $friendlyMz, 0);
};

// Step 2: deal (friendly's damage + 1) to the chosen enemy.
$customDQHandlers["SOR_151#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $fo = GetZoneObject($parts[0] ?? '');
    if (SWUObjGone($fo)) return;
    $amount = intval($fo->Damage ?? 0) + 1;
    SWUDealDamageToUnit($lastDecision, $amount, intval($player));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_151:0"] = function($player, $mzID = '') {
// Karabast — a friendly unit deals (its damage + 1) to an enemy unit.
            global $playerID;
            $playerID = intval($player);
            $friendly = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),
                ZoneSearch("mySpaceArena",  AnyUnitFilter)
            );
            $enemy = array_merge(
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($friendly) || empty($enemy)) return;
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_your_unit", "SOR_151#0");
            return;
};
