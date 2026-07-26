<?php
// SOR_111  |  Reprints: TWI_107
// Cost 2 - Patrolling V-Wing - [Command] - Power 1 - HP 1
// Text: When Played: Draw a card.

// TWI_107 Patrolling V-Wing — "When Played: Draw a card."
$whenPlayedAbilities["TWI_107:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DoDrawCard(intval($player), 1);
};

// SOR_111 Patrolling V-Wing — When Played: draw a card.
$whenPlayedAbilities["SOR_111:0"] = function($player, $mzID) {
    DoDrawCard(intval($player), 1);
};
