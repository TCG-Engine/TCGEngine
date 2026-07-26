<?php
// JTL_004
// Cost 5 - Rose Tico - Saving What We Love - [Vigilance,Heroism] - Power 4 - HP 6
// Text: Action [Exhaust]: Heal 2 damage from a Vehicle unit that attacked this phase.
// DeployText: On Attack: You may heal 2 damage from a Vehicle unit.
// Epic Action: If you control 5 or more resources, deploy this leader.

// ── JTL_004 Rose Tico (deployed leader unit) — On Attack: You may heal 2 damage from a Vehicle unit ──
// Any Vehicle (no "attacked this phase" restriction on the deployed side). On-Attack triggers don't
// close the action (combat owns it), so the may-decline handler simply no-ops.
$onAttackAbilities["JTL_004:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (!HasTrait($o->CardID, 'Vehicle')) continue;
        $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_heal_2_from_a_Vehicle_unit", "Heal_2_from_a_Vehicle_unit", "HEAL_TARGET|2");
};

// JTL_004 Rose Tico — Leader Action [Exhaust]: Heal 2 damage from a Vehicle unit that attacked this
// phase. "Attacked this phase" = the unit's controller carries its SWU_ATTACKED_{uid} flag (set in
// CombatLogic on every attack). Any Vehicle (friendly or enemy) qualifies. HEAL_TARGET closes nothing,
// so SWU_AFTER_ACTION is queued last.
$leaderAbilities["JTL_004"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (!HasTrait($o->CardID, 'Vehicle')) continue;
        $ctrl = intval($o->Controller ?? 0);
        if (GlobalEffectCount($ctrl, 'SWU_ATTACKED_' . intval($o->UniqueID ?? 0)) <= 0) continue;
        $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; } // no Vehicle attacked → fizzle
    SWUQueueChooseTarget($player, $targets,
        "Heal_2_from_a_Vehicle_that_attacked_this_phase", "HEAL_TARGET|2");
    SWUQueueAfterAction($player);
};
