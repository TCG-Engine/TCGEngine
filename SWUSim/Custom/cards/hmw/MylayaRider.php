<?php
// HMW_262 Mylaya Rider — Unit (Ground) 4/4, cost 6, [Heroism], Creature/Wookiee.
// "When Played: Create a Beast token and heal 2 damage from your base."

$whenPlayedAbilities["HMW_262:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    SWUCreateUnitToken(intval($player), 'HMW_T03');
    OnHealBase(intval($player), intval($player), 2);
};
