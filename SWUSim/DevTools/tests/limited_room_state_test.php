<?php
// Limited room state — the pure rules behind a Sealed room: seats, settings, start, build, submit / ready, the build
// clock, the start claim, the match result, and the true-majority kick vote.
// docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.1, §3.3, §3.4, §3.6.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php SWUSim/DevTools/tests/limited_room_state_test.php
chdir(dirname(__DIR__, 3));
require_once './SWUSim/Limited/LimitedRoomState.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

$now = 1_800_000_000;
$deck = fn(string $tag) => ['leader' => "L_$tag", 'base' => "B_$tag", 'main' => array_fill(0, 30, "C_$tag")];
$two = fn() => LTAddSeat(LTAddSeat(LTNew('sealed'), 1, 'alice'), 2, 'bob');
$started = fn() => LTStart($two(), [1 => ['SOR'], 2 => ['HMW']], [1 => ['SOR_001', 'SOR_002'], 2 => ['HMW_001']], ['SOR', 'HMW'], 77, $now);

echo "NoCardDictionary:\n";
$check(!function_exists('CardTitle') && !function_exists('GetAllCardIds'), 'the room state never loads the card dictionary (the poll requires it)');

echo "NewRoomIsSetup:\n";
$n = LTNew('sealed');
$check($n['phase'] === 'setup' && $n['mode'] === 'sealed', 'a new room is in setup');
$check($n['settings'] === SWULimitedDefaultSettings('sealed') && $n['settingsErrors'] === [], 'it starts on the Sealed defaults');

echo "SeatsCapAtTwo:\n";
$check(LTSeatCap($n) === 2, 'Sealed seats 2');
$check(LTAddSeat($two(), 3, 'carol') === null, 'a third seat is refused');
$check(LTAddSeat(LTAddSeat($n, 1, 'alice'), 1, 'alice') === null, 'the same seat twice is refused');
$check(LTAddSeat(LTDropSeat($started(), 2, 'left'), 3, 'carol') === null, 'no seat is added once the room has started');

echo "StartNeedsTwoSeatsAndValidSettings:\n";
$check(LTStart(LTAddSeat($n, 1, 'alice'), [], [], [], 1, $now) === null, 'one seat cannot start');
$bad = LTSetSettings($two(), ['packMode' => 'chaos'] + SWULimitedDefaultSettings('sealed'), ['Chaos Sealed needs at least 2 different sets.']);
$check($bad !== null && $bad['settingsErrors'] === ['Chaos Sealed needs at least 2 different sets.'], 'invalid settings are stored with their errors');
$check(LTStart($bad, [], [], [], 1, $now) === null, 'invalid settings cannot start');
$s = $started();
$check($s !== null && $s['phase'] === 'building' && $s['buildDeadline'] === $now + 1800, 'two seats + valid settings → building, 30-minute deadline');
$check(LTSetSettings($s, SWULimitedDefaultSettings('sealed'), []) === null, 'settings are locked once started');

echo "StartStoresWhatItWasGiven:\n";
$check($s['pools'] === [1 => ['SOR_001', 'SOR_002'], 2 => ['HMW_001']] && $s['lineups'] === [1 => ['SOR'], 2 => ['HMW']]
    && $s['setsInPlay'] === ['SOR', 'HMW'] && $s['seed'] === 77, 'pools, lineups, sets in play and seed are stored as given');
$poolsBefore = serialize($s['pools']);
$after = LTExpireBuild(LTSubmitDeck($s, 1, $deck('a')), $now + 5000);
$check(serialize($after['pools']) === $poolsBefore, 'submitting and the build clock never touch the pools');

echo "SubmitThenReady:\n";
$check(LTReady($s, 1) === null, 'Ready before Submit is refused');
$sub = LTSubmitDeck($s, 1, $deck('a'));
$check($sub['seats'][1]['submitted'] === true && $sub['seats'][1]['ready'] === false, 'Submit saves the deck, not ready');
$rdy = LTReady($sub, 1);
$check($rdy !== null && $rdy['seats'][1]['ready'] === true, 'Ready after Submit');
$check(LTSubmitDeck($rdy, 1, $deck('b')) === null, 'no edits after Ready');
$re = LTSubmitDeck($sub, 1, $deck('b'));
$check($re['seats'][1]['deck'] === $deck('b') && $re['seats'][1]['ready'] === false, 'a second Submit replaces the deck');
$check(LTSubmitDeck($s, 9, $deck('a')) === null, 'an unknown seat cannot submit');
$check(LTSubmitDeck($two(), 1, $deck('a')) === null, 'no submit before the room starts');

echo "BuildExpiryReadiesSubmitted:\n";
$e = LTSubmitDeck($s, 1, $deck('a'));
$check(LTExpireBuild($e, $now + 1799) === $e, 'nothing changes before the deadline');
$x = LTExpireBuild($e, $now + 1800);
$check($x['seats'][1]['ready'] === true && $x['seats'][2]['ready'] === false, 'at the deadline the submitted seat is readied, the other is not');
$check(LTExpireBuild($x, $now + 1900) === $x, 'expiry is idempotent');
$check(!LTReadyToStart($x), 'a seat with no deck keeps the match from starting');

echo "StartClaimIsExclusive:\n";
$both = LTReady(LTSubmitDeck(LTReady(LTSubmitDeck($s, 1, $deck('a')), 1), 2, $deck('b')), 2);
$check(LTReadyToStart($both), 'both Ready → ready to start');
$claimed = LTClaimStart($both);
$check($claimed !== null && !LTReadyToStart($claimed), 'once claimed, a second tick does not start again');
$check(LTClaimStart($claimed) === null, 'the claim cannot be taken twice');
$check(LTReleaseStart($claimed)['starting'] === false && LTReadyToStart(LTReleaseStart($claimed)), 'releasing a failed claim allows a retry');
$playing = LTBeginMatch($claimed, 'm1', 'g1', [1 => 1, 2 => 2]);
$check($playing['phase'] === 'playing' && $playing['starting'] === false && $playing['match']['gameName'] === 'g1', 'the match begins');
$check(LTBeginMatch($both, 'm1', 'g1', [1 => 1, 2 => 2]) === null, 'a match cannot begin without the claim');

echo "StaleStartClaimIsFreed:\n";
// Final review #2: a start that crashed mid-way (a fatal, a killed worker) must not hold the room forever — as High
// Ground's HG_START_CLAIM_SECONDS, a claim older than LT_START_CLAIM_SECONDS is free again.
$c = LTClaimStart($both, $now);
$check($c !== null && $c['starting'] === $now, 'the claim records when it was taken');
$check(!LTReadyToStart($c, $now + 59) && LTClaimStart($c, $now + 59) === null, 'a fresh claim blocks a second start');
$check(LTReadyToStart($c, $now + 61) && LTClaimStart($c, $now + 61) !== null, 'a claim older than a minute is free again');

echo "MatchCompleteEndsEvent:\n";
$mid = ['state' => 'in_progress', 'games' => [['gameName' => 'g1', 'winner' => 1], ['gameName' => 'g2', 'winner' => null]], 'wins' => ['1' => 1]];
$p2 = LTApplyMatch($playing, $mid);
$check($p2['phase'] === 'playing' && $p2['match']['gameName'] === 'g2', 'a running series moves the room to its latest game');
// Seat 2 of the match is pid 2; seat 1 is pid 1 — swap them to prove the mapping is used.
$swapped = LTBeginMatch($claimed, 'm1', 'g1', [1 => 2, 2 => 1]);
$done = ['state' => 'complete', 'winner' => 1, 'games' => [['gameName' => 'g1', 'winner' => 1], ['gameName' => 'g2', 'winner' => 2], ['gameName' => 'g3', 'winner' => 1]],
         'wins' => ['1' => 2, '2' => 1]];
$d = LTApplyMatch($swapped, $done);
$check($d['phase'] === 'done' && $d['result']['winner'] === 2 && $d['result']['score'] === [2 => 2, 1 => 1] && $d['result']['reason'] === 'played',
    'a complete match ends the event: the winner is the room seat behind match seat 1, score 2–1');

echo "KickThreshold:\n";
$check(array_map('LTKickVotesNeeded', [0, 1, 2, 3, 6, 7]) === [0, 1, 2, 2, 4, 4], 'true majority: 0,1,2,3,6,7 voters → 0,1,2,2,4,4');

echo "KickableRules:\n";
$seen = [1 => $now, 2 => $now];
$check(LTKickableSeats($s, $seen, $now + 10) === [], 'connected and before the deadline: nobody is kickable');
$check(LTKickableSeats($s, [1 => $now + 31, 2 => $now], $now + 31) === [2], 'silent for 31 s → kickable');
$check(LTKickableSeats($s, [1 => $now + 30, 2 => $now], $now + 30) === [], 'silent for exactly 30 s → not yet');
$late = LTSubmitDeck($s, 1, $deck('a'));
$fresh = [1 => $now + 1800, 2 => $now + 1800];
$check(LTKickableSeats($late, $fresh, $now + 1800) === [2], 'after the deadline, the seat with no submitted deck is kickable');
$waited = LTKickWait($late, 1, 2, $now + 1800);
$check($waited !== null && LTKickableSeats($waited, [1 => $now + 1819, 2 => $now + 1819], $now + 1819) === [], 'Wait holds the vote for 20 s');
$check(LTKickableSeats($waited, [1 => $now + 1820, 2 => $now + 1820], $now + 1820) === [2], '…and it reopens at +20');
$check(LTKickableSeats($two(), [1 => $now, 2 => $now - 999], $now) === [], 'nobody is kick-voted during setup (the host removes seats there)');

echo "SealedKickEndsEvent:\n";
$check(LTKickVoters($late, 2) === [1], 'the voters are the other live seats');
$check(LTKickVote($late, 1, 2, $seen, $now + 10) === null, 'a seat that is not kickable cannot be voted out');
$check(LTKickVote($late, 2, 2, $fresh, $now + 1800) === null, 'nobody votes on themselves');
$k = LTKickVote($late, 1, 2, $fresh, $now + 1800);
$check($k !== null && $k['phase'] === 'done' && $k['result']['winner'] === 1 && $k['result']['reason'] === 'kicked' && $k['seats'][2]['dropped'] === true,
    'one vote of one carries: the event ends, the voter wins');

echo "DropInSetupRemovesSeat:\n";
$dr = LTDropSeat($two(), 2, 'left');
$check(count($dr['seats']) === 1 && $dr['phase'] === 'setup', 'leaving during setup frees the seat');
$lb = LTDropSeat($s, 1, 'left');
$check($lb['phase'] === 'done' && $lb['result']['winner'] === 2 && $lb['result']['reason'] === 'left', 'leaving during the build ends a Sealed event');
$lp = LTDropSeat($playing, 2, 'left');
$check($lp['phase'] === 'playing' && $lp['seats'][2]['dropped'] === true, 'during the match, a drop only marks the seat (the match decides)');

// ── Draft (Plan 3) ─────────────────────────────────────────────────────────────────────────────────────────────────
$draftRoom = function (int $n) { $lt = LTNew('draft'); for ($p = 1; $p <= $n; $p++) $lt = LTAddSeat($lt, $p, "p$p"); return $lt; };

echo "DraftSeatsCapAtEight:\n";
$d8 = $draftRoom(8);
$check(LTSeatCap($d8) === 8 && $d8 !== null && count($d8['seats']) === 8 && LTAddSeat($d8, 9, 'p9') === null, 'a Draft room seats 8, not 9');

echo "DraftCanStartFromThree:\n";
$check(!LTCanStart($draftRoom(2)) && LTCanStart($draftRoom(3)) && LTCanStart($draftRoom(8)), 'Draft can start with 3–8 seats, not 2');
$check(LTCanStart($two()) && !LTCanStart(LTAddSeat(LTNew('sealed'), 1, 'a')), 'Sealed still needs exactly 2');
$badD = LTSetSettings($draftRoom(3), SWULimitedDefaultSettings('draft'), ['x']);
$check(!LTCanStart($badD), 'settings errors block the start');

echo "DraftDropKeepsTheEventGoing:\n";
$b3 = $draftRoom(3); $b3['phase'] = 'building'; $b3['buildDeadline'] = $now + 1800;
$g1 = LTDropSeat($b3, 3, 'kicked');
$check($g1['phase'] === 'building' && !empty($g1['seats'][3]['dropped']), 'one drop of three: the event goes on');
$g2 = LTDropSeat($g1, 2, 'left');
$check($g2['phase'] === 'done' && $g2['result']['winner'] === 1 && $g2['result']['reason'] === 'left', 'down to one live seat: done, that seat wins');
$dr3 = $draftRoom(3); $dr3['phase'] = 'drafting';
$check(LTDropSeat($dr3, 2, 'kicked')['phase'] === 'drafting', 'a drop while drafting keeps the draft running');

echo "DraftBuiltWhenAllReady:\n";
$bb = $b3;
$bb = LTReady(LTSubmitDeck($bb, 1, $deck('a')), 1);
$bb = LTReady(LTSubmitDeck($bb, 2, $deck('b')), 2);
$check(LTMarkBuilt($bb) === null, 'one live seat unready: not built');
$bb = LTReady(LTSubmitDeck($bb, 3, $deck('c')), 3);
$check((LTMarkBuilt($bb)['phase'] ?? '') === 'built', 'every live seat Ready: built');
$bd = LTDropSeat(LTReady(LTSubmitDeck(LTReady(LTSubmitDeck($b3, 1, $deck('a')), 1), 2, $deck('b')), 2), 3, 'kicked');
$check((LTMarkBuilt($bd)['phase'] ?? '') === 'built', 'a dropped seat is not waited for');
$check(!LTReadyToStart($bb), 'a Draft room never starts a match in this plan');
$check(LTMarkBuilt($both) === null, 'Sealed never becomes built');

echo "KickTargetsMustBeLiveSeats:\n";
$check(LTKickWait($late, 1, 999, $now) === null, 'Wait on a seat that does not exist is refused');
$check(LTKickVote($late, 1, 999, $fresh, $now + 1800) === null, 'a vote on a seat that does not exist is refused');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
