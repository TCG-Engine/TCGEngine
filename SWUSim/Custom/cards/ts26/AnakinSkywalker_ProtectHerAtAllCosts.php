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
    // The GATE and the TARGET POOL answer different questions. The gate asks how many friendly units
    // ENTERED play this phase — a fact about the past that a later defeat or control change cannot undo —
    // so it counts entry flags. The pool is who can actually receive the Shield NOW, so it is the live,
    // currently-friendly set. Using the live set for both meant a 2-unit turn where one entrant died (or
    // was stolen) failed the gate and the survivor never got its Shield.
    // RULING (2026-08-09): the Action costs nothing but [Exhaust], so it is always usable as a soft pass —
    // exhausting the leader is itself a gamestate change. Falling through here still exhausts and simply
    // does nothing; it must NOT be blocked in SWULeaderActionAffordable.
    $entered = _SWUEnteredThisPhaseUnits(intval($player));
    if (_SWUEnteredThisPhaseCount(intval($player)) < 2 || empty($entered)) {
        SWUAfterAction(intval($player)); return;
    }
    SWUQueueChooseTarget(intval($player), $entered, "Give_a_Shield_to_a_unit_that_entered_this_phase", "GIVE_SHIELD");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SWU_AFTER_ACTION", 1);
};
