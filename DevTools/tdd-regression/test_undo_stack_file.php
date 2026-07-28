<?php
// TDD guard for the per-game append-only undo-stack file (SWUSim undo redesign, Task 4).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_undo_stack_file.php
// The stack lives in a separate file (Games/<game>/UndoStack.txt), off the per-action gamestate hot
// path. Each line is one entry; the LINE INDEX is the ordinal. Payloads are base64 (newline-free).
error_reporting(E_ALL & ~E_DEPRECATED);
chdir('/var/www/html/TCGEngine');
$GLOBALS['gameName'] = 'utest_' . getmypid();
@mkdir('./Games/' . $GLOBALS['gameName'], 0777, true);
include_once './SWUSim/Custom/UndoStack.php';

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

UndoStackClear();
$check(UndoStackCount() === 0, 'empty after clear');

$rec1 = "1\tMAIN\taction\t0\t"          . base64_encode("payload-ZERO\nwith\ttabs and \x00 bytes");
$rec2 = "2\tRES\tphase-boundary\t1\t"   . base64_encode(str_repeat('X', 500));
$rec3 = "1\tAPS\taction\t0\t"           . base64_encode('third');
UndoStackAppend($rec1);
UndoStackAppend($rec2);
UndoStackAppend($rec3);
$check(UndoStackCount() === 3, 'three appends -> count == 3');
$check(UndoStackRead(1) === $rec2, 'read(1) returns the exact record (tabs + base64 + special bytes round-trip)');
$check(UndoStackRead(0) === $rec1, 'read(0) exact');
$check(UndoStackRead(5) === null, 'read out-of-range -> null');

UndoStackTruncateTo(0);
$check(UndoStackCount() === 1 && UndoStackRead(0) === $rec1, 'truncateTo(0) drops entries above index 0');

UndoStackClear();
$check(UndoStackCount() === 0 && !file_exists(UndoStackPath()), 'clear removes the file');

@rmdir('./Games/' . $GLOBALS['gameName']);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
