<?php
// Card data snapshot: dictionaries + per-field provenance, the official-only view the backfill and
// flip-audit tools read instead of re-implementing the generator.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } }

require __DIR__ . '/../../../AppCore/SWU/CardDataSupplementApply.php';
require __DIR__ . '/../../../AppCore/SWU/CardDataSnapshot.php';

$dicts = [
  'title' => ['SOR_010' => 'Darth Vader', 'HMW_019' => 'Dune Sea', 'HMW_178' => 'Desperate Nantex'],
  'trait' => ['SOR_010' => 'Force,Imperial,Sith', 'HMW_019' => 'Tatooine', 'HMW_178' => ''],
  'uuidLookup' => ['SOR_010' => 'x'],   // bookkeeping array: must NOT appear (not in $fields)
];
$supp = ['filled' => ['HMW_019' => ['trait' => 'swudb']], 'unknownCard' => [], 'unknownField' => []];
$snap = SWUBuildCardDataSnapshot('SWUSim', 'prefer', $dicts, ['title', 'trait'], ['HMW_178'], $supp);

check($snap['rootName'] === 'SWUSim' && $snap['mocks'] === 'prefer', 'header fields');
check(array_keys($snap['dictionaries']) === ['title', 'trait'], 'only requested fields');
check($snap['dictionaries']['trait']['HMW_178'] === '', 'blank values kept in dictionaries');
check($snap['provenance']['SOR_010']['trait'] === 'official', 'official provenance');
check($snap['provenance']['HMW_019']['trait'] === 'supplement:swudb', 'supplement provenance');
check($snap['provenance']['HMW_019']['title'] === 'official', 'unfilled field of a supplemented card is official');
check($snap['provenance']['HMW_178']['title'] === 'mock', 'mock provenance');
check(!isset($snap['provenance']['HMW_178']['trait']), 'blank values get no provenance');

// --- generator wiring (source checks) ---
$gen = file_get_contents(__DIR__ . '/../../../zzCardCodeGenerator.php');
check(strpos($gen, 'TryGET("mocks", "1")') !== false, 'generator reads mocks=');
check(preg_match('/\$snapshotPath = \$isHTTPRequest \? "" : TryGET\("snapshot", ""\)/', $gen) === 1, 'snapshot= is CLI-only');
check(strpos($gen, 'SWUBuildCardDataSnapshot(') !== false, 'generator builds the snapshot');

echo "OK\n";
