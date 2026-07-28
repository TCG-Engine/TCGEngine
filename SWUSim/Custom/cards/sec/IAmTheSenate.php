<?php
// SEC_092
// I Am the Senate
// Text: Create 5 Spy tokens.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_092:0"] = function($player, $mzID = '') {
// I Am the Senate — "Create 5 Spy tokens."
            SWUCreateUnitTokens(intval($player), 'SEC_T01', 5);
            return;
};
