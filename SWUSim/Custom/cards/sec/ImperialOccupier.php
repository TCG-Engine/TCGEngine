<?php
// SEC_132
// Cost 2 - Imperial Occupier - [Aggression,Villainy] - Power 2 - HP 2
// Text: When Defeated: Create a Spy token.

// SEC_132 Imperial Occupier — When Defeated: create a Spy token.
$whenDefeatedAbilities["SEC_132:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'SEC_T01');
};
