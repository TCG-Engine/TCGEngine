<?php
// TDD guard for the bookmark sidecar store (GetVersions(2)).
// Mirrors test_undo_in_gamestate.php: the point is that bookmarks ride INSIDE Gamestate.txt, so a bug
// report carries them and loading a gamestate restores them.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_bookmark_store_roundtrip.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
if (!function_exists('ConvertMzIDToAbsolute'))      { function ConvertMzIDToAbsolute($m,$p):string{return '';} }
if (!function_exists('QueueDamageAnimation'))       { function QueueDamageAnimation($t,$a):void{} }
if (!function_exists('QueueRestoreAnimation'))      { function QueueRestoreAnimation($t,$a):void{} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t):void{} }
if (!function_exists('QueueShieldBreakAnimation'))  { function QueueShieldBreakAnimation($t):void{} }
foreach (['DeterministicRNG','CoreZoneModifiers','GameAuth'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
foreach (['Assertions','Cards','CommonSetup','GameStateBuilder','GameTestAdapter','SchemaTestRunner','TestRunner'] as $f) include_once "./SWUSim/Tests/Framework/$f.php";
global $gameName, $playerID;
$gameName = 'bmtest_' . getmypid(); $playerID = 1;
// ./SWUSim/Games/, not ./Games/ — this test calls WriteGamestate('./SWUSim/'), which writes there.
@mkdir('./SWUSim/Games/' . $gameName, 0777, true);

$b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', [], []); $b->WithActivePlayer(1);
$g = new GameTestAdapter(); $g->loadState($b);
ob_start(); AutoAdvanceAndExecute(); ob_end_clean();

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

// ── cursor ───────────────────────────────────────────────────────────────────
BookmarkStoreClear();
$check(UndoCursorGet() === -1, 'cursor defaults to -1 on an empty store (got ' . UndoCursorGet() . ')');
UndoCursorSet(7);
$check(UndoCursorGet() === 7, 'cursor round-trips (got ' . UndoCursorGet() . ')');
UndoCursorSet(-1);

// ── bookmark records ─────────────────────────────────────────────────────────
$payloadA = str_repeat("zone-data-A|", 300) . "\ttab\nnl\x00nul";
$payloadB = "short-B";
$idA = BookmarkAppend(1, 4, 'MAIN', 12, 'before the Vader turn', $payloadA);
$idB = BookmarkAppend(3, 9, 'RES', 40, '', $payloadB);

$check($idA === 1, 'first bookmark gets id 1 (index 0 is the header) — got ' . $idA);
$check($idB === 2, 'second bookmark gets id 2 — got ' . $idB);
$check(BookmarkCount() === 2, 'count == 2 — got ' . BookmarkCount());

$a = BookmarkRead($idA);
$check($a !== null && $a['payload'] === $payloadA, 'payload A round-trips byte-exactly through compression');
$check($a['seat'] === 1 && $a['round'] === 4 && $a['phase'] === 'MAIN' && $a['cursorAt'] === 12, 'A metadata intact');
$check($a['label'] === 'before the Vader turn', 'A label round-trips (spaces survive base64)');
$b2 = BookmarkRead($idB);
$check($b2 !== null && $b2['seat'] === 3, 'seat 3 stored (Twin Suns range, not clamped to 1-2)');
$check($b2['label'] === '', 'empty label round-trips');
$check(BookmarkRead(99) === null, 'out-of-range read -> null');
$check(BookmarkRead(0) === null, 'id 0 is the header, never a bookmark');

// The cursor must be untouched by bookmark writes — they share the zone.
$check(UndoCursorGet() === -1, 'appending bookmarks does not disturb the cursor');

// list() omits payloads (it feeds a JSON endpoint; payloads must never reach a client).
$list = BookmarkList();
$check(count($list) === 2, 'list returns 2 entries');
$check(!isset($list[$idA]['payload']), 'list entries carry NO payload');
$check($list[$idA]['round'] === 4, 'list entry keeps round');

// ── cap ──────────────────────────────────────────────────────────────────────
for ($i = BookmarkCount(); $i < SWU_BOOKMARK_MAX; $i++) BookmarkAppend(1, 1, 'MAIN', 0, 'filler', 'p');
$check(BookmarkCount() === SWU_BOOKMARK_MAX, 'filled to the cap (' . SWU_BOOKMARK_MAX . ')');
$check(BookmarkAppend(1, 1, 'MAIN', 0, 'overflow', 'p') === -1, 'append past the cap returns -1');
$check(BookmarkCount() === SWU_BOOKMARK_MAX, 'a refused append does not grow the store');

// ── the point: survives a gamestate round-trip ───────────────────────────────
BookmarkStoreClear();
UndoCursorSet(5);
$idA = BookmarkAppend(2, 7, 'MAIN', 5, 'round seven', $payloadA);
ob_start(); WriteGamestate('./SWUSim/'); ob_end_clean();
$vz = &GetVersions(2); $vz = [];                              // wipe from memory
$check(BookmarkCount() === 0, 'store wiped from memory');
if (function_exists('RegressionClearGamestateMemory')) RegressionClearGamestateMemory($gameName);
ob_start(); ParseGamestate('./SWUSim/'); ob_end_clean();

$check(BookmarkCount() === 1, 'bookmark RESTORED from the gamestate — rides in Gamestate.txt');
$r = BookmarkRead(1);
$check($r !== null && $r['payload'] === $payloadA, 'restored payload byte-exact');
$check($r !== null && $r['label'] === 'round seven', 'restored label intact');
$check(UndoCursorGet() === 5, 'cursor RESTORED from the gamestate — got ' . UndoCursorGet());

BookmarkStoreClear();
array_map('unlink', glob('./SWUSim/Games/' . $gameName . '/*') ?: []); @rmdir('./SWUSim/Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
