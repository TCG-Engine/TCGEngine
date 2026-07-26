<?php
// TWI_006
// Cost 5 - Wat Tambor - Techno Union Foreman - [Command,Villainy] - Power 3 - HP 6
// Text: Action [Exhaust]: If a friendly unit was defeated this phase, give a unit +2/+2 for this phase.
// DeployText: On Attack: If a friendly unit was defeated this phase, you may give another unit +2/+2 for this phase.
// Epic Action: If you control 5 or more resources, deploy this leader.

// ── TWI Leaders — deployed sides ──────────────────────────────────────────────
// TWI_006 Wat Tambor (deployed) — "On Attack: If a friendly unit was defeated this phase, you may give
// another unit +2/+2 for this phase."
$onAttackAbilities["TWI_006:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_FRIENDLY_DEFEATED') <= 0) return;
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $targets = [];
    foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_another_unit_+2/+2_this_phase?", "Choose_another_unit", "APPLY_PHASE_BUFF|2|2|TWI_006");
    // Combat owns the after-action.
};

// TWI_006 Wat Tambor (front) — "Action [Exhaust]: If a friendly unit was defeated this phase, give a unit
// +2/+2 for this phase." (Affordability gates the defeat condition.)
$leaderAbilities["TWI_006"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = SWUAllUnits();
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Give_a_unit_+2/+2_this_phase", "APPLY_PHASE_BUFF|2|2|TWI_006");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};
