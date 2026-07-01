<?php header('Content-Type: text/plain');
// curl http://localhost:3400/TCGEngine/SWUSim/DevTools/test_blocklist_db.php
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../Database/functions.inc.php';
$A = 990001; $B = 990002; $C = 990003;
$c = GetLocalMySQLConnection();
$c->query("DELETE FROM blocklist WHERE blockingPlayer IN ($A,$B,$C) OR blockedPlayer IN ($A,$B,$C)");
$pass=0;$fail=0; function ok($cond,$msg){ global $pass,$fail; if($cond){$pass++;}else{$fail++;echo "FAIL: $msg\n";} }

ok(AddBlock($A,$B) === true, "AddBlock returns true");
ok(AreUsersBlocked($A,$B) === true, "A->B blocked");
ok(AreUsersBlocked($B,$A) === true, "bilateral: B sees A blocked too");
ok(AreUsersBlocked($A,$C) === false, "unrelated pair not blocked");
ok(AddBlock($A,$A) === false, "cannot self-block");
AddBlock($A,$B); // duplicate is a no-op (INSERT IGNORE)
$detailed = LoadBlockedUsersDetailed($A);
ok(count($detailed) === 1 && $detailed[0]['id'] === $B, "detailed list has B once");
ok(RemoveBlock($A,$B) === true, "RemoveBlock returns true");
ok(AreUsersBlocked($A,$B) === false, "unblocked");
ok(AreUsersBlocked(0,$B) === false, "anonymous (0) no-ops");

$c->query("DELETE FROM blocklist WHERE blockingPlayer IN ($A,$B,$C) OR blockedPlayer IN ($A,$B,$C)");
echo "\n$pass passed, $fail failed\n";
