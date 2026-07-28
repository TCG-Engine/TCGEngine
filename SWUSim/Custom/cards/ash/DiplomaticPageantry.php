<?php
// ASH_231
// Cost 1 - Diplomatic Pageantry - [Cunning]
// Text: Exhaust a friendly unit and an enemy unit. If you do, give 2 Advantage tokens to that friendly unit.

// ASH_231 Diplomatic Pageantry (event) — Exhaust a friendly unit and an enemy unit. If you do, give 2
// Advantage tokens to that friendly unit. (Fizzles unless both a friendly AND an enemy unit exist.)
$customDQHandlers["ASH_231#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;   // friendly choice
    $friendly = GetZoneObject($lastDecision);
    if (SWUObjGone($friendly)) return;
    $fuid = intval($friendly->UniqueID ?? 0);
    OnExhaustCard(intval($player), $lastDecision);
    $enemy = [];
    foreach (SWUAllUnits('their') as $emz) {
        $eo = GetZoneObject($emz); if ($eo !== null && empty($eo->removed) && intval($eo->Status ?? 1) === 1) $enemy[] = $emz;
    }
    if (empty($enemy)) return;   // no READY enemy unit → "exhaust an enemy unit" can't be done → no Advantage
    SWUQueueChooseTarget(intval($player), $enemy, "Exhaust_an_enemy_unit", "ASH_231#1|{$fuid}");
};

$customDQHandlers["ASH_231#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;   // enemy choice
    $enemy = GetZoneObject($lastDecision);
    if (SWUObjGone($enemy)) return;
    OnExhaustCard(intval($player), $lastDecision);
    $fmz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($fmz !== null) { DoGiveAdvantageToken(intval($player), $fmz); DoGiveAdvantageToken(intval($player), $fmz); }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["ASH_231:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $friendly = [];
    foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz); if ($o !== null && empty($o->removed) && intval($o->Status ?? 1) === 1) $friendly[] = $mz;
    }
    $enemy = [];
    foreach (array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz); if ($o !== null && empty($o->removed) && intval($o->Status ?? 1) === 1) $enemy[] = $mz;
    }
    if (empty($friendly) || empty($enemy)) return;   // can't exhaust both → fizzle
    SWUQueueChooseTarget(intval($player), $friendly, "Exhaust_a_friendly_unit", "ASH_231#0");
};
