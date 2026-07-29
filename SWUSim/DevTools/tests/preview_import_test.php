<?php
// Normalization, enum decoding and reprint classification — all against saved fixtures.
// NEVER hits the network: a preview being errata'd must not break the suite.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } }
function fixture($name) { return json_decode(file_get_contents(__DIR__ . "/fixtures/$name.json"), true); }

require __DIR__ . '/../PreviewImport.php';

// --- link parsing ---
$p = SWUPreviewParseLink('https://swudb.com/card/HMW/004');
check($p === ['set' => 'HMW', 'number' => '004'], 'parses a full card link');
$p = SWUPreviewParseLink('HMW/4');
check($p === ['set' => 'HMW', 'number' => '004'], 'parses shorthand and zero-pads');
check(SWUPreviewParseLink('nonsense') === null, 'rejects junk');
// TS26 numbers its cards with TWO digits — padding to 3 returns an empty record upstream.
check(SWUPreviewParseLink('https://swudb.com/card/TS26/9') === ['set' => 'TS26', 'number' => '09'],
      'double-digit set pads to 2');
check(SWUPreviewPadNumber('TS26', '09') === '09', 'already-padded TS26 number kept');
check(SWUPreviewPadNumber('HMW', '4') === '004', 'normal set pads to 3');
check(SWUPreviewPadNumber('HMW', '095') === '095', 'already-padded normal number kept');

// --- markup normalization ---
check(SWUPreviewNormalizeText('{p}Hello{/p}') === 'Hello', 'strips paragraph tags');
check(SWUPreviewNormalizeText('{p}{b}On Attack:{/b} Do it.{/p}') === 'On Attack: Do it.',
      'strips bold');
check(SWUPreviewNormalizeText('{p}Choose a non-{vehicle} unit.{/p}') === 'Choose a non-Vehicle unit.',
      'icon tag becomes its word');
check(SWUPreviewNormalizeText('{p}{keyword}Fortify{/keyword} {i}(Attach this to your base.){/i}{/p}')
      === 'Fortify (Attach this to your base.)', 'keyword unwrapped, reminder kept');
check(SWUPreviewNormalizeText('{p}Action [{T}]: Draw.{/p}') === 'Action [Exhaust]: Draw.',
      'exhaust icon');
check(SWUPreviewNormalizeText('{p}It costs {R5} less.{/p}') === 'It costs [5 resources] less.',
      'plural resource icon');
check(SWUPreviewNormalizeText('{p}It costs {R1} less.{/p}') === 'It costs [1 resource] less.',
      'singular resource icon');
check(SWUPreviewNormalizeText("{p}One{/p}\n{p}Two{/p}") === "One\nTwo", 'paragraphs newline-joined');
// The label is NOT in the source text — the paragraph class implies it.
check(SWUPreviewNormalizeText('{p-epic-action}If you control 7 resources, deploy this leader.{/p}')
      === 'Epic Action: If you control 7 resources, deploy this leader.',
      'epic-action paragraph gains its label');

// --- HMW_095: Fortify upgrade ---
$m = SWUPreviewToMock(fixture('hmw_095'));
check($m['title'] === 'Carbonite Chamber', 'title');
check($m['type'] === 'Upgrade', 'cardType enum decoded');
check($m['trait'] === ['Fortification'], 'traits');
check(strpos($m['text'], 'Fortify') === 0, 'Fortify at line-start so it parses as innate');

// --- HMW_004: double-sided leader ---
$m = SWUPreviewToMock(fixture('hmw_004'));
check($m['title'] === 'Grand Moff Tarkin', 'leader title');
check($m['subtitle'] === 'Tyrant of the Outer Rim', 'leader subtitle');
check($m['type'] === 'Leader', 'leader type');
check($m['arena'] === 'Space', 'arena enum decoded');
check($m['cost'] === 9 && $m['power'] === 2 && $m['hp'] === 12, 'stats');
check($m['aspect'] === ['Vigilance', 'Villainy'], 'aspect enums decoded');
check($m['deployText'] !== '', 'back text becomes deployText');
check($m['imageUrl'] !== '' && strpos($m['imageUrl'], '/cards/HMW/004.png') !== false, 'front art URL');
check($m['imageUrlBack'] !== '', 'back art URL');
// The source does NOT carry the deployed side's name/traits — the human fills these in.
check(($m['leaderUnitTitle'] ?? '') === '', 'leader unit title left blank for review');

// --- ASH_023: a BASE keeps its location trait ---
// The official API omits traits for every base; CardTraitSupplement.php backfills them, so a
// mocked base carrying "Seatos" matches what it will have after release rather than losing it.
$m = SWUPreviewToMock(fixture('ash_023'));
check($m['type'] === 'Base', 'base type decoded');
check($m['trait'] === ['Seatos'], 'base keeps its location trait');
check($m['arena'] === '', 'bases have no arena');

// --- classification ---
$c = SWUPreviewClassify(fixture('ic27_097'));
check($c['kind'] === 'reprint', 'IC27_097 is a reprint');
check($c['canonical'] === 'SOR_128', 'folds to its earliest printing');
$c = SWUPreviewClassify(fixture('ic27_001'));
check($c['kind'] === 'new', 'IC27_001 is a new card');
check($c['canonical'] === null, 'new cards have no canonical');
$c = SWUPreviewClassify(fixture('hmw_004'));
check($c['kind'] === 'new', 'HMW_004 is a new card');

// --- rarity comes from the matching alternativePrintings row, not the top level ---
$rec = fixture('ic27_001');
check(($rec['rarity'] ?? null) === null, 'fixture has no usable top-level rarity');
$m = SWUPreviewToMock($rec);
check(($m['rarity'] ?? '') !== '', 'rarity resolved from alternativePrintings');

// --- IC27_001: epic action split out of the front text ---
$m = SWUPreviewToMock(fixture('ic27_001'));
check($m['type'] === 'Leader', 'IC27_001 is a leader');
check(strpos($m['epicAction'], 'Epic Action: ') === 0, 'epic action separated and labelled');
check(strpos($m['text'], 'Epic Action') === false, 'front text no longer holds the epic action');
check(strpos($m['epicAction'], '(Flip him') !== false, 'epic action keeps its reminder text');
// Cost tokens inside an existing [...] list stay BARE, matching '[1 resource, Exhaust]' in the
// real dictionaries — they are not each individually bracketed.
check(strpos($m['text'], 'Action [1 resource, Exhaust, defeat a friendly unit]:') === 0,
      'cost list decoded bare inside its brackets');

echo "OK\n";
