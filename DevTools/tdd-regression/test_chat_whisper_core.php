<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_chat_whisper_core.php
//
// Whisper chat — the pure Core pieces (spec docs/superpowers/specs/2026-09-17-swusim-twinsuns-whisper-chat-design.md).
// READ-ONLY, no APCu (the CLI SAPI has none). The APCu store is exercised by test_swusim_whisper_chat_read.php.
header('Content-Type: text/plain');
require_once __DIR__ . '/../../Core/ViewerIdentity.php';
require_once __DIR__ . '/../../Core/ChatWhisper.php';

$checks = [];
$seat = function ($n) { return NormalizeViewerIdentity(strval($n), 4); };
$spec = NormalizeViewerIdentity('S', 4);

// ── ChatParseWhisperTargets ──
$checks['empty raw = public (empty array)']        = ChatParseWhisperTargets('', 1, 4) === [];
$checks['whitespace raw = public']                  = ChatParseWhisperTargets('  ', 1, 4) === [];
$checks['single target']                            = ChatParseWhisperTargets('3', 1, 4) === [3];
$checks['sorted + deduped']                         = ChatParseWhisperTargets('4,2,4', 1, 4) === [2, 4];
$checks['spaces tolerated']                         = ChatParseWhisperTargets(' 2 , 3 ', 1, 4) === [2, 3];
$checks['self target rejected']                     = ChatParseWhisperTargets('1,3', 1, 4) === null;
$checks['seat 0 rejected']                          = ChatParseWhisperTargets('0', 1, 4) === null;
$checks['seat above max rejected']                  = ChatParseWhisperTargets('5', 1, 4) === null;
$checks['non-numeric rejected']                     = ChatParseWhisperTargets('2,x', 1, 4) === null;
$checks['empty part rejected']                      = ChatParseWhisperTargets('2,,3', 1, 4) === null;
$checks['negative rejected']                        = ChatParseWhisperTargets('-2', 1, 4) === null;
$checks['2-seat sim: seat 3 out of range']          = ChatParseWhisperTargets('3', 1, 2) === null;

// ── ChatRowForViewer ──
$public  = ['id' => 7, 'playerID' => '1', 'playerLabel' => 'P1', 'text' => 'hello all', 'time' => 1];
$whisper = ['id' => 8, 'playerID' => '1', 'playerLabel' => 'P1', 'text' => 'secret plan', 'time' => 2, 'to' => [3]];

$checks['public row unchanged for a seat']          = ChatRowForViewer($public, $seat(2)) === $public;
$checks['public row unchanged for spectator']       = ChatRowForViewer($public, $spec) === $public;
$checks['public row unchanged for null viewer']     = ChatRowForViewer($public, null) === $public;
$checks['sender sees full whisper']                 = ChatRowForViewer($whisper, $seat(1)) === $whisper;
$checks['recipient sees full whisper']              = ChatRowForViewer($whisper, $seat(3)) === $whisper;

foreach (['seat 2' => $seat(2), 'seat 4' => $seat(4), 'spectator' => $spec, 'null viewer' => null] as $who => $viewer) {
    $r = ChatRowForViewer($whisper, $viewer);
    $checks["$who: text blanked"]      = ($r['text'] ?? null) === '';
    $checks["$who: redacted flag"]     = ($r['redacted'] ?? null) === true;
    $checks["$who: keeps to"]          = ($r['to'] ?? null) === [3];
    $checks["$who: keeps sender"]      = ($r['playerID'] ?? null) === '1';
    $checks["$who: keeps id"]          = ($r['id'] ?? null) === 8;
    $checks["$who: text not in JSON"]  = strpos(json_encode($r), 'secret plan') === false;
}
$checks['string seats in to still match'] = ChatRowForViewer(['playerID' => '1', 'text' => 't', 'to' => ['3']], $seat(3))['text'] === 't';

$fail = 0;
foreach ($checks as $name => $ok) { echo ($ok ? 'PASS' : 'FAIL') . "  $name\n"; if (!$ok) $fail++; }
echo $fail === 0 ? "\nPASS (" . count($checks) . ")\n" : "\nFAIL ($fail of " . count($checks) . ")\n";
exit($fail === 0 ? 0 : 1);
