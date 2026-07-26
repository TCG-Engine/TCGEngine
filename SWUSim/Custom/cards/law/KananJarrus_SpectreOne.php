<?php
// LAW_089
// Cost 4 - Kanan Jarrus - Spectre One - [Cunning,Vigilance,Heroism] - Power 3 - HP 4
// Text: Restore 1 / When Played: You may return a non-leader unit that costs 2 or less to its owner's hand. If you control a Command or Aggression unit, you may return a non-leader unit that costs 4 or less instead.

// LAW_089 Kanan Jarrus — Restore 1 + When Played: you may return a non-leader unit that costs 2 or less
// to its owner's hand (4 or less instead if you control a Command or Aggression unit).
$whenPlayedAbilities["LAW_089:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $threshold = (PlayerHasUnitWithAspectInPlay(intval($player), 'Command') || PlayerHasUnitWithAspectInPlay(intval($player), 'Aggression')) ? 4 : 2;
    $targets = [];
    foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) <= $threshold) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Return_a_non-leader_unit_to_hand?", "Choose_a_unit", "BOUNCE_UNIT");
};
