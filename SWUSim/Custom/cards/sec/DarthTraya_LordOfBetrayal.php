<?php
// SEC_188
// Cost 3 - Darth Traya - Lord of Betrayal - [Cunning,Villainy] - Power 2 - HP 5
// Text: On Attack: You may ready a non-unit leader.

// SEC_188 Darth Traya — On Attack: you may ready a non-unit (undeployed) leader. "a non-unit leader"
// has NO "friendly" qualifier → EITHER player's undeployed leader is a legal target (readying an enemy
// leader is a downside, but it's a legal choice). Each player has at most one leader, so a You/Opponent
// picker suffices; the "you may" adds a Pass to decline. A DEPLOYED leader (leader-unit) is excluded.
$onAttackAbilities["SEC_188:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $p = intval($player);
    $exhaustedUndeployedLeader = function($pl) {
        foreach (GetLeader($pl) as $l) {
            if (empty($l->removed) && empty($l->Deployed) && empty($l->Ready)) return true;
        }
        return false;
    };
    // ⚠ "Ready A NON-UNIT LEADER" is UNQUALIFIED — any player's leader, yours included (readying an
    // opponent's is a downside, but it is a legal choice). The old picker was a literal You/Opponent pair
    // built from OtherPlayer($p), so above two seats only seat 2's leader could ever be offered.
    // Seats are now offered as "P{n}" tokens, which SWUDecodePlayerPick already understands alongside
    // the legacy You/Opponent labels.
    $opts = [];
    foreach (GetLiveSeatsArray() as $seat) {
        if ($exhaustedUndeployedLeader($seat)) $opts[] = "P{$seat}";
    }
    if (empty($opts)) return;   // no undeployed exhausted leader anywhere → nothing to ready, no prompt
    $opts[] = 'Pass';           // "you may" → allow declining
    DecisionQueueController::AddDecision($p, "OPTIONCHOOSE", implode('&', $opts), 1,
        tooltip: "Ready_a_non-unit_leader?");
    DecisionQueueController::AddDecision($p, "CUSTOM", "SEC_188#0|{$p}", 1);
};

$customDQHandlers["SEC_188#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === 'Pass') return;
    global $playerID; $playerID = intval($player);
    $caster = intval($parts[0] ?? $player);
    $target = SWUDecodePlayerPick($lastDecision, $caster);
    $leader = &GetLeader($target);
    for ($i = 0; $i < count($leader); $i++) {
        if (empty($leader[$i]->removed) && empty($leader[$i]->Deployed) && empty($leader[$i]->Ready)) {
            $leader[$i]->Ready = true;
            break;
        }
    }
};
