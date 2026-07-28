<?php
// LOF_167
// Cost 5 - Saesee Tiin - Courageous Warrior - [Aggression] - Power 4 - HP 6
// Text: When Played: If you have the initiative, deal 1 damage to each of up to 3 units.

// LOF_167 Saesee Tiin — When Played: if you have the initiative, deal 1 damage to each of up to 3 units.
$whenPlayedAbilities["LOF_167:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (!PlayerHasIniative(intval($player))) return;
    $targets = array_values(SWUAllUnits());
    if (empty($targets)) return;
    $max = min(3, count($targets));
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|{$max}|" . implode('&', $targets), 1,
        tooltip: "Deal_1_damage_to_each_of_up_to_3_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_167#0", 1);
};

$customDQHandlers["LOF_167#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID; $playerID = intval($player);
    foreach (explode('&', $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        SWUDealDamageToUnit($mz, 1, intval($player));
    }
};
