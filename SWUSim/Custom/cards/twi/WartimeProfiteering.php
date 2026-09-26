<?php
// TWI_188
// Wartime Profiteering
// Text: Look at cards from the top of your deck equal to the number of units that were defeated this phase. Draw 1 and put the others on the bottom of your deck in a random order.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_188:0"] = function($player, $mzID = '') {
// Wartime Profiteering — "Look at cards from the top of your deck equal to the
                          // number of units defeated this phase. Draw 1 and put the others on the bottom."
                          // "UNITS" is unqualified — every unit that died this phase, on ANY seat, counts.
                          // SWU_FRIENDLY_DEFEATED is set per CONTROLLER at every unit-defeat site, so this
                          // has to sum every seat. It read seats 1 and 2 literally, which silently dropped
                          // every seat-3/4 defeat at a Twin Suns table.
            global $playerID; $playerID = intval($player);
            $n = SWUUnitsDefeatedThisPhase();
            if ($n <= 0) return;
            DoTopDeckSearch(intval($player), $n, fn($c) => true, 1, 'cards');
            return;
};
