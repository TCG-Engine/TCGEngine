<?php
// http://localhost:3100/TCGEngine/DevTools/tdd-regression/test_swudeck_leader_alignment.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php'; // $aspectData, UUIDLookup, CardIDLookup
include_once __DIR__ . '/../../SWUDeck/Custom/DeckFormats.php';

$checks = [];

// SOR_005 is Vigilance,Heroism per $aspectData (2579145458 => 'Vigilance,Heroism') — accepts a
// UUID (SWUDeck's native scheme), not the SET_NNN id, and must actually resolve via $aspectData
// (not just fall through to NEUTRAL, which was a false-positive gap in an earlier version of
// this test).
$sor005Uuid = UUIDLookup('SOR_005');
$checks['UUIDLookup resolved SOR_005'] = !empty($sor005Uuid);
$checks['SOR_005 (Vigilance,Heroism) resolves to HEROISM'] = SWUDeckLeaderAlignment($sor005Uuid) === 'HEROISM';

// SOR_010 is Aggression,Villainy per $aspectData (6088773439 => 'Aggression,Villainy').
$sor010Uuid = UUIDLookup('SOR_010');
$checks['SOR_010 (Aggression,Villainy) resolves to VILLAINY'] = SWUDeckLeaderAlignment($sor010Uuid) === 'VILLAINY';

// TWI_017 "Chancellor Palpatine, Playing Both Sides" — starts Heroism via the explicit override
// table in _SWULeaderStartAlignment, despite printing both aspects. Confirms the SET_NNN
// resolution (CardIDLookup) correctly feeds the override table keyed by SET_NNN ids.
$palpatineUuid = UUIDLookup('TWI_017');
if ($palpatineUuid) {
    $checks['Palpatine (TWI_017) starts Heroism via override'] = SWUDeckLeaderAlignment($palpatineUuid) === 'HEROISM';
}

// Unknown/garbage UUID: falls through to NEUTRAL rather than erroring.
$checks['unknown UUID falls back to NEUTRAL'] = SWUDeckLeaderAlignment('0000000000') === 'NEUTRAL';

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
