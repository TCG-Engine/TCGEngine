<?php
// SOR_031
// Cost 2 - Inferno Four - Unforgetting - [Vigilance,Villainy] - Power 2 - HP 3
// Text: When Played/When Defeated: Look at the top 2 cards of your deck. Put any number of them on the bottom of your deck and the rest on top in any order.

// SOR_031 Inferno Four — "When Played/When Defeated: Look at the top 2 cards of your deck.
//   Put any number of them on the bottom and the rest on top in any order."
$whenPlayedAbilities["SOR_031:0"] =
$whenDefeatedAbilities["SOR_031:0"] = function($player, $mzID) {
    DoScry($player, 2);
};
