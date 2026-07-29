<?php
// TDD guard for deterministic mulligan undo (SWUSim undo redesign, Task 12 / requirement #6):
//   "I mulligan; opponent doesn't; opponent undoes; I mulligan again — I get the SAME second hand."
// The seeded RNG (RNG_SEED) + counter-restoring snapshot make the mulligan reshuffle reproducible: a
// pregame-step snapshot before the mulligan captures the RNG counter, undo restores it, and re-running
// the mulligan replays the identical redraw. This test drives the real MulliganDecision handler.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_mulligan_determinism.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
if (!function_exists('ConvertMzIDToAbsolute'))      { function ConvertMzIDToAbsolute($m,$p):string{return '';} }
if (!function_exists('QueueDamageAnimation'))       { function QueueDamageAnimation($t,$a):void{} }
if (!function_exists('QueueRestoreAnimation'))      { function QueueRestoreAnimation($t,$a):void{} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t):void{} }
if (!function_exists('QueueShieldBreakAnimation'))  { function QueueShieldBreakAnimation($t):void{} }
foreach (['DeterministicRNG','CoreZoneModifiers','GameAuth'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
foreach (['Assertions','Cards','CommonSetup','GameStateBuilder','GameTestAdapter','SchemaTestRunner','TestRunner'] as $f) include_once "./SWUSim/Tests/Framework/$f.php";
global $gameName, $playerID, $customDQHandlers;
$gameName = 'mtest_' . getmypid(); $playerID = 1;
@mkdir('./Games/' . $gameName, 0777, true);

// A deck of distinct cards + a 6-card opening hand, so a reshuffle is observable in the resulting order.
$b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', [], []); $b->WithActivePlayer(1);
$deckIDs = ['SOR_095','SOR_046','SOR_100','SOR_101','SOR_102','SOR_103','SOR_104','SOR_105',
            'SOR_106','SOR_107','SOR_108','SOR_109','SOR_110','SOR_111','SOR_112','SOR_113'];
foreach (['SOR_200','SOR_201','SOR_202','SOR_203','SOR_204','SOR_205'] as $h) $b->WithCardInHandForPlayer(1, $h);
foreach ($deckIDs as $d) $b->WithCardInDeckForPlayer(1, $d);
$g = new GameTestAdapter(); $g->loadState($b);
ob_start(); AutoAdvanceAndExecute(); ob_end_clean();
$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = true;   // pregame undo free; determinism is what we assert here

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$handIDs = function () { $r = []; foreach (GetHand(1) as $c) { if (empty($c->removed)) $r[] = $c->CardID; } return $r; };

// Deterministic seed (mirrors CreateGame seeding the RNG before any shuffle).
SetSWUVar('RNG_SEED', 'fixed-seed-for-mulligan-determinism');
UndoStackClear(); SetSWUVar('UNDO_TOP', '-1');

$mulligan = $customDQHandlers['MulliganDecision'] ?? null;
$check(is_callable($mulligan), 'MulliganDecision handler is registered');

$perZone = function () { $s = EngineSnapshotState(); $out = []; foreach ($s['zones'] as $z => $v) $out[$z] = md5(serialize($v)); return $out; };

// The deterministic RNG hashes the FULL serialized zone state, so the shuffle only reproduces across an
// undo if the pre-shuffle state is byte-identical. Production guarantees that: every request ends with
// WriteGamestate and the next begins with ParseGamestate, canonicalizing every card object — including
// between the undo (request B) and the re-mulligan (request C). A raw in-memory LoadVersion restore skips
// that normalization, so we model each production request boundary explicitly.
$g->simulateRequestBoundary();   // canonicalize the freshly-built state (== start of the mulligan request)

// pregame-step snapshot BEFORE the mulligan (captures RNG counter + pending zones), then mulligan.
$preHand1 = $handIDs();
PushUndoSnapshot(1, 'pregame-step');
$stateA = $perZone();   // canonical state entering the FIRST shuffle
$mulligan(1, [1], 'YES');
$firstHand = $handIDs();
$check(count($firstHand) === 6, 'first mulligan draws 6 (got ' . count($firstHand) . ')');

// Opponent undoes -> restore the pregame-step; the request ends -> boundary canonicalizes (production).
ob_start(); SWUDoUndo(1, 'step'); ob_end_clean();
$g->simulateRequestBoundary();   // end-of-undo-request write + next-request parse
$preHand2 = $handIDs();
$check($preHand2 === $preHand1, 'after undo: pre-mulligan hand restored EXACTLY (order+ids)');
PushUndoSnapshot(1, 'pregame-step');
$stateB = $perZone();   // canonical state entering the SECOND shuffle — must equal stateA
$diff = [];
foreach ($stateA as $z => $h) if (($stateB[$z] ?? null) !== $h) $diff[] = $z;
foreach ($stateB as $z => $h) if (!array_key_exists($z, $stateA)) $diff[] = "+$z";
$check(empty($diff), 'pre-shuffle state identical across undo; diverging zones: ' . (empty($diff) ? '(none)' : implode(', ', $diff)));

// Re-mulligan (state already snapshotted for stateB above) -> MUST reproduce the identical second hand.
$mulligan(1, [1], 'YES');
$secondHand = $handIDs();
$check($secondHand === $firstHand, 'second mulligan reproduces the SAME hand (deterministic)'
    . "\n      first : " . implode(',', $firstHand)
    . "\n      second: " . implode(',', $secondHand));

// Sanity: a DIFFERENT seed would (almost certainly) give a different order — proves the seed is the lever.
ob_start(); SWUDoUndo(1, 'step'); ob_end_clean();
$g->simulateRequestBoundary();
SetSWUVar('RNG_SEED', 'a-totally-different-seed');
PushUndoSnapshot(1, 'pregame-step');
$mulligan(1, [1], 'YES');
$check($handIDs() !== $firstHand, 'a different seed yields a different order (seed is the determinism lever)');

$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = false;
UndoStackClear();
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
