<?php
// sync-preview-images.php [--dry] [--all] [--set=HMW] [--quiet]
//
// Mirrors the mock_ preview card art of PLAYABLE preview cards from SWUSim/WebpImages/ into
// SWUSim/PreviewsImplemented/, so a public "Previews" page can show what is actually available to play
// without having to reason about implementation status at render time.
//
//   docker exec -w /var/www/html/TCGEngine <container> \
//     php -d xdebug.mode=off SWUSim/DevTools/sync-preview-images.php [--dry]
//
// "Playable" is the exact INVERSE of "scaffold-cards.php would propose a stub for it", so the two tools
// can never disagree: a card counts as playable when it is a token (engine-generic), or vanilla /
// keyword-only (auto-wired by the dictionaries), or referenced by QUOTED CardID somewhere under
// SWUSim/Custom/ (per-card file, monolith, or engine file). Data files that merely LIST CardIDs
// (CardMocks.php, CardTraitSupplement.php) opt out via the SCAFFOLD-IGNORE marker.
//
// The sync is a MIRROR, not an append: a mock_*.webp in the destination whose card is no longer playable
// — or whose source art is gone, which is what the preview-cleanup path does once official data lands —
// is pruned. Nothing outside the mock_*.webp naming is ever touched.
//
// Both faces are carried: mock_<CID>.webp and mock_<CID>_back.webp (leaders).
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

require __DIR__ . '/scaffold-cards.php';   // main-guarded; gives us the dictionaries + the coverage oracle

$repoRoot = getenv('REPO_ROOT') ?: (function () {
    $d = __DIR__;
    while ($d !== '/' && $d !== '' && !(is_dir("$d/SWUSim") && is_dir("$d/Core"))) $d = dirname($d);
    return $d;
})();

const PREVIEW_SRC_DIR  = 'SWUSim/WebpImages';
const PREVIEW_DEST_DIR = 'SWUSim/PreviewsImplemented';

// "mock_HMW_004_back.webp" -> "HMW_004"; null when the name isn't mock preview art.
function preview_cid_from_filename(string $file): ?string {
    if (!preg_match('/^mock_(.+)\.webp$/', $file, $m)) return null;
    $cid = preg_replace('/_back$/', '', $m[1]);
    return $cid !== '' ? $cid : null;
}

// [playable, reason]. Mirrors scaffold_plan's skip conditions — see the header note.
function preview_playability(string $cid, array $keywords, array $covered): array {
    if (preg_match('/_T\d\d$/', $cid)) return [true, 'token'];
    // A reprint is implemented by its canonical (earliest) printing's file.
    $canon = function_exists('CardIDOverride') ? CardIDOverride($cid) : $cid;
    if (isset($covered[$cid]) || ($canon !== $cid && isset($covered[$canon]))) return [true, 'implemented'];
    if (!scaffold_is_non_vanilla($cid, $keywords)) return [true, 'auto-wired'];
    return [false, 'not implemented'];
}

function preview_sync(string $repoRoot, bool $dry, bool $all, string $onlySet): array {
    $src  = $repoRoot . '/' . PREVIEW_SRC_DIR;
    $dest = $repoRoot . '/' . PREVIEW_DEST_DIR;
    if (!is_dir($src)) throw new RuntimeException("missing source dir: $src");
    if (!is_dir($dest) && !$dry && !@mkdir($dest, 0775, true)) {
        throw new RuntimeException("could not create dest dir: $dest");
    }

    $keywords = scaffold_keyword_names(__DIR__ . '/../GeneratedCode/GeneratedKeywordCode.php');
    $covered  = scaffold_covered_cids(__DIR__ . '/../Custom');

    $report = ['copied' => [], 'updated' => [], 'unchanged' => [], 'skipped' => [], 'pruned' => []];
    $wanted = [];   // basename => true, for the prune pass

    foreach (scandir($src) ?: [] as $file) {
        $cid = preview_cid_from_filename($file);
        if ($cid === null) continue;
        if ($onlySet !== '' && strtoupper(explode('_', $cid)[0]) !== strtoupper($onlySet)) continue;

        [$playable, $reason] = $all ? [true, 'forced (--all)'] : preview_playability($cid, $keywords, $covered);
        if (!$playable) { $report['skipped'][] = "$file ($cid: $reason)"; continue; }
        $wanted[$file] = true;

        $from = "$src/$file";
        $to   = "$dest/$file";
        // Content compare, not mtime: re-importing a preview rewrites the source with the same bytes.
        $same = is_file($to) && filesize($to) === filesize($from) && md5_file($to) === md5_file($from);
        if ($same) { $report['unchanged'][] = $file; continue; }
        $bucket = is_file($to) ? 'updated' : 'copied';
        if (!$dry && !@copy($from, $to)) throw new RuntimeException("copy failed: $file");
        $report[$bucket][] = "$file ($cid: $reason)";
    }

    // Prune: only ever mock_*.webp, and only entries this run did not want.
    foreach (is_dir($dest) ? (scandir($dest) ?: []) : [] as $file) {
        if (preview_cid_from_filename($file) === null) continue;   // never touch anything else
        if (isset($wanted[$file])) continue;
        if ($onlySet !== '') {
            $cid = preview_cid_from_filename($file);
            if (strtoupper(explode('_', $cid)[0]) !== strtoupper($onlySet)) continue;   // out of scope this run
        }
        if (!$dry && !@unlink("$dest/$file")) throw new RuntimeException("prune failed: $file");
        $report['pruned'][] = $file;
    }

    return $report;
}

if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__)) {
    $dry     = in_array('--dry', $argv, true);
    $all     = in_array('--all', $argv, true);
    $quiet   = in_array('--quiet', $argv, true);
    $onlySet = '';
    foreach ($argv as $a) if (preg_match('/^--set=(.+)$/', $a, $m)) $onlySet = strtoupper($m[1]);

    $r = preview_sync($repoRoot, $dry, $all, $onlySet);
    $tag = $dry ? '[dry] ' : '';
    if (!$quiet) {
        foreach (['copied', 'updated', 'pruned', 'skipped'] as $k) {
            foreach ($r[$k] as $line) echo "{$tag}" . str_pad($k, 9) . " $line\n";
        }
    }
    printf("%s%d copied, %d updated, %d unchanged, %d pruned, %d skipped (unimplemented)\n",
        $tag, count($r['copied']), count($r['updated']), count($r['unchanged']),
        count($r['pruned']), count($r['skipped']));
    echo $dry ? "dry run — nothing written\n" : PREVIEW_DEST_DIR . " is in sync\n";
}
