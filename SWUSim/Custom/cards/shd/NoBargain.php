<?php
// SHD_244
// No Bargain
// Text: Each opponent discards a card from their hand. Draw a card.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_244:0"] = function($player, $mzID = '') {
// No Bargain — "Each opponent discards a card from their hand. Draw a card."
            // Twin Suns (Phase 3): each opponent discards (2-player: the one opponent).
            // "Each opponent discards a card" — hidden information, CR v9.0 7.1.a: every opponent chooses
            // independently and the picks are discarded together (SWUEachSeatDiscardsSimultaneously).
            SWUEachSeatDiscardsSimultaneously(intval($player), OpponentsOf(intval($player)), 'discard', 1,
                "Choose_card_to_discard");
            DoDrawCard(intval($player), 1);
            return;
};
