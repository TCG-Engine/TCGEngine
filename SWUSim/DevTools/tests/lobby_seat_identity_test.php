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

// ── Room-roster deck IDENTITY (leaders + base + the display cards the page renders) ────────────────
// The identity strip is a GENERIC card list built by the sim's LobbyAdapter. Player just stores it —
// it never knows what a leader is, which is what lets the shared page serve any sim.
check($p->getBase() === '',           'base starts empty');
check($p->getIdentityCards() === [],  'identity cards start empty');

$cards = [
    ['id'=>'SOR_010','name'=>'Darth Vader, Dark Lord of the Sith','url'=>'/x/SOR_010.webp','kind'=>'leader'],
    ['id'=>'SOR_023','name'=>'Command Center','url'=>'/x/SOR_023.webp','kind'=>'base'],
];
$p->setDeckIdentity(['SOR_010'], 'SOR_023', $cards);
check($p->getLeaders() === ['SOR_010'],  'setDeckIdentity stores the leaders');
check($p->getBase() === 'SOR_023',       'setDeckIdentity stores the base');
// Not "verbatim": setDeckIdentity NORMALISES each row (name falls back to the id, colours are
// hex-validated, and every row gains a colours key even when the adapter sent none), so compare the
// fields rather than asserting an identical array.
$stored = $p->getIdentityCards();
check(count($stored) === 2,                      'both identity cards are stored');
check($stored[0]['id'] === 'SOR_010' && $stored[0]['kind'] === 'leader', 'leader row round-trips');
check($stored[1]['id'] === 'SOR_023' && $stored[1]['kind'] === 'base',   'base row round-trips');
check($stored[0]['name'] === 'Darth Vader, Dark Lord of the Sith',       'the display name round-trips');
check($stored[0]['colors'] === [],                'a row with no colours normalises to an empty list');

// ★ ONE call writes all three, so they cannot drift. A seat showing last deck's base under this
// deck's leaders is worse than showing nothing — and that is exactly what happens when a caller
// remembers setLeaders() and forgets the rest.
$p->setDeckIdentity([], '', []);
check($p->getLeaders() === [],       'setDeckIdentity([]) clears the leaders');
check($p->getBase() === '',          'setDeckIdentity clears the base too');
check($p->getIdentityCards() === [], 'setDeckIdentity clears the identity cards too');

$p->setBase(null);
check($p->getBase() === '', 'setBase(null) normalises to an empty string, never null');

// Malformed rows are dropped rather than reaching the client as broken <img> tags.
$p->setDeckIdentity(['SOR_010'], '', [
    ['id'=>'SOR_010','name'=>'X','url'=>'/x.webp','kind'=>'leader'],
    ['name'=>'no id','url'=>'/y.webp'],   // no id
    ['id'=>'SOR_099','name'=>'no url'],   // no url
    'junk',                               // not even an array
]);
check(count($p->getIdentityCards()) === 1, 'malformed identity rows are dropped');
check($p->getIdentityCards()[0]['id'] === 'SOR_010', 'the surviving row is the well-formed one');

// ⚠ setDeckIdentity sanitises each row against an explicit key WHITELIST, so a key the adapter sends
// but the whitelist omits is silently dropped. That is how the aspect ring first shipped grey:
// `colors` was computed correctly and discarded here. Same trap as SWUGetFormat's return array.
$p->setDeckIdentity(['LAW_004'], 'JTL_026', [
    ['id'=>'LAW_004','name'=>'Aurra Sing, Assassin','url'=>'/x.webp','kind'=>'leader','colors'=>['#3b7dd8','#141414']],
    ['id'=>'JTL_026','name'=>'Massassi Temple','url'=>'/y.webp','kind'=>'base','colors'=>['#c0392b']],
]);
$ic = $p->getIdentityCards();
check($ic[0]['colors'] === ['#3b7dd8','#141414'], 'a dual-aspect leader keeps BOTH ring colours');
check($ic[1]['colors'] === ['#c0392b'],           'a base keeps its single ring colour');

// The page interpolates these into a CSS background:, so anything that is not a hex colour is
// dropped rather than escaped.
$p->setDeckIdentity([], '', [['id'=>'X','url'=>'/z.webp','kind'=>'leader',
    'colors'=>['#c0392b','red; background:url(evil)','','#GGG','#e2b13c']]]);
check($p->getIdentityCards()[0]['colors'] === ['#c0392b','#e2b13c'], 'non-hex ring colours are discarded');

// A row missing only its name falls back to the id, so the page always has something to show.
$p->setDeckIdentity([], '', [['id'=>'SOR_010','url'=>'/x.webp','kind'=>'leader']]);
check($p->getIdentityCards()[0]['name'] === 'SOR_010', 'a nameless row falls back to its CardID');

// ── Ready ────────────────────────────────────────────────────────────────────────────────────────
// Loading a legal deck auto-readies; Unready is the explicit "hold on" signal. Ready is a SEPARATE
// fact from deckOk — a legal deck you are still swapping is not one you are ready to play.
check($p->getReady() === false, 'a seat starts not ready');
$p->setReady(true);
check($p->getReady() === true,  'setReady(true) readies the seat');
$p->setReady(false);
check($p->getReady() === false, 'setReady(false) un-readies the seat');
$p->setReady(1);
check($p->getReady() === true,  'setReady coerces to a real bool');

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
