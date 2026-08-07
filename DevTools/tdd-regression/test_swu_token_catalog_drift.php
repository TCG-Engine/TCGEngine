<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swustats-web-server-1 \
//     php -d xdebug.mode=off DevTools/tdd-regression/test_swu_token_catalog_drift.php
//
// AppCore/SWU/Tokens.php is the SINGLE authority on which tokens exist. This pins it against the
// card data so a new set's token fails HERE, by name, instead of the "Needs Tokens" line silently
// under-reporting forever. Same single-authority-plus-drift-guard shape as AppCore/SWU/Formats.php,
// which exists because a hand-maintained list with no guard is exactly how the format lists drifted.
//
// ⚠ Loads SWUSim's dictionary ONLY. SWUDeck's dictionary declares the same function names, so
// requiring both is an instant fatal. SWUSim is the right side here because it is the one whose
// GetAllCardIds() includes token cards.
//
// READ-ONLY: no database, no POST.
//
// Design: docs/superpowers/specs/2026-08-06-swudeck-token-requirements-design.md
header('Content-Type: text/plain');
$root = dirname(__DIR__, 2);
require_once $root . '/SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once $root . '/AppCore/SWU/Tokens.php';

$checks = [];
$catalog = SWUTokenCatalog();

// Distinct token TITLES in the card data. The same token is reprinted per set (Shield is SOR_T02,
// SHD_T02, JTL_T04, …), so 27 ids collapse to 13 names.
$actual = [];
foreach (preg_grep('/_T\d\d$/', GetAllCardIds()) as $id) $actual[(string)CardTitle($id)] = true;
$actual = array_keys($actual);
sort($actual);

$declared = array_keys($catalog);
$sortedDeclared = $declared; sort($sortedDeclared);

$missing = array_values(array_diff($actual, $declared));   // in data, not declared -> under-reports
$extra   = array_values(array_diff($declared, $actual));   // declared, not in data -> stale entry

$checks['catalog has 13 tokens']         = count($declared) === 13;
$checks['no token missing from catalog'] = $missing === [];
$checks['no stale catalog entry']        = $extra === [];
$checks['catalog matches the card data'] = $sortedDeclared === $actual;

// Every entry is well-formed and its sample id is a real token card.
$badCategory = $badSample = [];
foreach ($catalog as $name => $meta) {
    if (!in_array($meta['category'] ?? '', ['upgrade', 'unit', 'resource', 'force'], true)) $badCategory[] = $name;
    $s = $meta['sample'] ?? '';
    if ($s === '' || CardTitle($s) !== $name) $badSample[] = $name . '->' . $s;
}
$checks['every category is valid']  = $badCategory === [];
$checks['every sample id resolves'] = $badSample === [];

// Output order is a CONTRACT — the UI prints the catalog in declaration order, and the tests in
// test_swu_token_requirements.php assert exact arrays.
$checks['order: upgrades first'] = array_slice($declared, 0, 4) === ['Shield', 'Experience', 'Advantage', 'Weakness'];
$checks['order: Credit then The Force last'] = array_slice($declared, -2) === ['Credit', 'The Force'];
$units = array_slice($declared, 4, count($declared) - 6);
$sortedUnits = $units; sort($sortedUnits);
$checks['order: units alphabetical'] = $units === $sortedUnits;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
if ($fails) {
    echo "FAIL (" . count($fails) . "/" . count($checks) . "):\n";
    foreach ($fails as $f) echo "  - $f\n";
    if ($missing)     echo "  MISSING from catalog (add them): " . implode(', ', $missing) . "\n";
    if ($extra)       echo "  STALE in catalog (remove them): " . implode(', ', $extra) . "\n";
    if ($badCategory) echo "  bad category: " . implode(', ', $badCategory) . "\n";
    if ($badSample)   echo "  bad sample id: " . implode(', ', $badSample) . "\n";
    echo "  declared order: " . implode(' | ', $declared) . "\n";
} else {
    echo "PASS (" . count($checks) . " checks)\n";
}
