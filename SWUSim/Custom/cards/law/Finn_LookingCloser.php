<?php
// LAW_095
// Cost 6 - Finn - Looking Closer - [Cunning,Vigilance] - Power 6 - HP 5
// Text: Ambush (When you play this unit, he may attack an enemy unit.) / On Attack: You may give a Shield token to a non-<uq> unit.

// LAW_095 Finn — Ambush + On Attack: you may give a Shield token to a non-unique unit.
$onAttackAbilities["LAW_095:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && !CardUnique($o->CardID ?? '')) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_a_Shield_token_to_a_non-unique_unit?", "Choose_a_unit", "GIVE_SHIELD");
};
