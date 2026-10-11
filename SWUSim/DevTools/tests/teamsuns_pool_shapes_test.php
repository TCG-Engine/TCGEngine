<?php
// STRUCTURAL GUARD — no hand-rolled `my*Arena` + `their*Arena` pool may exist in SWUSim/Custom.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/teamsuns_pool_shapes_test.php
//
// ─── WHY A STRUCTURAL TEST AND NOT MORE FIXTURES ────────────────────────────────────────────────
// In a TEAM game `their<Zone>` is the OPPONENT fan-out and EXCLUDES a teammate, so `my` + `their` no
// longer adds up to the table: a teammate's units land in NEITHER list and silently drop out of the
// pool. There is no error and no refusal — the card simply cannot see one seat. And because `team*`
// degrades to `my*` outside a team game, EVERY such fix is Premier byte-identical, so the whole
// 12k-section schema suite stays green whether the bug is present or not. Behavioural tests pin the
// SHAPES (core/TeamSuns_UnqualifiedPoolsIncludeTeammates.md); only a structural scan can pin the
// ABSENCE of the defect across ~700 card files.
//
// ⚠ THIS EXISTS BECAUSE THE SWEEP MISSED THE SAME DEFECT THREE TIMES, each time because the detector
// was written for one syntax and the codebase used several:
//   1. ZoneSearch comma-run   ZoneSearch('myGroundArena',F), ZoneSearch('mySpaceArena',F), …
//   2. array literal          foreach (['myGroundArena','mySpaceArena','theirGroundArena',…] as $z)
//   3. DOUBLE-quoted literal  foreach (["myGroundArena", …] as $z)        ← missed by a '-only regex
//   4. TWO-zone / arena pair  ['myGroundArena','theirGroundArena'] and ['my…'=>'their…'] maps
// A rescan reporting "0 remaining" after fix #1 was reporting only on the shape it could see; 98
// further occurrences across 78 files survived it. The detector below is deliberately quote-agnostic
// and count-agnostic: it matches any bracketed expression or ZoneSearch run mentioning BOTH sides.
//
// ⚠ IF THIS FAILS ON NEW CODE, the fix is usually NOT to add an exemption. Pick by the printed text:
//     "a unit" (unqualified)  -> team* + their*, or SWUAllUnits()
//     "a friendly unit"       -> team* only,     or SWUFriendlyUnits()
//     "a unit you control"    -> my* only,       or SWUControlledUnits()   (control is per-player)
//     "an enemy unit"         -> their* only     (already the fan-out)
//   and note the traps: "attack with a friendly unit" is controller-only ('my'), an ability COST is
//   paid from your own board ('my'), and a Piloting/keyword REMINDER is not the clause.

chdir(dirname(__DIR__, 3));
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

$root = './SWUSim/Custom';
$hits = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') continue;
    $src = @file_get_contents($file->getPathname());
    if ($src === false || strpos($src, 'theirGroundArena') === false && strpos($src, 'theirSpaceArena') === false) continue;

    // Any bracketed expression, or a run of 2-4 comma-separated ZoneSearch calls.
    $expr = '/\[[^\[\]]*\]|(?:ZoneSearch\([^()]*\)\s*,\s*){1,3}ZoneSearch\([^()]*\)/s';
    if (!preg_match_all($expr, $src, $ms, PREG_OFFSET_CAPTURE)) continue;
    foreach ($ms[0] as [$e, $off]) {
        if (!preg_match_all('/[\'"](my|their)(Ground|Space)Arena[\'"]/', $e, $zs)) continue;
        $sides = array_unique($zs[1]);
        if (!in_array('my', $sides, true) || !in_array('their', $sides, true)) continue;
        $line = substr_count(substr($src, 0, $off), "\n") + 1;
        $hits[] = str_replace('./SWUSim/Custom/', '', $file->getPathname()) . ':' . $line
                . '  ' . preg_replace('/\s+/', ' ', substr($e, 0, 80));
    }
}

$check(empty($hits), 'no hand-rolled my*Arena + their*Arena pool remains in SWUSim/Custom ('
    . count($hits) . ' found)');
foreach (array_slice($hits, 0, 30) as $h) echo "    $h\n";
if (count($hits) > 30) echo '    … and ' . (count($hits) - 30) . " more\n";

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
