<?php
// SEC_073
// Cost 1 - The Eye of Aldhani - [Vigilance]
// Text: At the start of the next action phase, for each enemy unit, its controller must pay 1 resource or exhaust that unit.

// SEC_073 The Eye of Aldhani — resolve the pay-or-exhaust: pay 1 resource for each SELECTED unit (keeps
// it ready), exhaust every unselected unit. (The MZMULTICHOOSE was capped at the target's ready resources.)
$customDQHandlers["SEC_073#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $target    = intval($parts[0] ?? $player);
    $remaining = intval($parts[1] ?? 0);   // additional Eye-of-Aldhani copies still to resolve after this one
    $playerID = $target;
    $selected = [];
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        foreach (explode('&', $lastDecision) as $s) { if ($s !== '' && $s !== '-') $selected[$s] = true; }
    }
    $units = array_values(array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)));
    foreach ($units as $mz) {
        if (isset($selected[$mz])) SWUPayInlineAbilityCost($target, 1);   // paid → unit stays as it was
        else                       OnExhaustCard($target, $mz);       // not paid → exhausted
    }
    // Chain the next copy's resolution (its cap is recomputed live, after this copy's spend).
    if ($remaining > 0) _SWUQueueEyeOfAldhaniResolution($target, $remaining);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_073:0"] = function($player, $mzID = '') {
// The Eye of Aldhani — At the start of the NEXT action phase, for each enemy
                          // unit, its controller must pay 1 resource or exhaust it. Arm a delayed flag
                          // (survives the regroup, like SOR_017); resolved in ActionPhaseStart.
            AddGlobalEffects(intval($player), 'SWU_EYE_ALDHANI');
            return;
};
