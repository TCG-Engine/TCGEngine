<?php
// LOF_194
// Cost 3 - J-Type Nubian Starship - [Cunning,Heroism] - Power 2 - HP 4
// Text: When Played: Draw a card. / When Defeated: Discard a card from your hand.

// LOF_194 J-Type Nubian Starship — When Played: draw a card. When Defeated: discard a card from your hand.
$whenPlayedAbilities["LOF_194:0"]   = function($player, $mzID) { DoDrawCard(intval($player), 1); };

$whenDefeatedAbilities["LOF_194:0"] = function($player, $mzID) {
    SWUOfferDiscard($player, ['from'=>'own']);
};
