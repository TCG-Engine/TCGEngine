<?php
// TWI_190
// On the Doorstep
// Text: Create 3 Battle Droid tokens and ready them.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_190:0"] = function($player, $mzID = '') {
// On the Doorstep — "Create 3 Battle Droid tokens and ready them."
            SWUCreateUnitTokens(intval($player), 'TWI_T01', 3, ready: true);
            return;
};
