<?php
// LAW_136
// Cost 2 - Syndicate Spice Runner - [Command,Villainy] - Power 2 - HP 2
// Text: When Played: Search the top 3 cards of your deck for an Underworld unit, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.)

// LAW_136 Syndicate Spice Runner — When Played: search the top 3 cards for an Underworld unit, reveal
// it, and draw it.
$whenPlayedAbilities["LAW_136:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (count(GetDeck(intval($player))) === 0) return;
    DoTopDeckSearch(intval($player), 3, fn($c) => CardType($c) === 'Unit' && HasTrait($c, 'Underworld'), 1);
};
