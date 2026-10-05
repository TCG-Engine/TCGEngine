<?php
// metapremier outcome classifier — docs/superpowers/specs/2026-10-03-swusim-metapremier-ratings-design.md §4.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php SWUSim/DevTools/tests/metapremier_classify_test.php
chdir(dirname(__DIR__, 3));
require_once './SWUSim/MetaPremier.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

$g = fn($winner, $reason = 'win', $pregame = true) => ['gameName'=>'x','winner'=>$winner,'detail'=>['pregameDone'=>$pregame,'endReason'=>$reason]];
$base = fn(array $games, $winner, array $extra = []) => array_merge([
    'matchId'=>'M500','createdAt'=>1700000000,'format'=>'metapremier','queueType'=>'bo3','state'=>'complete','winner'=>$winner,
    'players'=>['1'=>['userId'=>11],'2'=>['userId'=>22]],'games'=>$games], $extra);

$c = SWUMetaPremierClassify($base([$g(1), $g(1)], 1));
$check($c !== null && $c['outcome'] === 'win' && $c['winnerUserId'] === 11 && $c['loserUserId'] === 22 && $c['started'], '2-0 win');
$check($c !== null && $c['matchId'] === 'M500' && $c['matchCreatedAt'] === 1700000000 && $c['queueType'] === 'bo3', 'identity fields carried');
$check(SWUMetaPremierClassify($base([$g(1)], 1, ['format'=>'premier'])) === null, 'unrated format skipped');
$check(SWUMetaPremierClassify($base([$g(1)], 1, ['state'=>'in_progress'])) === null, 'incomplete skipped');
$check(SWUMetaPremierClassify($base([$g(1)], 1, ['players'=>['1'=>['userId'=>11],'2'=>['userId'=>null]]])) === null, 'guest seat skipped');
$check(SWUMetaPremierClassify($base([$g(1)], 1, ['players'=>['1'=>['userId'=>11],'2'=>['userId'=>22],'3'=>['userId'=>33]]])) === null, '3 seats skipped');
$check(SWUMetaPremierClassify($base([$g(1)], 1, ['players'=>['1'=>['userId'=>11],'2'=>['userId'=>11]]])) === null, 'same account on both seats skipped');
$check(SWUMetaPremierClassify($base([$g(1)], 0)) === null, 'no winner skipped');
$check(SWUMetaPremierClassify($base([$g(2, 'concede'), $g(2, 'concede')], 2))['outcome'] === 'concede', 'deciding concede');
$check(SWUMetaPremierClassify($base([$g(2)], 2, ['concededBy'=>1]))['outcome'] === 'concede', 'whole-match concede');
$check(SWUMetaPremierClassify($base([$g(1, 'abandon'), $g(1)], 1))['outcome'] === 'abandon', 'loser abandoned game 1, lost series');
// P2 abandons game 1 (P1 wins it), then P2 takes games 2 and 3: the series loser (P1) never abandoned.
$check(SWUMetaPremierClassify($base([$g(1, 'abandon'), $g(2), $g(2)], 2))['outcome'] === 'win', "winner's own abandon does not mark the loser");
$pre = SWUMetaPremierClassify($base([$g(1, 'abandon', false)], 1));
$check($pre['outcome'] === 'abandon' && $pre['started'] === false, 'pregame abandon: not started');
$check(SWUMetaPremierClassify($base([['gameName'=>'x','winner'=>1]], 1, ['concededBy'=>2]))['started'] === false, 'no detail: not started');
$check(SWUMetaPremierClassify($base([$g(1, 'win', false), $g(1, 'win', false)], 1))['started'] === true, 'two games: started');
// One game only (a Bo1, or a Bo3 conceded during game 1): started rests on game 1's pregame flag alone.
$check(SWUMetaPremierClassify($base([$g(1, 'concede', true)], 1, ['queueType' => 'bo1']))['started'] === true, 'one game past pregame: started');

// Final review #2: a whole-match forfeit from a path with no live gamestate (block opponent) cannot say whether
// pregame finished. It is a deliberate forfeit, so it is rated rather than being a free exit.
$check(SWUMetaPremierClassify($base([['gameName'=>'x','winner'=>1,'detail'=>['endReason'=>'concede','pregameUnknown'=>true]]], 1, ['concededBy'=>2]))['started'] === true,
    'forfeit with pregame unknown: started');

// ── Early concede (owner, 2026-10-05): conceding before Round 2's action phase is punished like an abandon. ──────
// `turns` is the engine's round counter (SWUCaptureCurrentGameDetail); it reaches 2 only when Round 1's regroup ends,
// so turns < 2 covers the mulligans, all of Round 1 and Round 1's regroup — the same cutoff SWUStats uses.
$gt = fn($winner, $reason, $turns, $pregame = true) => ['gameName'=>'x','winner'=>$winner,'detail'=>['pregameDone'=>$pregame,'endReason'=>$reason,'turns'=>$turns]];
$check(SWUMetaPremierClassify($base([$gt(1, 'concede', 1)], 1))['outcome'] === 'early', 'concede in Round 1 (or its regroup): early');
$check(SWUMetaPremierClassify($base([$gt(1, 'concede', 2)], 1))['outcome'] === 'concede', 'concede once Round 2 has begun: an ordinary concede');
$pre = SWUMetaPremierClassify($base([$gt(1, 'concede', 1, false)], 1));
$check($pre['outcome'] === 'early' && $pre['started'] === false, 'concede during mulligans: early, not started (a strike, no rating)');
$check(SWUMetaPremierClassify($base([$gt(2, 'win', 5), $gt(2, 'concede', 1)], 2))['outcome'] === 'early', "game 2 conceded in its first round: early");
$check(SWUMetaPremierClassify($base([$gt(1, 'concede', 1), $gt(2, 'win', 6), $gt(2, 'win', 7)], 2))['outcome'] === 'win', "the series WINNER's own early concede does not mark the loser");
$check(SWUMetaPremierClassify($base([['gameName'=>'x','winner'=>1,'detail'=>['endReason'=>'concede','pregameUnknown'=>true]]], 1, ['concededBy'=>2]))['outcome'] === 'concede',
    'blocking the opponent (round unknown) stays an ordinary concede');
$check(SWUMetaPremierClassify($base([$gt(1, 'abandon', 1)], 1))['outcome'] === 'abandon', 'an abandon stays an abandon, even in Round 1');
$check(SWUMetaPremierClassify($base([$gt(1, 'win', 1)], 1))['outcome'] === 'win', 'a Round-1 base kill is a win, not early');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
