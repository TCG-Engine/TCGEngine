<?php
// SEC_013
// Cost 5 - Luthen Rael - Don't You Want to Fight For Real? - [Aggression,Heroism] - Power 2 - HP 7
// Text: When a friendly unit is defeated while attacking: You may exhaust this leader. If you do, deal 1 damage to a unit or base.
// DeployText: When a friendly unit is defeated while attacking: You may deal 2 damage to a unit or base.
// Epic Action: If you control 5 or more resources, deploy this leader.

// Front-side YES → exhaust the leader (the cost), then deal 1 to a unit or base.
$customDQHandlers["SEC_013#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    $leaderArr = &GetLeader(intval($player));
    foreach ($leaderArr as &$l) {
        if (($l->CardID ?? '') === 'SEC_013' && empty($l->removed)) { $l->Ready = false; break; }
    }
    unset($l);
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'DEAL_TARGET', 'amount' => 1, 'includeBases' => true,
        'prompt' => "Deal_1_damage_to_a_unit_or_base",
    ]);
};
