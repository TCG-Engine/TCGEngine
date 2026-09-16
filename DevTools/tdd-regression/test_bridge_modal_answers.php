<?php

// The automation bridge must answer an MZMODAL the way the client does: with INDICES.
//
// 2026-09-15: BridgeEnumerateModalResults() returned the option LABELS ("Play_Card"), but
// Core/MZModalUI.js submits `indices.join(',')` ("0", "1", "0,2"). HellbreakSim's horror-action
// handler validates `^\d+$` and rejected every label, which silently consumed the prompt: the
// decision queue emptied, no action happened, and the enumerator then reported a free-play state.
// Any root whose handler reads the answer as an index saw the same thing.
//
// Single-select modals also have to offer EVERY option. The old first-k/last-k sampling hid the
// middle ones, so a Hellbreak player could never be driven to "Attack" or "Scheme" in a six-option
// horror prompt. Multi-select keeps the sampling — the combinations explode otherwise.

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');
chdir(dirname(__DIR__, 2));

$failures = 0;
$checks = 0;
$check = function($condition, string $message) use (&$failures, &$checks): void {
    ++$checks;
    $ok = boolval($condition);
    echo ($ok ? 'PASS' : 'FAIL') . ': ' . $message . PHP_EOL;
    if(!$ok) ++$failures;
};

// The bridge is a CLI script that dispatches on include, so pull in just the function under test.
$bridgeSource = (string)file_get_contents('./DevTools/TestAutomationBridge.php');
if(preg_match('/\nfunction BridgeEnumerateModalResults\(.*?\n}\n/s', $bridgeSource, $match)) {
    eval($match[0]);
} else {
    echo "FAIL: could not find BridgeEnumerateModalResults in TestAutomationBridge.php\n";
    exit(1);
}

$check(BridgeEnumerateModalResults('1|1|Attack&Pass') === ['0', '1'],
    'a two-option prompt answers with index 0 and index 1, not the labels');
$check(BridgeEnumerateModalResults('1|1|Play_Card&Attack&Scheme&Ability&Slumber&Pass') === ['0', '1', '2', '3', '4', '5'],
    'a single-select prompt offers EVERY option, including the middle ones');
$check(BridgeEnumerateModalResults('0|2|A&B&C') === ['-', '0', '1', '2', '0,1', '1,2'],
    'an optional multi-select offers none, each single option, and first/last pairs');
$check(BridgeEnumerateModalResults('1|1|') === ['-'], 'a prompt with no labels still answers');
$check(!array_filter(BridgeEnumerateModalResults('1|1|Attack&Pass'), fn($answer) => !preg_match('/^(-|\d+(,\d+)*)$/', $answer)),
    'every answer is "-" or comma-separated indices');

// ---------------------------------------------------------------------------
// The engine calls ActionMap($cardID) with ONE argument (Core/EngineActionRunner.php, 'FSM').
// ---------------------------------------------------------------------------
include_once './HellbreakSim/Custom/GameLogic.php';
$reflection = new ReflectionFunction('ActionMap');
$check($reflection->getNumberOfRequiredParameters() === 1,
    "HellbreakSim's ActionMap takes one required argument, like every other root");

echo PHP_EOL . ($failures === 0 ? "GREEN ({$checks} checks)" : "RED ({$failures} of {$checks} failed)") . PHP_EOL;
exit($failures === 0 ? 0 : 1);
