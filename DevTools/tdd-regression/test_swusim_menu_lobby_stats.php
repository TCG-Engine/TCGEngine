<?php
// The main menu's mode cards claimed "4 players in queue" and "2 tables forming" — mockup
// fixtures, hardcoded in MainMenu.php since the redesign. They must be DERIVED from the live
// lobbies instead.
//
// SWUMenuLobbyStats() is the pure derivation: it takes the same lobby rows APIs/Lobbies/
// GetLobbies.php builds, plus a seat-range lookup, and answers what the two cards should say.
// Kept pure so this test can drive every branch without APCu, a web server or a real lobby.
//
// The rules it encodes, and why:
//   · a PRIVATE lobby is not in any queue a visitor can join, and counting it would both lie and
//     advertise that a private room exists;
//   · a MATCHED lobby has already become a game — it belongs to Games in Progress, not a queue;
//   · the PvP / multiplayer split is read from the FORMAT's own maxPlayers, never a hardcoded id
//     list, so twinsuns, teamsuns and their preview variants are all covered the day they exist;
//   · PvP counts PLAYERS (a queue is people waiting), multiplayer counts TABLES (a 3-4 seat room
//     is the thing you join).
//
// Run: docker exec -w /var/www/html/TCGEngine <c> php DevTools/tdd-regression/test_swusim_menu_lobby_stats.php
header('Content-Type: text/plain');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

chdir(dirname(__DIR__, 2));
require_once './SWUSim/Custom/MenuLobbyStats.php';

$PASS = 0; $FAIL = 0; $MSGS = [];
function check($name, $cond, $detail = '') {
    global $PASS, $FAIL, $MSGS;
    if ($cond) { $PASS++; return; }
    $FAIL++; $MSGS[] = "FAIL: $name" . ($detail !== '' ? "  [$detail]" : '');
}

if (!function_exists('SWUMenuLobbyStats')) {
    echo "FAIL: SWUMenuLobbyStats() does not exist\nRED\n";
    exit(1);
}

// A stand-in for SWUFormatSeatRange: premier is 2-up, twinsuns/teamsuns are tables.
//
// ⚠ IT RETURNS A LIST, [min, max] — because that is what the real SWUFormatSeatRange() returns.
// The first version of this fake returned ['minPlayers'=>…,'maxPlayers'=>…], an associative shape
// that exists nowhere in production. The fake and the code agreed, all 25 checks were green, and
// a live Twin Suns table was being counted as two PvP players the whole time — the end-to-end
// gate (DevTools/ui-harness/swusim-menu2-modestats.mjs) is what caught it. A fake whose shape is
// invented tests the fake.
$seats = function (string $format): array {
    $m = ['premier' => 2, 'eternal' => 2, 'padawan' => 2, 'twinsuns' => 4, 'teamsuns' => 4,
          'twinsunspreview' => 4];
    return [2, $m[$format] ?? 2];
};
$L = function ($format, $numPlayers, $isPrivate = false, $state = 'open') {
    return ['format' => $format, 'numPlayers' => $numPlayers, 'isPrivate' => $isPrivate,
            'state' => $state];
};

// ─── nothing going on ────────────────────────────────────────────────────────
$r = SWUMenuLobbyStats([], $seats);
check('empty: no pvp players', ($r['pvpPlayers'] ?? -1) === 0, json_encode($r));
check('empty: no pvp lobbies', ($r['pvpLobbies'] ?? -1) === 0, json_encode($r));
check('empty: no tables',      ($r['multiTables'] ?? -1) === 0, json_encode($r));

// ─── PvP counts PLAYERS across lobbies, not lobbies ──────────────────────────
$r = SWUMenuLobbyStats([$L('premier', 1), $L('premier', 1), $L('eternal', 1)], $seats);
check('pvp sums players across lobbies', ($r['pvpPlayers'] ?? -1) === 3, json_encode($r));
check('pvp also reports the lobby count', ($r['pvpLobbies'] ?? -1) === 3, json_encode($r));
check('pvp lobbies are not tables',       ($r['multiTables'] ?? -1) === 0, json_encode($r));

// ─── multiplayer counts TABLES, not players ──────────────────────────────────
$r = SWUMenuLobbyStats([$L('twinsuns', 2), $L('teamsuns', 3)], $seats);
check('multi counts tables, not seats', ($r['multiTables'] ?? -1) === 2, json_encode($r));
check('a table is not a pvp player',    ($r['pvpPlayers'] ?? -1) === 0, json_encode($r));

// ─── the split is the FORMAT's seat range, not an id list ────────────────────
// A format this test has never heard of, with a 4-seat range, must land on the table side.
$wide = function (string $f): array { return [3, 4]; };
$r = SWUMenuLobbyStats([$L('some_future_multiplayer_format', 2)], $wide);
check('an unknown 3-4 seat format counts as a table', ($r['multiTables'] ?? -1) === 1, json_encode($r));
check('...and not as a pvp player',                   ($r['pvpPlayers'] ?? -1) === 0, json_encode($r));

// ─── exclusions ──────────────────────────────────────────────────────────────
$r = SWUMenuLobbyStats([$L('premier', 1, true), $L('twinsuns', 2, true)], $seats);
check('a private lobby is not in the pvp queue', ($r['pvpPlayers'] ?? -1) === 0, json_encode($r));
check('a private room is not a forming table',   ($r['multiTables'] ?? -1) === 0, json_encode($r));

$r = SWUMenuLobbyStats([$L('premier', 2, false, 'matched'), $L('twinsuns', 4, false, 'matched')], $seats);
check('a matched lobby has left the queue',  ($r['pvpPlayers'] ?? -1) === 0, json_encode($r));
check('a matched room is no longer forming', ($r['multiTables'] ?? -1) === 0, json_encode($r));

// ─── mixed, the realistic case ───────────────────────────────────────────────
$r = SWUMenuLobbyStats([
    $L('premier', 1), $L('premier', 1, true), $L('eternal', 1),
    $L('twinsuns', 2), $L('twinsuns', 3, false, 'matched'), $L('teamsuns', 1),
], $seats);
check('mixed: 2 public pvp players', ($r['pvpPlayers'] ?? -1) === 2, json_encode($r));
check('mixed: 2 forming tables',     ($r['multiTables'] ?? -1) === 2, json_encode($r));

// ─── junk in the cache must not throw or inflate ─────────────────────────────
$r = SWUMenuLobbyStats([['nonsense' => true], $L('premier', 1), null, 'string'], $seats);
check('malformed rows are skipped, the good one still counts',
    ($r['pvpPlayers'] ?? -1) === 1 && ($r['pvpLobbies'] ?? -1) === 1, json_encode($r));

// A negative or absurd numPlayers cannot drag the queue below zero or inflate it: the value comes
// from a cache entry anyone's session can write.
$r = SWUMenuLobbyStats([$L('premier', -5), $L('premier', 999)], $seats);
check('a negative seat count cannot go below zero', ($r['pvpPlayers'] ?? -1) >= 0, json_encode($r));
check('an absurd seat count is clamped to the format',
    ($r['pvpPlayers'] ?? -1) <= 2 + 2, json_encode($r));

// ─── THE DEFAULT, against the REAL format registry ───────────────────────────
// No injected callable: this is the code path production actually runs. Every check above uses a
// fake, and a fake can agree with a wrong implementation — it did, and a live Twin Suns table was
// counted as two PvP players while all of them were green. These checks cannot be fooled that
// way, because SWUFormatSeatRange() is the real thing.
$r = SWUMenuLobbyStats([$L('twinsuns', 2)]);
check('REAL registry: twinsuns is a table', ($r['multiTables'] ?? -1) === 1, json_encode($r));
check('REAL registry: twinsuns is not a pvp queue', ($r['pvpPlayers'] ?? -1) === 0, json_encode($r));
$r = SWUMenuLobbyStats([$L('teamsuns', 3)]);
check('REAL registry: teamsuns is a table', ($r['multiTables'] ?? -1) === 1, json_encode($r));
$r = SWUMenuLobbyStats([$L('premier', 1)]);
check('REAL registry: premier is a pvp queue', ($r['pvpPlayers'] ?? -1) === 1, json_encode($r));
check('REAL registry: premier is not a table',  ($r['multiTables'] ?? -1) === 0, json_encode($r));

// The fake and the real function must have the SAME SHAPE, or the checks above prove nothing.
$real = SWUFormatSeatRange('twinsuns');
check('SWUFormatSeatRange returns a [min, max] LIST', array_keys($real) === [0, 1], json_encode($real));
check('...and twinsuns really does seat more than 2', intval($real[1] ?? 0) > 2, json_encode($real));

// ─── the card copy ───────────────────────────────────────────────────────────
// The cards say something in EVERY state, including zero — a blank foot reads as a broken card.
check('pvp label, several', SWUMenuStatLabel('pvp', ['pvpPlayers' => 4, 'pvpLobbies' => 3, 'multiTables' => 0]) === '4 players in queue',
    SWUMenuStatLabel('pvp', ['pvpPlayers' => 4, 'pvpLobbies' => 3, 'multiTables' => 0]));
check('pvp label, one is singular', SWUMenuStatLabel('pvp', ['pvpPlayers' => 1, 'pvpLobbies' => 1, 'multiTables' => 0]) === '1 player in queue',
    SWUMenuStatLabel('pvp', ['pvpPlayers' => 1, 'pvpLobbies' => 1, 'multiTables' => 0]));
check('pvp label, empty says so', SWUMenuStatLabel('pvp', ['pvpPlayers' => 0, 'pvpLobbies' => 0, 'multiTables' => 0]) === 'No one in queue',
    SWUMenuStatLabel('pvp', ['pvpPlayers' => 0, 'pvpLobbies' => 0, 'multiTables' => 0]));
check('multi label, several', SWUMenuStatLabel('multi', ['pvpPlayers' => 0, 'pvpLobbies' => 0, 'multiTables' => 2]) === '2 tables forming',
    SWUMenuStatLabel('multi', ['pvpPlayers' => 0, 'pvpLobbies' => 0, 'multiTables' => 2]));
check('multi label, one is singular', SWUMenuStatLabel('multi', ['pvpPlayers' => 0, 'pvpLobbies' => 0, 'multiTables' => 1]) === '1 table forming',
    SWUMenuStatLabel('multi', ['pvpPlayers' => 0, 'pvpLobbies' => 0, 'multiTables' => 1]));
check('multi label, empty says so', SWUMenuStatLabel('multi', ['pvpPlayers' => 0, 'pvpLobbies' => 0, 'multiTables' => 0]) === 'No tables forming',
    SWUMenuStatLabel('multi', ['pvpPlayers' => 0, 'pvpLobbies' => 0, 'multiTables' => 0]));

echo implode("\n", $MSGS);
echo ($MSGS ? "\n\n" : '') . "PASS=$PASS FAIL=$FAIL\n" . ($FAIL ? "RED\n" : "ALL GREEN\n");
exit($FAIL ? 1 : 0);
