<?php
// LAW_125
// Cost 1 - Watchful - [Vigilance] - Upgrade Power 0 - Upgrade HP 2
// Text: Attached unit gains: "On Attack: Look at the top card of a deck. You may put it on the bottom of that deck. (Otherwise, leave it on top.)"

// LAW_125 Watchful — granted "On Attack: Look at the top card of a deck. You may put it on the bottom
// of that deck." Choose which deck, look at its top, then optionally bottom it. (OnAttackFromUpgrade
// seam fires this with the HOST mzID when the host attacks.)
$onAttackAbilities["LAW_125:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // ⚠ An EMPTY deck is not a legal pick — there is no top card to look at. Same gap as LAW_018, and
    // the same fix: one eligibility list drives the fizzle / auto-resolve / offer branches at EVERY seat
    // count, so the old `SeatCountForGame() <= 2` short-cuts are subsumed rather than duplicated.
    $decks = SWUSeatsWithNonEmptyDeck(intval($player));
    if (empty($decks)) return;
    if (count($decks) === 1) { WatchfulPeek(intval($player), $decks[0]); return; }
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE",
        "@-&" . SWUDeckPickerLabels(intval($player), "Your_deck&Opponent's_deck", $decks), 1, "Look_at_the_top_card_of_which_deck?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_125#0", 1);
};

$customDQHandlers["LAW_125#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $owner = SWUDecodeDeckPick($lastDecision, intval($player)); // Your_deck→self, Opponent's_deck/P{n}_deck→that player
    WatchfulPeek(intval($player), $owner);
};

// $parts[0] = the deck owner. On "Bottom", move that deck's top card to its bottom.
$customDQHandlers["LAW_125#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision !== 'Bottom') return;
    $owner = intval($parts[0] ?? $player);
    $idx   = _SWUTopDeckFrontIdx($owner);
    if ($idx === -1) return;
    $deck  = &GetDeck($owner);
    $topID = $deck[$idx]->CardID;
    $deck[$idx]->removed = true;
    DecisionQueueController::CleanupRemovedCards();
    _topDeckPutRemainingToBottom($owner, [$topID]);
};

// LAW_125 Watchful helper — $looker looks at the top card of $deckOwner's deck and may bottom it.
function WatchfulPeek(int $looker, int $deckOwner): void
{
  $idx = _SWUTopDeckFrontIdx($deckOwner);
  if ($idx === -1)
    return;
  $topID = GetDeck($deckOwner)[$idx]->CardID;
  DecisionQueueController::AddDecision($looker, "OPTIONCHOOSE", "@{$topID}&Bottom&Leave", 1, "Put_the_top_card_on_the_bottom_of_that_deck?");
  DecisionQueueController::AddDecision($looker, "CUSTOM", "LAW_125#1|{$deckOwner}", 1);
}
