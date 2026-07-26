<?php
// TWI_251
// Drop In
// Text: Create 2 Clone Trooper tokens.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_251:0"] = function($player, $mzID = '') {
// Drop In — "Create 2 Clone Trooper tokens."
            SWUCreateUnitTokens(intval($player), 'TWI_T02', 2);
            return;
};
