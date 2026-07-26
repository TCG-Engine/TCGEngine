<?php
// SEC_128
// Convene the Senate
// Text: Search the top 8 cards of your deck for up to 2 Official units, reveal them, and draw them. (Put the other cards on the bottom of your deck in a random order.) Create a Spy token.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_128:0"] = function($player, $mzID = '') {
// Convene the Senate — "Search the top 8 for up to 2 Official units, draw them.
                          // Create a Spy token."
            SWUCreateUnitToken(intval($player), 'SEC_T01');
            DoTopDeckSearch(intval($player), 8, fn($c) => stripos(CardType($c) ?? '', 'unit') !== false && HasTrait($c, 'Official'), 2);
            return;
};
