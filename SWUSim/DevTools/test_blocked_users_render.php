<?php header('Content-Type: text/plain');
// curl http://localhost:3400/TCGEngine/SWUSim/DevTools/test_blocked_users_render.php
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../Database/functions.inc.php';
require_once __DIR__ . '/../../SharedUI/Render/BlockedUsers.php';
$ME=990040;$T=990041;
$c=GetLocalMySQLConnection();
$c->query("DELETE FROM blocklist WHERE blockingPlayer=$ME");
$c->query("INSERT IGNORE INTO users (usersId,usersUid,usersEmail,usersPwd) VALUES ($T,'blk_render','r@x','x')");
$pass=0;$fail=0; function ok($x,$m){global $pass,$fail; if($x){$pass++;}else{$fail++;echo "FAIL: $m\n";}}
ok(trim(RenderBlockedUsers(0)) === '', "logged-out renders nothing");
$html = RenderBlockedUsers($ME);
ok(strpos($html,'blocked-user-add')!==false, "has username add input");
AddBlock($ME,$T);
$html = RenderBlockedUsers($ME);
ok(strpos($html,'blk_render')!==false, "lists blocked username");
ok(strpos($html,'Unblock')!==false, "has Unblock control");
$c->query("DELETE FROM blocklist WHERE blockingPlayer=$ME");
$c->query("DELETE FROM users WHERE usersId=$T");
echo "\n$pass passed, $fail failed\n";
