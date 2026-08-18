<?php
// "previews don't show in Sideboard experience" — the third site of the shared-corpus art-path family
// (after #970's base-Fortify badge and #971's zone popup).
//
// SWUSim/Sideboard.php is a STANDALONE page: it does not load Core's Card()/resolveCardImageID, so it
// hand-built "./concat/<id>.webp" and "./WebpImages/<id>.webp" relative to /TCGEngine/SWUSim/. SWU art
// is one shared corpus at AppCore/SWU/Images/, and preview art is mock_-prefixed, so:
//   • released cards resolved only where the deleted per-app SWUSim/concat tree still exists;
//   • preview (HMW) cards NEVER resolved — the grid renders <img alt=id>, so they showed as bare
//     text ("HMW_061"), and the Leader/Base slots (no alt) showed a broken-image icon.
//
// The canonical seam is AppCore/SWU/CardImagePath.php: SWUCardImagePath() server-side and its JS twin
// window.swuCardArtUrl() (emitted by SWUCardArtScript). Both apply the mock_ prefix and the corpus root.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';
$src  = file_get_contents($root . '/SWUSim/Sideboard.php');
check($src !== false && $src !== '', 'SWUSim/Sideboard.php is readable');

// Strip // and # comment bodies: assert CODE, not the explanatory prose added with the fix.
$code = preg_replace('~//[^\n]*~', '', $src);

check(strpos($code, 'CardImagePath.php') !== false, 'the page includes the canonical card-art seam');
check(strpos($code, 'SWUCardImagePath(') !== false, 'server-rendered art (Leader/Base) uses SWUCardImagePath()');
check(strpos($code, 'SWUCardArtScript(') !== false, 'the JS twin window.swuCardArtUrl is emitted for the client');
check(strpos($code, 'swuCardArtUrl(') !== false, 'client-built art (grid + hover preview) uses window.swuCardArtUrl()');

// No hand-built per-app art paths may remain — these are what broke.
check(strpos($code, "'./concat/'") === false && strpos($code, '"./concat/"') === false
      && strpos($code, './concat/<') === false,
      'no hand-built ./concat/ path remains');
check(strpos($code, "'./WebpImages/'") === false && strpos($code, '"./WebpImages/"') === false,
      'no hand-built ./WebpImages/ path remains');

// The Leader/Base hover wiring used to recover the CardID by string-stripping its own src. That cannot
// survive a corpus URL with a mock_ prefix, so the id must be carried explicitly.
check(strpos($code, 'data-card-id') !== false,
      'the Leader/Base slots carry their CardID as data, not parsed back out of the image src');
check(strpos($code, "replace('./concat/'") === false,
      'the src-reverse-parse is gone');

echo "PASS\n";
