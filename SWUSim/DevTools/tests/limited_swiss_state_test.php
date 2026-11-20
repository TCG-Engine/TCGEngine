<?php
// Limited Swiss rules — standings and tiebreaks, pairing, and the round flow (claim, commit, results, the between-rounds
// clock, drops). docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.4, §3.5, §3.6.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php SWUSim/DevTools/tests/limited_swiss_state_test.php
chdir(dirname(__DIR__, 3));
require_once './SWUSim/Limited/LimitedSwissState.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

$now = 1_800_000_000;
const A = 1, B = 2, C = 3, D = 4, E = 5;
// A playing Draft room with these seats (draft.order = $order) and finished rounds from compact rows [a, b|null, ga, gb]:
// b null = a bye (games filled in from $bestOf); equal games = a drawn match.
function swissLt(array $order, array $rounds, int $bestOf = 1): array {
    $lt = LTNew('draft');
    $lt['settings']['bestOf'] = $bestOf;
    foreach ($order as $p) $lt['seats'][$p] = ['name' => "p$p", 'deck' => ['leader' => 'L', 'base' => 'B', 'main' => []],
                                                'submitted' => true, 'ready' => true, 'dropped' => false];
    $lt['draft'] = ['order' => $order];
    $lt['phase'] = 'playing';
    $lt['seed'] = 4242;
    $lt['swiss'] = LTSwissNew();
    foreach ($rounds as $i => $rows) {
        $lt['swiss']['pairings'][$i + 1] = array_map(fn($r) => row($r, $bestOf), $rows);
        $lt['swiss']['round'] = $i + 1;
    }
    return $lt;
}
function row(array $r, int $bestOf): array {
    [$a, $b, $ga, $gb] = $r + [2 => 0, 3 => 0];
    if ($b === null) return ['a' => $a, 'b' => null, 'bye' => true, 'matchId' => '', 'gameName' => '', 'seats' => [],
                             'done' => true, 'games' => [$a => $bestOf === 3 ? 2 : 1], 'winner' => $a, 'draw' => false];
    return ['a' => $a, 'b' => $b, 'bye' => false, 'matchId' => "M$a$b", 'gameName' => "G$a$b", 'seats' => [1 => $a, 2 => $b],
            'done' => true, 'games' => [$a => $ga, $b => $gb], 'winner' => $ga > $gb ? $a : ($gb > $ga ? $b : null), 'draw' => $ga === $gb];
}
function byPid(array $rows): array { $o = []; foreach ($rows as $r) $o[$r['playerID']] = $r; return $o; }
$r4 = fn($x) => round($x, 4);

echo "StandingsBo3FourPlayers:\n";
$bo3 = [[[A, B, 2, 0], [C, D, 2, 1]], [[A, C, 2, 1], [B, D, 2, 0]], [[D, A, 2, 1], [B, C, 2, 1]]];
$st = LTSwissStandings(swissLt([A, B, C, D], $bo3, 3));
$check(array_column($st, 'playerID') === [A, B, C, D], 'rank order A, B, C, D: ' . json_encode(array_column($st, 'playerID')));
$check(array_column($st, 'points') === [6, 6, 3, 3], 'points 6, 6, 3, 3');
$check(array_column($st, 'rank') === [1, 2, 3, 4], 'ranks 1–4');
$s = byPid($st);
$check([$r4($s[A]['omw']), $r4($s[A]['gw']), $r4($s[A]['ogw'])] === [0.4444, 0.625, 0.4636], 'A: omw .4444, gw .6250, ogw .4636');
$check([$r4($s[B]['omw']), $r4($s[B]['gw']), $r4($s[B]['ogw'])] === [0.4444, 0.5714, 0.4815], 'B: omw .4444, gw .5714, ogw .4815');
$check([$r4($s[C]['omw']), $r4($s[C]['gw'])] === [0.5556, 0.4444], 'C: omw .5556, gw .4444');
$check([$r4($s[D]['omw']), $r4($s[D]['gw'])] === [0.5556, 0.375], 'D: omw .5556, gw .3750');
$check([$s[A]['wins'], $s[A]['losses'], $s[A]['draws'], $s[A]['byes']] === [2, 1, 0, 0], 'A is 2-1-0, no bye');

echo "FloorAtOneThird:\n";
$s = byPid(LTSwissStandings(swissLt([A, B, C, D], [[[A, B, 1, 0], [C, D, 1, 0]], [[A, C, 1, 0], [B, D, 1, 0]], [[A, D, 1, 0], [B, C, 1, 0]]])));
$check($r4($s[D]['mw']) === 0.3333, 'D (0–3) has mw .3333, floored: ' . $s[D]['mw']);
$check($r4($s[A]['omw']) === 0.4444, 'A\'s omw counts D at the floor (.4444, not .3333): ' . $s[A]['omw']);

echo "ByesCountForSelfNotAsOpponent:\n";
$byes = [[[A, B, 1, 0], [C, D, 1, 0], [E, null]], [[A, C, 1, 0], [B, E, 1, 0], [D, null]], [[D, A, 1, 0], [C, E, 1, 0], [B, null]]];
$st = LTSwissStandings(swissLt([A, B, C, D, E], $byes));
$check(array_column($st, 'playerID') === [D, A, C, B, E], 'order D, A, C, B, E (D beat A head-to-head): ' . json_encode(array_column($st, 'playerID')));
$s = byPid($st);
$check([$r4($s[A]['omw']), $r4($s[C]['omw']), $r4($s[B]['omw'])] === [0.6667, 0.5556, 0.5], 'omw A .6667, C .5556, B .5000');
$check($s[E]['points'] === 3 && $s[E]['byes'] === 1 && $s[D]['byes'] === 1 && $s[B]['byes'] === 1 && $s[A]['byes'] === 0, 'E has 3 points; each bye seat has one bye');

echo "ByeIsTwoNilInBo3:\n";
$s = byPid(LTSwissStandings(swissLt([A, B, C], [[[B, C, 2, 1], [A, null]]], 3)));
$check($s[A]['gw'] === 1.0 && $s[A]['points'] === 3 && $s[A]['omw'] === 1 / 3, 'a Bo3 bye is 2–0: gw 1.0, 3 points, no opponents (omw 1/3)');

echo "DroppedStaysInStandings:\n";
$lt = swissLt([A, B, C, D], $bo3, 3);
$lt['seats'][C]['dropped'] = true;
$s = byPid(LTSwissStandings($lt));
$check(isset($s[C]) && $s[C]['dropped'] === true && $r4($s[A]['omw']) === 0.4444, 'a dropped C is listed, marked dropped, and still in A\'s omw');

echo "DrawIsOnePoint:\n";
$s = byPid(LTSwissStandings(swissLt([A, B], [[[A, B, 1, 1]]], 3)));
$check($s[A]['points'] === 1 && $s[B]['points'] === 1 && $r4($s[A]['mw']) === 0.3333 && $s[A]['draws'] === 1, 'a 1–1 drawn match: 1 point each, mw .3333');

echo "SeatingBreaksAFullTie:\n";
$st = LTSwissStandings(swissLt([C, A, B, D], [[[A, B, 1, 0], [C, D, 1, 0]]]));
$check(array_column($st, 'playerID') === [C, A, B, D], 'identical records, no head-to-head: seating order C before A, B before D: ' . json_encode(array_column($st, 'playerID')));

// ── Pairing ───────────────────────────────────────────────────────────────────────────────────────────────────────────
$pairs = fn(array $ps) => array_map(fn($x) => [$x['a'], $x['b']], $ps);
$flat = function (array $ps) { $o = []; foreach ($ps as $x) { $o[] = $x['a']; if ($x['b'] !== null) $o[] = $x['b']; } sort($o); return $o; };
// The rounds so far as compact rows, from LTSwissPair output, with a result rule: $win(a, b) → the winner.
function playRound(array $lt, int $round, callable $win): array {
    $rows = [];
    foreach (LTSwissPair($lt, $round) as $x) $rows[] = $x['b'] === null ? [$x['a'], null] : ($win($x['a'], $x['b']) === $x['a'] ? [$x['a'], $x['b'], 1, 0] : [$x['a'], $x['b'], 0, 1]);
    $lt['swiss']['pairings'][$round] = array_map(fn($r) => row($r, 1), $rows);
    $lt['swiss']['round'] = $round;
    return $lt;
}

echo "RoundOneIsSeededRandom:\n";
$eight = swissLt([1, 2, 3, 4, 5, 6, 7, 8], []);
$p1 = LTSwissPair($eight, 1);
$check($p1 === LTSwissPair($eight, 1), 'the same blob pairs the same way');
$other = $eight; $other['seed']++;
$check($pairs(LTSwissPair($other, 1)) !== $pairs($p1), 'another seed pairs differently: ' . json_encode($pairs($p1)));
$check(count($p1) === 4 && $flat($p1) === [1, 2, 3, 4, 5, 6, 7, 8] && !in_array(null, array_column($p1, 'b'), true), 'every seat once, 4 tables, no bye');

echo "OddPodGetsOneBye:\n";
$five = swissLt([A, B, C, D, E], []);
$check(count(array_filter(LTSwissPair($five, 1), fn($x) => $x['b'] === null)) === 1 && $flat(LTSwissPair($five, 1)) === [1, 2, 3, 4, 5], 'round 1 of 5: exactly one bye, every seat once');
$after = swissLt([A, B, C, D, E], [[[A, B, 1, 0], [C, D, 1, 0], [E, null]]]);
$p2 = LTSwissPair($after, 2);
$bye = array_values(array_filter($p2, fn($x) => $x['b'] === null));
$check(count($bye) === 1 && $bye[0]['a'] === D, 'round 2: the bye goes to the lowest-ranked seat without one (D): ' . json_encode($pairs($p2)));

echo "ThreePlayersPlayEveryoneOnce:\n";
$three = swissLt([A, B, C], []);
for ($r = 1; $r <= 3; $r++) $three = playRound($three, $r, fn($a, $b) => min($a, $b));
$met = []; $byeCount = [A => 0, B => 0, C => 0];
foreach ($three['swiss']['pairings'] as $round) foreach ($round as $x) {
    if ($x['b'] === null) { $byeCount[$x['a']]++; continue; }
    $k = min($x['a'], $x['b']) . '-' . max($x['a'], $x['b']); $met[$k] = ($met[$k] ?? 0) + 1;
}
ksort($met);
$check($met === ['1-2' => 1, '1-3' => 1, '2-3' => 1] && $byeCount === [A => 1, B => 1, C => 1], 'three rounds of three: everyone meets once, one bye each: ' . json_encode([$met, $byeCount]));

echo "LaterRoundsPairByRecord:\n";
$r1 = swissLt([1, 2, 3, 4, 5, 6, 7, 8], [[[1, 2, 1, 0], [3, 4, 1, 0], [5, 6, 1, 0], [7, 8, 1, 0]]]);
$p2 = LTSwissPair($r1, 2);
$winners = [1, 3, 5, 7];
$same = array_filter($p2, fn($x) => in_array($x['a'], $winners, true) === in_array($x['b'], $winners, true));
$fresh = array_filter($p2, fn($x) => abs($x['a'] - $x['b']) !== 1 || min($x['a'], $x['b']) % 2 === 0);
$check(count($same) === 4 && count($fresh) === 4, 'round 2 pairs winners with winners, losers with losers, no rematch: ' . json_encode($pairs($p2)));

echo "NoRematchWithBacktracking:\n";
$got = _LTSwissSearch([1, 2, 3, 4, 5, 6], [[1, 2], [3, 4], [5, 6], [1, 3], [2, 5], [4, 6]], []);
$check($pairs($got) === [[1, 4], [2, 6], [3, 5]], 'greedy would leave 5–6; the search backtracks to 1–4, 2–6, 3–5: ' . json_encode($pairs($got)));

echo "RematchOnlyWhenUnavoidable:\n";
$two = swissLt([A, B, C, D], [[[A, B, 1, 0], [C, D, 1, 0]]]);
$two['seats'][C]['dropped'] = true; $two['seats'][D]['dropped'] = true;
$check($pairs(LTSwissPair($two, 2)) === [[A, B]], 'two live seats who already played are paired again; the dropped never appear');
$got = _LTSwissSearch([1, 2, 3], [[1, 2], [1, 3], [2, 3]], [1, 2, 3]);
$rem = count(array_filter($got, fn($x) => $x['b'] !== null));
$check(count($got) === 2 && $rem === 1 && $flat($got) === [1, 2, 3], 'everyone played everyone: one (unavoidable) rematch and a bye: ' . json_encode($pairs($got)));

echo "SecondByeOnlyWhenUnavoidable:\n";
$got = _LTSwissSearch([1, 2, 3], [], [1, 2, 3]);
$check($pairs($got) === [[1, 2], [3, null]], 'all three had a bye: the lowest-ranked (3) gets a second one: ' . json_encode($pairs($got)));
$got = _LTSwissSearch([1, 2, 3], [], [3]);
$check($pairs($got) === [[1, 3], [2, null]], 'no second bye while another seat can take it: ' . json_encode($pairs($got)));

// ── The round flow ────────────────────────────────────────────────────────────────────────────────────────────────────
function builtLt(array $order, int $bestOf = 1): array {
    $lt = swissLt($order, [], $bestOf);
    unset($lt['swiss']);
    $lt['phase'] = 'built';
    return $lt;
}
// Claim and commit the next round with fake matches "R<round>T<i>".
function startRound(array $lt, int $now, int $host = A, bool $asked = false): ?array {
    $c = LTSwissClaimRound($lt, $now, $host, $asked);
    if ($c === null) return null;
    $round = intval($c['swiss']['round']) + 1; $made = [];
    foreach ($c['swiss']['pending'] as $i => $x) {
        if ($x['b'] !== null) $made[$i] = ['matchId' => "R{$round}T$i", 'gameName' => "g{$round}_$i", 'seats' => [1 => $x['a'], 2 => $x['b']]];
    }
    return LTSwissCommitRound($c, $made, $now);
}
// A finished Match.json for pairing $x: seat 1 (a) wins $ga–$gb.
function doneMatch(array $x, int $ga = 1, int $gb = 0, string $state = 'complete'): array {
    return ['matchId' => $x['matchId'], 'state' => $state, 'wins' => ['1' => $ga, '2' => $gb], 'winner' => $ga > $gb ? 1 : ($gb > $ga ? 2 : 0),
            'games' => [['gameName' => $x['gameName'] . 'x']]];
}
function finishRound(array $lt, int $now): array {
    foreach ($lt['swiss']['pairings'][$lt['swiss']['round']] as $x) if (empty($x['bye'])) $lt = LTSwissApplyMatch($lt, $x['matchId'], doneMatch($x), $now);
    return $lt;
}

echo "BuiltStartsRoundOneAtOnce:\n";
$b4 = builtLt([A, B, C, D]);
$check(LTSwissCanStartRound($b4, $now, A, false), 'a built pod may start round 1 without the host');
$c = LTSwissClaimRound($b4, $now, A, false);
$check($c !== null && count($c['swiss']['pending']) === 2 && intval($c['starting']) === $now, 'the claim holds the start and two pending tables');
$r1 = startRound($b4, $now);
$check($r1 !== null && $r1['phase'] === 'playing' && $r1['swiss']['round'] === 1 && count($r1['swiss']['pairings'][1]) === 2
       && $r1['starting'] === false && $r1['swiss']['pending'] === null, 'commit → playing round 1 with two tables, the claim released');

echo "RoundClaimedOnce:\n";
$check(LTSwissClaimRound($c, $now + 10, A, false) === null, 'a second claim while the first is fresh is refused');
$check(LTSwissClaimRound($c, $now + LT_START_CLAIM_SECONDS, A, false) !== null, 'a claim older than LT_START_CLAIM_SECONDS (a crashed start) is free again');
$check(LTSwissCommitRound($b4, [], $now) === null, 'a commit without a claim is refused');
$check(LTSwissCommitRound($c, [0 => ['matchId' => 'x', 'gameName' => 'g', 'seats' => [1 => 1, 2 => 2]]], $now) === null, 'a commit missing a table\'s match is refused');

echo "ByeIsDoneAtCommit:\n";
$r5 = startRound(builtLt([A, B, C, D, E], 3), $now);
$bye = array_values(array_filter($r5['swiss']['pairings'][1], fn($x) => !empty($x['bye'])));
$check(count($bye) === 1 && $bye[0]['done'] && $bye[0]['games'] === [$bye[0]['a'] => 2] && $bye[0]['winner'] === $bye[0]['a'], 'the bye is done at commit: a 2–0 win in Bo3');

echo "ResultIdempotentAndScoped:\n";
$x0 = $r1['swiss']['pairings'][1][0];
$moved = LTSwissApplyMatch($r1, $x0['matchId'], doneMatch($x0, 1, 0, 'playing'), $now);
$check($moved['swiss']['pairings'][1][0]['gameName'] === $x0['gameName'] . 'x' && !$moved['swiss']['pairings'][1][0]['done'], 'an unfinished match moves the table\'s gameName, and the table stays open');
// Owner fix (2026-11-05): a Bo3 table shows its running game score, not 0–0 until the series ends.
$check($moved['swiss']['pairings'][1][0]['games'] === [$x0['a'] => 1, $x0['b'] => 0] && $moved['swiss']['pairings'][1][0]['winner'] === null,
       'an unfinished match carries its running game score (1–0), with no winner yet');
$once = LTSwissApplyMatch($r1, $x0['matchId'], doneMatch($x0, 2, 1), $now);
$check($once['swiss']['pairings'][1][0]['done'] && $once['swiss']['pairings'][1][0]['games'] === [$x0['a'] => 2, $x0['b'] => 1]
       && $once['swiss']['pairings'][1][0]['winner'] === $x0['a'], 'a complete match records the games and the winner');
$check(LTSwissApplyMatch($once, $x0['matchId'], doneMatch($x0, 2, 1), $now + 5) === $once, 'applying it again changes nothing');
$check(LTSwissApplyMatch($once, $x0['matchId'], doneMatch($x0, 0, 2), $now + 5) === $once, 'a different result for a finished table changes nothing');
$r2 = finishRound($r1, $now);
foreach (LTLiveSeats($r2) as $p) $r2 = LTReady($r2, $p);
$r2 = startRound($r2, $now + 1, A, true);
$check(LTSwissApplyMatch($r2, $x0['matchId'], doneMatch($x0, 0, 2), $now + 9) === $r2, 'a round-1 match applied during round 2 changes nothing');
$drawn = LTSwissApplyMatch($r1, $x0['matchId'], doneMatch($x0, 0, 0, 'abandoned'), $now);
$check($drawn['swiss']['pairings'][1][0]['done'] && $drawn['swiss']['pairings'][1][0]['draw'] && $drawn['swiss']['pairings'][1][0]['winner'] === null, 'an abandoned match is a draw');

echo "RoundEndsIntoBetween:\n";
$bt = finishRound($r1, $now + 100);
$check($bt['phase'] === 'between' && $bt['swiss']['betweenDeadline'] === $now + 100 + LT_BETWEEN_SECONDS, 'the round\'s last result → between, 15 minutes on the clock');
$check(array_filter(LTLiveSeats($bt), fn($p) => !empty($bt['seats'][$p]['ready']) || empty($bt['seats'][$p]['submitted'])) === [], 'every live seat un-Readied, its deck still submitted');

echo "BetweenNeedsReadyThenHost:\n";
$check(!LTSwissCanStartRound($bt, $now + 200, A, true), 'not everyone Ready: cannot start');
$allReady = $bt; foreach (LTLiveSeats($allReady) as $p) $allReady = LTReady($allReady, $p);
$check(!LTSwissCanStartRound($allReady, $now + 200, A, false), 'all Ready, before the deadline, host live, nobody asked: wait for the host');
$check(LTSwissCanStartRound($allReady, $now + 200, A, true), 'the host asks: start');
$noHost = $allReady; $noHost['seats'][A]['dropped'] = true;
$check(LTSwissCanStartRound($noHost, $now + 200, A, false), 'the host has dropped: start as soon as everyone is Ready');

echo "BetweenDeadlineAutoReadiesAndStarts:\n";
$late = LTSwissExpireBetween($bt, $now + 100 + LT_BETWEEN_SECONDS);
$check(array_filter(LTLiveSeats($late), fn($p) => empty($late['seats'][$p]['ready'])) === [] && LTSwissCanStartRound($late, $now + 100 + LT_BETWEEN_SECONDS, A, false),
       'at the deadline everyone is readied with their last deck and the round may start');
$check(LTSwissExpireBetween($bt, $now + 101) === $bt, 'before the deadline nothing changes');

echo "SubmitAndReadyBetweenRounds:\n";
$sub = LTSubmitDeck($bt, B, ['leader' => 'L2', 'base' => 'B2', 'main' => ['x']]);
$rd = $sub ? LTReady($sub, B) : null;
$check($sub !== null && $rd !== null && $rd['seats'][B]['ready'] && $rd['seats'][B]['deck']['leader'] === 'L2', 'Submit then Ready work between rounds (a new leader too)');
$check(LTReady($rd, B) === null && LTSubmitDeck($rd, B, ['leader' => 'L3', 'base' => 'B', 'main' => []]) === null, 'no editing or re-Ready after Ready');

echo "ThirdRoundEndsTheEvent:\n";
$ev = builtLt([A, B, C, D]);
$ev = finishRound(startRound($ev, $now), $now);
for ($r = 2; $r <= 3; $r++) { foreach (LTLiveSeats($ev) as $p) $ev = LTReady($ev, $p); $ev = finishRound(startRound($ev, $now + $r, A, true), $now + $r); }
$top = LTSwissStandings($ev)[0]['playerID'];
$check($ev['phase'] === 'done' && $ev['swiss']['round'] === 3 && $ev['result']['winner'] === $top && $ev['result']['reason'] === 'played',
       'round 3\'s last result ends the event; the winner is standings rank 1: ' . json_encode($ev['result']));

echo "DropBetweenRoundsNotPaired:\n";
$dr = LTDropSeat($bt, C, 'left');
foreach (LTLiveSeats($dr) as $p) $dr = LTReady($dr, $p);
$cl = LTSwissClaimRound($dr, $now + 200, A, true);
$check($cl !== null && !in_array(C, array_merge(array_column($cl['swiss']['pending'], 'a'), array_column($cl['swiss']['pending'], 'b')), true)
       && count($cl['swiss']['pending']) === 2, 'a seat dropped between rounds is not paired (3 live: a table and a bye)');
$one = LTDropSeat(LTDropSeat(LTDropSeat($bt, B, 'left'), C, 'left'), D, 'kicked');
$check($one['phase'] === 'done' && $one['result']['winner'] === A, 'down to one live seat: the event ends, that seat wins');

echo "KickBetweenRoundsOnlyWhenSilent:\n";
$seen = [A => $now + 200, B => $now + 200, C => $now + 200, D => $now + 200];
$check(LTKickableSeats($bt, $seen, $now + 200) === [], 'connected seats between rounds are not kickable');
// Final review #1: the break starts at $now + 100. Players of the last table were last seen when the round began (on their
// board ever since) — nobody is kickable in the break's first LT_BETWEEN_KICK_GRACE seconds, and silence counts from the
// break's start at the earliest.
$old = [A => $now - 1800, B => $now - 1800, C => $now + 200, D => $now + 200];
$check(LTKickableSeats($bt, $old, $now + 101) === [], 'the moment the break starts, seats last seen in the round are not kickable');
$check(LTKickableSeats($bt, $old, $now + 100 + LT_BETWEEN_KICK_GRACE - 1) === [], 'nor during the break\'s grace');
$late = $now + 100 + LT_BETWEEN_KICK_GRACE + 1;
$check(LTKickableSeats($bt, [A => $now - 1800, B => $late, C => $late, D => $late - 31], $late) === [A, D], 'after the grace, a seat silent 31 s is (A never came back, D went quiet)');
$check(LTKickableSeats($r1, [D => 0] + $seen, $now + 200) === [], 'while a round plays nobody is (the in-game kick decides)');

echo "SealedUntouched:\n";
$se = LTNew('sealed'); $se['seats'] = [1 => ['name' => 'a', 'ready' => true, 'submitted' => true, 'dropped' => false, 'deck' => []], 2 => ['name' => 'b', 'ready' => true, 'submitted' => true, 'dropped' => false, 'deck' => []]];
$se['phase'] = 'building';
$check(!LTSwissCanStartRound($se, $now, 1, true) && LTSwissClaimRound($se, $now, 1, true) === null && LTReadyToStart($se, $now), 'a Sealed room never starts a Swiss round, and its own start is as before');

echo "BotsNeverKickableDraftingOrBetween:\n";   // Plan 5
require_once './SWUSim/Limited/LimitedDraftState.php';
$bb = $bt; $bb['seats'][C]['bot'] = true; $bb['seats'][D]['bot'] = true;
$late = $now + 100 + LT_BETWEEN_KICK_GRACE + 5;
$check(LTKickableSeats($bb, [A => $late, B => $late], $late) === [], 'between rounds, silent bots are never kickable');
$dr = $bb; $dr['phase'] = 'drafting'; $dr['draft']['autoPicks'] = [A => 0, B => 0, C => 3, D => 3]; $dr['draft']['timeoutRun'] = [A => 0, B => 0, C => 3, D => 3];
$check(LTKickableSeats($dr, [A => $late, B => $late], $late) === [], 'while drafting, silent (even auto-picked) bots are never kickable');

echo "BotVsBotIsACoinToss:\n";   // Plan 5 (spec §3.7): bot-vs-bot is never played — each game is a coin toss
foreach ([1, 3] as $bo) {
    $bb4 = builtLt([A, B, C, D], $bo);
    $bb4['seats'][C]['bot'] = true; $bb4['seats'][D]['bot'] = true;
    $cl = LTSwissClaimRound($bb4, $now, A, false);
    $bi = null; $hi = null;
    foreach ($cl['swiss']['pending'] as $i => $x) { if (LTIsBot($cl, $x['a']) && $x['b'] !== null && LTIsBot($cl, $x['b'])) $bi = $i; else $hi = $i; }
    if ($bi === null) { $check(false, "Bo$bo: the fixture should pair the two bots (got " . json_encode($cl['swiss']['pending']) . ')'); continue; }
    $made = [$hi => ['matchId' => 'MH', 'gameName' => 'gH', 'seats' => [1 => $cl['swiss']['pending'][$hi]['a'], 2 => $cl['swiss']['pending'][$hi]['b']]]];
    $c1 = LTSwissCommitRound($cl, $made, $now);
    $c2 = LTSwissCommitRound($cl, $made, $now);
    $bt1 = $c1['swiss']['pairings'][1][$bi] ?? [];
    $g = array_values($bt1['games'] ?? []); rsort($g);
    $okGames = $bo === 1 ? $g === [1, 0] : ($g === [2, 0] || $g === [2, 1]);
    $check($c1 !== null && !empty($bt1['done']) && $bt1['matchId'] === '' && $okGames && $bt1['winner'] !== null,
           "Bo$bo: the bot table needs no match and is decided at commit: " . json_encode(['games' => $bt1['games'] ?? null, 'winner' => $bt1['winner'] ?? null]));
    $check($c1 === $c2, "Bo$bo: the toss is seeded — the same blob commits the same way");
    $check(empty($c1['swiss']['pairings'][1][$hi]['done']) && $c1['phase'] === 'playing', "Bo$bo: the human table still waits for its match");
}

echo "BotsReadyAtTheBreak:\n";
$br = startRound((function () use ($now) { $x = builtLt([A, B, C, D]); $x['seats'][C]['bot'] = true; return $x; })(), $now);
$br = finishRound($br, $now);
$check($br['phase'] === 'between' && !empty($br['seats'][C]['ready']) && empty($br['seats'][A]['ready']) && empty($br['seats'][B]['ready']),
       'when the break starts the bot is Ready at once; the humans are not');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
