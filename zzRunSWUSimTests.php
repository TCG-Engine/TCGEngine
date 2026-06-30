<?php
// Direct test runner for SWUSim schema-based tests — no auth needed.
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

$D = dirname(__FILE__) . '/SWUSim';
chdir($D);

include_once $D . '/../Core/DeterministicRNG.php';
include_once $D . '/../Core/CoreZoneModifiers.php';
include_once $D . '/../Core/NetworkingLibraries.php';
include_once $D . '/ZoneClasses.php';
include_once $D . '/ZoneAccessors.php';
include_once $D . '/GeneratedCode/GeneratedCardDictionaries.php';
include_once $D . '/GamestateParser.php';
include_once $D . '/Tests/Framework/Assertions.php';
include_once $D . '/Tests/Framework/Cards.php';
include_once $D . '/Tests/Framework/CommonSetup.php';
include_once $D . '/Tests/Framework/GameStateBuilder.php';
include_once $D . '/Tests/Framework/GameTestAdapter.php';
include_once $D . '/Tests/Framework/SchemaTestRunner.php';
include_once $D . '/Tests/Framework/TestRunner.php';
include_once $D . '/Tests/Cases/SchemaBasedTest.php';
include_once $D . '/Tests/Cases/triggers/WhenPlayedTest.php';

global $gameName, $updateNumber, $playerID;
$gameName = 'test_runner';
$playerID = 1;

$start = microtime(true);
$passed = 0;
$failed = 0;
$failures = [];

// Collect all test_ functions (including eval'd ones)
$testFns = array_filter(get_defined_functions()['user'], function($f) {
    return strncmp($f, 'test_', 5) === 0;
});

foreach ($testFns as $fn) {
    InitializeGamestate();
    try {
        $fn();
        $passed++;
    } catch (Throwable $e) {
        $failed++;
        $failures[] = ['name' => $fn, 'error' => $e->getMessage()];
        echo "FAIL: $fn\n  " . $e->getMessage() . "\n";
    }
}

$total = $passed + $failed;
$ms    = round((microtime(true) - $start) * 1000);
echo "\n$passed/$total passed ({$ms}ms)\n";
if ($failed > 0) {
    echo "$failed FAILED\n";
    exit(1);
}
