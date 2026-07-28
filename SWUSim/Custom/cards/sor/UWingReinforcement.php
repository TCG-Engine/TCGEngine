<?php
// SOR_104
// U-Wing Reinforcement
// Text: Search the top 10 cards of your deck for up to 3 units with combined cost 7 or less and play each of them for free. (Put the other cards on the bottom of your deck in a random order.)

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_104:0"] = function($player, $mzID = '') {
// U-Wing Reinforcement — "Search top 10 for up to 3 units, combined cost ≤7, play each free."
            DoTopDeckPlay(intval($player), 10, fn($c) => CardType($c) === 'Unit', 7, 3);
            return;
};
