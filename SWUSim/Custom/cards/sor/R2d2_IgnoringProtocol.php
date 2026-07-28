<?php
// SOR_236
// Cost 1 - R2-D2 - Ignoring Protocol - [Heroism] - Power 1 - HP 4
// Text: When Played/On Attack: Look at the top card of your deck. You may put it on the bottom of your deck. (Otherwise, leave it on top of your deck.)

// SOR_236 R2-D2 — "When Played/On Attack: Look at the top card of your deck.
//   You may put it on the bottom of your deck."
$whenPlayedAbilities["SOR_236:0"] =
$onAttackAbilities["SOR_236:0"]   = function($player, $mzID) {
    DoScry($player, 1);
};
