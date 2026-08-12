<?php
// TDD guard: bookmarks are PRIVATE-LOBBY ONLY, enforced on the SERVER. The client hides the menu
// items, but a crafted request must still be refused — client-side hiding is cosmetic.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_bookmark_private_gate.php
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
$gameName = 'bmgate_' . getmypid(); $playerID = 1;
@mkdir('./Games/' . $gameName, 0777, true);

$b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', [], []); $b->WithActivePlayer(1);
$b->WithCardInHandForPlayer(1, 'SOR_095');   // seed so hand counts start at 1
$g = new GameTestAdapter(); $g->loadState($b);
ob_start(); AutoAdvanceAndExecute(); ob_end_clean();

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$handCount = function () { $n = 0; foreach (GetHand(1) as $c) { if (empty($c->removed)) $n++; } return $n; };

UndoStackClear(); BookmarkStoreClear(); UndoCursorSet(-1);

// ── PUBLIC: both operations refused ──────────────────────────────────────────
$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = false;
ob_start(); $ok = SWUTakeBookmark(1, 'nope', '', $gameName); ob_end_clean();
$check($ok === false, 'SWUTakeBookmark refused in a public game');
$check(BookmarkCount() === 0, 'nothing stored in a public game');

// ── PRIVATE: take one, then go public and try to load it ─────────────────────
$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = true;
MZAddZone(1, 'myHand', 'SOR_046');                      // hand 2
ob_start(); SWUTakeBookmark(1, 'yes', '', $gameName); ob_end_clean();
$check(BookmarkCount() === 1, 'stored in a private game');
MZAddZone(1, 'myHand', 'SOR_046');                      // hand 3

$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = false;
$before = $handCount();
ob_start(); $ok = SWULoadBookmark(1, 1, '', $gameName); ob_end_clean();
$check($ok === false, 'SWULoadBookmark refused in a public game');
$check($handCount() === $before, 'a refused load leaves the gamestate untouched — got ' . $handCount());
$check(UndoStackCount() === 0, 'a refused load pushes NO snapshot — got ' . UndoStackCount());

// ── back to private: it works ────────────────────────────────────────────────
$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = true;
ob_start(); $ok = SWULoadBookmark(1, 1, '', $gameName); ob_end_clean();
$check($ok === true && $handCount() === 2, 'load succeeds again in a private game — hand ' . $handCount());

// ── the cap refusal ──────────────────────────────────────────────────────────
BookmarkStoreClear();
for ($i = 0; $i < SWU_BOOKMARK_MAX; $i++) { ob_start(); SWUTakeBookmark(1, 'f' . $i, '', $gameName); ob_end_clean(); }
ob_start(); $ok = SWUTakeBookmark(1, 'over', '', $gameName); ob_end_clean();
$check($ok === false, 'take refused at the cap');
$check(BookmarkCount() === SWU_BOOKMARK_MAX, 'store stays at the cap — got ' . BookmarkCount());

UndoStackClear(); BookmarkStoreClear();
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
