<?php
// ASH_203
// Cost 2 - Mando's N-1 Starfighter - Faster than a Fathier - [Cunning,Heroism] - Power 1 - HP 3
// Text: Support / On Attack: You may exhaust a friendly (non-upgrade) leader. If you do, this unit gets +2/+0 for this attack.

// ASH_203 Mando's N-1 Starfighter — On Attack: you may exhaust a friendly (non-upgrade) leader. If you
// do, this unit gets +2/+0 for this attack. Offered only when a friendly leader is ready to pay.
$onAttackAbilities["ASH_203:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $ld = GetLeader(intval($player));
    if (empty($ld) || empty($ld[0]) || empty($ld[0]->Ready)) return; // no ready leader → can't pay
    $self = GetZoneObject($mzID); $uid = SWUObjUID($self, 0);
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Exhaust_your_leader_for_+2/+0_this_attack?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_203#0|{$uid}", 1);
};

$customDQHandlers["ASH_203#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $leaderArr = &GetLeader(intval($player));
    if (empty($leaderArr) || empty($leaderArr[0]) || empty($leaderArr[0]->Ready)) return;
    $leaderArr[0]->Ready = false;   // exhaust the leader (the cost)
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz !== null) SWUAddAttackPowerBonus($mz, 2);
};
