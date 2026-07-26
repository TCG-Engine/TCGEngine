<?php
// SHD_127
// Commission
// Text: Search the top 10 cards of your deck for a Bounty Hunter, Item, or Transport card, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.) Smuggle [3 resources Command]

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_127:0"] = function($player, $mzID = '') {
// Commission — search the top 10 of your deck for a Bounty Hunter, Item,
                          // or Transport card; reveal it and draw it.
            global $playerID; $playerID = intval($player);
            if (count(GetDeck($player)) === 0) return;
            DoTopDeckSearch(intval($player), 10,
                fn($c) => HasTrait($c, 'Bounty Hunter') || HasTrait($c, 'Item') || HasTrait($c, 'Transport'), 1);
            return;
};
