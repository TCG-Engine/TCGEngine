<?php
// SOR_005
// Cost 6 - Luke Skywalker - Faithful Friend - [Vigilance,Heroism] - Power 4 - HP 7
// Text: Action [1 resource, exhaust]: Give a Shield token to a [Heroism] unit you played this phase.
// DeployText: On Attack: You may give another unit a Shield token.
// Epic Action: If you control 6 or more resources, deploy this leader. (Flip him, ready him, and move him to the ground arena.)

// ── SOR_005 Luke Skywalker — leader ability DQ handler ──────────────────────
$customDQHandlers["SOR_005#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-') {
        SWUAfterAction(intval($player));
        return;
    }
    GiveShieldToken(intval($player), $lastDecision);
    SWUAfterAction(intval($player));
};

// ── SOR_005 Luke Skywalker — Leader Unit On Attack ──────────────────────────
// On Attack: You may give a shield token to another unit. Single MZMAYCHOOSE via the
// helper; GIVE_SHIELD no-ops on a '-' decline. "Another" excludes the attacker's own mzID.
$onAttackAbilities["SOR_005:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_values(array_filter(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter),
        ZoneSearch('mySpaceArena',     AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter),
        ZoneSearch('theirSpaceArena',  AnyUnitFilter)
    ), fn($mz) => $mz !== $mzID));
    SWUQueueMayChooseTarget(intval($player), $targets,
        'Give_a_shield_to_another_unit?', 'Choose_a_unit_to_give_a_shield_to', 'GIVE_SHIELD', 0);
};

// SOR_005 Luke Skywalker — Leader Action [1 resource, Exhaust]:
// Give a shield token to a unit you played this phase with the Heroism aspect.
$leaderAbilities["SOR_005"] = function(int $player): void {
    global $playerID;
    $playerID = $player;


    // Collect units played this phase that have the Heroism aspect.
    $zone = &GetGlobalEffects($player);
    $prefix = 'SWU_PLAYED_UNIT_';
    $heroismTargets = [];
    foreach ($zone as $ge) {
        if (!str_starts_with($ge->CardID, $prefix)) continue;
        $uid = intval(substr($ge->CardID, strlen($prefix)));
        // Find the unit in arenas by UniqueID.
        foreach (['myGroundArena', 'mySpaceArena'] as $z) {
            foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                $obj = GetZoneObject($mz);
                if ($obj === null || ($obj->removed ?? false)) continue;
                if (intval($obj->UniqueID) !== $uid) continue;
                if (strpos(CardAspect($obj->CardID) ?? '', 'Heroism') !== false) {
                    $heroismTargets[] = $mz;
                }
            }
        }
    }
    $heroismTargets = array_values(array_unique($heroismTargets));

    if (empty($heroismTargets)) {
        SWUAfterAction($player);
        return;
    }
    if (count($heroismTargets) === 1) {
        DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $heroismTargets[0], 1);
    } else {
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $heroismTargets), 1,
            'Choose_a_Heroism_unit_played_this_phase_to_give_a_shield_to');
    }
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_005#0', 1);
};
