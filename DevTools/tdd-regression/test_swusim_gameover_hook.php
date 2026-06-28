<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_gameover_hook.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../Core/DeterministicRNG.php';
include_once __DIR__ . '/../../Core/CoreZoneModifiers.php';
include_once __DIR__ . '/../../SWUSim/ZoneClasses.php';
include_once __DIR__ . '/../../SWUSim/ZoneAccessors.php';
include_once __DIR__ . '/../../SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/../../SWUSim/GamestateParser.php';
include_once __DIR__ . '/../../SWUSim/Custom/GameLogic.php';

global $gameName;
$gameName = 'gohooktest_' . uniqid();

InitializeGamestate();
$checks = [];

// No winner initially.
$checks['no winner at start'] = SWUGetGameWinner() === 0;

// Declare winner 2.
SWUDeclareGameWinner(2, "GAMEOVER:Player 1's base has been defeated! Player 2 wins!");
$checks['winner recorded'] = SWUGetGameWinner() === 2;
$checks['gWinner set'] = (intval($GLOBALS['gWinner'] ?? 0) === 2);
$checks['flash set'] = strpos((string)GetFlashMessage(), 'GAMEOVER:') === 0;

// Idempotent: a second declaration does NOT overwrite the first winner.
SWUDeclareGameWinner(1, "GAMEOVER:Player 2's base has been defeated! Player 1 wins!");
$checks['idempotent first-wins'] = SWUGetGameWinner() === 2;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
