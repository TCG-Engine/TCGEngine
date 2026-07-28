<?php
// JTL_063
// Cost 3 - Landing Shuttle - [Vigilance] - Power 2 - HP 4
// Text: When Defeated: You may draw a card.

// ── JTL_063 Landing Shuttle — When Defeated: You may draw a card. ─────────────────────────────────────
$whenDefeatedAbilities["JTL_063:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, tooltip: "Draw_a_card?");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_063#0', 1);
};

$customDQHandlers["JTL_063#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    DoDrawCard(intval($player), 1);
};
