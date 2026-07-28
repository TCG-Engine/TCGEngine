<?php
// SHD_253
// This Is The Way
// Text: Search the top 8 cards of your deck for up to 2 Mandalorian and/or upgrade cards, reveal them, and draw them. (Put the other cards on the bottom of your deck in a random order.)

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_253:0"] = function($player, $mzID = '') {
// Wren's Handiwork — "Search the top 8 cards of your deck for up to 2 Mandalorian
                          // and/or upgrade cards, reveal them, and draw them."
            global $playerID; $playerID = intval($player);
            if (count(GetDeck($player)) === 0) return;
            DoTopDeckSearch(intval($player), 8,
                fn($c) => HasTrait($c, 'Mandalorian') || strpos(CardType($c) ?? '', 'Upgrade') !== false, 2);
            return;
};
