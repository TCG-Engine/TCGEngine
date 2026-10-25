<?php
/** Validate live builder inputs and snapshots; baseline creation is explicit and create-only. */
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

/** Publish a new baseline together with its marker; never replace an existing fixture. */
function gaSourceCreate(string $dir, array $meta, string $snapshot): void {
    if (file_exists($dir)) throw new RuntimeException('Refusing to replace existing fixture');
    $temp = $dir . '.tmp-' . bin2hex(random_bytes(8));
    if (!mkdir($temp, 0777, true)) throw new RuntimeException('Cannot create source fixture');
    try {
        if (file_put_contents($temp . '/meta.json', json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n") === false
            || file_put_contents($temp . '/expected_final_gamestate.txt', $snapshot) === false) {
            throw new RuntimeException('Cannot write source fixture');
        }
        if (file_exists($dir) || !rename($temp, $dir)) throw new RuntimeException('Cannot publish source fixture');
    } finally {
        if (is_dir($temp)) {
            foreach (glob($temp . '/*') as $file) unlink($file);
            rmdir($temp);
        }
    }
}

/** Existing serialized builders are explicit exceptions; new builders must stay compact. */
function gaSourceCheckFormat(array $definitions, string $root, string $allowFile): void {
    $allowed = array_fill_keys(array_filter(array_map('trim', file($allowFile)), fn($line) => $line !== '' && $line[0] !== '#'), true);
    foreach ($definitions as $slug => $def) {
        if (empty($def['strictActions']) || isset($def['meta']['behaviorFamily'])) continue;
        $dir = $root . '/' . $slug;
        if (!file_exists($dir)) continue; // A new definition will be created directly in compact format.
        $meta = is_file($dir . '/meta.json') ? json_decode(file_get_contents($dir . '/meta.json'), true, 512, JSON_THROW_ON_ERROR) : [];
        if (!isset($meta['sourceScenario'])) {
            if (isset($allowed[$slug])) continue;
            throw new RuntimeException("$slug: new builder scenarios must use compact source storage; migrate with --migrate-source --fixture=$slug");
        }
        foreach (['initial_gamestate.txt', 'actions.json', 'assertions.json'] as $file) {
            if (file_exists($dir . '/' . $file)) throw new RuntimeException("$slug: redundant source input $file");
        }
        if (!is_file($dir . '/expected_final_gamestate.txt')) throw new RuntimeException("$slug: Missing expected final snapshot");
    }
}
