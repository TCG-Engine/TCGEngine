<?php
// LAW_010
// Cost 5 - Leia Organa - Someone Who Loves You - [Command,Heroism] - Power 2 - HP 2
// Text: Action [2 resources, Exhaust]: For this phase, give a unit +1/+1 for each different aspect it has.
// DeployText: Overwhelm / When Deployed: Choose a unit. Give an Experience token to that unit for each different aspect among units you control.
// Epic Action: If you control 5 or more resources, deploy this leader.

// ── LAW_010 Leia Organa ───────────────────────────────────────────────────────
// Front Action [2 resources, Exhaust]: give a unit +1/+1 for this phase for each different aspect IT
// has. Deployed When Deployed: give a chosen unit an Experience token for each different aspect AMONG
// units you control.
$leaderActionResourceCosts["LAW_010"] = 2;

$leaderAbilities["LAW_010"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, SWUApplyCostHalving($player, 2))) { SWUAfterAction($player); return; }
    $units = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z)
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $units[] = $mz; }
    if (empty($units)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $units, "Give_a_unit_+1/+1_per_different_aspect_it_has", "LAW_010#0");
    SWUQueueAfterAction($player);
};

$customDQHandlers["LAW_010#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $asp = [];
    foreach (explode(',', (string)(CardAspect($o->CardID ?? '') ?? '')) as $a) { $a = trim($a); if ($a !== '') $asp[$a] = true; }
    $n = count($asp);
    if ($n > 0) SWUApplyPhaseBuff($lastDecision, $n, $n, 'LAW_010');
};

$whenPlayedAbilities["LAW_010:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $units = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z)
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $units[] = $mz; }
    if (empty($units)) return;
    SWUQueueChooseTarget(intval($player), $units, "Give_Experience_tokens_(=_distinct_aspects_you_control)_to_a_unit", "LAW_010#1");
};

$customDQHandlers["LAW_010#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $asp = [];
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (!empty($u->removed)) continue;
        foreach (explode(',', (string)(CardAspect($u->CardID ?? '') ?? '')) as $a) { $a = trim($a); if ($a !== '') $asp[$a] = true; }
    }
    $n = count($asp);
    for ($i = 0; $i < $n; $i++) DoGiveExperienceToken(intval($player), $lastDecision);
};
