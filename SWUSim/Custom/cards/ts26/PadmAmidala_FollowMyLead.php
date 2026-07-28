<?php
// TS26_04
// Cost 6 - Padmé Amidala - Follow My Lead - [Command,Heroism] - Power 5 - HP 6
// Text: Action [Exhaust]: If 2 or more friendly units entered play this phase (including tokens and leaders) , attack with 1 of them, even if it's exhausted. It can't attack bases for this attack.
// DeployText: When Attack Ends: You may attack with another friendly unit that entered play this phase, even if it's exhausted. It can't attack bases for this attack.
// Epic Action: If you control 6 or more resources, deploy this leader.

// TS26_04 Padmé Amidala (deployed) — When Attack Ends: you may attack with another friendly unit that
// entered play this phase (even if exhausted); it can't attack bases (CHAINED_ATTACK noBases flag).
$onAttackEndAbilities["TS26_04:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $tg = _SWUEnteredThisPhaseUnits(intval($player), $selfUID);
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Attack_with_another_unit_that_entered_this_phase_(no_bases)?", "Choose_a_unit", "CHAINED_ATTACK|0|1");
};

// TS26_04 Padmé Amidala (front) — Action [Exhaust]: if 2+ friendly units entered play this phase, attack
// with 1 of them (even if exhausted); it can't attack bases. (Deployed: When Attack Ends, may attack with
// another entered unit.)
$leaderAbilities["TS26_04"] = function(int $player): void {
    global $playerID; $playerID = intval($player);
    $entered = _SWUEnteredThisPhaseUnits(intval($player));
    if (count($entered) < 2) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $entered, "Attack_with_a_unit_that_entered_this_phase_(no_bases)", "TS26_04#0");
};

$customDQHandlers["TS26_04#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction(intval($player)); return; }
    BeginSWUAttack(intval($player), $lastDecision, true);   // noBases; combat owns the after-action
};
