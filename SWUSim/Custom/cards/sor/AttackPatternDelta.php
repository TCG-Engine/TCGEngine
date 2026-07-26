<?php
// SOR_106
// Cost 3 - Attack Pattern Delta - [Command,Command]
// Text: Give a friendly unit +3/+3 for this phase. / Give another friendly unit +2/+2 for this phase. / Give a third friendly unit +1/+1 for this phase.

// ── SOR_106 Attack Pattern Delta — chained per-buff resolver ────────────────
// Applies the current +power/+hp buff to the unit at $lastDecision, then queues the
// next descending buff against a DISTINCT remaining friendly unit (excluding any
// already buffed). No units leave play, so captured mzIDs stay valid across steps.
// Param: SOR_106|curPower|curHp|remainingBuffsCSV|excludedMzIDsCSV
//   remainingBuffsCSV: e.g. "2_2,1_1"   excludedMzIDsCSV: e.g. "myGroundArena-0,myGroundArena-1"
$customDQHandlers["SOR_106#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);

    $chosen = ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS')
        ? '' : $lastDecision;
    if ($chosen !== '') {
        SWUApplyPhaseBuff($chosen, intval($parts[0] ?? 0), intval($parts[1] ?? 0), 'SOR_106');
    }

    // Build the set of units already buffed (prior excludes + the one just chosen).
    $excluded = array_values(array_filter(explode(',', $parts[3] ?? '')));
    if ($chosen !== '') $excluded[] = $chosen;

    // Remaining descending buffs to assign.
    $remaining = array_values(array_filter(explode(',', $parts[2] ?? '')));
    if (empty($remaining)) return;
    $next = array_shift($remaining);            // "2_2"
    [$np, $nh] = array_pad(explode('_', $next), 2, '0');

    // Distinct friendly units that have not yet received a buff.
    $targets = array_values(array_filter(array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter),
        ZoneSearch('mySpaceArena',  AnyUnitFilter)
    ), fn($mz) => !in_array($mz, $excluded, true)));
    if (empty($targets)) return;               // not enough friendly units → fizzle

    if (count($targets) === 1) {
        DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
    } else {
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
            'Choose_another_friendly_unit_to_give_+' . intval($np) . '/+' . intval($nh));
    }
    $remStr = implode(',', $remaining);
    $excStr = implode(',', $excluded);
    DecisionQueueController::AddDecision($player, 'CUSTOM',
        "SOR_106#0|{$np}|{$nh}|{$remStr}|{$excStr}", 1);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_106:0"] = function($player, $mzID = '') {
// Attack Pattern Delta — "Give a friendly unit +3/+3 for this
            // phase. Give another friendly unit +2/+2 for this phase. Give a third friendly
            // unit +1/+1 for this phase." Each buff goes to a DISTINCT friendly unit; the
            // descending buffs are assigned one at a time via the chained SOR_106 handler.
            global $playerID;
            $playerID = intval($player);
            $targets = array_values(array_merge(
                ZoneSearch('myGroundArena', AnyUnitFilter),
                ZoneSearch('mySpaceArena',  AnyUnitFilter)
            ));
            if (empty($targets)) return;
            if (count($targets) === 1) {
                DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
            } else {
                DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
                    'Choose_a_friendly_unit_to_give_+3/+3');
            }
            // SOR_106|curPower|curHp|remainingBuffsCSV|excludedMzIDsCSV
            DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_106#0|3|3|2_2,1_1|', 1);
            return;
};
