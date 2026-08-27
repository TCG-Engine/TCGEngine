<?php
// SOR_013
// Cost 6 - Cassian Andor - Dedicated to the Rebellion - [Aggression,Heroism] - Power 4 - HP 6
// Text: Action [1 resource, Exhaust]: If you've dealt 3 or more damage to an enemy base this phase, draw a card.
// DeployText: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / When you deal damage to an enemy base: You may draw a card. Use this ability only once each round.
// Epic Action: If you control 6 or more resources, deploy this leader.

// SOR_013 Cassian Andor (deployed) — optional draw on the once-per-round base-damage trigger. The
// round's use was consumed at collect time, so declining (NO) just draws nothing.
$customDQHandlers["SOR_013#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    DoDrawCard(intval($player), 1);
};

// SOR_013 Cassian Andor — Leader Action [1 resource, Exhaust]: If you've dealt 3 or more damage to
// an enemy base this phase, draw a card. The 1-resource affordability is gated in
// SWULeaderActionAffordable; the leader exhausts via SWULeaderAction. The cumulative damage is the
// SWU_BASEDMG_AMT_{opponent} counter (one flag per point), set in SWUDealDamageToBase. Like Iden
// (SOR_002), the action is still spent if the condition fails.
$leaderAbilities["SOR_013"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    // ⚠ "If you've dealt 3 or more damage to AN enemy base this phase" — EXISTENTIAL, and the threshold
    // is PER BASE, never a sum across bases: 2 into seat 2 and 2 into seat 4 is not "3 or more to an
    // enemy base". This checked only OtherPlayer($player), so damage dealt to any other seat did not count.
    foreach (OpponentsOf($player) as $o) {
        if (GlobalEffectCount($player, 'SWU_BASEDMG_AMT_' . $o) >= 3) { DoDrawCard($player, 1); break; }
    }
    SWUAfterAction($player);
};
