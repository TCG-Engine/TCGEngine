<?php
// SHD_005
// Cost 6 - Hondo Ohnaka - That's Good Business - [Command,Villainy] - Power 3 - HP 7
// Text: When you play a card using Smuggle: You may exhaust this leader. If you do, give an Experience token to a unit.
// DeployText: Raid 1 (This unit gets +1/+0 while attacking.) / When you play a card using Smuggle: You may give an Experience token to a unit.
// Epic Action: If you control 6 or more resources, deploy this leader.

$customDQHandlers["SHD_005#exhaust"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    $leaderArr = &GetLeader(intval($player));
    foreach ($leaderArr as &$l) { if (($l->CardID ?? '') === 'SHD_005' && empty($l->removed)) { $l->Ready = false; break; } }  // exhaust the leader (cost)
    unset($l);
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_an_Experience_token", "GIVE_EXPERIENCE|1");
};
