<?php
// ASH_228
// Cost 1 - Preparation - [Cunning] - Upgrade Power 2 - Upgrade HP 1
// Text: When Played: Exhaust attached unit.

// ASH_228 Preparation (upgrade) — When Played: exhaust attached unit. Non-pilot upgrade → $mzID = host.
$whenPlayedAbilities["ASH_228:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    OnExhaustCard(intval($player), $mzID);
};
