<?php
// HMW_085
// Cost 2 - Remote Scout - [Vigilance] - Unit (Ground) 1/3 - Traits: Imperial, Trooper
// Text: When Played: Search the top 8 cards of your deck for an upgrade, reveal it, and draw it.
//       (Put the other cards on the bottom of your deck in a random order.)
//
// Mirrors SOR_125 Prepare for Takeoff (search top 8 for up to 2 Vehicle units) — DoTopDeckSearch reveals
// + draws the picks and bottoms the rest in random order. Here: up to 1 pick, filtered to upgrades.
$whenPlayedAbilities["HMW_085:0"] = function($player, $mzID = '') {
    DoTopDeckSearch($player, 8, fn($c) => strpos(CardType($c) ?? '', 'Upgrade') !== false, 1);
};
