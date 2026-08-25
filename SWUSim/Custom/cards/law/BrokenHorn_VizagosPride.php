<?php
// LAW_083
// Cost 5 - Broken Horn - Vizago's Pride - [Aggression,Cunning] - Power 5 - HP 4
// Text: When Played: If you have fewer cards in hand than an opponent, draw a card. If you control fewer resources than an opponent, resource the top card of your deck.

// LAW_083 Broken Horn — When Played: if you have fewer cards in hand than an opponent, draw a card; if
// you control fewer resources than an opponent, resource the top card of your deck (exhausted).
$whenPlayedAbilities["LAW_083:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // "AN opponent" in a CONDITION is EXISTENTIAL — true if ANY live opponent qualifies — not a target
    // to be chosen. This card must therefore NEVER prompt for a seat; adding a picker would be its own
    // I1 violation (a prompt Premier must never see). OtherPlayer() interrogated exactly one seat, so
    // above two seats two of the three opponents were invisible to the test, and from seats 2/3/4 only
    // seat 1 was ever compared. OpponentsOf() also filters to LIVE seats, so an eliminated seat's
    // abandoned board cannot satisfy the condition.
    // ⚠ TWO INDEPENDENT existentials: "fewer cards than an opponent" and "fewer resources than an
    // opponent" are separate tests and may be satisfied by DIFFERENT opponents. Comparing both against
    // one seat coupled them together, which is wrong even in spirit.
    $opps    = OpponentsOf(intval($player));
    $myHand  = count(array_filter(GetHand(intval($player)), fn($c) => empty($c->removed)));
    $maxHand = 0;
    foreach ($opps as $o) $maxHand = max($maxHand, count(array_filter(GetHand($o), fn($c) => empty($c->removed))));
    if ($myHand < $maxHand) DoDrawCard(intval($player), 1);
    $maxRes = 0;
    foreach ($opps as $o) $maxRes = max($maxRes, SWUResourceCount($o));
    if (SWUResourceCount(intval($player)) < $maxRes) {
        DecisionQueueController::CleanupRemovedCards();   // the draw left the milled top as a removed entry
        $deck = ZoneSearch("myDeck", null);
        if (!empty($deck)) {
            $r = MZMove(intval($player), $deck[0], "myResources");
            if ($r !== null) { $r->Status = 0; $r->Owner = intval($player); $r->Controller = intval($player); }
            SWUKeepCreditTokensLast(intval($player));
        }
    }
};
