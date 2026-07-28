<?php
// SOR_041
// Power of the Dark Side
// Text: An opponent chooses a unit they control. Defeat that unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_041:0"] = function($player, $mzID = '') {
// Power of the Dark Side — "An opponent chooses a unit they control. Defeat
                          // that unit." Any unit (incl. leaders). Cross-player choose via the
                          // intermediate CUSTOM (nonLeader=0). The event flow's FINISH_PLAY_CARD
                          // owns the after-action, so just queue the choose.
            DecisionQueueController::AddDecision($player, "CUSTOM", "OPP_DEFEAT_OWN_UNIT|0", 1);
            return;
};
