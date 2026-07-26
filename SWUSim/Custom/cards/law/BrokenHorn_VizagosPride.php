<?php
// LAW_083
// Cost 5 - Broken Horn - Vizago's Pride - [Aggression,Cunning] - Power 5 - HP 4
// Text: When Played: If you have fewer cards in hand than an opponent, draw a card. If you control fewer resources than an opponent, resource the top card of your deck.

// LAW_083 Broken Horn — When Played: if you have fewer cards in hand than an opponent, draw a card; if
// you control fewer resources than an opponent, resource the top card of your deck (exhausted).
$whenPlayedAbilities["LAW_083:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $opp = OtherPlayer(intval($player));
    $myHand  = count(array_filter(GetHand(intval($player)), fn($c) => empty($c->removed)));
    $oppHand = count(array_filter(GetHand($opp),            fn($c) => empty($c->removed)));
    if ($myHand < $oppHand) DoDrawCard(intval($player), 1);
    if (SWUResourceCount(intval($player)) < SWUResourceCount($opp)) {
        DecisionQueueController::CleanupRemovedCards();   // the draw left the milled top as a removed entry
        $deck = ZoneSearch("myDeck", null);
        if (!empty($deck)) {
            $r = MZMove(intval($player), $deck[0], "myResources");
            if ($r !== null) { $r->Status = 0; $r->Owner = intval($player); $r->Controller = intval($player); }
            SWUKeepCreditTokensLast(intval($player));
        }
    }
};
