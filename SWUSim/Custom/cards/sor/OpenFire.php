<?php
// SOR_172
// Open Fire
// Text: Deal 4 damage to a unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_172:0"] = function($player, $mzID = '') {
// Open Fire — "Deal 4 damage to a unit."
            $targets = implode("&", array_filter(array_merge(
                ZoneSearch("myGroundArena",    ["Unit", "Leader Unit"]),
                ZoneSearch("mySpaceArena",     ["Unit", "Leader Unit"]),
                ZoneSearch("theirGroundArena", ["Unit", "Leader Unit"]),
                ZoneSearch("theirSpaceArena",  ["Unit", "Leader Unit"])
            )));
            if ($targets === '') return;
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $targets, 1, "Choose_a_unit_to_deal_4_damage");
            DecisionQueueController::AddDecision($player, "CUSTOM", "DEAL_UNIT_DAMAGE|4", 1);
            return;
};
