<?php
// SWU-PGN writer: recording must not change the game. One seeded bot self-play game is played twice — not recorded,
// then recorded (the harness's --swupgn) — and the two final gamestates must be byte-identical (the game's own name
// aside; the recording lives in Games/<id>/Pgn/, outside the gamestate). The recorded game's files must also exist and
// validate, so the second run is known to have recorded at all.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_writer_rng_invariance.php [seed]
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
require_once __DIR__ . '/../../AppCore/SWU/SwuPgn/SwuPgn.php';
chdir(__DIR__ . '/../..');
$seed = $argv[1] ?? 'swupgnrng01';
const SWUPGN_RNG_DECKS = 'SWUSim/Tests/BotFixtures/ash-meta-2026-09/';

// One game: [game name, final Gamestate.txt, the harness's [RESULT] line].
$play = function (bool $record) use ($seed): array {
    $cmd = 'php -d apc.enable_cli=1 DevTools/SWUSimBotSelfPlayTest.php --verbose --seed=' . escapeshellarg($seed)
        . ' --deck=' . SWUPGN_RNG_DECKS . 'darth-vader_jtl_yellow.txt --deck2=' . SWUPGN_RNG_DECKS . 'admiral-piett_jtl_blue.txt'
        . ($record ? ' --swupgn' : '') . ' 2>&1';
    exec($cmd, $out);
    $name = ''; $result = '';
    foreach ($out as $l) {
        if (preg_match('/^\[INFO\] game created: (\S+)/', $l, $m)) $name = $m[1];
        if (str_starts_with($l, '[RESULT] ')) $result = $l;
    }
    $gs = $name !== '' && is_file("SWUSim/Games/$name/Gamestate.txt") ? strval(file_get_contents("SWUSim/Games/$name/Gamestate.txt")) : '';
    return [$name, $gs, $result];
};

[$n0, $gs0, $r0] = $play(false);
[$n1, $gs1, $r1] = $play(true);
SwuPgnTestCheck($n0 !== '' && $n1 !== '' && $gs0 !== '' && $gs1 !== '', 'both games ran and left a gamestate', [$n0, $n1, strlen($gs0), strlen($gs1)]);
SwuPgnTestCheck(str_contains($r0, '"fail":0') && str_contains($r0, '"winner":'), 'the unrecorded game finished cleanly', substr($r0, 0, 200));
SwuPgnTestCheck(!is_dir("SWUSim/Games/$n0/Pgn"), 'the unrecorded game has no recording');
$out = "SWUSim/Games/$n1/Pgn/out";
$all = is_file("$out/all.swupgn") ? strval(file_get_contents("$out/all.swupgn")) : '';
$v = $all === '' ? ['errors' => [['message' => 'no all.swupgn']]] : SwuPgnValidate(SwuPgnParse($all));
SwuPgnTestCheck($all !== '' && $v['errors'] === [], 'the recorded game wrote a valid all.swupgn', array_slice(array_column($v['errors'], 'message'), 0, 3));

// The same game, byte for byte: only the game's name differs between the two runs — also inside the compressed
// history snapshots ("~" + base64 DEFLATE, Core/SimHistory.php), which are opened up to compare — and the wall-clock
// time each game-log entry is stamped with.
$norm = function (string $gs, string $name) use (&$norm): string {
    $gs = preg_replace_callback('/~([A-Za-z0-9+\/]+={0,2})/', function ($m) use ($name, &$norm) {
        $raw = base64_decode($m[1], true); $payload = $raw === false ? false : @gzinflate($raw);
        return $payload === false ? $m[0] : '{' . $norm($payload, $name) . '}';
    }, $gs);
    $gs = preg_replace_callback('/MR1:([A-Za-z0-9+\/]+={0,2})/', function ($m) use ($name, &$norm) {   // the match replay (gzip: an mtime header)
        $raw = base64_decode($m[1], true); $payload = $raw === false ? false : @gzdecode($raw);
        return $payload === false ? $m[0] : 'MR1{' . $norm($payload, $name) . '}';
    }, $gs);
    // The clocks first, then the name — as a whole token only: a game name here is a short number ("47"), which a
    // plain replace also finds inside a timestamp or a card id (JTL_131).
    $gs = preg_replace('/\|@\d+(\.\d+)?\|/', '|@<time>|', $gs);   // game-log entries' wall clock
    $gs = preg_replace('/\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d(\.\d+)?(Z|[+-]\d\d:\d\d)?/', '<iso-time>', $gs);    // and the replay's
    return preg_replace('/(?<![A-Za-z0-9_#.])' . preg_quote($name, '/') . '(?![A-Za-z0-9_])/', '<game>', $gs);
};
$a = $norm($gs0, $n0); $b = $norm($gs1, $n1);
$firstDiff = null;
if ($a !== $b) { $la = explode("\n", $a); $lb = explode("\n", $b); foreach ($la as $i => $line) if (($lb[$i] ?? null) !== $line) { $o = strspn($line ^ ($lb[$i] ?? ''), "\0"); $firstDiff = "line $i @$o: " . substr($line, max(0, $o - 60), 140) . ' ≠ ' . substr($lb[$i] ?? '(none)', max(0, $o - 60), 140); break; } }
SwuPgnTestCheck($a === $b, 'recording does not change the game (final gamestates identical)', $firstDiff);
SwuPgnTestEq($r1, $r0, 'the same result line (winner, steps, actions)');

foreach ([$n0, $n1] as $n) if ($n !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $n)) exec('rm -rf ' . escapeshellarg("SWUSim/Games/$n"));
SwuPgnTestFinish();
