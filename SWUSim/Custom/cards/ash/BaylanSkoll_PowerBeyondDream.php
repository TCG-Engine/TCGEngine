<?php
// ASH_003
// Cost 5 - Baylan Skoll - Power Beyond Dream - [Vigilance,Villainy] - Power 4 - HP 6
// Text: Action [1 resource, Exhaust]: Give a friendly unit +2/+2 for this phase if it's the only unit you control in its arena.
// DeployText: On Attack: You may give a friendly unit +2/+2 and Sentinel for this phase if it's the only non-leader unit you control in its arena. (Enemy units in its arena must attack a Sentinel when they attack you.)
// Epic Action: If you control 5 or more resources, deploy this leader.

// ASH_003 Baylan Skoll — may give a friendly unit +2/+2 AND Sentinel for this phase
// if it's the only NON-leader unit you control in its arena.
$onAttackAbilities["ASH_003:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        $units = ZoneSearch($z, NonLeaderUnitFilter);   // excludes the deployed leader itself
        if (count($units) === 1) $targets[] = $units[0]; // the only non-leader unit in its arena
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Buff_a_lone_unit?",
        "Give_+2/+2_and_Sentinel_to_a_unit_alone_in_its_arena", "ASH_003#0");
};

// ASH_003 Baylan Skoll — Action [1 resource, Exhaust]: give a friendly unit +2/+2 for this phase if it's
// the only unit you control in its arena. Only offer units that are alone in their arena.
$leaderAbilities["ASH_003"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUPayInlineAbilityCost($player, 1)) { SWUAfterAction($player); return; }
    $ground = ZoneSearch('myGroundArena', AnyUnitFilter);
    $space  = ZoneSearch('mySpaceArena',  AnyUnitFilter);
    $targets = [];
    if (count($ground) === 1) $targets[] = $ground[0];   // the lone ground unit
    if (count($space)  === 1) $targets[] = $space[0];    // the lone space unit
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Give_+2/+2_to_a_unit_alone_in_its_arena", "APPLY_PHASE_BUFF|2|2|ASH_003");
    SWUQueueAfterAction($player);
};

// ── Deployed Leader Unit "On Attack" abilities (leader-gaps.md Group A) ──────
// Each is the DEPLOYED side of an ASH leader whose front-side Action is in
// LeaderAbilities.php. Mandatory multi-target MZCHOOSE is skipped in OnAttack
// ($playerID restore), so "may" picks use MZMAYCHOOSE; combat owns the after-action.
$customDQHandlers["ASH_003#0"] = function ($player, $parts, $lastDecision) {
  if (SWUDecisionDeclined($lastDecision))
    return;
  global $playerID;
  $playerID = intval($player);
  SWUApplyPhaseBuff($lastDecision, 2, 2, 'ASH_003');
  AddTurnEffect($lastDecision, 'SENTINEL^ASH_003');
};
