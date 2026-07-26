<?php
// SEC_083
// Cost 3 - ISB Shuttle - [Command,Villainy] - Power 3 - HP 2
// Text: When Played: If a friendly unit was defeated this phase, create a Spy token.

// SEC_083 ISB Shuttle — When Played: if a friendly unit was defeated this phase, create a Spy token.
$whenPlayedAbilities["SEC_083:0"] = function($player, $mzID) {
    if (GlobalEffectCount(intval($player), 'SWU_FRIENDLY_DEFEATED') > 0) SWUCreateUnitToken(intval($player), 'SEC_T01');
};
