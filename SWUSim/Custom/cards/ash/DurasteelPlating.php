<?php
// ASH_086
// Cost 2 - Durasteel Plating - [Vigilance] - Upgrade Power 1 - Upgrade HP 1
// Text: When Played: Give a Shield token to attached unit.

// ASH_086 Durasteel Plating (upgrade) — When Played: give a Shield token to attached unit. As a non-pilot
// upgrade, its WhenPlayed lands here with $mzID = the HOST.
$whenPlayedAbilities["ASH_086:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    DoGiveShieldToken(intval($player), $mzID);
};
