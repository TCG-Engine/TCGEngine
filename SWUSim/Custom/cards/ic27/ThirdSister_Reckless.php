<?php
// IC27_102
// Cost 3 - Third Sister - Reckless - [Aggression,Villainy] - Unit (Ground) 3/5 (unique)
//   Traits: Force, Imperial, Inquisitor
// Text: On Attack: Deal 1 damage to a friendly ground unit and 1 damage to an enemy ground unit.
//
// PREVIEW card (no official rulings). The released analogue is SOR_033 Death Trooper — the same sentence with 2,
// as a When Played — and this reuses its shape:
//   • Both halves are MANDATORY (no "you may"). The friendly pick is offered first; the enemy half is queued as a
//     CUSTOM continuation, so it resolves AFTER the friendly pick and still fires when the friendly pool is empty
//     ("and" is not "if you do").
//   • No "another": Third Sister is a legal friendly ground target herself, and Death Trooper's ruling (the only
//     friendly unit "must be chosen") carries over — a lone pick auto-resolves onto her.
//   • 'friendly' is TEAM-wide (Team Suns teammates included); 'their' is every opponent (OpponentsOf), so a
//     Twin Suns far seat and both Team Suns enemies are in the enemy pool, a teammate is not.
//   • It resolves before combat damage, so pinging a 1-HP defender defeats it and no combat damage is dealt.
//   • The damage is left source-less, like Death Trooper and IC27_005 Maul: no source reader in the card pool can
//     tell Third Sister apart here.
// Tests: SWUSim/Tests/Cases/ic27/ThirdSister_Reckless.md

$onAttackAbilities["IC27_102:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 1, 'side' => 'friendly', 'arena' => 'Ground',
        'prompt' => "Deal_1_damage_to_a_friendly_ground_unit",
    ]);
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "IC27_102#0", 1);
};

$customDQHandlers["IC27_102#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 1, 'side' => 'their', 'arena' => 'Ground',
        'prompt' => "Deal_1_damage_to_an_enemy_ground_unit", 'block' => 0,
    ]);
};
