<?php
// ASH_014
// Cost 6 - The Mandalorian - We Can't Keep Running - [Aggression,Heroism] - Power 4 - HP 6
// Text: When you take the initiative: You may pay 1 resource. If you do, draw a card.
// DeployText: Support (When you deploy this leader, you may attack with another unit. It gains this unit's other abilities for this attack.) / On Attack: If you have the initiative, you may draw a card.
// Epic Action: If you control 6 or more resources, deploy this leader.

// ASH_014 The Mandalorian — if you have the initiative, may draw a card.
$onAttackAbilities["ASH_014:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (!PlayerHasIniative(intval($player))) return;
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Draw_a_card?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_014#1", 1);
};

$customDQHandlers["ASH_014#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    DoDrawCard(intval($player), 1);
};

// ASH_014 The Mandalorian — "When you take the initiative: may pay 1 resource → draw a card." (Hooked in
// SWUTakeInitiative; this resolves the pay-and-draw on YES.)
$customDQHandlers["ASH_014#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    if (!SWUPayInlineAbilityCost(intval($player), 1)) return;   // pay 1 resource
    DoDrawCard(intval($player), 1);
};
