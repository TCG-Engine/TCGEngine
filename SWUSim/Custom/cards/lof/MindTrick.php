<?php
// LOF_202
// Mind Trick
// Text: Exhaust any number of units with a combined power of 4 or less. If you control a Force unit, those units lose all abilities and can't gain abilities for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_202:0"] = function($player, $mzID = '') {
// Mind Trick — "Exhaust any number of units with a combined power of 4 or less. If
                        // you control a Force unit, those units lose all abilities … for this phase."
            global $playerID; $playerID = intval($player);
            _SWUCombinedBudgetOffer(intval($player), 4, 'power', 1);
            return;
};
