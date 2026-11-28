<?php
// Every `SeatCountForGame() <op> N` gate in SWUSim's tracked PHP must be CLASSIFIED — docs/superpowers/specs/2026-11-20-swusim-always-two-design.md.
//
// Always Two runs four boards, so every gate written as "more than two seats = Twin Suns" fires in it. Some of those are 4-board
// PLUMBING it needs (zone naming, opponent unions, pickers); others are Twin Suns RULES it must not inherit (Blast/Plan, the 4th
// counter, elimination, heal-5, end-of-phase scoring, house rules). A new gate that nobody classified would silently hand Always
// Two a Twin Suns rule — and no behavioural fixture can pin the ABSENCE of that, so this test reads the source instead.
//
// fixtures/seatcount_gates.txt: one line per gate, `<path> | <trimmed source line> | <n> | <class>`, where n is which occurrence
// of that exact line in the file it is (1, 2, …) — identical lines can take different classes. Classes:
//   plumbing  — correct for any multi-board game, Always Two included.
//   shared    — a Twin Suns rule Always Two keeps on purpose (SWUCounterRulesActive: the no-strategic-pass rule).
//   rule      — Twin Suns only; the line itself must call SWUTwinSunsRulesActive() or SWUIsAlwaysTwo().
//   pending-<task> — a rule site a later plan task converts (listed as NOTE, not failure).
// Keyed on the line TEXT (+ occurrence), not line numbers, so unrelated edits elsewhere never churn it.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php SWUSim/DevTools/tests/alwaystwo_seatcount_gates_test.php
chdir(dirname(__DIR__, 3));
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

// Generated + gitignored per CLAUDE.md — the generator owns them, never this list.
$generated = ['InitialLayout.php', 'GetNextTurn.php', 'NextTurnRender.php', 'GamestateParser.php', 'ZoneAccessors.php', 'ZoneClasses.php'];
$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('SWUSim/Custom', FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) if (substr($f->getFilename(), -4) === '.php') $files[] = str_replace('\\', '/', $f->getPathname());
foreach (glob('SWUSim/*.php') as $f) if (!in_array(basename($f), $generated, true)) $files[] = $f;
sort($files);

$gate = '/SeatCountForGame\(\)\s*(>=|<=|===|!==|==|!=|>|<)\s*\d/';
$found = [];   // key "path | text | n" => "path:line"
foreach ($files as $path) {
    $seen = [];
    foreach (file($path) as $i => $raw) {
        $code = preg_replace('#//.*$#', '', $raw);   // a gate mentioned in a comment is not a gate
        if (!preg_match($gate, $code)) continue;
        $text = trim($raw);
        $seen[$text] = ($seen[$text] ?? 0) + 1;
        $found[$path . ' | ' . $text . ' | ' . $seen[$text]] = $path . ':' . ($i + 1);
    }
}

$table = [];
foreach (file(__DIR__ . '/fixtures/seatcount_gates.txt') as $ln) {
    $ln = rtrim($ln, "\r\n");
    if ($ln === '' || $ln[0] === '#') continue;
    $cut = strrpos($ln, ' | ');
    if ($cut === false) { $check(false, "malformed fixture line: $ln"); continue; }
    $table[substr($ln, 0, $cut)] = substr($ln, $cut + 3);
}

$check(count($found) > 0, 'the scan finds gates at all (' . count($found) . ')');
$unlisted = array_diff_key($found, $table);
foreach ($unlisted as $k => $where) $check(false, "UNCLASSIFIED gate at $where — add it to fixtures/seatcount_gates.txt: $k");
$check(empty($unlisted), 'every gate is classified');

foreach (array_diff_key($table, $found) as $k => $_) $check(false, "stale fixture line (no such gate any more): $k");

$pending = 0;
foreach ($found as $k => $where) {
    $class = $table[$k] ?? null;
    if ($class === null) continue;
    if (!in_array($class, ['plumbing', 'shared', 'rule'], true) && !str_starts_with($class, 'pending-')) {
        $check(false, "unknown class '$class' at $where");
    } elseif ($class === 'rule') {
        $line = substr($k, strpos($k, ' | ') + 3);
        $check(str_contains($line, 'SWUTwinSunsRulesActive()') || str_contains($line, 'SWUIsAlwaysTwo()'),
            "rule gate at $where is guarded for Always Two");
    } elseif (str_starts_with($class, 'pending-')) {
        echo "NOTE: $class at $where\n";
        $pending++;
    }
}
echo "pending: $pending\n";

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
