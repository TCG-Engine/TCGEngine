<?php
// SHD_073
// Cost 2 - Mandalorian Armor - [Vigilance] - Upgrade Power 1 - Upgrade HP 3
// Text: Attach to a non-Vehicle unit. / When Played: If attached unit is a Mandalorian, give a Shield token to it.

// ─── SHD_073 Mandalorian Armor (When Played as upgrade) ───────────────────────
// When Played: If attached unit is a Mandalorian, give a Shield token to it. (Reached via the upgrade
// WhenPlayed fallback in CollectWhenPlayedAsUpgradeTriggers, so $mzID is the HOST unit.)
$whenPlayedAbilities["SHD_073:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    if (TraitContains($host, 'Mandalorian')) {
        GiveShieldToken(intval($player), $mzID);
    }
};
