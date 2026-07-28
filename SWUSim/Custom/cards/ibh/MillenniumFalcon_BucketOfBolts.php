<?php
// IBH_031
// Cost 7 - Millennium Falcon - Bucket of Bolts - [Cunning,Heroism] - Power 5 - HP 6
// Text: When Played: If your base has more damage on it than an enemy base, ready this unit.

// IBH_031 Millennium Falcon — When Played: if your base has more damage on it than an enemy base, ready
// this unit.
$whenPlayedAbilities["IBH_031:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $myBase  = GetBase(intval($player));
    $oppBase = GetBase(OtherPlayer(intval($player)));
    $myDmg  = (count($myBase) > 0 && empty($myBase[0]->removed))   ? intval($myBase[0]->Damage ?? 0)  : 0;
    $oppDmg = (count($oppBase) > 0 && empty($oppBase[0]->removed)) ? intval($oppBase[0]->Damage ?? 0) : 0;
    if ($myDmg > $oppDmg) {
        $obj = GetZoneObject($mzID);
        if ($obj !== null && empty($obj->removed)) $obj->Status = 1; // ready it
    }
};
