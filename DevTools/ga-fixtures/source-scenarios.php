<?php
/** Guard the transition from serialized inputs to live builder inputs. Never record expectations. */
function gaSourceAssertMigrationInputs(string $dir, array $definition, string $initial, string $root): void {
    foreach (['actions', 'assertions'] as $part) {
        $path = $dir . '/' . $part . '.json';
        if (!is_file($path) || json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR) != ($definition[$part] ?? [])) {
            throw new RuntimeException("Migration mismatch: $part");
        }
    }
    $path = $dir . '/initial_gamestate.txt';
    if (!is_file($path) || RegressionNormalizeGamestateTextForComparison($root, file_get_contents($path)) !== RegressionNormalizeGamestateTextForComparison($root, $initial)) {
        $saved = is_file($path) ? RegressionNormalizeGamestateTextForComparison($root, file_get_contents($path)) : '';
        $built = RegressionNormalizeGamestateTextForComparison($root, $initial);
        throw new RuntimeException('Migration mismatch: initial state; ' . RegressionFormatSnapshotDiff($saved, $built));
    }
}

function gaSourceAssertFinal(string $dir, string $actual, string $root): void {
    $path = $dir . '/expected_final_gamestate.txt';
    if (!is_file($path)) throw new RuntimeException('Missing expected final snapshot');
    $expected = RegressionNormalizeGamestateTextForComparison($root, file_get_contents($path));
    $actual = RegressionNormalizeGamestateTextForComparison($root, $actual);
    if ($expected !== $actual) throw new RuntimeException('Final snapshot mismatch: ' . RegressionFormatSnapshotDiff($expected, $actual));
}
