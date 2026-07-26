<?php
// SEC_016
// Cost 6 - Padmé Amidala - What Do You Have to Hide? - [Cunning,Heroism] - Power 3 - HP 8
// Text: When you reveal or discard 1 or more cards from your hand: You may exhaust this leader. If you do, deal 1 damage to a unit.
// DeployText: When you reveal or discard 1 or more cards from your hand: You may deal 1 damage to a unit.
// Epic Action: If you control 6 or more resources, deploy this leader.

// Front-side YES → exhaust the leader, then deal 1 to a unit.
$customDQHandlers["SEC_016#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    $leaderArr = &GetLeader(intval($player));
    foreach ($leaderArr as &$l) { if (($l->CardID ?? '') === 'SEC_016' && empty($l->removed)) { $l->Ready = false; break; } }
    unset($l);
    $targets = _SWUAllUnitsOnly(intval($player));
    if (empty($targets)) return;
    SWUQueueChooseTarget($player, $targets, "Deal_1_damage_to_a_unit", "DEAL_UNIT_DAMAGE|1");
};
