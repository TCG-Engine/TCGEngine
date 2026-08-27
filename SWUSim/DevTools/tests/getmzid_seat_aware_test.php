<?php
// ── GetMzID() WAS STRUCTURALLY TWO-SEAT ─────────────────────────────────────────────────────────────
//
// ZoneClasses' GetMzID() built its prefix from the AMBIENT $playerID:
//     $prefix = $playerID == $this->PlayerID ? "my" : "their";
// "their<Zone>" names no seat. Above two seats that is not merely imprecise, it is WRONG: it resolves to
// whichever opponent the READER's frame picks — seat 2 for a seat-1 reader, and nothing at all for a
// reader at seats 3-4 (GetOpponent() NULLs there). Every other producer of an unqualified pool had
// already been converted to real p{n} mzIDs (ZoneSearch's opponent fan-out, SWUAllUnits,
// SWUAllBaseMzIDs); GetMzID() was the last one still speaking the two-seat dialect, and its ~29 call
// sites hand the result straight to damage / token / trigger APIs.
//
// The fix lives in the GENERATOR (zzGameCodeGenerator.php), gated on $rootName == "SWUSim":
// ZoneClasses.php is generated AND gitignored, so a hand-edit there has no git trace and is wiped by the
// next regen. ⚠ If this test fails after pulling, run `php zzGameCodeGenerator.php rootName=SWUSim`.
//
// 2-player must stay BYTE-IDENTICAL — the whole existing engine, client and test corpus addresses the
// single opponent as "their…", so the seat-specific form is emitted only when SeatCountForGame() > 2.

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

/** Seat $seat's first ground-arena object, or null. */
function gm_first_unit(int $seat) {
    $arena = &GetGroundArena($seat);
    return count($arena) > 0 ? $arena[0] : null;
}

// ── FOUR SEATS ───────────────────────────────────────────────────────────────
InitializeGamestate();
SetSeatOrder('1234'); SetLiveSeats('1234');
check(SeatCountForGame() === 4, 'four-seat game');

// AddGroundArena($player, $CardID, $Status, $Owner, $Damage, $Controller, $TurnEffects, $Subcards)
foreach ([1, 2, 3, 4] as $seat) AddGroundArena($seat, 'SOR_046', 1, $seat, 0, $seat, '-', '-');

$playerID = 1;
$own = gm_first_unit(1);
check($own !== null && $own->GetMzID() === 'myGroundArena-0',
    "own unit is still 'my…' (got: " . ($own ? $own->GetMzID() : 'null') . ')');

// The load-bearing case: a FOREIGN object read from seat 1's frame must name its seat, not "their".
foreach ([2, 3, 4] as $seat) {
    $u = gm_first_unit($seat);
    check($u !== null && $u->GetMzID() === "p{$seat}GroundArena-0",
        "seat {$seat}'s unit reads as p{$seat}GroundArena-0 from seat 1 (got: "
        . ($u ? $u->GetMzID() : 'null') . ')');
}

// And from a FAR seat's frame — the direction GetOpponent() cannot express at all.
$playerID = 4;
$u = gm_first_unit(1);
check($u !== null && $u->GetMzID() === 'p1GroundArena-0',
    "seat 1's unit reads as p1GroundArena-0 from SEAT 4 (got: " . ($u ? $u->GetMzID() : 'null') . ')');
$u = gm_first_unit(4);
check($u !== null && $u->GetMzID() === 'myGroundArena-0', "seat 4's own unit is 'my…' from seat 4");

// ── TWO SEATS — byte-identical to the historical behaviour ───────────────────
InitializeGamestate();
SetSeatOrder('12'); SetLiveSeats('12');
check(SeatCountForGame() === 2, 'two-seat game');
foreach ([1, 2] as $seat) AddGroundArena($seat, 'SOR_046', 1, $seat, 0, $seat, '-', '-');

$playerID = 1;
$own = gm_first_unit(1);
$opp = gm_first_unit(2);
check($own !== null && $own->GetMzID() === 'myGroundArena-0',    'two seats: own unit is myGroundArena-0');
check($opp !== null && $opp->GetMzID() === 'theirGroundArena-0',
    "two seats: the opponent is STILL 'theirGroundArena-0' (got: " . ($opp ? $opp->GetMzID() : 'null') . ')');

echo "PASS: getmzid_seat_aware_test\n";
