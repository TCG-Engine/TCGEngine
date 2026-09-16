<?php
// HMW_189 Neebray Manta — Unit (Space) 7/6, cost 8, [Aggression], Creature.
// "When Played: Draw 3 cards."

$whenPlayedAbilities["HMW_189:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    DoDrawCard(intval($player), 3);
};
