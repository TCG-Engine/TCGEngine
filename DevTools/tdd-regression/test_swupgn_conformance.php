<?php
// SWU-PGN/1.0 spec §20 conformance: every negative vector of the §20 table, hand-written under
// fixtures/swupgn/conformance/ (one change each from minimal.swupgn, which is Appendix A by another writer), plus
// positive files. Pass criteria (§20): Parser — every positive parses, every malformed negative is rejected with its
// code. Validator — the level and the error / warning code SETS equal the manifest.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_conformance.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';

$dir = __DIR__ . '/fixtures/swupgn/conformance';
$manifest = json_decode((string)file_get_contents("$dir/manifest.json"), true);
$vectors = $manifest['vectors'] ?? [];

// §20's negative-vector table, row by row: every one must be in the manifest.
$table = ['not-swupgn', 'bad-escape', 'duplicate-section', 'rounds-not-digits', 'bad-recorder-errors', 'unsupported-major', 'duplicate-key',
    'bad-move', 'bad-token-name', 'credit-zone', 'deck-build-move', 'fortify-enemy-base', 'token-to-hand', 'round-start-no-active',
    'duplicate-seq', 'result-mismatch', 'dangling-ref', 'proto-token', 'deck-reserved-key', 'to-string-key', 'reserved-token-name',
    'nested-reserved-token', 'reserved-tag-name', 'newer-minor-bad-seat', 'newer-minor-result-seat', 'over-cap', 'effect-end-unknown',
    'pre-release', 'damaged-keyframe', 'unknown-type', 'unknown-section', 'higher-minor', 'newer-minor-result'];
$negatives = array_map(fn($v) => basename($v['file'], '.swupgn'), array_filter($vectors, fn($v) => $v['class'] === 'negative'));
SwuPgnTestEq(array_values(array_diff($table, $negatives)), [], '§20: every row of the negative-vector table has a vector (' . count($table) . ' rows)');

$codes = function (array $issues): array { $c = array_values(array_unique(array_column($issues, 'code'))); sort($c); return $c; };
foreach ($vectors as $vec) {
    $name = $vec['file'];
    $text = (string)file_get_contents("$dir/$name");
    $opts = isset($vec['pilotProducts']) ? ['pilotProducts' => $vec['pilotProducts']] : [];
    $r = SwuPgnValidateText($text, $opts);
    SwuPgnTestEq([$r['level'], $codes($r['errors']), $codes($r['warnings'])], [$vec['level'], $vec['errors'], $vec['warnings']],
        "$name ({$vec['about']}): {$vec['level']}" . ($vec['errors'] ? ' errors ' . implode(',', $vec['errors']) : '') . ($vec['warnings'] ? ' warnings ' . implode(',', $vec['warnings']) : ''));
    $p = SwuPgnTryParse($text);
    if ($vec['level'] === 'malformed') SwuPgnTestEq($p['code'], $vec['errors'][0], "$name: the parser rejects it with {$vec['errors'][0]} ([P-01])");
    else SwuPgnTestCheck($p['doc'] !== null, "$name: the parser accepts it ([P-01])");
    // Any result must be consistent with §24: ok ⇔ no errors; the level is the lowest any issue allows.
    $caps = SwuPgnIssueCodes();
    $min = 4;
    foreach (array_merge($r['errors'], $r['warnings']) as $i) if ($caps[$i['code']][1] !== null) $min = min($min, array_search($caps[$i['code']][1], SwuPgnLevels(), true));
    SwuPgnTestCheck($r['ok'] === !$r['errors'] && $r['level'] === SwuPgnLevels()[$min], "$name: ok and level follow §24's \"Level at most\"");
}

// The pre-release vector is pre-release only by the registry: the shipped (empty) §25 constant lists no product.
SwuPgnTestEq(SwuPgnValidateText((string)file_get_contents("$dir/pre-release.swupgn"))['level'], 'complete', 'pre-release.swupgn with the shipped empty registry is an ordinary complete file');

SwuPgnTestFinish();
