<?php
// HMW_196
// Cost 1 - Qimir - Everyone Has a Weakness - [Cunning,Villainy] - Unit (Ground) 3/1 - Trait: Force
// Text: When Defeated: You may discard the top card of your deck. If it's not Villainy, give a
//       Weakness token to an enemy unit.

// ─── HMW_196 Qimir ─────────────────────────────────────────────────────────────────────────────────
// Two clauses with one "may", and it attaches to the DISCARD. The Weakness that follows is mandatory
// once its condition holds, so its target is an MZCHOOSE, not an MZMAYCHOOSE.
//
// ⚠ "an ENEMY unit" — narrower than its sibling HMW_059 Clone X Assassin, whose "a unit" spans both
// sides. One word apart in the same set, so this deliberately does NOT reuse HMW_059's
// GiveTokenUpgrade(friendlyOnly:false) call; it offers side 'their' explicitly.
//
// ⚠ The discard is not a COST. The second sentence is gated on "If it's NOT VILLAINY" — a condition on
// the discarded CARD — and never on "If you do", so it is offered even with no enemy unit in play: a
// player may want the top card in the discard pile for its own sake. Contrast LAW_257, whose optional
// half spends a RESOURCE and is correctly withheld when it could only fizzle. Pinned by
// Tests/Cases/hmw/Qimir_EveryoneHasAWeakness.md::NoEnemyUnit_TheDiscardStillHappens.
$whenDefeatedAbilities["HMW_196:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    // CANNOT-DO, not decline: an empty deck has nothing to discard, so the question is never asked.
    // Uses the same top-of-deck oracle SWUMillTopCard does, so the gate and the action cannot disagree.
    if (_SWUTopDeckFrontIdx(intval($player)) === -1) return;
    // A YESNO's prompt lives in the tooltip — a param prompt renders as the generic "choose Yes or No".
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1,
        tooltip: "Discard_the_top_card_of_your_deck?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_196#0", 1);
};

// Everything the continuation needs is derived AFTER the answer — which card came off the deck, its
// aspect, and the enemy pool — so nothing has to survive the request boundary in memory.
$customDQHandlers["HMW_196#0"] = function($player, $parts, $lastDecision) {
    if ((string)$lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $milled = SWUMillTopCard(intval($player));
    if ($milled === null) return;
    // "If it's not Villainy" reads the DISCARDED card's aspects. Villainy is an aspect, not a trait.
    if (strpos((string)(CardAspect($milled) ?? ''), 'Villainy') !== false) return;
    // Mandatory once we are here; side 'their' is the "an enemy unit" restriction. SWUOfferUnitTarget
    // no-ops on an empty pool, so no enemy unit is a clean fizzle of this clause alone.
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'GIVE_WEAKNESS',   // HMW_T02, a -1/-1 Token Upgrade; runs a shrink-defeat sweep
        'side'         => 'their',
        'prompt'       => 'Give_a_Weakness_token_to_an_enemy_unit',
    ]);
};
