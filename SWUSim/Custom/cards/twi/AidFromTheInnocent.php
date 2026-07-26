<?php
// TWI_201
// Aid from the Innocent
// Text: Search the top 10 cards of your deck for 2 Heroism non-unit cards and discard them. (Put the other cards on the bottom of your deck in a random order.) For this phase, you may play the discarded cards, and they each cost 2 resources less.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_201:0"] = function($player, $mzID = '') {
// Aid from the Innocent — "Search the top 10 cards of your deck for 2 Heroism
                          // non-unit cards and discard them. (Put the other cards on the bottom of your
                          // deck in a random order.)"
            global $playerID; $playerID = intval($player);
            _topDeckSearchBegin(intval($player), 10,
                fn($c) => strpos(CardType($c) ?? '', 'Unit') === false
                          && strpos(CardAspect($c) ?? '', 'Heroism') !== false,
                "count:2", "TWI_201#0");
            return;
};
