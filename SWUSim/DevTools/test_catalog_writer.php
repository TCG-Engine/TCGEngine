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

// ── Edit (rename) ─────────────────────────────────────────────────────────────────────────────────
// The label changes; the id, the asset path and isDefault must not — they are what every saved user
// selection points at, so a rename has to be invisible to players who already chose this cosmetic.
ok(SWUCatalogHasEntry('background','test-board',$tmp)===true, "HasEntry finds an existing entry");
ok(SWUCatalogHasEntry('background','never-added',$tmp)===false, "HasEntry rejects a missing entry");
ok(SWUCatalogUpdateEntryLabel('background','test-board','Renamed Board',$tmp)===true, "rename returns true");
$r = file_get_contents($tmp);
ok(strpos($r, "'test-board' => ['label'=>'Renamed Board', 'asset'=>'./Assets/Boards/SWUSim/test-board.webp', 'isDefault'=>false],")!==false,
   "rename changed ONLY the label (id + asset + isDefault intact)");
ok(strpos($r, "'Test Board'")===false, "old label gone");
ok(SWUCatalogUpdateEntryLabel('background','test-board',"Kanan's Board",$tmp)===true, "rename to an apostrophe label");
ok(strpos(file_get_contents($tmp), "'label'=>'Kanan\\'s Board'")!==false, "rename escapes the apostrophe");
ok(SWUCatalogUpdateEntryLabel('background','never-added','X',$tmp)===false, "rename of a missing id rejected");
ok(SWUCatalogUpdateEntryLabel('background','test-board','   ',$tmp)===false, "blank label rejected");
ok(SWUCatalogUpdateEntryLabel('bogus','test-board','X',$tmp)===false, "rename with a bad slot rejected");

// A COLUMN-ALIGNED built-in line (extra spaces before =>) must rename too — every shipped cardback
// entry looks like this, and a regex written only against the appended format would miss them all.
ok(SWUCatalogUpdateEntryLabel('cardback','against-the-galaxy','Against The Galaxy II',$tmp)===true, "rename an aligned built-in");
ok(strpos(file_get_contents($tmp), "'label'=>'Against The Galaxy II'")!==false, "aligned built-in label changed");

// ── SLOT SCOPING — the case that has no natural fixture ───────────────────────────────────────────
// Ids are unique only WITHIN a slot, so a background and a playmat may share one. A file-wide search
// would rewrite or delete whichever came first. No such pair exists in the shipped catalog today, so
// this builds one: without slot scoping these two assertions fail in opposite directions.
ok(SWUCatalogAppendEntry('background','twin-id','BG Twin','./Assets/Boards/SWUSim/twin-id.webp',$tmp)===true, "twin id in background");
ok(SWUCatalogAppendEntry('playmat','twin-id','Mat Twin','./Assets/Playmats/SWUSim/twin-id.webp',$tmp)===true, "twin id in playmat");
ok(SWUCatalogUpdateEntryLabel('playmat','twin-id','Mat Renamed',$tmp)===true, "rename the PLAYMAT twin");
$t = file_get_contents($tmp);
ok(strpos($t, "'label'=>'Mat Renamed'")!==false, "playmat twin renamed");
ok(strpos($t, "'label'=>'BG Twin'")!==false, "…and the background twin is UNTOUCHED");

// ── Delete ────────────────────────────────────────────────────────────────────────────────────────
ok(SWUCatalogDeleteEntry('background','twin-id',$tmp)===true, "delete returns true");
$d = file_get_contents($tmp);
ok(strpos($d, "'twin-id' => ['label'=>'BG Twin'")===false, "background twin line removed");
ok(strpos($d, "'label'=>'Mat Renamed'")!==false, "…and the playmat twin SURVIVED the delete");
ok(SWUCatalogDeleteEntry('background','twin-id',$tmp)===false, "deleting it twice is rejected");
ok(SWUCatalogDeleteEntry('background','never-added',$tmp)===false, "delete of a missing id rejected");
ok(SWUCatalogDeleteEntry('bogus','test-board',$tmp)===false, "delete with a bad slot rejected");
ok(SWUCatalogDeleteEntry('background','Bad Id',$tmp)===false, "delete with a non-kebab id rejected");

// The marker must survive every mutation, or nothing can be appended afterwards.
ok(strpos(file_get_contents($tmp), '//new backgrounds above this line')!==false, "slot marker intact after edit+delete");
ok(SWUCatalogAppendEntry('background','after-delete','After','./Assets/Boards/SWUSim/after-delete.webp',$tmp)===true,
   "append still works after an edit and a delete");

// …and the file is still valid PHP after all of it.
$lint2 = shell_exec('php -l ' . escapeshellarg($tmp) . ' 2>&1');
ok(strpos((string)$lint2, 'No syntax errors')!==false, "temp catalog still parses after edit+delete");
// The real catalog was never touched.
ok(file_get_contents($orig) === file_get_contents(__DIR__ . '/../Cosmetics/Catalog.php'), "real Catalog.php untouched");

@unlink($tmp); @unlink($noMarker);
echo "PASS=$pass FAIL=$fail\n";
