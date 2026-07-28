<?php
// TS26_39
// Cost 3 - Captain Vaughn - Search the Tunnels - [Vigilance,Heroism] - Power 2 - HP 5
// Text: Grit / When Defeated: Search the top 3 cards of your deck for a card and draw it. Then, put a card from your hand on top of your deck. (Put the other cards on the bottom of your deck in a random order.)

// TS26_39 Captain Vaughn — Grit (auto). When Defeated: search the top 3 cards of your deck for a card
// and draw it; then put a card from your hand on top of your deck. The rest go to the bottom.
$whenDefeatedAbilities["TS26_39:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    _topDeckSearchBegin(intval($player), 3, fn($c) => true, "count:1", "TS26_39#0");
};

$customDQHandlers["TS26_39#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $allIDs = array_values(array_filter(explode(',', $parts[0] ?? '')));
    $resolved = _topDeckResolveFromIDs($allIDs, $lastDecision ?? '');
    foreach ($resolved['drawn'] as $cid) AddHand(intval($player), CardID: $cid);
    _topDeckPutRemainingToBottom(intval($player), $resolved['remaining']);
    $hand = ZoneSearch('myHand');
    if (empty($hand)) return;
    SWUQueueChooseTarget(intval($player), $hand, "Put_a_card_from_your_hand_on_top_of_your_deck", "TS26_39#1");
};

$customDQHandlers["TS26_39#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cid = $o->CardID ?? '';
    $o->removed = true;
    DecisionQueueController::CleanupRemovedCards();
    $deck = &GetDeck(intval($player));
    $topObj = new Deck($cid, 'Deck', intval($player));
    array_unshift($deck, $topObj);
    foreach ($deck as $i => $c) { $c->mzIndex = $i; }
};
