<?php
// JTL_011
// Cost 4 - Major Vonreg - Red Baron - [Aggression,Villainy] - Power 2 - HP 5 - Upgrade Power 3 - Upgrade HP 3
// Text: Action [Exhaust]: Play a Vehicle unit from your hand (paying its cost). If you do, give another unit +1/+0 for this phase.
// DeployText: / Attached unit is a leader unit. It gains: "On Attack: You may give another unit in this arena +1/+0 for this phase." /
// Epic Action: If you control 4 or more resources, choose one: / Deploy this leader. / Deploy this leader as an upgrade on a friendly Vehicle unit without a Pilot on it.

// JTL_011 Major Vonreg — pilot grant: "On Attack: You may give another unit in this arena +1/+0 for
// this phase." (The front Action buffs "another unit" in any arena; the deployed side is arena-local.)
$onAttackAbilities["JTL_011:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $atk = GetZoneObject($mzID);
    if ($atk === null || ($atk->CardID ?? '') === 'JTL_011') return; // pilot-only grant
    $arena = (strpos($mzID, 'Space') !== false) ? 'Space' : 'Ground';
    $hostUid = intval($atk->UniqueID ?? 0);
    $targets = [];
    foreach (array_merge(ZoneSearch("my{$arena}Arena", AnyUnitFilter), ZoneSearch("their{$arena}Arena", AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->UniqueID ?? 0) === $hostUid) continue; // "another" excludes the attacking host
        $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_buff_another_unit_in_this_arena", "Give_another_unit_in_this_arena_+1/+0", "APPLY_PHASE_BUFF|1|0|JTL_011");
};

// ── JTL_011 Major Vonreg (leader action: play Vehicle, then buff ANOTHER unit) ───────────────────
// $lastDecision = the chosen Vehicle hand card. Snapshot in-play unit UIDs, play the Vehicle (full
// cost; ActivateCard owns the after-action), then queue the +1/+0 step at block 1 carrying the
// newly-played unit's UID so "another unit" excludes it.
$customDQHandlers["JTL_011#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) {
        SWUAfterAction(intval($player));
        return;
    }
    global $playerID;
    $playerID = intval($player);
    $before = [];
    foreach (GetField(intval($player)) as $u) {
        if ($u !== null && empty($u->removed)) $before[] = intval($u->UniqueID ?? 0);
    }
    ActivateCard(intval($player), $lastDecision, false, 0);
    $newUid = 0;
    foreach (GetField(intval($player)) as $u) {
        if (SWUObjGone($u)) continue;
        $uid = intval($u->UniqueID ?? 0);
        if (!in_array($uid, $before, true)) { $newUid = $uid; break; }
    }
    DecisionQueueController::AddDecision($player, 'CUSTOM', "JTL_011#1|{$newUid}", 1);
};

// Buff step: give ANOTHER unit (any unit except the just-played one, $parts[0] = its UID) +1/+0 this
// phase. Mandatory choose; no after-action (the play's FINISH_PLAY_CARD owns it).
$customDQHandlers["JTL_011#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $excludeUid = intval($parts[0] ?? 0);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->UniqueID ?? 0) === $excludeUid) continue; // "another" excludes the played unit
        $targets[] = $mz;
    }
    if (empty($targets)) return; // no other unit → buff fizzles
    SWUQueueChooseTarget(intval($player), $targets,
        "Give_another_unit_+1/+0_this_phase", "APPLY_PHASE_BUFF|1|0|JTL_011");
};

// JTL_011 Major Vonreg — Leader Action [Exhaust]: Play a Vehicle unit from your hand (paying its cost).
// If you do, give another unit +1/+0 for this phase. Continuation in CardDQHandlers.php ("JTL_011" plays
// the chosen Vehicle then "JTL_011#1" buffs another unit, excluding the just-played one by UniqueID).
$leaderAbilities["JTL_011"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $targets = [];
    foreach (SWUHandPlayablesAtDiscount($player, ['Unit'], 0) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && HasTrait($o->CardID, 'Vehicle')) $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; } // no Vehicle to play → action spent
    SWUQueueChooseTarget($player, $targets, "Play_a_Vehicle_unit_from_your_hand", "JTL_011#0");
};
