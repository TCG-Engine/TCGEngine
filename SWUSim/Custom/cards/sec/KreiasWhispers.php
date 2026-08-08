<?php
// SEC_232
// Cost 2 - Kreia's Whispers - [Cunning]
// Text: Draw 3 cards, then put a card from your hand on the top of your deck and another card from your hand on the bottom of your deck.

// Live (non-removed) hand mzIDs. ZoneSearch("myHand", null) also returns entries already marked REMOVED,
// which here means (a) the in-flight Kreia's Whispers itself — ActivateCard Removes the event to the
// discard BEFORE dispatching its When Played — and (b) the card already placed on TOP by handler #0.
// Offering either one is wrong: picking the removed event silently placed nothing, and the top-picked
// card reappeared as a legal choice for the BOTTOM placement. Same in-flight-event family as SEC_178
// Pursue the Lead.
function KreiasWhispersLiveHand(int $player): array {
    global $playerID; $playerID = intval($player);
    return array_values(array_filter(ZoneSearch("myHand", null), function($mz) {
        $o = GetZoneObject($mz); return $o !== null && empty($o->removed);
    }));
}

$customDQHandlers["SEC_232#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') KreiasWhispersMoveHandToDeck(intval($player), $lastDecision, true);
    $hand = KreiasWhispersLiveHand(intval($player));
    if (empty($hand)) return;
    SWUQueueChooseTarget(intval($player), $hand, "Put_a_card_on_the_BOTTOM_of_your_deck", "SEC_232#1");
};

$customDQHandlers["SEC_232#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') KreiasWhispersMoveHandToDeck(intval($player), $lastDecision, false);
};

// SEC_232 Kreia's Whispers (event) — Draw 3 cards, then put a card from your hand on TOP of your deck
// and another from your hand on the BOTTOM of your deck. _SEC232MoveHandToDeck moves a hand card to the
// deck without firing leave-play reactions (SWUUnitToBottomOfDeck is for units leaving play).
function KreiasWhispersMoveHandToDeck(int $player, string $mzID, bool $toTop): void
{
  global $playerID;
  $playerID = intval($player);
  $o = GetZoneObject($mzID);
  if (SWUObjGone($o))
    return;
  $cid = $o->CardID;
  $owner = intval($o->Owner ?? $player);
  $o->Remove();
  $deck = &GetDeck($owner);
  $d = new Deck($cid, 'Deck', $owner);
  if ($toTop)
    array_unshift($deck, $d);
  else
    array_push($deck, $d);
  foreach ($deck as $i => $c) {
    $c->mzIndex = $i;
  }
}

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_232:0"] = function($player, $mzID = '') {
// Kreia's Whispers — Draw 3, then put a card from hand on TOP of your deck and
                          // another on the BOTTOM of your deck.
            global $playerID; $playerID = intval($player);
            DoDrawCard(intval($player), 3);
            $hand = KreiasWhispersLiveHand(intval($player));
            if (empty($hand)) return;
            SWUQueueChooseTarget(intval($player), $hand, "Put_a_card_on_TOP_of_your_deck", "SEC_232#0");
            return;
};
