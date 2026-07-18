<?php
// http://localhost:3100/TCGEngine/DevTools/tdd-regression/test_swudeck_validate_leader_addition.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../SWUDeck/Custom/DeckValidation.php';

$checks = [];

// SWUDeckMaxLeaders($formatId): int — the pure piece of ValidateLeaderAddition's logic.
$checks['premier allows 1 leader'] = SWUDeckMaxLeaders('premier') === 1;
$checks['eternal allows 1 leader'] = SWUDeckMaxLeaders('eternal') === 1;
$checks['open allows 1 leader'] = SWUDeckMaxLeaders('open') === 1;
$checks['twinsuns allows 2 leaders'] = SWUDeckMaxLeaders('twinsuns') === 2;
$checks['unknown format defaults to 1'] = SWUDeckMaxLeaders('nonsense') === 1;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
