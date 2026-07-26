<?php
// ASH_116
// Cost 1 - Ant Droid - [Command] - Power 1 - HP 2
// Text: When Defeated: Draw a card.

// ASH_116 Ant Droid — When Defeated: draw a card.
$whenDefeatedAbilities["ASH_116:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DoDrawCard(intval($player), 1);
};
