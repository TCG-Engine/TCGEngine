<?php
require_once __DIR__ . '/../../Core/EngineActionRunner.php';
require_once __DIR__ . '/../ga-fixtures/source-scenarios.php';
function sourceMustThrow(callable $run, string $message): void {
    try { $run(); } catch (RuntimeException $e) {
        if (str_contains($e->getMessage(), $message)) return;
        throw $e;
    }
    throw new RuntimeException("Expected failure: $message");
}
$dir = sys_get_temp_dir() . '/ga-source-test-' . bin2hex(random_bytes(8));
mkdir($dir);
try {
    $root = 'GrandArchiveSim';
    $snapshot = file_get_contents(__DIR__ . '/../../Tests/Integration/GrandArchiveSim/tinderflare-pivot-makes-a-ranger-distant/expected_final_gamestate.txt');
    $def = ['actions' => [['playerID' => 1, 'mode' => 100, 'cardID' => 'YES']], 'assertions' => [['step' => 1, 'type' => 'zoneCount', 'expected' => 1]]];
    foreach (['actions', 'assertions'] as $part) file_put_contents("$dir/$part.json", json_encode($def[$part]));
    file_put_contents("$dir/initial_gamestate.txt", $snapshot);
    file_put_contents("$dir/expected_final_gamestate.txt", $snapshot);
    gaSourceAssertMigrationInputs($dir, $def, $snapshot, $root);
    foreach (['actions', 'assertions'] as $part) {
        $changed = $def; $changed[$part] = [];
        sourceMustThrow(fn() => gaSourceAssertMigrationInputs($dir, $changed, $snapshot, $root), "Migration mismatch: $part");
    }
    sourceMustThrow(fn() => gaSourceAssertMigrationInputs($dir, $def, $snapshot . "unexpected\n", $root), 'Migration mismatch: initial state');
    gaSourceAssertFinal($dir, $snapshot, $root);
    sourceMustThrow(fn() => gaSourceAssertFinal($dir, $snapshot . "unexpected\n", $root), 'Final snapshot mismatch');
    if (file_get_contents("$dir/expected_final_gamestate.txt") !== $snapshot) throw new RuntimeException('Expected snapshot was modified');
    unlink("$dir/expected_final_gamestate.txt");
    sourceMustThrow(fn() => gaSourceAssertFinal($dir, $snapshot, $root), 'Missing expected final snapshot');
    echo "PASS: migration rejects input drift; snapshot comparison rejects changed or missing expectations without rewriting them\n";
} finally {
    foreach (glob("$dir/*") as $file) unlink($file);
    rmdir($dir);
}
