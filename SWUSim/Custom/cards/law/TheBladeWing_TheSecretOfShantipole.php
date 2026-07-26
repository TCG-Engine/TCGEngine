<?php
// LAW_241
// Cost 6 - The Blade Wing - The Secret of Shantipole - [Cunning] - Power 3 - HP 3
// Text: When Played: You may return a non-leader unit to its owner's hand.

// LAW_241 The Blade Wing — When Played: you may return a non-leader unit to its owner's hand.
$whenPlayedAbilities["LAW_241:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = array_merge(
        ZoneSearch("myGroundArena", NonLeaderUnitFilter), ZoneSearch("mySpaceArena", NonLeaderUnitFilter),
        ZoneSearch("theirGroundArena", NonLeaderUnitFilter), ZoneSearch("theirSpaceArena", NonLeaderUnitFilter)
    );
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Return_a_non-leader_unit_to_hand?", "Choose_a_unit", "BOUNCE_UNIT");
};
