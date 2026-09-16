<?php
// SWUDB backfill planning — pure, network-free: the fetcher is injected.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } }

require __DIR__ . '/../../../AppCore/SWU/CardDataSupplementApply.php';
require __DIR__ . '/../CardDataBackfill.php';

$snapshot = [
  'dictionaries' => [
    'title' => ['HMW_019' => 'Dune Sea', 'HMW_022' => 'Shield Generator Complex', 'HMW_031' => 'Kyyyalstaad Swamp',
                'HMW_004' => 'Grand Moff Tarkin', 'HMW_T01' => 'Beast', 'HMW_050' => 'Wrong Card', 'HMW_060' => 'Unreachable',
                'JTL_030' => 'Mos Eisley', 'SOR_010' => 'Darth Vader'],
    'type'  => ['HMW_019' => 'Base', 'HMW_022' => 'Base', 'HMW_031' => 'Base', 'HMW_004' => 'Leader', 'HMW_T01' => 'Token Unit',
                'HMW_050' => 'Base', 'HMW_060' => 'Base', 'JTL_030' => 'Base', 'SOR_010' => 'Leader'],
    'trait' => ['HMW_019' => '', 'HMW_022' => 'Endor', 'HMW_031' => 'Kashyyyk', 'HMW_004' => 'Imperial,Official', 'HMW_T01' => '',
                'HMW_050' => '', 'HMW_060' => '', 'JTL_030' => 'Tatooine', 'SOR_010' => 'Force,Imperial,Sith'],
  ],
  'provenance' => [
    'HMW_022' => ['trait' => 'supplement:swudb'],   // existing swudb entry -> refreshed
    'HMW_031' => ['trait' => 'supplement:manual'],  // manual -> hands off
    'HMW_004' => ['trait' => 'official'],
    'JTL_030' => ['trait' => 'supplement:swudb'],
    'SOR_010' => ['trait' => 'official'],
  ],
];
$supplement = [
  'HMW_022' => ['trait' => ['value' => 'Hoth', 'source' => 'swudb']],
  'HMW_031' => ['trait' => ['value' => 'Kashyyyk', 'source' => 'manual']],
  'JTL_030' => ['trait' => ['value' => 'Tatooine', 'source' => 'swudb']],
];
$fetched = [];
$fetch = function (string $set, string $num) use (&$fetched) {
    $fetched[] = "{$set}_{$num}";
    $db = [
      'HMW_019' => ['cardName' => 'Dune Sea', 'traits' => ['Tatooine']],
      'HMW_022' => ['cardName' => 'Shield Generator Complex', 'traits' => ['Endor']],
      'HMW_050' => ['cardName' => 'Some Other Card', 'traits' => ['Naboo']],
      'JTL_030' => ['cardName' => 'Mos Eisley', 'traits' => ['Tatooine']],
    ];
    return $db["{$set}_{$num}"] ?? null;   // HMW_060 -> null (unreachable)
};

$plan = SWUPlanCardDataBackfill($snapshot, $supplement, $fetch, SWUBackfillFieldMap(), ['set' => 'HMW']);
$status = [];
foreach ($plan['rows'] as $row) $status[$row['cardID']] = $row['status'];

check($status['HMW_019'] === 'added', 'blank official trait -> added');
check($plan['supplement']['HMW_019']['trait'] === ['value' => 'Tatooine', 'source' => 'swudb'], 'added entry is swudb');
check($status['HMW_022'] === 'changed', 'existing swudb entry refreshed when SWUDB changed');
check($plan['supplement']['HMW_022']['trait']['value'] === 'Endor', 'refreshed value written');
check(!isset($status['HMW_031']) && $plan['supplement']['HMW_031']['trait']['source'] === 'manual', 'manual entry never touched or fetched');
check(!isset($status['HMW_004']), 'non-blank official value is not a candidate');
check($status['HMW_T01'] === 'token-skipped' && !in_array('HMW_T01', $fetched, true), 'tokens skipped without fetching');
check($status['HMW_050'] === 'title-mismatch' && !isset($plan['supplement']['HMW_050']), 'title mismatch writes nothing');
check($status['HMW_060'] === 'fetch-failed' && !isset($plan['supplement']['HMW_060']), 'fetch failure writes nothing');
check(!isset($status['JTL_030']), '--set filter excludes other sets');
check($plan['counts']['added'] === 1 && $plan['counts']['changed'] === 1, 'counts added/changed');
check($plan['counts']['failed'] === 1 && $plan['counts']['titleMismatch'] === 1 && $plan['counts']['tokenSkipped'] === 1, 'counts failures');

// --- source-empty: SWUDB has no value -> nothing written ---
$emptyFetch = fn(string $s, string $n) => ['cardName' => 'Dune Sea', 'traits' => []];
$p2 = SWUPlanCardDataBackfill(['dictionaries' => ['title' => ['HMW_019' => 'Dune Sea'], 'type' => ['HMW_019' => 'Base'], 'trait' => ['HMW_019' => '']], 'provenance' => []],
                              [], $emptyFetch, SWUBackfillFieldMap());
check($p2['rows'][0]['status'] === 'source-empty' && $p2['supplement'] === [], 'source-empty writes nothing');

// --- type filter ---
$p3 = SWUPlanCardDataBackfill($snapshot, $supplement, $fetch, SWUBackfillFieldMap(), ['set' => 'HMW', 'type' => 'Leader']);
check(count($p3['rows']) === 0, 'type filter excludes non-matching types');

// --- apostrophe typography does not cause a false title mismatch ---
$p4 = SWUPlanCardDataBackfill(['dictionaries' => ['title' => ['SEC_026' => "Jabba's Palace"], 'type' => ['SEC_026' => 'Base'], 'trait' => ['SEC_026' => '']], 'provenance' => []],
                              [], fn($s, $n) => ['cardName' => "Jabba\u{2019}s Palace", 'traits' => ['Tatooine']], SWUBackfillFieldMap());
check($p4['rows'][0]['status'] === 'added', 'curly apostrophe title still matches');

echo "OK\n";
