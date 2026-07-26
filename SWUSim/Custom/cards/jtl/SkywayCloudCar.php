<?php
// JTL_220
// Cost 3 - Skyway Cloud Car - [Cunning] - Power 3 - HP 3
// Text: When Defeated: You may return a non-leader unit with 2 or less power to its owner's hand.

// ── JTL_220 Skyway Cloud Car — When Defeated: may return a non-leader unit with 2 or less power to its
// owner's hand. ──────────────────────────────────────────────────────────────────────────────────────
$whenDefeatedAbilities["JTL_220:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena", NonLeaderUnitFilter), ZoneSearch("mySpaceArena", NonLeaderUnitFilter),
        ZoneSearch("theirGroundArena", NonLeaderUnitFilter), ZoneSearch("theirSpaceArena", NonLeaderUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && ObjectCurrentPower($o) <= 2) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_return_a_non-leader_unit_with_2_or_less_power", "Return_to_hand", "BOUNCE_UNIT");
};
