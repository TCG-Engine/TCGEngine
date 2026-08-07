<?php
// TS26_77
// Cost 4 - Deployed Droideka - [Cunning] - Power 4 - HP 3
// Text: Ambush (When you play this unit, it may attack an enemy unit.) / When Played: You may pay 2 resources. If you do, give an Experience token and a Shield token to this unit.

// TS26_77 Deployed Droideka — Ambush (keyword). When Played: you may pay 2 resources; if you do, give an
// Experience token and a Shield token to this unit.
$whenPlayedAbilities["TS26_77:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (SWUTotalPaymentCapacity(intval($player)) < 2) return;   // can't pay → no offer
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1,
        tooltip: "Pay_2_resources_to_give_this_unit_an_Experience_and_a_Shield?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "TS26_77#0|" . $mzID, 1);
};

$customDQHandlers["TS26_77#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    SWUPayInlineAbilityCost(intval($player), 2);
    $mzID = $parts[0] ?? '';
    if ($mzID && str_contains($mzID, '-')) {
        DoGiveExperienceToken(intval($player), $mzID);
        DoGiveShieldToken(intval($player), $mzID);
    }
};
