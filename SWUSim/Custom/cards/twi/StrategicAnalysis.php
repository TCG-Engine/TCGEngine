<?php
// TWI_175
// Strategic Analysis
// Text: Strategic Analysis

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_175:0"] = function($player, $mzID = '') {
// Strategic Analysis — "Draw 3 cards."
            global $playerID; $playerID = intval($player);
            DoDrawCard(intval($player), 3);
            return;
};
