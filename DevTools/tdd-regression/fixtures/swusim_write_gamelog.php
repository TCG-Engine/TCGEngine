<?php
// Test fixture: overwrite one saved game's GameLog zone.
//   curl 'http://localhost:3400/TCGEngine/DevTools/tdd-regression/fixtures/swusim_write_gamelog.php?gameName=<n>&log=<raw>'
//
// ⚠ MUST RUN IN THE WEB SAPI, NOT CLI, AND THAT IS THE WHOLE REASON THIS IS AN ENDPOINT.
// ParseGamestate() reads the MEMORY cache before the file (GamestateUsesMemoryStorage →
// SimGameReadGamestateCache), and APCu does not exist in the PHP CLI SAPI (apc.enable_cli=0). A CLI
// version of this fixture updated Gamestate.txt correctly and left the cache stale, so the very next
// web request read the OLD log and every assertion failed while the file on disk was right.
//
// ⚠ GOES THROUGH ParseGamestate/WriteGamestate, never file_put_contents. Gamestate.txt is serialised
// POSITIONALLY, zone by zone in GameSchema.txt order with no delimiters — a hand-built file silently
// misaligns every zone after the one you got wrong.
//
// The include preamble mirrors SWUSim/DevTools/bugreport-load-state.php, the established way to open
// a saved gamestate outside a turn.
//
// Lives in fixtures/ so the regression runner (which globs DevTools/tdd-regression/*.php) never runs
// it as a test, and is gated to local dev so it can never rewrite a real game in production.
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');
header('Content-Type: text/plain');

$root = dirname(__DIR__, 3);   // …/DevTools/tdd-regression/fixtures → repo root
chdir($root);

require_once $root . '/SWUSim/Mod/DevGate.php';
if (!SWUIsLocalDevRequest()) { http_response_code(403); echo "Local dev only.\n"; exit; }

// Animation stubs — the parse/restore path may reference them outside a real turn.
if (!function_exists('ConvertMzIDToAbsolute'))         { function ConvertMzIDToAbsolute($m, $p): string { return ''; } }
if (!function_exists('QueueDamageAnimation'))          { function QueueDamageAnimation($t, $a): void {} }
if (!function_exists('QueueRestoreAnimation'))         { function QueueRestoreAnimation($t, $a): void {} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t): void {} }
if (!function_exists('QueueShieldBreakAnimation'))     { function QueueShieldBreakAnimation($t): void {} }

foreach (['DeterministicRNG', 'CoreZoneModifiers', 'GameAuth'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php';
include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once './SWUSim/GamestateParser.php';

$gameNameArg = strval($_GET['gameName'] ?? '');
$logArg      = strval($_GET['log'] ?? '');
if (!preg_match('/^[0-9A-Za-z_-]+$/', $gameNameArg)) { http_response_code(400); echo "Invalid game name.\n"; exit; }
if (!is_file("./SWUSim/Games/{$gameNameArg}/Gamestate.txt")) {
    http_response_code(404);
    echo "No gamestate for {$gameNameArg}; create the game first (SWUSim/TestSchemaSetup.php).\n";
    exit;
}

global $gameName, $playerID, $gGameLog;
$gameName = $gameNameArg; $GLOBALS['gameName'] = $gameNameArg; $playerID = 1;

ParseGamestate('./SWUSim/');
$gGameLog = $logArg;
$GLOBALS['gGameLog'] = $logArg;
WriteGamestate('./SWUSim/');
echo "OK\n";
