<?php
// JTL_233
// Cost 3 - Sweep the Area - [Cunning]
// Text: Return up to 2 non-leader units in the same arena with a combined cost 3 or less to their owners' hands.

// ── JTL_233 Sweep the Area (event continuation) — return up to 2 non-leader units in the SAME arena with
// combined printed cost <= 3 to their owners' hands. Validates the picks (the harness doesn't). ───────
$customDQHandlers["JTL_233#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $picks = [];
    foreach (explode("&", $lastDecision) as $mz) {
        $mz = trim($mz);
        if ($mz === '' || $mz === '-') continue;
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || IsLeaderUnit($o)) continue;
        $arena = (strpos($mz, 'Space') !== false) ? 'Space' : 'Ground';
        $picks[] = ['uid' => intval($o->UniqueID ?? 0), 'arena' => $arena, 'cost' => intval(CardCost($o->CardID))];
    }
    $picks = array_slice($picks, 0, 2);
    if (empty($picks)) return;
    // Validate: all in the same arena and combined cost <= 3.
    $arena0 = $picks[0]['arena']; $total = 0;
    foreach ($picks as $p) { if ($p['arena'] !== $arena0) return; $total += $p['cost']; }
    if ($total > 3) return;
    foreach ($picks as $p) {
        $mz = SWUFindMzByUID($p['uid']);
        if ($mz !== null && $mz !== '') SWUBounceUnit(intval($player), $mz);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_233:0"] = function($player, $mzID = '') {
// Sweep the Area — return up to 2 non-leader units in the same arena with combined
                          // cost <= 3 to their owners' hands (continuation JTL_233 validates).
            global $playerID;
            $playerID = intval($player);
            $targets = SWUAllUnits(null, null, NonLeaderUnitFilter);
            if (empty($targets)) return;
            DecisionQueueController::AddDecision($player, "MZMULTICHOOSE",
                "0|2|" . implode("&", $targets), 1, tooltip: "Return_up_to_2_same-arena_units_(combined_cost_3_or_less)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_233#0", 1);
            return;
};
