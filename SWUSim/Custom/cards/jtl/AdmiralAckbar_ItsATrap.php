<?php
// JTL_016
// Cost 6 - Admiral Ackbar - It's A Trap! - [Cunning,Heroism] - Power 3 - HP 8
// Text: Action [1 resource, Exhaust]: Exhaust a non-leader unit. If you do, its controller creates an X-Wing token.
// DeployText: On Attack: You may exhaust a unit. If you do, its controller creates an X-Wing token.
// Epic Action: If you control 6 or more resources, deploy this leader.

// ── JTL_016 Admiral Ackbar — leader action AND deploy On Attack: exhaust a non-leader unit → its
// controller creates an X-Wing token. Shared continuation (no after-action; the leader action queues
// SWU_AFTER_ACTION separately, and On Attack is owned by combat). No-ops on a '-' may-decline.
$customDQHandlers["JTL_016#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $controller = intval($obj->Controller ?? $player);
    // "Exhaust a non-leader unit. IF YOU DO, its controller creates an X-Wing." Exhausting an already-
    // exhausted unit does nothing, so the "if you do" fails and no X-Wing is created.
    $wasReady = intval($obj->Status ?? 0) === 1;
    OnExhaustCard(intval($player), $lastDecision);
    if (!$wasReady) return;
    SWUCreateUnitToken($controller, 'JTL_T02'); // X-Wing (Space, 2/2)
};

// Deploy On Attack: "You may exhaust a unit. If you do, its controller creates an X-Wing token."
$onAttackAbilities["JTL_016:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_exhaust_a_unit", "Exhaust_a_unit_(its_controller_creates_an_X-Wing)", "JTL_016#0");
};

// JTL_016 Admiral Ackbar — Leader Action [1 resource, Exhaust]: Exhaust a non-leader unit. If you do,
// its controller creates an X-Wing token. The continuation exhausts the chosen unit and gives its
// CONTROLLER (which may be the opponent) an X-Wing.
$leaderAbilities["JTL_016"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $targets = array_merge(
        ZoneSearch("myGroundArena",    NonLeaderUnitFilter),
        ZoneSearch("mySpaceArena",     NonLeaderUnitFilter),
        ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
        ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
    );
    if (empty($targets)) { SWUAfterAction($player); return; } // no non-leader unit → action spent
    SWUQueueChooseTarget($player, $targets,
        "Exhaust_a_non-leader_unit_(its_controller_creates_an_X-Wing)", "JTL_016#0");
    SWUQueueAfterAction($player);
};
