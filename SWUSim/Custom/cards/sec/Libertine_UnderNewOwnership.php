<?php
// SEC_212
// Cost 4 - Libertine - Under New Ownership - [Cunning,Cunning] - Power 3 - HP 7
// Text: This unit gets +1/+0 for each captured card it's guarding. / When Played: Choose an enemy unit and a non-leader friendly unit. The enemy unit captures the friendly unit.

// SEC_212 Libertine — +1/+0 per captive (in ObjectCurrentPower) + When Played: choose an enemy unit and
// a non-leader friendly unit; the enemy unit captures the friendly unit.
$whenPlayedAbilities["SEC_212:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $enemies = SWUAllUnits('their');
    if (empty($enemies)) return;
    SWUQueueChooseTarget(intval($player), $enemies, "Choose_an_enemy_unit", "SEC_212#0|{$selfUID}");
};

$customDQHandlers["SEC_212#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $selfUID = intval($parts[0] ?? 0);
    $enemy = GetZoneObject($lastDecision);
    if (SWUObjGone($enemy)) return;
    $enemyUID = intval($enemy->UniqueID ?? 0);
    // "a non-leader friendly unit" — NO "another", so Libertine itself is a legal target. When Libertine
    // is the only friendly unit, it must be selectable (the enemy captures Libertine itself). $selfUID is
    // NOT excluded.
    $friendly = [];
    foreach (SWUFriendlyUnits(null, NonLeaderUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $friendly[] = $mz;
    }
    if (empty($friendly)) return;
    SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_non-leader_unit_to_be_captured", "SEC_212#1|{$enemyUID}");
};

$customDQHandlers["SEC_212#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $captor = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($captor !== null) DoCaptureUnit(intval($player), $captor, $lastDecision);
};
