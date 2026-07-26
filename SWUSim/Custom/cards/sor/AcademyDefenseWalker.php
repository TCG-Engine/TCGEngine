<?php
// SOR_037
// Cost 6 - Academy Defense Walker - [Vigilance,Villainy] - Power 5 - HP 5
// Text: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.) / When Played: Give an Experience token to each friendly damaged unit.

// SOR_037 Academy Defense Walker — When Played: give an Experience token to each friendly DAMAGED unit.
$whenPlayedAbilities["SOR_037:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->Damage ?? 0) > 0) DoGiveExperienceToken(intval($player), $mz);
    }
};
