<?php
// LAW_127
// Cost 2 - Kill Switch - [Vigilance] - Upgrade Power -1 - Upgrade HP -1
// Text: When Played: Exhaust attached unit.

// LAW_127 Kill Switch — When Played (as an upgrade): exhaust the attached unit. ($mzID = the host.)
$whenPlayedAbilities["LAW_127:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    OnExhaustCard(intval($player), $mzID);
};
