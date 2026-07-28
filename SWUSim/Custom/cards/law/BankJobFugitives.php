<?php
// LAW_262
// Cost 6 - Bank Job Fugitives - Power 4 - HP 6
// Text: When Played: Create a Credit token.

// LAW_262 Bank Job Fugitives — When Played: Create a Credit token.
$whenPlayedAbilities["LAW_262:0"] = function($player, $mzID) {
    SWUCreateCreditToken(intval($player), 1);
};
