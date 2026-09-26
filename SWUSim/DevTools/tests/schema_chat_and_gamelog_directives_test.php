<?php
// ── WithGameLog: / WithChat: — seeding the two streams the merged sidebar panel interleaves ─────────
//
// Owner, 2026-09-26: "do 2 now. makes sense if we want other visual tests down the road."
//
// WHY THESE EXIST. Tests/Visual/Chat_4P_WhisperMatrix.md could not be a schema: the board it needs has
// chat rows AND game-log lines, and the DSL could express neither. So it shipped as a CLI fixture
// (swusim_make_chat_matrix.php) that drove eight HTTP sends with usleep(120000) between them — which
// made it the 1-in-5 Visual doc that does NOT load in the Test Schema Editor, and the owner lost four
// attempts to a silent empty board before asking. Two directives close that gap for every future
// log/chat visual test, not just this one.
//
// ⚠ DECLARATION ORDER IS THE CONTRACT. The panel merges the two streams by TIMESTAMP alone, so a
// directive pair that cannot interleave is useless. WithGameLog and WithChat are therefore applied in
// the order they appear in the GIVEN block, ACROSS BOTH KEYS — which _parseGiven's key→values map
// cannot express, so the applier walks the raw ordered lines instead.
//
// ⚠ THE CHAT ROW IS WRITTEN BY THE FUNCTION SubmitChat.php CALLS (ChatAppendMessage), not by a
// hand-built array. The row shape is a contract with GetChat.php and the poll's CHATONLY branch —
// id/playerID/playerLabel/text/time/ts and 'to' ONLY on whispers — and a fixture that reimplements it
// drifts silently the first time a field is added. Same reason the log goes through AddGameLogEntry().
//
//     php -d xdebug.mode=off -d apc.enable_cli=1 SWUSim/DevTools/tests/schema_chat_and_gamelog_directives_test.php
$FAILS = 0;
function check($cond, $msg, $extra = null) {
    global $FAILS;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . (($cond || $extra === null) ? '' : '  ' . json_encode($extra)) . "\n";
    if (!$cond) $FAILS++;
}

if (!defined('STDERR')) define('STDERR', fopen('php://stderr', 'w'));
chdir(realpath(__DIR__ . '/../../..'));
$_GET['mode'] = 'cli';
if (!function_exists('ConvertMzIDToAbsolute')) { function ConvertMzIDToAbsolute($mzID, $p): string { return ''; } }
if (!function_exists('QueueDamageAnimation')) { function QueueDamageAnimation($t, $a): void {} }
if (!function_exists('QueueRestoreAnimation')) { function QueueRestoreAnimation($t, $a): void {} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t): void {} }
if (!function_exists('QueueShieldBreakAnimation')) { function QueueShieldBreakAnimation($t): void {} }
foreach (['./AccountFiles/AccountSessionAPI.php', './Core/HTTPLibraries.php', './Core/DeterministicRNG.php',
          './Core/CoreZoneModifiers.php', './Core/GameAuth.php', './Core/NetworkingLibraries.php',
          './Core/ChatConversation.php', './Core/ChatWhisper.php',
          './SWUSim/ZoneClasses.php', './SWUSim/ZoneAccessors.php',
          './SWUSim/GeneratedCode/GeneratedCardDictionaries.php', './SWUSim/GamestateParser.php',
          './SWUSim/Tests/Framework/Assertions.php', './SWUSim/Tests/Framework/Cards.php',
          './SWUSim/Tests/Framework/CommonSetup.php', './SWUSim/Tests/Framework/GameStateBuilder.php',
          './SWUSim/Tests/Framework/GameTestAdapter.php', './SWUSim/Tests/Framework/SchemaTestRunner.php',
          './SWUSim/Tests/Framework/TestRunner.php'] as $f) include_once $f;

check(function_exists('apcu_fetch') && apcu_enabled(),
      'APCu is on (this file needs -d apc.enable_cli=1; without it every chat assertion is vacuous)');

// A four-seat board with the two streams interleaved, exactly as the Visual doc seeds them.
$GIVEN = <<<'MD'
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP3Leader: SHD_014
WithP3Leader2: SHD_015
WithP4Leader: TWI_009
WithP4Leader2: TWI_010
WithChat: 1|gl hf everyone
WithGameLog: ATTACK|ALL|P1's [[SOR_046|Rebel Trooper]] attacked P3's base for 3 damage
WithChat: 2|may the best table win
WithGameLog: PLAY|ALL|P2 played [[SEC_080|Imperial Dark Trooper]]
WithChat: 1>3|P3 - I will not attack you this round
WithGameLog: DRAW|ALL|P2 drew a card
WithChat: 2>1,4|P1 and P4 - P3 is the threat here
MD;

// The runner needs a game name for the chat scope; the editor sets this before building.
global $gameName;
$gameName = '990' . substr((string)time(), -6);
apcu_delete(GetChatMessagesCacheKey($gameName));

ob_start();
$res = SchemaTestRunner::runString("# Q\n## GIVEN\n{$GIVEN}\n## WHEN\n## EXPECT\nSEATCOUNT:4\n", 'probe');
ob_end_clean();
check($res->passed, 'the four-seat board with both directives builds and passes its EXPECT',
      $res->passed ? null : [$res->message, $res->failedExpects]);

// ── 1. THE GAME LOG ─────────────────────────────────────────────────────────────────────────────────
$log = GetGameLog();
$entries = explode('<NL>', (string)$log);
check(count($entries) === 3, 'three WithGameLog lines produced three log entries', count($entries));

$parsed = array_map('SWUParseGameLogEntry', $entries);
check(($parsed[0]['type'] ?? '') === 'ATTACK' && ($parsed[1]['type'] ?? '') === 'PLAY'
      && ($parsed[2]['type'] ?? '') === 'DRAW',
      'the entries kept their TYPE and their declared ORDER',
      array_column($parsed, 'type'));
check(strpos($parsed[0]['text'] ?? '', '[[SOR_046|Rebel Trooper]]') !== false,
      'a card token survives the parse — the text field may contain PIPES, so the split is limited',
      $parsed[0]['text'] ?? null);
check(($parsed[0]['visibility'] ?? '') === 'ALL', 'visibility is carried through', $parsed[0]['visibility'] ?? null);

// The stamp is what the panel orders by. A 3-field legacy entry would sort as if it had none.
$logTs = array_map(fn($p) => floatval($p['ts'] ?? 0), $parsed);
check(min($logTs) > 0, 'every entry carries an @microtime stamp (not the legacy 3-field shape)', $logTs);
check($logTs[0] < $logTs[1] && $logTs[1] < $logTs[2],
      'log stamps strictly INCREASE in declaration order', $logTs);

// ── 2. THE CHAT ROWS ────────────────────────────────────────────────────────────────────────────────
$msgs = apcu_fetch(GetChatMessagesCacheKey($gameName));
$msgs = ($msgs === false) ? [] : $msgs;
check(count($msgs) === 4, 'four WithChat lines produced four chat rows', count($msgs));

check(($msgs[0]['playerID'] ?? '') === '1' && ($msgs[0]['playerLabel'] ?? '') === 'P1',
      'a public row carries the seat and the P-label SubmitChat would have written', $msgs[0] ?? null);
check(($msgs[0]['text'] ?? '') === 'gl hf everyone', 'and its text verbatim', $msgs[0]['text'] ?? null);
check(!array_key_exists('to', $msgs[0] ?? ['to' => 1]),
      'a PUBLIC row has NO "to" key — that is the shape every existing reader branches on');
check(array_keys($msgs[0] ?? []) === ['id', 'playerID', 'playerLabel', 'text', 'time', 'ts'],
      'the public row shape matches SubmitChat byte for byte', array_keys($msgs[0] ?? []));

check(($msgs[2]['to'] ?? null) === [3], 'a single-target whisper records to=[3]', $msgs[2]['to'] ?? null);
check(($msgs[3]['to'] ?? null) === [1, 4], 'a multi-target whisper records to=[1,4]', $msgs[3]['to'] ?? null);
check(($msgs[3]['playerLabel'] ?? '') === 'P2', 'and the sender seat is the one before the ">"', $msgs[3] ?? null);

$ids = array_column($msgs, 'id');
check($ids === [1, 2, 3, 4], 'ids increment from 1 exactly as SubmitChat allocates them', $ids);

// ── 3. THE INTERLEAVE — the entire reason both directives exist ─────────────────────────────────────
// Declared order is chat, log, chat, log, chat, log, chat. If the two streams were applied in two
// separate passes, every chat ts would sort before (or after) every log stamp and the panel would show
// two clumps. This is the assertion that catches that, and nothing else does.
$chatTs = array_map(fn($m) => floatval($m['ts'] ?? 0), $msgs);
$merged = [];
foreach ($chatTs as $i => $t) $merged[] = ['t' => $t, 'kind' => 'chat', 'i' => $i];
foreach ($logTs  as $i => $t) $merged[] = ['t' => $t, 'kind' => 'log',  'i' => $i];
usort($merged, fn($a, $b) => $a['t'] <=> $b['t']);
$shape = implode(',', array_map(fn($m) => $m['kind'], $merged));
check($shape === 'chat,log,chat,log,chat,log,chat',
      'sorting BOTH streams by timestamp reproduces the declared interleaving', $shape);

// ── 4. REFUSALS. A directive that cannot be honoured must say so, not half-apply. ───────────────────
function refusal(string $given): string {
    ob_start();
    try { SchemaTestRunner::runString("# Q\n## GIVEN\n{$given}\n## WHEN\n## EXPECT\nTURNPLAYER:1\n", 'probe');
          ob_end_clean(); return ''; }
    catch (Throwable $e) { ob_end_clean(); return $e->getMessage(); }
}
$base = "CommonSetup: bbw/rrk\nWithGamePhase: ActionPhase\n";
check(stripos(refusal($base . "WithGameLog: just some text"), 'WithGameLog') !== false,
      'WithGameLog without the TYPE|VIS| prefix is refused');
check(stripos(refusal($base . "WithChat: 9|hello"), 'seat') !== false,
      'WithChat from a seat that does not exist in this game is refused');
check(stripos(refusal($base . "WithChat: 1>9|hello"), 'whisper') !== false,
      'a whisper to a seat that does not exist is refused');
check(stripos(refusal($base . "WithChat: 1|"), 'empty') !== false,
      'an empty chat message is refused');
// The typo guard must still cover the new keys' neighbourhood.
check(stripos(refusal($base . "WithGameLogs: ATTACK|ALL|x"), 'Unknown GIVEN directive') !== false,
      'a near-miss spelling is still caught by the unknown-key guard');

echo $FAILS === 0 ? "\nALL PASS\n" : "\n{$FAILS} FAILED\n";
exit($FAILS === 0 ? 0 : 1);
