<?php
// Test SWUAllUnits() zone selection + ordering, with a stubbed ZoneSearch so it
// runs without a live game. ZoneSearch returns one sentinel mzID per zone, so the
// returned list is exactly the zone-visit order.
if (!defined('AnyUnitFilter')) define('AnyUnitFilter', 'ANY');
if (!defined('GroundArena')) define('GroundArena', 'Ground');
if (!defined('SpaceArena')) define('SpaceArena', 'Space');
function ZoneSearch($zone, $filter) { return [$zone]; }  // stub: one id == zone name

// Constants can also be passed for $arena.


require __DIR__ . '/../../Custom/CardHelpers.php';

// ⚠ UPDATED 2026-08-27. The unqualified pool is the TEAM pool, not the "my" pool — changed deliberately
// by the Team Suns work: once 'their' excludes a teammate, 'my' + 'their' no longer covers the table, so
// an unqualified pool must start from 'team'. ZoneSearch understands the "team<Zone>" prefix and degrades
// it to the caller's own zone outside a team game, so this stays byte-identical to the old behaviour at
// two seats. A qualified 'my' is still 'my' — that is the next assertion, and it is the control.
assert(SWUAllUnits() === ['teamGroundArena','teamSpaceArena','theirGroundArena','theirSpaceArena'], 'all: '.json_encode(SWUAllUnits()));
assert(SWUAllUnits('my') === ['myGroundArena','mySpaceArena'], 'my');
assert(SWUAllUnits('their') === ['theirGroundArena','theirSpaceArena'], 'their');
assert(SWUAllUnits(null, 'Ground') === ['teamGroundArena','theirGroundArena'], 'ground');
assert(SWUAllUnits(null, 'Space') === ['teamSpaceArena','theirSpaceArena'], 'space');
assert(SWUAllUnits('my', 'Ground') === ['myGroundArena'], 'single zone');
// Passing the Constants.php arena constants behaves identically to the strings.
assert(SWUAllUnits(null, GroundArena) === ['teamGroundArena','theirGroundArena'], 'ground const');
assert(SWUAllUnits(null, SpaceArena) === ['teamSpaceArena','theirSpaceArena'], 'space const');

echo "OK\n";
