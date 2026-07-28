<?php
// SEC_148
// Cost 2 - Karis Nemik - Freedom is a Pure Idea - [Aggression,Heroism] - Power 3 - HP 2
// Text: Hidden / When Defeated: You may disclose AggressionHeroism (reveal cards from your hand with these aspect icons among them). If you do, create a Spy token and ready it.

// SEC_148 Karis Nemik — Hidden (auto) + When Defeated: you may disclose AggressionHeroism → create a
// Spy token and ready it.
$whenDefeatedAbilities["SEC_148:0"] = function($player, $mzID) {
    SWUQueueDisclose(intval($player), ['Aggression', 'Heroism'], "SEC_148#0",
        "Disclose_AggressionHeroism_to_create_a_ready_Spy_token");
};

$customDQHandlers["SEC_148#0"] = function($player, $parts, $lastDecision) {
    SWUCreateUnitToken(intval($player), 'SEC_T01', true);   // ready
};
