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

// LAW_237 Qui-Gon Jinn — Sentinel + When Played/On Attack: look at the top 3, you may discard 1, put the rest
// back on top IN ANY ORDER.
// ⚠ ONE REVEALARRANGE step with a discard limit of 1 ("ids|1"): each card goes back on top in the order the player
// clicks, or — at most one — to the discard pile. Bug report 2026-10-01 ("not letting me choose the order to put
// back on top"): the old flow offered only the optional discard and never the reorder, so the rest stayed in
// deck order. The panel shows the CARDS (the live bug #962 was a prompt over the stacked deck pile showing a bare
// count); the limit is enforced by the client panel, the answer validator and REVEALARRANGE_FINALIZE alike.
// ⚠ Deliberately NOT routed through _topDeckSearchBegin: that funnel applies ASH_084 Arcana Star Map's
// "search twice that many" doubler, and this is a LOOK, not a search (LookNotDoubledByDeckSearchDoubler).
// LAW_237#0 above is the PREVIOUS flow's continuation, kept so a game saved mid-prompt still resolves.
$law237 = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  DecisionQueueController::CleanupRemovedCards();
  $deck = GetDeck($player);
  $ids = [];
  foreach ($deck as $c) {
    if (!empty($c->removed)) continue;
    $ids[] = (string)$c->CardID;
    if (count($ids) === 3) break;
  }
  if (empty($ids)) return;
  AddGameLogEntry('REVEAL', 'P' . intval($player) . ' looked at the top ' . count($ids) . ' cards of their deck');
  DecisionQueueController::AddDecision($player, "REVEALARRANGE", implode(',', $ids) . '|1', 1,
      "Discard_up_to_1_then_put_the_rest_back_on_top_in_any_order");
  DecisionQueueController::AddDecision($player, "CUSTOM", "REVEALARRANGE_FINALIZE|" . count($ids) . "|1", 1);
  MarkUndoRequiresConsent();
};

$whenPlayedAbilities["LAW_237:0"] = $law237;

$onAttackAbilities["LAW_237:0"] = $law237;
