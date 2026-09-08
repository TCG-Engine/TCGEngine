<?php
// Presence is a DISPLAY state, not a lifecycle state. A seat that stops polling is shown as Away and
// keeps its seat; only an explicit Leave or a host kick removes anyone. These are the pure rules —
// SWUSim/DevTools/tests/lobby_room_flow_test.php covers the endpoints that use them.
//
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
//     php SWUSim/DevTools/tests/lobby_presence_test.php
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';
require_once $root . '/APIs/Lobbies/Classes/Player.php';
require_once $root . '/APIs/Lobbies/Classes/TeamRooms.php';

function mkroom(array $ids): object {
    $l = new stdClass();
    $l->players      = [];
    $l->hostPlayerID = $ids[0];
    $l->numPlayers   = count($ids);
    foreach ($ids as $i) $l->players[] = new Player($i, 'deck');
    return $l;
}

$now = 1000000;

// ── The away threshold ───────────────────────────────────────────────────────────────────────────
// 90s is sized against the browser's ~60s background-timer floor, NOT the 1.5s poll interval. A
// hidden tab still beats once a minute, so Away means "actually gone" — the signal a host needs
// before deciding to kick.
check(SWU_LOBBY_AWAY_AFTER === 90, 'away threshold is 90s');
check(SWU_LOBBY_HOST_AWAY_AFTER === 300, 'host migration threshold is 300s');

$r = mkroom([1, 2, 3]);
foreach ($r->players as $p) $p->touch($now);
check(SWUSeatIsAway($r->players[0], $now + 5) === false, 'a seat polling 5s ago is present');
check(SWUSeatIsAway($r->players[0], $now + 90) === false, 'a seat exactly at the threshold is present');
check(SWUSeatIsAway($r->players[0], $now + 91) === true,  'one second past the threshold is away');

// A hidden tab throttled to one poll a minute must NEVER read as away — that is the whole point of 90.
check(SWUSeatIsAway($r->players[0], $now + 60) === false, 'a 60s-throttled hidden tab is still present');

// A seat that has never polled has just joined; treating it as away would flag people the instant
// they sit down.
$fresh = mkroom([1]);
check($fresh->players[0]->getLastSeen() === 0, 'a brand-new seat has no heartbeat yet');
check(SWUSeatIsAway($fresh->players[0], $now) === false, 'a seat that has never polled is NOT away');

check(SWUSeatIsAway(null, $now) === false, 'a non-Player entry is never away');

// ── Away NEVER removes anybody ───────────────────────────────────────────────────────────────────
$stay = mkroom([1, 2, 3]);
foreach ($stay->players as $p) $p->touch($now - 100000);
SWUMigrateHostIfAway($stay, $now);
check(count($stay->players) === 3, 'an away seat is not removed');
check($stay->numPlayers === 3,     'numPlayers is untouched by away');

// ── Host migration ───────────────────────────────────────────────────────────────────────────────
// Without auto-reaping, a host who shuts their laptop would brick the room: nobody can kick and
// nobody can start.
$h = mkroom([1, 2, 3]);
foreach ($h->players as $p) $p->touch($now);
check(SWUMigrateHostIfAway($h, $now) === false, 'a present host keeps the room');
check($h->hostPlayerID === 1,                   'hostPlayerID unchanged');

$h->players[0]->touch($now - 200);   // away by the 90s rule, but not yet by the 300s host rule
check(SWUMigrateHostIfAway($h, $now) === false, 'an away host keeps the room until 300s');
check($h->hostPlayerID === 1,                   'a coffee break does not cost you the room');

$h->players[0]->touch($now - 301);
check(SWUMigrateHostIfAway($h, $now) === true, 'a host away past 300s hands the room over');
check($h->hostPlayerID === 2,                  'the lowest present playerID inherits');

// Lowest-NUMBERED, not lowest-JOINED — team rooms reorder $lobby->players on every pick.
$order = mkroom([1, 4, 2]);
foreach ($order->players as $p) $p->touch($now);
$order->players[0]->touch($now - 400);
check(SWUMigrateHostIfAway($order, $now) === true, 'host away -> migration runs');
check($order->hostPlayerID === 2, 'migration picks the lowest playerID, not the first array entry');

// An away candidate must not inherit — handing the room to a second empty chair fixes nothing.
$allgone = mkroom([1, 2]);
$allgone->players[0]->touch($now - 400);
$allgone->players[1]->touch($now - 400);
check(SWUMigrateHostIfAway($allgone, $now) === false, 'no present candidate -> no migration');
check($allgone->hostPlayerID === 1,                   'hostPlayerID left alone');

$mixed = mkroom([1, 2, 3]);
$mixed->players[0]->touch($now - 400);   // host, away
$mixed->players[1]->touch($now - 400);   // seat 2, away
$mixed->players[2]->touch($now);         // seat 3, present
check(SWUMigrateHostIfAway($mixed, $now) === true, 'migration runs past an away candidate');
check($mixed->hostPlayerID === 3, 'the room goes to the lowest PRESENT seat, skipping away ones');

// ── Away must NEVER block Start ──────────────────────────────────────────────────────────────────
// The host reads Away and decides; a tab-visibility flicker must not take the Start button away from
// three people who are ready. Nothing here adds an away blocker — this pins that it stays that way.
$open = mkroom([1, 2, 3]);
$open->rootName = 'SWUSim';
$open->format   = 'twinsuns';
foreach ($open->players as $p) { $p->setDeckOk(true); $p->setReady(true); $p->touch($now - 100000); }
check(SWURoomStartBlockers($open) === [], 'a room of away-but-ready seats has NO start blockers');

// A host who is not in the array at all is SWUMigrateHostIfNeeded's job, not this one.
$vanished = mkroom([2, 3]);
$vanished->hostPlayerID = 1;
foreach ($vanished->players as $p) $p->touch($now);
check(SWUMigrateHostIfAway($vanished, $now) === false, 'an unseated host is not this function\'s job');
check($vanished->hostPlayerID === 1, 'hostPlayerID untouched by the away migration');

echo "PASS\n";
