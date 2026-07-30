<?php
// bugreport-load-state.php — CLI: step an already-written SWUSim Games/<game>/Gamestate.txt to a
// chosen undo state, in place. Used by zzBugReportViewer.php's "Load Last Round / Game Begin" actions
// (the raw snapshot is written first; this rewinds it). 'current' needs no step, so the viewer skips it.
//
//   php SWUSim/DevTools/bugreport-load-state.php <gameName> <current|last-round|begin>
//   → prints a JSON status line; exit 0 on success.
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');

$root = dirname(__DIR__, 2);   // …/SWUSim/DevTools → repo root (…/TCGEngine)
chdir($root);

// Animation stubs (mirrors the tdd-regression harness — the parse/restore path may reference them).
if (!function_exists('ConvertMzIDToAbsolute'))         { function ConvertMzIDToAbsolute($m, $p): string { return ''; } }
if (!function_exists('QueueDamageAnimation'))          { function QueueDamageAnimation($t, $a): void {} }
if (!function_exists('QueueRestoreAnimation'))         { function QueueRestoreAnimation($t, $a): void {} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t): void {} }
if (!function_exists('QueueShieldBreakAnimation'))     { function QueueShieldBreakAnimation($t): void {} }

foreach (['DeterministicRNG', 'CoreZoneModifiers', 'GameAuth'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php';
include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once './SWUSim/GamestateParser.php';                 // pulls in Custom/ (GameLogic, UndoStack, …) via ServerInclude
require_once __DIR__ . '/BugReportLoadStateLib.php';

function _bugLoadRespond(int $exit, array $payload): void { echo json_encode($payload) . "\n"; exit($exit); }

$gameNameArg = strval($argv[1] ?? '');
$mode        = strval($argv[2] ?? 'current');
if ($gameNameArg === '' || !preg_match('/^[0-9A-Za-z_-]+$/', $gameNameArg)) _bugLoadRespond(2, ['error' => 'Invalid game name.']);
if (!in_array($mode, ['current', 'last-round', 'begin'], true))              _bugLoadRespond(2, ['error' => 'Invalid mode.']);

global $gameName, $playerID;
$gameName = $gameNameArg; $GLOBALS['gameName'] = $gameNameArg; $playerID = 1;

if (!is_file("./SWUSim/Games/{$gameNameArg}/Gamestate.txt")) _bugLoadRespond(3, ['error' => 'Gamestate not found for game ' . $gameNameArg . '.']);

ParseGamestate('./SWUSim/');
$res = SWUBugReportStepLoadedGamestate($mode);
if (!$res['ok']) _bugLoadRespond(4, ['error' => 'Undo restore failed for mode ' . $mode . '.'] + $res);
if ($res['stepped']) WriteGamestate('./SWUSim/');

_bugLoadRespond(0, ['success' => true] + $res);
