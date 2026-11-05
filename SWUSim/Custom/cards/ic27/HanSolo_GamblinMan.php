<?php
// IC27_155
// Cost 3 - Han Solo - Gamblin' Man - [Cunning,Heroism] - Unit (Ground) 5/3
// Traits: Rebel - Unique
// Text: Saboteur. When Played: Search the top 10 cards of your deck for a card and draw it, then discard a random
//       card from your hand.
//
// The search is LAW_005 Jyn Erso's: "for a card" names no attribute, so a card is always taken (CR 8.26.1, "count:1:
// must"); the rest go to the bottom in a random order. "Then" is unconditional (TS26_39 Captain Vaughn): an empty deck
// skips the search but still discards. The discard runs in the search's continuation, so it comes AFTER the draw —
// the drawn card can be the one discarded. _SWUPlayerDiscardRandom draws from EngineRandomInt (undo-stable).
// Saboteur is the generated keyword.
// Tests: SWUSim/Tests/Cases/ic27/HanSolo_GamblinMan.md

$whenPlayedAbilities["IC27_155:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    if (empty(GetDeck(intval($player)))) { _SWUPlayerDiscardRandom(intval($player)); return; }
    _topDeckSearchBegin(intval($player), 10, fn($c) => true, "count:1:must", "IC27_155#0", 'cards', 'Take');
};

$customDQHandlers["IC27_155#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $allIDs = array_values(array_filter(explode(',', $parts[0] ?? '')));
    SWUFinishTopDeckSearch(intval($player), $allIDs, $lastDecision, 'bottom', false);   // no "reveal" → hidden draw
    _SWUPlayerDiscardRandom(intval($player));
};
