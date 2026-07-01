<?php header('Content-Type: text/plain');
// curl http://localhost:3400/TCGEngine/SWUSim/DevTools/test_blocked_users_endpoint.php
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../Database/functions.inc.php';
$c = GetLocalMySQLConnection();
// Seed two real users we can block by username.
$ME = 990010; $T1 = 990011;
$c->query("DELETE FROM blocklist WHERE blockingPlayer=$ME OR blockedPlayer=$ME");
$c->query("INSERT IGNORE INTO users (usersId, usersUid, usersEmail, usersPwd) VALUES ($ME,'blk_me','m@x','x'),($T1,'blk_target','t@x','x')");
$pass=0;$fail=0; function ok($c2,$m){ global $pass,$fail; if($c2){$pass++;}else{$fail++;echo "FAIL: $m\n";} }

$GLOBALS['__BLOCKED_TEST'] = true;
$GLOBALS['__BLOCKED_TEST_UID'] = $ME;
function run($action,$post){ $_POST = array_merge(['action'=>$action], $post); return include __DIR__.'/../BlockedUsers.php'; }

$r = run('add', ['username'=>'blk_target']);
ok(!empty($r['success']) && count($r['blocks'])===1 && $r['blocks'][0]['username']==='blk_target', "add by username");
$r = run('add', ['username'=>'no_such_user_xyz']);
ok(empty($r['success']) && $r['error']==='user_not_found', "add unknown username -> user_not_found");
$r = run('add', ['username'=>'blk_me']);
ok(empty($r['success']) && $r['error']==='cannot_block_self', "cannot block self");
$r = run('list', []);
ok(count($r['blocks'])===1, "list shows one block");
$r = run('remove', ['blockedId'=>(string)$T1]);
ok(!empty($r['success']) && count($r['blocks'])===0, "remove clears it");

// --- blockOpponent (server-resolved, with a seeded match) ---
require_once __DIR__ . '/../Match.php';
require_once __DIR__ . '/../MatchFlow.php';
$ME2 = $ME; $OPP = 990012;
$c->query("INSERT IGNORE INTO users (usersId, usersUid, usersEmail, usersPwd) VALUES ($OPP,'blk_opp','o@x','x')");
$c->query("DELETE FROM blocklist WHERE blockingPlayer=$ME2 OR blockedPlayer=$ME2");
$gn = 'blktest1';
@mkdir(__DIR__ . '/../Games/' . $gn, 0777, true);
$mid = SWUCreateMatch('SWUSim','premier','bo1', [
  1=>['userId'=>$ME2,'authKey'=>'k1'], 2=>['userId'=>$OPP,'authKey'=>'k2']]);
SWUWriteMatchRef($gn, $mid, 1);
$GLOBALS['__BLOCKED_TEST_UID'] = $ME2;
$r = run('blockOpponent', ['gameName'=>$gn]);
ok(!empty($r['success']) && $r['forfeited']===false, "blockOpponent (Bo1) succeeds, no forfeit");
ok(AreUsersBlocked($ME2,$OPP) === true, "opponent now blocked");

$c->query("DELETE FROM blocklist WHERE blockingPlayer=$ME OR blockedPlayer=$ME");
$c->query("DELETE FROM users WHERE usersId IN ($ME,$T1,$OPP)");
echo "\n$pass passed, $fail failed\n";
