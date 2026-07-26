<?php
// TWI_188
// Wartime Profiteering
// Text: Look at cards from the top of your deck equal to the number of units that were defeated this phase. Draw 1 and put the others on the bottom of your deck in a random order.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_188:0"] = function($player, $mzID = '') {
// Wartime Profiteering — "Look at cards from the top of your deck equal to the
                          // number of units defeated this phase. Draw 1 and put the others on the bottom."
                          // Total defeated this phase = both players' SWU_FRIENDLY_DEFEATED (set per
                          // controller at every unit-defeat site).
            global $playerID; $playerID = intval($player);
            $n = GlobalEffectCount(1, 'SWU_FRIENDLY_DEFEATED') + GlobalEffectCount(2, 'SWU_FRIENDLY_DEFEATED');
            if ($n <= 0) return;
            DoTopDeckSearch(intval($player), $n, fn($c) => true, 1);
            return;
};
