<?php
// The persistent 1v1 room (SWUSim/Rooms.php): its disk snapshot, the alive rule, and the lobby⇄match sync that reopens
// the waiting room when its match ends. Pure sections over hand-built lobbies; time is passed in, never read.
// Spec: docs/superpowers/specs/2026-10-30-persistent-lobby-rematch-design.md
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/room_lifecycle_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir(__DIR__ . '/../../..');
require_once './SWUSim/Rooms.php';

$FAILS = 0;
function check($cond, $msg) { global $FAILS; if ($cond) { echo "  ok: $msg\n"; } else { echo "  BAD: $msg\n"; $FAILS++; } }

$T = 1800000000;
// A started 1v1 room: host seat 1 (an account), guest seat 2, both ready, game 1 under way.
function room(int $now): object {
    $l = new stdClass();
    $l->id = 'rltest' . bin2hex(random_bytes(4));
    $l->rootName = 'SWUSim'; $l->format = 'premier'; $l->queueType = 'bo1'; $l->isPrivate = true;
    $l->maxPlayers = 2; $l->numPlayers = 2; $l->hostPlayerID = 1; $l->state = 'started'; $l->ready = true;
    $l->gameName = 'g1'; $l->inviteCode = bin2hex(random_bytes(12)); $l->roomMatchId = 'M900001';
    $a = new Player(1, 'deckA', '', 7); $a->setUsername('claudebot1'); $a->setReady(true); $a->touch($now - 3000);
    $b = new Player(2, 'deckB', '', null); $b->setReady(true); $b->touch($now - 3000);
    $l->players = [$a, $b];
    return $l;
}
function matchOf(string $state, array $games): array { return ['matchId' => 'M900001', 'state' => $state, 'games' => $games]; }
$k = fn(object $l, int $i) => $l->players[$i]->getAuthKey();

echo "sync:\n";
$l = room($T);
$changed = SWURoomSyncWithMatch($l, matchOf('in_progress', [['gameName' => 'g1', 'winner' => 1], ['gameName' => 'g2', 'winner' => null]]), $T);
check($l->state === 'started' && $l->gameName === 'g2', 'a running match keeps the room started, on the CURRENT game: ' . $l->gameName);

$l = room($T);
$changed = SWURoomSyncWithMatch($l, matchOf('complete', [['gameName' => 'g1', 'winner' => 1]]), $T);
check($changed === true, 'a complete match reports a change');
check($l->state === 'open' && !isset($l->gameName), 'a complete match reopens the room (open, no game)');
check(!$l->players[0]->getReady() && !$l->players[1]->getReady(), 'every seat is not Ready after the reopen');
check(SWURoomSeatState($l, $k($l, 0)) === 'endscreen' && SWURoomSeatState($l, $k($l, 1)) === 'endscreen', 'every seat starts on the end screen');
check($l->players[0]->getLastSeen() === $T && $l->players[1]->getLastSeen() === $T, 'every seat is touched at the reopen (nobody looks away)');
check(empty($l->ready), 'the lobby itself is not ready');

$before = serialize($l);
$again = SWURoomSyncWithMatch($l, matchOf('complete', [['gameName' => 'g1', 'winner' => 1]]), $T + 50);
check($again === false && serialize($l) === $before, 'syncing an already-open room with the same complete match changes nothing');

SWURoomSyncWithMatch($l, matchOf('sideboarding', [['gameName' => 'g1', 'winner' => 1]]), $T + 60);
check($l->state === 'started' && $l->gameName === 'g1', 'a converted match (no longer complete) puts the room back to started');

echo "seat states:\n";
$l = room($T);
SWURoomSyncWithMatch($l, matchOf('complete', [['gameName' => 'g1', 'winner' => 1]]), $T);
$p = SWURoomMarkInLobby($l, $k($l, 0), $T + 5);
check($p !== null && SWURoomSeatState($l, $k($l, 0)) === 'lobby', 'marking a seat in the lobby moves only that seat');
check(SWURoomSeatState($l, $k($l, 1)) === 'endscreen', 'the other seat stays on the end screen');
check(SWURoomMarkInLobby($l, 'not-a-key', $T) === null, 'an unknown key is not seated');
check(SWURoomEndScreenNames($l) === ['Guest P2'], 'end-screen names: the guest reads "Guest P2" (as its seat tile does): ' . json_encode(SWURoomEndScreenNames($l)));
$l2 = room($T);
SWURoomSyncWithMatch($l2, matchOf('complete', [['gameName' => 'g1', 'winner' => 1]]), $T);
check(SWURoomEndScreenNames($l2) === ['claudebot1', 'Guest P2'], 'an account reads its username: ' . json_encode(SWURoomEndScreenNames($l2)));
// A Quick Rematch (or a Convert) puts a match back in progress: both players are on its board, not on an end screen.
SWURoomSyncWithMatch($l2, matchOf('in_progress', [['gameName' => 'gq', 'winner' => null]]), $T + 20);
check(SWURoomEndScreenNames($l2) === [] && SWURoomSeatState($l2, $k($l2, 0)) === 'lobby', 'once a match runs again nobody reads as on the end screen: ' . json_encode(SWURoomEndScreenNames($l2)));

echo "quiet release:\n";
$mk = function (int $quietFor) use ($T, $k) {
    $l = room($T);
    SWURoomSyncWithMatch($l, matchOf('complete', [['gameName' => 'g1', 'winner' => 1]]), $T);
    SWURoomMarkInLobby($l, $k($l, 0), $T + $quietFor);
    $l->players[1]->touch($T);
    return $l;
};
$l = $mk(119); $keyB = $k($l, 1);
check(SWURoomReleaseQuietSeats($l, $T + 119) === false && $l->numPlayers === 2, 'an end-screen seat quiet for 119s is kept');
$l = $mk(120); $keyB = $k($l, 1);
check(SWURoomReleaseQuietSeats($l, $T + 120) === true, 'an end-screen seat quiet for 120s is released');
check($l->numPlayers === 1 && count($l->players) === 1 && SWURoomFindPlayerByAuthKey($l, $keyB) === null, 'the released seat is gone (1 player left)');
// Both seats on the end screen and quiet for 300s — but a Convert to Bo3 put a match back in progress, so the room is
// started again and every seat is held (call 11).
$l = room($T);
SWURoomSyncWithMatch($l, matchOf('complete', [['gameName' => 'g1', 'winner' => 1]]), $T);
SWURoomSyncWithMatch($l, matchOf('sideboarding', [['gameName' => 'g1', 'winner' => 1]]), $T);
check(SWURoomReleaseQuietSeats($l, $T + 300) === false && $l->numPlayers === 2, 'nothing is released while the room is started (a match is running)');
$l = $mk(300);
$l->players[0]->touch($T - 5000);   // the in-lobby seat is quiet too, but it is not on the end screen
SWURoomReleaseQuietSeats($l, $T + 300);
check(SWURoomFindPlayerByAuthKey($l, $k($l, 0)) !== null, 'a quiet IN-LOBBY seat is not released by this rule (the room has its own away rules)');

echo "release seat:\n";
$l = room($T);
SWURoomSyncWithMatch($l, matchOf('complete', [['gameName' => 'g1', 'winner' => 1]]), $T);
$hostKey = $k($l, 0);
check(SWURoomReleaseSeat($l, $hostKey) === true && intval($l->hostPlayerID) === 2, 'releasing the host hands host to the remaining seat');
check(!isset($l->roomSeatState[$hostKey]), 'the released seat leaves no seat state behind');
check(SWURoomReleaseSeat($l, $hostKey) === false, 'releasing it again is a no-op');

echo "alive:\n";
check(SWURoomIsAlive(matchOf('in_progress', []), $T - 99999, $T) === true, 'a running match keeps the room alive however old the file');
check(SWURoomIsAlive(matchOf('complete', []), $T - 899, $T) === true, 'complete + touched 899s ago: alive');
check(SWURoomIsAlive(matchOf('complete', []), $T - 900, $T) === false, 'complete + touched 900s ago: ended');
check(SWURoomIsAlive(null, $T - 10, $T) === true, 'no match yet + recently touched: alive');

echo "origin:\n";
check(SWUMatchOrigin(['roomId' => 'abc', 'isPrivate' => true]) === 'room', 'a match with a room id came from a room');
check(SWUMatchOrigin(['isPrivate' => false]) === 'public', 'a non-private match without a room came from the public queue');
check(SWUMatchOrigin(['isPrivate' => true]) === 'legacy', 'a private match without a room predates rooms (legacy)');

echo "alive after a long match:\n";
// A match that ran for an hour: the snapshot was written at its start and nothing touched it since. The match record's
// own updatedAt (stamped when it completed) is what says the room was in use.
check(SWURoomIsAlive(matchOf('complete', []) + ['updatedAt' => $T - 10], $T - 3600, $T) === true, 'a match that ended 10s ago keeps its room alive whatever the snapshot age');
check(SWURoomIsAlive(matchOf('complete', []) + ['updatedAt' => $T - 900], $T - 3600, $T) === false, 'ended 900s ago and untouched since: the room has ended');

echo "disk:\n";
$l = room($T);
register_shutdown_function(function () use ($l) {
    foreach ([SWURoomPath($l->id), SWURoomInvitePath($l->inviteCode), SWURoomPath('rlcorrupt')] as $f) if ($f !== '' && is_file($f)) unlink($f);
});
check(SWURoomSave($l) === true && is_file(SWURoomPath($l->id)), 'the snapshot is written beside the matches');
$rec = SWURoomLoad($l->id);
check(is_array($rec) && $rec['matchId'] === 'M900001', 'it reads back with its match id');
check(is_array($rec) && $rec['lobby']->players[0] instanceof Player && $rec['lobby']->players[1]->getAuthKey() === $k($l, 1), 'players come back as Player objects with their keys');
check(SWURoomIdForInvite($l->inviteCode) === $l->id, 'the invite index names the room');
// Atomic replace: a reader holding the OLD file open still reads the whole old snapshot, never a half-written new one
// (a write in place truncates under it — a concurrent load then sees garbage and the room reads as ended).
$fh = fopen(SWURoomPath($l->id), 'rb');
$l->queueType = 'bo3';
SWURoomSave($l);
$old = @unserialize(stream_get_contents($fh), ['allowed_classes' => [Player::class, 'stdClass']]);
fclose($fh);
check(is_array($old) && ($old['lobby']->queueType ?? '') === 'bo1' && (SWURoomLoad($l->id)['lobby']->queueType ?? '') === 'bo3',
      'a save replaces the snapshot atomically (an open reader keeps the complete old copy; the next load sees the new one)');
check(glob(dirname(SWURoomPath($l->id)) . '/*.tmp*') === [], 'and leaves no temp file behind');
file_put_contents(SWURoomPath('rlcorrupt'), 'not serialized');
check(SWURoomLoad('rlcorrupt') === null, 'a corrupt snapshot loads as null');
check(SWURoomLoad('nosuchroom') === null, 'a missing snapshot loads as null');
check(SWURoomPath('../etc/passwd') === SWURoomPath('etcpasswd'), 'the room id is sanitised (no traversal)');

echo "reap:\n";
// Rooms that ended long ago leave their snapshot and invite index behind; the reaper removes them a day after the room
// ended (the day keeps "This room has ended." for a late invite click). Real files, so real time.
$now = time();
$DAY = 86400;
$made = [];   // every file this section creates, removed at the end whatever the reaper did
$mkRoom = function (string $tag, string $matchId, int $age) use (&$made, $now) {
    $l = room($now);
    $l->id = 'rlreap' . $tag . bin2hex(random_bytes(3));
    $l->roomMatchId = $matchId;
    SWURoomSave($l);
    touch(SWURoomPath($l->id), $now - $age);
    $made[] = SWURoomPath($l->id); $made[] = SWURoomInvitePath($l->inviteCode);
    return $l;
};
$mkMatch = function (string $state, int $updatedAgo) use (&$made, $now) {
    $id = 'M99rl' . bin2hex(random_bytes(3));
    @mkdir(dirname(MatchPath('SWUSim', $id)), 0777, true);
    file_put_contents(MatchPath('SWUSim', $id), json_encode(['matchId' => $id, 'state' => $state, 'updatedAt' => $now - $updatedAgo, 'games' => []]));
    $made[] = MatchPath('SWUSim', $id);
    return $id;
};
$gone = fn(object $l) => !is_file(SWURoomPath($l->id)) && !is_file(SWURoomInvitePath($l->inviteCode));
$kept = fn(object $l) => is_file(SWURoomPath($l->id)) && is_file(SWURoomInvitePath($l->inviteCode));
$oldEnded  = $mkRoom('a', '', LOBBY_TTL_SECONDS + $DAY + 10);
$newEnded  = $mkRoom('b', '', LOBBY_TTL_SECONDS + $DAY - 100);
$running   = $mkRoom('c', $mkMatch('in_progress', 3 * $DAY), 3 * $DAY);
$abandoned = $mkRoom('d', $mkMatch('in_progress', 31 * $DAY), 31 * $DAY);
$justDone  = $mkRoom('e', $mkMatch('complete', 10), 3 * $DAY);
$orphanOld = SWURoomInvitePath('rlorphanold' . bin2hex(random_bytes(3)));
$orphanNew = SWURoomInvitePath('rlorphannew' . bin2hex(random_bytes(3)));
foreach ([[$orphanOld, 2 * $DAY], [$orphanNew, 60]] as [$f, $age]) { file_put_contents($f, json_encode(['roomId' => 'rlnosuchroom'])); touch($f, $now - $age); $made[] = $f; }
$tmp = SWURoomPath('rlreaptmp') . '.tmp999abc';
file_put_contents($tmp, 'half'); touch($tmp, $now - 7200); $made[] = $tmp;
clearstatcache();
$n = SWURoomReap($now);
clearstatcache();
check($gone($oldEnded), 'a room that ended over a day ago is removed, snapshot and invite index');
check($kept($newEnded), 'a room that ended less than a day ago is kept (a late invite still reads "This room has ended.")');
check($kept($running), 'a room whose match is still running is kept, however old the snapshot');
check($gone($abandoned), 'a room whose "running" match has not moved in 31 days is removed (abandoned)');
check($kept($justDone), 'a room whose match ended 10s ago is kept, however old the snapshot');
check(!is_file($orphanOld) && is_file($orphanNew), 'an invite index with no room is removed after a day, not before');
check(!is_file($tmp), 'a leftover temp file from a failed save is removed');
check($n >= 4, 'the reaper reports what it removed: ' . $n);
foreach ($made as $f) if ($f !== '' && is_file($f)) @unlink($f);
foreach ($made as $f) if (str_ends_with($f, '/Match.json')) @rmdir(dirname($f));

echo $FAILS === 0 ? "PASS\n" : "$FAILS FAILED\n";
