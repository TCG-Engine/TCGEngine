<?php
// SEC_245
// Cost 1 - When Has Become Now - [Villainy]
// Text: Play a card with Plot from your resources (paying its cost). Put the top card of your deck into play as a resource.

// Continuation: $lastDecision = chosen Plot resource mzID, or '-' to decline. Play it from resources
// (paying its cost) via a guarded nested ActivateCard, then ramp.
$customDQHandlers["SEC_245#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    $resMz = $lastDecision ?? '';
    if ($resMz !== '' && $resMz !== '-' && $resMz !== 'PASS') {
        $o = GetZoneObject($resMz);
        if ($o !== null && empty($o->removed)) {
            AddGameLogEntry('PLAY', 'P' . intval($player) . ' plays ' . GameLogCardRef($o->CardID) . ' with Plot (When Has Become Now)');
            // Nested play from resources, guarded so the inner ActivateCard's After Action doesn't
            // double-advance SEC_245's own event action (JTL_089#1 turn/PASS save-restore).
            $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
            ActivateCard(intval($player), $resMz, false);
            $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
        }
    }
    _SWUSec245Ramp(intval($player));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_245:0"] = function($player, $mzID = '') {
// When Has Become Now — play a card with Plot from your resources (paying its
                          // cost), then put the top card of your deck into play as a (ready) resource.
                          // Does NOT trigger the Plot keyword's deploy "replace with top of deck" (that's
                          // the leader-deploy ability); SEC_245's own ramp clause is the refill. "You may
                          // play" (Plot is optional) → MZMAYCHOOSE; the ramp happens regardless.
            global $playerID, $Plot_Cards; $playerID = intval($player);
            $ready = SWUTotalPaymentCapacity(intval($player));
            $resources = &GetResources(intval($player));
            $targets = [];
            $pos = 0;
            for ($i = 0; $i < count($resources); $i++) {
                if (!empty($resources[$i]->removed)) continue;
                $here = $pos; $pos++;
                $cid = $resources[$i]->CardID ?? '';
                if (!isset($Plot_Cards[$cid])) continue;
                if (SWUCardPlayBlocked(intval($player), $cid)) continue;
                if (SWUComputePlayCost(intval($player), $resources[$i]) > $ready) continue;
                $targets[] = "myResources-{$here}";
            }
            if (empty($targets)) { _SWUSec245Ramp(intval($player)); return; }  // no playable Plot → just ramp
            SWUQueueMayChooseTarget(intval($player), $targets,
                "Play_a_Plot_card_from_your_resources?", "Choose_a_Plot_card_to_play", "SEC_245#0");
            return;
};
