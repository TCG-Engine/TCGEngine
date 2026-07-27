<?php
// LAW_016
// Cost 5 - The Client - Please Lower Your Blaster - [Cunning,Villainy] - Power 4 - HP 4
// Text: Action [Exhaust]: If you created a token this phase, exhaust an enemy unit.
// DeployText: Shielded (When you deploy this leader, give him a Shield token.) / On Attack: If you created a token this phase, exhaust an enemy unit.
// Epic Action: If you control 5 or more resources, deploy this leader.

$leaderAbilities["LAW_016"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (GlobalEffectCount($player, 'SWU_CREATED_TOKEN') <= 0) { SWUAfterAction($player); return; }
    $enemies = TheClientPleaseLowerYourBlasterEnemies($player);
    if (empty($enemies)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $enemies, "Exhaust_an_enemy_unit", "EXHAUST_UNIT");
    SWUQueueAfterAction($player);
};

$onAttackAbilities["LAW_016:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_CREATED_TOKEN') <= 0) return;
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'EXHAUST_UNIT', 'side' => 'their',
        'prompt' => "Exhaust_an_enemy_unit",
    ]);
};

// ── LAW_016 The Client ────────────────────────────────────────────────────────
// Front Action [Exhaust]: if you created a token this phase, exhaust an enemy unit.
// Deployed: Shielded (auto) + On Attack: if you created a token this phase, exhaust an enemy unit.
function TheClientPleaseLowerYourBlasterEnemies(int $player): array {
    global $playerID; $playerID = $player;
    $enemies = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z)
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $enemies[] = $mz; }
    return $enemies;
}
