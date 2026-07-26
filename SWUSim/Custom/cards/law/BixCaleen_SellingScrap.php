<?php
// LAW_236
// Cost 4 - Bix Caleen - Selling Scrap - [Cunning] - Power 4 - HP 5
// Text: When Played/On Attack: You may discard a card from your hand. If you do, create a Credit token.

$customDQHandlers["LAW_236#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    DoDiscardCard(intval($player), $lastDecision);
    SWUCreateCreditToken(intval($player), 1);
};

// LAW_236 Bix Caleen — When Played/On Attack: you may discard a card from your hand. If you do, create
// a Credit token.
$law236 = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  $hand = ZoneSearch("myHand");
  if (empty($hand))
    return;
  SWUQueueMayChooseTarget(intval($player), $hand, "Discard_a_card_from_your_hand_to_create_a_Credit?", "Choose_a_card", "LAW_236#0");
};

$whenPlayedAbilities["LAW_236:0"] = $law236;

$onAttackAbilities["LAW_236:0"] = $law236;
