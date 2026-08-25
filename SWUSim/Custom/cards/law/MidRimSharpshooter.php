<?php
// LAW_193
// Cost 3 - Mid Rim Sharpshooter - [Aggression] - Power 3 - HP 3
// Text: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / When Played: You may pay 1 resource. If you do, an opponent discards a card from their hand.

// LAW_193 Mid Rim Sharpshooter — Saboteur + When Played: you may pay 1 resource. If you do, an opponent
// discards a card from their hand.
$whenPlayedAbilities["LAW_193:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (SWUTotalPaymentCapacity(intval($player)) < 1) return;
    // ⚠ NO empty-hand gate here, deliberately. A "you may pay N" whose effect fizzles is STILL OFFERED
    // and STILL COSTS — established project ruling (an action that fizzles pays its cost; ASH_004 Thrawn
    // uses it as a soft pass), pinned by PayOpponentEmptyHandStillPays below. This is NOT the
    // fizzle-only-offer rule, which is about an optional clause whose TARGET POOL is empty: here there is
    // no target to choose among, only an effect that may find nothing to do.
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Pay_1_resource_to_make_an_opponent_discard_a_card?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_193#0", 1);
};

$customDQHandlers["LAW_193#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    // PAY FIRST — the cost is owed for saying yes, whether or not anyone ends up discarding.
    if (!SWUPayInlineAbilityCost(intval($player), 1)) return;
    $eligible = SWUOpponentsWithCards(intval($player));
    if (empty($eligible)) return;   // resource spent, nobody holds a card — nothing to discard
    // "AN opponent discards" — the caster picks. Auto-resolves silently at one eligible opponent, so
    // 2-player is unchanged.
    SWUQueueChooseOpponent(intval($player), "LAW_193#1", "Which_opponent_discards_a_card?", $eligible);
};

$customDQHandlers["LAW_193#1"] = function($player, $parts, $lastDecision) {
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0) return;
    SWUDiscardCards(intval($player), 1, $opp);
};
