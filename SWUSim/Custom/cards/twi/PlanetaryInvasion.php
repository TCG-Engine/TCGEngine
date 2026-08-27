<?php
// TWI_178
// Cost 12 - Planetary Invasion - [Aggression]
// Text: Exploit 3 / Ready up to 3 units. Each of those units gets +1/+0 and gains Overwhelm for this phase.

// TWI_178 Planetary Invasion — ready each chosen unit + give it +1/+0 and Overwhelm for this phase.
$customDQHandlers["TWI_178#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $picks = ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS')
        ? [] : array_values(array_filter(explode('&', $lastDecision), fn($s) => $s !== '' && $s !== '-' && $s !== 'PASS'));
    foreach ($picks as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        OnReadyCard(intval($player), $mz);
        SWUApplyPhaseBuff($mz, 1, 0, '');          // +1/+0 this phase (generic source)
        AddTurnEffect($mz, 'TWI_178');             // grants Overwhelm this phase
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_178:0"] = function($player, $mzID = '') {
// Planetary Invasion — "Exploit 3. Ready up to 3 units. Each of those units
                          // gets +1/+0 and gains Overwhelm for this phase."
            global $playerID;
            $playerID = intval($player);
            $specs = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) $specs[] = $mz;
                }
            }
            if (empty($specs)) return;
            $max = min(3, count($specs));
            DecisionQueueController::AddDecision(intval($player), 'MZMULTICHOOSE',
                "0|{$max}|" . implode('&', $specs), 1, tooltip: 'Ready_up_to_3_units_(+1/+0_and_Overwhelm_this_phase)');
            DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'TWI_178#0', 1, dontSkipOnPass: 1);
            return;
};
