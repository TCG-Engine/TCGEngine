<?php
// flip-audit.php --set=HMW [--accept-gap=HMW_178.subtitle ...] [--json=<path>]
//
// Before deleting a set's mocks from AppCore/SWU/CardMocks.php: compares each mock's own values
// (mocks=prefer snapshot) with the official + supplement values (mocks=0 snapshot).
//   BLOCK no-official — FFG has no record yet
//   BLOCK gap         — the mock has a value that official + supplement leave blank
//                       (fix: add a supplement entry, or --accept-gap if the mock was wrong)
//   REVIEW            — both have values that differ (official wins; text/deployText listed first)
//   NEW               — official cards in the set that were never mocked (unimplemented)
// Prints the safe-to-delete list; deletes NOTHING. Exit 1 while any BLOCK exists.
//
//   docker exec -e DEVENV=true -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
//     php -d xdebug.mode=off SWUSim/DevTools/flip-audit.php --set=HMW
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../AppCore/SWU/MockCardMerge.php';
require_once __DIR__ . '/../../AppCore/SWU/CardDataSupplementApply.php';
require_once __DIR__ . '/../../AppCore/SWU/CardDataSnapshot.php';
require_once __DIR__ . '/FlipAudit.php';

$set = ''; $accepted = []; $jsonPath = '';
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--set=(.+)$/', $arg, $m)) $set = strtoupper($m[1]);
    else if (preg_match('/^--accept-gap=(.+)$/', $arg, $m)) $accepted[] = $m[1];
    else if (preg_match('/^--json=(.+)$/', $arg, $m)) $jsonPath = $m[1];
}
if ($set === '') { fwrite(STDERR, "Usage: flip-audit.php --set=HMW [--accept-gap=CARDID.field ...] [--json=path]\n"); exit(2); }

try {
    echo "Taking mock-side snapshot (mocks=prefer)...\n";
    $prefer = SWUTakeCardDataSnapshot('prefer');
    echo "Taking official-side snapshot (mocks=0)...\n";
    $official = SWUTakeCardDataSnapshot('0');
} catch (RuntimeException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

$a = SWUFlipAudit($prefer, $official, array_keys(SWULoadMockCards()), $set, $accepted);
$title = fn(string $id) => (string)($official['dictionaries']['title'][$id] ?? $prefer['dictionaries']['title'][$id] ?? '');
$show = fn($v) => str_replace("\n", "\\n", is_scalar($v) || $v === null ? var_export($v, true) : json_encode($v));

printf("\n== BLOCK: no official record (%d)\n", count($a['blockNoOfficial']));
foreach ($a['blockNoOfficial'] as $id) printf("  %-10s %s\n", $id, $title($id));
printf("\n== BLOCK: gap — mock value, official+supplement blank (%d)\n", count($a['blockGap']));
foreach ($a['blockGap'] as $g) printf("  %-10s %-28s %-20s mock=%s\n", $g['cardID'], $title($g['cardID']), $g['field'], $show($g['mock']));
printf("\n== REVIEW: values differ, official wins (%d)\n", count($a['review']));
foreach ($a['review'] as $r) printf("  %-10s %-28s %-20s\n      mock:     %s\n      official: %s\n", $r['cardID'], $title($r['cardID']), $r['field'], $show($r['mock']), $show($r['official']));
printf("\n== accepted gaps (%d)\n", count($a['acceptedGap']));
foreach ($a['acceptedGap'] as $g) printf("  %-10s %-20s mock=%s\n", $g['cardID'], $g['field'], $show($g['mock']));
printf("\n== info: official-only values (%d)\n", count($a['officialOnly']));
printf("\n== NEW: official cards never mocked (%d)\n", count($a['new']));
foreach ($a['new'] as $id) printf("  %-10s %s\n", $id, $title($id));
printf("\n== safe to delete (%d of %d mocks in %s)\n%s\n", count($a['safeToDelete']),
       count(array_filter(array_keys(SWULoadMockCards()), fn($id) => strtoupper(explode('_', $id)[0]) === $set)), $set,
       implode(' ', $a['safeToDelete']));

if ($jsonPath !== '') file_put_contents($jsonPath, json_encode($a, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE));
$blocks = count($a['blockNoOfficial']) + count($a['blockGap']);
echo "\n" . ($blocks > 0 ? "BLOCKED: $blocks item(s)." : "No blocks.") . "\n";
exit($blocks > 0 ? 1 : 0);
