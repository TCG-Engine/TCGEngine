<?php
// Flip audit: classifies every mock of a set against the official(+supplement) data.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } }

require __DIR__ . '/../../../AppCore/SWU/CardDataSupplementApply.php';
require __DIR__ . '/../FlipAudit.php';

// mocks=prefer view: mock values (plus supplement fills, which must count as blank)
$prefer = [
  'dictionaries' => [
    'title' => ['HMW_004' => 'Grand Moff Tarkin', 'HMW_019' => 'Dune Sea', 'HMW_021' => 'Kashirho', 'HMW_084' => 'Gunga City Guard', 'HMW_178' => 'Desperate Nantex', 'HMW_300' => 'Unreleased', 'JTL_030' => 'Mos Eisley'],
    'text'  => ['HMW_004' => '', 'HMW_019' => '', 'HMW_021' => '', 'HMW_084' => 'While you control a Naboo base, this unit gains Shielded.', 'HMW_178' => '', 'HMW_300' => '', 'JTL_030' => ''],
    'trait' => ['HMW_004' => 'Imperial,Official', 'HMW_019' => 'Tatooine', 'HMW_021' => 'Kashyyyk', 'HMW_084' => 'Gungan, Trooper', 'HMW_178' => 'Creature', 'HMW_300' => '', 'JTL_030' => 'Tatooine'],
    'leaderUnitTitle' => ['HMW_004' => 'The Death Star', 'HMW_019' => null, 'HMW_021' => null, 'HMW_084' => null, 'HMW_178' => null, 'HMW_300' => null, 'JTL_030' => null],
    'subtitle' => ['HMW_004' => 'Tyrant of the Outer Rim', 'HMW_019' => '', 'HMW_021' => '', 'HMW_084' => '', 'HMW_178' => 'Mock Error', 'HMW_300' => '', 'JTL_030' => ''],
  ],
  'provenance' => ['JTL_030' => ['trait' => 'supplement:swudb']],
];
// mocks=0 view: official + supplement
$official = [
  'dictionaries' => [
    'title' => ['HMW_004' => 'Grand Moff Tarkin', 'HMW_019' => 'Dune Sea', 'HMW_021' => 'Kachirho', 'HMW_084' => 'Gunga City Guard', 'HMW_178' => 'Desperate Nantex', 'HMW_200' => 'Never Mocked', 'JTL_030' => 'Mos Eisley'],
    'text'  => ['HMW_004' => '', 'HMW_019' => '', 'HMW_021' => '', 'HMW_084' => "While you control a Naboo base,\nthis unit gains Shielded.", 'HMW_178' => 'Ambush', 'HMW_200' => 'x', 'JTL_030' => ''],
    'trait' => ['HMW_004' => 'Imperial,Official', 'HMW_019' => '', 'HMW_021' => 'Kashyyyk', 'HMW_084' => 'Trooper,Gungan', 'HMW_178' => 'Creature', 'HMW_200' => 'X', 'JTL_030' => 'Tatooine'],
    'leaderUnitTitle' => ['HMW_004' => null, 'HMW_019' => null, 'HMW_021' => null, 'HMW_084' => null, 'HMW_178' => null, 'HMW_200' => null, 'JTL_030' => null],
    'subtitle' => ['HMW_004' => 'Tyrant of the Outer Rim', 'HMW_019' => '', 'HMW_021' => '', 'HMW_084' => '', 'HMW_178' => '', 'HMW_200' => '', 'JTL_030' => ''],
  ],
  'provenance' => [],
];
$mockIDs = ['HMW_004', 'HMW_019', 'HMW_021', 'HMW_084', 'HMW_178', 'HMW_300', 'IC27_001'];

$a = SWUFlipAudit($prefer, $official, $mockIDs, 'HMW', ['HMW_178.subtitle']);
$gapKeys = array_map(fn($g) => $g['cardID'] . '.' . $g['field'], $a['blockGap']);
$reviewKeys = array_map(fn($r) => $r['cardID'] . '.' . $r['field'], $a['review']);

check($a['blockNoOfficial'] === ['HMW_300'], 'mock with no official record blocks');
check(in_array('HMW_004.leaderUnitTitle', $gapKeys, true), 'Tarkin deployed title gap blocks');
check(in_array('HMW_019.trait', $gapKeys, true), 'base trait gap blocks');
check(in_array('HMW_021.title', $reviewKeys, true), 'renamed title is REVIEW');
check(!in_array('HMW_084.text', $reviewKeys, true), 'whitespace-only text difference is equal');
check(!in_array('HMW_084.trait', $reviewKeys, true), 'trait order/spacing difference is equal');
check(!in_array('HMW_178.subtitle', $gapKeys, true), 'accepted gap does not block');
check(count($a['acceptedGap']) === 1 && $a['acceptedGap'][0]['cardID'] === 'HMW_178', 'accepted gap listed');
check(count($a['officialOnly']) === 1 && $a['officialOnly'][0]['cardID'] === 'HMW_178' && $a['officialOnly'][0]['field'] === 'text', 'official-only listed');
check($a['new'] === ['HMW_200'], 'never-mocked official card listed as NEW');
check($a['safeToDelete'] === ['HMW_021', 'HMW_084', 'HMW_178'], 'only unblocked mocks are safe to delete, got ' . implode(',', $a['safeToDelete']));
check(!in_array('IC27_001', array_merge($a['blockNoOfficial'], $a['safeToDelete']), true), 'other sets ignored');

// --- text differences sort first in REVIEW ---
$prefer2 = ['dictionaries' => ['title' => ['HMW_001' => 'A'], 'text' => ['HMW_001' => 'old'], 'cost' => ['HMW_001' => 5]], 'provenance' => []];
$official2 = ['dictionaries' => ['title' => ['HMW_001' => 'A'], 'text' => ['HMW_001' => 'new'], 'cost' => ['HMW_001' => 4]], 'provenance' => []];
$b = SWUFlipAudit($prefer2, $official2, ['HMW_001'], 'HMW');
check($b['review'][0]['field'] === 'text' && $b['review'][1]['field'] === 'cost', 'text REVIEW rows first');

// --- a supplement-filled value on the prefer side is not the mock's own data ---
$prefer3 = ['dictionaries' => ['title' => ['HMW_002' => 'B'], 'trait' => ['HMW_002' => 'Endor']], 'provenance' => ['HMW_002' => ['trait' => 'supplement:swudb']]];
$official3 = ['dictionaries' => ['title' => ['HMW_002' => 'B'], 'trait' => ['HMW_002' => '']], 'provenance' => []];
$c = SWUFlipAudit($prefer3, $official3, ['HMW_002'], 'HMW');
check($c['blockGap'] === [] && $c['safeToDelete'] === ['HMW_002'], 'supplement value on mock side counts as blank');

echo "OK\n";
