<?php
// JTL_254
// Dedicated Wingmen
// Text: Create 2 X-Wing tokens.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_254:0"] = function($player, $mzID = '') {
// Dedicated Wingmen — create 2 X-Wing tokens.
            SWUCreateUnitTokens(intval($player), 'JTL_T02', 2);
            return;
};
