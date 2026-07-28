<?php
// TS26_02
// Cost 5 - Anakin Skywalker - Protect Her At All Costs - [Vigilance,Heroism] - Power 4 - HP 5
// Text: Action [Exhaust]: If 2 or more friendly units entered play this phase (including tokens and leaders), give a Shield token to 1 of them.
// DeployText: Sentinel (Enemy units in this arena must attack a Sentinel when they attack you.) / On Attack: Give a Shield token to another friendly unit that entered play this phase.
// Epic Action: If you control 5 or more resources, deploy this leader.

// TS26_02 Anakin Skywalker (deployed) — Sentinel (auto). On Attack: give a Shield token to another
// friendly unit that entered play this phase.
$onAttackAbilities["TS26_02:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $tg = _SWUEnteredThisPhaseUnits(intval($player), $selfUID);
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Give_a_Shield_to_a_unit_that_entered_this_phase?", "Choose_a_unit", "GIVE_SHIELD");
};

// TS26_02 Anakin Skywalker (front) — Action [Exhaust]: if 2+ friendly units entered play this phase,
// give a Shield token to 1 of them. (Deployed: Sentinel auto + OnAttack shield another entered unit.)
$leaderAbilities["TS26_02"] = function(int $player): void {
    global $playerID; $playerID = intval($player);
    $entered = _SWUEnteredThisPhaseUnits(intval($player));
    if (count($entered) < 2) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $entered, "Give_a_Shield_to_a_unit_that_entered_this_phase", "GIVE_SHIELD");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SWU_AFTER_ACTION", 1);
};
