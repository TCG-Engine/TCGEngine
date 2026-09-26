<?php
// RUN VIA CLI (it calls Apache inside the container; never curl this file):
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swusim_whisper_chat_send.php
//
// Whisper chat — SENDING. SubmitChat.php accepts/rejects `whisperTo` through SWUSim/Custom/ChatWhisperPolicy.php
// and stores the row with `to`. Read side (who sees the text) is test_swusim_whisper_chat_read.php.
header('Content-Type: text/plain');
require_once __DIR__ . '/fixtures/swusim_chat_http_helpers.php';

$checks = [];
$ts = swuchat_make_game(SWUCHAT_SCHEMA_TWINSUNS);
$tm = swuchat_make_game(SWUCHAT_SCHEMA_TEAMSUNS);
$pm = swuchat_make_game(SWUCHAT_SCHEMA_PREMIER);
$checks['fixtures created'] = $ts !== '' && $tm !== '' && $pm !== '';

// Public chat is unaffected.
$checks['public message still OK']                 = swuchat_send($ts, '1', 'hello table') === 'OK';

// Twin Suns: any other seat(s).
$checks['TS whisper to one seat OK']               = swuchat_send($ts, '1', 'ts-one', '3') === 'OK';
$checks['TS whisper to two seats OK']              = swuchat_send($ts, '2', 'ts-two', '4,1') === 'OK';
$checks['TS self target rejected']                 = swuchat_send($ts, '1', 'x', '1') === 'Invalid whisper.';
$checks['TS out-of-range rejected']                = swuchat_send($ts, '1', 'x', '5') === 'Invalid whisper.';
$checks['TS malformed rejected']                   = swuchat_send($ts, '1', 'x', '2,a') === 'Invalid whisper.';
// ⚠ THE GENERAL RULE ANSWERS FIRST, and that is deliberate. Since 2026-09-26 a SWUSim spectator may
// not chat AT ALL (SWUSim/Custom/ChatPolicy.php), and ChatSendRefusal runs before SubmitChat's
// whisper-specific spectator check — so the refusal a spectator gets is the broad one. The narrower
// "Spectators cannot whisper." line in SubmitChat.php is NOT dead: it still answers for any sim that
// has no spectator chat policy of its own.
$checks['TS spectator cannot whisper']             = swuchat_send($ts, 'S', 'x', '1') === 'Spectators cannot chat.';

// Team Suns: exactly the teammate (seat parity — 1&3 red, 2&4 blue).
$checks['Team whisper to teammate OK']             = swuchat_send($tm, '1', 'team-red', '3') === 'OK';
$checks['Team seat 4 to teammate 2 OK']            = swuchat_send($tm, '4', 'team-blue', '2') === 'OK';
$checks['Team whisper to opponent refused']        = swuchat_send($tm, '1', 'x', '2') === 'Whisper not allowed.';
$checks['Team teammate + opponent refused']        = swuchat_send($tm, '1', 'x', '2,3') === 'Whisper not allowed.';

// Premier: never.
$checks['Premier whisper refused']                 = swuchat_send($pm, '1', 'x', '2') === 'Whisper not allowed.';

// A sim with no policy file cannot whisper (folderPath GrandArchiveSim has none; auth is skipped in DEVENV).
$gaResp = trim(swuchat_http('SubmitChat.php?' . http_build_query([
    'gameName' => $ts, 'playerID' => '1', 'authKey' => 'testschema', 'folderPath' => 'GrandArchiveSim',
    'chatText' => 'x', 'whisperTo' => '2',
])));
$checks['sim without policy refused']              = $gaResp === 'Whispers are not available.' || $gaResp === 'Invalid whisper.';
// An empty folderPath skips auth in SubmitChat, so whispers must need one.
$noFolder = trim(swuchat_http('SubmitChat.php?' . http_build_query([
    'gameName' => $ts, 'playerID' => '1', 'chatText' => 'x', 'whisperTo' => '2',
])));
$checks['no folderPath refused']                   = $noFolder === 'Whispers are not available.';

// Stored shape, observed through GetChat.php (no viewer => redacted, but `to` and sender survive).
$rows = swuchat_getchat($ts);
$one = null; $two = null; $pub = null;
foreach ($rows as $r) {
    if (($r['text'] ?? '') === 'hello table') $pub = $r;
    if (isset($r['to']) && $r['to'] === [3] && strval($r['playerID']) === '1') $one = $r;
    if (isset($r['to']) && $r['to'] === [1, 4] && strval($r['playerID']) === '2') $two = $r;
}
$checks['public row stored without to']            = $pub !== null && !array_key_exists('to', $pub);
$checks['one-seat whisper stored with to=[3]']     = $one !== null;
$checks['two-seat whisper stored sorted to=[1,4]'] = $two !== null;
$checks['rejected whispers were not stored']       = count(array_filter($rows, function ($r) { return isset($r['to']); })) === 2;
$checks['GetChat never leaks whisper text']        = strpos(json_encode($rows), 'ts-one') === false && strpos(json_encode($rows), 'ts-two') === false;

$fail = 0;
foreach ($checks as $name => $ok) { echo ($ok ? 'PASS' : 'FAIL') . "  $name\n"; if (!$ok) $fail++; }
echo "games: twinsuns=$ts teamsuns=$tm premier=$pm\n";
echo $fail === 0 ? "\nPASS (" . count($checks) . ")\n" : "\nFAIL ($fail of " . count($checks) . ")\n";
exit($fail === 0 ? 0 : 1);
