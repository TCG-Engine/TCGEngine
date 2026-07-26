<?php
// LAW_244
// Unmarked Credits
// Text: Create a Credit token.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_244:0"] = function($player, $mzID = '') {
// Unmarked Credits — Create a Credit token.
            SWUCreateCreditToken(intval($player), 1);
            return;
};
