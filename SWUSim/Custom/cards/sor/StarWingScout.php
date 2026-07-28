<?php
// SOR_163
// Cost 3 - Star Wing Scout - [Aggression] - Power 4 - HP 1
// Text: When Defeated: If you have the initiative, draw 2 cards.

// SOR_163 Star Wing Scout — When Defeated: If you have the initiative, draw 2 cards.
$whenDefeatedAbilities["SOR_163:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $ic = (string)GetInitiativeCounter();
    $holder = (strpos($ic, 'P1') === 0) ? 1 : 2;       // "P1_CLAIMED"/"P1_UNCLAIMED" → 1, else 2
    if ($holder === intval($player)) DoDrawCard(intval($player), 2);
};
