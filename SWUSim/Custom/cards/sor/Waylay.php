<?php
// SOR_222
// Waylay
// Text: Return a non-leader unit to its owner's hand.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_222:0"] = function($player, $mzID = '') {
global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch('myGroundArena',    NonLeaderUnitFilter),
                ZoneSearch('mySpaceArena',     NonLeaderUnitFilter),
                ZoneSearch('theirGroundArena', NonLeaderUnitFilter),
                ZoneSearch('theirSpaceArena',  NonLeaderUnitFilter)
            );
            if (empty($targets)) return;
            if (count($targets) === 1) {
                // Single valid target — auto-bounce (Waylay is mandatory, no "you may").
                DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
                DecisionQueueController::AddDecision($player, 'CUSTOM', 'BOUNCE_UNIT', 1);
            } else {
                DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1, 'Choose_a_unit_to_return_to_hand');
                DecisionQueueController::AddDecision($player, 'CUSTOM', 'BOUNCE_UNIT', 1);
            }
            return;
};
