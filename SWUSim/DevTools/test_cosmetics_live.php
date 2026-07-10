<?php header('Content-Type: text/plain');
require_once __DIR__ . '/../CosmeticsBridge.php';
require_once __DIR__ . '/../MatchFlow.php';
$pass=0;$fail=0; function ok($x,$m){global $pass,$fail; if($x){$pass++;}else{$fail++;echo "FAIL: $m\n";}}

ok(is_file(__DIR__ . '/../CosmeticsLive.php'), "CosmeticsLive.php endpoint exists");

// Fixture match: seat 1 = user 111 (home-one playmat), seat 2 = user 222 (sor-key-art playmat).
$matchId = SWUNextMatchId();
$c1 = SWUResolveSeatCosmetics(null); $c1['playmat'] = SWUCosmeticResolve('playmat','home-one');
$c2 = SWUResolveSeatCosmetics(null); $c2['playmat'] = SWUCosmeticResolve('playmat','sor-key-art');
SWUWriteMatch(['matchId'=>$matchId,'rootName'=>'SWUSim','players'=>[
  '1'=>['userId'=>111,'cosmetics'=>$c1], '2'=>['userId'=>222,'cosmetics'=>$c2]]]);
$gameName = 'testcoslive_' . $matchId;
$gameDir = __DIR__ . '/../Games/' . $gameName; @mkdir($gameDir, 0775, true);
SWUWriteMatchRef($gameName, $matchId, 1);

// Viewer is seat 1 -> their own playmat is home-one, opponent (seat 2) is sor-key-art.
$p = SWUBuildCosmeticsPayload($gameName, 1, 111, false);
ok(array_keys($p) === ['background','myCardBack','theirCardBack','myPlaymat','theirPlaymat'], "payload keys exact");
ok(strpos($p['myPlaymat'], 'home-one') !== false, "viewer(seat1) myPlaymat = home-one");
ok(strpos($p['theirPlaymat'], 'sor-key-art') !== false, "viewer(seat1) theirPlaymat = opponent's sor-key-art");

// Schema-editor dev override: testschema authKey forces seat 2's playmat to overwhelming-barrage.
ok(SWUCosmeticSeatOverrides('testschema') === ['2'=>['playmat'=>'overwhelming-barrage']], "testschema -> seat2 overwhelming-barrage");
ok(SWUCosmeticSeatOverrides('') === [], "no override for normal authKey");
$ov = SWUCosmeticSeatOverrides('testschema');
// Viewer as seat 1: seat 2 is 'theirs' -> theirPlaymat forced to overwhelming-barrage (no match needed).
$po = SWUBuildCosmeticsPayload('no_game', 1, '', false, $ov);
ok(strpos($po['theirPlaymat'], 'overwhelming-barrage') !== false, "override: seat1 viewer sees P2 overwhelming-barrage as theirs");
// Viewer as seat 2: seat 2 is 'mine' -> myPlaymat forced.
$po2 = SWUBuildCosmeticsPayload('no_game', 2, '', false, $ov);
ok(strpos($po2['myPlaymat'], 'overwhelming-barrage') !== false, "override: seat2 viewer sees P2 overwhelming-barrage as mine");

@unlink(SWUMatchPath($matchId));
@unlink($gameDir . '/MatchRef.json'); @rmdir($gameDir);
echo "PASS=$pass FAIL=$fail\n";
