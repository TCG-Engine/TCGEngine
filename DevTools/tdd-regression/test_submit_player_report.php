<?php
// SWUSim/SubmitPlayerReport.php — who may report whom, and one report per reporter per MATCH (game 2 of a Bo3 is a
// follow-up, not a new report). Drives the endpoint in-process through its __REPORT_TEST seam (the same shape as
// BlockedUsers.php's __BLOCKED_TEST), against a fixture Bo3 match, temp stores and a scratch table.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off -d apc.enable_cli=1 DevTools/tdd-regression/test_submit_player_report.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir(__DIR__ . '/../..');
define('PLAYER_REPORT_TABLE', 'swusim_player_reports_test');
require_once './Core/NetworkingLibraries.php';
require_once './Database/ConnectionManager.php';
require_once './PlayerReportLib.php';
require_once './Core/Match/Match.php';
require_once './SWUSim/MatchFlow.php';

$FAILS = 0; $CHECKS = 0;
function check($cond, $msg, $extra = null) {
    global $FAILS, $CHECKS;
    $CHECKS++;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . (($cond || $extra === null) ? '' : '  ' . json_encode($extra)) . "\n";
    if (!$cond) $FAILS++;
}
function rrm($d) {
    if (!file_exists($d)) return;
    if (is_file($d) || is_link($d)) { @unlink($d); return; }
    foreach (scandir($d) as $f) if ($f !== '.' && $f !== '..') rrm("$d/$f");
    @rmdir($d);
}
function report(int $uid, array $post) {
    $GLOBALS['__REPORT_TEST'] = true;
    $GLOBALS['__REPORT_TEST_UID'] = $uid;
    $GLOBALS['__REPORT_TEST_POST'] = $post;
    return include './SWUSim/SubmitPlayerReport.php';
}

$tmp = sys_get_temp_dir() . '/preportep-' . bin2hex(random_bytes(4));
mkdir("$tmp/chats", 0777, true); mkdir("$tmp/reports", 0777, true);
putenv("SWUSIM_CHAT_DIR=$tmp/chats");
putenv("SWUSIM_PLAYERREPORT_DIR=$tmp/reports");
$conn = GetLocalMySQLConnection();
$conn->query('DROP TABLE IF EXISTS swusim_player_reports_test');
$conn->query(preg_replace('/^--.*$/m', '', str_replace('`swusim_player_reports`', '`swusim_player_reports_test`', file_get_contents('./Database/migrations/21_swusim_player_reports.sql'))));

// A private Bo3 between accounts 9001 and 9002 (room "prtestroom"), with two games; and one with a guest seat.
// Numeric, like every real game name — a chat scope token that is not numeric is refused (ChatScopeKey).
$g1 = '99' . getmypid() . '1'; $g2 = '99' . getmypid() . '2'; $g3 = '99' . getmypid() . '3';
$matchId = MatchCreate('SWUSim', 'premier', 'bo3', [1 => ['userId' => 9001, 'authKey' => 'k1'], 2 => ['userId' => 9002, 'authKey' => 'k2']], true, 'l:prtestroom');
MatchWriteRef('SWUSim', $g1, $matchId, 1);
MatchWriteRef('SWUSim', $g2, $matchId, 2);
$g4 = '99' . getmypid() . '4';
$tableMatch = MatchCreate('SWUSim', 'twinsuns', 'bo1', [1 => ['userId' => 9001, 'authKey' => 'k5'], 2 => ['userId' => 9002, 'authKey' => 'k6'], 3 => ['userId' => 9004, 'authKey' => 'k7']], true);
MatchWriteRef('SWUSim', $g4, $tableMatch, 1);
$guestMatch = MatchCreate('SWUSim', 'premier', 'bo1', [1 => ['userId' => 9001, 'authKey' => 'k3'], 2 => ['userId' => null, 'authKey' => 'k4']], true);
MatchWriteRef('SWUSim', $g3, $guestMatch, 1);
ChatPersistUseRoot('SWUSim');
apcu_delete('chat_l:prtestroom'); apcu_delete('chat_version_l:prtestroom');
ChatAppendMessage($g1, 2, 'bob', 'game one insult');

echo "── who may report ──\n";
$r = report(0, ['gameName' => $g1, 'note' => 'x', 'clientToken' => 'tok-00000000']);
check(($r['success'] ?? null) === false && ($r['error'] ?? '') === 'not_logged_in', 'a guest cannot report', $r);
$r = report(9003, ['gameName' => $g1, 'note' => 'x', 'clientToken' => 'tok-11111111']);
check(($r['success'] ?? null) === false && ($r['error'] ?? '') === 'no_opponent', 'an account not seated in the match cannot report', $r);
$r = report(9001, ['gameName' => $g3, 'note' => 'x', 'clientToken' => 'tok-22222222']);
check(($r['success'] ?? null) === false && ($r['error'] ?? '') === 'no_opponent', 'a guest opponent cannot be reported', $r);

$r = report(9001, ['gameName' => $g4, 'note' => 'x', 'clientToken' => 'tok-99999999']);
check(($r['success'] ?? null) === false && ($r['error'] ?? '') === 'not_one_v_one', 'a 3-seat (Twin Suns) table cannot report — Report is 1v1 only', $r);

echo "── one report per reporter per match ──\n";
$r = report(9001, ['gameName' => $g1, 'note' => 'abusive', 'clientToken' => 'tok-33333333']);
check(($r['success'] ?? null) === true && $r['kind'] === 'new' && $r['message'] === 'Reported. A moderator will review it.' && $r['report_number'] === 'R-1', 'game 1: a new report', $r);
$j = json_decode((string)@file_get_contents("$tmp/reports/R-1/report.json"), true);
check(($j['reported_id'] ?? 0) === 9002 && ($j['reporter_seat'] ?? 0) === 1 && ($j['reported_seat'] ?? 0) === 2 && ($j['match_id'] ?? '') === $matchId && ($j['lobby_id'] ?? '') === 'prtestroom' && ($j['format'] ?? '') === 'premier', 'it names the reported account, seats, match, room and format', $j);
$chat = json_decode((string)@file_get_contents("$tmp/reports/R-1/chat.json"), true);
check(in_array('game one insult', array_map(fn($row) => $row['text'], $chat['l:prtestroom'] ?? []), true), "the frozen chat holds the match's conversation", $chat);
$r = report(9001, ['gameName' => $g2, 'note' => 'again in game 2', 'clientToken' => 'tok-44444444']);
check(($r['success'] ?? null) === true && $r['kind'] === 'followup' && $r['message'] === 'Added to your earlier report.' && $r['report_number'] === 'R-1', 'game 2 of the same Bo3: a follow-up on R-1', $r);
report(9001, ['gameName' => $g2, 'note' => 'f2', 'clientToken' => 'tok-55555555']);
report(9001, ['gameName' => $g2, 'note' => 'f3', 'clientToken' => 'tok-66666666']);
$r = report(9001, ['gameName' => $g2, 'note' => 'f4', 'clientToken' => 'tok-77777777']);
check(($r['kind'] ?? '') === 'limit' && $r['message'] === "You've already reported this player for this match.", 'past three follow-ups: already reported', $r);

echo "── the note ──\n";
$long = str_repeat('é', 1200);
$r = report(9002, ['gameName' => $g1, 'note' => $long, 'clientToken' => 'tok-88888888']);
$j2 = json_decode((string)@file_get_contents("$tmp/reports/" . ($r['report_number'] ?? 'none') . "/report.json"), true);
check(mb_strlen($j2['note'] ?? '') === 1000 && mb_check_encoding($j2['note'] ?? '', 'UTF-8'), 'a long note is cut to 1000 characters, never mid-character', mb_strlen($j2['note'] ?? ''));

$conn->query('DROP TABLE IF EXISTS swusim_player_reports_test');
apcu_delete('chat_l:prtestroom'); apcu_delete('chat_version_l:prtestroom');
foreach ([$g1, $g2, $g3, $g4] as $g) { apcu_delete(ChatSidecarKey($g)); rrm(dirname(MatchRefPath('SWUSim', $g))); }
foreach ([$matchId, $guestMatch, $tableMatch] as $m) { apcu_delete(ChatSidecarKey($m)); rrm(dirname(MatchPath('SWUSim', $m))); }
rrm($tmp);
echo $FAILS === 0 ? "PASS: test_submit_player_report — all $CHECKS checks passed\n" : "FAIL: test_submit_player_report — $FAILS failed\n";
exit($FAILS === 0 ? 0 : 1);
