<?php
// SOR_123
// Recruit
// Text: Search the top 5 cards of your deck for a unit, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.)

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_123:0"] = function($player, $mzID = '') {
// Recruit — "Search the top 5 of your deck for a unit, reveal it, and draw it."
            DoTopDeckSearch(intval($player), 5, fn($c) => CardType($c) === 'Unit', 1);
            return;
};
