<?php
// SWUSim/Rl/tag-weights.md is the human-readable source of truth for the card tags and their weights (owner,
// 2026-10-01). This keeps it and the code from drifting apart: the header is the owner's, every row matches
// SWUBotWeightTable() (BotArchetypes.php), every tag the tagger emits is listed, and nothing stale is.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_tagweights_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotHeuristic.php';

$md = (string)@file_get_contents('./SWUSim/Rl/tag-weights.md');
$check($md !== '', 'SWUSim/Rl/tag-weights.md exists');

// The owner's columns, exactly, in this order.
$check((bool)preg_match('/^\| tag \| hyperaggro \| softaggro \| midrange \| softcontrol \| hardcontrol \|$/m', $md),
    'header is "tag, hyperaggro, softaggro, midrange, softcontrol, hardcontrol"');
$check(SWU_BOT_ARCHETYPES === ['hyperaggro', 'softaggro', 'midrange', 'softcontrol', 'hardcontrol'],
    'the code\'s column order is the same five styles in the same order');

$rows = [];
preg_match_all('/^\| `([a-z0-9-]+)` \| (-?\d+\.\d+) \| (-?\d+\.\d+) \| (-?\d+\.\d+) \| (-?\d+\.\d+) \| (-?\d+\.\d+) \|$/m', $md, $m, PREG_SET_ORDER);
foreach ($m as $r) $rows[$r[1]] = array_map('floatval', array_slice($r, 2, 5));
$check(count($rows) === count(array_unique(array_column($m, 1))), 'no tag is listed twice');

// Every tag the tagger emits (v3 table, both faces) must have a row — a new tag cannot ship undocumented.
$emitted = [];
foreach ($GLOBALS['SWUBotCardTagTable'] as $faces) foreach ($faces as $tags) foreach ($tags as $t) $emitted[$t] = true;
$missing = array_values(array_diff(array_keys($emitted), array_keys($rows)));
$stale   = array_values(array_diff(array_keys($rows), array_keys($emitted)));
$check(empty($missing), 'every emitted tag has a row: missing ' . json_encode(array_slice($missing, 0, 6)));
$check(empty($stale), 'no row for a tag the tagger no longer emits: ' . json_encode(array_slice($stale, 0, 6)));

// Every row equals the code's printed table (a tag with no weight row in code is 0.00 everywhere).
$T = SWUBotWeightTable();
$diff = [];
foreach ($rows as $t => $vals) {
    $code = $T[$t] ?? [0.0, 0.0, 0.0, 0.0, 0.0];
    foreach ($vals as $i => $v) if (abs($v - floatval($code[$i])) > 1e-9) { $diff[] = $t . '[' . SWU_BOT_ARCHETYPES[$i] . "] md=$v code={$code[$i]}"; break; }
}
$check(empty($diff), 'every row matches SWUBotWeightTable(): ' . json_encode(array_slice($diff, 0, 4)));

// And no WEIGHTED tag in code is missing from the doc (a weight row for a tag the tagger emits).
$undocumented = array_values(array_filter(array_keys($T), fn($k) => isset($emitted[$k]) && !isset($rows[$k])));
$check(empty($undocumented), 'every weighted tag in the code is documented: ' . json_encode($undocumented));
$check(!isset($rows['damage']) && !isset($rows['burn']), 'the retired `damage` and `burn` are not listed');

bot_test_finish();
