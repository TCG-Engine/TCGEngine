<?php
// If the host leaves, nothing reassigns hostPlayerID — and StartRoom requires
// $playerID === $lobby->hostPlayerID, so the lobby becomes PERMANENTLY UNSTARTABLE for everyone
// still sitting in it.
//
// The popup made this rare: leaving meant closing the popup, and you were probably abandoning
// anyway. A persistent page where people come and go, and where only Leave releases a seat, makes
// it routine.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';
require_once $root . '/APIs/Lobbies/Classes/Player.php';
require_once $root . '/APIs/Lobbies/Classes/TeamRooms.php';

function mk(array $ids): object {
    $l = new stdClass();
    $l->players     = [];
    $l->hostPlayerID = $ids[0];
    $l->numPlayers  = count($ids);
    foreach ($ids as $i) $l->players[] = new Player($i, 'deck');
    return $l;
}

$l = mk([1, 2, 3]);
SWUMigrateHostIfNeeded($l);
check($l->hostPlayerID === 1, 'host still seated -> unchanged');

// Host (1) left: seat 2 is the lowest remaining and inherits.
$l = mk([1, 2, 3]);
array_shift($l->players); $l->numPlayers = 2;
SWUMigrateHostIfNeeded($l);
check($l->hostPlayerID === 2, 'host left -> lowest remaining seat inherits');

// Lowest-NUMBERED, not lowest-JOINED: array order must not decide it. Team Suns reorders players,
// so "the first entry" is not a stable notion.
$l = mk([1, 4, 2]);
array_shift($l->players); $l->numPlayers = 2;
SWUMigrateHostIfNeeded($l);
check($l->hostPlayerID === 2, 'migration picks the lowest playerID, not the first array entry');

// A non-host leaving must not move the host.
$l = mk([1, 2, 3]);
array_splice($l->players, 1, 1); $l->numPlayers = 2;
SWUMigrateHostIfNeeded($l);
check($l->hostPlayerID === 1, 'a non-host leaving does not move the host');

// Empty lobby: nothing to migrate to, and no crash. LeaveQueue apcu_deletes this case anyway.
$l = mk([1]); $l->players = []; $l->numPlayers = 0;
SWUMigrateHostIfNeeded($l);
check($l->hostPlayerID === 1, 'empty lobby leaves hostPlayerID untouched, no crash');

// A legacy lobby with no hostPlayerID at all still resolves to a real seat rather than staying 0.
$l = mk([2, 3]); unset($l->hostPlayerID);
SWUMigrateHostIfNeeded($l);
check(($l->hostPlayerID ?? 0) === 2, 'a lobby with no hostPlayerID adopts the lowest seat');

// ── The start gate now includes Ready ─────────────────────────────────────────────────────────────
// Loading a legal deck auto-readies, so this blocks only when somebody deliberately pressed Unready.
require_once $root . '/AppCore/SWU/Formats.php';
$g = mk([1, 2]);
$g->format = 'premier';
$g->rootName = 'SWUSim';
foreach ($g->players as $pl) { $pl->setDeckOk(true); $pl->setReady(true); }
check(SWURoomStartBlockers($g) === [], 'two ready seats with legal decks can start');

$g->players[1]->setReady(false);
$bl = SWURoomStartBlockers($g);
check(in_array('1 player is not ready.', $bl, true), 'one un-ready seat blocks the start: ' . implode('; ', $bl));

$g->players[0]->setReady(false);
check(in_array('2 players are not ready.', SWURoomStartBlockers($g), true), 'the blocker counts un-ready seats');

// ── Seat-requirement wording ─────────────────────────────────────────────────────────────────────
// "at least" ONLY when the format accepts a range of seats. Twin Suns is 3-4, so 3 is a floor;
// Premier and Team Suns have fixed seat counts and state the exact number, because "at least 2"
// would imply a 3-player Premier game exists. The live count is deliberately absent — the status box
// shows it beside the people icon, so repeating it here said the same thing twice.
function _wordingFor(string $fmt): string {
    $l = new stdClass(); $l->format = $fmt; $l->rootName = 'SWUSim'; $l->players = []; $l->numPlayers = 0;
    $b = SWURoomStartBlockers($l);
    return $b[0] ?? '';
}
check(_wordingFor('premier')  === 'Need 2 players to start.',          'premier states an exact 2');
check(_wordingFor('eternal')  === 'Need 2 players to start.',          'eternal states an exact 2');
check(_wordingFor('padawan')  === 'Need 2 players to start.',          'padawan states an exact 2');
check(_wordingFor('teamsuns') === 'Need 4 players to start.',          'teamsuns states an exact 4');
check(_wordingFor('twinsuns') === 'Need at least 3 players to start.', 'twinsuns says AT LEAST 3 (seats 3-4)');
check(strpos(_wordingFor('premier'), 'currently') === false,           'the live count is not repeated in the blocker');

// ── Presence reaping ─────────────────────────────────────────────────────────────────────────────
// The poll is the heartbeat. A seat that stops polling has closed its browser (or lost the network)
// and is reaped, so it stops holding a slot and stops blocking the host's Start.
// ⚠ There is deliberately NO unload beacon: a refresh fires unload, so a beacon would release the
// seat and destroy the survive-a-refresh property the whole page exists for.
$now = 1000000;
$r = mk([1, 2, 3]);
foreach ($r->players as $pl) $pl->touch($now);
check(SWUReapAbsentSeats($r, $now + 5, 10) === 0, 'seats polling recently are kept');
check(count($r->players) === 3,                   'nothing was removed');

$r->players[1]->touch($now - 60);   // seat 2 went away a minute ago
check(SWUReapAbsentSeats($r, $now, 10) === 1, 'a silent seat is reaped');
check(count($r->players) === 2,               'the lobby shrank');
check($r->numPlayers === 2,                   'numPlayers follows the reap');
check($r->players[0]->getPlayerID() === 1 && $r->players[1]->getPlayerID() === 3, 'the right seat went');

// Exactly at the timeout is still present — the reap is strictly greater-than, so a seat is never
// dropped on the very tick its heartbeat is due.
$edge = mk([1]); $edge->players[0]->touch($now - 10);
check(SWUReapAbsentSeats($edge, $now, 10) === 0, 'a seat exactly at the timeout survives');
$edge->players[0]->touch($now - 11);
check(SWUReapAbsentSeats($edge, $now, 10) === 1, 'one second past the timeout is reaped');

// A seat that has NEVER polled has just joined. Reaping it instantly would evict people the moment
// they sit down, which is far worse than an empty seat lingering for ten seconds.
$fresh = mk([1, 2]);
check($fresh->players[0]->getLastSeen() === 0, 'a brand-new seat has no heartbeat yet');
check(SWUReapAbsentSeats($fresh, $now, 10) === 0, 'a seat that has never polled is NOT reaped');

// Reaping the host must hand the room to someone who is still in it.
$hostGone = mk([1, 2]);
foreach ($hostGone->players as $pl) $pl->touch($now);
$hostGone->players[0]->touch($now - 60);
check(SWUReapAbsentSeats($hostGone, $now, 10) === 1, 'the absent host is reaped');
SWUMigrateHostIfNeeded($hostGone);
check($hostGone->hostPlayerID === 2, 'the room is handed to the remaining seat');

echo "PASS\n";
