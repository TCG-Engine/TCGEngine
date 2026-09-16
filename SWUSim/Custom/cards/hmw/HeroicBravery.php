<?php
// HMW_264 Heroic Bravery — Upgrade +0/+2, cost 2, [Heroism], Innate.
// "When Played: If attached unit is a Heroism unit, give a Shield token to it."
// Mandatory. The When Played dispatches with the HOST as $mzID; "it" is that host.

$whenPlayedAbilities["HMW_264:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host) || strpos((string)CardAspect($host->CardID ?? ''), 'Heroism') === false) return;
    DoGiveShieldToken(intval($player), $mzID);
};
