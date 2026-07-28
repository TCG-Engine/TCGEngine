<?php
// LAW_229
// Cost 2 - The Master Codebreaker - High Stakes - [Cunning] - Power 1 - HP 4
// Text: The first Gambit card you play each round costs 1 resource less. / When Played: Search the top 8 cards of your deck for a Gambit card, reveal it, and draw it.

// LAW_229 The Master Codebreaker — "the first Gambit card you play each round costs 1 less" (cost
// modifier in SWUComputePlayCost) + When Played: search the top 8 cards for a Gambit card, draw it.
$whenPlayedAbilities["LAW_229:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (count(GetDeck(intval($player))) === 0) return;
    DoTopDeckSearch(intval($player), 8, fn($c) => HasTrait($c, 'Gambit'), 1);
};
