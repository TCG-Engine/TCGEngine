<?php
// TS26_68
// Cost 2 - Arms Deal - [Aggression]
// Text: You and an opponent each draw 2 cards.

$whenPlayedAbilities["TS26_68:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    DoDrawCard(intval($player), 2);
    DoDrawCard(OtherPlayer(intval($player)), 2);
};
