<?php
// TWI_229
// Cost 3 - Battle Droid Escort - [Villainy] - Power 1 - HP 1
// Text: When Played/When Defeated: Create a Battle Droid token.

// TWI_229 Battle Droid Escort — "When Played/When Defeated: Create a Battle Droid token."
$whenPlayedAbilities["TWI_229:0"] = $whenDefeatedAbilities["TWI_229:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'TWI_T01');
};
