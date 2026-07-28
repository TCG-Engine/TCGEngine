<?php
// SOR_086
// Cost 6 - Gladiator Star Destroyer - [Command,Villainy] - Power 5 - HP 6
// Text: When Played: Give a unit Sentinel for this phase. (Units in this arena can't attack your non-Sentinel units or your base.)

// SOR_086 Gladiator Star Destroyer — When Played: Give a unit Sentinel for this phase.
$whenPlayedAbilities["SOR_086:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUQueueChooseTarget(intval($player), _SWUCollectUnits(-1, fn($o) => true), // any unit, either player
        'Give_a_unit_Sentinel_for_this_phase', 'GRANT_PHASE_KEYWORD|SOR_086');
};
