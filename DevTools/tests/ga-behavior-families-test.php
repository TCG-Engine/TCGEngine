<?php
// Unit checks for fail-closed behavioral evaluation; no game runtime required.
require_once __DIR__ . '/../ga-fixtures/behavior-families/suites.php';
function RegressionEvaluateAssertionsForStep($assertions, $step) {
    return $assertions === ['fail'] ? [false, 'deliberate mismatch'] : [true, ''];
}
function GetDecisionQueue($player) { return $GLOBALS['queue']; }
function mustThrow(callable $run, string $message): void {
    try { $run(); } catch (RuntimeException $e) {
        if (str_contains($e->getMessage(), $message)) return;
        throw $e;
    }
    throw new RuntimeException("Expected failure: $message");
}
$cases = gaBehaviorCases();
if (count($cases) !== 255 || $cases['refreshing-charge-recovers-three-clamps-at-zero'][5] !== 1) {
    throw new RuntimeException('Family discovery lost a card or a boundary case');
}
mustThrow(fn() => gaCheckBehaviorStep(['assertions' => ['fail']], 'recover', 5), 'deliberate mismatch');
$GLOBALS['queue'] = [];
mustThrow(fn() => gaCheckBehaviorStep([], 'taunt', 3), 'exactly theirField-1');
$GLOBALS['queue'] = [(object)['Type' => 'MZCHOOSE', 'Param' => 'theirField-0&theirField-1']];
mustThrow(fn() => gaCheckBehaviorStep([], 'taunt', 3), 'exactly theirField-1');
$GLOBALS['queue'] = [(object)['Type' => 'MZCHOOSE', 'Param' => 'theirField-1']];
gaCheckBehaviorStep([], 'taunt', 3);
mustThrow(fn() => gaCheckBehaviorStep([], 'taunt', 4), 'advance the attack');
$GLOBALS['queue'] = [];
gaCheckBehaviorStep([], 'taunt', 4);
echo "PASS: family discovery, assertion failures, legal targets, and attack advancement\n";

$GLOBALS['queue'] = [(object)['Type' => 'MZCHOOSE', 'Param' => 'theirField-0&theirField-1']];
mustThrow(fn() => gaCheckBehaviorStep([], 'stealth', 3), 'exactly theirField-0');
$GLOBALS['queue'] = [(object)['Type' => 'MZCHOOSE', 'Param' => 'theirField-0']];
gaCheckBehaviorStep([], 'stealth', 3);
mustThrow(fn() => gaCheckBehaviorStep([], 'stealth', 4), 'advance the attack');
$GLOBALS['queue'] = [];
gaCheckBehaviorStep([], 'stealth', 4);
echo "PASS: stealth rejects protected targets and advances on a legal target\n";
