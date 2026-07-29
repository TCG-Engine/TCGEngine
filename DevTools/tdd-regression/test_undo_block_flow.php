<?php
// TDD guard for the undo request/deny/block anti-abuse flow (SWUSim undo redesign, Tasks 10/11):
//   • A public undo that needs consent sets a pending request (PENDING_UNDO_FROM + target).
//   • Deny clears the pending request and counts the denial; the SECOND deny raises the block prompt.
//   • Once the opponent blocks (UNDO_BLOCKED_{seat}), further requests from that seat are refused — no
//     pending request is created.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_undo_block_flow.php
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
$gameName = 'btest_' . getmypid(); $playerID = 1;
@mkdir('./Games/' . $gameName, 0777, true);

$b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', [], []); $b->WithActivePlayer(1);
$b->WithCardInHandForPlayer(1, 'SOR_095');
$g = new GameTestAdapter(); $g->loadState($b);
ob_start(); AutoAdvanceAndExecute(); ob_end_clean();

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

// Public game with an opponent (P2) action on the stack so P1's undo crosses it -> needs consent.
$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = false;
UndoStackClear(); SetSWUVar('UNDO_TOP', '-1');
SetSWUVar('UNDO_BLOCKED_1', 'false'); SetSWUVar('UNDO_BLOCKED_2', 'false');
SetSWUVar('PENDING_UNDO_FROM', ''); SetSWUVar('PENDING_UNDO_TARGET', ''); SetSWUVar('PENDING_BLOCK_PROMPT_FOR', '');
SetSWUVar('UNDO_DENY_COUNT_1', '0');
PushUndoSnapshot(1, 'action');  // ord0  P1
PushUndoSnapshot(2, 'action');  // ord1  P2 (opponent)
PushUndoSnapshot(1, 'action');  // ord2  P1 (folds ord1's non-reveal)

// Emulate case 10010's block (no SWU-specific fn; EngineActionRunner inlines it).
$applyBlock = function () { $t = intval(GetSWUVar('PENDING_BLOCK_PROMPT_FOR', '0')); if ($t >= 1 && $t <= 2) SetSWUVar('UNDO_BLOCKED_' . $t, 'true'); SetSWUVar('PENDING_BLOCK_PROMPT_FOR', ''); };

// ── Request #1 (Undo Phase always needs consent in public) then DENY ──
ob_start(); SWUDoUndo(1, 'phase', '', ''); ob_end_clean();
$check(GetSWUVar('PENDING_UNDO_FROM', '') === '1', 'request #1 sets PENDING_UNDO_FROM=1');
$check(GetSWUVar('PENDING_UNDO_TARGET', '') !== '', 'request #1 records a PENDING_UNDO_TARGET');
ob_start(); SWUDenyUndo(); ob_end_clean();
$check(GetSWUVar('PENDING_UNDO_FROM', '') === '', 'deny #1 clears the pending request');
$check(GetSWUVar('PENDING_UNDO_TARGET', '') === '', 'deny #1 clears the pending target');
$check(GetSWUVar('UNDO_DENY_COUNT_1', '0') === '1', 'deny #1 -> deny count 1');
$check(GetSWUVar('PENDING_BLOCK_PROMPT_FOR', '') === '', 'deny #1 does NOT raise the block prompt yet');

// ── Request #2 then DENY -> second denial raises the block prompt ──
ob_start(); SWUDoUndo(1, 'phase', '', ''); ob_end_clean();
$check(GetSWUVar('PENDING_UNDO_FROM', '') === '1', 'request #2 sets PENDING_UNDO_FROM=1');
ob_start(); SWUDenyUndo(); ob_end_clean();
$check(GetSWUVar('UNDO_DENY_COUNT_1', '0') === '2', 'deny #2 -> deny count 2');
$check(GetSWUVar('PENDING_BLOCK_PROMPT_FOR', '') === '1', 'deny #2 raises the block prompt for seat 1');

// ── Opponent blocks -> further requests refused (no pending request created) ──
$applyBlock();
$check(GetSWUVar('UNDO_BLOCKED_1', '') === 'true', 'block sets UNDO_BLOCKED_1');
SetSWUVar('PENDING_UNDO_FROM', ''); SetSWUVar('PENDING_UNDO_TARGET', '');
ob_start(); SWUDoUndo(1, 'phase', '', ''); ob_end_clean();
$check(GetSWUVar('PENDING_UNDO_FROM', '') === '', 'blocked seat 1 request creates NO pending request');

// A free undo restores a PRE-block payload (UNDO_BLOCKED_1='false' inside it), so the permanent block must
// be re-stamped afterward (_SWUReapplyUndoBlocks). Use a private game to make the undo free and exercise it.
$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = true;
ob_start(); SWUDoUndo(1, 'step', '', ''); ob_end_clean();
$check(GetSWUVar('UNDO_BLOCKED_1', '') === 'true', 'block survives a subsequent free undo (re-stamped)');

$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = false;
UndoStackClear();
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
