<?php
// LAW_106
// Cost 3 - Defiant Scrapper - [Vigilance,Heroism] - Power 3 - HP 4
// Text: When Played: You may defeat an enemy Credit token.

// LAW_106 Defiant Scrapper — When Played: You may defeat an enemy Credit token.
$whenPlayedAbilities["LAW_106:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = SWUEnemyCreditTokenMzIDs(intval($player));
    if (empty($targets)) return; // no enemy Credit token → fizzles cleanly
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Defeat_an_enemy_Credit_token?", "Choose_an_enemy_Credit_token", "DEFEAT_CREDIT_TOKEN");
};
