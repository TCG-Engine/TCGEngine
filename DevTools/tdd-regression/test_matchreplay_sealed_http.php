<?php
// Sealed old-style replays (L3): in a game with two human seats, "Save Replay" downloads an opaque record only the server
// can open, and it plays back from the saver's seat — never the initial gamestate or the opponent's hidden cards.
// Single-human games (goldfish, Arenabot) keep the plain format, played as seat 1.
// RUN VIA CLI (it calls Apache inside the container; logs in as claudebot1):
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_matchreplay_sealed_http.php
error_reporting(E_ALL & ~E_DEPRECATED);
chdir('/var/www/html/TCGEngine');
require_once __DIR__ . '/fixtures/swusim_chat_http_helpers.php';
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
require_once __DIR__ . '/../../Core/MatchReplay.php';
require_once __DIR__ . '/../../SWUSim/Custom/SwuPgnWriter/Identity.php';
if (!function_exists('GameMatchReplaySealKey')) { function GameMatchReplaySealKey(): string { return SwuPgnSubKey('seal'); } }   // what the SWUSim hook returns

$api = fn(string $q) => 'APIs/MatchReplay.php?' . $q;
function sealed_post(string $path, $body, ?int &$status = null): array {
    $ch = curl_init(SWUCHAT_BASE . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($body), CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_COOKIEJAR => swuchat_jar(), CURLOPT_COOKIEFILE => swuchat_jar()]);
    $r = (string)curl_exec($ch); $status = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE)); curl_close($ch);
    $j = json_decode($r, true); return is_array($j) ? $j + ['__raw' => $r] : ['__raw' => $r];
}
function sealed_get(string $path, ?int &$status = null): string {
    $ch = curl_init(SWUCHAT_BASE . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_COOKIEJAR => swuchat_jar(), CURLOPT_COOKIEFILE => swuchat_jar()]);
    $r = (string)curl_exec($ch); $status = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE)); curl_close($ch); return $r;
}

// P1 holds cards P2 never sees; P2 holds its own. P1 plays one card (public from then on) and concedes.
$P1_HIDDEN = ['LOF_158', 'SEC_186', 'JTL_039', 'ASH_194'];
$schema = <<<MD
## GIVEN
CommonSetup: bbw/rrk/{myResources:5; theirResources:5}
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1Hand: [SOR_095 LOF_158 SEC_186]
WithP1Deck: [JTL_039 ASH_194]
WithP2Hand: [SOR_237]
WithP2Deck: [SOR_101 SOR_102]

## WHEN

## EXPECT
TURNPLAYER:1
MD;
$gn = swuchat_make_game($schema);
SwuPgnTestCheck($gn !== '', 'a 2-seat PvP fixture game');
$input = fn(string $g, string $pid, int $mode, string $cardID = '') => json_decode(swuchat_http('ProcessInput.php?' . http_build_query(
    ['gameName' => $g, 'playerID' => $pid, 'authKey' => 'testschema', 'folderPath' => 'SWUSim', 'mode' => $mode, 'cardID' => $cardID, 'responseFormat' => 'json'])), true);
$input($gn, '1', 10002, 'myHand-0!FSM!');
$input($gn, '1', 10006);
$noHidden = fn(string $text) => array_values(array_filter($P1_HIDDEN, fn($c) => str_contains($text, $c)));

// ── Download: sealed, per seat ───────────────────────────────────────────────────────────────────
$raw = sealed_get($api(http_build_query(['action' => 'download', 'folderPath' => 'SWUSim', 'gameName' => $gn, 'playerID' => '2', 'authKey' => 'testschema'])), $st);
$dl = json_decode($raw, true) ?? [];
$rec = $dl['replay'] ?? [];
SwuPgnTestCheck($st === 200 && ($rec['format'] ?? '') === 'tcgengine-match-replay-sealed-v1', 'P2 downloads a SEALED record', "$st " . substr($raw, 0, 200));
SwuPgnTestCheck(!array_key_exists('initialGamestate', $rec) && !array_key_exists('actions', $rec), 'no initialGamestate / actions in it');
SwuPgnTestEq(array_keys($rec), ['format', 'rootName', 'gameName', 'savedAt', 'winner', 'actionCount', 'seat', 'sealed'], 'the record\'s fields');
SwuPgnTestEq([$rec['seat'] ?? null, $rec['winner'] ?? null], [2, 2], 'sealed for seat 2; P2 won');
SwuPgnTestEq($noHidden($raw), [], 'the response names none of P1\'s hidden cards');
sealed_get($api(http_build_query(['action' => 'download', 'folderPath' => 'SWUSim', 'gameName' => $gn, 'playerID' => 'S', 'authKey' => 'testschema'])), $st);
SwuPgnTestEq($st, 403, 'a spectator cannot download a sealed game\'s replay');
$open = MatchReplayUnseal($rec);
SwuPgnTestCheck(is_array($open) && ($open['seat'] ?? null) === 2 && count(array_intersect($P1_HIDDEN, $noHidden(strval($open['payload']['initialGamestate'] ?? '')))) === count($P1_HIDDEN),
    'the server can open it (and inside is the full game — so the checks below mean something)');

// ── Import: seat 2's view only ───────────────────────────────────────────────────────────────────
$imp = sealed_post($api('action=import'), ['replay' => $rec], $st);
SwuPgnTestCheck($st === 200 && !empty($imp['success']) && ($imp['playerID'] ?? null) === 2 && str_contains(strval($imp['nextTurnUrl'] ?? ''), 'playerID=2'), 'imports as seat 2', "$st " . substr($imp['__raw'], 0, 200));
$rg = strval($imp['gameName'] ?? ''); $rk = strval($imp['authKey'] ?? '');
SwuPgnTestCheck(preg_match('/^[a-f0-9]{32}$/', $rk) === 1 && substr_count($imp['__raw'], '"authKey"') === 1, 'one seat key is returned — seat 2\'s');
$step = fn() => json_decode(swuchat_http('ProcessInput.php?' . http_build_query(['gameName' => $rg, 'playerID' => 2, 'authKey' => $rk, 'folderPath' => 'SWUSim', 'mode' => 11101, 'responseFormat' => 'json'])), true);
$views = function () use ($rg, $rk) {
    $out = [swuchat_http('SWUSim/GetNextTurn.php?' . http_build_query(['gameName' => $rg, 'playerID' => 2, 'authKey' => $rk, 'lastUpdate' => 0, 'lastChatVersion' => 0, 'lastChatID' => 0]))];
    foreach (['p1Hand', 'p1Deck', 'theirHand', 'theirDeck'] as $z) {
        $out[] = swuchat_http('GetPopupContent.php?' . http_build_query(['gameName' => $rg, 'playerID' => 2, 'authKey' => $rk, 'folderPath' => 'SWUSim', 'popupType' => $z]));
    }
    return implode("\n", $out);
};
$leaks = $noHidden($views());
$steps = 0;
for ($i = 0; $i < intval($rec['actionCount'] ?? 0) + 2; $i++) {
    $step(); $steps++;
    $leaks = array_merge($leaks, $noHidden($views()));
}
SwuPgnTestEq(array_values(array_unique($leaks)), [], "every playback step ($steps), board and popups, names none of P1's hidden cards");
$ps = sealed_get('SWUSim/GetNextTurn.php?' . http_build_query(['gameName' => $rg, 'playerID' => 2, 'authKey' => $rk, 'lastUpdate' => 0, 'lastChatVersion' => 0, 'lastChatID' => 0]));
SwuPgnTestCheck(!str_contains($ps, 'MR1:') && !str_contains($ps, 'initialGamestate'), 'the poll never carries the replay\'s initial state');

// The playback game's keys (pinned in-process: the dev container accepts any seat key over HTTP).
require_once __DIR__ . '/../../Core/GameAuth.php';
$k = MatchReplayImportAuthKeys('RETURNED', 2);
SwuPgnTestCheck($k['p2'] === 'RETURNED' && !in_array('RETURNED', [$k['p1'], $k['p3'], $k['p4'], $k['spectator']], true) && $k['isPrivate'] === true && $k['casterMode'] === false,
    'a sealed import\'s key opens seat 2 only; every other seat and spectating get keys nobody is told');
$k = MatchReplayImportAuthKeys('RETURNED', 0);
SwuPgnTestCheck($k['p1'] === 'RETURNED' && $k['p2'] !== 'RETURNED', 'a plain import\'s key is seat 1\'s, as before');

// ── Tamper, re-save, branch ──────────────────────────────────────────────────────────────────────
$flip = $rec; $b = base64_decode($flip['sealed']); $b[intdiv(strlen($b), 2)] = chr(ord($b[intdiv(strlen($b), 2)]) ^ 1); $flip['sealed'] = base64_encode($b);
sealed_post($api('action=import'), ['replay' => $flip], $st);
SwuPgnTestEq($st, 400, 'a byte-flipped sealed record: 400');
$seat1 = $rec; $seat1['seat'] = 1;
sealed_post($api('action=import'), ['replay' => $seat1], $st);
SwuPgnTestEq($st, 400, 'a record edited to seat 1: 400');
$win = $rec; $win['winner'] = 1;
sealed_post($api('action=import'), ['replay' => $win], $st);
SwuPgnTestEq($st, 400, 'any edited field: 400');
sealed_get($api(http_build_query(['action' => 'download', 'folderPath' => 'SWUSim', 'gameName' => $rg, 'playerID' => '2', 'authKey' => $rk])), $st);
SwuPgnTestEq($st, 409, 'Save Replay inside a sealed replay: 409');
$pf = json_decode(swuchat_http('ProcessInput.php?' . http_build_query(['gameName' => $rg, 'playerID' => 2, 'authKey' => $rk, 'folderPath' => 'SWUSim', 'mode' => 11104, 'responseFormat' => 'json'])), true);
SwuPgnTestCheck(empty($pf['success']), '"Play from Here" (11104) is refused in a sealed replay', json_encode($pf));
// Reset (11102) rebuilds the playback state: it must keep the seal.
swuchat_http('ProcessInput.php?' . http_build_query(['gameName' => $rg, 'playerID' => 2, 'authKey' => $rk, 'folderPath' => 'SWUSim', 'mode' => 11102, 'responseFormat' => 'json']));
sealed_get($api(http_build_query(['action' => 'download', 'folderPath' => 'SWUSim', 'gameName' => $rg, 'playerID' => '2', 'authKey' => $rk])), $st);
SwuPgnTestEq($st, 409, 'after Reset, Save Replay is still refused');
$pf = json_decode(swuchat_http('ProcessInput.php?' . http_build_query(['gameName' => $rg, 'playerID' => 2, 'authKey' => $rk, 'folderPath' => 'SWUSim', 'mode' => 11104, 'responseFormat' => 'json'])), true);
SwuPgnTestCheck(empty($pf['success']), 'after Reset, Play from Here is still refused');

// ── Plain stays plain: a goldfish game ───────────────────────────────────────────────────────────
$deck = trim(implode("\n", array_filter(explode("\n", file_get_contents('./SWUSim/Tests/BotFixtures/ash-meta-2026-09/darth-vader_jtl_yellow.txt')), fn($l) => !str_starts_with($l, '#'))));
$j = json_decode(swuchat_http('APIs/Lobbies/JoinQueue.php', ['rootName' => 'SWUSim', 'format' => 'goldfish', 'deckLink' => $deck]), true);
$gg = strval($j['gameName'] ?? ''); $gk = strval($j['authKey'] ?? '');
$gin = fn(int $mode, string $card = '') => swuchat_http('ProcessInput.php?' . http_build_query(['gameName' => $gg, 'playerID' => 1, 'authKey' => $gk, 'folderPath' => 'SWUSim', 'mode' => $mode, 'cardID' => $card, 'responseFormat' => 'json']));
$gin(100, 'NO'); $gin(10006);
$pl = json_decode(sealed_get($api(http_build_query(['action' => 'download', 'folderPath' => 'SWUSim', 'gameName' => $gg, 'playerID' => '1', 'authKey' => $gk]))), true)['replay'] ?? [];
SwuPgnTestCheck(($pl['format'] ?? '') === 'tcgengine-match-replay-v1' && isset($pl['initialGamestate'], $pl['actions']) && !isset($pl['sealed']), 'a goldfish replay downloads PLAIN, as before');
$pi = sealed_post($api('action=import'), ['replay' => $pl], $st);
SwuPgnTestCheck($st === 200 && preg_match('/^[a-f0-9]{32}$/', strval($pi['authKey'] ?? '')) === 1 && str_contains(strval($pi['nextTurnUrl'] ?? ''), 'playerID=1'),
    'and imports as seat 1 with a 32-hex key (the SWUDeck simulation import contract)', substr($pi['__raw'], 0, 200));
SwuPgnTestCheck(!array_key_exists('playerID', $pi) || $pi['playerID'] === 1, 'a plain import is seat 1');

SwuPgnTestFinish();
