<?php
// LAW_045
// Cost 5 - Zeb Orellios - Spectre Four - [Vigilance,Aggression,Heroism] - Power 4 - HP 4
// Text: Sentinel / When Played: You may deal 3 damage to a ground unit. If you control a Command or Cunning unit, you may deal 5 damage to a ground unit instead.

// LAW_045 Zeb Orellios — Sentinel + When Played: deal 3 to a ground unit (5 instead if you control a
// Command or Cunning unit). "You may deal."
$whenPlayedAbilities["LAW_045:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $amount = (PlayerHasUnitWithAspectInPlay(intval($player), 'Command') || PlayerHasUnitWithAspectInPlay(intval($player), 'Cunning')) ? 5 : 3;
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => $amount, 'arena' => 'Ground', 'may' => true,
        'question' => "Deal_{$amount}_to_a_ground_unit?", 'prompt' => "Deal_{$amount}_damage_to_a_ground_unit",
    ]);
};
