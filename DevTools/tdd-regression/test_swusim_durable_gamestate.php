<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_durable_gamestate.php
header('Content-Type: text/plain');

include_once __DIR__ . '/../../Core/DeterministicRNG.php';
include_once __DIR__ . '/../../Core/CoreZoneModifiers.php';
include_once __DIR__ . '/../../SWUSim/ZoneClasses.php';
include_once __DIR__ . '/../../SWUSim/ZoneAccessors.php';
include_once __DIR__ . '/../../SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/../../SWUSim/GamestateParser.php';

global $gameName, $gTurnNumber;
$gameName = 'durabletest_' . uniqid();
$dir = __DIR__ . '/../../SWUSim/Games/' . $gameName;
@mkdir($dir, 0777, true);

// Write a recognizable state.
InitializeGamestate();
$gTurnNumber = 7;
WriteGamestate(__DIR__ . '/../../SWUSim/');

$fileWritten = is_file($dir . '/Gamestate.txt');

// Simulate APCu eviction: drop the cache key so the read MUST come from disk.
if (function_exists('apcu_delete')) apcu_delete(GetGamestateStorageKey($gameName));

// Re-parse; turn number must survive from the file.
$gTurnNumber = 0;
ParseGamestate(__DIR__ . '/../../SWUSim/');
$survived = (intval($gTurnNumber) === 7);

// cleanup
@unlink($dir . '/Gamestate.txt');
@rmdir($dir);

echo ($fileWritten && $survived)
   ? "PASS\n"
   : "FAIL fileWritten=" . var_export($fileWritten, true) . " turnAfterEvict=" . $gTurnNumber . "\n";
