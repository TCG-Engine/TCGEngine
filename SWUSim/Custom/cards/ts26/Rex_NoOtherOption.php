<?php
// TS26_06
// Cost 6 - Rex - No Other Option - [Aggression,Heroism] - Power 5 - HP 6
// Text: Action [Exhaust, ready an exhausted enemy unit]: The next event you play this phase costs 1 resource less.
// DeployText: On Attack: You may ready an exhausted enemy unit. If you do, the next event you play this phase costs 2 resources less.
// Epic Action: If you control 6 or more resources, deploy this leader.

// TS26_06 Rex (deployed) — On Attack: you may ready an exhausted enemy unit; if you do, the next event
// you play this phase costs 2 less. Shared TS26_06#0 handler (arm count + close flag in parts).
$onAttackAbilities["TS26_06:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $enemy = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status ?? 1) === 0) $enemy[] = $mz;
        }
    }
    if (empty($enemy)) return;
    SWUQueueMayChooseTarget(intval($player), $enemy, "Ready_an_exhausted_enemy_unit_(next_event_-2)?", "Choose_an_enemy_unit", "TS26_06#0|2|0");
};

$customDQHandlers["TS26_06#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $arm   = intval($parts[0] ?? 1);
    $close = intval($parts[1] ?? 0);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS' && str_contains($lastDecision, '-')) {
        OnReadyCard(intval($player), $lastDecision);
        for ($i = 0; $i < $arm; $i++) AddGlobalEffects(intval($player), 'SWU_REX_DISCOUNT_NEXT');
    }
    if ($close === 1) SWUAfterAction(intval($player));
};

// TS26_06 Rex (front) — Action [Exhaust, ready an exhausted enemy unit]: the next event you play this
// phase costs 1 resource less. (Deployed: On Attack, may ready an exhausted enemy → next event -2.)
$leaderAbilities["TS26_06"] = function(int $player): void {
    global $playerID; $playerID = intval($player);
    $enemy = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status ?? 1) === 0) $enemy[] = $mz;  // exhausted
        }
    }
    if (empty($enemy)) { SWUAfterAction(intval($player)); return; }   // cost can't be paid (guarded in affordability)
    SWUQueueChooseTarget(intval($player), $enemy, "Ready_an_exhausted_enemy_unit_(next_event_-1)", "TS26_06#0|1|1");
};
