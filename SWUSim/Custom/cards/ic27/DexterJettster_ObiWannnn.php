<?php
// IC27_049
// Cost 3 - Dexter Jettster - Obi-Wannnn!!! - [Vigilance] - Unit (Ground) 2/6 (unique)
//   Traits: Fringe
// Text: On Attack: You may pay [1 resource]. If you do, look at the top 3 cards of your deck. Put any number of
//       them on the bottom of your deck and the rest on top in any order.
//
// IC27_145 Poggle's pay shape: YESNO -> SWUPayInlineAbilityCost (Credits / SEC_122 Droids pay as a last resort) ->
// DoScry(3), the "look at the top N, any number to the bottom, rest on top in any order" primitive (it clamps to
// the deck size). The offer is raised only when it can buy something: a payment capacity of 1 AND a card in the
// deck — otherwise accepting could only spend a resource for nothing.
// Tests: SWUSim/Tests/Cases/ic27/DexterJettster_ObiWannnn.md
$onAttackAbilities["IC27_049:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (SWUTotalPaymentCapacity(intval($player)) < 1) return;
    if (count(array_filter(GetDeck(intval($player)), fn($c) => !SWUObjGone($c))) === 0) return;
    DecisionQueueController::AddDecision(intval($player), 'YESNO', '-', 1,
        tooltip: "Pay_1_to_look_at_the_top_3_cards_of_your_deck?");
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'IC27_049#0', 1);
};

$customDQHandlers["IC27_049#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    if (!SWUPayInlineAbilityCost(intval($player), 1)) return;
    DoScry(intval($player), 3);
};
