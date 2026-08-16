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
    global $Bounty_Cards;
    $cid        = $o->CardID;
    $controller = intval($o->Controller ?? OtherPlayer(intval($player)));
    // "Collect that unit's BountieS" — plural, and per CR 13.b each Bounty is an INDEPENDENT ability, so
    // a unit carrying an innate Bounty AND a granted one (an attached Bounty upgrade) yields TWO
    // collections. Snapshot the granted rewards BEFORE the bounce, because the bounce defeats the
    // upgrades that carry them — the same ordering the defeat and capture paths use, via the same shared
    // builder. Previously this queued ONE collection keyed on the returned unit's own CardID, so a
    // granted Bounty silently resolved to nothing and a second Bounty was never offered at all.
    $granted   = _SWUGrantedBountyRewards($o, $cid, $controller);
    $hasInnate = isset($Bounty_Cards[$cid]) && !_SWUGalenSuppressesCard($controller, $cid);
    SWUBounceUnit(intval($player), $lastDecision);
    if ($hasInnate) {                                       // offer the bounty reward (may-use), like DoCaptureUnit
        DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip:"Collect_the_Bounty?");
        DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SWUCollectBounty|{$cid}", 1);
    }
    if (!_SWUGalenSuppressesCard($controller, $cid)) {
        foreach ($granted as $rw) {
            DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip:"Collect_the_Bounty?");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SWUCollectBounty|{$rw['reward']}|{$rw['param']}", 1);
        }
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
