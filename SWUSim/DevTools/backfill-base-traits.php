<?php
// backfill-base-traits.php [--dry] [--type=Base] [--set=JTL]
//
// Fills AppCore/SWU/CardTraitSupplement.php with the traits the upstream card API omits.
// The API returns NO traits for bases (all 91), though every base prints one — see
// AppCore/SWU/TraitSupplement.php for why this file exists.
//
// Walks every card of the target type whose trait list is EMPTY in the generated dictionaries,
// looks each up through the PreviewImport client, and writes the results as tracked source. The
// result is static data: nothing queries that API at runtime or during a normal regen.
//
// ADDITIVE + IDEMPOTENT: existing entries are kept unless the fetch returns something different,
// and cards that already have official traits are skipped entirely.
//
//   docker compose exec -T -w /var/www/html/TCGEngine swusim-web-server \
//     php -d xdebug.mode=off SWUSim/DevTools/backfill-base-traits.php --dry
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

require_once __DIR__ . '/../GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/PreviewImport.php';
require_once __DIR__ . '/../../AppCore/SWU/TraitSupplement.php';

$argvOpts = [];
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry') { $argvOpts['dry'] = true; continue; }
    if (preg_match('/^--(\w+)=(.*)$/', $arg, $m)) $argvOpts[$m[1]] = $m[2];
}
$dry        = !empty($argvOpts['dry']);
$targetType = $argvOpts['type'] ?? 'Base';
$targetSet  = strtoupper((string)($argvOpts['set'] ?? ''));

global $typeData, $traitData;

// Candidates: right type, EMPTY traits, and not already supplemented with the same value.
$existing = SWULoadTraitSupplement();
$candidates = [];
foreach ($typeData as $cardID => $type) {
    if (strpos((string)$type, $targetType) === false) continue;
    if ($targetSet !== '' && strtoupper(explode('_', $cardID)[0]) !== $targetSet) continue;
    if (trim((string)($traitData[$cardID] ?? '')) !== '') continue;   // official data present
    $candidates[] = $cardID;
}
sort($candidates);

echo "Backfill target: type=$targetType" . ($targetSet !== '' ? " set=$targetSet" : '') . "\n";
echo count($candidates) . " card(s) with empty traits; " . count($existing) . " already supplemented.\n";
if ($dry) echo "(--dry: nothing will be written)\n";
echo str_repeat('-', 64) . "\n";

$resolved = $existing;
$added = $changed = $missed = $skipped = 0;

foreach ($candidates as $cardID) {
    $parts = explode('_', $cardID);
    if (count($parts) !== 2) { $missed++; continue; }
    [$set, $num] = $parts;

    $rec = SWUPreviewFetchCard($set, $num);
    if ($rec === null || ($rec['cardName'] ?? '') === '') {
        printf("  %-10s %-30s NO DATA\n", $cardID, (string)(CardTitle($cardID) ?? ''));
        $missed++;
        continue;
    }

    // Sanity: the fetched card must be the card we think it is, or the supplement would attach
    // one card's traits to another (a silent, hard-to-spot corruption).
    $fetchedTitle = trim((string)($rec['cardName'] ?? ''));
    $localTitle   = trim((string)(CardTitle($cardID) ?? ''));
    if ($fetchedTitle !== '' && $localTitle !== '' && strcasecmp($fetchedTitle, $localTitle) !== 0) {
        printf("  %-10s %-30s TITLE MISMATCH (source says \"%s\") — skipped\n",
               $cardID, $localTitle, $fetchedTitle);
        $skipped++;
        continue;
    }

    $traits = SWUNormalizeTraitString($rec['traits'] ?? []);
    if ($traits === '') {
        printf("  %-10s %-30s no traits at source\n", $cardID, $localTitle);
        $missed++;
        continue;
    }

    $prior = $resolved[$cardID] ?? null;
    $resolved[$cardID] = $traits;
    if ($prior === null)            { $added++;   $flag = 'NEW'; }
    elseif ($prior !== $traits)     { $changed++; $flag = 'CHANGED from "' . $prior . '"'; }
    else                            { $flag = 'unchanged'; }
    printf("  %-10s %-30s %-22s %s\n", $cardID, $localTitle, $traits, $flag);
}

echo str_repeat('-', 64) . "\n";
printf("added %d | changed %d | no data %d | skipped %d | total in file %d\n",
       $added, $changed, $missed, $skipped, count($resolved));

if ($dry) { echo "Dry run — no write.\n"; exit(0); }
if ($added === 0 && $changed === 0) { echo "Nothing to write.\n"; exit(0); }

ksort($resolved);
$header = <<<'PHP'
<?php
// Traits the upstream card API OMITS, supplied from tracked source.
//
// The API publishes no traits for bases (every one comes back empty) though each prints one —
// JTL_030 Mos Eisley is "Tatooine", SOR_024 Echo Base is "Hoth". Applied at generation time by
// AppCore/SWU/TraitSupplement.php, FILL-GAPS ONLY: official data always wins, so this file
// goes inert on its own if the API ever starts publishing them.
//
// Generated/extended by: php SWUSim/DevTools/backfill-base-traits.php [--dry]
// Hand-editing is fine — the backfill preserves entries it can't improve.
PHP;
$body = "\nreturn " . var_export($resolved, true) . ";\n";
$path = SWUTraitSupplementPath();
if (file_put_contents($path, $header . $body) === false) {
    fwrite(STDERR, "ERROR: could not write $path\n");
    exit(1);
}
echo "Wrote " . count($resolved) . " entries to $path\n";
echo "Now regenerate: php zzCardCodeGenerator.php rootName=SWUSim\n";
