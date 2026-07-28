<?php
// SEC_191
// Cost 5 - Trade Federation Delegates - [Cunning,Villainy] - Power 3 - HP 4
// Text: When Played: Create 2 Spy tokens.

// SEC_191 Trade Federation Delegates — When Played: create 2 Spy tokens.
$whenPlayedAbilities["SEC_191:0"] = function($player, $mzID) {
    SWUCreateUnitTokens(intval($player), 'SEC_T01', 2);
};
