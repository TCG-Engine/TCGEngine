<?php
// SHD_159
// The Chaos of War
// Text: Deal damage to each player's base equal to the number of cards in that player's hand.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_159:0"] = function($player, $mzID = '') {
// The Chaos of War — deal each player's hand-count to that player's own base.
            DecisionQueueController::CleanupRemovedCards();  // compact the just-played event out of the caster's hand
            // "EACH player's base", and the amount is read PER SEAT from that seat's own hand — was
            // the caster + OtherPlayer() only, so seats 3/4 took nothing.
            // Dealer stated explicitly (3rd arg): this includes SELF-damage to the caster's base, where
            // the funnel's fallback inference would otherwise credit an opponent.
            foreach (SWUSeatsInPlayerOrder(intval($player)) as $p) {
                $n = count(GetHand($p));
                if ($n > 0) SWUDealDamageToBase($n, $p, intval($player));
            }
            return;
};
