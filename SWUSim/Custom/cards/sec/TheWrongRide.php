<?php
// SEC_235
// The Wrong Ride
// Text: Exhaust 2 enemy resources. Plot (When you deploy a leader, you may play this card from your resources, paying its cost. Replace it with the top card of your deck.)

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_235:0"] = function($player, $mzID = '') {
// The Wrong Ride — Exhaust 2 enemy resources.
            global $playerID; $playerID = intval($player);
            // ⚠ "Exhaust 2 ENEMY resources" names no seat — the caster picks whose. Filtered to opponents
            // that actually have a ready resource (nothing to exhaust is a choice among nothing), and it
            // auto-resolves invisibly at one, so Premier is byte-identical.
            $eligible = [];
            foreach (OpponentsOf(intval($player)) as $o) {
                foreach (GetResources($o) as $r) { if (empty($r->removed) && intval($r->Status ?? 0) === 1) { $eligible[] = $o; break; } }
            }
            if (empty($eligible)) return;
            SWUQueueChooseOpponent(intval($player), 'SEC_235#EXH', "Exhaust_2_resources_of_which_opponent?", $eligible);
            return;
};

$customDQHandlers["SEC_235#EXH"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp > 0) SWUExhaustResources($opp, 2, true);   // exhaust up to 2 (as many as ready)
};
