<?php
// ASH_245
// Cost 7 - Eye of Sion - Delivered from Exile - [Villainy] - Power 5 - HP 8
// Text: Action [Exhaust]: Search the top 8 cards of your deck for a unit that costs the same as or less than this unit's power. Play it for free. It enters play ready.

$unitAbilities["ASH_245"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $pow  = $self ? intval(ObjectCurrentPower($self)) : 0;
    _topDeckSearchBegin(intval($player), 8,
        fn($cid) => strpos(CardType($cid) ?? '', 'Unit') !== false && intval(CardCost($cid)) <= $pow,
        "count:1", "ASH_245#0");
};

$customDQHandlers["ASH_245#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gForceEnterReady; $playerID = intval($player);
    $allIDs   = array_values(array_filter(explode(',', $parts[0] ?? '')));
    $resolved = _topDeckResolveFromIDs($allIDs, $lastDecision ?? '');
    $chosen   = $resolved['drawn'];
    _topDeckPutRemainingToBottom(intval($player), $resolved['remaining']);
    if (empty($chosen)) { SWUAfterAction($player); return; }
    $cardID = $chosen[0];
    $deck = &GetDeck(intval($player));
    $topObj = new Deck($cardID, 'Deck', intval($player));
    array_unshift($deck, $topObj);
    foreach ($deck as $i => $c) { $c->mzIndex = $i; }
    $gForceEnterReady = true;
    SWUPlayTopDeckCard(intval($player), false, 99);   // free
    $gForceEnterReady = null;
    SWUAfterAction($player);
};
