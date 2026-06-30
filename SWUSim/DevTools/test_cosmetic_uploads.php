<?php header('Content-Type: text/plain');
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../Database/functions.inc.php';
require_once __DIR__ . '/../Cosmetics/Catalog.php';
$c=GetLocalMySQLConnection(); $c->query("DELETE FROM cosmeticupload WHERE id LIKE '\_t\_%'");
$pass=0;$fail=0; function ok($x,$m){global $pass,$fail; if($x){$pass++;}else{$fail++;echo "FAIL: $m\n";}}

ok(AddCosmeticUpload('playmat','_t_mat','Test Mat','./Assets/Playmats/SWUSim/_t_mat.webp',7)===true, "add row");
// merged catalog picks it up (note: memoized per request — fine in a fresh request)
$cat = SWUCosmeticCatalog();
ok(isset($cat['playmat']['_t_mat']) && $cat['playmat']['_t_mat']['uploaded']===true, "catalog merges uploaded row");
ok(SWUCosmeticResolve('playmat','_t_mat')['asset']==='./Assets/Playmats/SWUSim/_t_mat.webp', "resolve uploaded asset");
ok($cat['playmat']['none']['isDefault']===true, "built-in still present");
$asset = DeleteCosmeticUpload('playmat','_t_mat');
ok($asset==='./Assets/Playmats/SWUSim/_t_mat.webp', "delete returns asset");
ok(DeleteCosmeticUpload('playmat','_t_mat')===null, "delete missing -> null");
ok(DeleteCosmeticUpload('playmat','none')===null, "cannot delete a built-in (not in table)");
echo "PASS=$pass FAIL=$fail\n";
