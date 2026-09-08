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

// USER RULING 2026-09-08: Open enforces nothing when a list is VALIDATED (SWUCheckFormat accepts any
// number of leaders), but SWUDeck's BUILDER still offers exactly one leader slot for an Open deck —
// the identity banner has one. The two are deliberately different; don't "fix" this to match.
$checks['open still builds with 1 leader'] = SWUDeckMaxLeaders('open') === 1;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
