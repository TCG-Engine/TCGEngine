<?php
// JTL_072
// Cost 6 - Wing Guard Security Team - [Vigilance] - Power 4 - HP 4
// Text: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.) / When Played: Give a Shield token to each of up to 2 Fringe units.

// ── JTL_072 Wing Guard Security Team — Sentinel (auto) + When Played: Give a Shield to each of up to 2
// Fringe units. ──────────────────────────────────────────────────────────────────────────────────────
$whenPlayedAbilities["JTL_072:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && HasTrait($o->CardID, 'Fringe')) $targets[] = $mz;
    }
    if (empty($targets)) return;
    $max = min(2, count($targets));
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE",
        "0|" . $max . "|" . implode("&", $targets), 1, tooltip: "Give_a_Shield_to_up_to_2_Fringe_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_072#0", 1, dontSkipOnPass: 1);
};

$customDQHandlers["JTL_072#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $picks = array_slice(array_filter(explode("&", $lastDecision), fn($m) => $m !== '' && $m !== '-'), 0, 2);
    foreach ($picks as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) GiveShieldToken(intval($player), $mz);
    }
};
