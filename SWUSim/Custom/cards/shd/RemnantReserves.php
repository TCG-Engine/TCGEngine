<?php
// SHD_093
// Remnant Reserves
// Text: Search the top 5 cards of your deck for up to 3 units, reveal them, and draw them. (Put the other cards on the bottom of your deck in a random order.)

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_093:0"] = function($player, $mzID = '') {
// Remnant Reserves — "Search the top 5 cards of your deck for up to 3 units,
                          // reveal them, and draw them."
            DoTopDeckSearch(intval($player), 5, function($cid) { return strpos(CardType($cid) ?? '', 'Unit') !== false; }, 3);
            return;
};
