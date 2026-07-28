<?php
// TWI_148
// Cost 5 - Senatorial Corvette - [Aggression,Heroism] - Power 5 - HP 4
// Text: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / When Defeated: Each opponent discards a card from their hand.

// TWI_148 Senatorial Corvette — Saboteur + "When Defeated: Each opponent discards a card from their hand."
$whenDefeatedAbilities["TWI_148:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // Twin Suns (Phase 3): each opponent discards (2-player: the one opponent).
    foreach (OpponentsOf(intval($player)) as $opp) {
        SWUDiscardCards(intval($player), 1, $opp);
    }
};
