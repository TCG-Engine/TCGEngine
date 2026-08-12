<?php
// TDD guard: bookmarks work at four seats (Twin Suns). The store lives in seat 2's Versions zone, which
// is a STORAGE SLOT rather than player-owned data — this proves that holds when seat 2 is a real player,
// and that a seat-3 or seat-4 bookmark is neither clamped nor dropped.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_bookmark_four_seats.php
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
$gameName = 'bm4_' . getmypid(); $playerID = 1;
@mkdir('./Games/' . $gameName, 0777, true);

// Four-seat game. SeatCountForGame() is count(GetSeatOrderArray()), and the builder sets that via
// WithSeatOrder — seat lists are single-digit concatenations (see SWUSim/Tests/Cases/twinsuns/*.md,
// which use `WithSeatOrder: 123`). CommonSetup stays two-code, exactly as those fixtures do.
$b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', [], []); $b->WithSeatOrder('1234'); $b->WithActivePlayer(1);
$g = new GameTestAdapter(); $g->loadState($b);
ob_start(); AutoAdvanceAndExecute(); ob_end_clean();
$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = true;

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$handCount = function ($p) { $n = 0; foreach (GetHand($p) as $c) { if (empty($c->removed)) $n++; } return $n; };

// Load-bearing: a silently-2-seat fixture would make every assertion below pass for the wrong reason.
$check(SeatCountForGame() === 4, 'fixture really has 4 seats — got ' . SeatCountForGame());

UndoStackClear(); BookmarkStoreClear(); UndoCursorSet(-1);

PushUndoSnapshot(3, 'action');
MZAddZone(3, 'myHand', 'SOR_046');
$h3 = $handCount(3);
ob_start(); $ok = SWUTakeBookmark(3, 'seat three', '', $gameName); ob_end_clean();
$check($ok === true, 'seat 3 can take a bookmark');
$r = BookmarkRead(1);
$check($r !== null && $r['seat'] === 3, 'seat 3 stored verbatim, not clamped — got ' . ($r['seat'] ?? 'null'));

ob_start(); SWUTakeBookmark(4, 'seat four', '', $gameName); ob_end_clean();
$check((BookmarkRead(2)['seat'] ?? 0) === 4, 'seat 4 stored verbatim');

// Seat 2 being a real player does not disturb the sidecar.
MZAddZone(2, 'myHand', 'SOR_046'); MZAddZone(2, 'myHand', 'SOR_046');
$check(BookmarkCount() === 2, 'seat 2 gameplay does not disturb the bookmark store');

// Any seat can load any bookmark — no consent in a private game.
MZAddZone(3, 'myHand', 'SOR_046');
ob_start(); $ok = SWULoadBookmark(1, 1, '', $gameName); ob_end_clean();
$check($ok === true, 'seat 1 can load seat 3\'s bookmark (no consent in private)');
$check($handCount(3) === $h3, 'seat 3 hand restored to its bookmarked size — got ' . $handCount(3));

// N-seat block re-stamp: a seat-4 block must survive a restore.
SetSWUVar('UNDO_BLOCKED_4', 'true');
ob_start(); SWULoadBookmark(1, 2, '', $gameName); ob_end_clean();
$check(GetSWUVar('UNDO_BLOCKED_4', 'false') === 'true', 'a seat-4 undo block survives a restore (N-seat re-stamp)');

UndoStackClear(); BookmarkStoreClear();
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
