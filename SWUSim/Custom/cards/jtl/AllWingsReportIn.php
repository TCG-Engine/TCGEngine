<?php
// JTL_122
// Cost 1 - All Wings Report In - [Command]
// Text: Exhaust up to 2 friendly space units. For each unit exhausted this way, create an X-Wing token.

// ── JTL_122 All Wings Report In (event continuation) — exhaust each chosen space unit; create an
// X-Wing per unit exhausted. ─────────────────────────────────────────────────────────────────────────
$customDQHandlers["JTL_122#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $picks = array_slice(array_filter(explode("&", $lastDecision), fn($m) => $m !== '' && $m !== '-'), 0, 2);
    $count = 0;
    foreach ($picks as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->Status) !== 1) continue;
        OnExhaustCard(intval($player), $mz);
        $count++;
    }
    SWUCreateUnitTokens(intval($player), 'JTL_T02', $count);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_122:0"] = function($player, $mzID = '') {
// All Wings Report In — exhaust up to 2 friendly space units; for each unit
                          // exhausted this way, create an X-Wing token. (Continuation JTL_122.)
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (ZoneSearch("mySpaceArena", AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && intval($o->Status) === 1) $targets[] = $mz; // ready only
            }
            if (empty($targets)) return;
            $max = min(2, count($targets));
            DecisionQueueController::AddDecision($player, "MZMULTICHOOSE",
                "0|" . $max . "|" . implode("&", $targets), 1, tooltip: "Exhaust_up_to_2_friendly_space_units");
            DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_122#0", 1);
            return;
};
