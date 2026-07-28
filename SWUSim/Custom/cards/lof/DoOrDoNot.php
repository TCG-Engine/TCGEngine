<?php
// LOF_175
// Cost 2 - Do or Do Not - [Aggression]
// Text: You may use the Force (lose your Force token). If you do, draw 2 cards. If you do not, draw a card.

// LOF_175 Do or Do Not — YES: use the Force + draw 2; NO: draw 1.
$customDQHandlers["LOF_175#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === 'YES') { UseTheForce(intval($player)); DoDrawCard(intval($player), 2); return; }
    DoDrawCard(intval($player), 1);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_175:0"] = function($player, $mzID = '') {
// Do or Do Not — "You may use the Force. If you do, draw 2. If you do not, draw 1."
            if (!PlayerHasTheForce(intval($player))) { DoDrawCard(intval($player), 1); return; }
            DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
                tooltip: "Use_the_Force_to_draw_2?_(otherwise_draw_1)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_175#0", 1);
            return;
};
