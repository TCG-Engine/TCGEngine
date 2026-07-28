<?php
// JTL_051
// Cost 3 - Red Squadron X-Wing - [Vigilance,Heroism] - Power 3 - HP 4
// Text: When Played: You may deal 2 damage to this unit. If you do, draw a card.

// ── JTL_051 Red Squadron X-Wing — When Played: You may deal 2 damage to this unit. If you do, draw. ──
$whenPlayedAbilities["JTL_051:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1,
        tooltip: "Deal_2_damage_to_this_unit_to_draw_a_card?");
    DecisionQueueController::AddDecision($player, 'CUSTOM', "JTL_051#0|" . $mzID, 1);
};

$customDQHandlers["JTL_051#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    $mz = $parts[0] ?? '';
    if ($mz !== '') SWUDealDamageToUnit($mz, 2, intval($player));
    DoDrawCard(intval($player), 1);
};
