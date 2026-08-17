<?php
// LAW_237
// Cost 4 - Qui-Gon Jinn - Influencing Chance - [Cunning] - Power 3 - HP 5
// Text: Sentinel / When Played/On Attack: Look at the top 3 cards of your deck. You may discard 1 of them. Put the rest back on top in any order.

// LAW_237 Qui-Gon Jinn — $lastDecision is a myTempZone-K spec (see the trigger below); K is the index
// into the top-of-deck slice, so the discarded card is the K-th deck entry. TempZone is drained on EVERY
// path, decline included — a staged card left behind would shift the next effect's indices.
$customDQHandlers["LAW_237#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $declined = SWUDecisionDeclined($lastDecision);
    $idx = -1;
    if (!$declined && preg_match('/-(\d+)$/', (string)$lastDecision, $m)) $idx = intval($m[1]);
    $temp = &GetTempZone($player);
    while (count($temp) > 0) array_pop($temp);
    if ($idx < 0) return;
    $deck = ZoneSearch("myDeck", null);
    if (!isset($deck[$idx])) return;
    $o = GetZoneObject($deck[$idx]);
    if (SWUObjGone($o)) return;
    $cardID = $o->CardID;
    $o->removed = true;
    DecisionQueueController::CleanupRemovedCards();
    SWUAddToDiscard(intval($player), $cardID, 'DECK');
};

// LAW_237 Qui-Gon Jinn — Sentinel + When Played/On Attack: look at the top 3, you may discard 1, put
// the rest back on top.
// ⚠ The peeked cards are STAGED INTO TempZone and the choice is offered over myTempZone-K, never over the
// deck's own myDeck-K mzIDs. `Deck` is declared `Display: Mode=Single(Stacked), BindTo=DeckSlot`, so a
// prompt pointing at it renders one stacked pile showing only its COUNT — the player sees a bare number
// and no cards (live bug report #962). TempZone is `Display: Mode=None`, which is what routes an MZCHOOSE
// spec to the card-image popup. Guarded by LookPromptOffersTheCARDS_NotTheDeckPile.
// ⚠ Deliberately NOT routed through _topDeckSearchBegin: that funnel applies ASH_084 Arcana Star Map's
// "search twice that many" doubler, and this is a LOOK, not a search (LookNotDoubledByDeckSearchDoubler).
$law237 = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  $deck = ZoneSearch("myDeck", null);
  if (empty($deck))
    return;
  $top = array_slice($deck, 0, 3);
  AddGameLogEntry('REVEAL', 'P' . intval($player) . ' looked at the top ' . count($top) . ' cards of their deck');
  $temp = &GetTempZone($player);
  while (count($temp) > 0) array_pop($temp);
  $tempMZs = [];
  foreach ($top as $k => $mz) {
    $o = GetZoneObject($mz);
    AddTempZone($player, $o->CardID ?? '');
    $tempMZs[] = "myTempZone-{$k}";
  }
  SWUQueueMayChooseTarget(intval($player), $tempMZs, "Discard_1_of_the_top_3_cards?", "Choose_a_card_to_discard", "LAW_237#0");
};

$whenPlayedAbilities["LAW_237:0"] = $law237;

$onAttackAbilities["LAW_237:0"] = $law237;
