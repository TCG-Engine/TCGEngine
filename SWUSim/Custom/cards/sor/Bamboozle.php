<?php
// SOR_199
// Bamboozle
// Text: You may discard a [Cunning] card from your hand instead of paying this event's cost.  Exhaust a unit and return each upgrade on it to its owner's hand.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_199:0"] = function($player, $mzID = '') {
// Bamboozle — "Exhaust a unit and return each upgrade on it to its owner's hand."
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch('myGroundArena',    AnyUnitFilter),
                ZoneSearch('mySpaceArena',     AnyUnitFilter),
                ZoneSearch('theirGroundArena', AnyUnitFilter),
                ZoneSearch('theirSpaceArena',  AnyUnitFilter)
            );
            if (empty($targets)) return;
            if (count($targets) === 1) {
                DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
                DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_199#1', 1);
            } else {
                DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1, 'Choose_a_unit_to_exhaust');
                DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_199#1', 1);
            }
            return;
};
