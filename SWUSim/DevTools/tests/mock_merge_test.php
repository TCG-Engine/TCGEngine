<?php
// Mock loading, API-shape adaptation, and merge precedence (official data always wins).
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } }

require __DIR__ . '/../MockCardMerge.php';

$fixture = sys_get_temp_dir() . '/cardmocks_' . getmypid() . '.php';
file_put_contents($fixture, <<<'PHP'
<?php
return [
  'HMW_004' => [
    'title' => 'Grand Moff Tarkin', 'subtitle' => 'Tyrant of the Outer Rim',
    'type' => 'Leader', 'arena' => 'Space', 'cost' => 9, 'power' => 2, 'hp' => 12,
    'aspect' => ['Vigilance', 'Villainy'], 'trait' => ['Imperial', 'Official'],
    'text' => 'Ignore the aspect penalties on upgrades with Fortify you play.',
    'epicAction' => 'Epic Action: If you control 9 or more resources, deploy this leader.',
    'deployText' => "Ignore the aspect penalties on upgrades with Fortify you play.\nWhen the regroup phase starts: You may defeat a base with 10 or less remaining HP.",
    'unique' => true, 'rarity' => 'Rare', 'set' => 'HMW',
    'imageUrl' => 'https://example.invalid/004.png',
    'imageUrlBack' => 'https://example.invalid/004-back.png',
    'leaderUnitTitle' => 'The Death Star', 'leaderUnitSubtitle' => 'Icon of Tyranny',
    'leaderUnitTrait' => ['Imperial', 'Vehicle', 'Capital Ship'],
    'leaderUnitArena' => 'Space', 'leaderUnitType' => 'Unit',
  ],
];
PHP);

// --- load ---
$mocks = SWUSimLoadMockCards($fixture);
check(count($mocks) === 1, 'loaded one mock');
check(isset($mocks['HMW_004']), 'keyed by SET_NNN');

// --- adapt to API shape ---
$row = SWUSimMockToImportRow('HMW_004', $mocks['HMW_004']);
check($row['id'] === 'HMW_004', 'row id is the CardID');
check($row['type']['name'] === 'Leader', 'type is a relation object with name');
check($row['arenas'][0]['name'] === 'Space', 'arenas is a relation list');
check($row['traits'][1]['name'] === 'Official', 'traits is a relation list');
check($row['aspects'][0]['name'] === 'Vigilance', 'aspects is a relation list');
check($row['set']['abbreviation'] === 'HMW', 'set carries abbreviation');
check($row['cardNumber'] === 4, 'cardNumber parsed from the CardID');
check($row['deployBox'] !== '', 'deployText maps onto deployBox');
check($row['leaderUnitTitle'] === 'The Death Star', 'leader unit override passes through');
check($row['documentId'] === '', 'mocks carry no documentId');

// --- merge: mock-only card is appended ---
$cards = [];
$res = SWUSimMergeMockCards($cards, false, $fixture);
check($res['added'] === ['HMW_004'], 'mock-only card added');
check($res['superseded'] === [], 'nothing superseded');
check(count($cards) === 1, 'card array grew by one');

// --- merge: official data wins ---
$cards = [['id' => 'HMW_004', 'title' => 'Official Tarkin']];
$res = SWUSimMergeMockCards($cards, false, $fixture);
check($res['added'] === [], 'no mock added when official exists');
check($res['superseded'] === ['HMW_004'], 'reported as superseded');
check(count($cards) === 1, 'card array unchanged');
check($cards[0]['title'] === 'Official Tarkin', 'official row untouched');

// --- merge: object mode for the generator ---
$cards = [];
SWUSimMergeMockCards($cards, true, $fixture);
check(is_object($cards[0]), 'object mode yields objects');
check($cards[0]->type->name === 'Leader', 'nested relations are objects too');
check($cards[0]->id === 'HMW_004', 'object row keeps its id');

// --- the generator merges AFTER the cache is written, so the cache stays pure API data ---
$gen = file_get_contents(__DIR__ . '/../../../zzCardCodeGenerator.php');
check(strpos($gen, 'SWUSimMergeMockCards') !== false, 'generator calls SWUSimMergeMockCards');
check(strpos($gen, "require_once __DIR__ . '/SWUSim/DevTools/MockCardMerge.php'") !== false,
      'generator requires MockCardMerge');
$mergePos = strpos($gen, 'SWUSimMergeMockCards');
$cachePos = strpos($gen, 'file_put_contents($cacheFile');
check($cachePos === false || $mergePos > $cachePos,
      'mock merge runs after the cache write so mocks never enter the cache');
check(strpos($gen, '"HMW"') !== false || strpos($gen, "'HMW'") !== false, 'HMW in validSets');
check(strpos($gen, '"IC27"') !== false || strpos($gen, "'IC27'") !== false, 'IC27 in validSets');

// --- keyword processor merges mocks and knows Fortify ---
$kw = file_get_contents(__DIR__ . '/../../../Data/ProcessKeywordsSWU.php');
check(strpos($kw, 'SWUSimMergeMockCards') !== false, 'keyword processor merges mocks');
check(preg_match("/\\\$booleanKeywords\s*=\s*\[[^\]]*'Fortify'/s", $kw) === 1,
      'Fortify registered as a boolean keyword');

// --- set registration, ordered after ASH ---
$allSets = require __DIR__ . '/../../../AppCore/SWU/AllSets.php';
check(isset($allSets['HMW']), 'HMW registered');
check(isset($allSets['IC27']), 'IC27 registered');
check($allSets['HMW'] === $allSets['ASH'] + 1, 'HMW ordered directly after ASH');
check($allSets['IC27'] === $allSets['HMW'] + 1, 'IC27 ordered directly after HMW');

$previewSets = require __DIR__ . '/../../../AppCore/SWU/PreviewSets.php';
check($previewSets === ['HMW', 'IC27'], 'preview-only sets declared');

// --- leader-unit-side dimensions exist and default to null ---
require_once __DIR__ . '/../../GeneratedCode/GeneratedCardDictionaries.php';
foreach (['CardLeaderUnitTitle', 'CardLeaderUnitSubtitle', 'CardLeaderUnitTrait',
          'CardLeaderUnitArena', 'CardLeaderUnitType'] as $fn) {
    check(function_exists($fn), "accessor $fn generated");
}
// An existing leader has no override -> null, so no current behavior changes.
check(CardLeaderUnitTitle('SOR_001') === null, 'existing leader has no title override');
check(CardLeaderUnitArena('SOR_001') === null, 'existing leader has no arena override');
check(CardTitle('SOR_001') === 'Director Krennic', 'existing leader row unchanged');

// --- mock art is fetched under a mock_ filename and exposed to the client ---
check(strpos($gen, "'mock_'") !== false || strpos($gen, '"mock_"') !== false,
      'generator builds mock_ image names');
check(strpos($gen, 'MockCardImageIDs') !== false, 'generator emits MockCardImageIDs');

// --- client-side image resolution must survive the "<CardID>_back" display id ---
// A DEPLOYED leader renders its unit side as "<CardID>_back" (SWUArenaDisplayCardID), so a
// resolver that only matches bare CardIDs leaves the arena unit with broken art.
$jsInc = file_get_contents(__DIR__ . '/../../../Core/jsInclude.js');
check(strpos($jsInc, 'function resolveCardImageID') !== false, 'resolver exists');
check(strpos($jsInc, '_back') !== false, 'resolver handles the _back display suffix');

// Card() is the single choke point every zone renders through; the server-side layout passes only
// a folder, never a filename, so resolution MUST happen there.
$uiLib = glob(__DIR__ . '/../../../Core/UILibraries20*.js');
check(count($uiLib) === 1, 'exactly one UILibraries bundle');
$ui = file_get_contents($uiLib[0]);
check(strpos($ui, 'resolveCardImageID(cardNumber)') !== false,
      'Card() resolves the mock image id');

// jsInclude.js now carries load-bearing render logic, so it needs the cache-buster its siblings have.
$nextTurn = file_get_contents(__DIR__ . '/../../../NextTurn.php');
check(preg_match('#jsInclude\.js\?v=#', $nextTurn) === 1, 'jsInclude.js is cache-busted');

unlink($fixture);
echo "OK\n";
