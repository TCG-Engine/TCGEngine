<?php
// TWI_247
// Cost 8 - AT-TE Vanguard - [Heroism] - Power 6 - HP 9
// Text: Restore 3 (When this unit attacks, heal 3 damage from your base.) / When Defeated: Create 2 Clone Trooper tokens.

// TWI_247 AT-TE Vanguard — "Restore 3. When Defeated: Create 2 Clone Trooper tokens."
$whenDefeatedAbilities["TWI_247:0"] = function($player, $mzID) {
    SWUCreateUnitTokens(intval($player), 'TWI_T02', 2);
};
