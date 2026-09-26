<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_match_invite_resolve.php
//
// A private lobby lives in APCu for LOBBY_TTL_SECONDS (900), and that TTL is only refreshed by the
// waiting room's own poll (PollLobbyUpdates' heartbeat). The moment a match starts, every client
// navigates into the game and NOTHING polls the lobby again — so the lobby AND its `invite:<code>`
// index age out roughly 15 minutes into a match that can easily run an hour. From then on the invite
// link resolves to nothing and the waiting room reports "That invite is invalid or has expired."
// (reported live 2026-09-25, game 2 of a private Bo3).
//
// The fix is a DURABLE invite index written beside the match, which outlives every game in it. These
// checks pin the resolver, and in particular that it follows the match to its CURRENT game: the
// lobby's own `gameName` is only ever set to GAME 1, so resolving through the lobby would send a
// game-2 refresh into a finished game 1 — worse than the error it replaces.
header('Content-Type: text/plain');
include_once __DIR__ . '/../../Core/Match/MatchFlow.php';

$SIM = 'MatchInviteTestSim';

// Clean slate so reruns are deterministic (this sim exists only for this test).
$simRoot = __DIR__ . '/../../' . $SIM;
if (is_dir($simRoot)) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($simRoot, FilesystemIterator::SKIP_DOTS),
                                         RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($rii as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
}

$spawnCount = 0;
MatchRegisterHooks($SIM, [
    'resolveLobbyDecks' => function ($lobby) {
        return [1 => ['originalDeck' => ['A1'], 'authKey' => 'hostkey1'],
                2 => ['originalDeck' => ['B1'], 'authKey' => 'guestkey2']];
    },
    'validateDeck' => function ($d, $f) { return true; },
    'setupGame'    => function ($lobby, $opts) use (&$spawnCount, $SIM) {
        $spawnCount++;
        $g = 'MITgame' . $spawnCount;
        @mkdir(__DIR__ . '/../../' . $SIM . '/Games/' . $g, 0777, true);
        return $g;
    },
]);

$checks = [];

// ── A private Bo3 lobby with an invite code ─────────────────────────────────────────────────────
$code  = 'a1b2c3d4e5f6a7b8c9d0e1f2';   // shape of bin2hex(random_bytes(12))
$lobby = new stdClass();
$lobby->format     = 'premier';
$lobby->queueType  = 'bo3';
$lobby->isPrivate  = true;
$lobby->inviteCode = $code;
$lobby->players    = [];

$matchId = MatchCreateFromLobby($SIM, $lobby);
$checks['match created'] = is_string($matchId) && $matchId !== '';

$m = MatchRead($SIM, $matchId);
$checks['invite code stamped on the match'] = ($m['inviteCode'] ?? null) === $code;

// ── The durable index resolves the code to the match, with no APCu involved ─────────────────────
$found = MatchFindByInviteCode($SIM, $code);
$checks['code resolves to the match']  = is_array($found) && ($found['matchId'] ?? '') === $matchId;
$checks['unknown code resolves to null'] = MatchFindByInviteCode($SIM, 'ffffffffffffffffffffffff') === null;
$checks['empty code resolves to null']   = MatchFindByInviteCode($SIM, '') === null;
// Path traversal must not escape the invites dir.
$checks['dirty code cannot traverse']    = MatchFindByInviteCode($SIM, '../../Match') === null;

// ── It follows the match to the CURRENT game — the whole point ──────────────────────────────────
$game1 = MatchCurrentGameName($found);
$checks['current game is game 1'] = $game1 === 'MITgame1';

// Seat 2 loses game 1; the match spawns game 2 (Bo3, no sideboard path needed for this check).
MatchRecordGameResult($SIM, $matchId, 'MITgame1', 1, 2);
$game2 = MatchSpawnNextGame($SIM, $matchId, 2, 'MITgame1');
$checks['game 2 spawned'] = $game2 === 'MITgame2';

$found2 = MatchFindByInviteCode($SIM, $code);
$checks['current game FOLLOWS to game 2'] = MatchCurrentGameName($found2) === 'MITgame2';
$checks['match still in progress']        = ($found2['state'] ?? '') !== 'complete';

// ── Seat resolution from a presented authKey (what the waiting room posts / the cookie carries) ──
$checks['host key resolves to seat 1']  = MatchSeatForAuthKey($found2, 'hostkey1')  === 1;
$checks['guest key resolves to seat 2'] = MatchSeatForAuthKey($found2, 'guestkey2') === 2;
$checks['unknown key resolves to 0']    = MatchSeatForAuthKey($found2, 'nope')      === 0;
$checks['empty key resolves to 0']      = MatchSeatForAuthKey($found2, '')          === 0;

// ── A finished match still resolves, but reports itself complete ────────────────────────────────
// The caller uses this to say "that match has ended" rather than dumping someone into a dead game.
MatchRecordGameResult($SIM, $matchId, 'MITgame2', 1, 2);   // seat 1 takes it 2-0
$done = MatchFindByInviteCode($SIM, $code);
$checks['finished match still resolves'] = is_array($done);
$checks['finished match reports complete'] = ($done['state'] ?? '') === 'complete' || MatchIsOver($done) === true;

// ── A lobby with NO invite code writes no index entry ───────────────────────────────────────────
$plain = new stdClass();
$plain->format    = 'premier';
$plain->queueType = 'bo1';
$plain->isPrivate = false;
$plain->players   = [];
$plainId = MatchCreateFromLobby($SIM, $plain);
$plainMatch = MatchRead($SIM, $plainId);
$checks['public lobby stamps no invite code'] = !isset($plainMatch['inviteCode']);
$invitesDir = MatchesDir($SIM) . '/invites';
$entries = is_dir($invitesDir) ? array_values(array_diff(scandir($invitesDir), ['.', '..'])) : [];
$checks['exactly one index entry exists'] = count($entries) === 1;

// ── The reaper takes the index entry with the match ─────────────────────────────────────────────
// MatchReapStale globs 'M*' so it never touches the invites dir itself — but without this the index
// files outlive every match they point at and accumulate forever on a server that already keeps
// thousands of game folders. A lookup through an orphan still behaves (MatchRead fails, the caller
// falls through to the expired copy); the leak is the problem, not the read.
$reaped = MatchReapStale($SIM, 0);   // maxAge 0 → the match completed above is stale immediately
$checks['reaper removed the finished match'] = !is_array(MatchRead($SIM, $matchId));
$checks['reaper removed its index entry']    = MatchFindByInviteCode($SIM, $code) === null;
$leftovers = is_dir($invitesDir) ? array_values(array_diff(scandir($invitesDir), ['.', '..'])) : [];
$checks['no orphan index files left']        = count($leftovers) === 0;
// The in-progress public match must survive — the reaper is not a "delete everything" button.
$checks['reaper spared the in-progress match'] = is_array(MatchRead($SIM, $plainId));

// Leave nothing behind.
if (is_dir($simRoot)) {
    $rii2 = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($simRoot, FilesystemIterator::SKIP_DOTS),
                                          RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($rii2 as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
    @rmdir($simRoot);
}

$fail = 0; foreach ($checks as $k => $v) { echo ($v ? 'PASS ' : 'FAIL ') . $k . "\n"; if (!$v) $fail++; }
echo ($fail === 0 ? "ALL GREEN\n" : "$fail FAILED\n");
