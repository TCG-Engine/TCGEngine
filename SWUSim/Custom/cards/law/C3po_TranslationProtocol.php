<?php
// LAW_152
// Cost 2 - C-3PO - Translation Protocol - [Command] - Power 1 - HP 4
// Text: On Attack: You may give an Experience token to another non-leader unit that shares a Trait with a friendly leader.

// LAW_152 C-3PO — On Attack: you may give an Experience token to another non-leader unit that shares a
// Trait with a friendly leader.
$onAttackAbilities["LAW_152:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $leader = SWUGetLeader(intval($player));
    if ($leader === null) return;
    $leaderTraits = array_filter(array_map('trim', explode(',', (string)(CardTrait($leader->CardID ?? '') ?? ''))));
    if (empty($leaderTraits)) return;
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    $targets = [];
    foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o) || intval($o->UniqueID ?? 0) === $uid) continue;
            $ut = array_filter(array_map('trim', explode(',', (string)(CardTrait($o->CardID ?? '') ?? ''))));
            if (!empty(array_intersect($leaderTraits, $ut))) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_an_Experience_token_to_a_unit_sharing_a_Trait_with_your_leader?", "Choose_a_unit", "GIVE_EXPERIENCE|1");
};
