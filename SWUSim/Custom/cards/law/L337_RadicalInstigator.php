<?php
// LAW_063
// Cost 6 - L3-37 - Radical Instigator - [Command,Aggression] - Power 3 - HP 2
// Text: Hidden / When Played: Search the top 10 cards of your deck for any number of Droid units with combined cost 5 or less and play each of them for free.

// LAW_063 L3-37 — Hidden + When Played: search the top 10 cards for any number of Droid units with
// combined cost 5 or less and play each for free.
$whenPlayedAbilities["LAW_063:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DoTopDeckPlay(intval($player), 10, fn($c) => CardType($c) === 'Unit' && HasTrait($c, 'Droid'), 5);
};
