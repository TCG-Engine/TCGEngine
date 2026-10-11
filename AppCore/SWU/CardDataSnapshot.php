<?php
// Card data snapshot: what the generator WOULD write for the SWU dictionaries, plus where each
// non-blank value came from. Produced by `zzCardCodeGenerator.php rootName=SWUSim snapshot=<path>`
// (CLI only), which stops before writing any dictionary, JS or image.
//
//   mocks=1       default merge: official rows win, mocks fill missing CardIDs
//   mocks=0       no mocks — the official(+supplement) view
//   mocks=prefer  a mock REPLACES the official row for its CardID (SWUSim only) — the mock's view
//
// Read by SWUSim/DevTools/backfill-card-data.php and flip-audit.php.

function SWUBuildCardDataSnapshot(string $rootName, string $mocks, array $associativeArrays, array $fields,
                                  array $mockIDs, array $supplementResult): array {
    $mockSet = array_fill_keys($mockIDs, true);
    $dictionaries = [];
    $provenance = [];
    foreach ($fields as $field) {
        $dictionaries[$field] = $associativeArrays[$field] ?? [];
        foreach ($dictionaries[$field] as $cardID => $value) {
            if (SWUIsBlankDictionaryValue($value)) continue;
            if (isset($supplementResult['filled'][$cardID][$field])) {
                $source = 'supplement:' . $supplementResult['filled'][$cardID][$field];
            } else if (isset($mockSet[$cardID])) {
                $source = 'mock';
            } else {
                $source = 'official';
            }
            $provenance[$cardID][$field] = $source;
        }
    }
    return ['rootName' => $rootName, 'mocks' => $mocks, 'dictionaries' => $dictionaries, 'provenance' => $provenance];
}

// Run the generator in a subprocess and return its snapshot. Inherits the environment, so run the
// calling tool with DEVENV=true (the generator's CLI auth check requires it). The generator can exit
// 0 on failure, so success is judged by the snapshot file, not the exit code alone.
function SWUTakeCardDataSnapshot(string $mocks, string $rootName = 'SWUSim'): array {
    $root = realpath(__DIR__ . '/../..');
    $out = tempnam(sys_get_temp_dir(), 'cardsnap_');
    $cmd = 'cd ' . escapeshellarg($root) . ' && ' . escapeshellarg(PHP_BINARY)
         . ' -d xdebug.mode=off -d memory_limit=2G zzCardCodeGenerator.php '
         . escapeshellarg('rootName=' . $rootName) . ' '
         . escapeshellarg('mocks=' . $mocks) . ' '
         . escapeshellarg('snapshot=' . $out) . ' 2>&1';
    $lines = [];
    exec($cmd, $lines, $code);
    $json = (string)@file_get_contents($out);
    @unlink($out);
    $data = $json !== '' ? json_decode($json, true) : null;
    if ($code !== 0 || !is_array($data) || !isset($data['dictionaries'], $data['provenance'])) {
        throw new RuntimeException("card data snapshot failed (mocks=$mocks, exit $code):\n"
            . implode("\n", array_slice($lines, -20)));
    }
    return $data;
}
