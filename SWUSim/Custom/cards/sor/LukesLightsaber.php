<?php
// SOR_053
// Cost 2 - Luke's Lightsaber - [Vigilance,Heroism] - Upgrade Power 3 - Upgrade HP 1
// Text: Attach to a non-Vehicle unit. / When Played: If attached unit is Luke Skywalker, heal all damage from him and give a Shield token to him.

// SOR_053 Luke's Lightsaber — When Played (as upgrade): If attached unit is Luke Skywalker,
// heal all damage from him and give him a Shield token. $mzID is the HOST unit's mzID
// (CollectWhenPlayedAsUpgradeTriggers falls back to the WhenPlayed window for non-pilot upgrades).
$whenPlayedAbilities["SOR_053:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    if (CardTitle($host->CardID) !== 'Luke Skywalker') return;
    OnHealUnit(intval($player), $mzID, 99);   // heal ALL damage (clamped at 0)
    GiveShieldToken(intval($player), $mzID);
};
