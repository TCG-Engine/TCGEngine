<?php
// LOF_188
// Cost 1 - As I Have Foreseen - [Cunning,Villainy]
// Text: Look at the top card of your deck. You may use the Force (lose your Force token). If you do, play that card. It costs 4 resources less.

// LOF_188 As I Have Foreseen — YES: use the Force + play the top deck card at a 4-resource discount.
$customDQHandlers["LOF_188#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    SWUPlayTopDeckCard(intval($player), false, 4); // pay (printed cost − 4)
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_188:0"] = function($player, $mzID = '') {
// As I Have Foreseen — "Look at the top card. You may use the Force. If you do,
                          // play that card. It costs 4 resources less." (No Force → you just looked.)
            if (!PlayerHasTheForce(intval($player))) return;
            // Only offer to use the Force if the top card is affordable at its −4 discount — otherwise the
            // Force would be spent for a play that can't happen. If unaffordable, the player just looked.
            global $playerID; $playerID = intval($player);
            $idx = _SWUTopDeckFrontIdx(intval($player));
            if ($idx === -1) return;
            $topObj = GetDeck(intval($player))[$idx];
            if (max(0, SWUComputePlayCost(intval($player), $topObj) - 4)
                > SWUResourceCount(intval($player), readyOnly: true)) return;
            DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
                tooltip: "Use_the_Force_to_play_the_top_card_(4_less)?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_188#0", 1);
            return;
};
