<?php
// BotData — the per-action RECORDER (spec docs/superpowers/specs/2026-09-23-swusim-bot-data-loop-design.md §1).
//
// ⚠ THE LOOKAHEAD TRAP. SWUBotLookahead() dispatches REAL actions in memory and restores the game
// byte-identically. Those dispatches go through ActionMap/CustomWidgetInput, which call
// SaveUndoVersion() -> _SWUOpenAction() — the very seam this recorder hooks. Unguarded, every
// hypothetical the bot evaluates is written as though it happened, which is both the opposite of
// "only what actually happened" (owner ruling 2026-09-23) and, with SWU_BOT_LOOKAHEAD_BUDGET at 32
// per rule decision, far more rows than the real game. Hence the depth counter below.
//
// It must be a DEPTH, not a boolean: _SWUBotLookaheadContinue() nests lookaheads inside lookaheads,
// so an inner exit would otherwise re-enable recording while an outer one is still running.
//
// ⚠ RECORDING MUST NEVER BREAK A GAME. Every write is wrapped: a failure degrades to "no data".
require_once __DIR__ . '/BotDataSnapshot.php';

function SWUBotDataEnterLookahead(): void {
    $GLOBALS['SWUBotDataLookaheadDepth'] = intval($GLOBALS['SWUBotDataLookaheadDepth'] ?? 0) + 1;
}
function SWUBotDataExitLookahead(): void {
    $GLOBALS['SWUBotDataLookaheadDepth'] = max(0, intval($GLOBALS['SWUBotDataLookaheadDepth'] ?? 0) - 1);
}
function SWUBotDataSuppressed(): bool {
    return intval($GLOBALS['SWUBotDataLookaheadDepth'] ?? 0) > 0;
}

// The directory this game records into, or '' when it does not record at all.
//
// ⚠ HUMAN-VS-BOT ONLY (owner ruling 2026-09-23: "BotData needs to be only human vs bot data"). The
// mode check alone is NOT enough: the self-play harness runs in botpractice mode too and sets
// botPlayers = [1,2] (DevTools/SWUSimBotSelfPlayTest.php:533). Bot-vs-bot games are already covered by
// the sweep tooling (sweep_fixtures.sh / retro.py / the strength test), and letting them in means a
// sweep run during a recording session silently dilutes the bundle — measured: a 2-game self-play
// smoke corpus produced a plausible-looking card finding that was an artifact of two 5-round bot
// games. So: at least one seat must be a bot, and at least one must NOT be.
function SWUBotDataDir(): string {
    if (!function_exists('SWUGameMode') || SWUGameMode() !== 'botpractice') return '';
    $bots = function_exists('GetSWUBotPlayers') ? GetSWUBotPlayers() : [];
    $seats = function_exists('SeatCountForGame') ? intval(SeatCountForGame()) : 2;
    if (count($bots) < 1 || count($bots) >= $seats) return '';
    $g = preg_replace('/[^A-Za-z0-9_]/', '', strval($GLOBALS['gameName'] ?? ''));
    if ($g === '') return '';
    return __DIR__ . '/../BotData/' . $g;
}

// Append one JSON line. Returns false on any failure; never throws.
function SWUBotDataAppend(string $stream, array $row): bool {
    $dir = SWUBotDataDir();
    if ($dir === '') return false;
    try {
        if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) return false;
        $line = json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($line === false) return false;
        // LOCK_EX: a single game can be written by overlapping requests — the bot's poll and the
        // human's action. Separate games never share a file (the dir is keyed by gameName).
        return @file_put_contents($dir . '/' . $stream . '.jsonl', $line . "\n", FILE_APPEND | LOCK_EX) !== false;
    } catch (\Throwable $e) {
        return false;
    }
}

// What the in-flight action is. The identity lives with the CALLER, not with the seam: _SWUOpenAction()
// is reached through SaveUndoVersion($playerID), which takes no action argument. ActionMap() and
// CustomWidgetInput() — the only two entry points that act — stash it in SWUBotDataInFlight, and this
// reads it back. Both the production request path AND the bot lookahead's in-memory dispatcher go
// through those two functions, so a test observes exactly what production writes.
//
// An unrecognised shape degrades to kind 'other' rather than dropping the row: the snapshot is still
// the position, which is the valuable half.
function SWUBotDataCurrentAction(): array {
    $f = $GLOBALS['SWUBotDataInFlight'] ?? [];
    $mz = strval($f['mz'] ?? '');
    $verb = strval($f['verb'] ?? '');
    $kind = 'other';
    if ($verb === 'FSM')                           $kind = str_starts_with($mz, 'myHand-') ? 'play' : 'attack';
    elseif (str_contains($verb, 'DeployLeader'))   $kind = 'deploy';
    elseif (str_contains($verb, 'LeaderAbility'))  $kind = 'ability';
    elseif (str_contains($verb, 'TakeInitiative')) $kind = 'initiative';
    elseif (str_contains($verb, 'Smuggle'))        $kind = 'smuggle';
    elseif ($verb !== '')                          $kind = 'custom:' . $verb;
    $card = '';
    if (str_starts_with($mz, 'myHand-')) {
        $o = GetHand(intval($GLOBALS['playerID'] ?? 0))[intval(substr($mz, strlen('myHand-')))] ?? null;
        if ($o !== null) $card = strval($o->CardID ?? '');
    }
    return ['kind' => $kind, 'card' => $card, 'mz' => $mz];
}

// Written ONCE, at game end. meta.json is the join key: without the outcome a trajectory teaches
// nothing. The cumulative GameLog lives HERE and nowhere else — carrying it in a per-action snapshot
// would be quadratic (measured on a real game: 82 KB of its state was log).
//
// ⚠ NOT hung off the Match layer's captureGameDetail hook. An Arenabot game sets isGoldfish
// (APIs/Lobbies/JoinQueue.php:293) and match creation is gated on !isGoldfish (:425), so it has NO
// Match record and that hook never fires for the games we record. SWUDeclareGameWinner() is the
// unified commit point every ending reaches, and it is idempotent.
function SWUBotDataFinalize(int $winner): void {
    // ⚠ SUPPRESSED INSIDE A LOOKAHEAD, for a reason that is not obvious: SWUBotLookahead restores the
    // GAMESTATE byte-identically, but nothing restores the FILESYSTEM. Rule 'break-lethal'
    // (BotRules.php) dispatches every candidate ATTACK through the lookahead, so a hypothetical lethal
    // reaches CombatLogic's SWUDeclareGameWinner() -> here. Without this line that hypothetical writes
    // meta.json with the wrong winner, and the idempotence guard below then blocks the REAL result
    // forever — silently inverting the outcome label on exactly the close games worth analysing.
    if (SWUBotDataSuppressed()) return;
    $dir = SWUBotDataDir();
    if ($dir === '') return;
    // Idempotent, like the commit point it hangs off — but a ZERO-BYTE meta.json is a failed write, not
    // a finalized game, and must not block the retry.
    if (is_file($dir . '/meta.json') && filesize($dir . '/meta.json') > 0) return;
    try {
        if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) return;
        // Close the trajectory. Every other row is a PRE-action snapshot, so without this the last
        // action of the game — the killing blow — has no after-state and its effect is invisible.
        SWUBotDataAppend('states', SWUBotDataSnapshot(intval($GLOBALS['playerID'] ?? 0), 'system',
            ['kind' => 'final', 'card' => '', 'mz' => '']));
        $live = fn($z) => array_values(array_filter((array)$z, fn($o) => $o !== null && empty($o->removed)));
        $meta = ['finished' => true, 'rootName' => 'SWUSim',
                 'rounds'   => function_exists('GetTurnNumber') ? intval(GetTurnNumber()) : 0,
                 'winner'   => $winner,
                 'botSeats' => function_exists('GetSWUBotPlayers') ? GetSWUBotPlayers() : [],
                 'botStyle' => class_exists('DecisionQueueController')
                     ? strval(DecisionQueueController::GetVariable('SWUBotProfile') ?? '') : '',
                 'cardPool' => function_exists('SWUGameCardPool') ? SWUGameCardPool() : '',
                 'leader'   => [], 'base' => [], 'baseHpLeft' => [], 'deckRemaining' => []];
        foreach ([1, 2] as $s) {
            $l = GetLeader($s); $b = GetBase($s);
            $meta['leader'][strval($s)] = empty($l) ? '' : strval($l[0]->CardID ?? '');
            $meta['base'][strval($s)]   = empty($b) ? '' : strval($b[0]->CardID ?? '');
            $meta['baseHpLeft'][strval($s)] = SWUBaseRemainingHp($s);
            // ⚠ What is LEFT in the deck at game end — NOT what the player brought. Deliberately named:
            // the first version called this 'deck', and a 50-card deck read as 34 cards in the Task 8
            // acceptance run, which would have made every card-level report quietly wrong.
            $meta['deckRemaining'][strval($s)] = array_map(fn($o) => strval($o->CardID ?? ''), $live(GetDeck($s)));
        }
        // The STARTING deck lists. Not derivable at game end — cards have scattered into hand, discard,
        // resources, play and removed-from-game — so they come from the creation inputs SWUSetupGame
        // persisted for the Rematch button. '' when unavailable (a game created before that existed).
        $meta['deckList'] = ['1' => '', '2' => ''];
        $rm = __DIR__ . '/../Games/' . preg_replace('/[^A-Za-z0-9_]/', '', strval($GLOBALS['gameName'] ?? '')) . '/Rematch.json';
        if (is_file($rm)) {
            $r = json_decode(strval(@file_get_contents($rm)), true);
            if (is_array($r)) {
                $meta['deckList']['1'] = strval($r['deckLink'] ?? '');
                $meta['deckList']['2'] = strval($r['deckLink2'] ?? '');
            }
        }
        // ⚠ $gGameLog is a single <NL>-joined STRING in SWUSim (GamestateParser.php), so this is a
        // one-element array holding the whole blob — a reader splits on '<NL>' and parses
        // 'TYPE|VISIBILITY|text'. is_string first: an unexpected object here would throw, and the catch
        // would silently cost the game BOTH meta.json and replay.json.
        $log = function_exists('GetGameLog') ? GetGameLog() : '';
        $meta['gameLog'] = is_array($log) ? array_map('strval', $log) : (is_string($log) ? [$log] : ['']);
        // A json_encode failure (invalid UTF-8 in a card text or log line) must not leave a zero-byte
        // meta.json behind — the guard at the top would then treat the game as finalized forever.
        $encoded = json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($encoded === false) return;
        @file_put_contents($dir . '/meta.json', $encoded, LOCK_EX);

        $cmds = function_exists('GetMatchReplayCommands') ? GetMatchReplayCommands() : [];
        @file_put_contents($dir . '/replay.json',
            json_encode(['commands' => $cmds], JSON_UNESCAPED_SLASHES), LOCK_EX);
    } catch (\Throwable $e) {
    }
}

// Read a game's meta, tolerating an ABANDONED game that never finalized. Never returns null: an
// unfinished game is a legitimate row in the corpus, not a parse failure.
function SWUBotDataReadMeta(string $dir): array {
    $p = rtrim($dir, '/') . '/meta.json';
    if (!is_file($p)) return ['finished' => false];
    $m = json_decode(strval(@file_get_contents($p)), true);
    if (!is_array($m)) return ['finished' => false];
    $m['finished'] = true;
    return $m;
}

// Undo (Bot Practice allows it). The rejected attempt is still data — an analysis wants to know the
// human tried a line and took it back — so history is APPENDED to, never rewritten. A reader that
// wants only the realised line drops the action rows back to the preceding 'undo' marker. The
// snapshot here is the position AFTER the rollback, which is where play actually resumes.
// $kind: 'undo' (LoadUndoSnapshot) or 'bookmark' (SWULoadBookmark). Both rewind the board, and both
// leave a backwards jump a reader cannot otherwise detect.
//
// ⚠ A bookmark load also CLEARS GAMEOVER_WINNER, making an already-ended game live again — so any
// meta.json from that first ending is now stale AND, being non-empty, would block the real one. It is
// removed here so the game's actual conclusion re-finalizes.
function SWUBotDataMarkRewound(string $kind = 'undo'): void {
    if (SWUBotDataSuppressed()) return;
    $dir = SWUBotDataDir();
    if ($dir === '') return;
    try {
        SWUBotDataAppend('states', SWUBotDataSnapshot(intval($GLOBALS['playerID'] ?? 0), 'system',
            ['kind' => $kind, 'card' => '', 'mz' => '']));
        if ($kind === 'bookmark' && is_file($dir . '/meta.json')) @unlink($dir . '/meta.json');
    } catch (\Throwable $e) {
    }
}

// Back-compat alias for the undo hook's original name.
function SWUBotDataMarkUndone(): void { SWUBotDataMarkRewound('undo'); }

// Called from ExecuteSWUAttack(), the ONE point where the attacker and the RESOLVED target are both
// known. The open-time snapshot cannot carry a target: it is taken before the target is chosen.
//
// Emitted as its own row (kind 'attack-resolved') rather than by patching the open row, so a declared
// attack that never resolves is still visible and nothing is ever lost. ⚠ _SWUMaulBeginDoubleAttack
// deliberately bypasses ExecuteSWUAttack (its own header says so), so that one card's double attack
// produces the open row only.
function SWUBotDataRecordAttack($attacker, string $attackerMz, string $targetMz): void {
    if (SWUBotDataSuppressed() || SWUBotDataDir() === '') return;
    try {
        $seat = intval($GLOBALS['playerID'] ?? 0);
        $botSeats = function_exists('GetSWUBotPlayers') ? GetSWUBotPlayers() : [];
        $actor = in_array($seat, $botSeats, true) ? 'bot'
               : (($seat >= 1 && $seat <= (function_exists('SeatCountForGame') ? SeatCountForGame() : 2)) ? 'human' : 'system');
        $isBase = stripos($targetMz, 'Base') !== false;
        $tObj = null;
        if (!$isBase && $targetMz !== '') {
            $saved = $GLOBALS['playerID'] ?? 0; $GLOBALS['playerID'] = $seat;
            $tObj = GetZoneObject($targetMz);
            $GLOBALS['playerID'] = $saved;
        }
        $row = SWUBotDataSnapshot($seat, $actor, [
            'kind' => 'attack-resolved',
            'card' => strval($attacker->CardID ?? ''),
            'mz'   => $attackerMz,
        ]);
        $row['action']['target'] = [
            'kind' => $isBase ? 'base' : 'unit',
            'mz'   => $targetMz,
            'id'   => $isBase ? '' : strval(is_object($tObj) ? ($tObj->CardID ?? '') : ''),
        ];
        SWUBotDataAppend('states', $row);
    } catch (\Throwable $e) {
    }
}

// Called from _SWUOpenAction(). One snapshot per real, user-initiated action.
function SWUBotDataRecordAction(): void {
    if (SWUBotDataSuppressed()) return;
    if (SWUBotDataDir() === '') return;
    try {
        $seat = intval($GLOBALS['playerID'] ?? 0);
        $botSeats = function_exists('GetSWUBotPlayers') ? GetSWUBotPlayers() : [];
        // ⚠ "not a bot seat" must NOT silently mean "the person". The pre-game setup flow acts on
        // seat 0, and a real Arenabot game's seat 1 IS the human — so a seat-0 row labelled 'human'
        // would put engine bookkeeping into the human-play corpus. Found in a real recorded game.
        $actor = in_array($seat, $botSeats, true) ? 'bot'
               : (($seat >= 1 && $seat <= (function_exists('SeatCountForGame') ? SeatCountForGame() : 2)) ? 'human' : 'system');
        SWUBotDataAppend('states', SWUBotDataSnapshot($seat, $actor, SWUBotDataCurrentAction()));
    } catch (\Throwable $e) {
        // A data-collection feature must never break a game someone is playing.
    }
}
