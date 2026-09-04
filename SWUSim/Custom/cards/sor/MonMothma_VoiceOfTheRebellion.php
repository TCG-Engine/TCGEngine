<?php
// SOR_096
// Cost 2 - Mon Mothma - Voice Of The Rebellion - [Command,Heroism] - Power 1 - HP 3
// Text: When Played: Search the top 5 cards of your deck for a REBEL card, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.)

// SOR_096 Mon Mothma — When Played: Search the top 5 for a REBEL card, reveal it, draw it.
$whenPlayedAbilities["SOR_096:0"] = function($player, $mzID) {
    // Owner-aware: these are cards in the SEARCHER's own deck, and HMW_108 strips a named trait from
    // enemy cards INCLUDING those not in play — so an opponent who named Rebel makes this find nothing.
    DoTopDeckSearch(intval($player), 5, fn($c) => _SWUCardHasTrait(intval($player), $c, 'Rebel'), 1);
};
