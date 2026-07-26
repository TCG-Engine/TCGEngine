<?php
// SOR_042
// Search Your Feelings
// Text: Search your deck for a card and draw it. (Then, shuffle your deck.)

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_042:0"] = function($player, $mzID = '') {
// Search Your Feelings — "Search your deck for a card and draw it. (Then,
                          // shuffle your deck.)" Reuse the top-N search with n = full deck size + an
                          // any-card filter + pick 1: TOPDECKSEARCH_FINALIZE draws the pick and shuffles
                          // the rest back (a full reshuffle, since the WHOLE deck was peeked). The
                          // searcher may draw nothing (AnswerDecision:''). The peeked cards are private
                          // to the searcher (it's their own decision).
            global $playerID;
            $playerID = intval($player);
            $deckSize = count(GetDeck(intval($player)));
            if ($deckSize === 0) return;   // empty deck → nothing to search
            DoTopDeckSearch(intval($player), $deckSize, fn($cid) => true, 1);
            return;
};
