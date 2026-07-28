<?php
// SOR_227  |  Reprints: SHD_236
// Cost 2 - Snowtrooper Lieutenant - [Villainy] - Power 2 - HP 2
// Text: When Played: You may attack with a unit. If it's an Imperial unit, it gets +2/+0 for this attack.

$whenPlayedAbilities["SOR_227:0"] = $swuAttackWithTraitWhenPlayed('Imperial');

// ─── SHD_236 Snowtrooper Lieutenant ───────────────────────────────────────────
// When Played: You may attack with a unit. If it's an Imperial unit, it gets +2/+0 for this attack.
$whenPlayedAbilities["SHD_236:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $targets[] = $mz;  // ready
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Attack_with_a_unit?", "Choose_a_ready_unit_to_attack_with", "SHD_236#0");
};

$customDQHandlers["SHD_236#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    if (HasTrait($o->CardID ?? '', 'Imperial')) SWUAddAttackPowerBonus($lastDecision, 2);
    BeginSWUAttack(intval($player), $lastDecision);
};
