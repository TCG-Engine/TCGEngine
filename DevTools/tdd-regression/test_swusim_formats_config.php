<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_formats_config.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../SWUSim/Formats.php';

$checks = [];

// Premier baseline unchanged.
$premier = SWUGetFormat('premier');
$checks['premier sets'] = $premier['legalSets'] === ['JTL','LOF','SEC','IBH','LAW','ASH'];
$checks['premier copyEx'] = ($premier['copyExceptions']['JTL_256'] ?? null) === 15;

// '*' resolves to all printed sets (includes SOR and ASH).
$eternalSets = SWUFormatLegalSets('eternal');
$checks['eternal has SOR'] = in_array('SOR', $eternalSets, true);
$checks['eternal has ASH'] = in_array('ASH', $eternalSets, true);
$checks['open same as eternal'] = SWUFormatLegalSets('open') === $eternalSets;

// Open has no bans; defaults fill missing keys.
$open = SWUGetFormat('open');
$checks['open no bans'] = $open['banned'] === [];
$checks['open default modifiers'] = $open['deckSizeModifiers'] === [];

// Disable-not-delete: preview is disabled by default.
$listed = SWUListFormats();
$checks['preview hidden from list'] = !array_key_exists('preview', $listed);
$checks['preview still resolvable'] = SWUGetFormat('preview') !== null;
$checks['enabled formats listed'] = array_key_exists('premier', $listed)
                                 && array_key_exists('eternal', $listed)
                                 && array_key_exists('open', $listed);

// Unknown format is null; queue types resolve.
$checks['unknown format null'] = SWUGetFormat('nope') === null;
$checks['bo3 bestOf 3'] = (SWUGetQueueType('bo3')['bestOf'] ?? null) === 3;
$checks['bo3 sideboard on'] = (SWUGetQueueType('bo3')['sideboard'] ?? null) === true;
$checks['bo1 sideboard off'] = (SWUGetQueueType('bo1')['sideboard'] ?? null) === false;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
