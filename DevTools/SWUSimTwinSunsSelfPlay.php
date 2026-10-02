<?php
// Headless Twin Suns self-play: 3 or 4 Arenabots play a free-for-all game to the end, in one CLI process per game.
// SWUSim/docs/todo-twinsuns-fill-bot.md, step 2 ("a smoke test that games finish with no stalls").
//
// The game is created the way a ROOM game with bots is: a twinsuns lobby whose seats all carry a botProfile goes
// through SWUSetupGame(), which marks them with SWUMarkBotSeats() — NOT Arenabot mode. So this also proves the
// step-1 start path. Every seat plays one of the four official Twin Suns pre-cons (SWUSetupTwinSunsPreCons),
// rotated by game number. The bot is stepped exactly as a browser steps it: ProcessBotControllerStep() once per
// "poll", with a full ParseGamestate() in between.
//
// What a green run proves: the machinery TERMINATES at 3 and 4 seats, through eliminations and the end-of-phase
// scoring, and the controller never acts for a dead seat. It says nothing about how WELL the bots play — at this
// step they still read seat 1 or 2 as "the opponent" (step 3 fixes that).
//
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
//     php -d apc.enable_cli=1 -d xdebug.mode=off DevTools/SWUSimTwinSunsSelfPlay.php --seats=3 --games=10
//
// Flags: --seats=3|4 (default 3) · --games=N (default 1; each game is its own child process) · --seed=S (default
// "ts"; game g uses "S-g") · --max-steps=N (default 8000) · --keep (keep finished games' directories) · -v
// One JSON line per game is printed as `[TSGAME] {...}`; the last line is a summary, and the exit code is non-zero
// if any game failed to finish.

if (!function_exists('apcu_store')) { fwrite(STDERR, "Needs APCu: run with -d apc.enable_cli=1\n"); exit(2); }

$args = ['seats' => 3, 'games' => 1, 'seed' => 'ts', 'maxSteps' => 8000, 'keep' => false, 'verbose' => false, 'child' => -1];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--seats=(\d)$/', $a, $m))          $args['seats'] = intval($m[1]);
    elseif (preg_match('/^--games=(\d+)$/', $a, $m))     $args['games'] = max(1, intval($m[1]));
    elseif (preg_match('/^--seed=(.+)$/', $a, $m))       $args['seed'] = $m[1];
    elseif (preg_match('/^--max-steps=(\d+)$/', $a, $m)) $args['maxSteps'] = intval($m[1]);
    elseif (preg_match('/^--child=(\d+)$/', $a, $m))     $args['child'] = intval($m[1]);
    elseif ($a === '--keep')                             $args['keep'] = true;
    elseif ($a === '-v' || $a === '--verbose')           $args['verbose'] = true;
    elseif ($a === '--trace')                            $args['trace'] = true;   // one line per step (child only)
}
if (!in_array($args['seats'], [3, 4], true)) { fwrite(STDERR, "--seats must be 3 or 4\n"); exit(2); }

// ── Parent: one child process per game (a game leaves globals behind that the next must not inherit) ──────────
if ($args['child'] < 0) {
    $done = 0; $failed = []; $rounds = []; $elims = 0; $shared = 0; $t0 = microtime(true);
    for ($g = 1; $g <= $args['games']; $g++) {
        // `timeout`: a game whose step never returns (an engine loop) fails THAT game instead of hanging the sweep.
        $cmd = sprintf('timeout 600 php -d apc.enable_cli=1 -d xdebug.mode=off -d memory_limit=1G %s --child=%d --seats=%d --seed=%s --max-steps=%d%s%s 2>&1',
            escapeshellarg(__FILE__), $g, $args['seats'], escapeshellarg($args['seed']), $args['maxSteps'],
            $args['keep'] ? ' --keep' : '', $args['verbose'] ? ' -v' : '');
        $out = shell_exec($cmd) ?? '';
        $line = null;
        foreach (explode("\n", $out) as $l) if (strpos($l, '[TSGAME] ') === 0) $line = $l;
        $r = $line !== null ? json_decode(substr($line, 9), true) : null;
        if ($args['verbose'] || !is_array($r) || empty($r['finished'])) echo rtrim($out) . "\n";
        else echo $line . "\n";
        flush();
        if (!is_array($r)) echo "[TSGAME] " . json_encode(['game' => $g, 'finished' => false, 'error' => 'no result (timed out after 600s, or crashed)']) . "\n";
        if (is_array($r) && !empty($r['finished'])) {
            $done++; $rounds[] = $r['rounds']; $elims += count($r['eliminated']); if (count($r['winners']) > 1) $shared++;
        } else {
            $failed[] = $g;
        }
    }
    sort($rounds);
    printf("[TSSUMMARY] seats=%d games=%d finished=%d failed=%s rounds(min/median/max)=%s eliminations=%d sharedWins=%d %.0fs\n",
        $args['seats'], $args['games'], $done, $failed ? implode(',', $failed) : 'none',
        $rounds ? $rounds[0] . '/' . $rounds[intdiv(count($rounds), 2)] . '/' . end($rounds) : '-', $elims, $shared, microtime(true) - $t0);
    exit($failed ? 1 : 0);
}

// ── Child: one game ─────────────────────────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../Core/EngineActionRunner.php';
require_once __DIR__ . '/../APIs/Lobbies/Classes/Player.php';
EngineLoadRootRuntime('SWUSim');
require_once __DIR__ . '/../SWUSim/CreateGame.php';
require_once __DIR__ . '/../SWUSim/Custom/SetupPanels.php';
require_once __DIR__ . '/../SWUSim/BotController.php';
if (!function_exists('WriteCache')) { function WriteCache(...$a) {} }
$swuDir = __DIR__ . '/../SWUSim/';
$g = $args['child'];

$precons = SWUSetupTwinSunsPreCons();
if (count($precons) < 4) { echo "[TSGAME] " . json_encode(['game' => $g, 'finished' => false, 'error' => 'pre-cons missing']) . "\n"; exit(1); }
$lobby = new stdClass();
$lobby->format = 'twinsuns';
$lobby->isPrivate = true;
$lobby->numPlayers = $lobby->maxPlayers = $args['seats'];
$lobby->players = [];
$decks = [];
for ($s = 1; $s <= $args['seats']; $s++) {
    $pc = $precons[($g + $s - 2) % count($precons)];   // rotate which pre-con sits where
    $p = new Player($s, $pc['input'], '');
    $p->setBotProfile('precon:' . $pc['key']);
    $lobby->players[] = $p;
    $decks[$s] = $pc['key'];
}
$seed = $args['seed'] . '-' . $g;
$gameName = SWUSetupGame($lobby, ['rngSeed' => $seed]);
ParseGamestate($swuDir);

$res = ['game' => $g, 'seed' => $seed, 'gameName' => $gameName, 'seats' => $args['seats'], 'decks' => $decks,
        'finished' => false, 'winners' => [], 'rounds' => 0, 'steps' => 0, 'eliminated' => [], 'baseHp' => [],
        'applied' => [], 'error' => ''];
$fail = function (string $why) use (&$res) { $res['error'] = $why; };

// Setup sanity: this is the room-game path, not Arenabot.
if (SWUGameMode() !== '' || !SWUHasBotSeats()) $fail('not a room game with bot seats (mode=' . SWUGameMode() . ')');
elseif (GetSWUBotPlayers() !== range(1, $args['seats'])) $fail('bot seats ' . json_encode(GetSWUBotPlayers()));
elseif (SeatCountForGame() !== $args['seats']) $fail('seat count ' . SeatCountForGame());

$noOps = 0;
for ($step = 0; $res['error'] === '' && $step < $args['maxSteps']; $step++) {
    ParseGamestate($swuDir);
    if (SWUGetGameWinner() !== 0) { $res['finished'] = true; break; }
    $owing = BotControllerPendingPlayerForClient();
    // The controller must never act for a dead seat. Checked BEFORE the step: the step itself may legitimately
    // eliminate the seat that took it (its own action can end with its base at 0).
    if ($owing > 0 && !IsSeatLive($owing)) { $fail("step $step: the controller owes a move for eliminated seat $owing"); break; }
    if (!empty($args['trace'])) {
        $head = '';
        if ($owing > 0) foreach (GetDecisionQueue($owing) as $e) if ($e && empty($e->removed)) { $head = $e->Type . ':' . substr(strval($e->Tooltip ?? ''), 0, 50); break; }
        echo "  #$step r" . GetTurnNumber() . ' ' . GetCurrentPhase() . ' turn=P' . GetTurnPlayer() . " owing=P$owing live=" . implode('', GetLiveSeatsArray())
           . ' hp=' . implode('/', array_map('SWUBaseRemainingHp', GetSeatOrderArray())) . " $head\n";
    }
    $stepT0 = microtime(true);
    $r = ProcessBotControllerStep(0, 'SWUSim', $gameName);
    $res['slowestStepMs'] = max($res['slowestStepMs'] ?? 0, intval(1000 * (microtime(true) - $stepT0)));
    if (empty($r['success'])) { $fail("step $step: " . strval($r['message'] ?? 'engine error')); break; }
    if (!empty($r['applied'])) {
        $noOps = 0;
        if ($owing > 0) $res['applied'][$owing] = ($res['applied'][$owing] ?? 0) + 1;
    } elseif (++$noOps >= 25) {
        ParseGamestate($swuDir);
        $q = [];
        foreach (GetSeatOrderArray() as $s) foreach (GetDecisionQueue($s) as $e) if ($e && empty($e->removed)) $q[] = "P$s:" . $e->Type . ':' . substr(strval($e->Tooltip ?? ''), 0, 60);
        $fail("stalled at step $step: phase=" . GetCurrentPhase() . ' turn=P' . GetTurnPlayer() . ' owing=' . $owing . ' queues=' . json_encode($q));
        break;
    }
    if ($args['verbose'] && $step % 200 === 0) {
        echo "  step $step round " . GetTurnNumber() . ' phase ' . GetCurrentPhase() . ' live ' . implode('', GetLiveSeatsArray()) . "\n";
    }
    $res['steps'] = $step + 1;
}
ParseGamestate($swuDir);
if ($res['error'] === '' && !$res['finished']) $fail("no winner after {$args['maxSteps']} steps (round " . GetTurnNumber() . ')');
$res['winners'] = SWUGetGameWinners();
$res['rounds'] = intval(GetTurnNumber());
$res['eliminated'] = array_values(array_diff(GetSeatOrderArray(), GetLiveSeatsArray()));
foreach (GetSeatOrderArray() as $s) $res['baseHp'][$s] = SWUBaseRemainingHp($s);
// Twin Suns ends at the end of the phase in which a seat is eliminated, so a finished game has an elimination
// (or ended by deck-out/other loss that also eliminates). A finish without one would mean the end-game fired early.
if ($res['finished'] && empty($res['eliminated'])) $fail('game ended with no seat eliminated');

if (!$args['keep'] && $res['finished'] && $res['error'] === '') {
    $dir = $swuDir . 'Games/' . $gameName;
    if (is_dir($dir) && preg_match('/^\d+$/', strval($gameName))) { array_map('unlink', glob("$dir/{,.}[!.]*", GLOB_BRACE) ?: []); @rmdir($dir); }
}
$res['finished'] = $res['finished'] && $res['error'] === '';
echo "[TSGAME] " . json_encode($res, JSON_UNESCAPED_SLASHES) . "\n";
exit($res['finished'] ? 0 : 1);
