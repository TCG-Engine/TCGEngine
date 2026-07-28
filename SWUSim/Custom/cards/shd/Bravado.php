<?php
// SHD_182
// Bravado
// Text: If you've defeated an enemy unit this phase, this event costs 2 resources less to play. Ready a unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_182:0"] = function($player, $mzID = '') {
// Bravado — "Ready a ground unit."
            global $playerID;
            $playerID = intval($player);
            $targets = array_values(array_merge(
                ZoneSearch('myGroundArena',    AnyUnitFilter),
                ZoneSearch('theirGroundArena', AnyUnitFilter)
            ));
            if (empty($targets)) return;
            if (count($targets) === 1) {
                DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
            } else {
                DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
                    'Choose_a_ground_unit_to_ready');
            }
            DecisionQueueController::AddDecision($player, 'CUSTOM', 'READY_UNIT', 1);
            return;
};
