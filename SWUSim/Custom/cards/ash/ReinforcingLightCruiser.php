<?php
// ASH_051
// Cost 6 - Reinforcing Light Cruiser - [Vigilance,Villainy] - Power 5 - HP 5
// Text: Restore 1 (When this unit attacks, heal 1 damage from your base.) / When Played: You may exhaust a unit.

// ASH_051 Reinforcing Light Cruiser — Restore 1 (keyword) + When Played: you may exhaust a unit.
$whenPlayedAbilities["ASH_051:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $tg = SWUAllUnits();
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Exhaust_a_unit?", "Choose_a_unit", "EXHAUST_UNIT");
};
