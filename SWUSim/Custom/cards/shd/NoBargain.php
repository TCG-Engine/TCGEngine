<?php
// SHD_244
// No Bargain
// Text: Each opponent discards a card from their hand. Draw a card.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_244:0"] = function($player, $mzID = '') {
// No Bargain — "Each opponent discards a card from their hand. Draw a card."
            // Twin Suns (Phase 3): each opponent discards (2-player: the one opponent).
            foreach (OpponentsOf(intval($player)) as $opp) {
                SWUDiscardCards(intval($player), 1, $opp);
            }
            DoDrawCard(intval($player), 1);
            return;
};
