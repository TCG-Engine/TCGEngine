<?php
// LOF_139
// Cost 2 - Battle Fury - [Aggression,Villainy] - Upgrade Power 3 - Upgrade HP 3
// Text: Attached unit gains: "On Attack: Discard a card from your hand."

// LOF_139 Battle Fury — attached gains "On Attack: discard a card from your hand."
$onAttackAbilities["LOF_139:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hand = ZoneSearch("myHand", null);
    if (empty($hand)) return;
    SWUQueueChooseTarget(intval($player), $hand, "Discard_a_card_from_your_hand", "DISCARD_FROM_OWN_HAND|" . intval($player));
};
