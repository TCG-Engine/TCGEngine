<?php
// High Ground — watching live with the PRODUCTION spectator check ON (docs/superpowers/specs/2026-10-30-swusim-high-ground-
// design.md, "Watching"; recon blocker 2: dev skips this check, so it must be tested with it on). Members watch with the
// room's watch key, which every game of a hill match takes as its spectator key — game 2 and 3 of a Bo3 included. Someone
// who isn't in the room has no key; someone who left can't watch the NEXT match (the key rotates).
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 SWUSim/DevTools/tests/high_ground_watch_auth_test.php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE); ini_set('display_errors', '1');
chdir(__DIR__ . '/../../..');
include_once './SWUSim/MatchFlow.php';
require_once './SWUSim/HighGround.php';
require_once './Core/GameAuth.php';
require_once './Core/NetworkingLibraries.php';

$FAILS = 0;
function check($cond, $msg) { global $FAILS; echo ($cond ? '  ok: ' : '  BAD: ') . $msg . "\n"; if (!$cond) $FAILS++; }
check(function_exists('apcu_enabled') && apcu_enabled(), 'setup: APCu is on in this CLI (run with -d apc.enable_cli=1)');

$DECK = trim(file_get_contents('./SWUSim/DevTools/tests/fixtures/premier_deck.txt'));
// A High Ground room built the way JoinQueue builds one: host + two members, all with a legal deck.
$lobbyID = 'hgwatch' . bin2hex(random_bytes(4));
$lobby = new stdClass();
$lobby->id = $lobbyID; $lobby->rootName = 'SWUSim'; $lobby->format = 'premier'; $lobby->queueType = 'bo3';
$lobby->isPrivate = true; $lobby->hostPlayerID = 1; $lobby->inviteCode = bin2hex(random_bytes(12)); $lobby->numPlayers = 3;
$players = [];
foreach ([1, 2, 3] as $i) { $p = new Player($i, $DECK, '', null); $p->setDeckOk(true); $players[] = $p; }
$lobby->players = [$players[0]];
SWUHighGroundCreate($lobby, $players[0]);
foreach ([1, 2] as $i) { $lobby->players[] = $players[$i]; SWUHighGroundOnJoin($lobby, $players[$i]); }
foreach ($players as $p) $p->setReady(true);
apcu_store($lobbyID, $lobby, 600);
$made = [];

$start = SWUHighGroundStartMatch($lobbyID, $players[0]->getAuthKey());
$mid = strval($start['matchId'] ?? ''); $g1 = strval($start['gameName'] ?? '');
check($g1 !== '', 'setup: the hill match started: ' . json_encode($start));
$made[] = $mid;
$watchKey1 = strval(apcu_fetch($lobbyID)->highGround['watchKey']);
$m = SWUReadMatch($mid);
$g2 = MatchSpawnNextGameWithDecks('SWUSim', $mid, 2, $g1, [1 => $m['players']['1']['originalDeck'], 2 => $m['players']['2']['originalDeck']]);
check($g2 !== '', 'setup: game 2 of the Bo3 spawned');

putenv('DEVENV=false');
$_SERVER['HTTP_HOST'] = 'petranaki.net';
$_SERVER['REMOTE_ADDR'] = '203.0.113.7';
try {
    check(!SimGameIsDevelopmentEnvironment() && SimGameIsPrivateGame('SWUSim', $g1), 'setup: dev mode OFF, a private game');

    echo "Member_WatchesGame1:\n";
    check(SimGameValidateSpectatorAuth('SWUSim', $g1, $watchKey1) === true, 'a member watches game 1 with the room\'s watch key');
    check(SimGameValidateViewerAuth('SWUSim', $g1, ['isSpectator' => true, 'viewerSeat' => 0], $watchKey1) === true,
        'the gate NextTurn / GetNextTurn use agrees');

    echo "Member_WatchesGame2OfBo3:\n";
    check(SimGameValidateSpectatorAuth('SWUSim', $g2, $watchKey1) === true, 'the same key opens game 2 (the URL a watcher follows)');

    echo "NonMember_NoKey_Refused:\n";
    check(SimGameValidateSpectatorAuth('SWUSim', $g1, '') === false, 'no key: refused');
    check(SimGameValidateSpectatorAuth('SWUSim', $g1, bin2hex(random_bytes(16))) === false, 'a made-up key: refused');
    check(SimGameValidateSpectatorAuth('SWUSim', $g1, $players[2]->getAuthKey()) === false, 'a member\'s own room key is not the watch key');

    echo "NonMember_OldKeyAfterRotation_RefusedOnNextMatch:\n";
    // Member 3 leaves mid-match: the live match keeps its key, the NEXT match gets a new one.
    $lobby = apcu_fetch($lobbyID);
    $leaverKey = $players[2]->getAuthKey();
    foreach ($lobby->players as $i => $p) if ($p->getAuthKey() === $leaverKey) array_splice($lobby->players, $i, 1);
    SWUHighGroundOnLeave($lobby, $leaverKey);
    apcu_store($lobbyID, $lobby, 600);
    $watchKey2 = strval($lobby->highGround['watchKey']);
    check($watchKey2 !== $watchKey1 && strlen($watchKey2) === 32, 'the watch key rotated');
    check(SimGameValidateSpectatorAuth('SWUSim', $g2, $watchKey1) === true, 'the match already on keeps working for the room');
    // End the series and start the next match.
    MatchAbortNoResult('SWUSim', $mid);
    $lobby = apcu_fetch($lobbyID);
    SWUHighGroundApplyMatch($lobby, array_merge(SWUReadMatch($mid), ['hgGone' => '']));
    foreach ($lobby->players as $p) $p->setReady(true);
    apcu_store($lobbyID, $lobby, 600);
    putenv('DEVENV=true');   // match creation runs as the app would; only the checks need the production gate
    $next = SWUHighGroundStartMatch($lobbyID, $players[0]->getAuthKey());
    putenv('DEVENV=false');
    $g3 = strval($next['gameName'] ?? '');
    $made[] = strval($next['matchId'] ?? '');
    check($g3 !== '', 'setup: the next match started: ' . json_encode($next));
    check(SimGameValidateSpectatorAuth('SWUSim', $g3, $watchKey1) === false, 'the old key (known to whoever left) does not open the next match');
    check(SimGameValidateSpectatorAuth('SWUSim', $g3, $watchKey2) === true, 'the current key does');

    echo "NormalPrivateGame_RandomKeyUnchanged:\n";
    $plain = (object)['isPrivate' => true, 'players' => []];
    $k = SimGameBuildAuthKeysFromLobby($plain)['spectator'];
    check(strlen($k) === 32 && $k !== $watchKey2, 'an ordinary private game still gets its own random key');
} finally {
    putenv('DEVENV=true');
    apcu_delete($lobbyID);
    foreach ([SWURoomPath($lobbyID), SWURoomInvitePath(strval($lobby->inviteCode ?? ''))] as $f) if ($f !== '' && is_file($f)) @unlink($f);
    foreach ($made as $x) {   // the match and its games' folders (their pointer files would mislead a reused game number)
        if ($x === '') continue;
        $rec = SWUReadMatch($x);
        foreach ((is_array($rec) ? $rec['games'] : []) as $g) { $gn = preg_replace('/[^A-Za-z0-9_]/', '', strval($g['gameName'] ?? '')); if ($gn !== '') exec('rm -rf ' . escapeshellarg(MatchGamesDir('SWUSim') . '/' . $gn)); }
        exec('rm -rf ' . escapeshellarg(MatchesDir('SWUSim') . '/' . $x));
    }
}
echo $FAILS === 0 ? "PASS\n" : "$FAILS FAILED\n";
exit($FAILS === 0 ? 0 : 1);
