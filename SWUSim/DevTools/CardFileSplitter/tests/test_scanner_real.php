<?php
// Durable invariant: scanning each real monolith is lossless (statements + gaps
// reconstruct the source byte-for-byte) and produces no overlapping spans. This
// must hold no matter how the monoliths shrink during rollout.
require __DIR__ . '/../Scanner.php';

$fail = 0;
foreach (['CardDQHandlers','LeaderAbilities','BaseAbilities'] as $f) {
    $path = __DIR__ . "/../../../Custom/$f.php";
    if (!is_file($path)) { echo "SKIP $f (missing)\n"; continue; }
    $src = file_get_contents($path);
    $stmts = splitter_scan($src);

    $rebuilt = ''; $cursor = 0; $overlaps = 0;
    foreach ($stmts as $s) {
        [$a,$b] = $s['span'];
        if ($a < $cursor) { $overlaps++; continue; }
        $rebuilt .= substr($src, $cursor, $a - $cursor) . substr($src, $a, $b - $a);
        $cursor = $b;
    }
    $rebuilt .= substr($src, $cursor);

    $lossless = ($rebuilt === $src);
    $withCards = count(array_filter($stmts, fn($s)=>!empty($s['cardIDs'])));
    $topUses = count(array_filter($stmts, fn($s)=>!empty($s['topLevelUses'])));
    printf("%-16s %6d stmts (%d w/cardIDs, %d topUse) lossless=%s overlaps=%d\n",
        $f, count($stmts), $withCards, $topUses, $lossless?'YES':'NO', $overlaps);
    if (!$lossless || $overlaps > 0) $fail++;
}
if ($fail) { fwrite(STDERR, "FAIL: $fail monolith(s) not lossless/overlapping\n"); exit(1); }
echo "OK\n";
