<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_table_match_facts.php
// Twin Suns rematch needs to REBUILD a table from its match record — the original lobby has long expired by the end of
// a game. So a multi-seat match must keep, per seat, which seats are bots (+ their name) and each seat's team, and the
// host seat. A 2-seat match record must stay exactly as it was. docs/superpowers/specs/2026-10-20-twinsuns-rematch-design.md
header('Content-Type: text/plain');
include __DIR__ . '/../../SWUSim/MatchFlow.php';
require_once __DIR__ . '/../../APIs/Lobbies/Classes/Player.php';

$j = json_decode(file_get_contents(__DIR__ . '/../../SWUSim/DevTools/tests/fixtures/twinsuns_deck.json'), true);
$lines = ['Leader', '1 ' . $j['leader']['id'], '1 ' . $j['secondleader']['id'], 'Base', '1 ' . $j['base']['id'], 'Deck'];
foreach ($j['deck'] as $c) $lines[] = $c['count'] . ' ' . $c['id'];
$ts = implode("\n", $lines);

$mk = function ($id, $deck, $user = '', $bot = '') {
    $p = new Player($id, $deck, '', null);
    if ($user !== '') $p->setUsername($user);
    if ($bot !== '') $p->setBotProfile($bot);
    return $p;
};

$checks = [];
$lobby = new stdClass();
$lobby->isPrivate = true; $lobby->format = 'twinsuns'; $lobby->queueType = 'bo1'; $lobby->hostPlayerID = 1;
$lobby->players = [$mk(1, $ts, 'claudebot1'), $mk(2, $ts), $mk(3, $ts, 'Arenabot Alpha', 'precon:x')];
$mid = SWUCreateMatchFromLobby($lobby);
$m = $mid ? SWUReadMatch($mid) : null;
$checks['multi-seat match created'] = is_array($m);
$checks['bot seat keeps its profile'] = ($m['players']['3']['botProfile'] ?? null) === 'precon:x';
$checks['bot seat keeps its name'] = ($m['players']['3']['username'] ?? null) === 'Arenabot Alpha';
$checks['a human keeps their name'] = ($m['players']['1']['username'] ?? null) === 'claudebot1';
$checks['a human has no botProfile key'] = is_array($m) && !array_key_exists('botProfile', $m['players']['1']);
$checks['a guest has no username key'] = is_array($m) && !array_key_exists('username', $m['players']['2']);
$checks['the host seat is kept'] = ($m['hostSeat'] ?? null) === 1;

// Team Suns: each seat's team.
$tl = new stdClass();
$tl->isPrivate = true; $tl->format = 'teamsuns'; $tl->queueType = 'bo1'; $tl->hostPlayerID = 2;
$tl->players = [];
foreach ([1, 2, 3, 4] as $s) { $p = $mk($s, $ts); $p->setSeat($s); $p->setTeam($s % 2 === 1 ? 'red' : 'blue'); $tl->players[] = $p; }
$tid = SWUCreateMatchFromLobby($tl);
$tm = $tid ? SWUReadMatch($tid) : null;
$checks['team seats keep their team'] = ($tm['players']['1']['team'] ?? null) === 'red' && ($tm['players']['4']['team'] ?? null) === 'blue';
$checks['team match keeps its host seat'] = ($tm['hostSeat'] ?? null) === 2;

// A 2-seat match record is unchanged: no new keys.
$cards = ['JTL_100','LOF_100','SEC_100','LAW_100','ASH_100','IBH_010','JTL_101','LOF_101','SEC_101','LAW_101','ASH_101','IBH_011','JTL_102','LOF_102','SEC_102','LAW_102'];
$dl = ["Leader","JTL_001","Base","JTL_023","Deck"]; foreach ($cards as $c) $dl[] = "3 $c"; $dl[] = "1 JTL_103"; $dl[] = "1 LOF_103";
$pl = new stdClass();
$pl->isPrivate = true; $pl->format = 'premier'; $pl->queueType = 'bo1'; $pl->hostPlayerID = 1;
$pl->players = [$mk(1, implode("\n", $dl), 'claudebot1'), $mk(2, implode("\n", $dl), 'claudebot2')];
$pid = SWUCreateMatchFromLobby($pl);
$pm = $pid ? SWUReadMatch($pid) : null;
$checks['2-seat: no hostSeat key'] = is_array($pm) && !array_key_exists('hostSeat', $pm);
$checks['2-seat: seat record has no new keys'] = is_array($pm)
    && array_keys($pm['players']['1']) === ['originalDeck', 'authKey', 'userId', 'deckIdentity', 'deckLink', 'cosmetics'];

echo (count(array_filter($checks)) === count($checks))
   ? "PASS (" . count($checks) . " checks)\n"
   : "FAIL: " . implode(', ', array_keys(array_filter($checks, fn($v) => !$v))) . "\n  flash=" . GetFlashMessage() . "\n";
