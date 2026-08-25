<?php
// TS26_60
// Cost 3 - Take Charge - [Command]
// Text: This event costs 1 resource less to play for each friendly leader unit. / Give an Experience token to each of up to 3 units.

// TS26_60 Take Charge — give an Experience token to each chosen unit (up to 3).
$customDQHandlers["TS26_60#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '' || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID; $playerID = intval($player);
    foreach (explode('&', $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        DoGiveExperienceToken(intval($player), $mz);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TS26_60:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $tg = SWUAllUnits();
    if (empty($tg)) return;
    $max = min(3, count($tg));
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|{$max}|" . implode('&', $tg), 1,
        tooltip: "Give_Experience_to_up_to_3_units");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "TS26_60#0", 1);
};
