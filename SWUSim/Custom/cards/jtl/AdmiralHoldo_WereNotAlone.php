<?php
// JTL_007
// Cost 6 - Admiral Holdo - We're Not Alone - [Command,Heroism] - Power 3 - HP 8
// Text: Action [1 resource, Exhaust]: Give a Resistance unit or a unit with a Resistance upgrade on it +2/+2 for this phase.
// DeployText: On Attack: You may give another Resistance unit or a unit with a Resistance upgrade on it +2/+2 for this phase.
// Epic Action: If you control 6 or more resources, deploy this leader.

// ── JTL_007 Admiral Holdo (deployed leader unit) — On Attack: may buff ANOTHER Resistance unit ──
// "You may give another Resistance unit (or a unit with a Resistance upgrade) +2/+2 for this phase."
// $mzID is Holdo's mzID; exclude her by UniqueID. On-Attack doesn't close the action (combat owns it).
$onAttackAbilities["JTL_007:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUid = ($self !== null) ? intval($self->UniqueID ?? 0) : 0;
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || intval($o->UniqueID ?? 0) === $selfUid) continue; // "another" excludes Holdo
        if (_SWUIsResistanceTarget($o)) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_give_another_Resistance_unit_+2/+2", "Give_+2/+2_this_phase", "APPLY_PHASE_BUFF|2|2|JTL_007");
};

// JTL_007 Admiral Holdo — Leader Action [1 resource, Exhaust]: Give a Resistance unit (or a unit with
// a Resistance upgrade on it) +2/+2 for this phase. Target = any qualifying unit (friendly or enemy);
// the +2/+2 flows through APPLY_PHASE_BUFF (registered token JTL_007, expires at regroup).
$leaderAbilities["JTL_007"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        if (_SWUIsResistanceTarget(GetZoneObject($mz))) $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; } // no eligible unit → action spent
    SWUQueueChooseTarget($player, $targets,
        "Give_a_Resistance_unit_+2/+2_this_phase", "APPLY_PHASE_BUFF|2|2|JTL_007");
    SWUQueueAfterAction($player);
};
