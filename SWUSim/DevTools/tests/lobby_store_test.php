<?php
// LobbyMutate is the only place allowed to read-modify-write a lobby. Before it existed, fourteen
// apcu_store sites across eight endpoints each fetched the whole lobby, modified it, and wrote it
// back with no lock — so a writer holding a snapshot across a multi-second swudb fetch silently
// reverted every heartbeat, ready flag, team pick and join committed in that window.
//
// Needs APCu, so it runs in the WEB SAPI (apc.enable_cli=0):
//   http://localhost:3400/TCGEngine/SWUSim/DevTools/tests/lobby_store_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
header('Content-Type: text/plain');
require_once __DIR__ . '/../../../APIs/Lobbies/Classes/LobbyStore.php';

$FAILS = 0;
function check($cond, $msg) { global $FAILS; if ($cond) { echo "  ok: $msg\n"; } else { echo "  BAD: $msg\n"; $FAILS++; } }

check(function_exists('apcu_store'), 'APCu is available in this SAPI');

$id = 'lobbystoretest_' . getmypid();
apcu_delete($id); apcu_delete('lobbylock:' . $id);

check(LobbyMutate($id, fn($l) => true) === null, 'a missing lobby returns null');

$seed = new stdClass(); $seed->id = $id; $seed->n = 0;
apcu_store($id, $seed, 60);

$out = LobbyMutate($id, function ($l) { $l->n = 5; return true; });
check($out !== null && $out->n === 5, 'the callback sees the lobby and its change is returned');
check(apcu_fetch($id)->n === 5,       'the change was stored');

LobbyMutate($id, function ($l) { $l->n = 99; return false; });
check(apcu_fetch($id)->n === 5, 'returning false abandons the write');

// The lock is released on the happy path, so a second mutation in the same request works.
$out = LobbyMutate($id, function ($l) { $l->n++; return true; });
check($out->n === 6, 'a second mutation acquires the lock again');

// ...and on the exception path, or one dropped request would wedge the room for its whole lock TTL.
try { LobbyMutate($id, function ($l) { throw new RuntimeException('boom'); }); } catch (RuntimeException $e) {}
check(apcu_fetch('lobbylock:' . $id) === false, 'the lock is released when the callback throws');
$out = LobbyMutate($id, function ($l) { $l->n = 7; return true; });
check($out !== null && $out->n === 7, 'the store still works after a thrown callback');

// A held lock makes a concurrent writer give up rather than clobber. The wait is bounded.
apcu_store('lobbylock:' . $id, 1, 60);
$t = microtime(true);
check(LobbyMutate($id, fn($l) => true) === null, 'a held lock returns null instead of writing');
$waited = microtime(true) - $t;
check($waited >= 1.5 && $waited < 4.0, "the wait is bounded (~2s, measured {$waited}s)");
apcu_delete('lobbylock:' . $id);

check(LobbyMutate($id, fn($l) => 'delete') !== null, 'delete returns the final lobby');
check(apcu_fetch($id) === false, 'delete removed the key');
check(apcu_fetch('lobbylock:' . $id) === false, 'delete released the lock');

check(LobbyMutate('', fn($l) => true) === null, 'an empty lobbyID is refused');

echo $FAILS === 0 ? "PASS\n" : "FAIL: $FAILS check(s)\n";
