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
        // deckOk and ready are ONE state in the live flow — loading a legal deck auto-readies the seat,
        // and SWURoomStartBlockers gates on both. Setting only deckOk builds a lobby that cannot exist,
        // and every blocker array then carries a phantom "not ready" entry beside the one under test.
        // The substring assertions below survive that; a future count- or equality-based one would not.
        $p->setDeckOk(true);
        $p->setReady(true);
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

// ── Unready is its OWN gate, and it needs its own fixture now that mkLobby readies everyone ──
// Before, every fixture was built unready, so the ready blocker was exercised only by accident —
// riding along inside blocker arrays that were asserted with substring searches for a DIFFERENT
// blocker. That is not coverage: it could never fail for its own reason. These two sections make it
// deliberate, and they are the reason readying in mkLobby is safe rather than a deletion of a test.
// Singular vs plural is asserted because the count is interpolated, so an off-by-one reads as a
// wording bug and would otherwise never be caught.
$oneUnready = mkLobby(4);
foreach (['red','blue','red','blue'] as $i => $t) SWURoomAssignTeam($oneUnready, $oneUnready->players[$i], $t);
$oneUnready->players[2]->setReady(false);          // deck still legal — this is a deliberate Unready
$u1 = SWURoomStartBlockers($oneUnready);
check($u1 === ['1 player is not ready.'],
      'an otherwise-startable room is blocked by ONE deliberate Unready, and by nothing else');

$twoUnready = mkLobby(4);
foreach (['red','blue','red','blue'] as $i => $t) SWURoomAssignTeam($twoUnready, $twoUnready->players[$i], $t);
$twoUnready->players[1]->setReady(false);
$twoUnready->players[3]->setReady(false);
check(SWURoomStartBlockers($twoUnready) === ['2 players are not ready.'],
      'two Unready seats report the PLURAL wording and a single combined blocker');

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


// ── Identity survives the start-time seat renumber (the 2026-08-26 playtest bug) ──────────────────
// StartRoom compacts seats to 1..N and, in a team room, sorts by the PICKED seat first. That CHANGES
// a player's playerID whenever join order differs from seat order — the normal case. The lobby poll
// used to authenticate on (playerID AND authKey) and echo the caller's own playerID back, so every
// moved player entered the game as a seat they no longer held and was rejected. Only the host, which
// keeps seat 1 and array position 0, got in.
//
// This models the renumber exactly as StartRoom does it, then asserts that the AUTHKEY still resolves
// to the seat the player now holds — which is what SWURoomFindPlayerByAuthKey guarantees.
function startRoomRenumber($lobby) {           // mirror of StartRoom.php's handoff
    $lobby->players = array_values($lobby->players);
    if (SWURoomIsTeamLobby($lobby)) {
        usort($lobby->players, fn($a, $b) => intval($a->getSeat() ?? 99) <=> intval($b->getSeat() ?? 99));
    }
    $seat = 1;
    foreach ($lobby->players as $p) { $p->setPlayerID($seat); ++$seat; }
    return $lobby;
}

$lr = mkLobby(4);
$keys = [];
foreach ($lr->players as $p) $keys[$p->getPlayerID()] = $p->getAuthKey();   // authKey by JOIN order

// A 3-cycle: join order 1,2,3,4 -> seats 1,3,4,2. Three of the four players move.
SWURoomAssignTeam($lr, $lr->players[0], 'red');    // join 1 -> seat 1
SWURoomAssignTeam($lr, $lr->players[1], 'red');    // join 2 -> seat 3
SWURoomAssignTeam($lr, $lr->players[2], 'blue');   // join 3 -> seat 2
SWURoomAssignTeam($lr, $lr->players[3], 'blue');   // join 4 -> seat 4
check($lr->players[1]->getSeat() === 3, 'precondition: the second red picker holds seat 3');
check($lr->players[2]->getSeat() === 2, 'precondition: the first blue picker holds seat 2');

startRoomRenumber($lr);

// Table order is now seat order: join1(1), join3(2), join2(3), join4(4) — joins 2 and 3 SWAPPED.
check($lr->players[0]->getAuthKey() === $keys[1], 'seat 1 is still the player who joined first (the host)');
check($lr->players[1]->getAuthKey() === $keys[3], 'seat 2 is now the player who joined THIRD');
check($lr->players[2]->getAuthKey() === $keys[2], 'seat 3 is now the player who joined SECOND');

// ⚠ THE ASSERTION THAT WOULD HAVE CAUGHT THE BUG: every player's authKey resolves to their CURRENT
// seat, and for the moved players that seat is NOT the playerID their browser captured at join.
foreach ([1 => 1, 2 => 3, 3 => 2, 4 => 4] as $joinOrder => $expectedSeat) {
    $found = SWURoomFindPlayerByAuthKey($lr, $keys[$joinOrder]);
    check($found !== null, "authKey of join-order {$joinOrder} still resolves after the renumber");
    check($found->getPlayerID() === $expectedSeat,
          "join-order {$joinOrder} now holds seat {$expectedSeat} (its captured playerID is stale)");
}
check(SWURoomFindPlayerByAuthKey($lr, 'not-a-real-key') === null, 'an unknown authKey resolves to nobody');
check(SWURoomFindPlayerByAuthKey($lr, '') === null, 'an empty authKey resolves to nobody');

// Twin Suns sets no seat, so the sort is a no-op and playerIDs are untouched — Premier/Twin Suns stay
// byte-identical, which is why this never surfaced before Team Suns.
$lt = mkLobby(4, 'twinsuns');
$tkeys = [];
foreach ($lt->players as $p) $tkeys[$p->getPlayerID()] = $p->getAuthKey();
startRoomRenumber($lt);
foreach ([1, 2, 3, 4] as $i) {
    check($lt->players[$i - 1]->getAuthKey() === $tkeys[$i], "twin suns: join-order {$i} keeps seat {$i}");
}


echo "PASS\n";