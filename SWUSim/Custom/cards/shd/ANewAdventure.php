<?php
// SHD_207
// Cost 2 - A New Adventure - [Cunning,Cunning]
// Text: Return a non-leader unit that costs 6 or less to its owner's hand. Then, its owner may play it for free.

// ─── SHD_207 A New Adventure (Event) continuation ─────────────────────────────
// Return the chosen ≤6-cost non-leader unit to its owner's hand; then its owner may play it for free
// (reuses LOF_185#2 for the free-replay).
$customDQHandlers["SHD_207#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $owner = intval($obj->Owner ?? $player);
    if ($owner <= 0) $owner = $player;
    if (!SWUBounceUnit(intval($player), $lastDecision)) return;
    $hand = GetHand($owner);
    $idx  = count($hand) - 1;
    if ($idx < 0) return;
    $playerID = $owner;
    DecisionQueueController::AddDecision($owner, 'YESNO', '-', 1, tooltip:"Play_the_returned_unit_for_free?");
    DecisionQueueController::AddDecision($owner, 'CUSTOM', "LOF_185#2|myHand-{$idx}", 1);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_207:0"] = function($player, $mzID = '') {
// A New Adventure — "Return a non-leader unit that costs 6 or less to its owner's
                          // hand. Then, its owner may play it for free."
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) <= 6) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Return_a_non-leader_unit_(cost_6_or_less)", "SHD_207#0");
            return;
};
