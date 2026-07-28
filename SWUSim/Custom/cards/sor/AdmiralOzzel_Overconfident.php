<?php
// SOR_129
// Cost 2 - Admiral Ozzel - Overconfident - [Aggression,Villainy] - Power 2 - HP 3
// Text: Action [exhaust]: Play an Imperial unit from your hand (paying its cost). It enters play ready. Each opponent may ready a unit.

// SOR_129 Admiral Ozzel — Action [Exhaust]: Play an Imperial unit from your hand (paying its cost).
// It enters play ready. Each opponent may ready a unit.
$unitAbilities["SOR_129"] = fn($player, $mzID) => SWUOfferDiscountPlay($player,
    ['discount' => 0, 'types' => ['Unit'],
     'filter' => fn($cid) => HasTrait($cid, 'Imperial'),
     'prompt' => "Play_an_Imperial_unit_from_your_hand_(it_enters_ready)", 'continuation' => 'OZZEL_PLAY']);
