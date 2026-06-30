<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_match_reaper.php
header('Content-Type: text/plain');
include __DIR__ . '/../../SWUSim/MatchFlow.php';

// Stale, completed match → should be reaped.
$stale = SWUCreateMatch('SWUSim', 'premier', 'bo3', [
    1 => ['originalDeck' => [], 'authKey' => 'r1'],
    2 => ['originalDeck' => [], 'authKey' => 'r2'],
]);
// Backdate + complete by writing the file directly (SWUWriteMatch would stamp updatedAt=now).
$sm = SWUReadMatch($stale); $sm['state'] = 'complete'; $sm['updatedAt'] = time() - 100000;
file_put_contents(SWUMatchPath($stale), json_encode($sm), LOCK_EX);

// Fresh, in-progress match → should survive.
$fresh = SWUCreateMatch('SWUSim', 'premier', 'bo3', [
    1 => ['originalDeck' => [], 'authKey' => 'f1'],
    2 => ['originalDeck' => [], 'authKey' => 'f2'],
]);

$reaped = SWUReapStaleMatches(86400);

$checks = [];
$checks['reaped at least one'] = $reaped >= 1;
$checks['stale dir gone'] = !is_file(SWUMatchPath($stale));
$checks['fresh dir survives'] = is_file(SWUMatchPath($fresh));

// cleanup the fresh one
@array_map('unlink', glob(dirname(SWUMatchPath($fresh)) . '/*') ?: []);
@rmdir(dirname(SWUMatchPath($fresh)));

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks) reaped=$reaped\n"
   : "FAIL: " . implode(', ', $fails) . " reaped=$reaped\n";
