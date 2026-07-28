<?php
// SOR_181
// Cost 4 - Jabba the Hutt - Cunning Daimyo - [Cunning,Villainy] - Power 2 - HP 8
// Text: Each TRICK event you play costs [1 resource] less. / When Played: Search the top 8 cards of your deck for a TRICK event, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.)

// SOR_181 Jabba the Hutt — When Played: search the top 8 of your deck for a Trick event, reveal it,
// and draw it. (The "Trick events cost 1 less" passive lives in $playCostFieldModifiers.)
$whenPlayedAbilities["SOR_181:0"] = function($player, $mzID) {
    DoTopDeckSearch(intval($player), 8,
        fn($c) => HasTrait($c, 'Trick') && stripos(CardType($c) ?? '', 'Event') !== false, 1);
};
