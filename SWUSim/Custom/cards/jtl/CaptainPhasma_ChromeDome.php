<?php
// JTL_010
// Cost 5 - Captain Phasma - Chrome Dome - [Aggression,Villainy] - Power 4 - HP 6
// Text: Action [Exhaust]: If you played a First Order card this phase, deal 1 damage to a base.
// DeployText: On Attack: If you played another First Order card this phase, you may deal 1 damage to a unit. If you do, deal 1 damage to a base.
// Epic Action: If you control 5 or more resources, deploy this leader.

// ── JTL_010 Captain Phasma (deployed leader unit) — On Attack ───────────────
// "If you played another First Order card this phase, you may deal 1 damage to a unit. If you do, deal
// 1 damage to a base." Gated on SWU_PLAYED_FO. The may-choose routes to the JTL_010 continuation, which
// deals 1 to the chosen unit then offers the base.
$onAttackAbilities["JTL_010:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_PLAYED_FO') <= 0) return;
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_deal_1_damage_to_a_unit", "Deal_1_damage_to_a_unit", "JTL_010#0");
};

// Deploy continuation: deal 1 to the chosen unit ($lastDecision), then "if you do" deal 1 to a base.
$customDQHandlers["JTL_010#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    SWUQueueChooseTarget(intval($player), ['myBase-0', 'theirBase-0'],
        "Deal_1_damage_to_a_base", "DEAL_BASE_DAMAGE|1");
};

// JTL_010 Captain Phasma — Leader Action [Exhaust]: If you played a First Order card this phase, deal 1
// damage to a base. Condition = SWU_PLAYED_FO flag; the base target is chosen (DEAL_BASE_DAMAGE|1).
$leaderAbilities["JTL_010"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    if (GlobalEffectCount($player, 'SWU_PLAYED_FO') <= 0) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, ['myBase-0', 'theirBase-0'],
        "Deal_1_damage_to_a_base", "DEAL_BASE_DAMAGE|1");
    SWUQueueAfterAction($player);
};
