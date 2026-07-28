<?php
// LOF_059
// Cost 2 - Nightsister Warrior - [Vigilance] - Power 2 - HP 2
// Text: When Defeated: Draw a card.

// LOF_059 Nightsister Warrior — When Defeated: draw a card.
$whenDefeatedAbilities["LOF_059:0"] = function($player, $mzID) { DoDrawCard(intval($player), 1); };
