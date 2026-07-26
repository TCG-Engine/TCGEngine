<?php
// SEC_068
// Cost 7 - Lando Calrissian - Trust Me - [Vigilance] - Power 6 - HP 8
// Text: Grit / When Played: You may choose an enemy unit and another friendly non-leader unit. If you do, heal 6 damage from your base and the enemy unit captures the friendly unit.

// SEC_068 Lando Calrissian — Grit (auto) + When Played: you may choose an enemy unit and ANOTHER
// friendly non-leader unit; heal 6 from your base and the enemy unit captures the friendly unit.
$whenPlayedAbilities["SEC_068:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $enemies = SWUAllUnits('their');
    if (empty($enemies)) return;
    SWUQueueMayChooseTarget(intval($player), $enemies, "Have_an_enemy_unit_capture_a_friendly_unit?", "Choose_an_enemy_unit", "SEC_068#0|{$selfUID}");
};

$customDQHandlers["SEC_068#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $selfUID = intval($parts[0] ?? 0);
    $enemy = GetZoneObject($lastDecision);
    if (SWUObjGone($enemy)) return;
    $enemyUID = intval($enemy->UniqueID ?? 0);
    $friendly = [];
    foreach (array_merge(ZoneSearch("myGroundArena", NonLeaderUnitFilter), ZoneSearch("mySpaceArena", NonLeaderUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID) $friendly[] = $mz;
    }
    if (empty($friendly)) return;
    SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_non-leader_unit_to_be_captured", "SEC_068#1|{$enemyUID}");
};

$customDQHandlers["SEC_068#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    OnHealBase(intval($player), intval($player), 6);
    $captor = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($captor !== null) DoCaptureUnit(intval($player), $captor, $lastDecision);
};
