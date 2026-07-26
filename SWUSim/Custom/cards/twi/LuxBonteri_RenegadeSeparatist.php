<?php
// TWI_210
// Cost 2 - Lux Bonteri - Renegade Separatist - [Cunning] - Power 3 - HP 2
// Text: When an opponent plays a card: If that opponent paid less than the card's cost to play it, ready or exhaust a unit.

// ── TWI_210 Cunning — "When an opponent plays a card: if that opponent paid less than the
// card's cost to play it, ready or exhaust a unit." (Task 5.1)
//
// Step 1: TWI_210#1 — OPTIONCHOOSE result is "Ready" or "Exhaust".
//   $parts contains the unit mzID list (pipe-split from the CUSTOM key via "TWI_210#1|mzA&mzB…").
//   $lastDecision = the chosen mode.
//   Queues MZCHOOSE over the provided unit list, then READY_UNIT or EXHAUST_UNIT.
$customDQHandlers["TWI_210#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);

    // $parts[0] is the '&'-joined unit list passed after the '|' in "TWI_210#1|mzA&mzB&…".
    $unitList = array_values(array_filter(
        explode('&', $parts[0] ?? ''),
        fn($mz) => $mz !== '' && ($o = GetZoneObject($mz)) !== null && empty($o->removed ?? false)
    ));
    if (empty($unitList)) return;   // all units left play before the choice resolved

    $mode    = $lastDecision; // "Ready" or "Exhaust"
    $handler = ($mode === 'Ready') ? 'READY_UNIT' : 'EXHAUST_UNIT';
    $tooltip = ($mode === 'Ready') ? 'Choose_a_unit_to_ready' : 'Choose_a_unit_to_exhaust';
    SWUQueueChooseTarget(intval($player), $unitList, $tooltip, $handler);
};
