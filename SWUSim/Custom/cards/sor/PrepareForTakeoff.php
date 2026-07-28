<?php
// SOR_125
// Prepare for Takeoff
// Text: Search the top 8 cards of your deck for up to 2 Vehicle units, reveal them, and draw them. (Put the other cards on the bottom of your deck in a random order.)

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_125:0"] = function($player, $mzID = '') {
// Prepare for Takeoff — "Search the top 8 cards for up to 2 Vehicle units, draw them."
            DoTopDeckSearch($player, 8,
                fn($c) => HasTrait($c, 'Vehicle') && CardType($c) === 'Unit',
                2
            );
            return;
};
