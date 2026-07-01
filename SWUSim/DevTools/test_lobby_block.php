<?php header('Content-Type: text/plain');
// curl http://localhost:3400/TCGEngine/SWUSim/DevTools/test_lobby_block.php
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../Database/functions.inc.php';
require_once __DIR__ . '/../../APIs/Lobbies/JoinQueue_blocklib.php';
$A=990020;$B=990021;
$c=GetLocalMySQLConnection();
$c->query("DELETE FROM blocklist WHERE blockingPlayer IN ($A,$B) OR blockedPlayer IN ($A,$B)");
AddBlock($A,$B);
$pass=0;$fail=0; function ok($x,$m){global $pass,$fail; if($x){$pass++;}else{$fail++;echo "FAIL: $m\n";}}
ok(SWUJoinBlocked($A, $B) === true, "joiner A blocked-with host B");
ok(SWUJoinBlocked($A, 990099) === false, "unrelated host not blocked");
ok(SWUJoinBlocked(0, $B) === false, "anonymous joiner never blocked");
// SWULobbyHostUserId reads players[0]->getUserId()
$host = new class { public function getUserId(){ return 990021; } };
$lobby = (object)['players'=>[$host]];
ok(SWULobbyHostUserId($lobby) === 990021, "host userId read from players[0]");
ok(SWULobbyHostUserId((object)['players'=>[]]) === 0, "empty lobby -> 0");
$c->query("DELETE FROM blocklist WHERE blockingPlayer IN ($A,$B) OR blockedPlayer IN ($A,$B)");
echo "\n$pass passed, $fail failed\n";
