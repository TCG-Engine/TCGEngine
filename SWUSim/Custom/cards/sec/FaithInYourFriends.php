<?php
// SEC_211
// Cost 2 - Faith in Your Friends - [Cunning,Heroism]
// Text: Search the top 3 cards of your deck for a card and draw it. Then, you may disclose CunningCunningCunningHeroismHeroism (reveal cards from your hand with these aspect icons among them). If you do, create 2 Spy tokens.

// SEC_211 Faith in Your Friends — after the search draw (block 2): offer the disclose; on success
// create 2 Spy tokens.
$customDQHandlers["SEC_211#0"] = function($player, $parts, $lastDecision) {
    SWUQueueDisclose(intval($player), ['Cunning', 'Cunning', 'Cunning', 'Heroism', 'Heroism'],
        "SEC_211#1", "Disclose_CunningCunningCunningHeroismHeroism_to_create_2_Spy_tokens");
};

$customDQHandlers["SEC_211#1"] = function($player, $parts, $lastDecision) {
    SWUCreateUnitTokens(intval($player), 'SEC_T01', 2);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_211:0"] = function($player, $mzID = '') {
// Faith in Your Friends — "Search the top 3 of your deck for a card and draw it.
                          // Then, you may disclose CunningCunningCunningHeroismHeroism → create 2 Spy tokens."
                          // The disclose is queued at block 2 so it offers the hand AFTER the search's draw.
            DoTopDeckSearch(intval($player), 3, fn($c) => true, 1);
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_211#0", 2);
            return;
};
