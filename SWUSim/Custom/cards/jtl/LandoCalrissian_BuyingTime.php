<?php
// JTL_003
// Cost 7 - Lando Calrissian - Buying Time - [Vigilance,Heroism] - Power 5 - HP 7 - Upgrade Power 5 - Upgrade HP 5
// Text: Action [1 resource, Exhaust]: Play a unit from your hand (paying its cost). If you do and you control a ground unit and a space unit, give a Shield token to a unit.
// DeployText: Sentinel / Attached unit is a leader unit. / Attached unit gains Sentinel. / When deployed as an upgrade: You may give a Shield token to a unit in a different arena. /
// Epic Action: If you control 7 or more resources, choose one: / Deploy this leader. / Deploy this leader as an upgrade on a friendly Vehicle unit without a Pilot on it.

// ── JTL_003 Lando Calrissian (leader action: play unit, then conditional Shield) ─────────────────
// $lastDecision = the chosen hand unit. Queue the post-play Shield check FIRST (block 1, runs before
// the play's FINISH_PLAY_CARD at block 10), then play the unit at full cost — ActivateCard owns the
// end-of-action. The "JTL_003#1" step runs once the unit is on the board.
$customDQHandlers["JTL_003#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) {
        SWUAfterAction(intval($player));
        return;
    }
    global $playerID;
    $playerID = intval($player);
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_003#1', 1);
    ActivateCard(intval($player), $lastDecision, false, 0);
};

// If, after the play, P1 controls both a ground unit and a space unit, give a Shield token to a unit
// (any unit). Mandatory choose; GIVE_SHIELD doesn't close the action (FINISH_PLAY_CARD does).
$customDQHandlers["JTL_003#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $hasGround = !empty(ZoneSearch("myGroundArena", AnyUnitFilter));
    $hasSpace  = !empty(ZoneSearch("mySpaceArena",  AnyUnitFilter));
    if (!$hasGround || !$hasSpace) return; // condition not met → no Shield
    GiveTokenUpgrade($player, '', ['token'=>'SHIELD','friendlyOnly'=>false,'prompt'=>"Give_a_Shield_token_to_a_unit"]);
};

// JTL_003 Lando Calrissian — When deployed as an upgrade: You may give a Shield token to a unit in a
// DIFFERENT arena than the host Vehicle.
$whenPlayedAsUpgradeAbilities["JTL_003:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    // Host arena from its mzID; target the OTHER arena (both players' units).
    $otherZones = (strpos($mzID, 'Space') !== false)
        ? ['myGroundArena', 'theirGroundArena']
        : ['mySpaceArena',  'theirSpaceArena'];
    $targets = [];
    foreach ($otherZones as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Give_a_Shield_to_a_unit_in_a_different_arena", "Choose_a_unit_to_Shield", "GIVE_SHIELD");
};

// JTL_003 Lando Calrissian — Leader Action [1 resource, Exhaust]: Play a unit from your hand (paying
// its cost). If you do and you control a ground unit and a space unit, give a Shield token to a unit.
// The 1-resource is paid here (after the affordability gate); affordability of the played unit is
// against the remaining resources. Continuation in CardDQHandlers.php ("JTL_003" → "JTL_003#1").
$leaderAbilities["JTL_003"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; } // gate should prevent
    $targets = [];
    foreach (SWUHandPlayablesAtDiscount($player, ['Unit'], 0) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; } // no affordable unit → action spent
    // MAY-choose: "Play a unit from your hand" is optional — the player may decline (a soft pass that still
    // paid 1 + exhausted the leader), and hand contents stay hidden. Even with one affordable unit, the
    // decline option must be offered (so it does NOT auto-resolve like a mandatory single-target choose).
    SWUQueueMayChooseTarget($player, $targets, "Play_a_unit_from_your_hand?", "Play_a_unit_from_your_hand", "JTL_003#0");
};
