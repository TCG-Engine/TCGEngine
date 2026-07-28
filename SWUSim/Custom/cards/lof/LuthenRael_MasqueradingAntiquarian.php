<?php
// LOF_068
// Cost 4 - Luthen Rael - Masquerading Antiquarian - [Vigilance] - Power 4 - HP 5
// Text: On Attack: Search the top 5 cards of your deck for an Item upgrade, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.)

// LOF_068 Luthen Rael — On Attack: search the top 5 for an Item upgrade, reveal and draw it.
$onAttackAbilities["LOF_068:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DoTopDeckSearch(intval($player), 5,
        fn($c) => strpos(CardType($c) ?? '', 'Upgrade') !== false && HasTrait($c, 'Item'), 1);
};
