<?php header('Content-Type: text/plain');
require_once __DIR__ . '/../CosmeticsBridge.php';   // pulls MatchFlow, Match, Catalog
require_once __DIR__ . '/../MatchFlow.php';
$pass=0;$fail=0; function ok($x,$m){global $pass,$fail; if($x){$pass++;}else{$fail++;echo "FAIL: $m\n";}}

// Build a minimal match with two seats owned by users 111 and 222.
$matchId = SWUNextMatchId();
$match = [
  'matchId'=>$matchId, 'rootName'=>'SWUSim', 'players'=>[
    '1'=>['userId'=>111, 'cosmetics'=>SWUResolveSeatCosmetics(null)],
    '2'=>['userId'=>222, 'cosmetics'=>SWUResolveSeatCosmetics(null)],
  ],
];
SWUWriteMatch($match);
$gameName = 'testcospatch_' . $matchId;
// Ref lives at SWUSim/Games/<gameName>/MatchRef.json — the game dir must exist to write it.
$gameDir = __DIR__ . '/../Games/' . $gameName;
@mkdir($gameDir, 0775, true);
SWUWriteMatchRef($gameName, $matchId, 1);

// Patch seat 1 (user 111) playmat -> home-one.
ok(SWUPatchMatchSeatCosmetic($gameName, 111, 'playmat', 'home-one') === true, "patch returns true for owning seat");
$m = SWUReadMatch($matchId);
ok($m['players']['1']['cosmetics']['playmat']['id'] === 'home-one', "seat 1 playmat updated");
ok($m['players']['2']['cosmetics']['playmat']['id'] === 'none', "seat 2 untouched");

// A non-seat user cannot patch anything.
ok(SWUPatchMatchSeatCosmetic($gameName, 999, 'playmat', 'sor-key-art') === false, "non-seat user -> false");
$m = SWUReadMatch($matchId);
ok($m['players']['1']['cosmetics']['playmat']['id'] === 'home-one', "no seat changed by non-seat user");

// No match for this game -> false, no error.
ok(SWUPatchMatchSeatCosmetic('no_such_game_xyz', 111, 'playmat', 'home-one') === false, "missing match -> false");

// Cleanup
@unlink(SWUMatchPath($matchId));
@unlink($gameDir . '/MatchRef.json'); @rmdir($gameDir);
echo "PASS=$pass FAIL=$fail\n";
