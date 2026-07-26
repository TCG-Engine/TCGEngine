<?php
// LAW_116
// Cost 2 - Rodian Bondsman - [Vigilance] - Power 2 - HP 3
// Text: When Defeated: Each player creates a Credit token.

// LAW_116 Rodian Bondsman — When Defeated: each player creates a Credit token.
$whenDefeatedAbilities["LAW_116:0"] = function($player, $mzID) {
    SWUCreateCreditToken(intval($player), 1);
    SWUCreateCreditToken(OtherPlayer(intval($player)), 1);
};
