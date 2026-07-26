<?php
// LOF_075
// Cure Wounds
// Text: Use the Force (lose your Force token). If you do, heal 6 damage from a unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_075:0"] = function($player, $mzID = '') {
// Cure Wounds — "Use the Force. If you do, heal 6 damage from a unit." Mandatory
                          // use (auto if you control the Force; fizzles if you don't).
            if (!PlayerHasTheForce(intval($player))) return;
            UseTheForce(intval($player));
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Heal_6_damage_from_a_unit", "HEAL_TARGET|6");
            return;
};
