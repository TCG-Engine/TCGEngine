<?php
// A match record is never seen half-written (2026-11-04). MatchWrite used file_put_contents(LOCK_EX), which TRUNCATES the
// file before it takes the lock, and MatchRead takes no lock — so a read landing during any write saw an empty Match.json
// and the caller answered "Unknown match." (it surfaced as a sideboard Cancel failing while both seats polled; a probe saw
// 346 of 1,651 reads come back empty against one writer). One process rewrites the match 3,000 times under MatchWithLock
// while this one reads it as fast as it can: every read must decode.
// CLI (it starts the writer as a second PHP process):
// docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off DevTools/tdd-regression/test_match_write_atomic.php
chdir(__DIR__ . '/../..');
include_once './Core/Match/Match.php';
$id = 'MATOMIC' . ($argv[2] ?? getmypid());
if (($argv[1] ?? '') === 'write') {
    for ($i = 0; $i < 3000; $i++) MatchWithLock('SWUSim', $id, function (&$m) use ($i) { $m['n'] = $i; });
    exit(0);
}
$dir = MatchesDir('SWUSim') . '/' . $id;
@mkdir($dir, 0777, true);
MatchWrite(['rootName' => 'SWUSim', 'matchId' => $id, 'pad' => str_repeat('x', 20000), 'n' => -1]);
$writer = proc_open([PHP_BINARY, '-d', 'xdebug.mode=off', __FILE__, 'write', substr($id, 7)], [], $pipes);
$reads = 0; $empty = 0; $t0 = microtime(true);
while (microtime(true) - $t0 < 30) {
    $st = proc_get_status($writer);
    $reads++;
    if (MatchRead('SWUSim', $id) === null) $empty++;
    if (!$st['running']) break;
}
proc_close($writer);
$last = MatchRead('SWUSim', $id);
$leftovers = array_values(array_filter(glob($dir . '/*') ?: [], fn($f) => !in_array(basename($f), ['Match.json', 'Match.lock'], true)));
exec('rm -rf ' . escapeshellarg($dir));
$checks = [
    "every read during the writes decoded ($empty of $reads empty)" => $empty === 0 && $reads > 100,
    'the last write is the one on disk (n=' . json_encode($last['n'] ?? null) . ')' => intval($last['n'] ?? -2) === 2999,
    'no temp files are left beside it (' . count($leftovers) . ')' => $leftovers === [],
];
foreach ($checks as $name => $ok) echo ($ok ? '  ok: ' : '  BAD: ') . $name . "\n";
echo in_array(false, $checks, true) ? "FAIL\n" : "PASS (" . count($checks) . " checks)\n";
