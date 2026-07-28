<?php
// LAW_232
// Cost 3 - Champion's KT9 Podracer - [Cunning] - Power 2 - HP 3
// Text: When Played: Create a Credit token.

// LAW_232 Champion's KT9 Podracer — When Played: create a Credit token.
$whenPlayedAbilities["LAW_232:0"] = function($player, $mzID) {
    SWUCreateCreditToken(intval($player), 1);
};
