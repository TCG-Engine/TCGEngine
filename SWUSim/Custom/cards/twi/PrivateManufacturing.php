<?php
// TWI_257
// Cost 2 - Private Manufacturing
// Text: Draw 2 cards. If you control no token units, put 2 cards from your hand on the bottom of your deck in any order.

// TWI_257 Private Manufacturing (event continuation) — put the chosen hand cards on the bottom of the deck.
$customDQHandlers["TWI_257#0"] =
// JTL_028 Nabat Village — move the chosen hand cards ($lastDecision, &-joined) to the bottom of the deck
// (same mechanic as TWI_257 "put 2 cards from hand on the bottom"), fired at the first action phase.
$customDQHandlers["JTL_028#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $picks = (SWUDecisionDeclined($lastDecision))
        ? [] : array_values(array_filter(explode('&', $lastDecision), fn($s) => $s !== '' && $s !== '-' && $s !== 'PASS'));
    $cardIDs = [];
    foreach ($picks as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        $cardIDs[] = $o->CardID;
        $o->removed = true;
    }
    if (!empty($cardIDs)) {
        DecisionQueueController::CleanupRemovedCards();
        _topDeckPutRemainingToBottom(intval($player), $cardIDs);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_257:0"] = function($player, $mzID = '') {
// Private Manufacturing — "Draw 2 cards. If you control no token units, put 2
                          // cards from your hand on the bottom of your deck in any order."
            global $playerID; $playerID = intval($player);
            DoDrawCard(intval($player), 2);
            $hasToken = false;
            foreach (GetUnitsInPlay(intval($player)) as $u) {
                if (empty($u->removed) && strpos(CardType($u->CardID ?? '') ?? '', 'Token') !== false) { $hasToken = true; break; }
            }
            if ($hasToken) return;
            DecisionQueueController::CleanupRemovedCards();
            $hand = array_values(ZoneSearch("myHand"));
            $n = min(2, count($hand));
            if ($n <= 0) return;
            DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "{$n}|{$n}|" . implode('&', $hand), 1,
                tooltip: "Put_2_cards_from_your_hand_on_the_bottom_of_your_deck");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "TWI_257#0", 1);
            return;
};
