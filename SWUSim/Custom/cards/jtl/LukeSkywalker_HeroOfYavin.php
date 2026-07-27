<?php
// JTL_012
// Cost 6 - Luke Skywalker - Hero of Yavin - [Aggression,Heroism] - Power 5 - HP 6 - Upgrade Power 4 - Upgrade HP 5
// Text: Action [Exhaust]: If you attacked with a Fighter unit this phase, deal 1 damage to a unit.
// DeployText: / This upgrade can't be defeated by enemy card abilities. / Attached unit is a leader unit. If it's a Fighter, it gains: "On Attack: You may deal 3 damage to a unit." /
// Epic Action: If you control 6 or more resources, choose one: / Deploy this leader. / Deploy this leader as an upgrade on a friendly Vehicle unit without a Pilot on it.

// JTL_012 — pilot grant, gated on the host being a Fighter: "If it's a Fighter, it gains: 'On Attack:
// You may deal 3 damage to a unit.'"
$onAttackAbilities["JTL_012:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $atk = GetZoneObject($mzID);
    if ($atk === null || !HasTrait($atk->CardID, 'Fighter')) return; // pilot-only + Fighter-host gate
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 3, 'side' => 'any', 'may' => true,
        'question' => "You_may_deal_3_to_a_unit", 'prompt' => "Deal_3_damage_to_a_unit",
    ]);
};

// JTL_012 Luke Skywalker — Leader Action [Exhaust]: If you attacked with a Fighter unit this phase,
// deal 1 damage to a unit. Condition = the SWU_ATTACKED_FIGHTER flag (set in CombatLogic).
$leaderAbilities["JTL_012"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    if (GlobalEffectCount($player, 'SWU_ATTACKED_FIGHTER') <= 0) { SWUAfterAction($player); return; }
    $targets = SWUAllUnits();
    if (empty($targets)) { SWUAfterAction($player); return; } // no unit to hit → fizzle
    SWUQueueChooseTarget($player, $targets, "Deal_1_damage_to_a_unit", "DEAL_UNIT_DAMAGE|1");
    SWUQueueAfterAction($player);
};
