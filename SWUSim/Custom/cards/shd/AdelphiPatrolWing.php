<?php
// SHD_101
// Cost 5 - Adelphi Patrol Wing - [Command,Heroism] - Power 4 - HP 6
// Text: When Played: You may attack with a unit. If you have the initiative, it gets +2/+0 for this attack.

// ─── SHD_101 Adelphi Patrol Wing ──────────────────────────────────────────────
// When Played: You may attack with a unit. If you have the initiative, it gets +2/+0 for this attack.
$whenPlayedAbilities["SHD_101:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $targets[] = $mz;
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets, "Attack_with_a_unit?", "Choose_a_ready_unit_to_attack_with", "SHD_101#0");
};

$customDQHandlers["SHD_101#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    // ⚠ "If YOU have the initiative" — ask the engine, do not decode the counter string. This used to be
    // `strpos($ic,'P1') === 0 ? 1 : 2`, which collapses EVERY non-P1 holder onto seat 2: at 3+ seats a
    // seat-3 or seat-4 initiative holder got no bonus, and seat 2 got one it had not earned.
    if (PlayerHasIniative(intval($player))) SWUAddAttackPowerBonus($lastDecision, 2);
    BeginSWUAttack(intval($player), $lastDecision);
};
