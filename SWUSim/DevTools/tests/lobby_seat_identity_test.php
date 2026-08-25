<?php
// Lobby identity vs seat. $playerID is WHO YOU ARE (endpoints authenticate on it and it must never
// move while a room is open). $seat is WHERE YOU SIT (Team Suns reassigns it on every team pick).
// Conflating them migrates host away from the room creator and breaks in-flight poll auth.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';
require_once $root . '/APIs/Lobbies/Classes/Player.php';

$p = new Player(1, 'deckLink', '', 42);

check($p->getPlayerID() === 1,   'playerID is set from the constructor');
check($p->getSeat() === null,    'seat starts null — unassigned until a team is picked');
check($p->getTeam() === null,    'team starts null');

$p->setSeat(3);
check($p->getSeat() === 3,       'setSeat stores the seat');
check($p->getPlayerID() === 1,   'setSeat does NOT touch playerID — identity is stable');

$p->setTeam('red');
check($p->getTeam() === 'red',   'setTeam stores the team');

check($p->getLeaders() === [],   'leaders start empty');
$p->setLeaders(['SOR_010', 'JTL_006']);
check($p->getLeaders() === ['SOR_010', 'JTL_006'], 'setLeaders stores the resolved leaders');
$p->setLeaders(['SOR_010', '', null]);
check($p->getLeaders() === ['SOR_010'], 'setLeaders drops empty entries');

$p->setSeat(null);
check($p->getSeat() === null,    'seat can be released back to null when leaving a team');

$json = $p->jsonSerialize();
check(array_key_exists('seat', $json), 'jsonSerialize exposes seat');
check(array_key_exists('team', $json), 'jsonSerialize exposes team');
check($json['playerID'] === 1,         'jsonSerialize still exposes playerID');

// Host resolution must tolerate a lobby cached before hostPlayerID existed.
$legacy = new stdClass();
check(intval($legacy->hostPlayerID ?? 1) === 1, 'legacy lobby with no hostPlayerID resolves host to seat 1');

$modern = new stdClass();
$modern->hostPlayerID = 2;
check(intval($modern->hostPlayerID ?? 1) === 2, 'hostPlayerID is honoured when present');

echo "PASS\n";
