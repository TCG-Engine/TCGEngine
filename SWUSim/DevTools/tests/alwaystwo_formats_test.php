<?php
// Always Two (Doubles) formats — docs/superpowers/specs/2026-11-20-swusim-always-two-design.md.
// Four ids, one per mode × pool. Each reuses Premier's or Eternal's card pool and bans verbatim, is private-room only, never
// rated and records nothing. 1v1 = two seats, two decks per seat; 4P = exactly four seats in two teams.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php SWUSim/DevTools/tests/alwaystwo_formats_test.php
chdir(dirname(__DIR__, 3));
error_reporting(E_ALL & ~E_DEPRECATED);
require_once './AppCore/SWU/Formats.php';
require_once './AppCore/SWU/BoosterSets.php';
require_once './SWUSim/StatsSubmit.php';
require_once './SWUSim/SWUForgeDelivery.php';
require_once './SWUSim/GameSetupRules.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

$ids = [
    'at1v1-premier' => ['mode' => '1v1', 'base' => 'premier'],
    'at1v1-eternal' => ['mode' => '1v1', 'base' => 'eternal'],
    'at4p-premier'  => ['mode' => '4p',  'base' => 'premier'],
    'at4p-eternal'  => ['mode' => '4p',  'base' => 'eternal'],
];

foreach ($ids as $id => $want) {
    echo "$id:\n";
    $f = SWUGetFormat($id);
    $base = SWUGetFormat($want['base']);
    $check($f !== null, "$id is registered");
    if ($f === null) continue;
    $check(strlen($id) <= 16, "$id fits the stats varchar(16)");
    $check(SWUFormatLegalSets($id) === SWUFormatLegalSets($want['base']), "$id uses {$want['base']}'s legal sets");
    $check(($f['banned'] ?? null) === ($base['banned'] ?? null), "$id uses {$want['base']}'s ban list");
    $check(($f['minDeck'] ?? null) === ($base['minDeck'] ?? null) && ($f['maxCopies'] ?? null) === ($base['maxCopies'] ?? null)
        && ($f['leaderCount'] ?? null) === ($base['leaderCount'] ?? null), "$id uses {$want['base']}'s deckbuilding");
    $check(SWUFormatAlwaysTwoMode($id) === $want['mode'], "$id is Always Two mode {$want['mode']}");
    $check(SWUFormatDecksPerPlayer($id) === ($want['mode'] === '1v1' ? 2 : 1), "$id decks per player");
    $check(SWUFormatRecordsNothing($id), "$id records nothing");
    $check(SWUMatchStatsSkip(['format' => $id]) !== null, "$id never goes to SWUStats");
    $check(SWUForgeShouldEnqueue(['format' => $id]) === false, "$id never queues for SWU Forge");
    $check(!in_array($id, SWUStatsFormats(false), true), "$id is not a stats format");
    $check(!SWUFormatAllowsPublicQueue($id, true), "$id is never in a public queue");
    $check(!SWUFormatIsRated($id), "$id is never rated");
    // 1v1 shipped 2026-11-20 (plan Task 13); 4P waits for the partner vote (plan Phase 5).
    $check(!empty($f['enabled']) === ($want['mode'] === '1v1'), "$id is " . ($want['mode'] === '1v1' ? 'enabled' : 'still disabled'));
    if ($want['mode'] === '4p') {
        $check(SWUFormatSeatRange($id) === [4, 4], "$id is exactly four seats (no 3P)");
        $check(SWUFormatIsTeamFormat($id), "$id is a team format (red/blue room)");
    } else {
        $check(SWUFormatSeatRange($id) === [2, 2], "$id is exactly two seats");
        $check(!SWUFormatIsTeamFormat($id), "$id draws no red/blue teams in its 2-seat room");
    }
}

echo "Lobby:\n";
require_once './APIs/Lobbies/Classes/LobbyAdapter.php';
require_once './SWUSim/LobbyAdapter.php';
$adapter = new SWULobbyAdapter();
$room = function (string $format) { $o = new stdClass(); $o->format = $format; $o->isPrivate = true; $o->rootName = 'SWUSim';
    $o->maxPlayers = SWUGetFormat($format)['maxPlayers'] ?? 2; $o->queueType = 'bo1'; return $o; };
foreach (array_keys($ids) as $id) {
    $check($adapter->wantsWaitingRoom($room($id)), "$id gets a private waiting room");
    $check($adapter->houseRuleDefs($room($id)) === [], "$id offers no Twin Suns house rules");
}
$check($adapter->houseRuleDefs($room('teamsuns')) !== [], 'control: Team Suns still offers house rules');

echo "Controls:\n";
foreach (['premier', 'eternal', 'teamsuns', 'dualingsuns', 'twinsuns'] as $other) {
    $check(SWUFormatAlwaysTwoMode($other) === '', "$other is not Always Two");
    $check(SWUFormatDecksPerPlayer($other) === 1, "$other has one deck per player");
}
$check(!SWUFormatRecordsNothing('premier'), 'Premier still records');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
