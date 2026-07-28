<?php
// SHD_181
// Pillage
// Text: Choose a player. They discard 2 cards from their hand.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_181:0"] = function($player, $mzID = '') {
// Pillage
            SWUDiscardCards($player, 2);
            return;
};
