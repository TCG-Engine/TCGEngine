<?php
/** Exercise direct creation, immutable baselines, and format enforcement through real entry points. */
$repo = dirname(__DIR__, 2);
$token = 'source-creation-test-' . bin2hex(random_bytes(6));
$definitionFile = "$repo/DevTools/ga-fixtures/$token.php";
$fixtureRoot = "$repo/Tests/Integration/GrandArchiveSim";
$slugs = [$token, "$token-invalid", "$token-rejection"];
function creationRun(string $script, array $args, bool $pass = true): string {
    global $repo;
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$repo/DevTools/$script");
    foreach ($args as $arg) $cmd .= ' ' . escapeshellarg($arg);
    exec($cmd . ' 2>&1', $lines, $status);
    $output = implode("\n", $lines);
    if (($status === 0) !== $pass) throw new RuntimeException("Unexpected exit $status: $output");
    return $output;
}
function creationAssert(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
try {
    $definitions = json_decode(creationRun('create-prd-ability-fixtures.php', ['--export-definitions']), true, 512, JSON_THROW_ON_ERROR);
    $valid = $definitions['tinderflare-pivot-makes-a-ranger-distant'];
    $bad = $valid;
    $bad['assertions'][] = ['step' => 0, 'type' => 'zoneCount', 'zone' => 'p1Field', 'expected' => -1];
    $rejection = $definitions['twilight-slime-class-bonus-gives-other-slimes-spellshroud'];
    $source = "<?php\n";
    foreach (array_combine($slugs, [$valid, $bad, $rejection]) as $slug => $def) {
        $source .= '$fixtures[' . var_export($slug, true) . '] = ' . var_export($def, true) . ";\n";
    }
    file_put_contents($definitionFile, $source);
    creationRun('create-prd-ability-fixtures.php', ["--fixture=$token", '--dry-run']);
    creationAssert(!file_exists("$fixtureRoot/$token"), 'Dry run wrote a fixture');
    creationRun('create-prd-ability-fixtures.php', ["--fixture=$token-invalid"], false);
    creationAssert(!file_exists("$fixtureRoot/$token-invalid"), 'Failed assertions published a baseline');
    foreach ([$token, "$token-rejection"] as $slug) {
        creationRun('create-prd-ability-fixtures.php', ["--fixture=$slug"]);
        $files = array_map('basename', glob("$fixtureRoot/$slug/*"));
        sort($files);
        creationAssert($files === ['expected_final_gamestate.txt', 'meta.json'], 'Unexpected fixture files');
        $meta = json_decode(file_get_contents("$fixtureRoot/$slug/meta.json"), true);
        creationAssert($meta['sourceScenario']['seed'] === 42, 'Seed not recorded');
        creationRun('RunIntegrationTests.php', ['--root=GrandArchiveSim', "--test=$slug"]);
    }
    $snapshotPath = "$fixtureRoot/$token/expected_final_gamestate.txt";
    $original = file_get_contents($snapshotPath);
    file_put_contents($snapshotPath, $original . "unexpected\n");
    creationRun('create-prd-ability-fixtures.php', ["--fixture=$token"], false);
    creationAssert(file_get_contents($snapshotPath) === $original . "unexpected\n", 'Generation overwrote expectation');
    file_put_contents($snapshotPath, $original);
    file_put_contents("$fixtureRoot/$token/actions.json", '[]');
    creationRun('create-prd-ability-fixtures.php', ['--check-source-format', "--fixture=$token"], false);
    creationRun('RunIntegrationTests.php', ['--root=GrandArchiveSim', "--test=$token"], false);
    unlink("$fixtureRoot/$token/actions.json");
    $metaPath = "$fixtureRoot/$token/meta.json";
    $meta = json_decode(file_get_contents($metaPath), true);
    unset($meta['sourceScenario']);
    file_put_contents($metaPath, json_encode($meta));
    creationRun('create-prd-ability-fixtures.php', ['--check-source-format', "--fixture=$token"], false);
    echo "PASS: direct creation, expected rejection, failed assertion, dry run, immutable snapshot, and format enforcement\n";
} finally {
    if (file_exists($definitionFile)) unlink($definitionFile);
    foreach ($slugs as $slug) {
        foreach (glob("$fixtureRoot/$slug/*") as $file) unlink($file);
        if (is_dir("$fixtureRoot/$slug")) rmdir("$fixtureRoot/$slug");
    }
}
