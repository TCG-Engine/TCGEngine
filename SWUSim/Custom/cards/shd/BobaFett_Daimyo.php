<?php
// SHD_008
// Cost 6 - Boba Fett - Daimyo - [Command,Heroism] - Power 4 - HP 7
// Text: When you play a unit that has 1 or more keywords: You may exhaust this leader. If you do, give a friendly unit +1/+0 for this phase.
// DeployText: Each other friendly unit that has 1 or more keywords gets +1/+0.
// Epic Action: If you control 6 or more resources, deploy this leader.

$customDQHandlers["SHD_008#front"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    $leaderArr = &GetLeader(intval($player));
    foreach ($leaderArr as &$l) { if (($l->CardID ?? '') === 'SHD_008' && empty($l->removed)) { $l->Ready = false; break; } }  // exhaust the leader (cost)
    unset($l);
    $friendly = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter));
    if (empty($friendly)) return;
    SWUQueueChooseTarget(intval($player), $friendly, "Give_a_friendly_unit_+1/+0_for_this_phase", "APPLY_PHASE_BUFF|1|0|SHD_008");
};
