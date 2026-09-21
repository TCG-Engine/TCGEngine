<?php
// SOR_224
// Cost 6 - Change of Heart - [Cunning]
// Text: Take control of a non-leader unit. At the start of the regroup phase, its owner takes control of it.

// ── SOR_224 Change of Heart — event steal handler ───────────────────────────
// Receives chosen unit mzID from MZCHOOSE/PASSPARAMETER.
// Moves it to $player's arena and marks it TEMPORARY_STEAL so RegroupPhaseStart
// returns it to its owner.
$customDQHandlers["SOR_224#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-') return;
    global $playerID;
    $playerID = intval($player);
    $newMzID = SWUTakeControlOfUnit(intval($player), $lastDecision);
    if ($newMzID !== '') {
        AddTurnEffect($newMzID, 'TEMPORARY_STEAL');
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_224:0"] = function($player, $mzID = '') {
// Change of Heart — "Take control of a non-leader unit. At the start of the regroup phase, its owner takes control of it."
            global $playerID;
            $playerID = intval($player);
            // "A non-leader unit" is UNQUALIFIED, so the pool is the WHOLE table.
            // ⚠ NOT my* + their*: in a team game `their*` excludes a teammate, so that pairing leaves a
            // teammate's units in NEITHER list and they silently drop out of the pool. SWUAllUnits()
            // starts from 'team', which degrades to 'my' outside a team game — Premier byte-identical.
            $targets = SWUAllUnits(null, null, ['Unit']);
            if (empty($targets)) return;
            if (count($targets) === 1) {
                DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
            } else {
                DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
                    'Choose_a_non-leader_unit_to_take_control_of');
            }
            DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_224#0', 1);
            return;
};
