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
    // ⚠ 'theirBase-0' is a HAND-BUILT relative mzID: it names SEAT 2 and nothing else, so above two seats
    // a far seat's base could not be targeted at all. SWUAllBaseMzIDs(…, 'any') is the caster's own base
    // plus EVERY opponent's, as real p{n}Base mzIDs. (This shape is invisible to a seat-helper scan —
    // there is no OtherPlayer() here, just a string.)
    // For Jar Jar this also skews the RANDOM pick: with only two bases in the pool a four-seat table
    // was drawing from 2 bases instead of 4, so every base's odds were wrong as well as unreachable.
    foreach (SWUAllBaseMzIDs(intval($player), 'any') as $bmz) $pool[] = $bmz;
    $pick = $pool[array_rand($pool)];
    if (strpos($pick, 'Base') !== false) {
        // The random pick already names the seat — read it out of the mzID rather than assuming seat 2.
        SWUDealDamageToBase(2, SWUMzOwner($pick, intval($player)), intval($player));
    } else {
        SWUDealDamageToUnit($pick, 2, intval($player));
    }
    AddGameLogEntry('ABILITY', 'TWI202_HIT', 'ALL'); // regression tag (random target isn't scriptable)
    // Combat owns the after-action.
};
