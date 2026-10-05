<?php
// Meta Premier queue gates + rating-window pairing order — docs/superpowers/specs/2026-10-03-swusim-metapremier-ratings-design.md §2.3, §5.1-5.2.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php SWUSim/DevTools/tests/metapremier_pairing_test.php
chdir(dirname(__DIR__, 3));
require_once './APIs/Lobbies/Classes/Player.php';
require_once './SWUSim/MetaPremier.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

// ── The widening window ──────────────────────────────────────────────────────────────────────────────
$check(SWUMetaPremierWindow(0) == 150 && SWUMetaPremierWindow(14) == 150, 'window starts at 150');
$check(SWUMetaPremierWindow(15) == 200 && SWUMetaPremierWindow(119) == 500, 'window widens 50 per 15s');
$check(is_infinite(SWUMetaPremierWindow(120)), 'window opens at 120s');

// ── Gates ────────────────────────────────────────────────────────────────────────────────────────────
$noCd = fn($u) => 0;
$check((SWUMetaPremierQueueRefusal('metapremier', 'bo3', null, false, $noCd)['code'] ?? '') === 'login_required', 'guest refused');
$check((SWUMetaPremierQueueRefusal('metapremier', 'bo3', 0, false, $noCd)['code'] ?? '') === 'login_required', 'user id 0 refused');
$check((SWUMetaPremierQueueRefusal('metapremier', 'bo3', 5, true, $noCd)['code'] ?? '') === 'queue_only', 'private room refused');
$check((SWUMetaPremierQueueRefusal('metapremier', 'bo1', 5, false, $noCd)['code'] ?? '') === 'queue_type_unavailable', 'bo1 refused while off');
$cd = SWUMetaPremierQueueRefusal('metapremier', 'bo3', 5, false, fn($u) => 252);
$check(($cd['code'] ?? '') === 'cooldown' && ($cd['secondsLeft'] ?? 0) === 252 && strpos($cd['message'] ?? '', '4m 12s') !== false, 'cooldown refused with time left');
$check(SWUMetaPremierQueueRefusal('metapremier', 'bo3', 5, false, $noCd) === null, 'logged-in bo3 allowed');
$check(SWUMetaPremierQueueRefusal('premier', 'bo1', null, true, fn($u) => 999) === null, 'premier untouched by every gate');

// ── Candidate ordering ───────────────────────────────────────────────────────────────────────────────
$now = 10000;
$mkLobby = function ($rating, $age, $uid, $fmt = 'metapremier', $qt = 'bo3') use ($now) {
    $l = new stdClass(); $l->rootName = 'SWUSim'; $l->format = $fmt; $l->queueType = $qt; $l->isPrivate = false;
    $l->rating = $rating; $l->createdAt = $now - $age; $l->players = [new Player(1, '', '', $uid)]; return $l;
};
$store = ['a' => $mkLobby(1700, 0, 1), 'b' => $mkLobby(1560, 0, 2), 'c' => $mkLobby(1520, 30, 3),
          'd' => $mkLobby(1900, 200, 4), 'own' => $mkLobby(1500, 0, 99), 'p' => $mkLobby(1500, 0, 5, 'premier'),
          'q' => $mkLobby(1500, 0, 6, 'metapremier', 'bo1')];
$list = array_map(fn($k) => ['info' => $k], array_keys($store));
$keys = array_map(fn($e) => $e['info'], SWUMetaPremierOrderCandidates($list, fn($k) => $store[$k], 'metapremier', 'bo3', 1500.0, 99, $now));
// c: diff 20 (window 250) · b: diff 60 · a: diff 200 > 150 → out · d: waited 200s → open, diff 400 · own lobby / other format / other match type → out
$check($keys === ['c', 'b', 'd'], 'closest first, out-of-window dropped, opened lobby kept, own/other-format excluded (got ' . implode(',', $keys) . ')');
$tie = ['x' => $mkLobby(1550, 10, 7), 'y' => $mkLobby(1450, 40, 8)];
$keys = array_map(fn($e) => $e['info'], SWUMetaPremierOrderCandidates([['info' => 'x'], ['info' => 'y']], fn($k) => $tie[$k], 'metapremier', 'bo3', 1500.0, 99, $now));
$check($keys === ['y', 'x'], 'equal distance: the longer-waiting lobby first');
$check(SWUMetaPremierOrderCandidates([['info' => 'gone'], ['nope' => 1]], fn($k) => false, 'metapremier', 'bo3', 1500.0, 99, $now) === [], 'expired / malformed entries skipped');

// ── Both players' waits count (final review #1: two out-of-range players waited forever) ─────────────
// The window is the WIDER of the waiting lobby's and the joiner's own: a joiner who has waited 2 minutes takes anyone.
$fresh = ['f' => $mkLobby(1900, 0, 11)];
$keys = array_map(fn($e) => $e['info'], SWUMetaPremierOrderCandidates([['info' => 'f']], fn($k) => $fresh[$k], 'metapremier', 'bo3', 1500.0, 99, $now, 120));
$check($keys === ['f'], "a joiner who has waited 120s takes a fresh lobby 400 away");
$keys = array_map(fn($e) => $e['info'], SWUMetaPremierOrderCandidates([['info' => 'f']], fn($k) => $fresh[$k], 'metapremier', 'bo3', 1500.0, 99, $now, 0));
$check($keys === [], "... but not without having waited");
// Where the joiner's wait started: their own oldest waiting rated lobby, else none.
$mine = ['m1' => $mkLobby(1500, 40, 99), 'm2' => $mkLobby(1500, 90, 99), 'x' => $mkLobby(1500, 300, 5), 'p' => $mkLobby(1500, 500, 99, 'premier')];
$check(SWUMetaPremierOwnWaitStart(array_map(fn($k) => ['info' => $k], array_keys($mine)), fn($k) => $mine[$k], 'metapremier', 'bo3', 99) === $now - 90,
    "own wait start = the oldest of the player's own waiting rated lobbies");
$check(SWUMetaPremierOwnWaitStart([['info' => 'x']], fn($k) => $mine[$k], 'metapremier', 'bo3', 99) === null, 'no own lobby → no wait start');
// A lobby that already made its game stays in APCu for its TTL. Re-queuing after a match must start a fresh wait,
// not inherit the old lobby's age (which would open the window to anyone at once).
$done = $mkLobby(1500, 500, 99); $done->gameName = 'G1'; $done->numPlayers = 2; $done->maxPlayers = 2;
$check(SWUMetaPremierOwnWaitStart([['info' => 'done']], fn($k) => $done, 'metapremier', 'bo3', 99) === null, 'a matched lobby is not a wait');
// The poll's question: is there now someone in range for the player waiting in $myKey?
$pair = ['me' => $mkLobby(1670, 130, 1), 'them' => $mkLobby(1330, 110, 2)];
$list = [['info' => 'me'], ['info' => 'them']];
$check(SWUMetaPremierShouldRequeue('me', $list, fn($k) => $pair[$k], $now) === true, 'two lobbies 340 apart, one waited 130s → requeue');
$pair2 = ['me' => $mkLobby(1670, 20, 1), 'them' => $mkLobby(1330, 10, 2)];
$check(SWUMetaPremierShouldRequeue('me', $list, fn($k) => $pair2[$k], $now) === false, '... not while both windows are still narrow');
$check(SWUMetaPremierShouldRequeue('me', [['info' => 'me']], fn($k) => $pair[$k], $now) === false, 'alone in the queue → no requeue');
$solo = ['me' => $mkLobby(1500, 300, 1), 'mine2' => $mkLobby(1500, 10, 1)];
$check(SWUMetaPremierShouldRequeue('me', [['info' => 'me'], ['info' => 'mine2']], fn($k) => $solo[$k], $now) === false, 'never requeues toward my own other lobby');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
