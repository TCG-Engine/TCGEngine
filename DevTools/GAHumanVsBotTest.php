<?php
// Headless human-vs-bot smoke test for GrandArchiveSim. Unlike GABotSelfPlayTest.php (both seats
// bot-controlled, driven entirely through the production controller), this configures the lobby
// exactly as SharedUI/Sites/GrandArchiveSim/MainMenu.php now does for a real "Practice vs Bot"
// game — botPlayers=[2] only — and proves the one guarantee that setup depends on: the bot
// controller (Core/jsInclude.js's mode-10017 poll -> BotController.php) never acts for seat 1.
//
// Seat 1 ("the human") is driven with the same legal-action/heuristic building blocks the bot
// itself uses (BotLegalActions.php + BotHeuristic.php), called directly — NOT through
// ProcessBotControllerStep() — so this only stands in for "a player who keeps making legal moves,"
// not a claim about human skill. Seat 2 is driven exclusively through ProcessBotControllerStep(),
// the real mode-10017 code path a browser's poll loop calls.
//
// Usage: php -d apc.enable_cli=1 -d xdebug.mode=off DevTools/GAHumanVsBotTest.php [--max-steps=2000] [--verbose]

if (!function_exists('apcu_store')) {
  $GLOBALS['GABotTestUsingApcuFallback'] = true;
  $GLOBALS['GABotTestApcu'] = [];
  function apcu_store($key, $value, $ttl = 0) { $GLOBALS['GABotTestApcu'][strval($key)] = $value; return true; }
  function apcu_fetch($key, &$success = null) {
    $key = strval($key);
    $success = array_key_exists($key, $GLOBALS['GABotTestApcu']);
    return $success ? $GLOBALS['GABotTestApcu'][$key] : false;
  }
  function apcu_exists($key) { return array_key_exists(strval($key), $GLOBALS['GABotTestApcu']); }
  function apcu_delete($key) {
    $key = strval($key);
    $existed = array_key_exists($key, $GLOBALS['GABotTestApcu']);
    unset($GLOBALS['GABotTestApcu'][$key]);
    return $existed;
  }
  function apcu_inc($key, $step = 1, &$success = null, $ttl = 0) {
    $key = strval($key);
    if (!array_key_exists($key, $GLOBALS['GABotTestApcu'])) { $success = false; return false; }
    $GLOBALS['GABotTestApcu'][$key] += $step;
    $success = true;
    return $GLOBALS['GABotTestApcu'][$key];
  }
}

require_once __DIR__ . '/../Core/EngineActionRunner.php';
require_once __DIR__ . '/../APIs/Lobbies/Classes/Player.php';
EngineLoadRootRuntime('GrandArchiveSim');
if (!empty($GLOBALS['GABotTestUsingApcuFallback'])) $GLOBALS['APCuEnabled'] = true;
require_once __DIR__ . '/../GrandArchiveSim/CreateGame.php';

function HumanBotTestParseArgs($argv) {
  $args = ['maxSteps' => 300, 'verbose' => false];
  foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--max-steps=')) $args['maxSteps'] = intval(substr($arg, 12));
    elseif ($arg === '--verbose') $args['verbose'] = true;
  }
  return $args;
}

// This test's job is the seat-isolation guarantee (below), not full-match completion — that's
// already covered end-to-end by GABotSelfPlayTest.php using the same heuristic through the real
// controller for both seats. Seat 1 here is driven by a simplified standalone loop (no client
// polling, no HTTP round-trips) that can legitimately get stuck on heuristic gaps the production
// controller's own poll cadence and retry logic paper over; a bounded step count keeps that from
// hanging the test suite.
$args = HumanBotTestParseArgs($argv);
$fixtureDir = __DIR__ . '/../GrandArchiveSim/Tests/BotFixtures';
$deckPath1 = $fixtureDir . '/worlds2026_deck_a.txt';
$deckPath2 = $fixtureDir . '/worlds2026_deck_b.txt';

$checks = [];
function HumanBotTestCheck(&$checks, $label, $passed, $detail = '') { $checks[] = [$label, $passed, $detail]; }

function HumanBotTestStallDiagnostic($gaDir, $gameName, $reason = 'state') {
  ParseGamestate($gaDir);
  $decisionQueues = [];
  foreach ([1, 2] as $seat) {
    foreach (GetDecisionQueue($seat) as $entry) {
      if ($entry === null || !empty($entry->removed)) continue;
      $decisionQueues[] = ['seat' => $seat, 'type' => strval($entry->Type ?? ''), 'param' => strval($entry->Param ?? ''), 'priority' => intval($entry->Priority ?? 0)];
    }
  }
  $effectStack = [];
  foreach (GetEffectStack() as $obj) {
    if ($obj === null || !empty($obj->removed)) continue;
    $effectStack[] = ['cardId' => strval($obj->CardID ?? ''), 'controller' => intval($obj->Controller ?? 0), 'turnEffects' => array_values($obj->TurnEffects ?? [])];
  }
  echo '[' . strtoupper($reason) . '] ' . json_encode([
    'gameName' => $gameName,
    'turnPlayer' => intval(GetTurnPlayer()),
    'phase' => strval(GetCurrentPhase()),
    'pendingDecisionPlayer' => function_exists('GABotPendingDecisionPlayer') ? GABotPendingDecisionPlayer() : 0,
    'botControllerPending' => BotControllerPendingPlayerForClient(),
    'decisionQueues' => $decisionQueues,
    'effectStack' => $effectStack,
  ], JSON_UNESCAPED_SLASHES) . "\n";
}

// ── Lobby setup mirrors MainMenu.php's "Practice vs Bot" submission exactly: botPlayers=[2] only,
// so the bot pilots the opponent seat while seat 1 stays under the (simulated) human's control. ──
$lobby = new stdClass();
$lobby->numPlayers = 2;
$lobby->maxPlayers = 2;
$lobby->format = 'bot';
$lobby->isGoldfish = true;
$lobby->goldfishPlayers = [];
$lobby->botPlayers = [2];
$lobby->players = [
  new Player(1, file_get_contents($deckPath1), ''),
  new Player(2, file_get_contents($deckPath2), ''),
];

$gameName = GASetupGame($lobby, ['deterministicShuffle' => true]);
if ($args['verbose']) echo "[INFO] game created: {$gameName}\n";

$gaDir = __DIR__ . '/../GrandArchiveSim/';
$maxConsecutiveNoOps = 20;
$steps = 0;
$consecutiveNoOps = 0;
$stalled = false;
$gameOver = false;
$winner = 0;
$botTouchedSeat1 = false;
$seat1ManualSteps = 0;
$seat2ControllerSteps = 0;

for (; $steps < $args['maxSteps']; $steps++) {
  ParseGamestate($gaDir);

  $winner = intval(DecisionQueueController::GetVariable('GAMEOVER_WINNER'));
  if ($winner !== 0) { $gameOver = true; break; }

  // Engine-level plumbing, not a per-seat decision: in production this runs on every mode-10017
  // poll regardless of whose turn it is (BotController.php's ProcessBotControllerStep does the
  // same check unconditionally). Without it, a stack left idle by either seat's own action would
  // never advance — neither GABotLegalActions(1) nor the bot controller resolve it.
  $idleStack = function_exists('GetLiveEffectStackEntries') ? GetLiveEffectStackEntries() : [];
  if (GABotPendingDecisionPlayer() === 0 && !empty($idleStack)) {
    $beforeSignature = GABotIdleEffectStackSignature();
    $resumed = function_exists('ResumeIdleEffectStackIfNeeded') && ResumeIdleEffectStackIfNeeded();
    $afterSignature = GABotIdleEffectStackSignature();
    if ($resumed && $beforeSignature !== $afterSignature) { $consecutiveNoOps = 0; continue; }
    HumanBotTestCheck($checks, 'no idle Effect Stack left unresolved', false,
      "step {$steps}: resumeAttempted=" . ($resumed ? 'true' : 'false'));
    break;
  }

  // The core guarantee under test: with botPlayers=[2], the production controller must never
  // decide it owes seat 1 an action — that would mean the bot is fighting the human for their
  // own seat.
  $botPending = BotControllerPendingPlayerForClient();
  if ($botPending === 1) { $botTouchedSeat1 = true; break; }

  if ($botPending === 2) {
    $result = ProcessBotControllerStep(0, 'GrandArchiveSim', $gameName);
    $seat2ControllerSteps++;
    if (empty($result['success'])) {
      HumanBotTestCheck($checks, 'bot step succeeds', false, "step {$steps}: " . strval($result['message'] ?? ''));
      break;
    }
    if (!empty($result['writeGamestate'])) WriteGamestate($gaDir);
    if (empty($result['applied'])) { $consecutiveNoOps++; if ($consecutiveNoOps >= $maxConsecutiveNoOps) { $stalled = true; break; } }
    else $consecutiveNoOps = 0;
    continue;
  }

  // Not the bot's turn: drive seat 1 as "the human," using the same legal-action building blocks
  // the bot uses, but calling them directly rather than through the bot controller — this is the
  // one seat that mode-10017 must never touch. Mirrors ProcessBotControllerStep's own
  // exclude-and-retry loop (BotController.php:190-213): the enumerator's pre-filters can't fully
  // replicate the engine's real legality chain (e.g. BeginCombatPhase), so a candidate that looks
  // legal can still silently no-op — detected here the same way, via a before/after state hash.
  $legal = GABotLegalActions($gameName, 1);
  $remaining = is_array($legal['actions'] ?? null) ? $legal['actions'] : [];
  $result = null; $cleanAction = null; $noOp = true;
  while (!empty($remaining)) {
    $cleanAction = GABotChooseActionHeuristic($remaining, $legal);
    if ($cleanAction === null) break;
    if ($args['verbose']) echo "[DEBUG] step {$steps} seat1 action: " . json_encode($cleanAction) . "\n";
    $beforeHash = GABotComparableGamestateHash($gameName);
    $result = EngineExecuteLoadedAction($cleanAction, 'GrandArchiveSim', $gameName, ['updateCache' => false]);
    $afterHash = GABotComparableGamestateHash($gameName);
    $noOp = ($beforeHash !== null && $afterHash !== null && $beforeHash === $afterHash);
    if (!$noOp) break;
    $remaining = array_values(array_filter($remaining, fn($a) => strval($a['cardID'] ?? '') !== strval($cleanAction['cardID'] ?? '')));
  }
  if ($cleanAction === null) {
    $consecutiveNoOps++;
    if ($consecutiveNoOps >= $maxConsecutiveNoOps) { $stalled = true; break; }
    continue;
  }
  $seat1ManualSteps++;
  if (empty($result['success'])) {
    HumanBotTestCheck($checks, 'seat 1 (human) step succeeds', false, "step {$steps}: " . strval($result['message'] ?? ''));
    break;
  }
  if ($noOp) { $consecutiveNoOps++; if ($consecutiveNoOps >= $maxConsecutiveNoOps) { $stalled = true; break; } }
  else $consecutiveNoOps = 0;

  if ($args['verbose'] && $steps > 0 && $steps % 50 === 0) echo "[INFO] step {$steps}...\n";
}

HumanBotTestCheck($checks, 'bot controller never claims seat 1', !$botTouchedSeat1,
  $botTouchedSeat1 ? "BotControllerPendingPlayerForClient() returned 1 at step {$steps}" : '');
HumanBotTestCheck($checks, 'both seats made real progress', $seat1ManualSteps > 0 && $seat2ControllerSteps > 0,
  "seat1 manual={$seat1ManualSteps}, seat2 controller={$seat2ControllerSteps}" . ($gameOver ? ", winner=P{$winner}" : '')
    . ($stalled ? " (stalled after {$consecutiveNoOps} consecutive no-op steps)" : ''));
if (!$gameOver) HumanBotTestStallDiagnostic($gaDir, $gameName, $stalled ? 'stall' : 'timeout');
elseif ($args['verbose']) echo "[INFO] game completed in {$steps} steps, winner=P{$winner}\n";
HumanBotTestCheck($checks, 'no unrecognized decision-type gaps', empty($GLOBALS['GABotUnrecognizedDecisions']),
  empty($GLOBALS['GABotUnrecognizedDecisions']) ? '' : json_encode($GLOBALS['GABotUnrecognizedDecisions']));

$failures = 0;
foreach ($checks as [$label, $passed, $detail]) {
  echo ($passed ? '[PASS] ' : '[FAIL] ') . $label . ($detail !== '' ? " — {$detail}" : '') . "\n";
  if (!$passed) $failures++;
}
echo "[SUMMARY] Total: " . count($checks) . " | Pass: " . (count($checks) - $failures) . " | Fail: {$failures}\n";

exit($failures > 0 ? 1 : 0);
