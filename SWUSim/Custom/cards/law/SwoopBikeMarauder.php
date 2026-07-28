<?php
// LAW_107
// Cost 4 - Swoop Bike Marauder - [Vigilance,Heroism] - Power 4 - HP 4
// Text: On Attack: Draw a card.

// LAW_107 Swoop Bike Marauder — On Attack: draw a card.
$onAttackAbilities["LAW_107:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DoDrawCard(intval($player), 1);
};
