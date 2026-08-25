<?php
// SEC_089
// Cost 8 - PreMor Personnel Carrier - [Command,Villainy] - Power 6 - HP 6
// Text: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) / When Played: Give this unit an Experience token for each ground unit you control.

// SEC_089 PreMor Personnel Carrier — Overwhelm (auto) + When Played: give itself an Experience token for
// each ground unit you control.
$whenPlayedAbilities["SEC_089:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $n = 0;
    foreach (SWUControlledUnits('Ground') as $mz) {   // "each ground unit YOU CONTROL"
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $n++;
    }
    for ($i = 0; $i < $n; $i++) DoGiveExperienceToken(intval($player), $mzID);
};
