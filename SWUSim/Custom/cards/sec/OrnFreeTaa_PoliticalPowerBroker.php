<?php
// SEC_112
// Cost 2 - Orn Free Taa - Political Power Broker - [Command] - Power 0 - HP 4
// Text: This unit gets +1/+0 for each Law card in your discard pile. / When Played: Search the top 10 cards of your deck for a Law card, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.)

// SEC_112 Orn Free Taa — When Played: search the top 10 of your deck for a Law card, reveal+draw it.
$whenPlayedAbilities["SEC_112:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DoTopDeckSearch(intval($player), 10, fn($c) => HasTrait($c, 'Law'), 1);
};
