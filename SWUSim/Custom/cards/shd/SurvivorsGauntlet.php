<?php
// SHD_064
// Cost 5 - Survivors' Gauntlet - [Vigilance] - Power 4 - HP 6
// Text: When Played/On Attack: You may attach an upgrade on a unit to another eligible unit controlled by the same player.

// ─── SHD_064 Survivors' Gauntlet (When Played / On Attack) ────────────────────
// You may attach an upgrade on a unit to another eligible unit controlled by the same player.
$whenPlayedAbilities["SHD_064:0"] =
$onAttackAbilities["SHD_064:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUQueueMoveUpgrade(intval($player), '',
        "Move_an_upgrade_to_another_eligible_unit_the_same_player_controls", '', 'sameController');
};
