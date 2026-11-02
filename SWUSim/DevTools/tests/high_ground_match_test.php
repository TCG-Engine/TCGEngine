<?php
// High Ground — the match-layer pieces (docs/superpowers/specs/2026-10-30-swusim-high-ground-design.md):
//   · MatchAbortNoResult: the host's "End match" — the match ends with NO result (no winner, no stats, sealed);
//   · a lobby-supplied spectator key (every game of a hill match takes the room's watch key; anything else stays random);
//   · the optional gameResultRecorded hook is a no-op for a sim that doesn't register it.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 SWUSim/DevTools/tests/high_ground_match_test.php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE); ini_set('display_errors', '1');
chdir(__DIR__ . '/../../..');
include_once './SWUSim/MatchFlow.php';
require_once './SWUSim/MatchHooks.php';
require_once './Core/GameAuth.php';

$FAILS = 0;
function check($cond, $msg) { global $FAILS; echo ($cond ? '  ok: ' : '  BAD: ') . $msg . "\n"; if (!$cond) $FAILS++; }

$seat = fn($k) => ['originalDeck' => ['leader' => 'SOR_005', 'base' => 'SOR_023', 'mainDeck' => [], 'sideboard' => []],
                   'authKey' => $k, 'userId' => null, 'deckIdentity' => '', 'deckLink' => 'dl-' . $k, 'cosmetics' => []];
$made = [];
$newMatch = function (string $queueType) use ($seat, &$made): string {
    $id = MatchCreate('SWUSim', 'premier', $queueType, ['1' => $seat('ka'), '2' => $seat('kb')], true);
    $made[] = $id;
    MatchWithLock('SWUSim', $id, function (&$m) {
        $m['games'][] = ['gameName' => 'hgmt' . mt_rand(100000, 999999), 'gameNumber' => 1, 'winner' => null];
        $m['currentGameNumber'] = 1;
    });
    return $id;
};

echo "Abort_SealsOpenGames_NoWinner:\n";
$id = $newMatch('bo3');
$open = SWUReadMatch($id)['games'][0]['gameName'];
$m = MatchAbortNoResult('SWUSim', $id);
$m = SWUReadMatch($id);
check(($m['state'] ?? '') === 'abandoned', 'the match is abandoned: ' . ($m['state'] ?? '?'));
check(array_key_exists('winner', $m['games'][0]) && $m['games'][0]['winner'] === null && !empty($m['games'][0]['statsRecorded']), 'the open game is sealed with no winner');
check(($m['games'][0]['detail']['endReason'] ?? '') === 'aborted', 'its end reason is aborted');
check(intval($m['winner'] ?? 0) === 0 && intval($m['wins']['1'] ?? 0) + intval($m['wins']['2'] ?? 0) === 0, 'no match winner, no wins');

echo "Abort_LaterGameOverIsNoOp:\n";
check(MatchGameIsSealed($m, $open), 'MatchAfterActionHook returns early on this game (it is sealed)');

echo "Abort_ClearsSideboarding:\n";
$id = $newMatch('bo3');
MatchBeginSideboarding('SWUSim', $id, 2);
MatchAbortNoResult('SWUSim', $id);
$m = SWUReadMatch($id);
check(!isset($m['sideboard']) && !isset($m['pendingFirstPlayer']), 'sideboarding is cleared');

echo "Abort_CompleteMatchUntouched:\n";
$id = $newMatch('bo1');
MatchRecordGameResult('SWUSim', $id, SWUReadMatch($id)['games'][0]['gameName'], 1, 5, [1]);
MatchAbortNoResult('SWUSim', $id);
check((SWUReadMatch($id)['state'] ?? '') === 'complete' && intval(SWUReadMatch($id)['winner']) === 1, 'a finished match keeps its result');

echo "SpectatorKey_FromLobbyWhenSet:\n";
$lobby = (object)['isPrivate' => true, 'players' => [], 'spectatorKey' => str_repeat('ab', 16)];
check(SimGameBuildAuthKeysFromLobby($lobby)['spectator'] === str_repeat('ab', 16), 'a lobby-supplied spectator key is used');

echo "SpectatorKey_RandomOtherwise:\n";
$plain = (object)['isPrivate' => true, 'players' => []];
$k1 = SimGameBuildAuthKeysFromLobby($plain)['spectator']; $k2 = SimGameBuildAuthKeysFromLobby($plain)['spectator'];
check(strlen($k1) === 32 && $k1 !== $k2, 'no supplied key: a fresh random key, as before');
check(strval(SimGameBuildAuthKeysFromLobby((object)['isPrivate' => false, 'players' => [], 'spectatorKey' => 'x'])['spectator'] ?? '') === '',
    'a public game still has no spectator key');

echo "HookAbsent_OtherSimsUnchanged:\n";
check(MatchHook('GrandArchiveSim', 'gameResultRecorded', [], 'g') === null, 'an unregistered optional hook is a quiet no-op');
check(in_array('gameResultRecorded', MATCH_OPTIONAL_HOOKS, true), 'gameResultRecorded is a declared optional hook');

echo "PollPath_DoesNotLoadTheEngine:\n";
// Every SWUSim room poll (1v1, Twin Suns, public queue — every 1.5s) loads HighGround.php, so it must stay LIGHT: no
// SWUSim/MatchFlow.php, which drags in CreateGame, the card dictionaries and GameLogic (~1,900 files).
$probe = function (string $code): array {
    $out = shell_exec(escapeshellarg(PHP_BINARY) . ' -d apc.enable_cli=1 -r ' . escapeshellarg('chdir("' . getcwd() . '"); error_reporting(0); ' . $code .
        ' $f = get_included_files(); echo json_encode(["n" => count($f), "engine" => count(array_filter($f, fn($x) => str_contains($x, "SWUSim/MatchFlow.php") || str_contains($x, "Custom/GameLogic.php")))]);'));
    return json_decode((string)$out, true) ?: ['n' => -1, 'engine' => -1, 'raw' => $out];
};
$p = $probe('require "./SWUSim/HighGround.php";');
check(($p['engine'] ?? -1) === 0 && ($p['n'] ?? 999) < 120, 'HighGround.php loads ' . ($p['n'] ?? '?') . ' files, none of the engine');

echo "ChatOverride_DoesNotLoadTheEngineInsideAFunction:\n";
// Loading the engine from inside a function leaves its globals local to that call — later top-level includes in the same
// request (SubmitChat's block check, NextTurn) then find them missing. The override must read matches through Core.
$p = $probe('require "./APIs/Lobbies/Classes/Player.php"; require "./SWUSim/Custom/ChatPolicy.php"; SimChatViewerOverride("999999999", [], []);');
check(($p['engine'] ?? -1) === 0, 'the chat override reads matches without the engine (' . ($p['n'] ?? '?') . ' files)');

foreach ($made as $x) exec('rm -rf ' . escapeshellarg(MatchesDir('SWUSim') . '/' . $x));
echo $FAILS === 0 ? "PASS\n" : "$FAILS FAILED\n";
exit($FAILS === 0 ? 0 : 1);
