<?php
require_once __DIR__ . '/../GrandArchiveSim/Custom/UIRevision.php';
$lock = tmpfile();
if ($lock === false) throw new RuntimeException('Cannot create test lock');
flock($lock, LOCK_EX);
$cases = [
    ['7', 7, $lock, null],
    ['6', 7, $lock, 'changed'],
    ['8', 7, $lock, 'changed'],
    ['7', 7, true, 'locked'],
    ['7', 7, false, 'locked'],
    ['07', 7, $lock, 'Invalid'],
    [['7'], 7, $lock, 'Invalid'],
    ['-1', 7, $lock, 'Invalid'],
    ['7.0', 7, $lock, 'Invalid'],
    ['9999999999999999999999', 7, $lock, 'Invalid'],
];
foreach ($cases as [$expected, $current, $handle, $error]) {
    $result = GAUIRevisionError($expected, $current, $handle);
    if ($error === null ? $result !== null : !str_contains($result ?? '', $error)) {
        throw new RuntimeException('Unexpected revision guard result');
    }
}
fclose($lock);
echo count($cases) . " revision guard cases passed\n";
