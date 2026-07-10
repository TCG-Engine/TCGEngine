<?php header('Content-Type: text/plain');
require_once __DIR__ . '/../Cosmetics/CosmeticAssets.php';
$pass=0;$fail=0; function ok($x,$m){global $pass,$fail; if($x){$pass++;}else{$fail++;echo "FAIL: $m\n";}}

ok(SWUCosmeticSlotDir('background') === '/Assets/Boards/SWUSim/', "background dir");
ok(SWUCosmeticSlotDir('cardback')   === '/Assets/CardBacks/SWUSim/', "cardback dir");
ok(SWUCosmeticSlotDir('playmat')    === '/Assets/Playmats/SWUSim/', "playmat dir");
ok(SWUCosmeticSlotDir('bogus')      === null, "unknown slot dir null");

ok(SWUCosmeticAssetRel('background','naboo') === './Assets/Boards/SWUSim/naboo.webp', "asset rel path");
ok(SWUCosmeticAssetRel('bogus','naboo') === null, "bad slot -> null");
ok(SWUCosmeticAssetRel('background','') === null, "empty id -> null");

// delete: create dummy asset(s) under the repo, delete, verify gone (valid kebab id)
$repo = realpath(__DIR__ . '/../..');
$bg = $repo . '/Assets/Boards/SWUSim/tdel-test.webp';
$bgm = $repo . '/Assets/Boards/SWUSim/tdel-test-mobile.webp';
file_put_contents($bg, 'x'); file_put_contents($bgm, 'x');
ok(SWUCosmeticDeleteAsset('background','tdel-test') === true, "delete returns true");
ok(!is_file($bg) && !is_file($bgm), "background + mobile removed");
ok(SWUCosmeticDeleteAsset('background','Bad Id') === false, "bad id rejected");

echo "PASS=$pass FAIL=$fail\n";
