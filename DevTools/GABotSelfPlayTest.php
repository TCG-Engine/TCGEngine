<?php
// Headless bot-vs-bot self-play test for GrandArchiveSim — no browser, no HTTP, no APCu-auth
// plumbing. Drives the bot the same way the real client's polling loop does (mode 10017 →
// GrandArchiveSim/BotController.php's ProcessBotControllerStep(), which enumerates legal actions
// via BotLegalActions.php and executes the chosen one via Core/EngineActionRunner.php's
// EngineExecuteLoadedAction()) but calls that function directly in a single CLI process, the same
// way DevTools/RunIntegrationTests.php drives scripted regression fixtures.
//
// Verifies two things end to end, from a real 60-card decklist on each side:
//   1. The bot can pilot a full self-play game to completion without stalling (no unrecognized
//      decision type, no infinite no-op loop).
//   2. The telemetry pipeline (GrandArchiveSim/Telemetry.php + StatsSubmit.php) populates
//      correctly from bot-driven actions — the same assertion that used to require manually
//      curling GrandArchiveSim/DevToolsDebugTelemetry.php against a hand-played game.
//
// Usage (run inside the GA web-server container, where the live card dictionary + DB are set up):
//   docker exec -w /var/www/html/TCGEngine <container> \
//     php -d apc.enable_cli=1 -d xdebug.mode=off DevTools/GABotSelfPlayTest.php \
//     [--deck=<path>] [--deck2=<path>] [--max-steps=2000] [--verbose]
//
// `apc.enable_cli=1` is required — GASetupGame() stores auth metadata via APCu
// (SimGameWriteAuthKeysFromLobby) and throws if that store fails; CLI SAPI disables APCu by
// default. Since this whole test runs as one continuous process (no separate requests), that
// APCu state never needs to survive past this one invocation.
//
// Defaults to the two fixture decklists in GrandArchiveSim/Tests/BotFixtures/ (real 2026 World
// Championship lists, pulled from FanOfIn.Site's event data) so the test is runnable with no args.

// Homebrew PHP does not ship APCu by default. This harness is deliberately one CLI process, so an
// in-memory compatibility layer has the same lifetime and semantics it needs from APCu without a
// system-wide extension install. Production/web requests continue to use the real extension.
if (!function_exists('apcu_store')) {
  $GLOBALS['GABotTestUsingApcuFallback'] = true;
  $GLOBALS['GABotTestApcu'] = [];
  function apcu_store($key, $value, $ttl = 0) {
    $GLOBALS['GABotTestApcu'][strval($key)] = $value;
    return true;
  }
  function apcu_fetch($key, &$success = null) {
    $key = strval($key);
    $success = array_key_exists($key, $GLOBALS['GABotTestApcu']);
    return $success ? $GLOBALS['GABotTestApcu'][$key] : false;
  }
  function apcu_exists($key) {
    return array_key_exists(strval($key), $GLOBALS['GABotTestApcu']);
  }
  function apcu_delete($key) {
    $key = strval($key);
    $existed = array_key_exists($key, $GLOBALS['GABotTestApcu']);
    unset($GLOBALS['GABotTestApcu'][$key]);
    return $existed;
  }
  function apcu_inc($key, $step = 1, &$success = null, $ttl = 0) {
    $key = strval($key);
    if (!array_key_exists($key, $GLOBALS['GABotTestApcu'])) {
      $success = false;
      return false;
    }
    $GLOBALS['GABotTestApcu'][$key] += $step;
    $success = true;
    return $GLOBALS['GABotTestApcu'][$key];
  }
}

require_once __DIR__ . '/../Core/EngineActionRunner.php';
require_once __DIR__ . '/../APIs/Lobbies/Classes/Player.php';
// Loads the same runtime a real request has for GA actions (Core/NetworkingLibraries.php in
// particular — WriteCache() etc. — which EngineExecuteLoadedAction's cache bookkeeping needs but
// CreateGame.php's own include list doesn't pull in on its own).
EngineLoadRootRuntime('GrandArchiveSim');
if (!empty($GLOBALS['GABotTestUsingApcuFallback'])) $GLOBALS['APCuEnabled'] = true;
// No ambient $lobby here, so CreateGame.php's auto-run guard stays quiet; this just defines
// GASetupGame() (and pulls in GameLogic.php, which in turn pulls in Telemetry/BotController/etc).
require_once __DIR__ . '/../GrandArchiveSim/CreateGame.php';

function BotTestParseArgs($argv) {
  $args = ['deck' => null, 'deck2' => null, 'maxSteps' => 2000, 'verbose' => false, 'deterministic' => true, 'payloadOut' => null];
  foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--deck=')) $args['deck'] = substr($arg, 7);
    elseif (str_starts_with($arg, '--deck2=')) $args['deck2'] = substr($arg, 8);
    elseif (str_starts_with($arg, '--max-steps=')) $args['maxSteps'] = intval(substr($arg, 12));
    elseif ($arg === '--verbose') $args['verbose'] = true;
    elseif ($arg === '--random-shuffle') $args['deterministic'] = false;
    elseif (str_starts_with($arg, '--payload-out=')) $args['payloadOut'] = substr($arg, 14);
  }
  return $args;
}

$args = BotTestParseArgs($argv);
$fixtureDir = __DIR__ . '/../GrandArchiveSim/Tests/BotFixtures';
$deckPath1 = $args['deck'] ?? ($fixtureDir . '/worlds2026_deck_a.txt');
$deckPath2 = $args['deck2'] ?? ($fixtureDir . '/worlds2026_deck_b.txt');

if (!is_file($deckPath1)) { fwrite(STDERR, "deck file not found: $deckPath1\n"); exit(1); }
if (!is_file($deckPath2)) { fwrite(STDERR, "deck file not found: $deckPath2\n"); exit(1); }

$checks = [];   // [label, passed, detail]
function BotTestCheck(&$checks, $label, $passed, $detail = '') {
  $checks[] = [$label, $passed, $detail];
}

function BotTestStallDiagnostic($gaDir, $gameName, $reason = 'state') {
  ParseGamestate($gaDir);
  $decisionQueues = [];
  foreach ([1, 2] as $seat) {
    foreach (GetDecisionQueue($seat) as $entry) {
      if ($entry === null || !empty($entry->removed)) continue;
      $decisionQueues[] = [
        'seat' => $seat,
        'type' => strval($entry->Type ?? ''),
        'param' => strval($entry->Param ?? ''),
        'priority' => intval($entry->Priority ?? 0),
      ];
    }
  }
  $effectStack = [];
  foreach (GetEffectStack() as $obj) {
    if ($obj === null || !empty($obj->removed)) continue;
    $effectStack[] = [
      'cardId' => strval($obj->CardID ?? ''),
      'controller' => intval($obj->Controller ?? 0),
      'turnEffects' => array_values($obj->TurnEffects ?? []),
    ];
  }
  $turnPlayer = GetTurnPlayer();
  $phase = GetCurrentPhase();
  $pending = function_exists('GABotPendingDecisionPlayer') ? GABotPendingDecisionPlayer() : 0;
  echo '[' . strtoupper($reason) . '] ' . json_encode([
    'gameName' => $gameName,
    'turnPlayer' => intval($turnPlayer),
    'phase' => strval($phase),
    'pendingDecisionPlayer' => $pending,
    'decisionQueues' => $decisionQueues,
    'effectStack' => $effectStack,
  ], JSON_UNESCAPED_SLASHES) . "\n";
}

// ── Build a bot-vs-bot game directly in-process (mirrors JoinQueue.php's createBot branch, minus
// the HTTP/matchmaking layer — a CLI test drives GASetupGame() itself). ──────────────────────────
$lobby = new stdClass();
$lobby->numPlayers = 2;
$lobby->maxPlayers = 2;
$lobby->format = 'bot';
$lobby->isGoldfish = true;
$lobby->goldfishPlayers = [];
$lobby->botPlayers = [1, 2];
$lobby->players = [
  new Player(1, file_get_contents($deckPath1), ''),
  new Player(2, file_get_contents($deckPath2), ''),
];

$gameName = GASetupGame($lobby, ['deterministicShuffle' => $args['deterministic']]);
$lastChampions = ['1' => GASnapshotChampion(1), '2' => GASnapshotChampion(2)];
if ($args['verbose']) echo "[INFO] game created: {$gameName}\n";

// ── Drive the bot to completion ─────────────────────────────────────────────────────────────────
$maxConsecutiveNoOps = 20; // same philosophy as BotController.php's own no-op guard: a handful of
                           // doomed-but-offered actions in a row is normal; that many with zero
                           // progress means the bot is genuinely stuck.
$steps = 0;
$consecutiveNoOps = 0;
$stalled = false;
$gameOver = false;
$winner = 0;

$gaDir = __DIR__ . '/../GrandArchiveSim/';

for (; $steps < $args['maxSteps']; $steps++) {
  // Re-parse before every step, mirroring what a real HTTP request does on every poll (mode 10017):
  // ParseGamestate() reconstructs each zone object fresh from the wire text, correctly stamping
  // Location/PlayerID/mzIndex from the zone it's found in. Without this, a card that started in
  // the deck (constructed via `new Deck($cardID)` with no location/owner) keeps those blank/zero
  // fields forever as it's array-shifted between zones in memory — SelectionMetadataMzID() then
  // computes "their-N" for EVERY hand card of EITHER player (comparing $playerID against a stale
  // PlayerID=0), so BotLegalActions.php's CanActivateCardForSelection() check silently rejects
  // every materialize candidate. A single continuous CLI process never hits this naturally, unlike
  // real play, where each request is a fresh reparse.
  ParseGamestate($gaDir);
  foreach ([1, 2] as $seat) {
    $snapshot = GASnapshotChampion($seat);
    if (!empty($snapshot['championId'])) $lastChampions[strval($seat)] = $snapshot;
  }

  $winner = intval(DecisionQueueController::GetVariable('GAMEOVER_WINNER'));
  if ($winner !== 0) { $gameOver = true; break; }

  $result = ProcessBotControllerStep(0, 'GrandArchiveSim', $gameName);
  if (empty($result['success'])) {
    $message = strval($result['message'] ?? 'unknown engine error');
    $decoded = json_decode($message, true);
    $label = is_array($decoded) && ($decoded['code'] ?? '') === 'idle_effect_stack_no_progress'
      ? 'bot does not leave an idle Effect Stack unresolved'
      : 'bot step succeeds';
    BotTestCheck($checks, $label, false, "step {$steps}: " . $message);
    break;
  }
  // ProcessBotControllerStep() is normally reached through EngineActionRunner mode 10017, whose
  // outer action persists controller-owned work (for example ResumeIdleEffectStackIfNeeded()) when
  // no nested gameplay action ran. This harness calls the controller directly, so mirror that one
  // outer write before the next loop's ParseGamestate() would otherwise restore stale disk state.
  if (!empty($result['writeGamestate'])) WriteGamestate($gaDir);
  if (empty($result['applied'])) {
    $consecutiveNoOps++;
    if ($consecutiveNoOps >= $maxConsecutiveNoOps) { $stalled = true; break; }
  } else {
    $consecutiveNoOps = 0;
  }

  if ($args['verbose'] && $steps > 0 && $steps % 50 === 0) echo "[INFO] step {$steps}...\n";
}

BotTestCheck($checks, 'game reaches completion', $gameOver,
  $gameOver ? "in {$steps} bot steps, winner=P{$winner}"
    : ($stalled ? "stalled after {$consecutiveNoOps} consecutive no-op steps (around step {$steps})"
                : "hit --max-steps={$args['maxSteps']} without a winner"));

if (!$gameOver) BotTestStallDiagnostic($gaDir, $gameName, $stalled ? 'stall' : 'timeout');

BotTestCheck($checks, 'no unrecognized decision-type gaps', empty($GLOBALS['GABotUnrecognizedDecisions']),
  empty($GLOBALS['GABotUnrecognizedDecisions']) ? '' : json_encode($GLOBALS['GABotUnrecognizedDecisions']));

// ── Telemetry / logging assertions (in-process — the same globals Telemetry.php's accumulator
// writes to, no separate debug-endpoint request needed). ───────────────────────────────────────
ParseGamestate($gaDir); // refresh once more: the loop's last executed step wrote state but never reparsed it
$detail = GACaptureCurrentGameDetail();
foreach ([1, 2] as $seat) {
  $key = strval($seat);
  if (empty($detail['champions'][$key]['championId']) && !empty($lastChampions[$key]['championId'])) {
    $detail['champions'][$key] = $lastChampions[$key];
    $lastTurn = null;
    foreach (($detail['telemetry']['turns'] ?? []) as $turn) {
      if (intval($turn['seat'] ?? 0) === $seat) $lastTurn = $turn;
    }
    if ($lastTurn !== null) {
      $detail['champions'][$key]['level'] = intval($lastTurn['level'] ?? $detail['champions'][$key]['level']);
      $detail['champions'][$key]['hp'] = intval($lastTurn['hp'] ?? 0);
    }
  }
}
$tel = $detail['telemetry'];

foreach ([1, 2] as $seat) {
  $champ = $detail['champions'][strval($seat)] ?? null;
  // A defeated champion may already be removed by cleanup before final capture. The winner must
  // still resolve; for the losing seat, an empty final snapshot is expected and meaningful.
  $championPresent = !empty($champ['championId']);
  $championExpected = !$gameOver || $seat === $winner;
  BotTestCheck($checks, "P{$seat} champion " . ($championExpected ? 'resolved' : 'captured or defeated'),
    $championPresent || !$championExpected, $championPresent ? $champ['championId'] : '(defeated)');

  $materialized = 0; $drawn = 0;
  foreach (($tel['cards'][strval($seat)] ?? []) as $c) {
    $materialized += intval($c['materialized'] ?? 0);
    $drawn += intval($c['drawn'] ?? 0) + intval($c['drawnToMemory'] ?? 0);
  }
  BotTestCheck($checks, "P{$seat} telemetry: cards drawn", $drawn > 0, "drawn={$drawn}");
  BotTestCheck($checks, "P{$seat} telemetry: cards materialized", $materialized > 0, "materialized={$materialized}");
}

BotTestCheck($checks, 'telemetry: turn snapshots recorded', !empty($tel['turns']), 'count=' . count($tel['turns'] ?? []));
BotTestCheck($checks, 'telemetry: combat events recorded', !empty($tel['combatEvents']), 'count=' . count($tel['combatEvents'] ?? []));

if ($gameOver && !empty($args['payloadOut'])) {
  $matchId = 'headless-local-' . strval($gameName);
  $match = [
    'matchId' => $matchId,
    'createdAt' => time(),
    'format' => 'bot',
    'bestOf' => 1,
    'winner' => $winner,
    'wins' => ['1' => $winner === 1 ? 1 : 0, '2' => $winner === 2 ? 1 : 0],
    'players' => [
      '1' => ['deckLink' => ''],
      '2' => ['deckLink' => ''],
    ],
  ];
  $game = ['gameName' => strval($gameName), 'gameNumber' => 1, 'winner' => $winner, 'detail' => $detail];
  $payload = GABuildGameResultPayload($match, $game);
  $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  $written = $encoded !== false && file_put_contents($args['payloadOut'], $encoded . "\n") !== false;
  BotTestCheck($checks, 'analytics payload exported', $written, strval($args['payloadOut']));
}

// ── Report ───────────────────────────────────────────────────────────────────────────────────────
$failures = 0;
foreach ($checks as [$label, $passed, $detail]) {
  echo ($passed ? '[PASS] ' : '[FAIL] ') . $label . ($detail !== '' ? " — {$detail}" : '') . "\n";
  if (!$passed) $failures++;
}
echo "[SUMMARY] Total: " . count($checks) . " | Pass: " . (count($checks) - $failures) . " | Fail: {$failures}\n";

exit($failures > 0 ? 1 : 0);
