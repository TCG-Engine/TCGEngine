<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
//     php -d xdebug.mode=off DevTools/tdd-regression/test_swusim_botpractice_style.php
//
// ── WHY THIS FILE EXISTS ─────────────────────────────────────────────────────────────────────────
// Bot Practice's live bot picks its chooser from the SWUBotProfile game variable
// (SWUBotActiveChooserProfile, SWUSim/BotHeuristic.php), falling back to 'first-legal'. Until the menu
// was wired (2026-09-14) nothing ever wrote that variable, so every Bot Practice game a human could
// start played the first legal move, not the heuristic stack the RL bots spec built. The menu now sends
// a Play Style (botStyle = aggro | normal | control); APIs/Lobbies/JoinQueue.php keeps it on the lobby
// and SWUSim/CreateGame.php stores SWUBotProfile = heuristic-<style>.
//
// Like test_swusim_botpractice_joinqueue_lobby.php, this POSTs to the REAL endpoint over the
// container's loopback and then opens the game it created — no hand-built lobby.
//
// ⚠ CONTROLS: an unknown style falls back to Normal (a junk value must never leave the bot on
// first-legal), and a Hotseat game given a botStyle stores nothing (no bot drives it).

$ROOT = dirname(__DIR__, 2);
$BASEURL = 'http://localhost/TCGEngine/APIs/Lobbies/JoinQueue.php';
$deckA = @file_get_contents($ROOT . '/SWUSim/Tests/BotFixtures/premier_deck_a.txt');
$deckB = @file_get_contents($ROOT . '/SWUSim/Tests/BotFixtures/premier_deck_b.txt');

$checks = [];
$createdGames = [];
$note = function ($k, $v) use (&$checks) { $checks[$k] = ($v === true); };

if (!is_string($deckA) || !is_string($deckB)) {
    echo "FAIL: bot fixture decks missing under SWUSim/Tests/BotFixtures/\n";
    exit(1);
}

function BotStylePostQueue(string $url, array $fields): ?object {
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

require_once $ROOT . '/Core/EngineActionRunner.php';
EngineLoadRootRuntime('SWUSim');
require_once $ROOT . '/SWUSim/CreateGame.php';
require_once $ROOT . '/SWUSim/BotHeuristic.php';
$swuDir = $ROOT . '/SWUSim/';

// Open a created game; return its mode, bot seats, the stored profile and the chooser seat 2 would use.
function BotStyleReadGame(string $swuDir, $name): array {
    global $gameName, $playerID;
    $gameName = $name;
    $playerID = 1;
    ParseGamestate($swuDir);
    $stored = DecisionQueueController::GetVariable('SWUBotProfile');
    return [
        'mode'     => function_exists('SWUGameMode') ? SWUGameMode() : '?',
        'botSeats' => function_exists('GetSWUBotPlayers') ? GetSWUBotPlayers() : [],
        'stored'   => $stored === null ? '' : strval($stored),
        'active'   => SWUBotActiveChooserProfile(2),
    ];
}

$scenario = function (string $label, array $extra, callable $assert) use ($BASEURL, $deckA, $deckB, $swuDir, $note, &$createdGames) {
    $res = BotStylePostQueue($BASEURL, array_merge([
        'rootName' => 'SWUSim', 'format' => 'botpractice', 'queueType' => 'bo1',
        'deckLink' => $deckA, 'deckLink2' => $deckB,
    ], $extra));
    $note("$label: endpoint reports success", is_object($res) && ($res->success ?? false) === true);
    if (!is_object($res) || empty($res->gameName)) return;
    $createdGames[] = $res->gameName;
    $assert(BotStyleReadGame($swuDir, $res->gameName));
};

// A) Control → heuristic-control, and the live chooser resolves to it.
$scenario('A', ['botStyle' => 'control'], function ($g) use ($note) {
    $note('A: mode is botpractice', $g['mode'] === 'botpractice');
    $note('A: stored profile is heuristic-control', $g['stored'] === 'heuristic-control');
    $note('A: seat 2 would choose with heuristic-control', $g['active'] === 'heuristic-control');
    $note('A: that profile is registered', isset($GLOBALS['SWUBotChoosers']['heuristic-control']));
});
// B) Aggro.
$scenario('B', ['botStyle' => 'aggro'], function ($g) use ($note) {
    $note('B: stored profile is heuristic-aggro', $g['stored'] === 'heuristic-aggro');
});
// C) No style sent (an older client, or a direct request) → Normal, never first-legal.
$scenario('C', [], function ($g) use ($note) {
    $note('C: no botStyle → heuristic-normal', $g['stored'] === 'heuristic-normal' && $g['active'] === 'heuristic-normal');
});
// D) A junk style → Normal. Case is ignored.
$scenario('D', ['botStyle' => 'expert<script>'], function ($g) use ($note) {
    $note('D: junk botStyle → heuristic-normal', $g['stored'] === 'heuristic-normal');
});
$scenario('D2', ['botStyle' => 'CONTROL'], function ($g) use ($note) {
    $note('D2: botStyle is case-insensitive', $g['stored'] === 'heuristic-control');
});

// E) CONTROL — Hotseat given a botStyle stores nothing: no bot drives it.
$resE = BotStylePostQueue($BASEURL, [
    'rootName' => 'SWUSim', 'format' => 'hotseat', 'queueType' => 'bo1',
    'deckLink' => $deckA, 'deckLink2' => $deckB, 'botStyle' => 'control',
]);
$note('E: hotseat still succeeds', is_object($resE) && ($resE->success ?? false) === true);
if (is_object($resE) && !empty($resE->gameName)) {
    $createdGames[] = $resE->gameName;
    $g = BotStyleReadGame($swuDir, $resE->gameName);
    $note('E: hotseat stores no bot profile', $g['stored'] === '' && $g['botSeats'] === []);
}

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
