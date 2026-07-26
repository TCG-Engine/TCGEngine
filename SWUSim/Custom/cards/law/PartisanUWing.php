<?php
// LAW_161
// Cost 5 - Partisan U-Wing - [Command] - Power 3 - HP 5
// Text: When Played: If a friendly unit was defeated this phase, create a Credit token.

// LAW_161 Partisan U-Wing — When Played: if a friendly unit was defeated this phase, create a Credit token.
$whenPlayedAbilities["LAW_161:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_FRIENDLY_DEFEATED') > 0) SWUCreateCreditToken(intval($player), 1);
};
