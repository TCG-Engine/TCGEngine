<?php
// IC27_110
// Cost 10 - Supremacy - Destroy the Resistance - [Aggression,Villainy] - Unit (Space) 10/10 (unique)
//   Traits: First Order, Vehicle, Capital Ship
// Text: When Played: Deal damage to a base equal to half its remaining HP, rounded down.
//
// "a base" has no qualifier: every live seat's base is offered, the caster's own included (SWUOfferBaseTarget
// 'any'). The pick is mandatory. The amount is computed at RESOLUTION from the chosen base's remaining HP —
// SWUBaseRemainingHp reads the base's real max HP (a 34-HP base gives 17, not 15) minus its damage — and
// rounded down, so a base on 1 remaining takes 0. The dealing player is the caster.
// Tests: SWUSim/Tests/Cases/ic27/Supremacy_DestroyTheResistance.md
$whenPlayedAbilities["IC27_110:0"] = function($player, $mzID) {
    SWUOfferBaseTarget(intval($player), ['baseSide' => 'any', 'continuation' => 'IC27_110#0',
        'prompt' => 'Deal_damage_to_a_base_equal_to_half_its_remaining_HP']);
};

$customDQHandlers["IC27_110#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $tp = SWUMzOwner((string)$lastDecision, intval($player));
    if ($tp <= 0) return;
    $amount = intdiv(SWUBaseRemainingHp($tp), 2);
    if ($amount > 0) SWUDealDamageToBase($amount, $tp, intval($player));
};
