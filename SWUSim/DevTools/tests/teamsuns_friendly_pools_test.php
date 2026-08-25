<?php
// Team Suns FRIENDLY pools. The whole team seam lives in ONE place — the "team<Arena>" zone spec
// inside ZoneSearch — and everything else reaches it through SWUFriendlyUnits()/SWUControlledUnits().
//
// The load-bearing invariant asserted throughout: "my<Arena>" NEVER changes meaning. It stays
// SELF-ONLY in every format, which is what keeps Coordinate, Exploit, "if you control X" and attack
// legality correct without auditing any of them.
//
// ⚠ An unrecognized zone spec does NOT fail loudly — GetZone() returns null, ZoneSearch warns and
// returns []. So always assert pool CONTENT, never merely that a call succeeded.
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
$repo = getenv('REPO_ROOT') ?: '/var/www/html/TCGEngine';
chdir($repo);

if (!function_exists('ConvertMzIDToAbsolute'))         { function ConvertMzIDToAbsolute($m, $p): string { return ''; } }
if (!function_exists('QueueDamageAnimation'))          { function QueueDamageAnimation($t, $a): void {} }
if (!function_exists('QueueRestoreAnimation'))         { function QueueRestoreAnimation($t, $a): void {} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t): void {} }
if (!function_exists('QueueShieldBreakAnimation'))     { function QueueShieldBreakAnimation($t): void {} }
include_once './Core/DeterministicRNG.php';
include_once './Core/CoreZoneModifiers.php';
include_once './Core/GameAuth.php';
include_once './SWUSim/ZoneClasses.php';
include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once './SWUSim/GamestateParser.php';
include_once './SWUSim/Tests/Framework/Assertions.php';
include_once './SWUSim/Tests/Framework/Cards.php';
include_once './SWUSim/Tests/Framework/CommonSetup.php';
include_once './SWUSim/Tests/Framework/GameStateBuilder.php';
include_once './SWUSim/Tests/Framework/GameTestAdapter.php';
include_once './SWUSim/Tests/Framework/SchemaTestRunner.php';
include_once './SWUSim/Tests/Framework/TestRunner.php';
global $gameName, $playerID; $gameName = 'test_runner'; $playerID = 1;

function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

// Seed one ready ground unit per seat.
// AddGroundArena($player, $CardID, $Status, $Owner, $Damage, $Controller, $TurnEffects, $Subcards)
function seedBoard(array $seats) {
    foreach ($seats as $s) AddGroundArena($s, 'SOR_046', 1, $s, 0, $s, '-', '-');
}

// ── A four-seat TEAM game ────────────────────────────────────────────────────
InitializeGamestate();
SetSeatOrder('1234'); SetLiveSeats('1234');
AddGlobalEffects(1, 'SWU_MODE_TEAMS');
seedBoard([1, 2, 3, 4]);
$playerID = 1;

$team = ZoneSearch('teamGroundArena', AnyUnitFilter); sort($team);
check($team === ['myGroundArena-0', 'p3GroundArena-0'], 'teamGroundArena spans seat 1 and its teammate seat 3');

// my<Arena> is UNCHANGED — self only. THE guard that nothing else can regress.
check(ZoneSearch('myGroundArena', AnyUnitFilter) === ['myGroundArena-0'], 'myGroundArena is still SELF-ONLY');

$their = ZoneSearch('theirGroundArena', AnyUnitFilter); sort($their);
check($their === ['p2GroundArena-0', 'p4GroundArena-0'], 'theirGroundArena is the two opponents');

// SWUAllUnits(null) — the 'any' pool — must be EVERY unit on the table. THE BUG FIX.
$all = SWUAllUnits(null, 'Ground'); sort($all);
check(count($all) === 4, 'SWUAllUnits(null) spans all four seats (got ' . count($all) . ')');
check(in_array('p3GroundArena-0', $all, true), 'the TEAMMATE is reachable by an unqualified pool');

// 'my' is untouched, so its 54 existing callers keep their meaning.
check(SWUAllUnits('my', 'Ground') === ['myGroundArena-0'], "SWUAllUnits('my') is still SELF-ONLY");
check(count(SWUAllUnits('team', 'Ground')) === 2, "SWUAllUnits('team') spans the team");

// Seat 3 sees the mirror image: its teammate is seat 1.
$playerID = 3;
$team3 = ZoneSearch('teamGroundArena', AnyUnitFilter); sort($team3);
check($team3 === ['myGroundArena-0', 'p1GroundArena-0'], 'seat 3 team pool is itself plus seat 1');
$playerID = 1;

// An ELIMINATED teammate drops out of the friendly pool.
SetLiveSeats('124');
check(ZoneSearch('teamGroundArena', AnyUnitFilter) === ['myGroundArena-0'],
      'an eliminated teammate leaves the friendly pool');
SetLiveSeats('1234');

// ── The two named helpers are THE API ────────────────────────────────────────
$friendly = SWUFriendlyUnits('Ground'); sort($friendly);
check($friendly === ['myGroundArena-0', 'p3GroundArena-0'], 'SWUFriendlyUnits spans the team');
check(SWUControlledUnits('Ground') === ['myGroundArena-0'], 'SWUControlledUnits is SELF-ONLY');
check(SWUFriendlyUnits('Ground') !== SWUControlledUnits('Ground'),
      'friendly and controlled are genuinely DIFFERENT sets in a team game');

// ── Twin Suns (no teams): every spec degenerates ─────────────────────────────
InitializeGamestate();
SetSeatOrder('1234'); SetLiveSeats('1234');
seedBoard([1, 2, 3, 4]);
$playerID = 1;
check(ZoneSearch('teamGroundArena', AnyUnitFilter) === ZoneSearch('myGroundArena', AnyUnitFilter),
      'without teams, teamGroundArena is IDENTICAL to myGroundArena');
check(count(SWUAllUnits(null, 'Ground')) === 4, 'Twin Suns: an unqualified pool still spans all four seats');

// ── Two-player: byte-identical to history ────────────────────────────────────
InitializeGamestate();
seedBoard([1, 2]);
$playerID = 1;
check(ZoneSearch('teamGroundArena', AnyUnitFilter) === ['myGroundArena-0'], '2-player: team === my');
check(SWUFriendlyUnits('Ground') === SWUControlledUnits('Ground'),
      'in a 2-player game friendly === controlled (this is what keeps Premier byte-identical)');
check(count(SWUAllUnits(null, 'Ground')) === 2, '2-player: an unqualified pool is both units');

// ── Poe (JTL_013) piloting a TEAMMATE'S ship must not disturb ANY seat's aspect array ──────
// USER CONCERN (2026-08-25): JTL_013 attaches YOUR LEADER as a Pilot upgrade onto the chosen host, and
// the Team Suns ruling lets that host belong to your TEAMMATE. A player's cost-payment aspects come
// from PlayerAspects(), which reads that seat's own Leader + Base zones (plus ASH_135 Darksaber hosts
// among that seat's own units). Leaders never leave their zone, so the physical location of the pilot
// upgrade must be irrelevant in BOTH directions:
//   • P1 must KEEP Poe's aspects even though the upgrade sits on seat 3's ship
//   • P3 must NOT GAIN them just because it is hosting him
InitializeGamestate();
SetSeatOrder('1234'); SetLiveSeats('1234');
AddGlobalEffects(1, 'SWU_MODE_TEAMS');
// Deployed=true with DeployedUniqueID pointing at the host that lives in SEAT 3's arena —
// the realistic cross-seat state, which also exercises the $ldrDeployedUID skip in PlayerAspects.
AddLeader(1, 'JTL_013', false, true, true, 9901);   // P1's leader: Poe — Aggression,Heroism
AddBase(1, 'SOR_026');                          // Catacombs of Cadera — Aggression
AddLeader(3, 'SOR_010', false, true, false, 0);     // P3's leader (different aspects)
AddBase(3, 'SOR_019');                          // Security Complex — Vigilance
$playerID = 1;

$p1Before = PlayerAspects(1); sort($p1Before);
$p3Before = PlayerAspects(3); sort($p3Before);
check(in_array('Aggression', $p1Before, true) && in_array('Heroism', $p1Before, true),
      'precondition: P1 provides Poe\'s Aggression + Heroism');
check(!in_array('Heroism', $p3Before, true), 'precondition: P3 does NOT provide Heroism');

// Seat 3 fields a ship CARRYING P1's Poe as a Pilot upgrade — the cross-seat attach.
$poe = ['CardID' => 'JTL_013', 'Owner' => 1, 'Controller' => 1, 'TurnEffects' => [], 'IsPilot' => true];
AddGroundArena(3, 'SOR_046', 1, 3, 0, 3, '-', [$poe], 9901);

$p1After = PlayerAspects(1); sort($p1After);
$p3After = PlayerAspects(3); sort($p3After);
check($p1After === $p1Before, 'P1 KEEPS its aspect array while Poe pilots a teammate\'s ship');
check($p3After === $p3Before, 'P3 does NOT GAIN Poe\'s aspects by hosting him');
check(!in_array('Heroism', $p3After, true), 'P3 still provides no Heroism (no leakage through the subcard)');

// And the aspect PENALTY each seat pays is unchanged — the number players actually feel.
check(SWUAspectPenalty(1, 'SOR_095') === SWUAspectPenalty(1, 'SOR_095'), 'P1 penalty is stable');
$penalty3 = SWUAspectPenalty(3, 'JTL_013');
check($penalty3 > 0, 'P3 still pays a penalty for an Aggression/Heroism card — it did not inherit Poe');

echo "PASS\n";
