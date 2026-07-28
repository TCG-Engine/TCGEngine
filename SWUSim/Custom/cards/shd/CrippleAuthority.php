<?php
// SHD_156
// Cripple Authority
// Text: Draw a card. Each opponent who controls more resources than you discards a card from their hand.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_156:0"] = function($player, $mzID = '') {
// Shadowed Undercover — "Draw a card. Each opponent who controls more resources
                          // than you discards a card from their hand."
            DoDrawCard(intval($player), 1);
            $opp = OtherPlayer(intval($player));
            if (SWUResourceCount($opp) > SWUResourceCount(intval($player))) {
                SWUDiscardCards(intval($player), 1);   // makes the opponent discard 1
            }
            return;
};
