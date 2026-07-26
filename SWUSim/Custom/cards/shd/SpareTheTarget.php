<?php
// SHD_206
// Cost 3 - Spare the Target - [Cunning,Heroism]
// Text: Return an enemy non-leader unit to its owner's hand. Collect that unit's Bounties.

// ─── SHD_206 Spare the Target (Event) continuation ────────────────────────────
// Return an enemy non-leader unit to its owner's hand. Collect that unit's Bounties.
$customDQHandlers["SHD_206#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cid       = $o->CardID;
    $hasBounty = ObjectHasBounty($o) > 0;
    SWUBounceUnit(intval($player), $lastDecision);
    if ($hasBounty) {                                       // offer the bounty reward (may-use), like DoCaptureUnit
        DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip:"Collect_the_Bounty?");
        DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SWUCollectBounty|{$cid}", 1);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_206:0"] = function($player, $mzID = '') {
// Spare the Target — "Return an enemy non-leader unit to its owner's hand. Collect
                          // that unit's Bounties."
            $targets = array_merge(
                ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
                ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Return_an_enemy_unit_and_collect_its_Bounties", "SHD_206#0");
            return;
};
