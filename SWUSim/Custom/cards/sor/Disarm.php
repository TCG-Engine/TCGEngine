<?php
// SOR_216
// Disarm
// Text: Give an enemy unit -4/-0 for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_216:0"] = function($player, $mzID = '') {
// Disarm — "Give an enemy unit –4/–0 for this phase."
            global $playerID;
            $playerID = intval($player);
            $targets = array_values(array_merge(
                ZoneSearch('theirGroundArena', AnyUnitFilter),
                ZoneSearch('theirSpaceArena',  AnyUnitFilter)
            ));
            if (empty($targets)) return;
            if (count($targets) === 1) {
                DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
            } else {
                DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
                    'Choose_an_enemy_unit_to_give_-4/-0');
            }
            DecisionQueueController::AddDecision($player, 'CUSTOM', 'APPLY_PHASE_DEBUFF|4|0|SOR_216', 1);
            return;
};
