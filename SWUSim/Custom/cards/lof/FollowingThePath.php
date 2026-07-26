<?php
// LOF_103
// Following the Path
// Text: Search the top 8 cards of your deck for up to 2 Force units, reveal them, and put them on top of your deck in any order. (Put the other cards on the bottom of your deck in a random order.)

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_103:0"] = function($player, $mzID = '') {
// Following the Path — "Search the top 8 cards for up to 2 Force units, reveal them,
                        // and put them on top of your deck in any order. (Put the others on the bottom.)"
            _topDeckSearchBegin(intval($player), 8,
                fn($c) => HasTrait($c, 'Force') && CardType($c) === 'Unit', "count:2", "LOF_103#0");
            return;
};
