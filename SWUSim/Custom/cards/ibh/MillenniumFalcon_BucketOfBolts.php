<?php
// IBH_031
// Cost 7 - Millennium Falcon - Bucket of Bolts - [Cunning,Heroism] - Power 5 - HP 6
// Text: When Played: If your base has more damage on it than an enemy base, ready this unit.

// IBH_031 Millennium Falcon — When Played: if your base has more damage on it than an enemy base, ready
// this unit.
$whenPlayedAbilities["IBH_031:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $myBase  = GetBase(intval($player));
    // "more damage than AN enemy base" — EXISTENTIAL, so compare against the LEAST damaged enemy base:
    // if any enemy base has less damage than yours, the condition is met. OtherPlayer() checked seat 2.
    $oppDmgMin = null;
    foreach (OpponentsOf(intval($player)) as $o) {
        foreach (GetBase($o) as $b) {
            if (empty($b->removed)) { $d = intval($b->Damage ?? 0); $oppDmgMin = ($oppDmgMin === null) ? $d : min($oppDmgMin, $d); break; }
        }
    }
    $myDmg  = (count($myBase) > 0 && empty($myBase[0]->removed))   ? intval($myBase[0]->Damage ?? 0)  : 0;
    $oppDmg = ($oppDmgMin === null) ? 0 : $oppDmgMin;
    if ($myDmg > $oppDmg) {
        $obj = GetZoneObject($mzID);
        if ($obj !== null && empty($obj->removed)) $obj->Status = 1; // ready it
    }
};
