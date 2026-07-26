<?php
// TWI_237
// Droid Deployment
// Text: Create 2 Battle Droid tokens.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_237:0"] = function($player, $mzID = '') {
// Droid Deployment — "Create 2 Battle Droid tokens."
            SWUCreateUnitTokens(intval($player), 'TWI_T01', 2);
            return;
};
