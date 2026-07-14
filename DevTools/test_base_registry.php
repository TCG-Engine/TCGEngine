<?php
// Web-accessible self-test for Core/StatsBaseRegistry.php.
// Run: curl -s http://localhost:3100/TCGEngine/DevTools/test_base_registry.php
header('Content-Type: application/json');
require_once __DIR__ . '/../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/../Core/StatsBaseRegistry.php';

$fail = [];
function check(&$fail, $label, $got, $want) {
    if ($got !== $want) $fail[] = "$label: got " . json_encode($got) . " want " . json_encode($want);
}

// LOF_026 and LOF_027 both resolve to the same Red Force canonical (consolidation).
check($fail, 'LOF_026 kind',  ResolveOpponentBase('5396502974')['kind'],  'common');
check($fail, 'LOF_026 color', ResolveOpponentBase('5396502974')['color'], 'Red');
check($fail, 'LOF_026 type',  ResolveOpponentBase('5396502974')['type'],  'Force');
check($fail, 'LOF_027 canonical==LOF_026', ResolveOpponentBase('8710346686')['canonical'], '5396502974');

// Splash sample.
check($fail, 'LAW_022 color', ResolveOpponentBase('2248996839')['color'], 'Green');
check($fail, 'LAW_022 type',  ResolveOpponentBase('2248996839')['type'],  'Splash');

// Rare base (Data Vault, JTL_024) => named, by name.
check($fail, 'DataVault kind',  ResolveOpponentBase('4028826022')['kind'], 'named');
check($fail, 'DataVault name',  ResolveOpponentBase('4028826022')['name'], 'Data Vault');

// Special base (Echo Caverns, IBH_002) => named.
check($fail, 'EchoCaverns kind', ResolveOpponentBase('1049149674')['kind'], 'named');

// Standard fallback: a 30HP common (Amnesty Housing, SEC_025) => common Standard, color from aspect.
check($fail, 'Amnesty kind',  ResolveOpponentBase('4642946597')['kind'], 'common');
check($fail, 'Amnesty type',  ResolveOpponentBase('4642946597')['type'], 'Standard');
check($fail, 'Amnesty color', ResolveOpponentBase('4642946597')['color'], 'Yellow');

// Unknown GUID not in any list and not in the dictionary => null (forces legacy fallback).
check($fail, 'unknown base => null', ResolveOpponentBase('9999999999'), null);

// Column-suffix mapping (Legacy is the original suffix-less columns).
check($fail, 'Legacy suffix',   StatsTypeColumnSuffix('Legacy'),   '');
check($fail, 'Standard suffix', StatsTypeColumnSuffix('Standard'), 'Standard');
check($fail, 'Force suffix',    StatsTypeColumnSuffix('Force'),    'Force');
check($fail, 'Splash suffix',   StatsTypeColumnSuffix('Splash'),   'Splash');

// Aspect->color.
check($fail, 'Aggression=>Red', AspectToColor('Aggression'), 'Red');
check($fail, 'Neutral=>Colorless', AspectToColor('Heroism'), 'Colorless');

// --- StatsBaseBucket: common bases collapse by (color,type); rares stay individual. ---
// Two DIFFERENT red 30HP commons must share one bucket + representative art.
//   2696059415 = Naval Intelligence HQ (also the canonical red-30 GUID)
//   4286542404 = Imperial Prison Complex (a different red 30HP common)
check($fail, 'bucket NavalIntel key',   StatsBaseBucket('2696059415')['key'],         'grp:Standard:Red');
check($fail, 'bucket ImpPrison key',    StatsBaseBucket('4286542404')['key'],         'grp:Standard:Red');
check($fail, 'bucket NavalIntel disp',  StatsBaseBucket('2696059415')['displayBase'], '2696059415');
check($fail, 'bucket ImpPrison disp',   StatsBaseBucket('4286542404')['displayBase'], '2696059415');
// Force base (LOF_026 red) → per-color Force canonical.
check($fail, 'bucket Force key',        StatsBaseBucket('5396502974')['key'],         'grp:Force:Red');
check($fail, 'bucket Force disp',       StatsBaseBucket('5396502974')['displayBase'], '5396502974');
// Splash base (LAW_022 green) → per-color Splash canonical.
check($fail, 'bucket Splash key',       StatsBaseBucket('2248996839')['key'],         'grp:Splash:Green');
check($fail, 'bucket Splash disp',      StatsBaseBucket('2248996839')['displayBase'], '2248996839');
// Rare base (Data Vault, JTL_024) stays individual (key == displayBase == its own GUID).
check($fail, 'bucket DataVault key',    StatsBaseBucket('4028826022')['key'],         '4028826022');
check($fail, 'bucket DataVault disp',   StatsBaseBucket('4028826022')['displayBase'], '4028826022');
// Unknown GUID (not in any list, not in dict) → itself (safe passthrough).
check($fail, 'bucket unknown key',      StatsBaseBucket('9999999999')['key'],         '9999999999');

echo json_encode(['pass' => empty($fail), 'failures' => $fail], JSON_PRETTY_PRINT);
