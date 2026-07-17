<?php
// http://localhost:3100/TCGEngine/DevTools/tdd-regression/test_swudeck_client_format_data.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php'; // $titleData, UUIDLookup
include_once __DIR__ . '/../../SWUDeck/Custom/DeckFormats.php';

$checks = [];

// Premier: fixed curated set list, no bans.
$premier = SWUDeckClientFormatData('premier');
$checks['premier legalSets matches config'] = $premier['legalSets'] === ['JTL', 'LOF', 'SEC', 'IBH', 'LAW', 'ASH'];
$checks['premier has no banned UUIDs'] = $premier['bannedUUIDs'] === [];

// Eternal: every set legal, JTL_140 + JTL_170 banned — both must resolve to a UUID.
$eternal = SWUDeckClientFormatData('eternal');
$checks['eternal legalSets includes SOR'] = in_array('SOR', $eternal['legalSets'], true);
$checks['eternal legalSets includes ASH'] = in_array('ASH', $eternal['legalSets'], true);
$checks['eternal has exactly 2 banned UUIDs'] = count($eternal['bannedUUIDs']) === 2;
$expectedBannedUUIDs = [UUIDLookup('JTL_140'), UUIDLookup('JTL_170')];
sort($expectedBannedUUIDs);
$actualBannedUUIDs = $eternal['bannedUUIDs'];
sort($actualBannedUUIDs);
$checks['eternal banned UUIDs match JTL_140/JTL_170'] = $actualBannedUUIDs === $expectedBannedUUIDs;

// Open and Twin Suns: no bans configured today.
$open = SWUDeckClientFormatData('open');
$checks['open has no banned UUIDs'] = $open['bannedUUIDs'] === [];
$twinsuns = SWUDeckClientFormatData('twinsuns');
$checks['twinsuns has no banned UUIDs'] = $twinsuns['bannedUUIDs'] === [];

// Unknown format: degrades to empty/safe output, doesn't throw.
$unknown = SWUDeckClientFormatData('nonsense');
$checks['unknown format returns empty legalSets'] = $unknown['legalSets'] === [];
$checks['unknown format returns empty bannedUUIDs'] = $unknown['bannedUUIDs'] === [];

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
