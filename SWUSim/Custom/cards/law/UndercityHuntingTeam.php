<?php
// LAW_138
// Cost 5 - Undercity Hunting Team - [Command,Villainy] - Power 5 - HP 5
// Text: When Played: Search the top 5 cards of your deck for a Bounty Hunter unit, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.)

// LAW_138 Undercity Hunting Team — When Played: search the top 5 cards for a Bounty Hunter unit, draw it.
$whenPlayedAbilities["LAW_138:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (count(GetDeck(intval($player))) === 0) return;
    DoTopDeckSearch(intval($player), 5, fn($c) => CardType($c) === 'Unit' && HasTrait($c, 'Bounty Hunter'), 1);
};
