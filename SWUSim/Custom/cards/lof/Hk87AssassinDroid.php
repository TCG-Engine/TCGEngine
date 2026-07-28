<?php
// LOF_235
// Cost 5 - HK-87 Assassin Droid - [Villainy] - Power 4 - HP 4
// Text: When Defeated: Deal 2 damage to each ground unit.

// LOF_235 HK-87 Assassin Droid — When Defeated: deal 2 damage to each ground unit.
$whenDefeatedAbilities["LOF_235:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    foreach (SWUAllUnits(null, GroundArena) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        SWUDealDamageToUnit($mz, 2, intval($player));
    }
};
