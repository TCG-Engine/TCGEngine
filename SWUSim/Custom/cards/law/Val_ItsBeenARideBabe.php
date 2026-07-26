<?php
// LAW_091
// Cost 2 - Val - It's Been a Ride, Babe - [Cunning,Vigilance] - Power 2 - HP 4
// Text: When Played: Give a Shield token to another friendly unit. / When Defeated: Give a Shield token to an enemy unit.

// LAW_091 Val — When Played: Shield to another friendly unit. When Defeated: Shield to an enemy unit.
$whenPlayedAbilities["LAW_091:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    $others = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $uid) $others[] = $mz;
    }
    if (empty($others)) return;
    SWUQueueChooseTarget(intval($player), $others, "Give_a_Shield_token_to_another_friendly_unit", "GIVE_SHIELD");
};

$whenDefeatedAbilities["LAW_091:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $enemy = SWUAllUnits('their');
    if (empty($enemy)) return;
    SWUQueueChooseTarget(intval($player), $enemy, "Give_a_Shield_token_to_an_enemy_unit", "GIVE_SHIELD");
};
