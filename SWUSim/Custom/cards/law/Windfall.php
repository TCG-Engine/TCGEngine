<?php
// LAW_248
// Windfall
// Text: Create 3 Credit tokens.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_248:0"] = function($player, $mzID = '') {
// Windfall — Create 3 Credit tokens.
            SWUCreateCreditToken(intval($player), 3);
            return;
};
