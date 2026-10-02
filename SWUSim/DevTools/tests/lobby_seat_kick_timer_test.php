<?php
// A room seat cannot be REMOVED by the host during its first minute (owner request 2026-10-01: "someone can kick a
// seat as soon as they join"). These are the pure rules — the KickSeat endpoint and the roster's kickableIn field
// both read SWUSeatKickableIn, so this is where the clock is pinned.
//
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
//     php SWUSim/DevTools/tests/lobby_seat_kick_timer_test.php
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';
require_once $root . '/APIs/Lobbies/Classes/Player.php';
require_once $root . '/APIs/Lobbies/Classes/TeamRooms.php';

check(SWU_SEAT_KICK_ARM_AFTER === 60, 'a seat is protected for 60s after it joins');

// A seat is stamped with its join time when it is CREATED — every new seat goes through the constructor.
$before = time();
$p = new Player(2, 'deck');
check($p->getJoinedAt() >= $before && $p->getJoinedAt() <= time(), 'a new seat records when it joined');

$t0 = 1000000;
$p->setJoinedAt($t0);
check(SWUSeatKickableIn($p, $t0) === 60, 'just joined: 60s until it can be removed');
check(SWUSeatKickableIn($p, $t0 + 1) === 59, '1s later: 59s');
check(SWUSeatKickableIn($p, $t0 + 59) === 1, '59s later: 1s — the boundary is still protected');
check(SWUSeatKickableIn($p, $t0 + 60) === 0, 'exactly 60s later: removable');
check(SWUSeatKickableIn($p, $t0 + 3600) === 0, 'long after: removable (never negative)');

// Deliberate exemptions.
$legacy = new Player(3, 'deck');
$legacy->setJoinedAt(0);
check(SWUSeatKickableIn($legacy, $t0) === 0, 'a seat with no join time (a lobby from before this rule) is removable');

$bot = new Player(4, '');
$bot->setBotProfile('arenabot-normal');
$bot->setJoinedAt($t0);
check(SWUSeatKickableIn($bot, $t0) === 0, 'a BOT seat is removable at once — the host added it on purpose');

check(SWUSeatKickableIn(null, $t0) === 0, 'a non-seat is not protected');

// The join time must NOT move when the same person re-presents their key (a page reload): touch() is presence.
$p->touch($t0 + 30);
check($p->getJoinedAt() === $t0, 'touch() (a poll / a reload) does not restart the timer');

echo "\nALL PASS\n";
