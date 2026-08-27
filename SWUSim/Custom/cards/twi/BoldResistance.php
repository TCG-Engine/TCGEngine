<?php
// TWI_153
// Cost 3 - Bold Resistance - [Aggression,Heroism]
// Text: Choose up to 3 units that share the same Trait. Each of those units gets +2/+0 for this phase.

// TWI_153 Bold Resistance — buff the chosen units +2/+0 this phase, but only if they all share a
// common Trait (server-side enforcement of "units that share the same Trait").
$customDQHandlers["TWI_153#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $picks = ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS')
        ? [] : array_values(array_filter(explode('&', $lastDecision), fn($s) => $s !== '' && $s !== '-' && $s !== 'PASS'));
    $objs = [];
    foreach ($picks as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $objs[$mz] = $o; }
    if (count($objs) <= 1) { foreach ($objs as $mz => $o) SWUApplyPhaseBuff($mz, 2, 0, 'TWI_153'); return; }
    // Intersect traits across all chosen units; buff only if they share ≥1 trait.
    $common = null;
    foreach ($objs as $o) {
        $traits = array_values(array_filter(array_map('trim', explode(',', (string)(CardTrait($o->CardID ?? '') ?? '')))));
        $common = ($common === null) ? $traits : array_intersect($common, $traits);
    }
    if (empty($common)) return; // no shared trait → invalid selection, no buff
    foreach ($objs as $mz => $o) SWUApplyPhaseBuff($mz, 2, 0, 'TWI_153');
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_153:0"] = function($player, $mzID = '') {
// Bold Resistance — "Choose up to 3 units that share the same Trait. Each of
                          // those units gets +2/+0 for this phase."
            global $playerID;
            $playerID = intval($player);
            $all = array_merge(
                ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter),
                ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
            );
            if (empty($all)) return;
            DecisionQueueController::AddDecision(intval($player), 'MZMULTICHOOSE',
                "0|3|" . implode('&', $all), 1, tooltip: 'Choose_up_to_3_units_sharing_a_Trait_(+2/+0_this_phase)');
            DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'TWI_153#0', 1, dontSkipOnPass: 1);
            return;
};
