<?php
// TWI_202
// Cost 2 - Jar Jar Binks - Foolish Gungan - [Cunning,Cunning] - Power 2 - HP 3
// Text: On Attack: Deal 2 damage to a random unit or base.

// TWI_202 Jar Jar Binks — "On Attack: Deal 2 damage to a random unit or base." (Pool = all units + both
// bases; deterministic-collapse isn't possible since both bases are always in the pool — smoke-verified.)
$onAttackAbilities["TWI_202:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $pool = [];
    foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $pool[] = $mz;
        }
    }
    $pool[] = "myBase-0";
    $pool[] = "theirBase-0";
    $pick = $pool[array_rand($pool)];
    if (strpos($pick, 'Base') !== false) {
        SWUDealDamageToBase(2, ($pick === 'myBase-0') ? intval($player) : OtherPlayer(intval($player)), intval($player));
    } else {
        SWUDealDamageToUnit($pick, 2, intval($player));
    }
    AddGameLogEntry('ABILITY', 'TWI202_HIT', 'ALL'); // regression tag (random target isn't scriptable)
    // Combat owns the after-action.
};
