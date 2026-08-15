<?php
// LAW_264
// Cost 1 - From a Certain Point of View
// Text: Play a card from your hand, ignoring its aspect penalties.

// LAW_264 From a Certain Point of View — play the chosen hand card with its aspect penalty fully
// waived. Nested play (turn/PASS save-restore); the event's FINISH_PLAY_CARD owns the After Action.
$customDQHandlers["LAW_264#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $discount = SWUAspectPenalty(intval($player), $o->CardID);
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $lastDecision, false, $discount);
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_264:0"] = function($player, $mzID = '') {
{ // From a Certain Point of View — "Play a card from your hand, ignoring its
                          // aspect penalties." (Mirrors the LAW common-base play, but waives the FULL
                          // aspect penalty.) CleanupRemovedCards first so the just-played event isn't in
                          // the hand index (the LOF_150 trap), then offer affordable hand cards.
            global $playerID; $playerID = intval($player);
            DecisionQueueController::CleanupRemovedCards();
            $ready   = SWUTotalPaymentCapacity(intval($player));
            $targets = [];
            foreach (ZoneSearch("myHand") as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                $cid = $o->CardID;
                if (_SWUCantPlayFromHand($cid)) continue;
                $discount = SWUAspectPenalty(intval($player), $cid);
                $eff      = max(0, SWUComputePlayCost(intval($player), $o) - $discount);
                if ($ready >= $eff) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Play_a_card_(ignoring_aspect_penalties)", "LAW_264#0", may: true);
            return;
        }

        // ── IBH Events ─────────────────────────────────────────────────────────
};
