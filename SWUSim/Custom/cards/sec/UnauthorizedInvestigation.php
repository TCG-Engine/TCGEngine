<?php
// SEC_181
// Cost 3 - Unauthorized Investigation - [Aggression]
// Text: Create a Spy token. / You may disclose Aggression (reveal a card from your hand with this aspect icon). If you do, create another Spy token.

// SEC_181 Unauthorized Investigation — disclose succeeded → create another Spy token.
$customDQHandlers["SEC_181#0"] = function($player, $parts, $lastDecision) {
    SWUCreateUnitToken(intval($player), 'SEC_T01');
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_181:0"] = function($player, $mzID = '') {
// Unauthorized Investigation — "Create a Spy token. You may disclose
                          // Aggression → create another Spy token."
            SWUCreateUnitToken(intval($player), 'SEC_T01');
            SWUQueueDisclose(intval($player), ['Aggression'], "SEC_181#0",
                "Disclose_Aggression_to_create_another_Spy_token");
            return;
};
