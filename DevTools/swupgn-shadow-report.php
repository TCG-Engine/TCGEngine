<?php
// Summary of a SWU-PGN shadow run (SWUPGN_SHADOW=1 over the schema suite — SWUSim/Custom/SwuPgnWriter/Shadow.php):
// tests run, recorder failures, fold mismatches, the recorder's time per action, and announcement gaps per card.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off -e SWUPGN_SHADOW=1 -e SWUPGN_SHADOW_OUT=/tmp/shadow.jsonl \
//     otmtcge-swusim-web-server-1 php .claude/skills/swusim-debug-game/scripts/run-schema-tests.php [files…]
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/swupgn-shadow-report.php /tmp/shadow.jsonl [topN]
$path = $argv[1] ?? (sys_get_temp_dir() . '/swupgn-shadow.jsonl');
$top = intval($argv[2] ?? 40);
if (!is_file($path)) { fwrite(STDERR, "no shadow report at $path\n"); exit(2); }
$tests = 0; $skipped = 0; $finishChecks = 0; $errors = []; $throws = []; $invalid = []; $mismatched = []; $actions = 0; $ms = 0.0; $gaps = 0; $kinds = []; $cards = []; $gapTests = 0;
foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $raw) {
    $l = json_decode($raw, true);
    if (!is_array($l)) continue;
    if (!empty($l['skipped'])) { $skipped++; continue; }
    $tests++;
    if (!empty($l['error'])) $errors[] = [$l['test'], $l['error']];
    if (intval($l['recorderErrors'] ?? 0) > 0) $throws[] = [$l['test'], intval($l['recorderErrors'])];
    if (intval($l['finishCheckFailures'] ?? 0) > 0) $finishChecks++;
    if (intval($l['mismatchCount'] ?? 0) > 0) $mismatched[] = [$l['test'], intval($l['mismatchCount']), $l['mismatches'][0] ?? null];
    if (!empty($l['validateErrors'])) $invalid[] = [$l['test'], $l['validateErrors'][0]];
    $actions += intval($l['actions'] ?? 0); $ms += floatval($l['recorderMs'] ?? 0);
    $gaps += intval($l['gaps'] ?? 0);
    if (intval($l['gaps'] ?? 0) > 0) $gapTests++;
    foreach ($l['gapKinds'] ?? [] as $k => $n) $kinds[$k] = ($kinds[$k] ?? 0) + intval($n);
    foreach ($l['gapCards'] ?? [] as $c => $n) $cards[$c] = ($cards[$c] ?? 0) + intval($n);
}
arsort($kinds); arsort($cards);
printf("tests run           %d (+%d skipped: 3-4 seat games are not recorded)\n", $tests, $skipped);
printf("shadow errors       %d\n", count($errors));
printf("recorder throws     %d tests\n", count($throws));
printf("game-end checks     %d tests (expected: a shadow recording starts mid-game, so the game's own files cannot check clean)\n", $finishChecks);
printf("fold mismatches     %d tests\n", count($mismatched));
printf("invalid recordings  %d tests\n", count($invalid));
printf("recorder time       %.3f ms/action over %d actions (budget < 10)\n", $actions ? $ms / $actions : 0, $actions);
printf("announcement gaps   %d in %d tests\n", $gaps, $gapTests);
if ($kinds) echo '  by kind           ' . implode(', ', array_map(fn($k, $n) => "$k $n", array_keys($kinds), $kinds)) . "\n";
foreach ([['shadow errors', $errors], ['recorder throws', $throws], ['invalid recordings', $invalid]] as [$title, $rows]) {
    if (!$rows) continue;
    echo "\n$title:\n";
    foreach (array_slice($rows, 0, $top) as $r) echo '  ' . $r[0] . ' — ' . json_encode($r[1]) . "\n";
}
if ($mismatched) {
    echo "\nfold mismatches (first of each):\n";
    foreach (array_slice($mismatched, 0, $top) as [$t, $n, $first]) echo "  $t — $n — " . json_encode($first) . "\n";
}
if ($cards) {
    echo "\ngaps per card (top $top):\n";
    $titles = [];
    if (is_file(__DIR__ . '/../SWUSim/GeneratedCode/GeneratedCardDictionaries.php')) include_once __DIR__ . '/../SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
    foreach (array_slice($cards, 0, $top, true) as $c => $n) {
        $title = function_exists('CardTitle') && preg_match('/^[A-Z0-9]+_/', $c) ? strval(CardTitle($c) ?? '') : '';
        printf("  %5d  %s%s\n", $n, $c, $title !== '' ? "  $title" : '');
    }
}
exit(($errors || $throws || $mismatched || $invalid) ? 1 : 0);
