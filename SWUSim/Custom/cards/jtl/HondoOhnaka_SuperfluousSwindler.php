<?php
// JTL_056
// Cost 4 - Hondo Ohnaka - Superfluous Swindler - [Vigilance,Vigilance] - Power 3 - HP 5
// Text: Shielded / On Attack: You may take control of a non-Pilot upgrade on a unit and attach it to a different eligible unit.

// JTL_056 Hondo Ohnaka — Shielded + On Attack: take control of a non-Pilot upgrade and move it.
$onAttackAbilities["JTL_056:0"] = function($player, $mzID) {
    SWUQueueMoveUpgrade(intval($player), 'nonpilot', "Take_control_of_a_non-Pilot_upgrade_to_move_it");
};
