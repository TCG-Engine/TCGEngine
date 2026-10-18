<?php
// ── A COMBAT resume must settle the effect stack's EMPTY layers, like a bare resume ─────────────────
//
// ASH meta run 2026-10-17, game 493761 (repro 506684): Maul Blue's deployed Darth Maul attacked Canto Bight
// Security. The batch held P1's Shadowed Undercity trigger and P2's On Defense; Maul's On Attack pings hit
// deployed Jabba's side, whose reaction opened a NESTED layer (CR 7.6.11), resolved, and left that layer EMPTY.
// Only a BARE resume pops empty layers (_SWUEsSettleLayers); the COMBAT resume counted every entry from its
// batch start, saw both players' triggers, and asked "Resolve_Which_Player_First?" — but the answer handler
// offers only the innermost layer, which was empty. Nothing resolved, the resume asked again, forever (the
// bot harness's all-candidates-no-op). The two stack entries are exactly the stuck game's.
//     php -d xdebug.mode=off SWUSim/DevTools/tests/combat_resume_stale_layer_test.php
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

if (!defined('STDERR')) define('STDERR', fopen('php://stderr', 'w'));
chdir(realpath(__DIR__ . '/../../..'));
$_GET['mode'] = 'cli';
if (!function_exists('ConvertMzIDToAbsolute')) { function ConvertMzIDToAbsolute($mzID, $p): string { return ''; } }
if (!function_exists('QueueDamageAnimation')) { function QueueDamageAnimation($t, $a): void {} }
if (!function_exists('QueueRestoreAnimation')) { function QueueRestoreAnimation($t, $a): void {} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t): void {} }
if (!function_exists('QueueShieldBreakAnimation')) { function QueueShieldBreakAnimation($t): void {} }
foreach (['./AccountFiles/AccountSessionAPI.php', './Core/HTTPLibraries.php', './Core/DeterministicRNG.php',
          './Core/CoreZoneModifiers.php', './Core/GameAuth.php', './SWUSim/ZoneClasses.php', './SWUSim/ZoneAccessors.php',
          './SWUSim/GeneratedCode/GeneratedCardDictionaries.php', './SWUSim/GamestateParser.php',
          './SWUSim/Tests/Framework/Assertions.php', './SWUSim/Tests/Framework/Cards.php',
          './SWUSim/Tests/Framework/CommonSetup.php', './SWUSim/Tests/Framework/GameStateBuilder.php',
          './SWUSim/Tests/Framework/GameTestAdapter.php', './SWUSim/Tests/Framework/SchemaTestRunner.php',
          './SWUSim/Tests/Framework/TestRunner.php'] as $f) include_once $f;

ob_start();
SchemaTestRunner::runString(
    "# Q\n## GIVEN\nCommonSetup: ngw/ngw\nSkipPreGame: true\nWithActivePlayer: 1\n"
    . "WithP1GroundArena: SOR_095:1:0\nWithP2GroundArena: LAW_121:1:0\n## WHEN\n## EXPECT\nP1GROUNDARENACOUNT:1\n", 'probe');
ob_end_clean();

global $customDQHandlers, $playerID;
$queued = function (int $p): array {
    return array_values(array_map(fn($e) => strval($e->Type ?? '') . ' ' . strval($e->Param ?? ''),
        array_filter(GetDecisionQueue($p), fn($e) => $e !== null && empty($e->removed))));
};
$clearQueues = function () {
    foreach ([1, 2] as $p) { $q = &GetDecisionQueue($p); foreach ($q as $e) if ($e !== null) $e->removed = true; unset($q); }
    DecisionQueueController::CleanupRemovedCards();
};

// The stuck game's stack: one trigger per player under a stale, EMPTY nested layer (floor 2 = both live entries).
$clearQueues();
$playerID = 1;
AddEffectStack(CardID: 'LOF_021', Controller: 1, TriggerType: 'LOF_021', Params: '');
AddEffectStack(CardID: 'LAW_121', Controller: 2, TriggerType: 'OnDefense', Params: 'myGroundArena-0');
_SWUEsSetLayers([['live' => 2, 'hold' => 0]]);
check(_SWUEsTopLayerIndices() === [], 'precondition: the innermost layer is empty while two triggers wait beneath it');

// The combat resume (continuation COMBAT, batch start 0) — exactly the stuck game's parameters.
$customDQHandlers['SWU_TRIGGER_RESUME'](1, ['1', 'COMBAT', 'myGroundArena-0', 'theirGroundArena-0', '22', '0'], null);
check(_SWUEsLayers() === [], 'the combat resume pops the empty layer (got ' . json_encode(_SWUEsLayers()) . ')');
$q1 = $queued(1);
check((bool)array_filter($q1, fn($s) => str_starts_with($s, 'CUSTOM SWU_TRIGGER_ORDER_CHOICE')),
    'both players still have a trigger in the SAME layer, so the active player picks who resolves first (' . implode(' ; ', $q1) . ')');

// "Mine first": P1's single trigger must be dispatched — not an empty choice that asks the same question again.
$clearQueues();
$customDQHandlers['SWU_TRIGGER_ORDER_CHOICE'](1, ['1'], 'YES');
$q1 = $queued(1);
check((bool)array_filter($q1, fn($s) => $s === 'CUSTOM RESOLVE_NEXT_TRIGGER|EffectStack-0'),
    "answering YES dispatches P1's Shadowed Undercity trigger (" . implode(' ; ', $q1) . ')');
echo "ALL PASS\n";
