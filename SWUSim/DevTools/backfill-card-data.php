<?php
// backfill-card-data.php [--set=HMW] [--type=Base] [--field=trait] [--dry]
//
// Fills AppCore/SWU/CardDataSupplement.php with values the official FFG API omits, from SWUDB.
// Reads a mocks=0 generator snapshot (the official+supplement view), fetches each candidate card
// once, and writes/refreshes `swudb` entries. `manual` entries are never touched. Nothing queries
// SWUDB during a normal regen — the result is tracked source.
//
// Exit 1 if any card failed to fetch or mismatched its title (a blocked run must not look like
// "nothing to fill").
//
//   docker exec -e DEVENV=true -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
//     php -d xdebug.mode=off SWUSim/DevTools/backfill-card-data.php --set=HMW --dry
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

require_once __DIR__ . '/PreviewImport.php';
require_once __DIR__ . '/../../AppCore/SWU/CardDataSupplementApply.php';
require_once __DIR__ . '/../../AppCore/SWU/CardDataSnapshot.php';
require_once __DIR__ . '/CardDataBackfill.php';

$opts = [];
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry') { $opts['dry'] = true; continue; }
    if (preg_match('/^--(\w+)=(.*)$/', $arg, $m)) $opts[$m[1]] = $m[2];
}
$dry = !empty($opts['dry']);

$fieldMap = SWUBackfillFieldMap();
if (isset($opts['field'])) {
    if (!isset($fieldMap[$opts['field']])) {
        fwrite(STDERR, "Unknown --field=" . $opts['field'] . " (allow-listed: " . implode(', ', array_keys($fieldMap)) . ")\n");
        exit(2);
    }
    $fieldMap = [$opts['field'] => $fieldMap[$opts['field']]];
}

echo "Taking official-only snapshot (mocks=0)...\n";
try {
    $snapshot = SWUTakeCardDataSnapshot('0');
} catch (RuntimeException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

$plan = SWUPlanCardDataBackfill($snapshot, SWULoadCardDataSupplement(), 'SWUPreviewFetchCard', $fieldMap,
                                ['set' => $opts['set'] ?? '', 'type' => $opts['type'] ?? '']);

foreach ($plan['rows'] as $r) {
    $title = (string)($snapshot['dictionaries']['title'][$r['cardID']] ?? '');
    $extra = $r['status'] === 'changed' ? ' (was "' . $r['prior'] . '")' : '';
    printf("  %-10s %-32s %-16s %-14s %s%s\n", $r['cardID'], $title, $r['field'], $r['status'], $r['value'], $extra);
}
$c = $plan['counts'];
echo str_repeat('-', 72) . "\n";
printf("added %d | changed %d | unchanged %d | source-empty %d | fetch-failed %d | title-mismatch %d | token-skipped %d\n",
       $c['added'], $c['changed'], $c['unchanged'], $c['sourceEmpty'], $c['failed'], $c['titleMismatch'], $c['tokenSkipped']);

if ($dry) {
    echo "Dry run — nothing written.\n";
} else if ($c['added'] + $c['changed'] > 0) {
    if (!SWUWriteCardDataSupplement($plan['supplement'])) { fwrite(STDERR, "ERROR: could not write supplement\n"); exit(1); }
    echo "Wrote " . SWUCardDataSupplementPath() . "\nNow regenerate: php zzCardCodeGenerator.php rootName=SWUSim (and rootName=SWUDeck)\n";
} else {
    echo "Nothing to write.\n";
}
exit(($c['failed'] + $c['titleMismatch']) > 0 ? 1 : 0);
