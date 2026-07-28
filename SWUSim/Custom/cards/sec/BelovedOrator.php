<?php
// SEC_097
// Cost 3 - Beloved Orator - [Command,Heroism] - Power 2 - HP 3
// Text: When Played: Create a Spy token.

// SEC_097 Beloved Orator — When Played: create a Spy token.
$whenPlayedAbilities["SEC_097:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'SEC_T01');
};
