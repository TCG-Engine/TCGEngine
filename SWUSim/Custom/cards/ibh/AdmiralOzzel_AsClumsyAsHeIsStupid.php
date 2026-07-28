<?php
// IBH_082
// Cost 3 - Admiral Ozzel - As Clumsy as He Is Stupid - [Aggression,Villainy] - Power 3 - HP 1
// Text: When Defeated: Each opponent discards a card from their hand.

// IBH_082 / IBH_085 Admiral Ozzel — When Defeated: each opponent discards a card from their hand.
$whenDefeatedAbilities["IBH_082:0"] =
$whenDefeatedAbilities["IBH_085:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // Twin Suns (Phase 3): EACH opponent discards a card (2-player: the one opponent). Each chooses.
    foreach (OpponentsOf(intval($player)) as $opp) {
        SWUDiscardCards(intval($player), 1, $opp);
    }
};
