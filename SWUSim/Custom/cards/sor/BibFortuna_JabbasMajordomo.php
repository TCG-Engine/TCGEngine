<?php
// SOR_177
// Cost 2 - Bib Fortuna - Jabba's Majordomo - [Cunning,Villainy] - Power 1 - HP 3
// Text: Shielded (When you play this unit, give him a Shield token.) / Action [Exhaust]: Play an event from your hand. It costs [1 resource] less.

// SOR_177 Bib Fortuna — Action [Exhaust]: Play an event from your hand. It costs 1 less.
// ⚠ 'may' => true — PLAY-FROM-HAND IS ALWAYS DECLINABLE (standing ruling), even with no printed
// "you may": the hand is a HIDDEN zone, so a player can never be forced to reveal they held a playable
// card. Without it SWUOfferDiscountPlay routes to a mandatory MZCHOOSE and the Action's exhaust cost is
// spent with no way out. Siblings with the identical printed shape already pass it (SOR_093 Alliance
// Dispatcher, TWI_120 Strategic Acumen).
$unitAbilities["SOR_177"] = fn($player, $mzID) => SWUOfferDiscountPlay($player,
    ['discount' => 1, 'types' => ['Event'], 'may' => true,
     'prompt' => "Play_an_event_from_your_hand_(it_costs_1_less)"]);
