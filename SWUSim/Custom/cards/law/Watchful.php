<?php
// LAW_125
// Cost 1 - Watchful - [Vigilance] - Upgrade Power 0 - Upgrade HP 2
// Text: Attached unit gains: "On Attack: Look at the top card of a deck. You may put it on the bottom of that deck. (Otherwise, leave it on top.)"

// LAW_125 Watchful — granted "On Attack: Look at the top card of a deck. You may put it on the bottom
// of that deck." Choose which deck, look at its top, then optionally bottom it. (OnAttackFromUpgrade
// seam fires this with the HOST mzID when the host attacks.)
$onAttackAbilities["LAW_125:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $opp    = OtherPlayer(intval($player));
    $mine   = _SWUTopDeckFrontIdx(intval($player)) !== -1;
    $theirs = _SWUTopDeckFrontIdx($opp) !== -1;
    if (SeatCountForGame() <= 2) {   // 2-player auto-resolve short-cuts (N-player always offers the picker)
        if (!$mine && !$theirs) return;
        if ($mine && !$theirs) { WatchfulPeek(intval($player), intval($player)); return; }
        if ($theirs && !$mine) { WatchfulPeek(intval($player), $opp); return; }
    }
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "@-&" . SWUDeckPickerLabels(intval($player)), 1, "Look_at_the_top_card_of_which_deck?");
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
