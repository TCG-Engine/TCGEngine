<?php
// SOR_076
// Make an Opening
// Text: Give a unit -2/-2 for this phase. Heal 2 damage from your base.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_076:0"] = function($player, $mzID = '') {
// Make an Opening — "Give a unit –2/–2 for this phase. Heal 2 damage from your base."
            global $playerID;
            $playerID = intval($player);
            OnHealBase(intval($player), intval($player), 2);
            // ⚠ UNQUALIFIED pool = the WHOLE table. NOT my*+their*: `their*` excludes a Team Suns
            // teammate, so that pairing leaves their units in NEITHER list and they silently drop out
            // of the pool. SWUAllUnits() starts from 'team' (degrades to 'my' outside a team game, so
            // Premier is byte-identical). See memory: unqualified pools miss teammates.
            $targets = SWUAllUnits();
            if (empty($targets)) return;
            if (count($targets) === 1) {
                DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
            } else {
                DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
                    'Choose_a_unit_to_give_-2/-2');
            }
            DecisionQueueController::AddDecision($player, 'CUSTOM', 'APPLY_PHASE_DEBUFF|2|2|SOR_076', 1);
            return;
};
