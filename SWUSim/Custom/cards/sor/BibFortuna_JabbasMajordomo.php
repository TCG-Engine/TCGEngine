<?php
// SOR_177
// Cost 2 - Bib Fortuna - Jabba's Majordomo - [Cunning,Villainy] - Power 1 - HP 3
// Text: Shielded (When you play this unit, give him a Shield token.) / Action [Exhaust]: Play an event from your hand. It costs [1 resource] less.

// SOR_177 Bib Fortuna — Action [Exhaust]: Play an event from your hand. It costs 1 less.
$unitAbilities["SOR_177"] = fn($player, $mzID) => SWUOfferDiscountPlay($player,
    ['discount' => 1, 'types' => ['Event'], 'prompt' => "Play_an_event_from_your_hand_(it_costs_1_less)"]);
