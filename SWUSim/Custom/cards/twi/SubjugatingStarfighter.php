<?php
// TWI_112
// Cost 4 - Subjugating Starfighter - [Command] - Power 3 - HP 3
// Text: Ambush (When you play this unit, it may ready and attack an enemy unit.) / When Played: If you have the initiative, create a Battle Droid token.

// TWI_112 Subjugating Starfighter — Ambush + "When Played: If you have the initiative, create a Battle
// Droid token."
$whenPlayedAbilities["TWI_112:0"] = function($player, $mzID) {
    if (PlayerHasIniative(intval($player))) SWUCreateUnitToken(intval($player), 'TWI_T01');
};
