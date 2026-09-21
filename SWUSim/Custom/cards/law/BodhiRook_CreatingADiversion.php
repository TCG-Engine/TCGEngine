<?php
// LAW_104
// Cost 2 - Bodhi Rook - Creating a Diversion - [Vigilance,Heroism] - Power 2 - HP 4
// Text: On Attack: You may give a friendly Rebel unit Sentinel for this phase.

// LAW_104 Bodhi Rook — On Attack: you may give a friendly Rebel unit Sentinel for this phase.
$onAttackAbilities["LAW_104:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    // ⚠ FRIENDLY spans the TEAM (user ruling 2026-08-25, IBH_095): in Team Suns a teammate's
    // unit is friendly, so the pool is SWUFriendlyUnits() ('team'), not 'my'. ⚠ NOT the same as "a unit you control",
    // which stays 'my' — control is per-player. 'team' degrades to 'my' outside a team game, so
    // Premier is byte-identical. See memory: unqualified pools miss teammates.
    foreach (SWUFriendlyUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && TraitContains($o, 'Rebel')) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_a_friendly_Rebel_unit_Sentinel?", "Choose_a_Rebel_unit", "LAW_104#0");
};

$customDQHandlers["LAW_104#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if ($o !== null && empty($o->removed)) AddTurnEffect($lastDecision, 'LAW_104');   // Sentinel this phase
};
