<?php
// LAW_069
// Cost 6 - The Ghost - Home of the Spectres - [Command,Cunning,Heroism] - Power 4 - HP 4
// Text: When Played: You may give an Experience token and a Shield token to a unit. If you control a Vigilance or Aggression unit, you may give an Experience token and a Shield token to each of up to 2 units instead.

// LAW_069 The Ghost — When Played: you may give an Experience + Shield token to a unit (to each of up
// to 2 units instead if you control a Vigilance or Aggression unit).
$whenPlayedAbilities["LAW_069:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $max = (PlayerHasUnitWithAspectInPlay(intval($player), 'Vigilance') || PlayerHasUnitWithAspectInPlay(intval($player), 'Aggression')) ? 2 : 1;
    $units = SWUAllUnits();
    if (empty($units)) return;
    $k = min($max, count($units));
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|{$k}|" . implode("&", $units), 1, tooltip: "Give_Experience_+_Shield_to_up_to_{$k}_unit(s)");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_069#0", 1, dontSkipOnPass: 1);
};

$customDQHandlers["LAW_069#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        DoGiveExperienceToken(intval($player), $mz);
        DoGiveShieldToken(intval($player), $mz);
    }
};
