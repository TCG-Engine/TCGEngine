<?php
// SHD_156
// Cripple Authority
// Text: Draw a card. Each opponent who controls more resources than you discards a card from their hand.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_156:0"] = function($player, $mzID = '') {
// Shadowed Undercover — "Draw a card. Each opponent who controls more resources
                          // than you discards a card from their hand."
            DoDrawCard(intval($player), 1);
            // "EACH opponent who controls more resources than you" — a per-seat TEST, not one opponent.
            // The comparison is re-read per seat: in Twin Suns some opponents qualify and others do not,
            // which is the whole point of the clause. Was OtherPlayer() + a single discard.
            $mine = SWUResourceCount(intval($player));
            foreach (OpponentsOf(intval($player)) as $opp) {
                if (SWUResourceCount($opp) > $mine) SWUDiscardCards(intval($player), 1, $opp);
            }
            return;
};
