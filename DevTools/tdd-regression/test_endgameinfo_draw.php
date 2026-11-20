<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_endgameinfo_draw.php
// The end-game menu's data (SWUSim/EndGameInfo.php) for a DRAWN game (docs/superpowers/specs/2026-10-30-swusim-draws-design.md):
// isDraw / matchDraw appear ONLY when true, didWin is false and winners is empty, for players and spectators alike. A
// decided game's response keeps exactly the keys, in exactly the order, it had before draws existed (a public contract).
header('Content-Type: text/plain');
chdir(__DIR__ . '/../..');
include_once './SWUSim/MatchFlow.php';

$fails = 0;
function check($cond, $msg) { global $fails; echo ($cond ? '  ok: ' : '  BAD: ') . $msg . "\n"; if (!$cond) $fails++; }

// Captured from the endpoint on 2026-10-30, before draws: a won Bo1, seat 1 and a spectator.
const WON_KEYS_SEAT = ["origin","endScreen","isMatch","tableRematch","matchId","sideboardPending","didWin","winners","winnerNames","seatCount","bestOf","matchState","gameNumber","wins","matchWinner","convertible","rated","convertRequestedByMe","convertRequestedByOpp","seriesOver","isSpectator","statsStatus","statsHtml"];
const WON_KEYS_SPEC = ["origin","isMatch","tableRematch","matchId","sideboardPending","didWin","winners","winnerNames","seatCount","bestOf","matchState","gameNumber","wins","matchWinner","convertible","rated","convertRequestedByMe","convertRequestedByOpp","seriesOver","isSpectator","statsStatus","statsHtml"];

$made = [];
$mk = function (string $result, string $queue = 'bo1') use (&$made): string {   // game 1: 'D' drawn or '1' won by seat 1
    $p = fn($k) => ['originalDeck' => ['leader' => 'SOR_014', 'base' => 'SOR_019', 'cards' => []], 'authKey' => $k];
    $mid = MatchCreate('SWUSim', 'premier', $queue, [1 => $p('egdk1'), 2 => $p('egdk2')], true);
    $g = 'egidraw_' . $result . $queue . '_' . getmypid();
    MatchWithLock('SWUSim', $mid, function (&$m) use ($g) { $m['games'][] = ['gameName' => $g, 'gameNumber' => 1, 'winner' => null]; });
    @mkdir('./SWUSim/Games/' . $g, 0777, true);
    MatchWriteRef('SWUSim', $g, $mid, 1);
    if ($result === 'D') MatchRecordGameDraw('SWUSim', $mid, $g, 5); else MatchRecordGameResult('SWUSim', $mid, $g, 1, 5, [1]);
    $made[] = [$g, $mid];
    return $g;
};
$egi = fn(string $g, string $seat, string $key) =>
    json_decode(strval(file_get_contents("http://localhost/TCGEngine/SWUSim/EndGameInfo.php?gameName=$g&playerID=$seat&authKey=$key")), true) ?? [];

echo "drawn game:\n";
$g = $mk('D');
$j = $egi($g, '1', 'egdk1');
check(($j['isDraw'] ?? null) === true && ($j['matchDraw'] ?? null) === true, 'isDraw and matchDraw are true');
check(($j['didWin'] ?? null) === false && ($j['winners'] ?? null) === [] && intval($j['matchWinner'] ?? -1) === 0, 'didWin false, no winners, matchWinner 0');
$k = array_keys($j);
check(end($k) === 'statsHtml', 'statsHtml stays last');
$s = $egi($g, 'S', '');
check(($s['isDraw'] ?? null) === true && array_key_exists('didWin', $s) && $s['didWin'] === null, 'a spectator sees the draw too');

echo "NEGATIVE: a won game is unchanged\n";
$g = $mk('1');
check(array_keys($egi($g, '1', 'egdk1')) === WON_KEYS_SEAT, 'seat 1: exactly the keys, in order, it had before draws');
check(array_keys($egi($g, 'S', '')) === WON_KEYS_SPEC, 'spectator: exactly the keys, in order, it had before draws');

echo "Bo3 score line:\n";
$h = strval($egi($mk('D', 'bo3'), '1', 'egdk1')['statsHtml'] ?? '');
check(str_contains($h, 'Match score: 0 – 0, 1 draw (game 1)'), 'a drawn game 1 shows in the score: ' . (preg_match('/Match score:[^<]*/', $h, $mm) ? $mm[0] : '-'));
$h = strval($egi($mk('1', 'bo3'), '1', 'egdk1')['statsHtml'] ?? '');
check(str_contains($h, 'Match score: 1 – 0 (game 1)'), 'NEGATIVE: no draws, the score line is unchanged: ' . (preg_match('/Match score:[^<]*/', $h, $mm) ? $mm[0] : '-'));

foreach ($made as [$g, $mid]) exec('rm -rf ' . escapeshellarg('./SWUSim/Games/' . $g) . ' ' . escapeshellarg(MatchesDir('SWUSim') . '/' . $mid));
echo $fails === 0 ? "PASS\n" : "$fails FAILED\n";
