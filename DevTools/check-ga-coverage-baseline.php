<?php
/**
 * Guard rail: the cards that have no semantic fixture must be exactly the ones listed in DevTools/ga-fixtures/uncovered-baseline.txt
 * (and explained in DevTools/ga-fixtures/UNFIXABLE.md). A newly implemented card without a fixture fails here; so does a baseline card that
 * got a fixture (remove it from the baseline and from UNFIXABLE.md).
 *
 * Usage: php DevTools/check-ga-coverage-baseline.php      (rebuilds the backlog first)
 */
$repoRoot = dirname(__DIR__);
passthru('php ' . escapeshellarg($repoRoot . '/DevTools/build-ga-semantic-backlog.php') . ' > /dev/null', $buildStatus);
if ($buildStatus !== 0) { fwrite(STDERR, "build-ga-semantic-backlog.php failed\n"); exit(1); }

$backlog = json_decode(file_get_contents($repoRoot . '/Tests/Integration/GrandArchiveSim/semantic-coverage-backlog.json'), true);
$uncovered = [];
foreach ($backlog['cards'] ?? [] as $card) if (!empty($card['needsSemanticCoverage'])) $uncovered[] = $card['name'] . ' (' . $card['cardId'] . ')';
sort($uncovered);

$baselineFile = $repoRoot . '/DevTools/ga-fixtures/uncovered-baseline.txt';
$baseline = is_file($baselineFile) ? array_values(array_filter(array_map('trim', file($baselineFile)), fn($line) => $line !== '' && $line[0] !== '#')) : [];
sort($baseline);

$new = array_values(array_diff($uncovered, $baseline));
$fixed = array_values(array_diff($baseline, $uncovered));
foreach ($new as $name) echo "NEW uncovered card: $name -- write a fixture, or list it in uncovered-baseline.txt and UNFIXABLE.md with the reason\n";
foreach ($fixed as $name) echo "NOW covered: $name -- remove it from uncovered-baseline.txt and UNFIXABLE.md\n";
echo count($uncovered) . " cards without a semantic fixture; baseline lists " . count($baseline) . "\n";
exit(empty($new) && empty($fixed) ? 0 : 1);
