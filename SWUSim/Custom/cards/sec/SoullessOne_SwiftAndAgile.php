<?php
// SEC_190
// Cost 3 - Soulless One - Swift and Agile - [Cunning,Villainy] - Power 3 - HP 3
// Text: On Attack: You may disclose CunningCunningVillainy (reveal cards from your hand with these aspect icons among them). If you do, ready 2 resources.

// SEC_190 Soulless One — On Attack: you may disclose CunningCunningVillainy → ready 2 resources.
$onAttackAbilities["SEC_190:0"] = function($player, $mzID) {
    SWUQueueDisclose(intval($player), ['Cunning', 'Cunning', 'Villainy'], "SEC_190#0",
        "Disclose_CunningCunningVillainy_to_ready_2_resources");
};

$customDQHandlers["SEC_190#0"] = function($player, $parts, $lastDecision) {
    SWUReadyResources(intval($player), 2);
};
