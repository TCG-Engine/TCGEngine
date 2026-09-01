<?php header('Content-Type: text/plain');
// End-to-end cover for EDITING and DELETING a cosmetic — the half the mod tool was missing.
// The uploader could only ever ADD: the DB-era delete endpoint was dropped when the catalog moved
// into Catalog.php (2026-07-09), and the Mod landing page still advertised "add/remove".
//
// Runs the real endpoint LOGIC against a temp copy of Catalog.php, so nothing here can mutate the
// shipped catalog. The HTTP layer (mod gate + dev gate) is not exercised — those are shared with the
// existing upload path and unchanged.
require_once __DIR__ . '/../Cosmetics/CatalogWriter.php';
require_once __DIR__ . '/../Cosmetics/CosmeticAssets.php';
$pass=0;$fail=0; function ok($x,$m){global $pass,$fail; if($x){$pass++;}else{$fail++;echo "FAIL: $m\n";}}

$orig = __DIR__ . '/../Cosmetics/Catalog.php';
$tmp  = sys_get_temp_dir() . '/cos_edit_' . getmypid() . '.php';
copy($orig, $tmp);
$origBytes = file_get_contents($orig);

// Load the catalog from the TEMP file so isDefault lookups match what we mutate.
$catOf = function(string $path): array {
    $src = file_get_contents($path);
    $fn  = 'SWUCosmeticCatalog_' . substr(md5($src), 0, 8);
    if (!function_exists($fn)) {
        eval(str_replace('function SWUCosmeticCatalog()', "function {$fn}()",
             substr($src, strpos($src, 'function SWUCosmeticCatalog()'),
                    strpos($src, "\n}\n", strpos($src, 'function SWUCosmeticCatalog()')) + 3
                    - strpos($src, 'function SWUCosmeticCatalog()'))));
    }
    return $fn();
};

// ── The DEFAULT GUARD is the load-bearing rule ────────────────────────────────────────────────────
// SWUCosmeticResolve() falls back to the slot default whenever a saved choice is missing — that
// fallback is exactly what makes deleting anything else safe, so the default itself must be
// undeletable. Verified here as data (the endpoint refuses on `isDefault`).
$cat = $catOf($tmp);
foreach (['background'=>'default', 'cardback'=>'classic', 'playmat'=>'none'] as $slot => $expectId) {
    $defaults = array_keys(array_filter($cat[$slot], fn($o) => !empty($o['isDefault'])));
    ok(count($defaults) === 1, "$slot has exactly one default");
    ok(($defaults[0] ?? '') === $expectId, "$slot default is '$expectId'");
}

// ── Rename ────────────────────────────────────────────────────────────────────────────────────────
ok(SWUCatalogHasEntry('background','echo-base',$tmp), "echo-base exists to rename");
ok(SWUCatalogUpdateEntryLabel('background','echo-base','Echo Base Renamed',$tmp), "rename succeeds");
$after = file_get_contents($tmp);
ok(strpos($after, "'echo-base' => ['label'=>'Echo Base Renamed'")!==false, "label changed");
ok(strpos($after, "'asset'=>'./Assets/Boards/SWUSim/echo-base.webp'")!==false, "asset path UNCHANGED by rename");
ok(substr_count($after, "'echo-base' =>") === 1, "no duplicate entry created");

// A rename must leave the DEFAULT flag alone — renaming the default is allowed, un-defaulting it is not.
ok(SWUCatalogUpdateEntryLabel('background','default','Default Renamed',$tmp), "the default can be renamed");
// ⚠ Assert the PARTS, not the exact spacing: the shipped built-ins are column-aligned
// ('label'=>'Default',        'asset'=>…) and a rename deliberately preserves that alignment, so a
// literal single-space expectation fails against correct behaviour.
$defLine = '';
foreach (explode("\n", file_get_contents($tmp)) as $ln) { if (strpos($ln, "'default' =>") !== false) { $defLine = $ln; break; } }
ok(strpos($defLine, "'label'=>'Default Renamed'")!==false, "default label changed");
ok(strpos($defLine, "'isDefault'=>true")!==false, "…and it is STILL the default afterwards");
ok(strpos($defLine, "'asset'=>'./Assets/Boards/SWUSim/default.webp'")!==false, "…with its asset intact");

// ── Delete ────────────────────────────────────────────────────────────────────────────────────────
ok(SWUCatalogDeleteEntry('background','echo-base',$tmp), "delete succeeds");
$after2 = file_get_contents($tmp);
ok(strpos($after2, "'echo-base' =>")===false, "entry gone from the catalog");
ok(strpos($after2, "'death-star' =>")!==false, "its NEIGHBOUR survived (the line splice is exact)");

// ── The catalog is still loadable after every mutation ────────────────────────────────────────────
$lint = shell_exec('php -l ' . escapeshellarg($tmp) . ' 2>&1');
ok(strpos((string)$lint,'No syntax errors')!==false, "mutated catalog still parses");
$cat2 = $catOf($tmp);
ok(!isset($cat2['background']['echo-base']), "deleted id is absent from the loaded catalog");
ok(isset($cat2['background']['default']), "default still present");
ok(count($cat2['cardback']) === count($cat['cardback']), "other slots untouched by background edits");

// ── Staged replacement paths ──────────────────────────────────────────────────────────────────────
// A replace must never write over the live asset before the mod confirms, or Cancel is a lie.
$st = SWUCosmeticStagedAbs('background','death-star');
ok(is_string($st) && str_ends_with($st, '/Assets/Boards/SWUSim/death-star.staged.webp'), "staged path is a sibling of the live asset");
ok(SWUCosmeticStagedAbs('background','Bad Id') === null, "staged path rejects a non-kebab id");
ok(SWUCosmeticStagedAbs('bogus','death-star') === null, "staged path rejects a bad slot");
// A staged filename can never be mistaken for a cosmetic id (ids are kebab-case, no dots).
ok(preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', 'death-star.staged') === 0, "'<id>.staged' is not a legal id");
ok(SWUCosmeticCommitStaged('background','death-star') === false, "committing with nothing staged fails cleanly");

// ── The real catalog was never touched ────────────────────────────────────────────────────────────
ok(file_get_contents($orig) === $origBytes, "shipped Catalog.php byte-identical");

@unlink($tmp);
echo "PASS=$pass FAIL=$fail\n";
