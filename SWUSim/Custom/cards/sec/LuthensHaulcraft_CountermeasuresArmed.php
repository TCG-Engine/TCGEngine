<?php
// SEC_153
// Cost 5 - Luthen's Haulcraft - Countermeasures Armed - [Aggression,Heroism] - Power 5 - HP 3
// Text: When Defeated: You may choose an opponent and disclose AggressionAggressionHeroism (reveal cards from your hand with these aspect icons among them). If you do, that opponent discards 2 cards from their hand.

// SEC_153 Luthen's Haulcraft — When Defeated: you may choose an opponent and disclose
// AggressionAggressionHeroism → that opponent discards 2 cards. (2-player: the one opponent.)
$whenDefeatedAbilities["SEC_153:0"] = function($player, $mzID) {
    SWUQueueDisclose(intval($player), ['Aggression', 'Aggression', 'Heroism'], "SEC_153#0",
        "Disclose_AggressionAggressionHeroism_to_make_an_opponent_discard_2");
};

$customDQHandlers["SEC_153#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    // The card says "you may CHOOSE AN OPPONENT and disclose … If you do, THAT OPPONENT discards 2", so
    // the pick is part of the cost. It is taken AFTER the disclose purely for plumbing (SWUQueueDisclose
    // owns the reveal), which is not observable: both are prerequisites of the same "if you do".
    $eligible = SWUOpponentsWithCards(intval($player));
    if (empty($eligible)) return;               // nobody holds a card — nothing to make anyone discard
    SWUQueueChooseOpponent(intval($player), "SEC_153#1", "Which_opponent_discards_2_cards?", $eligible);
};

$customDQHandlers["SEC_153#1"] = function($player, $parts, $lastDecision) {
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0) return;
    SWUDiscardCards(intval($player), 2, $opp);
};
