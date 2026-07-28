<?php
// SHD_118
// Cost 4 - Kihraxz Heavy Fighter - [Command] - Power 3 - HP 3
// Text: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) / On Attack: You may exhaust another friendly unit. If you do, this unit gets +3/+0 for this attack.

// ─── SHD_118 Kihraxz Heavy Fighter ────────────────────────────────────────────
// Overwhelm (auto) + On Attack: You may exhaust another friendly unit. If you do, this unit gets +3/+0 for
// this attack.
$onAttackAbilities["SHD_118:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID
                && intval($o->Status ?? 0) === 1) $targets[] = $mz;   // ready → can be exhausted
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Exhaust_another_friendly_unit_for_+3/+0?", "Choose_a_friendly_unit_to_exhaust", "SHD_118#0|{$mzID}");
};

$customDQHandlers["SHD_118#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $selfMz = $parts[0] ?? '';
    OnExhaustCard(intval($player), $lastDecision);
    if ($selfMz !== '') SWUAddAttackPowerBonus($selfMz, 3);
};
