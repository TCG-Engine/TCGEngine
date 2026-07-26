<?php
// SOR_169
// Keep Fighting
// Text: Ready a unit with 3 or less power.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_169:0"] = function($player, $mzID = '') {
// Keep Fighting — "Ready a unit with 3 or less power."
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena",    ["Unit", "Leader Unit"]),
                ZoneSearch("mySpaceArena",     ["Unit", "Leader Unit"]),
                ZoneSearch("theirGroundArena", ["Unit", "Leader Unit"]),
                ZoneSearch("theirSpaceArena",  ["Unit", "Leader Unit"])
            ) as $mz) {
                $obj = GetZoneObject($mz);
                if (SWUObjGone($obj)) continue;
                if (ObjectCurrentPower($obj) <= 3) $targets[] = $mz;
            }
            if (empty($targets)) return;
            if (count($targets) === 1) {
                DecisionQueueController::AddDecision($player, "PASSPARAMETER", $targets[0], 0);
            } else {
                $targetStr = implode("&", $targets);
                DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, tooltip:"Choose_a_unit_to_ready");
            }
            DecisionQueueController::AddDecision($player, "CUSTOM", "READY_UNIT", 0);
            return;
};
