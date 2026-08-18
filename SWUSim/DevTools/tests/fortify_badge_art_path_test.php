<?php
// Bug #970 — "Fortify upgrades not showing image on preview".
//
// A base's attached FORTIFY upgrades are surfaced by the Base zone's
// `Counters: UpgradeCount=Badge(...,PopupFrom=UpgradeCardIDs)` (Schemas/SWUSim/GameSchema.txt), whose
// hover popup is built by Core/CounterRendering.js and rendered by showLineageOverflowPopup(), which
// re-appends "/concat/" to the folder it is handed.
//
// SWU card art is ONE shared corpus at AppCore/SWU/Images/{concat,WebpImages}, deliberately NOT under an
// app root — so that folder must be the CORPUS BASE. Deriving it from AssetReflectionPath()/window.rootPath
// yields the app root ("SWUSim") and requests ./SWUSim/concat/<id>.webp, a tree the shared-corpus
// migration deleted: every card in the popup 404s. It is NOT about preview cards — resolveCardImageID()
// has already applied the mock_ prefix by then; SOR_120 fails identically. Preview cards only LOOK
// correlated because Fortify exists solely in HMW today.
//
// Guarded here rather than in Tests/Cases because the failure is in shipped client JS, which the schema
// harness cannot execute. Tests/Visual/Fortify_BaseBadge_UpgradeArt.md carries the pixel evidence.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';
$js = file_get_contents($root . '/Core/CounterRendering.js');
check($js !== false && $js !== '', 'CounterRendering.js is readable');

// The PopupFrom badge branch — isolate it so the assertions cannot be satisfied by an unrelated
// assetImageFolder use elsewhere in the file (there is one at the effect-source badge, ~line 263).
$start = strpos($js, '$popupFrom = params.PopupFrom');
if ($start === false) $start = strpos($js, 'params.PopupFrom');
check($start !== false, 'the PopupFrom badge branch is present');
// Wide enough to span the whole PopupFrom block (the shared-corpus derivation carries a long
// explanatory comment); short enough not to reach the next counter type.
$branch = substr($js, $start, 3500);

check(strpos($branch, 'assetImageFolder') !== false,
      'the popup folder is derived from window.assetImageFolder (the shared SWU corpus), not the app root');
check(strpos($branch, 'AppCore/SWU/Images') !== false,
      'the shared-corpus base is recognised explicitly, mirroring Card()/subFolder');

// Non-SWU roots (GrandArchive etc.) must keep the reflection rewrite — this file is shared.
check(strpos($branch, 'AssetReflectionPath') !== false,
      'the non-SWU fallback still honours AssetReflectionPath');

// ORDER matters, not just presence: the shared-corpus branch must be consulted BEFORE the app-root
// fallback, or a regression that merely MENTIONS assetImageFolder while still letting rootPath win
// would satisfy the checks above and ship the 404 again.
$posShared = strpos($branch, 'AppCore/SWU/Images');
$posRootFallback = strpos($branch, 'window.rootPath');
check($posShared !== false && $posRootFallback !== false && $posShared < $posRootFallback,
      'the shared-corpus branch is taken BEFORE the app-root fallback');

// The other half of the contract: the generated transport still publishes the corpus path the fix
// reads. If this regresses, the fix silently falls back to the broken app-root form.
$ntr = @file_get_contents($root . '/SWUSim/NextTurnRender.php');
if ($ntr !== false && $ntr !== '') {
    check(preg_match("/window\.assetImageFolder = '([^']*)'/", $ntr, $m) === 1,
          'SWUSim still emits window.assetImageFolder');
    check(strpos($m[1], 'AppCore/SWU/Images') !== false,
          "emitted assetImageFolder points at the shared corpus (got: {$m[1]})");
} else {
    echo "  skip: SWUSim/NextTurnRender.php not generated here\n";
}

echo "PASS\n";
