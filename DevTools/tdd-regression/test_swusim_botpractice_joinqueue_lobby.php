<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
//     php -d xdebug.mode=off DevTools/tdd-regression/test_swusim_botpractice_joinqueue_lobby.php
//
// ── WHY THIS FILE EXISTS ─────────────────────────────────────────────────────────────────────────
// Every other Bot Practice test builds its own lobby in-process. DevTools/SWUSimBotSelfPlayTest.php
// does exactly that, and for eight tasks its version — botPlayers=[1,2], a real deck at BOTH seats —
// was the only one anyone executed. The lobby the PUBLIC ENDPOINT builds was never run once, and it
// disagreed on every field that mattered: botpractice matched no arm in APIs/Lobbies/JoinQueue.php's
// local-mode branch, so seat 2 fell through to goldfish's `new Player(2, '', '')` empty sponge. Its
// deck load then failed, $deckLoadOk went false, QueuePregameSetup() never ran, and the game sat in
// phase APS with no hands, no mulligan and no base while the bot polled forever. Dead on arrival,
// through the only path a human can reach.
//
// So this test refuses to construct a lobby. It POSTs to the real endpoint over HTTP — the same
// request the menu will send in Phase 5 — and then opens the game the endpoint actually created and
// reads the seats out of it. A parallel construction here would reproduce exactly the blind spot it
// exists to close.
//
// It runs over the container's own loopback (http://localhost/TCGEngine/...) rather than by
// require-ing JoinQueue.php, for three reasons: JoinQueue `exit`s, so an in-process include gets one
// scenario per run and this needs four; the endpoint needs APCu, which the CLI SAPI lacks and the web
// SAPI has; and a loopback POST is literally the transport a browser uses.
//
// ⚠ CONTROLS ARE HALF THE POINT. Scenarios C and D assert goldfish and hotseat are UNCHANGED. The
// new arm is an addition to a public endpoint's shared branch, and "did not disturb the neighbours"
// is not something the botpractice assertions can show.

$ROOT = dirname(__DIR__, 2);
$BASEURL = 'http://localhost/TCGEngine/APIs/Lobbies/JoinQueue.php';
$deckA = @file_get_contents($ROOT . '/SWUSim/Tests/BotFixtures/premier_deck_a.txt');   // leader SOR_009
$deckB = @file_get_contents($ROOT . '/SWUSim/Tests/BotFixtures/premier_deck_b.txt');   // leader SOR_010

$checks = [];
$createdGames = [];
$note = function ($k, $v) use (&$checks) { $checks[$k] = ($v === true); };

if (!is_string($deckA) || !is_string($deckB)) {
    echo "FAIL: bot fixture decks missing under SWUSim/Tests/BotFixtures/\n";
    exit(1);
}

// POST the queue form exactly as the client does, and return the decoded JSON (or null).
function BotPracticePostQueue(string $url, array $fields): ?object {
    $ctx = stream_context_create(['http' => [
        'method'        => 'POST',
        'header'        => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content'       => http_build_query($fields),
        'timeout'       => 60,
        'ignore_errors' => true,
    ]]);
    $raw = @file_get_contents($url, false, $ctx);
    if (!is_string($raw)) return null;
    $j = json_decode($raw);
    return is_object($j) ? $j : null;
}

// ── Engine runtime, so the created game can be OPENED rather than guessed at ─────────────────────
// ParseGamestate falls back to Games/<id>/Gamestate.txt when the APCu entry is not visible, which is
// exactly this process's situation: the game was created in the web SAPI, this runs in the CLI one.
require_once $ROOT . '/Core/EngineActionRunner.php';
EngineLoadRootRuntime('SWUSim');
require_once $ROOT . '/SWUSim/CreateGame.php';
$swuDir = $ROOT . '/SWUSim/';

// Open a created game and return a flat summary of both seats.
function BotPracticeReadGame(string $swuDir, $name): array {
    global $gameName, $playerID;
    $gameName = $name;
    $playerID = 1;
    ParseGamestate($swuDir);
    $cardOf = function ($arr) {
        foreach ((array)$arr as $c) { if (empty($c->removed)) return strval($c->CardID ?? ''); }
        return '';
    };
    $liveCount = function ($arr) {
        $n = 0; foreach ((array)$arr as $c) { if (empty($c->removed)) $n++; } return $n;
    };
    return [
        'mode'      => function_exists('SWUGameMode') ? SWUGameMode() : '?',
        'botSeats'  => function_exists('GetSWUBotPlayers') ? GetSWUBotPlayers() : [],
        'phase'     => strval(GetCurrentPhase()),
        'flash'     => strval(GetFlashMessage()),
        // The opening decision queue. A seat whose deck failed to load has an EMPTY queue, because
        // $deckLoadOk=false skips QueuePregameSetup() entirely — that is the DOA fingerprint.
        'queued'    => [1 => count(GetDecisionQueue(1)), 2 => count(GetDecisionQueue(2))],
        'queueHead' => [1 => strval(GetDecisionQueue(1)[0]->Param ?? ''),
                        2 => strval(GetDecisionQueue(2)[0]->Param ?? '')],
        'leader'    => [1 => $cardOf(GetLeader(1)),  2 => $cardOf(GetLeader(2))],
        'base'      => [1 => $cardOf(GetBase(1)),    2 => $cardOf(GetBase(2))],
        'deck'      => [1 => $liveCount(GetDeck(1)), 2 => $liveCount(GetDeck(2))],
        'hand'      => [1 => $liveCount(GetHand(1)), 2 => $liveCount(GetHand(2))],
    ];
}

// ════════════════════════════════════════════════════════════════════════════════════════════════
// A) THE REGRESSION ITSELF — botpractice through the real endpoint gives seat 2 a REAL deck
// ════════════════════════════════════════════════════════════════════════════════════════════════
$resA = BotPracticePostQueue($BASEURL, [
    'rootName'  => 'SWUSim',
    'format'    => 'botpractice',
    'queueType' => 'bo1',
    'deckLink'  => $deckA,
    'deckLink2' => $deckB,
]);
$note('A: endpoint returns JSON', is_object($resA));
$note('A: endpoint reports success', is_object($resA) && ($resA->success ?? false) === true);
$note('A: endpoint returns a gameName', is_object($resA) && !empty($resA->gameName));

if (is_object($resA) && !empty($resA->gameName)) {
    $createdGames[] = $resA->gameName;
    $g = BotPracticeReadGame($swuDir, $resA->gameName);

    $note('A: game mode is botpractice', $g['mode'] === 'botpractice');
    // THE assertion. deckLink2's leader is SOR_010; deckLink's is SOR_009. An empty string is the
    // goldfish sponge (the bug); SOR_009 would mean deckLink2 was ignored and P1's list reused.
    $note('A: seat 2 has a leader from deckLink2 (SOR_010)', $g['leader'][2] === 'SOR_010');
    $note('A: seat 1 has its own leader (SOR_009)',          $g['leader'][1] === 'SOR_009');
    $note('A: seat 2 has a real base',                       $g['base'][2] !== '');
    $note('A: seat 2 has a real deck',                       $g['deck'][2] > 0);
    // Pregame actually ran. This is the symptom the goldfish sponge produced: no hands anywhere,
    // because seat 2's failed load set $deckLoadOk=false and skipped QueuePregameSetup() for BOTH.
    $note('A: seat 1 drew an opening hand',                  $g['hand'][1] > 0);
    $note('A: seat 2 drew an opening hand',                  $g['hand'][2] > 0);
    // ⚠ NOT a phase assertion. A healthy game is legitimately still in phase APS here — the mulligan
    // runs inside it — so 'phase !== APS' is not the DOA signal even though the dead game shows APS.
    // The signal that separates them is the QUEUE: pregame setup either ran and asked both seats for
    // a mulligan, or never ran at all and asked nobody anything.
    $note('A: seat 1 was asked to mulligan',                 $g['queueHead'][1] === 'mulligan');
    $note('A: seat 2 was asked to mulligan',                 $g['queueHead'][2] === 'mulligan');
    $note('A: no deck-load failure in the flash message',
          stripos($g['flash'], 'could not load') === false && stripos($g['flash'], 'no deck link') === false);
    // Seat 1 is the HUMAN. Defaulting botPlayers to GA's [1,2] would hand the human's seat to the bot.
    $note('A: bot drives seat 2 only, by default',           $g['botSeats'] === [2]);
}

// ════════════════════════════════════════════════════════════════════════════════════════════════
// B) the botPlayers POST field is HONOURED, not dropped
//    JoinQueue used to hardcode `botPlayers = $isGABot ? $gaBotPlayers : []` and this worked only by
//    accident, via SWUSim/CreateGame.php's own empty->[2] fallback. Ask for both seats and check.
// ════════════════════════════════════════════════════════════════════════════════════════════════
$resB = BotPracticePostQueue($BASEURL, [
    'rootName'   => 'SWUSim',
    'format'     => 'botpractice',
    'queueType'  => 'bo1',
    'deckLink'   => $deckA,
    'deckLink2'  => $deckB,
    'botPlayers' => '1,2',
]);
$note('B: endpoint reports success', is_object($resB) && ($resB->success ?? false) === true);
if (is_object($resB) && !empty($resB->gameName)) {
    $createdGames[] = $resB->gameName;
    $g = BotPracticeReadGame($swuDir, $resB->gameName);
    $note('B: botPlayers=1,2 reaches the game', $g['botSeats'] === [1, 2]);
    $note('B: both seats still have real decks', $g['deck'][1] > 0 && $g['deck'][2] > 0);
}

// ════════════════════════════════════════════════════════════════════════════════════════════════
// C) CONTROL — goldfish is UNCHANGED: seat 2 is still the empty passive sponge
// ════════════════════════════════════════════════════════════════════════════════════════════════
$resC = BotPracticePostQueue($BASEURL, [
    'rootName'  => 'SWUSim',
    'format'    => 'goldfish',
    'queueType' => 'bo1',
    'deckLink'  => $deckA,
    'deckLink2' => $deckB,   // deliberately supplied: goldfish must still IGNORE it
]);
$note('C: goldfish still succeeds', is_object($resC) && ($resC->success ?? false) === true);
if (is_object($resC) && !empty($resC->gameName)) {
    $createdGames[] = $resC->gameName;
    $g = BotPracticeReadGame($swuDir, $resC->gameName);
    $note('C: goldfish mode intact',              $g['mode'] === 'goldfish');
    $note('C: goldfish seat 2 stays empty',       $g['leader'][2] === '' && $g['deck'][2] === 0);
    $note('C: goldfish seat 1 still set up',      $g['leader'][1] === 'SOR_009' && $g['hand'][1] > 0);
    $note('C: goldfish drives no bot seats',      $g['botSeats'] === []);
}

// ════════════════════════════════════════════════════════════════════════════════════════════════
// D) CONTROL — hotseat is UNCHANGED: seat 2 gets deckLink2, and no bot drives anything
// ════════════════════════════════════════════════════════════════════════════════════════════════
$resD = BotPracticePostQueue($BASEURL, [
    'rootName'  => 'SWUSim',
    'format'    => 'hotseat',
    'queueType' => 'bo1',
    'deckLink'  => $deckA,
    'deckLink2' => $deckB,
]);
$note('D: hotseat still succeeds', is_object($resD) && ($resD->success ?? false) === true);
if (is_object($resD) && !empty($resD->gameName)) {
    $createdGames[] = $resD->gameName;
    $g = BotPracticeReadGame($swuDir, $resD->gameName);
    $note('D: hotseat mode intact',           $g['mode'] === 'hotseat');
    $note('D: hotseat seat 2 uses deckLink2', $g['leader'][2] === 'SOR_010' && $g['deck'][2] > 0);
    $note('D: hotseat drives no bot seats',   $g['botSeats'] === []);
}

// ── clean up the games this test created ────────────────────────────────────────────────────────
foreach ($createdGames as $gn) {
    $dir = $swuDir . 'Games/' . $gn;
    if (is_dir($dir)) {
        array_map('unlink', glob($dir . '/*') ?: []);
        @rmdir($dir);
    }
}

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
if (empty($checks)) { echo "FAIL: no checks ran (endpoint unreachable?)\n"; exit(1); }
if ($fails) {
    echo "FAIL (" . count($fails) . "/" . count($checks) . "):\n";
    foreach ($fails as $f) echo "  - $f\n";
    exit(1);
}
echo "PASS (" . count($checks) . " checks)\n";
exit(0);
