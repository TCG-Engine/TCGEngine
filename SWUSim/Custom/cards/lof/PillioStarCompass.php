<?php
// LOF_122
// Cost 2 - Pillio Star Compass - [Command] - Upgrade Power 1 - Upgrade HP 1
// Text: Attach to a non-Vehicle unit. / When Played: Search the top 3 cards of your deck for a unit, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order).

// LOF_122 Pillio Star Compass — When Played: search the top 3 for a unit, reveal and draw it.
$whenPlayedAbilities["LOF_122:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DoTopDeckSearch(intval($player), 3, fn($c) => strpos(CardType($c) ?? '', 'Unit') !== false, 1);
};
