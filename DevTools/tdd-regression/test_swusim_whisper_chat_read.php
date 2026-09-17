<?php
// RUN VIA CLI (it calls Apache inside the container; never curl this file):
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swusim_whisper_chat_read.php
//
// Whisper chat — READING. Each viewer's poll payload: sender + recipients get the text; every other
// seat and spectators get the row with text "" + redacted:true. Also pins GetChat.php (unauthenticated,
// no viewer) and the full-board poll branch. Needs SWUSim/GetNextTurn.php regenerated.
header('Content-Type: text/plain');
require_once __DIR__ . '/fixtures/swusim_chat_http_helpers.php';

$checks = [];
$SECRET = 'whisper-secret-' . bin2hex(random_bytes(4));
$PUBLIC = 'public-hello-' . bin2hex(random_bytes(4));

// ── Twin Suns: seat 1 whispers seat 3 ──
$gn = swuchat_make_game(SWUCHAT_SCHEMA_TWINSUNS);
$checks['fixture created'] = $gn !== '';
$checks['public send OK']  = swuchat_send($gn, '2', $PUBLIC) === 'OK';
$checks['whisper send OK'] = swuchat_send($gn, '1', $SECRET, '3') === 'OK';

foreach (['1' => true, '3' => true, '2' => false, '4' => false, 'S' => false] as $pid => $isParty) {
    $p = swuchat_poll($gn, $pid);
    $msgs = $p['messages'] ?? [];
    $raw = json_encode($p);
    $checks["seat $pid: CHATONLY payload parsed"] = isset($p['messages']);
    $w = null; $pub = null;
    foreach ($msgs as $m) {
        if (isset($m['to']) && strval($m['playerID']) === '1') $w = $m;
        if (($m['text'] ?? '') === $PUBLIC) $pub = $m;
    }
    $checks["seat $pid: sees public text"]        = $pub !== null && !array_key_exists('to', $pub);
    $checks["seat $pid: receives the whisper row"] = $w !== null && ($w['to'] ?? null) === [3];
    if ($isParty) {
        $checks["seat $pid: reads whisper text"] = $w !== null && ($w['text'] ?? '') === $SECRET && empty($w['redacted']);
    } else {
        $checks["seat $pid: text blanked + redacted"] = $w !== null && ($w['text'] ?? null) === '' && ($w['redacted'] ?? null) === true;
        $checks["seat $pid: secret absent from payload"] = strpos($raw, $SECRET) === false;
    }
}

// Full-board branch (a different echo site in the generated file).
$checks['full poll seat 2: secret absent'] = strpos(swuchat_poll_full_raw($gn, '2'), $SECRET) === false;
$checks['full poll seat S: secret absent'] = strpos(swuchat_poll_full_raw($gn, 'S'), $SECRET) === false;
$checks['full poll seat 3: secret present'] = strpos(swuchat_poll_full_raw($gn, '3'), $SECRET) !== false;

// GetChat.php — no viewer at all.
$checks['GetChat.php: secret absent'] = strpos(json_encode(swuchat_getchat($gn)), $SECRET) === false;

// ── Team Suns: seat 2 whispers teammate 4 ──
$TEAM = 'team-secret-' . bin2hex(random_bytes(4));
$tm = swuchat_make_game(SWUCHAT_SCHEMA_TEAMSUNS);
$checks['team fixture created'] = $tm !== '';
$checks['team whisper OK']      = swuchat_send($tm, '2', $TEAM, '4') === 'OK';
foreach (['2' => true, '4' => true, '1' => false, '3' => false, 'S' => false] as $pid => $isParty) {
    $raw = json_encode(swuchat_poll($tm, $pid));
    $checks["team seat $pid: " . ($isParty ? 'reads' : 'cannot read')] = $isParty
        ? strpos($raw, $TEAM) !== false
        : (strpos($raw, $TEAM) === false && strpos($raw, '"redacted":true') !== false);
}

$fail = 0;
foreach ($checks as $name => $ok) { echo ($ok ? 'PASS' : 'FAIL') . "  $name\n"; if (!$ok) $fail++; }
echo "games: twinsuns=$gn teamsuns=$tm\n";
echo $fail === 0 ? "\nPASS (" . count($checks) . ")\n" : "\nFAIL ($fail of " . count($checks) . ")\n";
exit($fail === 0 ? 0 : 1);
