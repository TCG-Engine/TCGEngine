<?php
// TS26_18
// Cost 4 - Jendirian Valley - Refugee Freighter - [Vigilance,Command] - Power 1 - HP 5
// Text: Restore 1 / When Played: Search the top 8 cards of your deck for a card and resource it. (Put the other cards on the bottom of your deck in a random order.)

// TS26_18 Jendirian Valley — Restore 1 (auto). When Played: search the top 8 cards of your deck for a
// card and resource it (put it into play as a resource, exhausted); the rest go to the bottom.
$whenPlayedAbilities["TS26_18:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    _topDeckSearchBegin(intval($player), 8, fn($c) => true, "count:1", "TS26_18#0");
};

$customDQHandlers["TS26_18#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $allIDs = array_values(array_filter(explode(',', $parts[0] ?? '')));
    $resolved = _topDeckResolveFromIDs($allIDs, $lastDecision ?? '');
    $chosen = $resolved['drawn'];
    _topDeckPutRemainingToBottom(intval($player), $resolved['remaining']);
    if (empty($chosen)) return;
    $deck = &GetDeck(intval($player));
    $topObj = new Deck($chosen[0], 'Deck', intval($player));
    array_unshift($deck, $topObj);
    foreach ($deck as $i => $c) { $c->mzIndex = $i; }
    SWURampResourceExhausted(intval($player), 'myDeck-0');
};
