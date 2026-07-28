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

assert(SWUAllUnits() === ['myGroundArena','mySpaceArena','theirGroundArena','theirSpaceArena'], 'all: '.json_encode(SWUAllUnits()));
assert(SWUAllUnits('my') === ['myGroundArena','mySpaceArena'], 'my');
assert(SWUAllUnits('their') === ['theirGroundArena','theirSpaceArena'], 'their');
assert(SWUAllUnits(null, 'Ground') === ['myGroundArena','theirGroundArena'], 'ground');
assert(SWUAllUnits(null, 'Space') === ['mySpaceArena','theirSpaceArena'], 'space');
assert(SWUAllUnits('my', 'Ground') === ['myGroundArena'], 'single zone');
// Passing the Constants.php arena constants behaves identically to the strings.
assert(SWUAllUnits(null, GroundArena) === ['myGroundArena','theirGroundArena'], 'ground const');
assert(SWUAllUnits(null, SpaceArena) === ['mySpaceArena','theirSpaceArena'], 'space const');

echo "OK\n";
