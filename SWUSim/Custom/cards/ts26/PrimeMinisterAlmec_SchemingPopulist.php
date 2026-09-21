<?php
// TS26_28
// Cost 4 - Prime Minister Almec - Scheming Populist - [Command,Cunning] - Power 2 - HP 4
// Text: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / When Played: Give a friendly unit +2/+2 for this phase. Exhaust each enemy unit in its arena with less power than it.

// TS26_28 Prime Minister Almec — Saboteur. When Played: give a friendly unit +2/+2 for this phase, then
// exhaust each enemy unit in its arena with less power than it (measured after the buff).
$whenPlayedAbilities["TS26_28:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // ⚠ FRIENDLY spans the TEAM (user ruling 2026-08-25, IBH_095): in Team Suns a teammate's
    // unit is friendly, so the pool is SWUFriendlyUnits() ('team'), not 'my'. ⚠ NOT the same as "a unit you control",
    // which stays 'my' — control is per-player. 'team' degrades to 'my' outside a team game, so
    // Premier is byte-identical. See memory: unqualified pools miss teammates.
    $tg = SWUFriendlyUnits();
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Give_a_friendly_unit_+2/+2_this_phase", "TS26_28#0");
};

$customDQHandlers["TS26_28#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    SWUApplyPhaseBuff($lastDecision, 2, 2, 'TS26_28');
    $buffed = GetZoneObject($lastDecision);
    if (SWUObjGone($buffed)) return;
    $power = intval(ObjectCurrentPower($buffed));
    $arena = strpos($lastDecision, 'SpaceArena') !== false ? 'theirSpaceArena' : 'theirGroundArena';
    foreach (ZoneSearch($arena, AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval(ObjectCurrentPower($o)) < $power) OnExhaustCard(intval($player), $mz);
    }
};
