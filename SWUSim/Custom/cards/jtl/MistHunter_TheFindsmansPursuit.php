<?php
// JTL_186
// Cost 3 - Mist Hunter - The Findsman's Pursuit - [Cunning,Villainy] - Power 3 - HP 4
// Text: On Attack: If you played a Bounty Hunter or Pilot card this phase, you may draw a card.

// JTL_186 Mist Hunter — On Attack: If you played a Bounty Hunter or Pilot card this phase, may draw a card.
$onAttackAbilities["JTL_186:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_PLAYED_BOUNTYHUNTER') <= 0
        && GlobalEffectCount(intval($player), 'SWU_PLAYED_PILOT') <= 0) return;
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, tooltip: "Draw_a_card?");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_186#0', 1);
};

$customDQHandlers["JTL_186#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    DoDrawCard(intval($player), 1);
};
