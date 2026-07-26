<?php
// LAW_240
// Cost 6 - Milodon Rider - [Cunning] - Power 5 - HP 6
// Text: Ambush (When you play this unit, it may attack an enemy unit.) / When Played: You may return another friendly non-leader unit to its owner's hand.

// LAW_240 Milodon Rider — Ambush + When Played: you may return another friendly non-leader unit to its
// owner's hand.
$whenPlayedAbilities["LAW_240:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    $targets = [];
    foreach (array_merge(ZoneSearch("myGroundArena", NonLeaderUnitFilter), ZoneSearch("mySpaceArena", NonLeaderUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $uid) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Return_another_friendly_non-leader_unit_to_hand?", "Choose_a_unit", "BOUNCE_UNIT");
};
