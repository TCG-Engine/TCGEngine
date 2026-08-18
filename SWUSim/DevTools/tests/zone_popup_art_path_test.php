<?php
// Bug #971 — "card image in discard not showing either" (the sibling of #970).
//
// Clicking a pile opens the zone popup built by createPopupHTML() in Core/jsInclude.js — the modal
// titled "Their Discard" (the title is the zone name split on capitals). It hand-builds its art folder
// as "./" + <#folderPath input> + "/concat" = "./SWUSim/concat", then Card() runs that through the
// AssetReflectionPath rewrite (which returns 'SWUSim' here) and yields ./SWUSim/concat/<id>.webp.
//
// SWU card art is ONE shared corpus at AppCore/SWU/Images/{concat,WebpImages}; the per-app SWUSim/concat
// tree was deleted by the shared-corpus migration. Two different symptoms follow, and BOTH were reported:
//   • a checkout with no legacy tree  -> EVERY card in the popup 404s;
//   • an environment that still has the legacy tree -> only PREVIEW cards 404, because their art is
//     mock_-prefixed and was only ever synced into the shared corpus. That is the reported shape:
//     game 3342's theirDiscard is exactly [HMW_162 (preview), LOF_107 (released)].
//
// The fix is the same one #970 applied to the base-Fortify badge: prefer window.assetImageFolder, which
// NextTurnRender.php emits as the corpus concat path, and let Card()'s isSharedSWUArt guard keep it
// absolute and skip the reflection rewrite.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';
$js = file_get_contents($root . '/Core/jsInclude.js');
check($js !== false && $js !== '', 'Core/jsInclude.js is readable');

$start = strpos($js, 'function createPopupHTML');
check($start !== false, 'createPopupHTML is present');
$fn = substr($js, $start, 2500);
// Assert CODE, not prose: the explanatory comment names assetImageFolder too, so matching the raw
// text let a reverted fix still pass. (Caught by mutation — the first version of this guard was not
// load-bearing.) Strip // comment bodies before any assertion below.
$fn = preg_replace('~//[^\n]*~', '', $fn);

check(strpos($fn, 'PopulateZone(') !== false, 'the popup renders its cards through PopulateZone');
check(strpos($fn, 'assetImageFolder') !== false,
      'the popup art folder comes from window.assetImageFolder (the shared SWU corpus)');

// The bare hand-built form must not be what PopulateZone receives. It may survive as the NON-SWU
// fallback, so assert ORDER: the corpus is consulted first.
$posShared = strpos($fn, 'assetImageFolder');
$posHandBuilt = strpos($fn, 'folderPath + "/concat"');
if ($posHandBuilt === false) $posHandBuilt = strpos($fn, "folderPath + '/concat'");
check($posHandBuilt === false || $posShared < $posHandBuilt,
      'the shared-corpus folder is preferred over the hand-built app-root path');

// Other roots (GrandArchive, Gudnak, FaB) share this function and have no shared corpus — their
// per-app folder must still be reachable as the fallback.
check($posHandBuilt !== false, 'the per-app fallback is retained for non-SWU roots');

// The other half of the contract, same as #970: the transport still publishes what the fix reads.
$ntr = @file_get_contents($root . '/SWUSim/NextTurnRender.php');
if ($ntr !== false && $ntr !== '') {
    check(preg_match("/window\.assetImageFolder = '([^']*)'/", $ntr, $m) === 1,
          'SWUSim still emits window.assetImageFolder');
    check(strpos($m[1], 'AppCore/SWU/Images') !== false,
          "emitted assetImageFolder points at the shared corpus (got: {$m[1]})");
}
echo "PASS\n";
