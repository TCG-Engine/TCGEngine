<?php

// A Hellbreak variant printing (borderless 2xx, alt art 4xx) is the SAME card as its base printing,
// and must resolve to the base card's ID everywhere an ID comes in from outside.
//
// 2026-09-15: the workbook importer expanded a row like "#062 / #262" into two unrelated cards.
// Reviewed data then lived under whichever printing we happened to transcribe (DOT_262, DOT_455),
// while HellbreakHub and the rules team use the base number (DOT_062, DOT_167). The ability database
// already held copy-pasted abilities on DOT_245 / DOT_436 / DOT_437, identical to their base cards,
// and bound to drift apart the first time one was edited.

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');
chdir(dirname(__DIR__, 2));

$failures = 0;
$checks = 0;
$check = function($condition, string $message) use (&$failures, &$checks): void {
    ++$checks;
    $ok = boolval($condition);
    echo ($ok ? 'PASS' : 'FAIL') . ': ' . $message . PHP_EOL;
    if(!$ok) ++$failures;
};

$tmpRoot = sys_get_temp_dir() . '/hellbreak-variants-' . getmypid();
$rrmdir = function(string $dir) use (&$rrmdir): void {
    if(!is_dir($dir)) return;
    foreach(array_diff(scandir($dir), ['.', '..']) as $entry) {
        $path = $dir . '/' . $entry;
        is_dir($path) ? $rrmdir($path) : unlink($path);
    }
    rmdir($dir);
};

// ---------------------------------------------------------------------------
// Importer: the workbook marks a variant printing by a name suffix
// ---------------------------------------------------------------------------
include_once './DevTools/Hellbreak/import-workbook.php';

$rows = [
    1 => ['Collector Number', 'Name', 'Type', 'Aspect', 'Loyalty', 'Cost'],
    2 => ['#062', 'Hypnotic Gaze', 'Event', 'Cursed', '3', '5'],
    3 => ['#262', 'Hypnotic Gaze  (Borderless)', 'Event', 'Cursed', '3', '5'],
    4 => ['#045', 'Dracula, Ancient Vampire', 'Minion', 'Cursed', '3', '9'],
    5 => ['#429', 'Dracula, Ancient Vampire (Poster)', 'Minion', 'Cursed', '3', '9'],
    // "#114 / #115" means "one of these two numbers": two guesses for one unrevealed card, which
    // HellbreakHub later settled as 115. It is NOT a base + variant pair.
    6 => ['#114 / #115', 'Vicious Cur', 'Minion', 'Feral', '1', '1'],
    7 => ['DOT 167', 'Dracula, Blood is Life', 'Minion', 'Revenant', '2', '6'],
    8 => ['DOT 455', 'Dracula, Blood Is Life', 'Minion', 'Revenant', '2', '6'],
    9 => ['#300', 'Twin (Borderless)', 'Event', 'Void', '1', '1'],
    10 => ['#030', 'Twin', 'Event', 'Void', '1', '1'],
    11 => ['#031', 'Twin', 'Event', 'Void', '1', '1'],
];
[$cards] = normalizeRows($rows);
$warnings = [];
$linked = linkVariantPrintingsByName($cards, $warnings);
$check(($cards['DOT_262']['baseCard'] ?? null) === 'DOT_062', 'a "(Borderless)" printing links to the card with the same name');
$check(($cards['DOT_429']['baseCard'] ?? null) === 'DOT_045', 'a "(Poster)" printing links the same way');
$check(($cards['DOT_062']['baseCard'] ?? null) === '', 'the base printing has no base card of its own');
$check(($cards['DOT_114']['baseCard'] ?? null) === '' && ($cards['DOT_115']['baseCard'] ?? null) === '',
    'an uncertain "114 / 115" row links neither number to the other');
$check(($cards['DOT_455']['baseCard'] ?? null) === '', 'a name without a variant suffix is never guessed from its name alone');
$check(($cards['DOT_300']['baseCard'] ?? null) === '' && (bool)array_filter($warnings, fn($w) => str_contains($w, 'DOT_300')),
    'a variant whose name matches two cards is reported and left unlinked');
$check($linked === 2, 'two variants were linked by name');

// Real workbook: DOT_455 ("Dracula, Blood Is Life", no suffix) is already a variant of DOT_167 via
// the reviewed baseCards list, so the borderless DOT_367 must pick DOT_167, not see two candidates.
$dracula = [
    'DOT_167' => ['id' => 'DOT_167', 'name' => 'Dracula, Blood is Life', 'baseCard' => ''],
    'DOT_455' => ['id' => 'DOT_455', 'name' => 'Dracula, Blood Is Life', 'baseCard' => 'DOT_167'],
    'DOT_367' => ['id' => 'DOT_367', 'name' => 'Dracula, Blood is Life (Borderless)', 'baseCard' => ''],
];
$warnings = [];
linkVariantPrintingsByName($dracula, $warnings);
$check($dracula['DOT_367']['baseCard'] === 'DOT_167' && $warnings === [], 'a card that is already a variant is never offered as a base');

// Reviewed data supplies pairs the workbook lists on separate rows.
@mkdir($tmpRoot . '/HellbreakSim/CardData', 0777, true);
file_put_contents($tmpRoot . '/HellbreakSim/CardData/ReviewedCardFaces.json', json_encode([
    'baseCards' => ['DOT_455' => 'DOT_167', 'DOT_999' => 'DOT_001'],
    'cards' => [
        'DOT_062' => ['combat' => 0, 'traits' => ['Spell'], 'text' => 'Lurking Action — Take control of a minion that costs 3 blood or less.'],
        'DOT_167' => ['combat' => 3, 'health' => 6, 'traits' => ['Undead', 'Vampire'], 'text' => 'Attack or Moved — You may deal 1 damage to a minion here.'],
    ],
]));
$warnings = [];
applyReviewedCardFaces($cards, $tmpRoot, $warnings);
$check(($cards['DOT_455']['baseCard'] ?? null) === 'DOT_167', 'a reviewed baseCards entry links DOT_455 to DOT_167');
$check((bool)array_filter($warnings, fn($w) => str_contains($w, 'DOT_999')), 'a baseCards entry for an unknown card is reported, not silently applied');

// Variants carry their base card's gameplay data; their own printing details stay.
$warnings = [];
$report = applyVariantBaseCards($cards, $warnings);
$check(($cards['DOT_455']['text'] ?? '') === $cards['DOT_167']['text'] && $cards['DOT_455']['health'] === 6,
    'DOT_455 inherits the base card\'s reviewed text and stats');
$check(($cards['DOT_262']['traits'] ?? '') === 'Spell', 'DOT_262 inherits the base card\'s traits');
$check($cards['DOT_455']['collectorNumber'] !== $cards['DOT_167']['collectorNumber'], 'the variant keeps its own collector number');
$check(($report['variants'] ?? 0) === 3, 'the import report counts three variant printings');
$check(buildCardBaseMap($cards) === ['DOT_262' => 'DOT_062', 'DOT_429' => 'DOT_045', 'DOT_455' => 'DOT_167'],
    'the base map lists exactly the three variants');

// A variant whose base is missing is reported and left unlinked rather than pointing nowhere.
$orphan = ['DOT_430' => ['id' => 'DOT_430', 'name' => 'Orphan', 'baseCard' => 'DOT_777']];
$warnings = [];
applyVariantBaseCards($orphan, $warnings);
$check($orphan['DOT_430']['baseCard'] === '' && count($warnings) === 1, 'a variant of a card that does not exist is unlinked and reported');

// Card art: a base card with no art borrows its variant's, so a converted deck still shows the card.
@mkdir($tmpRoot . '/target/WebpImages', 0777, true);
@mkdir($tmpRoot . '/target/concat', 0777, true);
@mkdir($tmpRoot . '/target/crops', 0777, true);
file_put_contents($tmpRoot . '/target/WebpImages/DOT_262.webp', str_repeat('x', 9000));
file_put_contents($tmpRoot . '/target/concat/DOT_262.webp', str_repeat('y', 9000));
file_put_contents($tmpRoot . '/target/crops/DOT_262_cropped.png', str_repeat('z', 9000));
$copied = inheritVariantImages($cards, $tmpRoot . '/target');
$check(is_file($tmpRoot . '/target/concat/DOT_062.webp') && is_file($tmpRoot . '/target/WebpImages/DOT_062.webp')
    && is_file($tmpRoot . '/target/crops/DOT_062_cropped.png'), 'DOT_062 borrows DOT_262\'s art in all three image folders');
$check($copied === 1, 'exactly one base card needed borrowed art');
file_put_contents($tmpRoot . '/target/concat/DOT_167.webp', str_repeat('b', 9000));
inheritVariantImages($cards, $tmpRoot . '/target');
$check(file_get_contents($tmpRoot . '/target/concat/DOT_167.webp') === str_repeat('b', 9000), 'a base card\'s own art is never overwritten');

// ---------------------------------------------------------------------------
// Resolver shared by CardEditor, the hosted Card Code Service, and deck import
// ---------------------------------------------------------------------------
include_once './Core/CardBaseMap.php';

CardBaseMapSetOverride('HellbreakSim', ['DOT_262' => 'DOT_062', 'DOT_455' => 'DOT_167']);
$check(ResolveBaseCardID('HellbreakSim', 'DOT_262') === 'DOT_062', 'DOT_262 resolves to DOT_062');
$check(ResolveBaseCardID('HellbreakSim', 'dot_455') === 'DOT_167', 'lookups ignore case');
$check(ResolveBaseCardID('HellbreakSim', 'DOT_062') === 'DOT_062', 'a base card resolves to itself');
$check(ResolveBaseCardID('HellbreakSim', 'DOT_001') === 'DOT_001', 'a card with no variants passes through unchanged');
$resolution = CardBaseResolution('HellbreakSim', 'DOT_262');
$check($resolution === ['cardId' => 'DOT_062', 'requestedCardId' => 'DOT_262', 'isVariant' => true], 'the resolution reports both IDs');
$check(ResolveBaseCardID('AzukiSim', 'DOT_262') === 'DOT_262', 'a root without a base map is untouched');
$check(ResolveBaseCardID('../HellbreakSim', 'DOT_262') === 'DOT_262', 'a root name with path characters never reads a file');

// The map file the importer writes is what the resolver reads when no override is set.
CardBaseMapSetOverride('HellbreakSim', null);
@mkdir($tmpRoot . '/engine/HellbreakSim/GeneratedCode', 0777, true);
writeCardBaseMap($tmpRoot . '/engine/HellbreakSim/GeneratedCode', buildCardBaseMap($cards));
CardBaseMapSetEngineRoot($tmpRoot . '/engine');
$check(ResolveBaseCardID('HellbreakSim', 'DOT_455') === 'DOT_167', 'the resolver reads the CardBaseMap.json the importer wrote');
CardBaseMapSetEngineRoot(null);

// ---------------------------------------------------------------------------
// Deck import converts variant IDs so games only hold base IDs
// ---------------------------------------------------------------------------
include_once './HellbreakSim/Custom/DeckImport.php';
CardBaseMapSetOverride('HellbreakSim', ['DOT_262' => 'DOT_062']);
$deckFile = $tmpRoot . '/deck.txt';
$deckLines = ['header', 'header', '1', 'DOT_001', '0', '2', 'DOT_016', 'DOT_015', '0', '2', 'DOT_262 1', 'DOT_052 1', '0'];
file_put_contents($deckFile, implode("\n", $deckLines) . "\n");
$parsed = HellbreakParseDeckGamestateFile($deckFile);
$check(($parsed['success'] ?? false) === true, 'the saved deck still parses');
$check(($parsed['mainDeck'] ?? []) === ['DOT_062', 'DOT_052'], 'a saved DOT_262 enters the game as DOT_062');
CardBaseMapSetOverride('HellbreakSim', null);

$rrmdir($tmpRoot);

echo PHP_EOL . ($failures === 0 ? "GREEN ({$checks} checks)" : "RED ({$failures} of {$checks} failed)") . PHP_EOL;
exit($failures === 0 ? 0 : 1);
