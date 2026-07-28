<?php
// SOR_087
// Cost 7 - Darth Vader - Commanding the First Legion - [Command,Villainy] - Power 5 - HP 7
// Text: Ambush / When Played: Search the top 10 cards of your deck for any number of [Villainy] units with combined cost 3 or less and play each of them for free.

// SOR_087 Darth Vader — "When Played: Search the top 10 cards for any number of Villainy units
//   with combined cost 3 or less and play each of them for free."
// WhenPlayed triggers fire before Ambush (TOPDECKSEARCH queued at priority 1).
// Units are placed directly in arena — no WhenPlayed triggers on the free-played units.
$whenPlayedAbilities["SOR_087:0"] = function($player, $mzID) {
    DoTopDeckPlay($player, 10,
        fn($c) => strpos(CardAspect($c) ?? '', 'Villainy') !== false && CardType($c) === 'Unit',
        3
    );
};
