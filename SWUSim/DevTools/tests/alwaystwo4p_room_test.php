<?php
// 4P Always Two — the room (docs/superpowers/specs/2026-11-20-swusim-always-two-design.md, "## 4P Always Two" → Room): a 4-seat team
// room (Red/Blue, the Team Suns room flow), exactly 4 humans, one deck each, NO bots ("it does have to 4P" — owner, 2026-11-20), and
// it cannot start with fewer than 4. Control: a Team Suns room keeps offering bots.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php SWUSim/DevTools/tests/alwaystwo4p_room_test.php
$fails = 0;
function check($cond, $msg) { global $fails; if (!$cond) { $fails++; echo "FAIL: $msg\n"; } else echo "PASS: $msg\n"; }

$root = __DIR__ . '/../../..';
require_once $root . '/AppCore/SWU/Formats.php';
require_once $root . '/APIs/Lobbies/Classes/Player.php';
require_once $root . '/APIs/Lobbies/Classes/TeamRooms.php';
require_once $root . '/APIs/Lobbies/Classes/LobbyAdapter.php';
require_once $root . '/SWUSim/LobbyAdapter.php';
$a = new SWULobbyAdapter();

function room(string $format, int $humans): object {
    $o = new stdClass();
    $o->format = $format; $o->isPrivate = true; $o->rootName = 'SWUSim'; $o->queueType = 'bo1';
    $o->maxPlayers = SWUFormatDefinitions()[$format]['maxPlayers'] ?? 2;
    $o->players = [];
    for ($i = 1; $i <= $humans; $i++) {
        $p = new Player($i, 'deck-' . $i, '', null);
        $p->setDeckOk(true); $p->setReady(true); $p->setSeat($i); $p->setTeam($i % 2 === 1 ? 'red' : 'blue');
        $p->setUsername('claudebot' . $i);
        $o->players[] = $p;
    }
    return $o;
}

foreach (['at4p-premier', 'at4p-eternal'] as $fmt) {
    check($a->botProfiles(room($fmt, 2)) === [], "$fmt offers no room bots (exactly 4 humans)");
    check($a->seatModel(room($fmt, 2))['teams'] === ['red', 'blue'], "$fmt seats Red / Blue teams");
    $b3 = $a->startBlockers(room($fmt, 3));
    check(in_array('Need 4 players to start.', $b3, true), "$fmt with 3 people cannot start: " . json_encode($b3));
    check($a->startBlockers(room($fmt, 4)) === [], "$fmt with 4 people on two teams of 2 can start: " . json_encode($a->startBlockers(room($fmt, 4))));
}
check($a->botProfiles(room('teamsuns', 2)) !== [], 'control: a Team Suns room still offers bots');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
