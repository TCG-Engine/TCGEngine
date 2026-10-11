<?php
/**
 * Prove that scenario-builder fixtures regenerate to what is committed.
 *
 * For each scenario slug (all of DevTools/ga-fixtures/*.php, or the slugs named on the command line) this
 * regenerates the fixture, compares every file with the version in HEAD, and restores the committed files.
 * Comparison is by meaning, not bytes: JSON is decoded (the generator re-indents it and stamps meta.json
 * "name" with the slug), and gamestate text ignores CRLF vs LF.
 *
 * A fixture that is not in HEAD yet (a brand-new scenario) is regenerated and left in place.
 *
 * Usage: php DevTools/verify-ga-scenarios.php [--keep] [slug ...]
 * With --keep the regenerated files are left in place; restore them afterwards with
 *   git checkout -- Tests/Integration/GrandArchiveSim
 * Exit status is 1 when any fixture differs.
 */
$repoRoot = dirname(__DIR__);
$fixtureRoot = 'Tests/Integration/GrandArchiveSim';

$keep = in_array('--keep', $argv, true);   // compare only; leave regenerated files in place (restore once afterwards, e.g. when running slugs in parallel)
$slugs = array_values(array_filter(array_slice($argv, 1), fn($a) => !str_starts_with($a, '--')));
if (empty($slugs)) {
    foreach (glob(__DIR__ . '/ga-fixtures/*.php') as $file) {
        if (in_array(basename($file), ['Scenario.php', 'decks.php'], true)) continue;
        if (preg_match_all("/gaScenario\\('((?:[^'\\\\]|\\\\.)+)'\\)/", file_get_contents($file), $m)) {
            foreach ($m[1] as $slug) $slugs[] = stripslashes($slug);
        }
    }
}

function verifyHeadFile(string $repoRoot, string $path): ?string {
    $out = shell_exec('cd ' . escapeshellarg($repoRoot) . ' && git show ' . escapeshellarg('HEAD:' . $path) . ' 2>/dev/null');
    return $out === null || $out === '' ? null : $out;
}

$failures = [];
foreach ($slugs as $slug) {
    $dir = "$fixtureRoot/$slug";
    $existedInHead = verifyHeadFile($repoRoot, "$dir/actions.json") !== null;
    $log = shell_exec('cd ' . escapeshellarg($repoRoot) . ' && php DevTools/create-prd-ability-fixtures.php --fixture=' . escapeshellarg($slug) . ' 2>&1');
    if (strpos($log, '[OK] Fixture passes verification') === false) {
        $failures[$slug] = ['generator verification failed'];
        echo "FAIL  $slug (generator verification failed)\n";
        if (!$keep) shell_exec('cd ' . escapeshellarg($repoRoot) . ' && git checkout -- ' . escapeshellarg($dir) . ' 2>/dev/null');
        continue;
    }
    if (!$existedInHead) {
        echo "NEW   $slug (not in HEAD; left in place)\n";
        continue;
    }

    $differs = [];
    foreach (['actions.json', 'assertions.json', 'meta.json', 'initial_gamestate.txt', 'expected_final_gamestate.txt'] as $file) {
        $head = verifyHeadFile($repoRoot, "$dir/$file");
        $now = @file_get_contents("$repoRoot/$dir/$file");
        if ($head === null || $now === false) { $differs[] = "$file (missing)"; continue; }
        if (str_ends_with($file, '.json')) {
            $a = json_decode($head, true);
            $b = json_decode($now, true);
            if ($file === 'meta.json') { unset($a['name'], $b['name']); }
            if ($a != $b) $differs[] = $file;
        } elseif (str_replace("\r\n", "\n", $head) !== str_replace("\r\n", "\n", $now)) {
            $differs[] = $file;
        }
    }
    if (!$keep) shell_exec('cd ' . escapeshellarg($repoRoot) . ' && git checkout -- ' . escapeshellarg($dir) . ' 2>/dev/null');
    if ($differs) {
        $failures[$slug] = $differs;
        echo "DIFF  $slug: " . implode(', ', $differs) . "\n";
    } else {
        echo "same  $slug\n";
    }
}

echo "\n" . (count($slugs) - count($failures)) . " of " . count($slugs) . " scenarios match HEAD.\n";
exit($failures ? 1 : 0);
