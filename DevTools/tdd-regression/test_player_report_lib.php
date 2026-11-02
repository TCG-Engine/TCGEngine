<?php
// PlayerReportLib.php — Petranaki player reports (docs/superpowers/specs/2026-10-27-saved-chat-and-player-reports-design.md).
// Disk first, then the table; a follow-up per reporter per match; a frozen copy of the saved chat. Works on temp dirs
// and a scratch table (swusim_player_reports_test, created from migration 21 and dropped at the end).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off -d apc.enable_cli=1 DevTools/tdd-regression/test_player_report_lib.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir(__DIR__ . '/../..');
define('PLAYER_REPORT_TABLE', 'swusim_player_reports_test');
require_once './Core/NetworkingLibraries.php';
require_once './Database/ConnectionManager.php';
require_once './PlayerReportLib.php';

$FAILS = 0; $CHECKS = 0;
function check($cond, $msg, $extra = null) {
    global $FAILS, $CHECKS;
    $CHECKS++;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . (($cond || $extra === null) ? '' : '  ' . json_encode($extra)) . "\n";
    if (!$cond) $FAILS++;
}
function rrm($d) {
    if (!file_exists($d)) return;
    if (is_file($d)) { @unlink($d); return; }
    foreach (scandir($d) as $f) if ($f !== '.' && $f !== '..') rrm("$d/$f");
    @rmdir($d);
}
function dbRow($conn, $n) {
    $r = $conn->query('SELECT * FROM swusim_player_reports_test WHERE reportNumber = ' . intval($n));
    return $r ? $r->fetch_assoc() : null;
}

$tmp = sys_get_temp_dir() . '/preport-' . bin2hex(random_bytes(4));
mkdir("$tmp/chats", 0777, true); mkdir("$tmp/games/7001", 0777, true); mkdir("$tmp/reports", 0777, true);
putenv("SWUSIM_CHAT_DIR=$tmp/chats");
putenv("SWUSIM_CHAT_GAMES_DIR=$tmp/games");
putenv("SWUSIM_PLAYERREPORT_DIR=$tmp/reports");

$conn = null;
try { $conn = GetLocalMySQLConnection() ?: null; } catch (Throwable $e) { $conn = null; }
check($conn !== null, 'a database connection for the table checks');
if ($conn) {
    $conn->query('DROP TABLE IF EXISTS swusim_player_reports_test');
    $sql = str_replace('`swusim_player_reports`', '`swusim_player_reports_test`', file_get_contents('./Database/migrations/21_swusim_player_reports.sql'));
    $sql = preg_replace('/^--.*$/m', '', $sql);
    check($conn->query($sql) === true, 'migration 21 creates the table', $conn->error);
}

// A room conversation the game belongs to, with a whisper in the game itself.
ChatPersistUseRoot('SWUSim');
foreach (['l:prroom', 'm:PRM1', '7001'] as $k) { apcu_delete('chat_' . $k); apcu_delete('chat_version_' . $k); apcu_delete(ChatSidecarKey($k)); }
apcu_delete(ChatSidecarKey('PRM1'));
ChatAppendMessage('l:prroom', 1, 'alice', 'hi in the room');
ChatPublishConversationForGame('7001', 'l:prroom');
ChatPublishConversationForGame('PRM1', 'l:prroom');
ChatAppendMessage('7001', 2, 'bob', 'you are terrible');
ChatAppendMessage('7001', 2, 'bob', 'quiet threat', [1]);

echo "── chat keys ──\n";
check(PlayerReportChatKeys('7001', 'PRM1') === ['l:prroom'], 'a game and its match that share the room resolve to one key', PlayerReportChatKeys('7001', 'PRM1'));
check(PlayerReportChatKeys('7002', '') === ['7002'], 'a matchless game keeps its own key', PlayerReportChatKeys('7002', ''));

$base = ['gameName' => '7001', 'matchId' => 'PRM1', 'lobbyId' => 'prroom', 'format' => 'premier',
         'reporterId' => 9001, 'reporterName' => 'alice', 'reporterSeat' => 1,
         'reportedId' => 9002, 'reportedName' => 'bob', 'reportedSeat' => 2,
         'note' => 'abusive in chat', 'clientIp' => '10.0.0.1', 'createdAt' => 1761500000];

echo "── a new report ──\n";
$r1 = PlayerReportFile($base, 'tok-aaaaaaaa', $conn);
check($r1['ok'] && $r1['kind'] === 'new' && $r1['reportNumber'] === 1, 'files R-1', $r1);
check(is_file("$tmp/reports/R-1/report.json") && is_file("$tmp/reports/R-1/chat.json"), 'report.json and chat.json are on disk');
check($conn && $r1['dbSaved'] && (dbRow($conn, 1)['reportedName'] ?? '') === 'bob' && (dbRow($conn, 1)['status'] ?? '') === 'open', 'and in the table, open', $conn ? dbRow($conn, 1) : null);
$frozen = json_decode(file_get_contents("$tmp/reports/R-1/chat.json"), true);
$rows = $frozen['l:prroom'] ?? [];
$texts = array_map(fn($r) => $r['text'], $rows);
check($texts === ['hi in the room', 'you are terrible', 'quiet threat'], 'the frozen chat holds the whole conversation, whisper text included', $texts);
check(($rows[2]['to'] ?? null) === [1] && ($rows[1]['src'] ?? '') === 'game:7001' && ($rows[0]['src'] ?? '') === 'room:prroom', 'with recipients and src tags', $rows);

echo "── retries, follow-ups and the limit ──\n";
$dup = PlayerReportFile($base, 'tok-aaaaaaaa', $conn);
check($dup['kind'] === 'duplicate' && $dup['reportNumber'] === 1, 'the same token is a duplicate', $dup);
ChatAppendMessage('7001', 2, 'bob', 'still going');
if ($conn) $conn->query("UPDATE swusim_player_reports_test SET status = 'closed' WHERE reportNumber = 1");
$f1 = PlayerReportFile(['note' => 'it continued'] + $base, 'tok-bbbbbbbb', $conn);
check($f1['kind'] === 'followup' && $f1['reportNumber'] === 1, 'the same reporter in the same match adds a follow-up', $f1);
$j = json_decode(file_get_contents("$tmp/reports/R-1/report.json"), true);
check(count($j['followups'] ?? []) === 1 && ($j['followups'][0]['note'] ?? '') === 'it continued', 'the follow-up note is kept', $j['followups'] ?? null);
check(is_file("$tmp/reports/R-1/chat-f1.json") && in_array('still going', array_map(fn($r) => $r['text'], json_decode(file_get_contents("$tmp/reports/R-1/chat-f1.json"), true)['l:prroom'] ?? []), true), 'with a fresh chat copy');
check($conn && (dbRow($conn, 1)['status'] ?? '') === 'open' && intval(dbRow($conn, 1)['followups'] ?? 0) === 1, 'and the closed report is reopened', $conn ? dbRow($conn, 1) : null);
PlayerReportFile($base, 'tok-cccccccc', $conn);
PlayerReportFile($base, 'tok-dddddddd', $conn);
$lim = PlayerReportFile($base, 'tok-eeeeeeee', $conn);
check($lim['kind'] === 'limit' && !is_file("$tmp/reports/R-1/chat-f4.json"), 'a 4th follow-up is refused and changes nothing', $lim);
$other = PlayerReportFile(['reporterId' => 9002, 'reporterName' => 'bob', 'reporterSeat' => 2, 'reportedId' => 9001, 'reportedName' => 'alice', 'reportedSeat' => 1] + $base, 'tok-ffffffff', $conn);
check($other['kind'] === 'new' && $other['reportNumber'] === 2, 'a different reporter in the same match is a new report', $other);

echo "── the database being down ──\n";
$down = PlayerReportFile(['matchId' => 'PRM2', 'gameName' => '7003'] + $base, 'tok-gggggggg', null);
check($down['ok'] && $down['kind'] === 'new' && !$down['dbSaved'] && is_file("$tmp/reports/R-3/report.json"), 'a report with no database is still saved to disk', $down);
check($conn && PlayerReportReconcile($conn) === 1 && dbRow($conn, 3) !== null, 'and reconcile puts it in the table later');

echo "── a follow-up filed while the database is down ──\n";
if ($conn) $conn->query("UPDATE swusim_player_reports_test SET status = 'closed', updatedAt = 1761500050 WHERE reportNumber = 3");
$fd = PlayerReportFile(['matchId' => 'PRM2', 'gameName' => '7003', 'note' => 'more, db down', 'createdAt' => 1761500200] + $base, 'tok-jjjjjjjj', null);
check($fd['kind'] === 'followup' && !$fd['dbSaved'], 'the follow-up is saved to disk only', $fd);
check($conn && PlayerReportReconcile($conn) >= 0 && (dbRow($conn, 3)['status'] ?? '') === 'open' && intval(dbRow($conn, 3)['followups'] ?? 0) === 1, 'reconcile brings it into the list: reopened, with the follow-up counted', $conn ? dbRow($conn, 3) : null);
if ($conn) $conn->query("UPDATE swusim_player_reports_test SET status = 'closed', updatedAt = 1761500300 WHERE reportNumber = 3");
check($conn && PlayerReportReconcile($conn) >= 0 && (dbRow($conn, 3)['status'] ?? '') === 'closed', "and a moderator's later close is not undone by the next reconcile", $conn ? dbRow($conn, 3) : null);

echo "── rate limit ──\n";
if ($conn) {
    for ($i = 0; $i < 10; $i++) $conn->query("INSERT INTO swusim_player_reports_test (reportNumber, createdAt, updatedAt, reporterId, reportedId, note, clientToken, clientIp) VALUES (" . (100 + $i) . ", 1761500000, 1761500000, 9100, 9002, '', '" . sha1("rl$i") . "', '10.0.0.9')");
}
check(PlayerReportRateLimit($conn, 9100, '10.9.9.9', 1761500100) !== null, 'an 11th report by one account in an hour waits');
check(PlayerReportRateLimit($conn, 9101, '10.9.9.9', 1761500100) === null, 'another account is not limited');
check(PlayerReportRateLimit(null, 9100, '10.9.9.9', 1761500100) === null, 'no database never limits');

echo "── what was said after the report ──\n";
ChatAppendMessage('7001', 2, 'bob', 'after R-2 was filed');
$afterRows = PlayerReportChatAfter(PlayerReportLoad(2));
check(array_map(fn($r) => $r['text'], $afterRows['l:prroom'] ?? []) === ['after R-2 was filed'], 'only lines newer than the frozen copy are "after the report"', $afterRows);

echo "── status, delete, parse ──\n";
check(($conn ? PlayerReportSetStatus($conn, 2, 'closed', 'warned bob') : [])['success'] ?? false, 'close with a note');
check($conn && (dbRow($conn, 2)['resolutionNote'] ?? '') === 'warned bob' && dbRow($conn, 2)['status'] === 'closed', 'is stored');
$list = $conn ? PlayerReportList($conn) : [];
check(($list[0]['status'] ?? '') === 'open' && end($list)['status'] === 'closed', 'the list puts open reports first', array_map(fn($r) => [$r['number'], $r['status']], $list));
$del = $conn ? PlayerReportDelete($conn, 1) : [];
check(!empty($del['success']) && !is_dir("$tmp/reports/R-1") && dbRow($conn, 1) === null, 'delete removes the folder and the row', $del);
check(PlayerReportFile($base, 'tok-hhhhhhhh', $conn)['kind'] === 'new', 'and the match is free for a new report after delete');
check(PlayerReportParseNumber('R-7') === 7 && PlayerReportParseNumber('P-7') === null && PlayerReportParseNumber('../R-7') === null, 'R-<n> parses; anything else does not');

echo "── a mangled note is scrubbed ──\n";
$bad = PlayerReportFile(['matchId' => 'PRM9', 'note' => "cut \xE2\x82"] + $base, 'tok-iiiiiiii', $conn);
$bj = json_decode((string)@file_get_contents("$tmp/reports/R-{$bad['reportNumber']}/report.json"), true);
check(is_array($bj) && strpos($bj['note'] ?? '', 'cut') === 0, 'a note cut mid-character still saves readable JSON', $bad);
check(!empty($bad['dbSaved']) && $conn && dbRow($conn, $bad['reportNumber']) !== null, 'and the database accepts its row (an invalid UTF-8 byte would be refused)', $bad);

echo "── a conversation from before saved chat ──\n";
ChatPersistUseRoot('');
apcu_delete('chat_l:oldroom'); apcu_delete('chat_version_l:oldroom');
ChatAppendMessage('l:oldroom', 1, 'alice', 'said before the deploy');
ChatPersistUseRoot('SWUSim');
$old = PlayerReportFreezeChat(['l:oldroom']);
check(array_map(fn($r) => $r['text'], $old['l:oldroom'] ?? []) === ['said before the deploy'], 'a conversation never saved to disk is frozen from APCu', $old);
apcu_delete('chat_l:oldroom'); apcu_delete('chat_version_l:oldroom');

if ($conn) $conn->query('DROP TABLE IF EXISTS swusim_player_reports_test');
foreach (['l:prroom', '7001'] as $k) { apcu_delete('chat_' . $k); apcu_delete('chat_version_' . $k); }
apcu_delete(ChatSidecarKey('7001')); apcu_delete(ChatSidecarKey('PRM1'));
rrm($tmp);
echo $FAILS === 0 ? "PASS: test_player_report_lib — all $CHECKS checks passed\n" : "FAIL: test_player_report_lib — $FAILS failed\n";
exit($FAILS === 0 ? 0 : 1);
