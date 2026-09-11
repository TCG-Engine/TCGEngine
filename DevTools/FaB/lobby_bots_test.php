<?php
require_once __DIR__ . '/../../APIs/Lobbies/Classes/LobbyBots.php';
function expectBotTest($ok, $message) { if (!$ok) throw new RuntimeException($message); }
$host = new Player(1, ''); $host->setDeckOk(true); $host->setReady(true);
$room = (object)['rootName'=>'FaBSim', 'format'=>'upf', 'isPrivate'=>true, 'hostPlayerID'=>1,
    'players'=>[$host], 'numPlayers'=>1, 'state'=>'open'];
foreach (['bad-auth','bad-profile'] as $case) {
    try { LobbyAddBot($room, $case === 'bad-auth' ? '' : $host->getAuthKey(), $case === 'bad-profile' ? 'unknown' : 'goldfish');
        throw new RuntimeException('Invalid bot request accepted');
    } catch (InvalidArgumentException $e) {}
}
for ($i=0; $i<3; ++$i) LobbyAddBot($room, $host->getAuthKey(), 'goldfish');
expectBotTest(count($room->players) === 4, 'Missing bots');
expectBotTest(LobbyAdapterFor('FaBSim')->startBlockers($room) === [], 'Bots blocked start');
try { LobbyAddBot($room, $host->getAuthKey(), 'goldfish'); throw new RuntimeException('Overfilled room'); }
catch (InvalidArgumentException $e) {}
$bot = $room->players[1]; $bot->touch(1);
expectBotTest(!SWUSeatIsAway($bot), 'Bot appeared away');
$host->touch(1);
expectBotTest(!SWUMigrateHostIfAway($room), 'Bot became host');
$human = new Player(9,''); $room->players[3] = $human;
expectBotTest(SWUMigrateHostIfAway($room) && $room->hostPlayerID === 9, 'Host did not migrate to human');
array_shift($room->players); $room->hostPlayerID=1; SWUMigrateHostIfNeeded($room);
expectBotTest($room->hostPlayerID === 9, 'Departed host migrated to bot');
$room->state='starting';
try { LobbyAddBot($room, $human->getAuthKey(), 'goldfish'); throw new RuntimeException('Changed starting room'); }
catch (InvalidArgumentException $e) {}
echo "Lobby bot rules passed.\n";
$host = new Player(1, '');
$room = (object)['rootName'=>'FaBSim', 'format'=>'upf', 'isPrivate'=>true, 'hostPlayerID'=>1,
    'players'=>[$host], 'numPlayers'=>1, 'state'=>'open'];
LobbyAddBot($room, $host->getAuthKey(), 'goldfish', 4);
expectBotTest($room->players[1]->getSeat() === 4 && $host->getSeat() === 1, 'Requested slot not assigned');
foreach ([0, 1, 4, 5] as $seat) {
    try { LobbyAddBot($room, $host->getAuthKey(), 'goldfish', $seat); throw new RuntimeException('Invalid or occupied slot accepted'); }
    catch (InvalidArgumentException $e) {}
}
$human = new Player(3, ''); $room->players[]=$human;
LobbyEnsureFixedSeats($room);
expectBotTest($human->getSeat() === 2 && $room->players[1]->getSeat() === 4, 'Join shifted bot slot');
array_splice($room->players, 1, 1);
LobbyEnsureFixedSeats($room);
expectBotTest($human->getSeat() === 2, 'Removal shifted human slot');
echo "Explicit UPF bot slots passed.\n";
