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
    // ⚠ UNQUALIFIED pool = the WHOLE table, so the own side is 'team*', not 'my*': `their*` is the
    // OPPONENT fan-out and excludes a teammate, so my*+their* leaves a teammate's units in NEITHER
    // list. 'team*' degrades to 'my*' outside a team game (Premier byte-identical).
    foreach (["teamGroundArena", "teamSpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
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
    // ⚠ EngineRandomInt(), never array_rand(). array_rand() draws from PHP's unseeded Mt19937 — it does
    // not advance $gRandomCounter and is not restored by an undo snapshot, so undo→redo across this
    // attack hit a DIFFERENT target. EngineRandomInt() is seeded from gamestate + the per-game secret
    // RNG_SEED: still unpredictable to players, but reproducible on undo/replay.
    $pick = $pool[EngineRandomInt(0, count($pool) - 1)];
    if (strpos($pick, 'Base') !== false) {
        // The random pick already names the seat — read it out of the mzID rather than assuming seat 2.
        SWUDealDamageToBase(2, SWUMzOwner($pick, intval($player)), intval($player));
    } else {
        SWUDealDamageToUnit($pick, 2, intval($player));
    }
    // The damage funnel logs the random hit ("P1's Jar Jar Binks dealt 2 damage to …"); this used to be a
    // raw 'TWI202_HIT' test tag printed in the live game log (game-log sweep, 2026-09-11).
    // Combat owns the after-action.
};
