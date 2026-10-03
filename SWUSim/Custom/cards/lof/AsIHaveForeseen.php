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
            global $playerID; $playerID = intval($player);
            $idx = _SWUTopDeckFrontIdx(intval($player));
            if ($idx === -1) return;   // empty deck → nothing to look at
            $topObj = GetDeck(intval($player))[$idx];
            $topID  = $topObj->CardID;
            // Only offer to use the Force if the top card is affordable at its −4 discount — otherwise the
            // Force would be spent for a play that can't happen.
            $canOffer = PlayerHasTheForce(intval($player))
                && max(0, SWUComputePlayCost(intval($player), $topObj) - 4) <= SWUTotalPaymentCapacity(intval($player));
            if (!$canOffer) {
                // "Look at the top card of your deck" still happens, so SHOW it (the SOR_238 C-3PO / ASH_229
                // Camtono whiff pattern). Returning silently logged "had no effect" and looked broken.
                DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "@{$topID}&OK", 1,
                    tooltip: "As_I_Have_Foreseen:_the_top_card_stays_on_top");
                return;
            }
            // The offer shows the card it would play (a bare YES/NO named nothing).
            DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "@{$topID}&YES&NO", 1,
                tooltip: "Use_the_Force_to_play_the_top_card_(4_less)?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_188#0", 1);
            return;
};
