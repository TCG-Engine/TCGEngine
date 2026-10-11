<?php
// TWI_124
// Tactical Advantage (reprint of SOR_124)
// Text: Give a unit +2/+2 for this phase.

// When Played (event) — migrated from OnPlayEvent. ($cardID hardcoded: the phase-buff source key.)
$whenPlayedAbilities["TWI_124:0"] = function($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
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
            'Choose_a_unit_to_give_+2/+2');
    }
    DecisionQueueController::AddDecision($player, 'CUSTOM', "APPLY_PHASE_BUFF|2|2|TWI_124", 1);
};
