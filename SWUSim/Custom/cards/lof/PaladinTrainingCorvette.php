<?php
// LOF_099
// Cost 5 - Paladin Training Corvette - [Command,Heroism] - Power 3 - HP 5
// Text: When Played: You may give an Experience token to each of up to 3 Force units.

// LOF_099 Paladin Training Corvette — When Played: may give an Experience token to each of up to 3 Force units.
$whenPlayedAbilities["LOF_099:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && TraitContains($o, 'Force')) $targets[] = $mz;
    }
    if (empty($targets)) return;
    $max = min(3, count($targets));
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|{$max}|" . implode('&', $targets), 1,
        tooltip: "Give_an_Experience_token_to_each_of_up_to_3_Force_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_099#0", 1);
};

$customDQHandlers["LOF_099#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID; $playerID = intval($player);
    foreach (explode('&', $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        DoGiveExperienceToken(intval($player), $mz);
    }
};
