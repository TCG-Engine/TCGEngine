<?php
// LOF_057
// Cost 1 - Owen Lars - Devoted Uncle - [Vigilance] - Power 0 - HP 3
// Text: Restore 2 / When Defeated: Search the top 5 cards of your deck for a Force unit, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.)

// LOF_057 Owen Lars — Restore 2 + When Defeated: search the top 5 for a Force unit, reveal and draw it.
$whenDefeatedAbilities["LOF_057:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DoTopDeckSearch(intval($player), 5,
        fn($c) => strpos(CardType($c) ?? '', 'Unit') !== false && HasTrait($c, 'Force'), 1);
};
