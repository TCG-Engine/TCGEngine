<?php
// Team Suns room rules, as pure functions over a $lobby stdClass — no APCu, no HTTP.
// Seats are FIXED SLOTS (red 1,3 / blue 2,4), not array positions: LeaveQueue uses array_splice,
// which reindexes, so a positional seat would shuffle the whole table when one person drops.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';
require_once $root . '/APIs/Lobbies/Classes/Player.php';
require_once $root . '/APIs/Lobbies/Classes/TeamRooms.php';

function mkLobby($n, $format = 'teamsuns') {
    $l = new stdClass();
    $l->rootName = 'SWUSim';
    $l->format = $format;
    $l->hostPlayerID = 1;
    $l->players = [];
    for ($i = 1; $i <= $n; $i++) {
        $p = new Player($i, 'deck' . $i, '', 100 + $i);
        $p->setDeckOk(true);
        $l->players[] = $p;
    }
    $l->numPlayers = $n;
    return $l;
}

// ── Seat slots ──
check(SWURoomTeamSeatSlots('red')  === [1, 3], 'red holds seats 1 and 3');
check(SWURoomTeamSeatSlots('blue') === [2, 4], 'blue holds seats 2 and 4');
check(SWURoomTeamSeatSlots('green') === [],    'an unknown team has no slots');

// ── Assignment takes the LOWER free slot ──
$l = mkLobby(2);
check(SWURoomAssignTeam($l, $l->players[0], 'red'), 'first red pick succeeds');
check($l->players[0]->getSeat() === 1, 'first red picker takes seat 1');
check(SWURoomAssignTeam($l, $l->players[1], 'red'), 'second red pick succeeds');
check($l->players[1]->getSeat() === 3, 'second red picker takes seat 3 (lower free slot)');

// ── A full team rejects, changing nothing ──
$l3 = mkLobby(3);
SWURoomAssignTeam($l3, $l3->players[0], 'red');
SWURoomAssignTeam($l3, $l3->players[1], 'red');
check(SWURoomAssignTeam($l3, $l3->players[2], 'red') === false, 'a third red pick is rejected');
check($l3->players[2]->getTeam() === null, 'the rejected player keeps no team');
check($l3->players[2]->getSeat() === null, 'the rejected player keeps no seat');

// ── Switching teams RELEASES the old seat ──
$l4 = mkLobby(2);
SWURoomAssignTeam($l4, $l4->players[0], 'red');
check($l4->players[0]->getSeat() === 1, 'precondition: on red seat 1');
check(SWURoomAssignTeam($l4, $l4->players[0], 'blue'), 'switching to blue succeeds');
check($l4->players[0]->getSeat() === 2, 'switcher takes blue seat 2');
check(SWURoomAssignTeam($l4, $l4->players[1], 'red'), 'the vacated red slot is reusable');
check($l4->players[1]->getSeat() === 1, 'the vacated seat 1 is handed to the next red picker');

// ── A leave frees only that slot; NOBODY else moves ──
$l5 = mkLobby(2);
SWURoomAssignTeam($l5, $l5->players[0], 'red');   // seat 1
SWURoomAssignTeam($l5, $l5->players[1], 'red');   // seat 3
array_splice($l5->players, 0, 1);                 // exactly what LeaveQueue.php does
check(count($l5->players) === 1, 'precondition: one player left');
check($l5->players[0]->getSeat() === 3, 'the remaining player KEEPS seat 3 — no reshuffle');

// ── Auto-assign on join: only when exactly one team is full ──
$empty = mkLobby(0);
check(SWURoomAutoTeamOnJoin($empty) === null, '0/0 — the joiner picks');

$oneRed = mkLobby(1); SWURoomAssignTeam($oneRed, $oneRed->players[0], 'red');
check(SWURoomAutoTeamOnJoin($oneRed) === null, '1/0 — the joiner picks');

$split = mkLobby(2);
SWURoomAssignTeam($split, $split->players[0], 'red');
SWURoomAssignTeam($split, $split->players[1], 'blue');
check(SWURoomAutoTeamOnJoin($split) === null, '1/1 — the joiner still picks');

$redFull = mkLobby(2);
SWURoomAssignTeam($redFull, $redFull->players[0], 'red');
SWURoomAssignTeam($redFull, $redFull->players[1], 'red');
check(SWURoomAutoTeamOnJoin($redFull) === 'blue', '2/0 — red is full, so the joiner is forced blue');

$blueFull = mkLobby(3);
SWURoomAssignTeam($blueFull, $blueFull->players[0], 'blue');
SWURoomAssignTeam($blueFull, $blueFull->players[1], 'blue');
SWURoomAssignTeam($blueFull, $blueFull->players[2], 'red');
check(SWURoomAutoTeamOnJoin($blueFull) === 'red', '2/1 — the 4th joiner is forced onto red');

// A non-team format never auto-assigns.
$tw = mkLobby(2, 'twinsuns');
check(SWURoomAutoTeamOnJoin($tw) === null, 'twinsuns never auto-assigns a team');

// ── Start blockers ──
$short = mkLobby(3);
SWURoomAssignTeam($short, $short->players[0], 'red');
SWURoomAssignTeam($short, $short->players[1], 'blue');
SWURoomAssignTeam($short, $short->players[2], 'red');
$b = SWURoomStartBlockers($short);
check(count($b) > 0, 'a 3-player Team Suns room cannot start');
check(strpos(implode(' ', $b), '4 players') !== false, 'the blocker names the player requirement');

$unassigned = mkLobby(4);
SWURoomAssignTeam($unassigned, $unassigned->players[0], 'red');
SWURoomAssignTeam($unassigned, $unassigned->players[1], 'blue');
SWURoomAssignTeam($unassigned, $unassigned->players[2], 'red');
$b2 = SWURoomStartBlockers($unassigned);
check(count($b2) > 0, 'an unassigned player blocks start');

$badDeck = mkLobby(4);
foreach (['red','blue','red','blue'] as $i => $t) SWURoomAssignTeam($badDeck, $badDeck->players[$i], $t);
$badDeck->players[2]->setDeckOk(false);
$b3 = SWURoomStartBlockers($badDeck);
check(count($b3) > 0, 'an illegal deck blocks start');

$good = mkLobby(4);
foreach (['red','blue','red','blue'] as $i => $t) SWURoomAssignTeam($good, $good->players[$i], $t);
check(SWURoomStartBlockers($good) === [], 'a full, assigned, deck-legal 2/2 room can start');

// Leader conflict WITHIN a team blocks; the SAME leader on OPPOSING teams does not.
$sameTeam = SWURoomStartBlockers($good, [
    1 => ['SOR_010','JTL_006'],  // red
    2 => ['LAW_011','IBH_053'],  // blue
    3 => ['SOR_010','ASH_011'],  // red — collides with seat 1
    4 => ['SEC_009','TWI_017'],  // blue
]);
check(count($sameTeam) > 0, 'a leader shared WITHIN a team blocks start');
check(strpos(implode(' ', $sameTeam), 'SOR_010') !== false, 'the blocker names the shared leader');

$crossTeam = SWURoomStartBlockers($good, [
    1 => ['SOR_010','JTL_006'],  // red
    2 => ['SOR_010','IBH_053'],  // blue — same leader, OPPOSING team
    3 => ['LAW_011','ASH_011'],  // red
    4 => ['SEC_009','TWI_017'],  // blue
]);
check($crossTeam === [], 'the same leader on OPPOSING teams is fine');

// SWURoomLeaderSets reads the per-Player cache and keys by SEAT (not array position), so it feeds
// SWURoomStartBlockers directly.
$cached = mkLobby(4);
foreach (['red','blue','red','blue'] as $i => $t) SWURoomAssignTeam($cached, $cached->players[$i], $t);
$cached->players[0]->setLeaders(['SOR_010','JTL_006']);   // seat 1, red
$cached->players[1]->setLeaders(['LAW_011','IBH_053']);   // seat 2, blue
$cached->players[2]->setLeaders(['SOR_010','ASH_011']);   // seat 3, red — collides with seat 1
$cached->players[3]->setLeaders(['SEC_009','TWI_017']);   // seat 4, blue
$sets = SWURoomLeaderSets($cached);
check($sets[1] === ['SOR_010','JTL_006'], 'leader sets are keyed by SEAT, not array position');
check($sets[3] === ['SOR_010','ASH_011'], 'seat 3 leaders come from the third player');
$live = SWURoomStartBlockers($cached, $sets);
check(count($live) > 0, 'the LIVE path (cache -> leader sets -> blockers) catches the conflict');
check(strpos(implode(' ', $live), 'SOR_010') !== false, 'the live blocker names the shared leader');

// An unseated player contributes nothing and must not warn.
$partial = mkLobby(2);
SWURoomAssignTeam($partial, $partial->players[0], 'red');
$partial->players[0]->setLeaders(['SOR_010']);
check(SWURoomLeaderSets($partial) === [1 => ['SOR_010']], 'unseated players are skipped');

echo "PASS\n";
