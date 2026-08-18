<?php
// Every LOCAL .js/.css a page links must carry a cache-busting token, or a deploy can leave a client on
// a stale copy with nothing to force a refetch — a failure mode that is invisible on the server and
// looks like "the fix didn't work" to the player.
//
// Two mechanisms are in play and they are NOT interchangeable:
//   • ?v=<filemtime> via _VersionAsset() (SharedUI/Render/AssetVersion.php) — the default for everything.
//   • a datestamped FILENAME (Core/UILibraries<YYYYMMDD>.js, GeneratedUI_<ts>.js,
//     GeneratedCardDictionaries_<ts>.js) — the only lever that survives a CDN configured to ignore query
//     strings. UILibraries deliberately carries BOTH; see DevTools/bump-uilibraries-cache.py.
// A file whose name already encodes a timestamp is exempt; anything else must go through the helper.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = realpath(__DIR__ . '/../../..');

check(is_file($root . '/SharedUI/Render/AssetVersion.php'), 'the shared _VersionAsset seam exists');
$seam = file_get_contents($root . '/SharedUI/Render/AssetVersion.php');
check(strpos($seam, "function_exists('_VersionAsset')") !== false,
      'the seam is function_exists-guarded (safe to require from anywhere)');
// It used to live inside Render/Head.php, which meant only RenderHead pages could reach it — that is
// exactly why a dozen hand-written tags shipped unversioned.
$head = file_get_contents($root . '/SharedUI/Render/Head.php');
check(strpos($head, 'AssetVersion.php') !== false && preg_match('/^function _VersionAsset/m', $head) !== 1,
      'Head.php requires the seam rather than defining its own copy');

$skip = ['/Games/', '/node_modules/', '/.git/', '/Tests/', '/DevTools/'];
$offenders = [];
$scanned = 0;
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($rii as $f) {
    $p = $f->getPathname();
    if ($f->isDir() || substr($p, -4) !== '.php') continue;
    foreach ($skip as $s) if (strpos($p, $s) !== false) continue 2;
    $scanned++;
    foreach (file($p) as $ln => $line) {
        if (preg_match('~^\s*(//|\*|#)~', $line)) continue;                        // commented-out line
        if (!preg_match_all('~<(?:script|link)[^>]*?(?:src|href)\s*=\s*[\'"]([^\'"]+\.(?:js|css))(?:\?[^\'"]*)?[\'"]~i',
                            $line, $m)) continue;
        foreach ($m[0] as $k => $tag) {
            $url = $m[1][$k];
            if (preg_match('~^https?://|^//~i', $url)) continue;                   // external host
            if (strpos($tag, '?v=') !== false) continue;                           // already versioned
            if (strpos($url, '<?') !== false || strpos($url, '$') !== false) continue;  // built at runtime
            if (preg_match('~_?[0-9]{8}([0-9]{6})?\.(js|css)$~', $url)) continue;  // self-busting filename
            if (preg_match('~<!--.*' . preg_quote($url, '~') . '~', $line)) continue;   // inside a comment
            $offenders[] = str_replace($root . '/', '', $p) . ':' . ($ln + 1) . '  ' . $url;
        }
    }
}
sort($offenders);
foreach ($offenders as $o) fwrite(STDERR, "  unversioned: $o\n");
check(empty($offenders),
      "no unversioned local .js/.css tag in {$scanned} scanned PHP files (found " . count($offenders) . ")");

// Every path handed to the helper must actually exist: _VersionAsset is fail-safe and returns the bare
// path for a file it cannot stat, so a typo'd path loses its versioning SILENTLY rather than 404ing.
$bad = 0; $n = 0;
$rii2 = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($rii2 as $f) {
    $p = $f->getPathname();
    if ($f->isDir() || substr($p, -4) !== '.php') continue;
    if (strpos($p, '/Games/') !== false) continue;
    $src = file_get_contents($p);
    if (!preg_match_all('~_VersionAsset\(\s*\'([^\']+)\'~', $src, $m)) continue;
    foreach ($m[1] as $web) {
        $n++;
        if (preg_match('~^https?://~i', $web)) continue;
        if (!is_file($root . preg_replace('~^/TCGEngine~', '', $web))) {
            $bad++;
            fwrite(STDERR, "  missing: $web  (in " . str_replace($root . '/', '', $p) . ")\n");
        }
    }
}
check($bad === 0, "all {$n} literal _VersionAsset() paths resolve to a real file");

echo "PASS\n";
