<?php
/**
 * Advisory inventory for GrandArchiveSim's hand-authored semantic regression contracts.
 *
 * Usage: php DevTools/audit-ga-semantic-coverage.php [--root=GrandArchiveSim] [--implemented-cards-json=file] [--strict] [--verbose]
 */
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

$repoRoot = dirname(__DIR__);
$rootName = 'GrandArchiveSim';
$strict = false;
$verbose = false;
$implementedCardsPath = null;
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--root=')) $rootName = substr($arg, 7);
    elseif (str_starts_with($arg, '--implemented-cards-json=')) $implementedCardsPath = substr($arg, 25);
    elseif ($arg === '--strict') $strict = true;
    elseif ($arg === '--verbose') $verbose = true;
}

require_once __DIR__ . '/GaSemanticCoverage.php';

$fixtureRoot = $repoRoot . '/Tests/Integration/' . $rootName;
if (!is_dir($fixtureRoot)) {
    fwrite(STDERR, "Fixture directory not found: {$fixtureRoot}\n");
    exit(1);
}

$totals = ['fixtures' => 0, 'legacy' => 0, 'complete' => 0, 'incomplete' => 0, 'semanticAssertions' => 0];
$cards = [];
foreach (scandir($fixtureRoot) as $slug) {
    if ($slug === '.' || $slug === '..' || !is_dir($fixtureRoot . '/' . $slug)) continue;
    $metaPath = $fixtureRoot . '/' . $slug . '/meta.json';
    $assertionsPath = $fixtureRoot . '/' . $slug . '/assertions.json';
    $actionsPath = $fixtureRoot . '/' . $slug . '/actions.json';
    if (!is_file($assertionsPath)) continue; // not a real fixture dir
    $meta = is_file($metaPath) ? json_decode(file_get_contents($metaPath), true) : [];
    $assertions = json_decode(file_get_contents($assertionsPath), true);
    $actions = is_file($actionsPath) ? json_decode(file_get_contents($actionsPath), true) : [];
    if (!is_array($meta)) $meta = [];
    if (!is_array($assertions)) $assertions = [];
    if (!is_array($actions)) $actions = [];
    $totals['fixtures']++;

    $contract = GaSemanticContract($meta);
    if ($contract === null) {
        $totals['legacy']++;
        continue;
    }
    $semantic = GaSemanticAssertions($assertions, $actions);
    $testedCards = GaResolveTestedCards($meta);
    $mechanics = $contract['mechanics'] ?? [];
    $clauses = $contract['rulesClauses'] ?? [];
    $complete = GaSemanticContractIsComplete($meta, $assertions, $actions);
    if (!$complete) {
        echo "[INCOMPLETE] {$slug}\n";
        $totals['incomplete']++;
        continue;
    }
    $totals['complete']++;
    $totals['semanticAssertions'] += count($semantic);
    foreach ($testedCards as $cardId) {
        $cardId = strval($cardId);
        if ($cardId === '') continue;
        $cards[$cardId]['fixtures'][] = $slug;
        foreach ($mechanics as $mechanic) $cards[$cardId]['mechanics'][strval($mechanic)] = true;
    }
}

echo "Fixtures: {$totals['fixtures']} | Complete semantic contracts: {$totals['complete']} | Incomplete: {$totals['incomplete']} | Legacy: {$totals['legacy']} | Semantic assertions: {$totals['semanticAssertions']}\n";
echo 'Covered cards: ' . count($cards) . "\n";
if ($verbose) {
    foreach ($cards as $cardId => $coverage) {
        echo "[COVERED] {$cardId}: " . implode(', ', array_keys($coverage['mechanics'])) . ' via ' . implode(', ', $coverage['fixtures']) . "\n";
    }
}

// The card_abilities table is deliberately not queried here: CI often has no
// authoring database. Supply a checked-in/exported JSON list when available;
// both ["id"] and [{"cardId":"id"}] formats are accepted.
$missingImplemented = [];
if ($implementedCardsPath !== null) {
    if (!is_file($implementedCardsPath)) {
        fwrite(STDERR, "Implemented-card JSON not found: {$implementedCardsPath}\n");
        exit(1);
    }
    $implemented = json_decode(file_get_contents($implementedCardsPath), true);
    if (!is_array($implemented)) {
        fwrite(STDERR, "Implemented-card JSON must be an array.\n");
        exit(1);
    }
    // The exporter writes an envelope for provenance; retain support for a
    // bare array so ad-hoc CI exports remain usable.
    if (isset($implemented['cards']) && is_array($implemented['cards'])) $implemented = $implemented['cards'];
    foreach ($implemented as $entry) {
        $cardId = is_array($entry) ? strval($entry['cardId'] ?? $entry['card_id'] ?? '') : strval($entry);
        if ($cardId !== '' && !isset($cards[$cardId])) $missingImplemented[] = $cardId;
    }
    sort($missingImplemented);
    echo 'Implemented cards without a complete semantic contract: ' . count($missingImplemented) . "\n";
    if ($verbose) {
        foreach ($missingImplemented as $cardId) echo "[MISSING] {$cardId}\n";
    } elseif (!empty($missingImplemented)) {
        echo "Run with --verbose to print the full card-ID backlog.\n";
    }
}

exit($strict && ($totals['incomplete'] > 0 || !empty($missingImplemented)) ? 1 : 0);
