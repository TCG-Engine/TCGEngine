<?php
// ASH_185
// Cost 2 - Intimidation - [Aggression]
// Text: If you control a unit with 4 or more power, draw 2 cards.

$whenPlayedAbilities["ASH_185:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $has4 = false;
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (empty($u->removed) && intval(ObjectCurrentPower($u)) >= 4) { $has4 = true; break; }
    }
    if ($has4) DoDrawCard(intval($player), 2);
};
