<?php
// TS26_33
// Cost 3 - Kouhun Assassination - [Cunning,Vigilance,Villainy]
// Text: An opponent (of your choice) may discard a card from their hand. If they do, give a non-Vehicle unit -8/-8 for this phase.

// TS26_33 Kouhun Assassination — the opponent's discard choice ($player = opponent). If they discarded,
// the caster (parts[0]) gives a non-Vehicle unit -8/-8 for this phase.
// Runs on the CASTER's queue with the picked seat in $lastDecision. Hands the discard choice to THAT
// opponent, on their queue and in their frame.
$customDQHandlers["TS26_33#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? $player);
    $opp    = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === $caster) return;
    $playerID = $opp;
    $hand = ZoneSearch('myHand');   // chosen opponent's hand, in their frame
    if (empty($hand)) { $playerID = $caster; return; }
    SWUQueueMayChooseTarget($opp, $hand, "Discard_a_card?", "Choose_a_card_to_discard", "TS26_33#0|" . $caster);
};

$customDQHandlers["TS26_33#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? 0);
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    $playerID = intval($player);          // opponent frame — discard their chosen card
    DoDiscardCard(intval($player), $lastDecision);
    SWUOfferUnitTarget($caster, '', [   // caster picks the -8/-8 target
        'continuation' => 'APPLY_PHASE_DEBUFF|8|8|TS26_33', 'side' => 'any', 'notTraits' => ['Vehicle'],
        'prompt' => "Give_a_non-Vehicle_unit_-8/-8",
    ]);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TS26_33:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    // ⚠ "An opponent (OF YOUR CHOICE) may discard…" — the parenthesised phrase is NOT reminder text to be
    // stripped. It is the card explicitly settling the question the rest of this sweep has to infer: the
    // CASTER picks the opponent, then that opponent decides whether to discard.
    // FILTER: the chosen player is asked to DO something ("may discard a card from THEIR hand"), and an
    // empty-handed opponent cannot discard, cannot satisfy "if they do", and cannot enable the rider — a
    // choice among nothing. (Contrast TWI_222, where "if they DON'T" makes an empty hand a payoff.)
    $eligible = SWUOpponentsWithCards(intval($player));
    if (empty($eligible)) return;                 // nobody can discard ⇒ no offer at all
    SWUQueueChooseOpponent(intval($player), 'TS26_33#1|' . intval($player),
        "Choose_an_opponent_to_offer_the_discard", $eligible);
};
