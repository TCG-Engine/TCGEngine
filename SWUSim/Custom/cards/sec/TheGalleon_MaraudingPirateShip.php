<?php
// SEC_141
// Cost 7 - The Galleon - Marauding Pirate Ship - [Aggression,Villainy] - Power 6 - HP 6
// Text: When Played: You may disclose AggressionAggressionVillainy (reveal cards from your hand with these aspect icons among them). If you do, create 3 Spy tokens.

// SEC_141 The Galleon — When Played: you may disclose AggressionAggressionVillainy → create 3 Spy tokens.
$whenPlayedAbilities["SEC_141:0"] = function($player, $mzID) {
    SWUQueueDisclose(intval($player), ['Aggression', 'Aggression', 'Villainy'], "SEC_141#0",
        "Disclose_AggressionAggressionVillainy_to_create_3_Spy_tokens");
};

$customDQHandlers["SEC_141#0"] = function($player, $parts, $lastDecision) {
    SWUCreateUnitTokens(intval($player), 'SEC_T01', 3);
};
