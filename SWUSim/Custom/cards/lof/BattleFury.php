<?php
// LOF_139
// Cost 2 - Battle Fury - [Aggression,Villainy] - Upgrade Power 3 - Upgrade HP 3
// Text: Attached unit gains: "On Attack: Discard a card from your hand."

// LOF_139 Battle Fury — attached gains "On Attack: discard a card from your hand."
$onAttackAbilities["LOF_139:0"] = function($player, $mzID) {
    SWUOfferDiscard($player, ['from'=>'own']);
};
