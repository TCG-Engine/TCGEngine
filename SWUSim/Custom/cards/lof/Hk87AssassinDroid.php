<?php
// LOF_235
// Cost 5 - HK-87 Assassin Droid - [Villainy] - Power 4 - HP 4
// Text: When Defeated: Deal 2 damage to each ground unit.

// LOF_235 HK-87 Assassin Droid — When Defeated: deal 2 damage to each ground unit.
$whenDefeatedAbilities["LOF_235:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // UID SNAPSHOT, then re-find each unit before dealing. Walking the slot list directly skipped the unit right
    // behind any unit this damage defeats: the defeat compacts the arena and the next unit slides into a slot the
    // loop has already passed (reported 2026-10-19 with Grand Admiral Thrawn: "4 damage to some, only 2 to others").
    $uids = [];
    foreach (SWUAllUnits(null, GroundArena) as $mz) {
        $o = GetZoneObject($mz);
        if (!SWUObjGone($o)) $uids[] = intval($o->UniqueID ?? 0);
    }
    foreach ($uids as $uid) {
        $playerID = intval($player);
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDealDamageToUnit($mz, 2, intval($player));
    }
};
