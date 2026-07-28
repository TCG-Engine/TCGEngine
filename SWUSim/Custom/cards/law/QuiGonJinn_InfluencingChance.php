<?php
// LAW_237
// Cost 4 - Qui-Gon Jinn - Influencing Chance - [Cunning] - Power 3 - HP 5
// Text: Sentinel / When Played/On Attack: Look at the top 3 cards of your deck. You may discard 1 of them. Put the rest back on top in any order.

$customDQHandlers["LAW_237#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cardID = $o->CardID;
    $o->removed = true;
    DecisionQueueController::CleanupRemovedCards();
    SWUAddToDiscard(intval($player), $cardID, 'DECK');
};

// LAW_237 Qui-Gon Jinn — Sentinel + When Played/On Attack: look at the top 3, you may discard 1, put
// the rest back on top.
$law237 = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  $deck = ZoneSearch("myDeck", null);
  if (empty($deck))
    return;
  $top = array_slice($deck, 0, 3);
  AddGameLogEntry('REVEAL', 'P' . intval($player) . ' looked at the top ' . count($top) . ' cards of their deck');
  SWUQueueMayChooseTarget(intval($player), $top, "Discard_1_of_the_top_3_cards?", "Choose_a_card_to_discard", "LAW_237#0");
};

$whenPlayedAbilities["LAW_237:0"] = $law237;

$onAttackAbilities["LAW_237:0"] = $law237;
