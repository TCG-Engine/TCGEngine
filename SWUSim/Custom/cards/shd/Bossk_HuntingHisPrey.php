<?php
// SHD_010
// Cost 5 - Bossk - Hunting His Prey - [Villainy,Aggression] - Power 4 - HP 6
// Text: Action [Exhaust]: Deal 1 damage to a unit with a Bounty. You may give it +1/+0 for this phase.
// DeployText: When you collect a bounty: You may collect that bounty again. Use this ability only once each round.
// Epic Action: If you control 5 or more resources, deploy this leader.

// ── SHD_010 Bossk ──────────────────────────────────────────────────────────────
// Front Action [Exhaust]: Deal 1 damage to a unit with a Bounty. You may give it +1/+0 for this phase.
// Deployed: "When you collect a bounty: you may collect that bounty again. Once each round." (reactive)
$leaderAbilities["SHD_010"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && HasKeyword_Bounty($o)) $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; }   // no unit with a Bounty → action fizzles
    SWUQueueChooseTarget($player, $targets, "Deal_1_to_a_unit_with_a_Bounty", "SHD_010#front");
    SWUQueueAfterAction($player);
};

$customDQHandlers["SHD_010#front"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $uid = intval($o->UniqueID ?? 0);
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    if (SWUFindMzByUID($uid) === null) return;                 // defeated by the 1 → no unit left to buff
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip:"Give_it_+1/+0_for_this_phase?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SHD_010#buff|{$uid}", 1);
};

$customDQHandlers["SHD_010#buff"] = function($player, $parts, $lastDecision) {
    if (($lastDecision ?? '') !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz !== null) SWUApplyPhaseBuff($mz, 1, 0, 'SHD_010');   // +1/+0 for this phase
};
