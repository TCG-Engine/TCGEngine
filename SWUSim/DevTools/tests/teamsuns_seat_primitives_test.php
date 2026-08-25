<?php
// Team Suns seat primitives. Team membership is SEAT PARITY (red 1,3 / blue 2,4), which needs no new
// game state — the lobby's forced R/B/R/B seating is what makes parity correct, and SeatOrder is left
// intact by SWUEliminateSeat so parity survives an elimination.
//
// Boots the real engine (same include chain as run-schema-tests.php) so these are true unit tests of
// the helpers rather than assertions about a fixture.
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

// ── A four-seat TEAM game ────────────────────────────────────────────────────
InitializeGamestate();
SetSeatOrder('1234'); SetLiveSeats('1234');
AddGlobalEffects(1, 'SWU_MODE_TEAMS');

check(SWUIsTeamGame() === true, 'SWU_MODE_TEAMS makes SWUIsTeamGame() true');
check(SWUGameMode() === '',     'SWU_MODE_TEAMS does not masquerade as a goldfish/hotseat mode');

check(SWUTeamOf(1) === SWUTeamOf(3), 'seats 1 and 3 are the same team (Red)');
check(SWUTeamOf(2) === SWUTeamOf(4), 'seats 2 and 4 are the same team (Blue)');
check(SWUTeamOf(1) !== SWUTeamOf(2), 'Red and Blue are different teams');

check(SWUTeammatesOf(1) === [3], 'seat 1 teammate is seat 3');
check(SWUTeammatesOf(3) === [1], 'seat 3 teammate is seat 1');
check(SWUTeammatesOf(2) === [4], 'seat 2 teammate is seat 4');
check(SWUTeammatesOf(4) === [2], 'seat 4 teammate is seat 2');

// ── OpponentsOf excludes teammates (the cascade point) ───────────────────────
check(OpponentsOf(1) === [2, 4], 'seat 1 opponents are the BLUE seats only (3 is a teammate)');
check(OpponentsOf(3) === [2, 4], 'seat 3 opponents are the BLUE seats only');
check(OpponentsOf(2) === [1, 3], 'seat 2 opponents are the RED seats only');
check(OpponentsOf(4) === [1, 3], 'seat 4 opponents are the RED seats only');
check(count(OpponentsOf(1)) === 2, 'a team game has exactly 2 opponents, not 3');

// "Each player" is NOT narrowed — a teammate is still a player.
check(SWUSeatsInPlayerOrder(1) === [1, 2, 3, 4], 'SWUSeatsInPlayerOrder still spans the whole table');

// ── Parity survives elimination ──────────────────────────────────────────────
// SWUEliminateSeat trims LiveSeats but deliberately leaves SeatOrder intact, so parity keeps working.
SetLiveSeats('134');
check(SWUTeamOf(1) === SWUTeamOf(3), 'parity is unchanged after a seat is eliminated');
check(SWUTeammatesOf(1) === [3],     'a live teammate is still reported after an elimination');
check(SWUTeammatesOf(2) === [4],     'querying FROM an eliminated seat still lists its LIVE teammates');
check(SWUTeammatesOf(4) === [],      'an ELIMINATED seat is not reported as a teammate (4 loses 2)');
check(OpponentsOf(1) === [4],        'an eliminated opponent drops out of OpponentsOf');
check(OpponentsOf(4) === [1, 3],     'the surviving lone Blue faces both Red seats');

// ── A non-team game degenerates: everyone is their own team ──────────────────
InitializeGamestate();
SetSeatOrder('1234'); SetLiveSeats('1234');   // 4-player TWIN SUNS — no SWU_MODE_TEAMS
check(SWUIsTeamGame() === false, 'a Twin Suns game is not a team game');
check(SWUTeamOf(1) !== SWUTeamOf(3), 'without teams, seats 1 and 3 are DIFFERENT teams');
check(SWUTeamOf(2) !== SWUTeamOf(4), 'without teams, seats 2 and 4 are DIFFERENT teams');
check(SWUTeammatesOf(1) === [], 'without teams, nobody has a teammate');
check(SWUTeammatesOf(3) === [], 'without teams, seat 3 has no teammate either');
check(OpponentsOf(1) === [2, 3, 4], 'Twin Suns is UNCHANGED — all three others are opponents');

// ── Two-player games are untouched ───────────────────────────────────────────
InitializeGamestate();
check(SWUIsTeamGame() === false, 'a 2-player game is not a team game');
check(SWUTeammatesOf(1) === [],  'a 2-player game has no teammates');

echo "PASS\n";
