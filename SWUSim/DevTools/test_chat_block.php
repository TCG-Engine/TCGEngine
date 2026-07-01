<?php header('Content-Type: text/plain');
// curl http://localhost:3400/TCGEngine/SWUSim/DevTools/test_chat_block.php
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../Database/functions.inc.php';
require_once __DIR__ . '/../Match.php';
require_once __DIR__ . '/../MatchFlow.php';
$A=990030;$B=990031;
$c=GetLocalMySQLConnection();
$c->query("DELETE FROM blocklist WHERE blockingPlayer IN ($A,$B) OR blockedPlayer IN ($A,$B)");
$gn='blkchat1'; @mkdir(__DIR__.'/../Games/'.$gn,0777,true);
$mid=SWUCreateMatch('SWUSim','premier','bo1',[1=>['userId'=>$A,'authKey'=>'k1'],2=>['userId'=>$B,'authKey'=>'k2']]);
SWUWriteMatchRef($gn,$mid,1);
$pass=0;$fail=0; function ok($x,$m){global $pass,$fail; if($x){$pass++;}else{$fail++;echo "FAIL: $m\n";}}
ok(SWUAreGamePlayersBlocked($gn) === false, "not blocked initially");
AddBlock($A,$B);
ok(SWUAreGamePlayersBlocked($gn) === true, "blocked after AddBlock (live derive)");
ok(SWUAreGamePlayersBlocked('no_such_game') === false, "missing game -> false");
$c->query("DELETE FROM blocklist WHERE blockingPlayer IN ($A,$B) OR blockedPlayer IN ($A,$B)");
echo "\n$pass passed, $fail failed\n";
