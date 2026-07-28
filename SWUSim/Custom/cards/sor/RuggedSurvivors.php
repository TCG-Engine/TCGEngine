<?php
// SOR_067
// Cost 5 - Rugged Survivors - [Vigilance] - Power 3 - HP 5
// Text: Grit / On Attack: If you control a leader unit, you may draw a card.

// SOR_067 Rugged Survivors — On Attack: if you control a leader unit, you may draw a card.
// Grit is the auto-wired keyword (SOR_067 is in $Grit_Cards). The "may draw" is a same-player YESNO
// with no target, so it is safe queued inline in the OnAttack closure (no relative-mzID MZCountChoices).
$onAttackAbilities["SOR_067:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (!SWUControlsLeaderUnit(intval($player))) return;   // "if you control a leader unit"
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip: "Draw_a_card?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_067#0", 1);
};

$customDQHandlers["SOR_067#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;   // "you may" — decline draws nothing
    DoDrawCard(intval($player), 1);
};
