<?php
/**
 * Mutation spot-check for GrandArchiveSim semantic fixtures: proves the assertions can fail.
 *
 * For each sampled fixture it picks one hand-labelled (semantic) assertion, changes its expected value, replays the
 * fixture and requires it to FAIL. A mutant that still passes means the assertion is not really checking the state
 * it claims to (wrong step, wrong zone, or the value is unreachable), and is reported as a survivor.
 * The fixture's assertions.json is always restored afterwards.
 *
 * Usage: php DevTools/mutation-spot-check.php [--root=GrandArchiveSim] [--sample=25] [--seed=1] [--slug=a,b] [--all]
 */
error_reporting(E_ALL & ~E_DEPRECATED);
$repoRoot = dirname(__DIR__);
chdir($repoRoot);

$root = 'GrandArchiveSim'; $sample = 25; $seed = 1; $slugs = null; $all = false;
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--root=')) $root = substr($arg, 7);
    elseif (str_starts_with($arg, '--sample=')) $sample = intval(substr($arg, 9));
    elseif (str_starts_with($arg, '--seed=')) $seed = intval(substr($arg, 7));
    elseif (str_starts_with($arg, '--slug=')) $slugs = explode(',', substr($arg, 7));
    elseif ($arg === '--all') $all = true;
}

$mutable = ['zone_count', 'card_property_equals', 'computed_power_equals', 'computed_life_equals'];
$fixtureRoot = "$repoRoot/Tests/Integration/$root";
$candidates = [];
foreach (scandir($fixtureRoot) as $slug) {
    if ($slug[0] === '.' || !is_file("$fixtureRoot/$slug/assertions.json")) continue;
    if ($slugs !== null && !in_array($slug, $slugs, true)) continue;
    $assertions = json_decode(file_get_contents("$fixtureRoot/$slug/assertions.json"), true) ?: [];
    foreach ($assertions as $i => $a) {
        if (!empty($a['semantic']) && in_array($a['type'] ?? '', $mutable, true) && array_key_exists('value', $a)) {
            $candidates[$slug][] = $i;
        }
    }
}
$names = array_keys($candidates);
mt_srand($seed);
if (!$all && $slugs === null) {
    shuffle($names);
    $names = array_slice($names, 0, $sample);
}
sort($names);

$survivors = [];
$killed = 0;
foreach ($names as $slug) {
    $path = "$fixtureRoot/$slug/assertions.json";
    $original = file_get_contents($path);
    $assertions = json_decode($original, true);
    $index = $candidates[$slug][mt_rand(0, count($candidates[$slug]) - 1)];
    $value = $assertions[$index]['value'];
    $assertions[$index]['value'] = is_numeric($value) ? strval(intval($value) + 1) : $value . '_MUTANT';
    if (is_int($value)) $assertions[$index]['value'] = intval($value) + 1;
    file_put_contents($path, json_encode($assertions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    try {
        $out = shell_exec('php DevTools/RunIntegrationTests.php --root=' . escapeshellarg($root) . ' --test=' . escapeshellarg($slug) . ' 2>&1');
    } finally {
        file_put_contents($path, $original);
    }
    $failed = str_contains($out, '[FAIL]');
    $label = $assertions[$index]['label'] ?? '';
    if ($failed) { ++$killed; echo "killed    $slug  [$index]\n"; }
    else { $survivors[] = $slug; echo "SURVIVED  $slug  [$index] " . ($assertions[$index]['type'] ?? '') . ' ' . ($assertions[$index]['mzId'] ?? $assertions[$index]['zone'] ?? '') . " - $label\n"; }
}
echo "\n" . count($names) . " mutants: $killed killed, " . count($survivors) . " survived.\n";
exit(count($survivors) > 0 ? 1 : 0);
