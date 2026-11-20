<?php
// IC27_138
// Cost 2 - Banshee - Scream Machine - [Cunning,Villainy] - Unit (Space) 2/3 (unique)
//   Traits: Separatist, Vehicle, Transport
// Text: On Attack: You may search the top 5 cards of your deck for a non-unit card, reveal it, and put it on top
//       of your deck. (Put the other cards on the bottom of your deck in a random order.)
//
// The "may" covers the whole SEARCH, so it is asked first as a YESNO: declining leaves the top five where they
// are, while searching and choosing nothing (CR 8.26.1) still bottoms all five — two different decks, so the
// decline cannot be folded into an empty pick. "your deck" is the CONTROLLER's. A Pilot is a unit outside play
// (CR 7.5.17), so the CardType filter already excludes it. The pick is finished by LOF_103 Following the Path's
// shared handler: the chosen card goes on top, revealed, and the rest to the bottom in random order.
$onAttackAbilities["IC27_138:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // CR 8.6.2: searching an empty deck is ignored, so there is nothing to offer.
    $live = array_filter(GetDeck(intval($player)), fn($c) => empty($c->removed));
    if (empty($live)) return;
    DecisionQueueController::AddDecision(intval($player), 'YESNO', '-', 0,
        tooltip: "Search_the_top_5_cards_of_your_deck_for_a_non-unit_card?");
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', "IC27_138#0", 0);
};

$customDQHandlers["IC27_138#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;              // declining searches nothing
    global $playerID; $playerID = intval($player);
    _topDeckSearchBegin(intval($player), 5,
        fn($c) => stripos(CardType($c) ?? '', 'Unit') === false, "count:1", "LOF_103#0",
        'non-unit cards', 'Put on top');
};
