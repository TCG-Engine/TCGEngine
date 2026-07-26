<?php
// IBH_099
// Cost 7 - Blizzard One - Veers at the Helm - [Vigilance,Villainy] - Power 5 - HP 7
// Text: When Played: You may defeat a non-leader ground unit with 3 or less remaining HP.

// IBH_099 Blizzard One — When Played: you may defeat a non-leader ground unit with 3 or less remaining HP.
$whenPlayedAbilities["IBH_099:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits(null, GroundArena) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || IsLeaderUnit($o)) continue; // non-leader
        if (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= 3) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Defeat_a_non-leader_ground_unit_(3_or_less_remaining_HP)?",
        "Choose_a_unit", "DEFEAT_UNIT");
};
