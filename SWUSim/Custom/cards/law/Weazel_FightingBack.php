<?php
// LAW_182
// Cost 2 - Weazel - Fighting Back - [Aggression,Heroism] - Power 2 - HP 3
// Text: On Attack: Another friendly unit gains Raid 2 for this phase. (It gets +2/+0 while attacking.)

// LAW_182 Weazel — On Attack: another friendly unit gains Raid 2 for this phase.
$onAttackAbilities["LAW_182:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    $others = [];
    // ⚠ FRIENDLY spans the TEAM (user ruling 2026-08-25, IBH_095): in Team Suns a teammate's
    // unit is friendly, so the pool is SWUFriendlyUnits() ('team'), not 'my'. ⚠ NOT the same as "a unit you control",
    // which stays 'my' — control is per-player. 'team' degrades to 'my' outside a team game, so
    // Premier is byte-identical. See memory: unqualified pools miss teammates.
    foreach (SWUFriendlyUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $uid) $others[] = $mz;
    }
    if (empty($others)) return;
    SWUQueueMayChooseTarget(intval($player), $others, "Give_another_friendly_unit_Raid_2_for_this_phase?", "Choose_a_unit", "LAW_182#0");
};

$customDQHandlers["LAW_182#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if ($o !== null && empty($o->removed)) AddTurnEffect($lastDecision, 'LAW_182');   // Raid 2 this phase
};
