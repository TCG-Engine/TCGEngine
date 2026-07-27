<?php
// LAW_113
// Cost 1 - Shield Drive Outfitter - [Vigilance] - Power 1 - HP 3
// Text: When Played: You may pay 1 resource. If you do, give a Shield token to a unit.

// LAW_113 Shield Drive Outfitter — When Played: you may pay 1 resource. If you do, give a Shield token
// to a unit.
$whenPlayedAbilities["LAW_113:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (SWUResourceCount(intval($player), readyOnly: true) < 1) return;
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Pay_1_resource_to_give_a_Shield_token_to_a_unit?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_113#0", 1);
};

$customDQHandlers["LAW_113#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    if (!SWUExhaustResources(intval($player), 1)) return;
    GiveTokenUpgrade($player, '', ['token'=>'SHIELD','friendlyOnly'=>false,'prompt'=>"Give_a_Shield_token_to_a_unit"]);
};
