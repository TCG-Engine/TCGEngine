<?php
// split-cards.php <SET> [--dry]
//
// Moves a set's per-card ability registrations out of the Custom/ monoliths into
// Custom/cards/<set>/Title_Subtitle.php files (reprints consolidated into the base
// printing's file), rewrites the monoliths with those statements removed, writes
// the CardID→path index, and VERIFIES that the populated (array,key) registration
// set is byte-identical before vs after. On any mismatch it auto-reverts.
//
//   --dry : compute + write card files to a temp dir and lint them, but DO NOT
//           touch the real monoliths. Prints the plan summary. Safe preview.
//
// Run inside the swusim web container:
//   docker exec -w /var/www/html/TCGEngine <container> \
//     php -d xdebug.mode=off SWUSim/DevTools/split-cards.php SOR [--dry]

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

$repo = getenv('REPO_ROOT') ?: (function () {
    $d = __DIR__;
    while ($d !== '/' && $d !== '' && !(is_dir("$d/SWUSim") && is_dir("$d/Core"))) $d = dirname($d);
    return $d;
})();
chdir($repo);

require __DIR__ . '/../GeneratedCode/GeneratedCardDictionaries.php';
require $repo . '/AppCore/SWU/Overrides.php';
require $repo . '/AppCore/SWU/DeckValidation.php';
require __DIR__ . '/CardFileSplitter/HeaderGen.php';
require __DIR__ . '/CardFileSplitter/Scanner.php';
require __DIR__ . '/CardFileSplitter/Router.php';
require __DIR__ . '/CardFileSplitter/Emitter.php';
require __DIR__ . '/CardFileSplitter/Verify.php';

$set = strtoupper($argv[1] ?? '');
$dry = in_array('--dry', $argv, true);
if ($set === '') { fwrite(STDERR, "usage: split-cards.php <SET> [--dry]\n"); exit(2); }

$monoliths = [
    'SWUSim/Custom/CardDQHandlers.php',
    'SWUSim/Custom/LeaderAbilities.php',
    'SWUSim/Custom/BaseAbilities.php',
];
$cardsDir  = 'SWUSim/Custom/cards';
$indexPath = "$cardsDir/_index.generated.php";
$snapHelper = 'SWUSim/DevTools/CardFileSplitter/snapshot_keys.php';

$testMap = splitter_build_testname_map('SWUSim/Tests/Cases');

// ── 0. Pinned keys: registration entries read BY VALUE at include time anywhere
// across the monoliths. Their definitions must stay put (load-order safety). ──
$allStmts = [];
foreach ($monoliths as $m) $allStmts = array_merge($allStmts, splitter_scan(file_get_contents($m)));
$pinned = splitter_pinned_keys($allStmts);

// ── 1. Compute the merged plan across all monoliths (statement-level merge) ──
$merged = [];       // base => ['set','basename','reprints'=>[],'statements'=>[]]
$index  = [];
$left   = [];
$remaining = [];    // monolith path => rewritten source
$movedCount = 0;

foreach ($monoliths as $m) {
    $src  = file_get_contents($m);
    $plan = splitter_emit_plan($src, $set, $testMap, $pinned);
    $remaining[$m] = $plan['remaining'];
    foreach ($plan['files'] as $base => $f) {
        if (!isset($merged[$base])) {
            $merged[$base] = ['set'=>$f['set'], 'basename'=>$f['basename'], 'reprints'=>[], 'statements'=>[]];
        }
        foreach ($f['statements'] as $st) { $merged[$base]['statements'][] = $st; $movedCount++; }
        foreach ($f['reprints'] as $rp) if (!in_array($rp, $merged[$base]['reprints'], true)) $merged[$base]['reprints'][] = $rp;
    }
    $index += $plan['index'];
    foreach ($plan['left'] as $l) $left[] = $l;
}

if (empty($merged)) { fwrite(STDERR, "No movable cards found for set $set.\n"); exit(1); }

// Render final file contents. Guard against two base cards resolving to the same
// path (would silently overwrite and drop registrations): disambiguate with the
// CardID and log it.
$fileContents = []; // relpath => content
$claimed = [];      // relpath => base card that claimed it
$collisions = [];
foreach ($merged as $base => $f) {
    $rel = "{$f['set']}/{$f['basename']}.php";
    if (isset($claimed[$rel])) {
        $prev = $claimed[$rel];
        $rel = "{$f['set']}/{$f['basename']}__{$base}.php";
        $collisions[] = "{$f['basename']}.php claimed by $prev and $base — $base renamed to $rel";
    }
    $claimed[$rel] = $base;
    $fileContents[$rel] = "<?php\n" . splitter_render_card($base, $f['reprints'], $f['statements']);
    // keep the index pointing at the actual path written
    foreach (array_keys($index, "{$f['set']}/{$f['basename']}.php", true) as $cid) $index[$cid] = $rel;
}
if ($collisions) { echo "⚠ basename collisions disambiguated:\n"; foreach ($collisions as $c) echo "  $c\n"; }

echo "── split $set ──\n";
echo "cards: " . count($merged) . ", statements moved: $movedCount, index entries: " . count($index) . "\n";
echo "left in monolith (touching $set): " . count($left) . "\n";

// ── DRY: write to a temp dir, lint, and stop. ──
if ($dry) {
    $tmp = sys_get_temp_dir() . "/split_dry_$set";
    exec('rm -rf ' . escapeshellarg($tmp)); @mkdir($tmp, 0777, true);
    $bad = 0;
    foreach ($fileContents as $rel => $content) {
        $p = "$tmp/$rel";
        @mkdir(dirname($p), 0777, true);
        file_put_contents($p, $content);
        if (!splitter_php_lints($content)) { $bad++; echo "  LINT FAIL: $rel\n"; }
    }
    foreach ($remaining as $m => $src) if (!splitter_php_lints($src)) { $bad++; echo "  LINT FAIL (remaining): $m\n"; }
    echo $bad === 0 ? "DRY OK — all files + remaining lint clean. Wrote preview to $tmp\n"
                    : "DRY FAILED — $bad lint error(s)\n";
    exit($bad === 0 ? 0 : 1);
}

// ── 2. Backup monoliths. ──
$backups = [];
foreach ($monoliths as $m) { $bak = "$m.bak"; copy($m, $bak); $backups[$m] = $bak; }
$restore = function() use ($backups, $fileContents, $cardsDir, $indexPath) {
    foreach ($backups as $m => $bak) { copy($bak, $m); }
    foreach (array_keys($fileContents) as $rel) @unlink("$cardsDir/$rel");
    @unlink($indexPath);
};

// ── 3. BEFORE snapshot (monoliths intact, cards/ empty of this set). ──
$before = json_decode(shell_exec('php -d xdebug.mode=off ' . escapeshellarg($snapHelper) . ' 2>/dev/null'), true);
if (!is_array($before)) { $restore(); fwrite(STDERR, "FATAL: could not capture BEFORE snapshot.\n"); exit(1); }

// ── 4. Mutate: write card files, rewrite monoliths, write index. ──
foreach ($fileContents as $rel => $content) {
    $p = "$cardsDir/$rel";
    @mkdir(dirname($p), 0777, true);
    file_put_contents($p, $content);
}
foreach ($remaining as $m => $src) file_put_contents($m, $src);

ksort($index);
$idx = "<?php\n// GENERATED by split-cards.php — CardID => cards/<set>/<file> relative path.\nreturn [\n";
foreach ($index as $cid => $rel) $idx .= "    '" . addslashes($cid) . "' => '" . addslashes($rel) . "',\n";
$idx .= "];\n";
$existingIdx = is_file($indexPath) ? require $indexPath : [];
if (is_array($existingIdx)) {
    // Preserve prior sets' index entries; merge this set's on top.
    $merged_index = $existingIdx;
    foreach ($index as $cid => $rel) $merged_index[$cid] = $rel;
    ksort($merged_index);
    $idx = "<?php\n// GENERATED by split-cards.php — CardID => cards/<set>/<file> relative path.\nreturn [\n";
    foreach ($merged_index as $cid => $rel) $idx .= "    '" . addslashes($cid) . "' => '" . addslashes($rel) . "',\n";
    $idx .= "];\n";
}
file_put_contents($indexPath, $idx);

// ── 5. AFTER snapshot (monoliths shrunk, cards/ populated, loader active). ──
$after = json_decode(shell_exec('php -d xdebug.mode=off ' . escapeshellarg($snapHelper) . ' 2>/dev/null'), true);
if (!is_array($after)) { $restore(); fwrite(STDERR, "FATAL: could not capture AFTER snapshot — reverted.\n"); exit(1); }

// ── 6. Diff. Any mismatch → revert. ──
$diff = splitter_diff_keys($before, $after);
if (!empty($diff['missing']) || !empty($diff['added'])) {
    echo "KEY DIFF FAILED — reverting.\n";
    if ($diff['missing']) echo "  MISSING (" . count($diff['missing']) . "): " . implode(', ', array_slice($diff['missing'], 0, 20)) . "\n";
    if ($diff['added'])   echo "  ADDED ("   . count($diff['added'])   . "): " . implode(', ', array_slice($diff['added'], 0, 20)) . "\n";
    $restore();
    exit(1);
}

echo "key diff: OK (" . count($before) . " keys, 0 missing, 0 added)\n";
echo "backups left at *.bak (remove after you've run the suite).\n";
echo "LEFT-BEHIND log (touching $set):\n";
$byReason = [];
foreach ($left as $l) { $r = preg_replace('/:.*/', '', $l['reason']); $byReason[$r] = ($byReason[$r] ?? 0) + 1; }
foreach ($byReason as $r => $c) echo "  $r: $c\n";
exit(0);
