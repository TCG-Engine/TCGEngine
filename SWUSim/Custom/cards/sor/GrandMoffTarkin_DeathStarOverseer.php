<?php
// SOR_084
// Cost 4 - Grand Moff Tarkin - Death Star Overseer - [Command,Villainy] - Power 2 - HP 3
// Text: When Played: Search the top 5 cards of your deck for up to 2 Imperial cards, reveal them, and draw them. (Put the other cards on the bottom of your deck in a random order.)

// SOR_084 Grand Moff Tarkin — "When Played: Search the top 5 cards for up to 2 Imperial cards,
//   reveal and draw them. Put the rest on the bottom in a random order."
$whenPlayedAbilities["SOR_084:0"] = function($player, $mzID) {
    DoTopDeckSearch($player, 5, fn($c) => HasTrait($c, 'Imperial'), 2);
};
