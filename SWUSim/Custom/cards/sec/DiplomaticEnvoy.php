<?php
// SEC_109
// Cost 2 - Diplomatic Envoy - [Command] - Power 2 - HP 2
// Text: When Played: You may disclose Command (reveal a card from your hand with this aspect icon). If you do, the next unit you play this phase gains Ambush for this phase.

// SEC_109 Diplomatic Envoy — When Played: you may disclose Command → the next unit you play this phase
// gains Ambush for this phase (shares the LOF_180 "next unit gains Ambush" charge, consumed at next entry).
$whenPlayedAbilities["SEC_109:0"] = function($player, $mzID) {
    SWUQueueDisclose(intval($player), ['Command'], "SEC_109#0",
        "Disclose_Command_so_the_next_unit_you_play_gains_Ambush");
};

$customDQHandlers["SEC_109#0"] = function($player, $parts, $lastDecision) {
    AddGlobalEffects(intval($player), 'SWU_LOF180_NEXT_AMBUSH');
};
