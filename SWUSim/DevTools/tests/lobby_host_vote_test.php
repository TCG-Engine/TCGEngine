<?php
// Kick Host vote rules, as pure functions over a $lobby stdClass — no APCu, no HTTP. Same contract
// as teamsuns_rooms_test.php: every rule here is a pure function of ($lobby, $now), so the whole
// timer/vote/carry sequence is testable without sleeping or hitting a real endpoint.
//
// The feature: a forming room's host can go afk and hold the room hostage — nobody else can start
// it and nobody else can remove the host. Once a room has sat at 3+ human seats, with the SAME host
// and roster, for SWU_HOSTVOTE_ARM_AFTER seconds, a Kick Host vote opens. Threshold is the in-game
// kick-vote rule (KickVoteThreshold, non-unanimous): 2 of 2 at three seats, 2 of 3 at four.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';
require_once $root . '/APIs/Lobbies/Classes/Player.php';
require_once $root . '/APIs/Lobbies/Classes/HostVote.php';

function mkLobby($n, $hostPlayerID = 1) {
    $l = new stdClass();
    $l->rootName = 'SWUSim';
    $l->format = 'twinsuns';
    $l->hostPlayerID = $hostPlayerID;
    $l->players = [];
    for ($i = 1; $i <= $n; $i++) {
        $l->players[] = new Player($i, 'deck' . $i, '', 100 + $i);
    }
    $l->numPlayers = $n;
    return $l;
}
function addBot($lobby, $playerID, $profile = 'heuristic') {
    $p = new Player($playerID, '', '', null);
    $p->setBotProfile($profile);
    $lobby->players[] = $p;
    $lobby->numPlayers = count($lobby->players);
    return $p;
}

// ── Signature: reflects human seats + host, ignores bots ────────────────────────────────────────
$l = mkLobby(3);
$sigA = SWUHostVoteSignature($l);
$sigB = SWUHostVoteSignature(mkLobby(3));
check($sigA === $sigB, 'two freshly-built 3-seat lobbies with the same ids share a signature');

$l4 = mkLobby(4);
check(SWUHostVoteSignature($l4) !== $sigA, 'a 4th human seat changes the signature');

$lHost2 = mkLobby(3, 2);
check(SWUHostVoteSignature($lHost2) !== $sigA, 'a different host changes the signature');

$lBot = mkLobby(3);
addBot($lBot, 4);
check(SWUHostVoteSignature($lBot) === $sigA, 'adding a BOT does not change the signature');

// ── Arming: stamps once, re-arms only on a real roster change ───────────────────────────────────
$l = mkLobby(3);
check(($l->hostVoteArmedAt ?? 0) === 0, 'precondition: never armed');
check(SWUHostVoteArm($l, 1000) === true, 'first arm call reports a change');
check($l->hostVoteArmedAt === 1000, 'first arm call stamps now');

check(SWUHostVoteArm($l, 1050) === false, 'a second call with the SAME roster reports no change');
check($l->hostVoteArmedAt === 1000, 'the timestamp does not move on an unchanged roster');

array_splice($l->players, 0, 0, [new Player(4, 'deck4', '', 104)]);  // a 4th human joins
$l->numPlayers = count($l->players);
check(SWUHostVoteArm($l, 1050) === true, 'a roster change (4th joiner) re-arms');
check($l->hostVoteArmedAt === 1050, 'the timestamp moves to the re-arm time');

// ── Window: needs 3+ human seats, the arm delay elapsed, and no game started yet ─────────────────
$l = mkLobby(3);
check(SWUHostVoteWindowOpen($l, 1000) === false, 'never armed: window is closed');

SWUHostVoteArm($l, 1000);
check(SWUHostVoteWindowOpen($l, 1000) === false, 'armed but 0s elapsed: still closed');
check(SWUHostVoteWindowOpen($l, 1119) === false, '119s elapsed: still closed');
check(SWUHostVoteWindowOpen($l, 1120) === true, '120s elapsed: window is open');
check(SWUHostVoteWindowOpen($l, 5000) === true, 'stays open well past the deadline');

$l2 = mkLobby(2);
SWUHostVoteArm($l2, 1000);
check(SWUHostVoteWindowOpen($l2, 5000) === false, 'a 2-player room never opens the window, no matter how long it waits');

$lStarted = mkLobby(3);
SWUHostVoteArm($lStarted, 1000);
$lStarted->gameName = 'SWU_1234';
check(SWUHostVoteWindowOpen($lStarted, 5000) === false, 'a room that already has a game never opens the window');

// ── Voter seats: every human seat except the host; bots never vote ──────────────────────────────
$l = mkLobby(4, 1);
addBot($l, 5);
check(SWUHostVoteVoterSeats($l) === [2, 3, 4], 'voters are every human seat but the host, and no bot');

// ── Needed: the exact 2/2 and 2/3 the owner specified ────────────────────────────────────────────
$l3 = mkLobby(3, 1);
check(SWUHostVoteNeeded($l3) === 2, '3-seat room (2 voters): needs 2 — unanimous');
$l4 = mkLobby(4, 1);
check(SWUHostVoteNeeded($l4) === 2, '4-seat room (3 voters): needs 2 — majority, not unanimous');

// ── Recording a Yes vote ─────────────────────────────────────────────────────────────────────────
$l = mkLobby(3, 1);
SWUHostVoteArm($l, 1000);
check(SWUHostVoteRecordYes($l, 2, 1000) === false, 'a vote before the window opens is refused');
check(SWUHostVoteYesIDs($l) === [], 'the refused vote recorded nothing');

check(SWUHostVoteRecordYes($l, 1, 1120) === false, 'the HOST cannot vote to kick themselves');
check(SWUHostVoteRecordYes($l, 99, 1120) === false, 'an unseated playerID cannot vote');

check(SWUHostVoteRecordYes($l, 2, 1120) === true, 'seat 2 votes yes once the window is open');
check(SWUHostVoteYesIDs($l) === [2], 'the yes is recorded');
check(SWUHostVoteRecordYes($l, 2, 1130) === false, 'the same seat voting again is a no-op');
check(SWUHostVoteYesIDs($l) === [2], 'no duplicate vote was added');

// ── Tally + carry: 3-seat room needs BOTH voters, 4-seat needs 2 of 3 ────────────────────────────
$l3 = mkLobby(3, 1);
SWUHostVoteArm($l3, 1000);
SWUHostVoteRecordYes($l3, 2, 1120);
$t = SWUHostVoteTally($l3);
check($t['yes'] === [2] && $t['needed'] === 2 && $t['carried'] === false, '3-seat room: one of two Yes votes does NOT carry');
SWUHostVoteRecordYes($l3, 3, 1120);
$t = SWUHostVoteTally($l3);
check($t['carried'] === true, '3-seat room: BOTH Yes votes carries (unanimous 2/2)');

$l4 = mkLobby(4, 1);
SWUHostVoteArm($l4, 1000);
SWUHostVoteRecordYes($l4, 2, 1120);
check(SWUHostVoteTally($l4)['carried'] === false, '4-seat room: one of three Yes votes does NOT carry');
SWUHostVoteRecordYes($l4, 3, 1120);
$t4 = SWUHostVoteTally($l4);
check($t4['carried'] === true, '4-seat room: two of three Yes votes carries (majority, not unanimous)');
check(count($t4['yes']) === 2 && !in_array(4, $t4['yes'], true), 'the non-voting third seat is not counted as Yes');

// ── A stale Yes from a seat that has since left does not carry a tally ───────────────────────────
$l = mkLobby(4, 1);
SWUHostVoteArm($l, 1000);
SWUHostVoteRecordYes($l, 2, 1120);
SWUHostVoteRecordYes($l, 3, 1120);
check(SWUHostVoteTally($l)['carried'] === true, 'precondition: two Yes votes carry at 4 seats');
array_splice($l->players, 1, 1);   // seat 2 (the array is 0-indexed; players[0]=seat1 host,[1]=seat2) leaves
$l->numPlayers = count($l->players);
$after = SWUHostVoteTally($l);
check(!in_array(2, $after['yes'], true), 'the departed seat 2 no longer counts toward Yes');
check($after['carried'] === false, 'losing one Yes voter drops the tally back below threshold');

// ── A vote is scoped to the CURRENT host — a stale tally does not carry onto a new host ─────────
$l = mkLobby(3, 1);
SWUHostVoteArm($l, 1000);
SWUHostVoteRecordYes($l, 2, 1120);
SWUHostVoteRecordYes($l, 3, 1120);
check(SWUHostVoteTally($l)['carried'] === true, 'precondition: unanimous Yes carries against host 1');
$l->hostPlayerID = 2;   // host changed by some other path (e.g. SWUMigrateHostIfAway)
check(SWUHostVoteYesIDs($l) === [], 'the old votes read as empty against the NEW host');
check(SWUHostVoteTally($l)['carried'] === false, 'the stale tally does not carry over onto the new host');

// ── State read: canVote / youVoted per viewer ────────────────────────────────────────────────────
$l = mkLobby(3, 1);
SWUHostVoteArm($l, 1000);
$closedState = SWUHostVoteState($l, 1050, 2);
check($closedState['open'] === false, 'state reports the window as closed before the delay elapses');
check($closedState['canVote'] === false, 'nobody can vote while the window is closed');

$openState = SWUHostVoteState($l, 1120, 2);
check($openState['open'] === true, 'state reports the window as open');
check($openState['needed'] === 2, 'state carries the needed count');
check($openState['yesCount'] === 0, 'state carries the live Yes count');
check($openState['canVote'] === true, 'a legal non-host voter with no vote yet CAN vote');
check($openState['youVoted'] === false, 'that voter has not voted yet');

SWUHostVoteRecordYes($l, 2, 1120);
$votedState = SWUHostVoteState($l, 1120, 2);
check($votedState['canVote'] === false, 'a voter who already voted cannot vote again');
check($votedState['youVoted'] === true, 'that voter is shown as having voted');
check($votedState['yesCount'] === 1, 'the Yes count reflects the recorded vote');

$hostState = SWUHostVoteState($l, 1120, 1);
check($hostState['canVote'] === false, 'the host itself is never offered a vote');

$strangerState = SWUHostVoteState($l, 1120, 0);
check($strangerState['canVote'] === false, 'a viewer with no seat (playerID 0) cannot vote');

echo "PASS\n";
