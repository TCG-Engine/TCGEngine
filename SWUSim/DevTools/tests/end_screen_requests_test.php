<?php
// End-screen requests after a finished 1v1 match (SWUSim/EndScreenRequests.php): Rematch / Quick Rematch / Convert to
// Bo3 as accept-or-decline requests, the 30s re-request rule, crossing requests, and a player leaving the end screen.
// Pure — the match array in, time passed in. Spec: docs/superpowers/specs/2026-10-30-persistent-lobby-rematch-design.md
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/end_screen_requests_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
require_once __DIR__ . '/../../EndScreenRequests.php';

$FAILS = 0;
function check($cond, $msg) { global $FAILS; if ($cond) { echo "  ok: $msg\n"; } else { echo "  BAD: $msg\n"; $FAILS++; } }

$T = 1800000000;
function finished(int $bestOf = 1, string $format = 'premier'): array {
    return ['matchId' => 'M1', 'format' => $format, 'bestOf' => $bestOf, 'state' => 'complete',
            'players' => ['1' => ['authKey' => 'a'], '2' => ['authKey' => 'b']]];
}
// Both seats seen just now (the end-screen heartbeat store's shape: seat => last poll, '_since' => store birth).
$seen = fn(int $now, int $ago1 = 0, int $ago2 = 0) => ['_since' => $now - 1000, 1 => $now - $ago1, 2 => $now - $ago2];

echo "request:\n";
$m = finished();
$r = EndReqOpen($m, 1, 'rematch', 1, 'r1', $T);
check($r['ok'] && $r['accepted'] === null, 'a rematch request is stored');
$v2 = EndReqView($m, 2, $seen($T), $T);
$v1 = EndReqView($m, 1, $seen($T), $T);
check(($v2['incoming']['id'] ?? '') === 'r1' && ($v2['incoming']['kind'] ?? '') === 'rematch', 'the opponent sees it as incoming');
check(($v1['outgoing']['kind'] ?? '') === 'rematch' && $v1['incoming'] === null, 'the requester sees it as outgoing');
check(($v1['outgoing']['repeatIn'] ?? -1) === 30, 'and may ask again in 30s');

echo "30s:\n";
$r = EndReqOpen($m, 1, 'rematch', 1, 'r2', $T + 29);
check(!$r['ok'] && $r['error'] === 'You can ask again in 1s.', 'asking again at +29s is refused: ' . strval($r['error']));
check((EndReqView($m, 2, $seen($T + 29), $T + 29)['incoming']['id'] ?? '') === 'r1', 'the refused request did not replace the open one');
$r = EndReqOpen($m, 1, 'rematch', 1, 'r3', $T + 30);
check($r['ok'] && (EndReqView($m, 2, $seen($T + 30), $T + 30)['incoming']['id'] ?? '') === 'r3', 'at +30s it is allowed and replaces the old request (a new pop-up)');

echo "decline:\n";
$m = finished();
EndReqOpen($m, 1, 'rematch', 1, 'r1', $T);
$r = EndReqAnswer($m, 2, 'r1', false, $T + 3);
check($r['ok'] && $r['accepted'] === null, 'the opponent declines');
check(($r['declined'] ?? null) === 'rematch', 'the answer names what was declined (the log line needs it): ' . json_encode($r['declined'] ?? null));
check(EndReqView($m, 2, $seen($T + 3), $T + 3)['incoming'] === null, 'the request is gone');
check((EndReqView($m, 1, $seen($T + 3), $T + 3)['declined']['kind'] ?? '') === 'rematch', 'the requester is told it was declined');
$r = EndReqOpen($m, 1, 'rematch', 1, 'r2', $T + 4);
check($r['ok'], 'after an answer the requester may ask again at once (no 30s wait)');
check(EndReqView($m, 1, $seen($T + 4), $T + 4)['declined'] === null, 'asking again clears the declined notice');
$m = finished();
EndReqOpen($m, 1, 'convert', 1, 'c1', $T);
check((EndReqAnswer($m, 2, 'c1', false, $T + 1)['declined'] ?? null) === 'convert', 'a declined Convert is named as the convert');
$m = finished();
EndReqOpen($m, 1, 'rematch', 1, 'r1', $T);
check(array_key_exists('declined', $r = EndReqAnswer($m, 2, 'r1', true, $T + 1)) && $r['declined'] === null, 'an accept declines nothing');

echo "accept:\n";
$m = finished();
EndReqOpen($m, 1, 'quick', 3, 'q1', $T);
$r = EndReqAnswer($m, 2, 'q1', true, $T + 2);
check($r['ok'] && ($r['accepted'] ?? null) === ['kind' => 'quick', 'bestOf' => 3, 'from' => 1, 'by' => 2], 'accepting returns what was agreed: ' . json_encode($r['accepted']));
check(EndReqView($m, 1, $seen($T + 2), $T + 2)['outgoing'] === null && EndReqView($m, 2, $seen($T + 2), $T + 2)['incoming'] === null, 'and clears the request');
$r = EndReqAnswer($m, 2, 'q1', true, $T + 3);
check(!$r['ok'] && $r['error'] === 'That request is no longer open.', 'a second accept of the same request is refused');

echo "crossing:\n";
$m = finished();
EndReqOpen($m, 1, 'rematch', 1, 'x1', $T);
$r = EndReqOpen($m, 2, 'rematch', 1, 'x2', $T + 1);
check($r['ok'] && ($r['accepted']['kind'] ?? '') === 'rematch' && ($r['accepted']['from'] ?? 0) === 1 && ($r['accepted']['by'] ?? 0) === 2,
      'both asking for a Rematch counts as agreeing (the first asker is "from"): ' . json_encode($r['accepted']));
$m = finished();
EndReqOpen($m, 1, 'quick', 1, 'x1', $T);
$r = EndReqOpen($m, 2, 'quick', 3, 'x2', $T + 1);
check($r['ok'] && $r['accepted'] === null, 'Quick Rematch Bo1 crossing Quick Rematch Bo3 does NOT agree');
check((EndReqView($m, 1, $seen($T + 1), $T + 1)['incoming']['bestOf'] ?? 0) === 3 && (EndReqView($m, 2, $seen($T + 1), $T + 1)['incoming']['bestOf'] ?? 0) === 1,
      'each sees the other\'s as its own pop-up');
$m = finished();
EndReqOpen($m, 1, 'quick', 1, 'x1', $T);
$r = EndReqOpen($m, 2, 'quick', 1, 'x2', $T + 1);
check(($r['accepted']['kind'] ?? '') === 'quick' && ($r['accepted']['bestOf'] ?? 0) === 1, 'Quick Rematch crossing Quick Rematch at the same length agrees');
$m = finished();
EndReqOpen($m, 1, 'rematch', 1, 'x1', $T);
$r = EndReqOpen($m, 2, 'quick', 1, 'x2', $T + 1);
check($r['ok'] && $r['accepted'] === null, 'Rematch crossing Quick Rematch never merges');
check(EndReqView($m, 1, $seen($T + 1), $T + 1)['incoming']['kind'] === 'quick' && EndReqView($m, 2, $seen($T + 1), $T + 1)['incoming']['kind'] === 'rematch', 'both stay pending');

echo "stale answers:\n";
$m = finished();
EndReqOpen($m, 1, 'rematch', 1, 'r1', $T);
$r = EndReqAnswer($m, 2, 'nope', true, $T + 1);
check(!$r['ok'] && $r['error'] === 'That request is no longer open.', 'answering a request id that is not open is refused');
check(!EndReqAnswer($m, 1, 'r1', true, $T + 1)['ok'], 'the requester cannot accept their own request');

echo "departed:\n";
$m = finished();
EndReqOpen($m, 1, 'rematch', 1, 'r1', $T);
EndReqMarkDeparted($m, 1, 'menu');
$r = EndReqAnswer($m, 2, 'r1', true, $T + 1);
check(!$r['ok'], 'accepting after the requester went to the menu is refused: ' . strval($r['error']));
$r = EndReqOpen($m, 2, 'rematch', 1, 'r2', $T + 1);
check(!$r['ok'] && $r['error'] === 'Your opponent has left the end-game screen.', 'asking someone who left is refused');
$v2 = EndReqView($m, 2, $seen($T + 1), $T + 1);
check($v2['opponentWent'] === 'menu' && $v2['kinds'] === [] && $v2['incoming'] === null, 'the stayer sees where they went, no requests and nothing to ask for');
$m = finished();
EndReqMarkDeparted($m, 1, 'requeue');
check(EndReqView($m, 2, $seen($T), $T)['opponentWent'] === 'requeue' && EndReqView($m, 2, $seen($T), $T)['kinds'] === [], 'requeued: the same');
$m = finished();
EndReqMarkDeparted($m, 1, 'lobby');
check(EndReqView($m, 2, $seen($T), $T)['opponentWent'] === 'lobby', 'went to the lobby: the stayer is told (Return to Lobby takes them there too)');

echo "kinds:\n";
check(EndReqKindsAllowed(finished(1)) === ['rematch', 'quick', 'convert'], 'a finished Bo1 offers all three');
check(EndReqKindsAllowed(finished(3)) === ['rematch', 'quick'], 'a finished Bo3 has no Convert');
check(EndReqKindsAllowed(finished(1, 'metapremier')) === [], 'a rated match offers none (players requeue)');
$running = finished(); $running['state'] = 'in_progress';
check(EndReqKindsAllowed($running) === [], 'nothing while the match is still running');
$m = finished(3);
check(!EndReqOpen($m, 1, 'convert', 3, 'c', $T)['ok'], 'Convert on a Bo3 is refused');
$m = finished(1, 'metapremier');
check(EndReqOpen($m, 1, 'rematch', 1, 'r', $T)['error'] === 'That is not available for this match.', 'a rated Rematch is refused');
$m = finished();
EndReqOpen($m, 1, 'convert', 1, 'c', $T);
check((EndReqView($m, 2, $seen($T), $T)['incoming']['bestOf'] ?? 0) === 3, 'a Convert request is always Best of 3');

echo "quiet:\n";
$m = finished();
check(EndReqView($m, 2, $seen($T, 119, 0), $T)['opponentWent'] === null, 'opponent seen 119s ago: still there');
check(EndReqView($m, 2, $seen($T, 120, 0), $T)['opponentWent'] === 'gone', 'opponent seen 120s ago: gone (tab closed)');
check(EndReqView($m, 2, $seen($T, 120, 0), $T)['kinds'] === [], 'nothing to ask a player who is gone');
check(EndReqView($m, 2, ['_since' => $T - 30], $T)['opponentWent'] === null, 'a fresh heartbeat store (after a wipe) gives everyone time to re-stamp');
check(EndReqView($m, 2, ['_since' => $T - 500, 2 => $T], $T)['opponentWent'] === 'gone', 'an opponent who never polled the end screen in 120s+ of the store\'s life is gone');

echo "result:\n";
$m = finished();
$m['endScreen']['result'] = ['kind' => 'room', 'lobbyID' => 'L1', 'keys' => ['1' => 'k1', '2' => 'k2']];
check(EndReqView($m, 1, $seen($T), $T)['result'] === ['kind' => 'room', 'lobbyID' => 'L1', 'authKey' => 'k1'], 'a room result hands each seat ONLY its own key');
check(EndReqView($m, 0, $seen($T), $T)['result'] === null, 'a spectator learns nothing');

echo "failed:\n";
$m = finished();
$m['endScreen']['result'] = ['kind' => 'failed', 'message' => 'Could not start that — try again.'];
check(EndReqView($m, 1, $seen($T), $T)['result'] === ['kind' => 'failed', 'message' => 'Could not start that — try again.'], 'a fire that failed is shown to BOTH seats (no silent nothing)');
check(EndReqView($m, 2, $seen($T), $T)['result']['kind'] === 'failed', 'seat 2 too');
EndReqOpen($m, 1, 'rematch', 1, 'again', $T);
check(EndReqView($m, 1, $seen($T), $T)['result'] === null, 'asking again clears the failure');

echo "blocked:\n";
$m = finished(); $m['blocked'] = true;
check(EndReqKindsAllowed($m) === [], 'a blocked pair has nothing to ask each other');

echo $FAILS === 0 ? "PASS\n" : "$FAILS FAILED\n";
