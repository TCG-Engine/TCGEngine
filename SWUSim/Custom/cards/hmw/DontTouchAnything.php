<?php
// HMW_217
// Cost 2 - Don't Touch Anything - [Cunning,Heroism] - Event - Traits: Trick - NON-unique
// Text: Deal 3 damage to a random enemy unit.
//
// THE POOL IS "ENEMY" AND "UNIT", AND BOTH WORDS DO WORK. SWUAllUnits('their') is the whole
// implementation of the first: it is the ONE helper that answers "enemy" correctly in all three live
// formats — ZoneSearch expands `their<Zone>` across EVERY live opponent above two seats (returning
// seat-addressed p{n}<Zone>-{i} mzIDs), and in a Team Suns game 'their' already excludes a TEAMMATE.
// Hand-rolling the pool is how the engine's other random-target card got this wrong: TWI_202 Jar Jar
// Binks built a literal 'theirBase-0', which named seat 2 and nothing else, so above two seats a far
// seat was both unreachable and missing from the odds.
//
// "UNIT" is the second word, and it is the sharpest difference from Jar Jar — whose text reads "a
// random unit OR BASE" and whose pool therefore contains both bases. This card's does not. Its text
// also says neither "non-leader" nor an arena, so AnyUnitFilter across both arenas is exactly right:
// a deployed enemy leader unit and an enemy space unit are legal targets.
//
// The pick is genuinely random (no decision is raised), so it follows TWI_202 in using array_rand over
// the engine's seeded RNG state rather than a decision. The tests make the OUTCOME deterministic
// without constraining the choice — either exactly one legal target, or two identical bodies where
// whichever is picked dies.
//
// Damage goes through the ordinary SWUDealDamageToUnit funnel rather than writing Damage directly, so
// Shields, prevention and the state-based defeat sweep all apply for free.
$whenPlayedAbilities["HMW_217:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $targets = SWUAllUnits('their');
    if (empty($targets)) return;   // no enemy units: clean fizzle, no prompt, no base as a consolation
    $pick = $targets[array_rand($targets)];
    // The target is random, so the log line is the only way a player can see what was hit.
    AddGameLogEntry('ABILITY', 'HMW217_HIT ' . CardTitle(GetZoneObject($pick)->CardID ?? ''), 'ALL');
    SWUDealDamageToUnit($pick, 3, intval($player));
};
