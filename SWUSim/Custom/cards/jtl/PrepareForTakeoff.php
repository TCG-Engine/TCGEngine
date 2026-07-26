<?php
// JTL_128
// Prepare for Takeoff
// Text: Search the top 8 cards of your deck for up to 2 Vehicle units, reveal them, and draw them. (Put the other cards on the bottom of your deck in a random order.)

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_128:0"] = function($player, $mzID = '') {
// Prepare for Takeoff — search the top 8 cards for up to 2 Vehicle units, reveal
                          // and draw them.
            global $playerID;
            $playerID = intval($player);
            DoTopDeckSearch(intval($player), 8,
                fn($c) => stripos(CardType($c) ?? '', 'Unit') !== false && HasTrait($c, 'Vehicle'), 2);
            return;
};
