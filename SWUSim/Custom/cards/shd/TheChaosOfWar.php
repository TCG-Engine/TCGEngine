<?php
// SHD_159
// The Chaos of War
// Text: Deal damage to each player's base equal to the number of cards in that player's hand.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_159:0"] = function($player, $mzID = '') {
// The Chaos of War — deal each player's hand-count to that player's own base.
            DecisionQueueController::CleanupRemovedCards();  // compact the just-played event out of the caster's hand
            $opp     = OtherPlayer(intval($player));
            $myHand  = count(GetHand(intval($player)));   // now excludes this event
            $oppHand = count(GetHand($opp));
            if ($myHand  > 0) SWUDealDamageToBase($myHand,  intval($player));
            if ($oppHand > 0) SWUDealDamageToBase($oppHand, $opp);
            return;
};
