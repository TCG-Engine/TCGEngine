<?php
// http://localhost:3100/TCGEngine/DevTools/tdd-regression/test_melee_fetch_gateway.php
//
// Every melee.gg request the tournament importer makes goes through SWUMeleeFetch() (2026-10-22): melee's
// load balancer 403s the DigitalOcean droplets, so a tournament is RECORDED on a machine melee answers and
// REPLAYED on swustats.net from a package. Replay must never touch the network, and an unrecorded request
// must fail loudly instead of quietly importing a partial tournament.
// Pure unit test: the $live closures are fakes, nothing is fetched or written.
header('Content-Type: text/plain');
require_once __DIR__ . '/../../Stats/MeleeFetch.php';

$checks = [];
$calls = 0;
$fake = function ($status, $body) use (&$calls) {
    return function () use (&$calls, $status, $body) { $calls++; return ['status' => $status, 'body' => $body, 'error' => null]; };
};

// ── live (default): the closure runs, nothing is kept ────────────────────────────────────────────
SWUMeleeFetchLive();
$r = SWUMeleeFetch('GET', 'https://melee.gg/Tournament/View/1', null, $fake(200, '<html>one</html>'));
$checks['live: answers with the closure result'] = $r['status'] === 200 && $r['body'] === '<html>one</html>';
$checks['live: the closure ran once'] = $calls === 1;
$checks['live: nothing is recorded'] = SWUMeleeFetchRecorded() === [];

// ── record: the closure runs and every answer is kept, keyed by method + url + body ──────────────
SWUMeleeFetchRecord();
$calls = 0;
SWUMeleeFetch('GET', 'https://melee.gg/Tournament/View/1', null, $fake(200, '<html>page</html>'));
SWUMeleeFetch('POST', 'https://melee.gg/Standing/GetRoundStandings', 'roundId=5&start=0', $fake(200, '{"data":[1]}'));
SWUMeleeFetch('POST', 'https://melee.gg/Standing/GetRoundStandings', 'roundId=5&start=500', $fake(200, '{"data":[]}'));
// The decklist fetch retries once: a failed first try then a success must replay as the success.
SWUMeleeFetch('GET', 'https://melee.gg/Decklist/GetTournamentViewData/9', null, $fake(0, false));
SWUMeleeFetch('GET', 'https://melee.gg/Decklist/GetTournamentViewData/9', null, $fake(200, '{"Json":"{}"}'));
$recorded = SWUMeleeFetchRecorded();
$checks['record: every closure ran'] = $calls === 5;
$checks['record: one entry per distinct request (retry overwrites)'] = count($recorded) === 4;
$checks['record: entries carry method/url/body/status/payload'] = isset($recorded[0]['method'], $recorded[0]['url'], $recorded[0]['status'])
    && array_key_exists('body', $recorded[0]) && array_key_exists('payload', $recorded[0]);

// ── replay: answers come from the package, the network is never used ─────────────────────────────
SWUMeleeFetchReplay($recorded);
$calls = 0;
$page = SWUMeleeFetch('GET', 'https://melee.gg/Tournament/View/1', null, $fake(500, 'LIVE'));
$b0 = SWUMeleeFetch('POST', 'https://melee.gg/Standing/GetRoundStandings', 'roundId=5&start=0', $fake(500, 'LIVE'));
$b1 = SWUMeleeFetch('POST', 'https://melee.gg/Standing/GetRoundStandings', 'roundId=5&start=500', $fake(500, 'LIVE'));
$dl = SWUMeleeFetch('GET', 'https://melee.gg/Decklist/GetTournamentViewData/9', null, $fake(500, 'LIVE'));
$checks['replay: the closure never runs'] = $calls === 0;
$checks['replay: page answers from the package'] = $page['status'] === 200 && $page['body'] === '<html>page</html>';
$checks['replay: POST bodies are told apart'] = $b0['body'] === '{"data":[1]}' && $b1['body'] === '{"data":[]}';
$checks['replay: a retried request replays its LAST answer'] = $dl['status'] === 200 && $dl['body'] === '{"Json":"{}"}';

// A request the package never recorded is an error, never a silent live fetch or an empty answer.
$threw = false;
try { SWUMeleeFetch('GET', 'https://melee.gg/Decklist/GetTournamentViewData/10', null, $fake(200, 'LIVE')); }
catch (RuntimeException $e) { $threw = true; }
$checks['replay: an unrecorded request throws'] = $threw;
$checks['replay: ...and still never runs the closure'] = $calls === 0;

// A recorded failure (body FALSE) replays as a failure, so the importer takes the same path it took live.
SWUMeleeFetchRecord();
SWUMeleeFetch('GET', 'https://melee.gg/Decklist/GetTournamentViewData/11', null, $fake(0, false));
SWUMeleeFetchReplay(SWUMeleeFetchRecorded());
$f = SWUMeleeFetch('GET', 'https://melee.gg/Decklist/GetTournamentViewData/11', null, $fake(200, 'LIVE'));
$checks['replay: a recorded failure replays as body FALSE'] = $f['body'] === false;

// Package entries survive a JSON round trip (that is how they travel).
SWUMeleeFetchReplay(json_decode(json_encode($recorded), true));
$checks['replay: works from JSON-decoded entries'] = SWUMeleeFetch('GET', 'https://melee.gg/Tournament/View/1', null, $fake(500, 'LIVE'))['body'] === '<html>page</html>';

SWUMeleeFetchLive();
$checks['live again: replay is switched off'] = SWUMeleeFetch('GET', 'https://melee.gg/x', null, $fake(200, 'L'))['body'] === 'L';

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
foreach ($checks as $name => $ok) echo ($ok ? '  ok  ' : '  FAIL ') . $name . "\n";
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL (" . count($fails) . " of " . count($checks) . ")\n";
