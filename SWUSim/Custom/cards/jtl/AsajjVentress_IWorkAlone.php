<?php
// JTL_001
// Cost 6 - Asajj Ventress - I Work Alone - [Vigilance,Villainy] - Power 4 - HP 6 - Upgrade Power 3 - Upgrade HP 4
// Text: Action [Exhaust]: Deal 1 damage to a friendly unit. If you do, deal 1 damage to an enemy unit in the same arena.
// DeployText: Grit / Attached unit is a leader unit. It gains Grit and: "On Attack: You may deal 1 damage to a friendly unit. If you do, deal 1 damage to an enemy unit in the same arena." /
// Epic Action: If you control 6 or more resources, choose one: / Deploy this leader. / Deploy this leader as an upgrade on a friendly Vehicle unit without a Pilot on it.

// ── JTL_001 Asajj Ventress (leader action continuation) ─────────────────────
// $lastDecision = the chosen friendly unit. Deal 1 to it, then "if you do" deal 1 to an enemy unit in
// the SAME arena. The arena is read off the friendly mzID; the enemy half is a mandatory choose over
// that arena's enemy units (DEAL_UNIT_DAMAGE|1), followed by SWU_AFTER_ACTION to close the action.
$customDQHandlers["JTL_001#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) {
        SWUAfterAction(intval($player));
        return;
    }
    global $playerID;
    $playerID = intval($player);
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    $enemyZone = (strpos($lastDecision, 'Space') !== false) ? 'theirSpaceArena' : 'theirGroundArena';
    $enemies = ZoneSearch($enemyZone, AnyUnitFilter);
    if (empty($enemies)) { SWUAfterAction(intval($player)); return; } // no same-arena enemy → done
    SWUQueueChooseTarget(intval($player), $enemies,
        "Deal_1_damage_to_an_enemy_unit_in_the_same_arena", "DEAL_UNIT_DAMAGE|1");
    SWUQueueAfterAction($player);
};

// JTL_001 Asajj Ventress — pilot grant: "On Attack: You may deal 1 to a friendly unit. If you do,
// deal 1 to an enemy unit in the same arena." (Same effect as her front Action, but "you may" and
// combat-owned — so a dedicated combat-safe continuation, not the front's JTL_001#0.)
$onAttackAbilities["JTL_001:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $atk = GetZoneObject($mzID);
    if ($atk === null || ($atk->CardID ?? '') === 'JTL_001') return; // deployed-unit side has only Grit
    $friendly = SWUAllUnits('my');
    if (empty($friendly)) return;
    SWUQueueMayChooseTarget(intval($player), $friendly,
        "You_may_deal_1_to_a_friendly_unit", "Deal_1_to_a_friendly_unit", "JTL_001#1");
};

// Deal 1 to the chosen friendly, then (mandatory) 1 to an enemy in the SAME arena. No SWUAfterAction.
$customDQHandlers["JTL_001#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    $enemyZone = (strpos($lastDecision, 'Space') !== false) ? 'theirSpaceArena' : 'theirGroundArena';
    $enemies = ZoneSearch($enemyZone, AnyUnitFilter);
    if (empty($enemies)) return; // no same-arena enemy → done
    SWUQueueChooseTarget(intval($player), $enemies,
        "Deal_1_to_an_enemy_unit_in_the_same_arena", "DEAL_UNIT_DAMAGE|1");
};

// JTL_001 Asajj Ventress — Leader Action [Exhaust]: Deal 1 damage to a friendly unit. If you do, deal
// 1 damage to an enemy unit in the same arena. Mandatory friendly target (no decline); the enemy half
// is gated on an enemy unit existing in the SAME arena as the damaged friendly unit. Continuation in
// CardDQHandlers.php ("JTL_001") deals the friendly damage then offers the same-arena enemy.
$leaderAbilities["JTL_001"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $friendly = SWUAllUnits('my');
    if (empty($friendly)) { SWUAfterAction($player); return; } // no friendly to damage → fizzle
    SWUQueueChooseTarget($player, $friendly,
        "Deal_1_damage_to_a_friendly_unit", "JTL_001#0");
};
