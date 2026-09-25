<?php
// Force a Twin Suns / Team Suns game's LIVE SEATS (the non-eliminated subset of SeatOrder). Fixture
// support for DevTools/ui-harness/swusim-twinsuns-home-panels-xbrowser.mjs, which has to see the board
// after eliminations without playing a game into each one.
//
//   php SWUSim/DevTools/set-live-seats.php <gameName> [<liveSeats>]
//
//   liveSeats : digits, e.g. "13" = seats 1 and 3 are still in it. OMIT to only PRINT the current
//               SeatOrder / LiveSeats — which is how the harness picks a source game.
//
// Prints "seatOrder=<digits> liveSeats=<digits>" either way, so a caller can assert what it got.
// Dev-only: it writes a gamestate directly, so point it at a scratch clone, never a live game.
error_reporting(E_ALL & ~E_DEPRECATED);
if (!function_exists('ConvertMzIDToAbsolute'))         { function ConvertMzIDToAbsolute($m, $p): string { return ''; } }
if (!function_exists('QueueDamageAnimation'))          { function QueueDamageAnimation($t, $a): void {} }
if (!function_exists('QueueRestoreAnimation'))         { function QueueRestoreAnimation($t, $a): void {} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t): void {} }
if (!function_exists('QueueShieldBreakAnimation'))     { function QueueShieldBreakAnimation($t): void {} }
include_once './Core/DeterministicRNG.php';
include_once './Core/CoreZoneModifiers.php';
include_once './Core/NetworkingLibraries.php';
include_once './SWUSim/ZoneClasses.php';
include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once './SWUSim/GamestateParser.php';
InitializeGamestate();

if ($argc < 2) { fwrite(STDERR, "usage: set-live-seats.php <gameName> [<liveSeats>]\n"); exit(1); }
$g    = $argv[1];
$live = isset($argv[2]) ? preg_replace('/[^1-9]/', '', strval($argv[2])) : null;

$GLOBALS['gameName'] = $g;
ParseGamestate('./SWUSim/');

$order = implode('', GetSeatOrderArray());
if ($live !== null) {
    // Every live seat must be a seat that actually played, or the client's "were you ever seated" test
    // (SeatOrderData) and its "are you still in it" test (LiveSeatsData) disagree and the view builder
    // takes a branch no real game can reach.
    foreach (str_split($live) as $d) {
        if (strpos($order, $d) === false) {
            fwrite(STDERR, "seat $d is not in SeatOrder '$order'\n");
            exit(1);
        }
    }
    SetLiveSeats($live);
    // ⚠ THE SAME PATH PREFIX ParseGamestate USED — WriteGamestate() defaults to "./", which resolves to
    // ./Games/<id>/ from the web root and silently loses the write (see set-twinsuns-counters.php).
    WriteGamestate('./SWUSim/');
}
$now = trim((string)GetLiveSeats());
echo "seatOrder=$order liveSeats=" . ($now === '' ? $order : $now) . "\n";
