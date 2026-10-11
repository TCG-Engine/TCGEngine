<?php
// HMW_144 Howler Pack — Unit (Ground) 3/3, cost 6, [Command], Creature.
// "When Played/When Defeated: Create a Beast token."

$whenPlayedAbilities["HMW_144:0"] = $whenDefeatedAbilities["HMW_144:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    SWUCreateUnitToken(intval($player), 'HMW_T03');
};
