<?php header('Content-Type: text/plain');
require_once __DIR__ . '/../Cosmetics/CatalogWriter.php';
$pass=0;$fail=0; function ok($x,$m){global $pass,$fail; if($x){$pass++;}else{$fail++;echo "FAIL: $m\n";}}

// Work on a temp copy of the real catalog so the test never mutates it.
$orig = __DIR__ . '/../Cosmetics/Catalog.php';
$tmp  = sys_get_temp_dir() . '/catalog_test_' . getmypid() . '.php';
copy($orig, $tmp);

ok(SWUCatalogAppendEntry('background','test-board','Test Board','./Assets/Boards/SWUSim/test-board.webp',$tmp)===true, "append returns true");
$after = file_get_contents($tmp);
ok(strpos($after, "'test-board' => ['label'=>'Test Board', 'asset'=>'./Assets/Boards/SWUSim/test-board.webp', 'isDefault'=>false],")!==false, "entry written verbatim");
ok(strpos($after, "'test-board'") < strpos($after, "//new backgrounds above this line"), "entry is above the marker");

// Label with an apostrophe must be escaped and still parse.
ok(SWUCatalogAppendEntry('cardback','fett-back',"Fett's Back",'./Assets/CardBacks/SWUSim/fett-back.webp',$tmp)===true, "append apostrophe label");
$after2 = file_get_contents($tmp);
ok(strpos($after2, "'label'=>'Fett\\'s Back'")!==false, "apostrophe escaped");

// The mutated temp file still parses.
$lint = shell_exec('php -l ' . escapeshellarg($tmp) . ' 2>&1');
ok(strpos((string)$lint, 'No syntax errors')!==false, "temp catalog still parses");

// Rejections.
ok(SWUCatalogAppendEntry('bogus','x','X','./x.webp',$tmp)===false, "invalid slot rejected");
ok(SWUCatalogAppendEntry('background','Bad Id','X','./x.webp',$tmp)===false, "non-kebab id rejected");
$noMarker = sys_get_temp_dir() . '/nomarker_' . getmypid() . '.php';
file_put_contents($noMarker, "<?php\n// nothing here\n");
ok(SWUCatalogAppendEntry('background','x','X','./x.webp',$noMarker)===false, "missing marker rejected");

@unlink($tmp); @unlink($noMarker);
echo "PASS=$pass FAIL=$fail\n";
