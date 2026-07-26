<?php
// ASH_140
// Cost 4 - Stronger Together - [Command]
// Text: Create 2 Mandalorian tokens.

$whenPlayedAbilities["ASH_140:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    SWUCreateUnitTokens(intval($player), 'ASH_T01', 2);
};
