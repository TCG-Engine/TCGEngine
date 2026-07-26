<?php
// SHD_126
// Cost 4 - The Darksaber - [Command] - Upgrade Power 4 - Upgrade HP 3
// Text: Attach to a non-Vehicle unit. / While playing this upgrade on a Mandalorian unit, ignore its aspect penalty. / Attached unit gains, "On Attack: Give an Experience token to each other friendly Mandalorian unit."

// ─── SHD_126 The Darksaber — granted "On Attack: give an Experience token to each OTHER friendly
// Mandalorian unit." Rides the OnAttackFromUpgrade seam (upgrade CardID key → fired for the host). ───
$onAttackAbilities["SHD_126:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    $hostUID = SWUObjUID($host, 0);
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if (intval($o->UniqueID ?? 0) === $hostUID) continue;              // "other"
            if (!HasTrait($o->CardID ?? '', 'Mandalorian')) continue;
            DoGiveExperienceToken(intval($player), $mz);
        }
    }
};
