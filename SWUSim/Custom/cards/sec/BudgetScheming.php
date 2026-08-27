<?php
// SEC_124
// Cost 2 - Budget Scheming - [Command]
// Text: Give an Experience token to each of up to 3 Official units.

// ── SEC Phase 5: Experience / buff ───────────────────────────────────────────
// SEC_124 Budget Scheming — give an Experience token to each chosen Official unit (up to 3).
$customDQHandlers["SEC_124#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    foreach (array_slice(array_values(array_filter(explode('&', (string)$lastDecision), fn($m) => $m !== '' && $m !== '-')), 0, 3) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) DoGiveExperienceToken(intval($player), $mz);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_124:0"] = function($player, $mzID = '') {
// Budget Scheming — "Give an Experience token to each of up to 3 Official units."
            global $playerID; $playerID = intval($player);
            $officials = [];
            foreach (SWUAllUnits() as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Official')) $officials[] = $mz;
            }
            if (empty($officials)) return;
            DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|3|" . implode('&', $officials), 1, tooltip: "Give_Experience_to_up_to_3_Official_units");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_124#0", 1, dontSkipOnPass: 1);
            return;
};
