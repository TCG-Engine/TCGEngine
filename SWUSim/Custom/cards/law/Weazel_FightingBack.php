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
    foreach (SWUAllUnits('my') as $mz) {
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
