<?php
// JTL_040
// Cost 7 - Fleet Interdictor - [Vigilance,Villainy] - Power 6 - HP 6
// Text: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.) / When Defeated: You may defeat a space unit that costs 3 or less.

// ── JTL_040 Fleet Interdictor — Sentinel (auto) + When Defeated: may defeat a space unit ≤3 cost. ────
$whenDefeatedAbilities["JTL_040:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits(null, SpaceArena) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID)) <= 3) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_defeat_a_space_unit_costing_3_or_less", "Defeat_a_space_unit", "DEFEAT_UNIT");
};
