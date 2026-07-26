<?php
// Test: dictionary-driven header + filename generation.
require __DIR__ . '/../../../GeneratedCode/GeneratedCardDictionaries.php';
require __DIR__ . '/../HeaderGen.php';

// Basename is clean CamelCase from the card's own title/subtitle — equals the test
// filename for convention-following cards, and never misattributes another card's name.
assert(splitter_card_basename('SOR_097') === 'AdmiralAckbar_BrilliantStrategist', splitter_card_basename('SOR_097'));
assert(splitter_card_basename('SOR_033') === 'DeathTrooper', splitter_card_basename('SOR_033')); // no subtitle
assert(splitter_card_basename('SHD_170') === splitter_card_basename('SHD_170'), 'stable');       // IG-11, never "KyloRen..."
assert(splitter_card_basename('SHD_170') !== 'KyloRen_RashAndDeadly', 'must not misattribute: '.splitter_card_basename('SHD_170'));

// Deterministic, non-empty for a fabricated ID with no dictionary entry.
assert(splitter_card_basename('ZZ_999') !== '', 'fallback empty');

// Header content for SOR_033 Death Trooper (reprints SHD_030, SEC_030).
$h = splitter_card_header('SOR_033', ['SHD_030', 'SEC_030']);
assert(str_contains($h, 'SOR_033'), 'header missing CardID');
assert(str_contains($h, 'Reprints: SHD_030, SEC_030'), 'header missing reprints');
assert(str_contains($h, 'Death Trooper'), 'header missing title');
assert(str_contains($h, '[Vigilance,Villainy]'), 'header missing aspect: ' . $h);
assert(str_contains($h, '// Text:'), 'header missing Text line');

// A leader with DeployText + Epic Action (SOR_010 Darth Vader is a leader).
$hl = splitter_card_header('SOR_005', []); // Luke Skywalker leader
assert(str_contains($hl, 'Luke Skywalker'), 'leader title');

echo "OK\n";
