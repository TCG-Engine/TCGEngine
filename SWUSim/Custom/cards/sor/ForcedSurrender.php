<?php
// SOR_175
// Forced Surrender
// Text: Draw 2 cards. Each opponent whose base you've damaged this phase discards 2 cards from their hand.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_175:0"] = function($player, $mzID = '') {
// — "Draw 2 cards. Each opponent whose base you've damaged this phase
                          // discards 2 cards from their hand." Twin Suns: check each opponent's own
                          // SWU_DMGBASE_{opp} flag and make that opponent discard (2-player → one opp).
            global $playerID;
            $playerID = intval($player);
            DoDrawCard(intval($player), 2);
            foreach (OpponentsOf(intval($player)) as $opp) {
                if (GlobalEffectCount(intval($player), 'SWU_DMGBASE_' . $opp) > 0) {
                    SWUDiscardCards(intval($player), 2, $opp); // that opponent discards 2
                }
            }
            return;
};
