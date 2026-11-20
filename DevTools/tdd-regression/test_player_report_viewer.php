<?php
// PlayerReportViewerLib.php — the moderator page's rendering (pure: synthetic data in, HTML out), plus the gate:
// zzPlayerReportViewer.php refuses a non-moderator.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off -d apc.enable_cli=1 DevTools/tdd-regression/test_player_report_viewer.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir(__DIR__ . '/../..');
require_once './PlayerReportViewerLib.php';

$FAILS = 0; $CHECKS = 0;
function check($cond, $msg, $extra = null) {
    global $FAILS, $CHECKS;
    $CHECKS++;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . (($cond || $extra === null) ? '' : '  ' . json_encode($extra)) . "\n";
    if (!$cond) $FAILS++;
}

echo "── the list ──\n";
$rows = [
    ['id' => 'R-2', 'number' => 2, 'status' => 'closed', 'createdAt' => 1761500100, 'reporterName' => 'carol', 'reportedName' => 'dave', 'gameName' => '7010', 'note' => 'old one', 'followups' => 0],
    ['id' => 'R-3', 'number' => 3, 'status' => 'open', 'createdAt' => 1761500000, 'reporterName' => 'alice', 'reportedName' => '<b>bob</b>', 'gameName' => '7001', 'note' => "first line <script>x()</script>\nsecond line", 'followups' => 2],
];
$list = PlayerReportViewerListHtml($rows);
check(strpos($list, 'R-3') !== false && strpos($list, 'R-3') < strpos($list, 'R-2'), 'open reports are listed before closed ones', null);
check(strpos($list, '<script>x()') === false && strpos($list, '&lt;script&gt;') !== false, 'a note is escaped');
check(strpos($list, '<b>bob</b>') === false && strpos($list, '&lt;b&gt;bob') !== false, 'a name is escaped');
check(strpos($list, 'second line') === false, 'only the first line of the note is shown');
check(strpos($list, 'href="?report=R-3"') !== false, 'each row links to its detail');

echo "── the detail ──\n";
$report = ['report_number' => 'R-3', 'number' => 3, 'created_at' => '2026-10-27T01:00:00+00:00', 'game_name' => '7001',
    'match_id' => 'M9', 'lobby_id' => 'room1', 'format' => 'premier', 'reporter_id' => 9001, 'reporter_name' => 'alice',
    'reporter_seat' => 1, 'reported_id' => 9002, 'reported_name' => 'bob', 'reported_seat' => 2, 'note' => 'abusive <i>x</i>',
    'status' => 'open', 'resolution_note' => '', 'followups' => [['at' => '2026-10-27T01:05:00+00:00', 'note' => 'again', 'game_name' => '7002']]];
$frozen = ['l:room1' => [
    ['id' => 0, 'playerID' => 0, 'playerLabel' => 'Sideboard', 'text' => 'an earlier match sideboard line', 'time' => 1761499000, 'ts' => 1761499000.1, 'src' => 'sideboard:M8'],
    ['id' => 1, 'playerID' => 1, 'playerLabel' => 'alice', 'text' => 'before the match', 'time' => 1761500000, 'ts' => 1761500000.1, 'src' => 'room:room1'],
    ['id' => 2, 'playerID' => 2, 'playerLabel' => 'bob', 'text' => 'in game <img src=x>', 'time' => 1761500100, 'ts' => 1761500100.1, 'src' => 'game:7001'],
    ['id' => 3, 'playerID' => 2, 'playerLabel' => 'bob', 'text' => 'secret threat', 'time' => 1761500200, 'ts' => 1761500200.1, 'src' => 'game:7001', 'to' => [1]],
    ['id' => 4, 'playerID' => 0, 'playerLabel' => 'Sideboard', 'text' => '30 seconds left', 'time' => 1761500300, 'ts' => 1761500300.1, 'src' => 'sideboard:M9'],
]];
$after = ['l:room1' => [['id' => 5, 'playerID' => 2, 'playerLabel' => 'bob', 'text' => 'said later', 'time' => 1761500400, 'ts' => 1761500400.1, 'src' => 'game:7002']]];
$loaded = ['report' => $report, 'chat' => $frozen, 'followupChats' => [1 => $frozen]];
$d = PlayerReportViewerDetailHtml($loaded, $after, ['7001', '7002']);
check(strpos($d, 'alice') !== false && strpos($d, 'bob') !== false && strpos($d, 'premier') !== false && strpos($d, 'M9') !== false, 'the detail names both players, the format and the match');
check(strpos($d, 'abusive &lt;i&gt;x&lt;/i&gt;') !== false && strpos($d, 'in game &lt;img') !== false && strpos($d, '<img src=x>') === false, 'note and chat text are escaped');
check(strpos($d, 'again') !== false, 'follow-up notes are shown');
check(preg_match('/whisper → P1/u', $d) === 1 && strpos($d, 'secret threat') !== false, 'a whisper is shown in full, labelled with its recipients');
$det = strpos($d, '<details'); $endDet = strpos($d, '</details>');
check($det !== false && strpos($d, 'before the match') > $det && strpos($d, 'before the match') < $endDet, 'room chat from before the match is inside a collapsed <details>');
check(strpos($d, 'an earlier match sideboard line') > $det && strpos($d, 'an earlier match sideboard line') < $endDet, "another match's sideboard line is earlier room chat too, not the reported match");
check(strpos($d, 'in game') > $endDet, 'the match itself is not collapsed');
// Game 1 is gone from the match record: the reported match then starts at ITS sideboard line (sideboard:M9).
$d2 = PlayerReportViewerDetailHtml($loaded, [], ['7002']);
$det2 = strpos($d2, '<details'); $end2 = strpos($d2, '</details>');
check($det2 !== false && strpos($d2, '30 seconds left') > $end2 && strpos($d2, 'in game') < $end2, "the reported match's own sideboard line (sideboard:<its match>) starts the match section");
$h = strpos($d, 'After the report');
check($h !== false && strpos($d, 'said later') > $h, 'what was said after the report sits under its own heading');
check(strpos($d, 'game:7001') !== false && strpos($d, 'sideboard:M9') !== false, 'each line shows where it was sent from');
check(strpos($d, 'Close') !== false && strpos($d, 'Delete') !== false, 'an open report offers Close and Delete');

echo "── the gate ──\n";
$ch = curl_init('http://localhost/TCGEngine/zzPlayerReportViewer.php');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => ['Host: petranaki.invalid']]);
$page = (string)curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
check($code > 0 && strpos($page, 'Player Report') === false && strpos($page, '<table') === false, 'a visitor who is not a moderator gets no reports', substr($page, 0, 120));

echo $FAILS === 0 ? "PASS: test_player_report_viewer — all $CHECKS checks passed\n" : "FAIL: test_player_report_viewer — $FAILS failed\n";
exit($FAILS === 0 ? 0 : 1);
