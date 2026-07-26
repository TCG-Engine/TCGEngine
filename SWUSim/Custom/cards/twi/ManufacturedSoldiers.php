<?php
// TWI_102
// Cost 3 - Manufactured Soldiers - [Command,Command]
// Text: Choose one: / <bullet>Create 2 Clone Trooper tokens. / Create 3 Battle Droid tokens.</bullet>

// TWI_102 Manufactured Soldiers — resolve the chosen mode.
$customDQHandlers["TWI_102#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === 'Clones') SWUCreateUnitTokens(intval($player), 'TWI_T02', 2);
    else if ($lastDecision === 'Droids') SWUCreateUnitTokens(intval($player), 'TWI_T01', 3);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_102:0"] = function($player, $mzID = '') {
// Manufactured Soldiers — "Choose one: Create 2 Clone Trooper tokens. /
                          // Create 3 Battle Droid tokens."
            DecisionQueueController::AddDecision(intval($player), 'OPTIONCHOOSE', 'Clones&Droids', 1,
                tooltip: 'Choose_one:_2_Clone_Troopers_or_3_Battle_Droids');
            DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'TWI_102#0', 1);
            return;
};
