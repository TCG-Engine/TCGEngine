<?php
// ASH_111
// Cost 6 - Children of the Watch - [Command,Heroism] - Power 3 - HP 3
// Text: When Played: Create 2 Mandalorian tokens.

// ASH_111 Children of the Watch — When Played: create 2 Mandalorian tokens.
$whenPlayedAbilities["ASH_111:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUCreateUnitTokens(intval($player), 'ASH_T01', 2);
};
