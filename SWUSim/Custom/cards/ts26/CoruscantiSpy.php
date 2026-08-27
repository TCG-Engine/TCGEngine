<?php
// TS26_53
// Cost 1 - Coruscanti Spy - [Command] - Power 0 - HP 2
// Text: Raid 2 (This unit gets +2/+0 while attacking.) / When Played: Heal 2 damage from each of any number of bases.

// TS26_53 Coruscanti Spy — Raid 2 (auto). When Played: heal 2 damage from each of any number of bases.
$whenPlayedAbilities["TS26_53:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|2|myBase-0&theirBase-0", 1,
        tooltip: "Heal_2_from_each_of_any_number_of_bases");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "TS26_53#0", 1, dontSkipOnPass: 1);
};

$customDQHandlers["TS26_53#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '' || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID; $playerID = intval($player);
    // ⚠ Owner decoded from the mzID — a Twin Suns "p{n}Base-0" pick matched neither literal and was
    // silently skipped.
    foreach (explode('&', $lastDecision) as $b) {
        if (strpos($b, 'Base') === false) continue;
        OnHealBase(intval($player), SWUMzOwner($b, intval($player)), 2);
    }
};
