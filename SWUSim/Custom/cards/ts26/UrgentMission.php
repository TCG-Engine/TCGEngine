<?php
// TS26_64
// Cost 2 - Urgent Mission - [Aggression,Heroism]
// Text: Deal 2 damage to your base. Draw 2 cards.

$whenPlayedAbilities["TS26_64:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    SWUDealDamageToBase(2, intval($player));
    DoDrawCard(intval($player), 2);
};
