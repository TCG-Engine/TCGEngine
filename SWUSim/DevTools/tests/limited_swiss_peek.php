<?php
// REGRESSION-SKIP: a read-only helper for DevTools/ui-harness/swusim-limited-draft-sim.mjs --swiss, not a test.
// Every Swiss round's tables of a Limited room (the room poll only carries the current round), by lobby playerID:
//   ?lobbyID=…  →  {pairings: {round: [{a, b, bye, aName, bName, games: [a's, b's]}]}}
// Moderators only, like limited_fixture.php.
header('Content-Type: application/json');
chdir(__DIR__ . '/../../..');
include_once './AccountFiles/AccountSessionAPI.php';
$authError = CheckLoggedInUserMod();
if ($authError !== null && $authError !== '' && $authError !== false) { echo json_encode(['error' => 'moderators only']); exit; }
require_once './APIs/Lobbies/Classes/Player.php';
require_once './SWUSim/Limited/LimitedRoom.php';

$lobbyID = strval($_GET['lobbyID'] ?? '');
SWURoomRehydrate($lobbyID);
$l = apcu_fetch($lobbyID);
if (!SWUIsLimitedRoom($l)) { echo json_encode(['error' => 'not a Limited room']); exit; }
$lt = $l->limited;
$name = fn($p) => $p === null ? '' : strval($lt['seats'][$p]['name'] ?? '');
$out = [];
foreach (($lt['swiss']['pairings'] ?? []) as $n => $round) {
    $out[$n] = array_map(fn($x) => ['a' => $x['a'], 'b' => $x['b'], 'bye' => !empty($x['bye']), 'aName' => $name($x['a']), 'bName' => $name($x['b']),
                                    'games' => [intval($x['games'][$x['a']] ?? 0), $x['b'] === null ? 0 : intval($x['games'][$x['b']] ?? 0)]], $round);
}
echo json_encode(['pairings' => $out]);
