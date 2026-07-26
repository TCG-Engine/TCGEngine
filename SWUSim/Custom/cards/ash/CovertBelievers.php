<?php
// ASH_080
// Cost 5 - Covert Believers - [Vigilance] - Power 4 - HP 5
// Text: When Defeated: Create a Mandalorian token.

// ASH_080 Covert Believers — When Defeated: create a Mandalorian token (same as ASH_058).
$whenDefeatedAbilities["ASH_080:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUCreateUnitToken(intval($player), 'ASH_T01');
};
