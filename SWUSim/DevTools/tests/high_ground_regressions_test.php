<?php
// High Ground — bugs the 16-account stress run found (high_ground_stress_test.php), pinned without races or HTTP:
//   1. A member who leaves in the middle of their first hill match got a Tonight row named "Player": the row is made when the
//      result is recorded, after they had left, so their name was gone.
//   2. Each request saved ITS copy of the room after the lock was released, so when the last two people left at once the
//      second-to-last could write the room back after the last one ended it — a ghost room that came back from disk.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 SWUSim/DevTools/tests/high_ground_regressions_test.php
error_reporting(E_ALL & ~E_DEPRECATED);
chdir(__DIR__ . '/../../..');
require_once './APIs/Lobbies/Classes/Player.php';
require_once './APIs/Lobbies/Classes/LobbyStore.php';
require_once './SWUSim/Rooms.php';
require_once './SWUSim/HighGroundState.php';

$FAILS = 0;
function check($cond, $msg) { global $FAILS; if ($cond) { echo "  ok: $msg\n"; } else { echo "  BAD: $msg\n"; $FAILS++; } }

echo "HGR_WalkOutKeepsTonightName:\n";
$hg = HGAddMember(HGAddMember(HGNew(), 'kA', 'alice', 1), 'kB', 'bob', 2);
$hg = HGBeginMatch($hg, 'm1');
$hg = HGRemoveMember($hg, 'kB');   // bob walks out mid-match, before any game of his is recorded
$seats = [1 => ['key' => 'kA', 'userId' => 1, 'deckLink' => 'dA'], 2 => ['key' => 'kB', 'userId' => 2, 'deckLink' => 'dB']];
$hg = HGEndMatch(HGRecordGame($hg, 'g1', $seats, 1), 1);
$names = array_column(HGTonightRows($hg), 'name');
check($names === ['alice', 'bob'], 'the walk-out\'s row keeps their name: ' . json_encode($names));
check(!HGIsMember($hg, 'kB') && $hg['hill'] === ['kA'] && $hg['line'] === [], 'and they are still out of the room');

echo "HGR_SaveLatestWritesTheRoomAsItIsNow:\n";
if (!function_exists('apcu_store') || !apcu_enabled()) { echo "  SKIP: run with -d apc.enable_cli=1\n"; exit(2); }
$id = 'hgreg' . bin2hex(random_bytes(6));
$room = new stdClass();
$room->id = $id; $room->rootName = 'SWUSim'; $room->players = []; $room->numPlayers = 0; $room->maxPlayers = 16;
$room->highGround = HGAddMember(HGNew(), 'kA', 'alice', 1);
apcu_store($id, $room, 600);
$stale = clone $room;                                      // what a slower request still holds
LobbyMutate($id, function ($l) { $l->highGround = HGAddMember($l->highGround, 'kB', 'bob', 2); return true; });
check(SWURoomSaveLatest($id) === true, 'a live room is saved');
$disk = SWURoomLoad($id);
check(array_values($disk['lobby']->highGround['names'] ?? []) === ['alice', 'bob'], 'the snapshot is the room as it is now, not a request\'s older copy');
apcu_delete($id);                                          // APCu lost the room (evicted / expired) — it has NOT ended
check(SWURoomSaveLatest($id) === false && SWURoomLoad($id) !== null, 'a room missing from APCu is not saved over, and its snapshot is kept');
apcu_store($id, SWURoomLoad($id)['lobby'], 600);            // (as SWURoomRehydrate would put it back)
SWURoomSaveLatest($id);
apcu_delete($id); SWURoomForget($id);                       // the last person left: the room has ended
check(SWURoomSaveLatest($id) === false && !is_file(SWURoomPath($id)), 'an ended room is never written back to disk');
check(SWURoomRehydrate($id) === false && apcu_fetch($id) === false, 'so nothing can bring it back');
apcu_store($id, $stale, 600);                               // a slower request still sees the room (it read it before the end)
check(SWURoomSaveLatest($id) === false && !is_file(SWURoomPath($id)), 'a room marked ended is not written, whatever a request still sees');
apcu_delete($id); apcu_delete(_SWURoomEndedKey($id));
SWURoomForget($id);

echo $FAILS === 0 ? "PASS\n" : "$FAILS FAILED\n";
exit($FAILS === 0 ? 0 : 1);
