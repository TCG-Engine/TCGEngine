<?php
// SOR_074
// Repair
// Text: Heal 3 damage from a unit or base.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_074:0"] = function($player, $mzID = '') {
// Repair — "Heal 3 damage from a unit or base." Bases ARE valid
            // MZCHOOSE targets via myBase-0 / theirBase-0 (GetZone recognizes those zones).
            $targets = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter),
                ["myBase-0", "theirBase-0"]
            );
            DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1, "Heal_3_from_a_unit_or_base");
            DecisionQueueController::AddDecision($player, "CUSTOM", "HEAL_TARGET|3", 1);
            return;
};
