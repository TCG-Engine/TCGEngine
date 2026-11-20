<?php
// High Ground (docs/superpowers/specs/2026-10-30-swusim-high-ground-design.md) — the pure room state: the hill, the line,
// the bleachers, fixed guest names and the "Tonight" table. No I/O: every function takes the state and returns the new one.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php SWUSim/DevTools/tests/high_ground_state_test.php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE); ini_set('display_errors', '1');
chdir(__DIR__ . '/../../..');
require_once './SWUSim/HighGroundState.php';

$FAILS = 0;
function check($cond, $msg) { global $FAILS; echo ($cond ? '  ok: ' : '  BAD: ') . $msg . "\n"; if (!$cond) $FAILS++; }

// A room with members A, B, C, D (accounts), in that join order.
function hg4(): array {
    $hg = HGNew();
    foreach (['A', 'B', 'C', 'D'] as $i => $k) $hg = HGAddMember($hg, $k, 'user' . $k, 100 + $i);
    return $hg;
}
// One hill match from the next pair, won by $winnerSeat (0 = drawn match).
function playMatch(array $hg, string $matchId, int $winnerSeat): array {
    $hg = HGBeginMatch($hg, $matchId);
    return HGEndMatch($hg, $winnerSeat);
}
function seat(string $key, string $deck, string $leader = 'L1', string $base = 'B1', int $userId = 0, string $name = 'Deck'): array {
    return ['key' => $key, 'userId' => $userId, 'deckLink' => $deck, 'leader' => $leader, 'base' => $base, 'deckName' => $name];
}

echo "Join_GoesToBackOfLine:\n";
$hg = hg4();
check($hg['line'] === ['A', 'B', 'C', 'D'], 'the line is join order: ' . implode(',', $hg['line']));
check($hg['hill'] === [] && $hg['bleachers'] === [] && $hg['live'] === null, 'nobody on the hill or in the bleachers yet');
check($hg['names']['A'] === 'userA', 'an account keeps its username');

echo "Guest_GetsFixedName:\n";
$g = HGAddMember(HGAddMember(HGNew(), 'g1', null), 'g2', '');
check($g['names']['g1'] === 'Guest 1' && $g['names']['g2'] === 'Guest 2', 'guests are numbered in join order');
$g = HGAddMember(HGRemoveMember($g, 'g1'), 'g3', null);
check($g['names']['g2'] === 'Guest 2', 'a guest keeps their name when another leaves');
check($g['names']['g3'] === 'Guest 3', 'a guest number is never reused');
check(!isset($g['names']['g1']), 'a member who left has no name entry');

echo "FirstPair_IsFirstTwoInLine:\n";
check(HGNextPair(hg4()) === ['A', 'B'], 'the first two in line play first');
check(HGNextPair(HGAddMember(HGNew(), 'A', 'a')) === ['A'], 'one member: no pair yet');
check(HGBeginMatch(HGAddMember(HGNew(), 'A', 'a'), 'm0') === null, 'a match needs two players');

echo "Win_LoserToBack_WinnerHolds:\n";
$hg = HGBeginMatch(hg4(), 'm1');
check($hg['live'] === ['matchId' => 'm1', 'keys' => [1 => 'A', 2 => 'B']], 'live: A is seat 1, B seat 2');
check($hg['hill'] === ['A', 'B'] && $hg['line'] === ['C', 'D'], 'the pair left the line');
$hg = HGEndMatch($hg, 1);
check($hg['hill'] === ['A'] && $hg['line'] === ['C', 'D', 'B'] && $hg['live'] === null, 'A holds, B to the back');
check(HGNextPair($hg) === ['A', 'C'], 'next is the winner vs the next in line');
$hg = playMatch($hg, 'm2', 2);
check($hg['hill'] === ['C'] && $hg['line'] === ['D', 'B', 'A'], 'a challenger who wins takes the hill');

echo "MidMatchJoiner_BackOfLine:\n";
$hg = HGAddMember(HGBeginMatch(hg4(), 'm1'), 'E', 'userE');
check($hg['line'] === ['C', 'D', 'E'], 'a mid-match joiner waits at the back');
$hg = HGEndMatch($hg, 1);
check($hg['line'] === ['C', 'D', 'E', 'B'], 'the loser goes behind them');

echo "Bleachers_SkippedByNextPair:\n";
$hg = HGToBleachers(hg4(), 'B');
check($hg['bleachers'] === ['B'] && $hg['line'] === ['A', 'C', 'D'], 'B sits in the bleachers');
check(HGNextPair($hg) === ['A', 'C'], 'the bleachers are skipped');

echo "JoinLine_FromBleachers_ToBack:\n";
$hg = HGJoinLine($hg, 'B');
check($hg['line'] === ['A', 'C', 'D', 'B'] && $hg['bleachers'] === [], 'back in at the back of the line');
check(HGJoinLine($hg, 'B') === null, 'joining the line from the line is refused');

echo "Draw_BothStay_ThenStepDown:\n";
$hg = playMatch(hg4(), 'm1', 0);
check($hg['hill'] === ['A', 'B'] && $hg['drawn'] === true, 'after a drawn match both stay on the hill');
check(HGNextPair($hg) === ['A', 'B'], 'if nobody steps down, the same two play again');
$hg = HGStepDown($hg, 'B');
check($hg !== null && $hg['hill'] === ['A'] && $hg['line'] === ['C', 'D', 'B'], 'B stepped down to the back');
check(HGNextPair($hg) === ['A', 'C'], 'A plays the next in line');

echo "StepDown_RefusedAfterWin:\n";
$won = playMatch(hg4(), 'm1', 1);
check(HGStepDown($won, 'A') === null, 'step down only exists after a drawn match');
check(HGStepDown(playMatch(hg4(), 'm1', 0), 'C') === null, 'someone in line cannot step down');

echo "HillHolderLeaves_NextTwoPlay:\n";
$hg = HGRemoveMember(playMatch(hg4(), 'm1', 1), 'A');
check($hg['hill'] === [] && HGNextPair($hg) === ['C', 'D'], 'the holder left: the next two in line play');
$hg = HGToBleachers(playMatch(hg4(), 'm1', 1), 'A');
check($hg !== null && HGNextPair($hg) === ['C', 'D'] && $hg['bleachers'] === ['A'], 'the holder stepped out to the bleachers');

echo "Abort_StayerKeepsHill_GoneToBack:\n";
$hg = HGAbortMatch(HGBeginMatch(hg4(), 'm1'), 'B');
check($hg['hill'] === ['A'] && $hg['line'] === ['C', 'D', 'B'] && $hg['live'] === null, 'A stays, B (gone) at the back');
$hg = HGAbortMatch(HGRemoveMember(HGBeginMatch(hg4(), 'm1'), 'B'), 'B');
check($hg['hill'] === ['A'] && $hg['line'] === ['C', 'D'], 'B already left the room: not re-added');

echo "Abort_UnknownGoneKey_NobodyLost:\n";
$hg = HGAbortMatch(HGBeginMatch(hg4(), 'm1'), '');
check($hg['hill'] === ['A'] && $hg['line'] === ['C', 'D', 'B'], 'no gone player named: A keeps the hill, B is not dropped from the room\'s line');

echo "Begin_RefusedWhenPairChanged:\n";
// Start claims the pair, builds the match outside the lock, then commits: if the pair moved meanwhile (a step down, a
// trip to the bleachers, a reorder), the commit must refuse rather than seat one pair and record another.
$hg = playMatch(hg4(), 'm1', 0);                       // drawn: A and B both stay
$claimed = HGNextPair($hg);                             // [A, B]
$moved = HGStepDown($hg, 'A');                          // A steps down while the match is being built
check(HGBeginMatch($moved, 'm2', $claimed) === null, 'the pair changed under Start: refused');
check(HGBeginMatch($hg, 'm2', $claimed) !== null, 'the same pair: committed');

echo "SendToBack_HostMovesHillOrBleachers:\n";
$hg = HGSendToBack(playMatch(hg4(), 'm1', 1), 'A');    // A holds the hill but walked away
check($hg !== null && $hg['hill'] === [] && end($hg['line']) === 'A', 'the hill-holder goes to the back of the line');
$hg = HGSendToBack(HGToBleachers(hg4(), 'D'), 'D');
check($hg !== null && $hg['bleachers'] === [] && end($hg['line']) === 'D', 'someone in the bleachers goes to the back of the line');
check(HGSendToBack(HGBeginMatch(hg4(), 'm1'), 'A') === null, 'not someone playing right now');

echo "Reorder_StaleSetRefused:\n";
$hg = hg4();
$re = HGReorderLine($hg, ['D', 'C', 'B', 'A']);
check($re !== null && $re['line'] === ['D', 'C', 'B', 'A'], 'the host reorders the line');
check(HGReorderLine($hg, ['D', 'C', 'B']) === null, 'a reorder missing someone is refused');
check(HGReorderLine($hg, ['D', 'C', 'B', 'A', 'E']) === null, 'a reorder with someone extra is refused');
check(HGReorderLine($hg, ['D', 'C', 'B', 'B']) === null, 'a reorder with a duplicate is refused');
check($hg['line'] === ['A', 'B', 'C', 'D'], 'a refused reorder leaves the line as it was');

echo "ToBleachers_RefusedWhileLive:\n";
check(HGToBleachers(HGBeginMatch(hg4(), 'm1'), 'A') === null, 'a live player cannot go to the bleachers');

echo "RecordGame_Bo3TwoOneByGames:\n";
$s = [1 => seat('A', 'deckA', 'LA', 'BA', 100), 2 => seat('B', 'deckB', 'LB', 'BB', 101)];
$hg = hg4();
foreach (['g1' => 1, 'g2' => 2, 'g3' => 1] as $gn => $w) $hg = HGRecordGame($hg, $gn, $s, $w);
$rows = HGTonightRows($hg);
check(count($rows) === 2, 'two rows: ' . count($rows));
check([$rows[0]['name'], $rows[0]['w'], $rows[0]['l'], $rows[0]['d']] === ['userA', 2, 1, 0], 'A: 2-1-0');
check([$rows[1]['name'], $rows[1]['w'], $rows[1]['l'], $rows[1]['d']] === ['userB', 1, 2, 0], 'B: 1-2-0');
check($rows[0]['leader'] === 'LA' && $rows[0]['base'] === 'BA', 'the row shows the leader and base');

echo "RecordGame_DeckChangeMakesSecondRow:\n";
$hg = HGRecordGame($hg, 'g4', [1 => seat('A', 'deckA2', 'LC', 'BC', 100), 2 => seat('B', 'deckB', 'LB', 'BB', 101)], 2);
$rows = HGTonightRows($hg);
check(count($rows) === 3 && $rows[2]['name'] === 'userA' && $rows[2]['l'] === 1, 'A on a new deck is a new row');
check($rows[1]['w'] === 2, 'B on the same deck stays one row');

echo "RecordGame_DrawAddsDToBoth:\n";
$hg = HGRecordGame(hg4(), 'g1', $s, 0);
$rows = HGTonightRows($hg);
check($rows[0]['d'] === 1 && $rows[1]['d'] === 1 && $rows[0]['w'] + $rows[0]['l'] === 0, 'a drawn game is a D for both');

echo "RecordGame_IsIdempotent:\n";
$hg = HGRecordGame(HGRecordGame(hg4(), 'g1', $s, 1), 'g1', $s, 1);
check(HGTonightRows($hg)[0]['w'] === 1, 'the same game recorded twice counts once');

echo "TonightRows_DeckNameOnlyWhenAmbiguous:\n";
$hg = HGRecordGame(hg4(), 'g1', [1 => seat('A', 'deckA', 'L1', 'B1', 100, 'Aggro'), 2 => seat('B', 'deckB', 'L1', 'B1', 101, 'Mid')], 1);
$hg = HGRecordGame($hg, 'g2', [1 => seat('A', 'deckA2', 'L1', 'B1', 100, 'Control'), 2 => seat('B', 'deckB', 'L1', 'B1', 101, 'Mid')], 1);
$rows = HGTonightRows($hg);
$a = array_values(array_filter($rows, fn($r) => $r['name'] === 'userA'));
$b = array_values(array_filter($rows, fn($r) => $r['name'] === 'userB'));
check(($a[0]['deckName'] ?? null) === 'Aggro' && ($a[1]['deckName'] ?? null) === 'Control', 'A has two lists on one leader+base: names shown');
check(!array_key_exists('deckName', $b[0]), 'B has one row with that leader+base: no deck name');

echo "TonightRows_OrderIsFirstAppearance:\n";
$hg = HGRecordGame(hg4(), 'g1', $s, 2);
foreach (['g2', 'g3'] as $gn) $hg = HGRecordGame($hg, $gn, $s, 2);
$rows = HGTonightRows($hg);
check($rows[0]['name'] === 'userA' && $rows[1]['w'] === 3, 'rows keep first-appearance order, not wins');

echo "Guest_RowNameFixed:\n";
$hg = HGAddMember(HGNew(), 'g', null);
$hg = HGRecordGame($hg, 'g1', [1 => seat('g', 'deckG'), 2 => seat('A', 'deckA', 'L', 'B', 100)], 1);
check(HGTonightRows($hg)[0]['name'] === 'Guest 1', 'a guest row carries the guest\'s fixed name');

echo "WatchKey_Rotates:\n";
$k1 = HGRotateWatchKey(HGNew())['watchKey'];
$k2 = HGRotateWatchKey(['watchKey' => $k1] + HGNew())['watchKey'];
check(strlen($k1) === 32 && $k1 !== $k2, 'a fresh 32-hex watch key every rotation');

echo "Cap:\n";
check(HG_MAX_MEMBERS === 16, 'sixteen people, everyone counted');

echo $FAILS === 0 ? "PASS\n" : "$FAILS FAILED\n";
exit($FAILS === 0 ? 0 : 1);
