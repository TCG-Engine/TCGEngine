<?php
// http://localhost:3100/TCGEngine/DevTools/tdd-regression/test_swudeck_format_catalog.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../SWUDeck/Custom/DeckFormats.php';

$checks = [];

$catalog = SWUDeckBuildableFormats();
$checks['exactly 4 formats'] = count($catalog) === 4;
$checks['order is premier,eternal,twinsuns,open'] = array_keys($catalog) === ['premier', 'eternal', 'twinsuns', 'open'];
$checks['premier has displayName'] = ($catalog['premier']['displayName'] ?? null) === 'Premier';
$checks['eternal has displayName'] = ($catalog['eternal']['displayName'] ?? null) === 'Eternal';
$checks['twinsuns has displayName'] = ($catalog['twinsuns']['displayName'] ?? null) === 'Twin Suns';
$checks['open has displayName'] = ($catalog['open']['displayName'] ?? null) === 'Open';
$checks['each format has a color'] = count(array_filter($catalog, fn($f) => !empty($f['color']))) === 4;
$checks['goldfish excluded'] = !array_key_exists('goldfish', $catalog);
$checks['hotseat excluded'] = !array_key_exists('hotseat', $catalog);
$checks['preview excluded'] = !array_key_exists('preview', $catalog);

$checks['color lookup known'] = SWUDeckFormatColor('eternal') === $catalog['eternal']['color'];
$checks['color lookup unknown falls back'] = SWUDeckFormatColor('nonsense') === '#cccccc';
$checks['name lookup known'] = SWUDeckFormatDisplayName('twinsuns') === 'Twin Suns';
$checks['name lookup unknown falls back to raw id'] = SWUDeckFormatDisplayName('nonsense') === 'nonsense';

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
