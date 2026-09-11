<?php
// Game-log "had no effect" audit (gamelog-updates #2). Runs EVERY schema test section and prints each
// "P1's X had no effect" line with the section that produced it, to triage false positives (a trigger bagged
// although its trigger condition cannot hold — e.g. HMW_014 Wicket's front reaction after he deploys).
// Run in the container:  php -d xdebug.mode=off -d memory_limit=2G SWUSim/DevTools/scan-noeffect.php
// Output: NOEFFECT<TAB>file::section<TAB>log entry
if (!defined('STDERR')) define('STDERR', fopen('php://stderr','w'));
// Scratch single-file runner: php runcase.php <repo-relative .md path>
chdir('/var/www/html/TCGEngine');
$_GET['mode'] = 'cli';
if (!function_exists('ConvertMzIDToAbsolute')) { function ConvertMzIDToAbsolute($mzID, $p): string { return ''; } }
if (!function_exists('QueueDamageAnimation')) { function QueueDamageAnimation($t, $a): void {} }
if (!function_exists('QueueRestoreAnimation')) { function QueueRestoreAnimation($t, $a): void {} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t): void {} }
if (!function_exists('QueueShieldBreakAnimation')) { function QueueShieldBreakAnimation($t): void {} }
include_once './AccountFiles/AccountSessionAPI.php';
include_once './Core/HTTPLibraries.php';
include_once './Core/DeterministicRNG.php';
include_once './Core/CoreZoneModifiers.php';
include_once './Core/GameAuth.php';
include_once './SWUSim/ZoneClasses.php';
include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once './SWUSim/GamestateParser.php';
include_once './SWUSim/Tests/Framework/Assertions.php';
include_once './SWUSim/Tests/Framework/Cards.php';
include_once './SWUSim/Tests/Framework/CommonSetup.php';
include_once './SWUSim/Tests/Framework/GameStateBuilder.php';
include_once './SWUSim/Tests/Framework/GameTestAdapter.php';
include_once './SWUSim/Tests/Framework/SchemaTestRunner.php';
include_once './SWUSim/Tests/Framework/TestRunner.php';
$paths = glob("SWUSim/Tests/Cases/*/*.md"); foreach ($paths as $path) {
foreach (SchemaTestRunner::splitSegments(file_get_contents($path)) as $seg) {
    $name = $seg['name'] ?? basename($path);
    ob_start(); $r = SchemaTestRunner::runString($seg['content'], $path . '::' . $name); ob_end_clean();
    
    foreach (explode("<NL>", (string)($GLOBALS["gGameLog"] ?? "")) as $e) if (strpos($e, "had no effect") !== false) echo "NOEFFECT\t$path::$name\t$e\n";
}
}
