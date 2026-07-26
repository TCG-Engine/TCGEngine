<?php
// LOF_078
// Whirlwind of Power
// Text: Give a unit -2/-2 for this phase. If you control a Force unit, give it -3/-3 instead.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_078:0"] = function($player, $mzID = '') {
// Whirlwind of Power — "Give a unit -2/-2 for this phase. If you control a Force
                          // unit, give it -3/-3 instead."
            global $playerID; $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            $n = PlayerHasUnitWithTraitInPlay(intval($player), 'Force', -1) ? 3 : 2;
            SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_-{$n}/-{$n}_for_this_phase", "APPLY_PHASE_DEBUFF|{$n}|{$n}|LOF_078");
            return;
};
