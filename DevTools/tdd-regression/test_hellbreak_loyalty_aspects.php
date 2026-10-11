<?php

// Hellbreak loyalty must be stored per aspect, so a card that asks for more than one aspect
// ("1 Cursed, 1 Feral") stays playable.
//
// 2026-09-15: the workbook importer kept loyalty as ONE integer (integerValue() grabs the first
// number in the cell) and aspect as a comma-joined string. HellbreakCardLoyalty() then paired them
// as [$aspect => $count], so a two-aspect card became ['Cursed, Feral' => 1]: a requirement for an
// aspect literally named "Cursed, Feral" that no vault can ever provide. The card was silently
// unplayable. No DOT card has mixed loyalty yet, but the rulebook describes loyalty as "the small
// aspect icon(s)", so the data has to carry a per-aspect map.

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

// ---------------------------------------------------------------------------
// Importer: loyalty cell + aspect column -> per-aspect map
// ---------------------------------------------------------------------------
include_once './DevTools/Hellbreak/import-workbook.php';

$check(function_exists('loyaltyAspectCounts'), 'importer exposes loyaltyAspectCounts()');
if(function_exists('loyaltyAspectCounts')) {
    $warnings = [];
    $check(loyaltyAspectCounts('2', 'Feral', 'Emmett', $warnings) === ['Feral' => 2],
        'a bare count with one aspect becomes {Feral: 2}');
    $check(loyaltyAspectCounts('1 Cursed, 1 Feral', 'Cursed, Feral', 'Mixed', $warnings) === ['Cursed' => 1, 'Feral' => 1],
        'explicit "1 Cursed, 1 Feral" becomes one entry per aspect');
    $check(loyaltyAspectCounts('cursed 1 / FERAL 2', '', 'Mixed', $warnings) === ['Cursed' => 1, 'Feral' => 2],
        'aspect-then-count pairs parse too, and aspect names are normalised to engine casing');
    $check(loyaltyAspectCounts('', 'Cursed', 'Dracula', $warnings) === [],
        'an empty loyalty cell means no loyalty (monsters, locations)');
    $check(loyaltyAspectCounts('0', 'Cursed', 'Bat', $warnings) === [],
        'a zero count means no loyalty');
    $check($warnings === [], 'none of the unambiguous cells raised a warning');

    $warnings = [];
    $ambiguous = loyaltyAspectCounts('2', 'Cursed, Feral', 'Two Aspect Card', $warnings);
    $check(count($warnings) === 1 && str_contains($warnings[0], 'Two Aspect Card'),
        'a bare count with two aspects is flagged for review instead of guessed silently');
    $check(!array_filter(array_keys($ambiguous), fn($aspect) => str_contains((string)$aspect, ',')),
        'the ambiguous case never invents an aspect named "Cursed, Feral"');
}

// normalizeRows writes both the total (search/sort) and the per-aspect map (engine).
$rows = [
    1 => ['Collector Number', 'Name', 'Type', 'Aspect', 'Loyalty', 'Cost'],
    2 => ['DOT 124', 'Emmett', 'Minion', 'Feral', '2', '6'],
    3 => ['DOT 900', 'Mixed Test Card', 'Minion', 'Cursed, Feral', '1 Cursed, 1 Feral', '2'],
    4 => ['DOT 001', 'Dracula', 'Monster', 'Cursed', '', ''],
];
[$cards] = normalizeRows($rows);
$check(($cards['DOT_124']['loyalty'] ?? null) === 2, 'Emmett keeps loyalty 2 as the searchable total');
$check(json_decode((string)($cards['DOT_124']['loyaltyAspects'] ?? ''), true) === ['Feral' => 2],
    'Emmett loyaltyAspects is {"Feral":2}');
$check(($cards['DOT_900']['loyalty'] ?? null) === 2, 'the mixed card total is 2 (1 + 1)');
$check(json_decode((string)($cards['DOT_900']['loyaltyAspects'] ?? ''), true) === ['Cursed' => 1, 'Feral' => 1],
    'the mixed card loyaltyAspects is {"Cursed":1,"Feral":1}');
$check(($cards['DOT_001']['loyaltyAspects'] ?? null) === '{}',
    'a monster writes an explicit empty map, not a blank that would fall back to the old path');

// A reviewed loyalty override (for cells the workbook cannot express) wins over the workbook.
$tmpRoot = sys_get_temp_dir() . '/hellbreak-loyalty-' . getmypid();
@mkdir($tmpRoot . '/HellbreakSim/CardData', 0777, true);
file_put_contents($tmpRoot . '/HellbreakSim/CardData/ReviewedCardFaces.json', json_encode([
    'cards' => ['DOT_900' => ['loyalty' => ['Cursed' => 1, 'Void' => 1]]],
]));
$warnings = [];
applyReviewedCardFaces($cards, $tmpRoot, $warnings);
$check(json_decode((string)$cards['DOT_900']['loyaltyAspects'], true) === ['Cursed' => 1, 'Void' => 1],
    'a reviewed loyalty map overrides the workbook value');
$check($cards['DOT_900']['loyalty'] === 2, 'the reviewed override also refreshes the searchable total');
@unlink($tmpRoot . '/HellbreakSim/CardData/ReviewedCardFaces.json');
@rmdir($tmpRoot . '/HellbreakSim/CardData');
@rmdir($tmpRoot . '/HellbreakSim');
@rmdir($tmpRoot);

// ---------------------------------------------------------------------------
// Engine: HellbreakCardLoyalty reads the map, and the legacy fallback never invents an aspect
// ---------------------------------------------------------------------------
// Stub the generated dictionary. Unknown IDs return '' like the generated functions do.
$GLOBALS['__loyaltyStub'] = [
    'TEST_MIXED'  => ['loyaltyAspects' => '{"Cursed":1,"Feral":1}', 'loyalty' => 2, 'aspect' => 'Cursed, Feral'],
    'TEST_NONE'   => ['loyaltyAspects' => '{}', 'loyalty' => 0, 'aspect' => 'Cursed'],
    'TEST_LEGACY' => ['loyaltyAspects' => '', 'loyalty' => 2, 'aspect' => 'Feral'],
    'TEST_LEGACY_MULTI' => ['loyaltyAspects' => '', 'loyalty' => 1, 'aspect' => 'Cursed, Feral'],
];
function CardLoyaltyAspects($cardID) { return $GLOBALS['__loyaltyStub'][$cardID]['loyaltyAspects'] ?? ''; }
function CardLoyalty($cardID) { return $GLOBALS['__loyaltyStub'][$cardID]['loyalty'] ?? ''; }
function CardAspect($cardID) { return $GLOBALS['__loyaltyStub'][$cardID]['aspect'] ?? ''; }

include_once './HellbreakSim/Custom/GameLogic.php';

$mixed = HellbreakCardLoyalty('TEST_MIXED');
$check($mixed === ['Cursed' => 1, 'Feral' => 1], 'the engine reads a two-aspect loyalty map');
$check(HellbreakMeetsLoyalty(['Cursed' => 1, 'Feral' => 1], $mixed), 'one Cursed + one Feral in the vault meets it');
$check(!HellbreakMeetsLoyalty(['Cursed' => 2], $mixed), 'two Cursed and no Feral does not');
$check(HellbreakCardLoyalty('TEST_NONE') === [], 'an explicit empty map means no loyalty');
$check(HellbreakCardLoyalty('TEST_LEGACY') === ['Feral' => 2],
    'data generated before loyaltyAspects existed still works through the count + aspect path');
$legacyMulti = HellbreakCardLoyalty('TEST_LEGACY_MULTI');
$check(!array_filter(array_keys($legacyMulti), fn($aspect) => str_contains((string)$aspect, ',')),
    'the legacy path never produces an aspect named "Cursed, Feral"');

echo PHP_EOL . ($failures === 0 ? "GREEN ({$checks} checks)" : "RED ({$failures} of {$checks} failed)") . PHP_EOL;
exit($failures === 0 ? 0 : 1);
