<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swu_shared_mock_source.php
//
// The mock (preview card) module is SHARED data, not SWUSim's private data: SWUDeck's dictionary
// needs the same entries so preview cards can be deckbuilt. This guards its home and its names so
// it cannot drift back inside one app.
//
// Design: docs/superpowers/specs/2026-08-04-swu-shared-card-universe-design.md §1, §3
header('Content-Type: text/plain');

$appCore = __DIR__ . '/../../AppCore/SWU/';
$checks = [];

// ── The module lives in AppCore/SWU ──────────────────────────────────────────
$checks['CardMocks.php is in AppCore/SWU']      = file_exists($appCore . 'CardMocks.php');
$checks['MockCardMerge.php is in AppCore/SWU']  = file_exists($appCore . 'MockCardMerge.php');
$checks['MockCardWriter.php is in AppCore/SWU'] = file_exists($appCore . 'MockCardWriter.php');

// ── and nowhere else ─────────────────────────────────────────────────────────
$checks['no CardMocks.php left in SWUSim/Custom']       = !file_exists(__DIR__ . '/../../SWUSim/Custom/CardMocks.php');
$checks['no MockCardMerge.php left in SWUSim/DevTools'] = !file_exists(__DIR__ . '/../../SWUSim/DevTools/MockCardMerge.php');
$checks['no MockCardWriter.php left in SWUSim/DevTools']= !file_exists(__DIR__ . '/../../SWUSim/DevTools/MockCardWriter.php');

// ── The functions are app-neutral ────────────────────────────────────────────
if ($checks['MockCardMerge.php is in AppCore/SWU'])  require_once $appCore . 'MockCardMerge.php';
if ($checks['MockCardWriter.php is in AppCore/SWU']) require_once $appCore . 'MockCardWriter.php';

foreach ([
    'SWUMockCardsPath', 'SWUDoubleDigitSets', 'SWUIsMockCardID', 'SWULoadMockCards',
    'SWUMockToImportRow', 'SWUMockIsSuperseded', 'SWUMergeMockCards',
    'SWUWriteMockCard', 'SWUDeleteMockCard', 'SWUWriteReprintOverride',
    '_SWURenderMockFile',   // private helper, hence the underscore
] as $fn) {
    $checks["$fn() exists"] = function_exists($fn);
}

// No SWUSim-prefixed survivors anywhere in the repo's PHP.
$stragglers = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../../'));
foreach ($rii as $f) {
    if ($f->isDir() || substr($f->getFilename(), -4) !== '.php') continue;
    $p = $f->getPathname();
    if (strpos($p, '/node_modules/') !== false || strpos($p, '/vendor/') !== false) continue;
    $src = file_get_contents($p);
    if (preg_match('/SWUSim(MockCardsPath|DoubleDigitSets|IsMockCardID|LoadMockCards|MockToImportRow|MockIsSuperseded|MergeMockCards|WriteMockCard|DeleteMockCard|RenderMockFile|WriteReprintOverride)/', $src)) {
        $stragglers[] = str_replace(__DIR__ . '/../../', '', $p);
    }
}
$checks['no SWUSim*-prefixed mock calls remain'] = count($stragglers) === 0;
if ($stragglers) echo "STRAGGLERS: " . implode(', ', $stragglers) . "\n";

// ── The mock data itself survived the move intact ────────────────────────────
if ($checks['CardMocks.php is in AppCore/SWU']) {
    $mocks = require $appCore . 'CardMocks.php';
    $checks['mock data is a non-empty array'] = is_array($mocks) && count($mocks) > 0;
    $checks['SWUMockCardsPath points at the moved file'] =
        function_exists('SWUMockCardsPath') && realpath(SWUMockCardsPath()) === realpath($appCore . 'CardMocks.php');
}

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
