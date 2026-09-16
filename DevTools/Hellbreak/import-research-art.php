<?php

// Card art from the HellbreakHub mirror: fill gaps, and upgrade a card when the hub has a better
// source than the workbook gave us.
//
//   php DevTools/Hellbreak/import-research-art.php --dry               # show what would change
//   php DevTools/Hellbreak/import-research-art.php --card=DOT_161      # one card
//   php DevTools/Hellbreak/import-research-art.php                     # fill gaps only
//   php DevTools/Hellbreak/import-research-art.php --download --prefer-better   # what the admin
//                                                                               # refresh runs
//
// WHY "prefer better" rather than "hub always wins": measured 2026-09-16 over the 155 cards that
// have both, the hub source is HIGHER resolution than our stored art for 68 and LOWER for 87. Most
// hub images are 750-768px against our 900px pipeline output, and the tail is worse (DOT_003 is
// 300px, DOT_011 458px). Replacing everything would upgrade 68 cards and visibly soften 87. So a
// card is only replaced when the hub source is actually wider than what is already stored.
//
// ONLY numerically named mirror files are considered:
//
//   001.webp   -> DOT_001          a normal card
//   011L.webp  -> DOT_011          a location; the L is the hub's suffix, not part of the ID
//   001b.webp  -> DOT_001_back     the back face (a monster's unleashed side)
//
// Everything else is deliberately excluded: token art (T1-T3), foil printings (*bloodfoil), alt art
// (179alt1), and the hub's word-id files (cronewitch, scubadiver, ...) which are the cards whose
// collector number is still unsettled and the likeliest to be redrawn rather than official art.
//
// Images go through the importer's own writeCardImages(), so WebpImages, concat and crops are all
// written — HellbreakDeck reflects the crops, and a plain file copy leaves its grid broken. That
// helper also rejects a non-card aspect ratio or a blank placeholder.
//
// ⚠ The mirror is a fan site's copy and a file's number is the hub's opinion: 167.webp is really the
// DOT_455 alt-art printing. Good enough to render; not a source for transcribing reviewed card data.
//
// The art root is gitignored. To move it to another machine use
// zzCodeGeneratorMain.php -> Generated card data -> Export archive.

declare(strict_types=1);

require_once __DIR__ . '/import-workbook.php'; // CLI block is guarded; only the helpers load.

const HELLBREAK_RESEARCH_IMAGE_DIR = '/_research/img';

/**
 * Refresh the local mirror from hellbreakhub.com.
 *
 * The file list is derived from the card dictionary, so this works on a machine that has never had a
 * mirror — prod included, where _research is gitignored and therefore absent. A file is fetched only
 * when it is missing or its sha256 no longer matches the manifest, so a re-run costs nothing, and the
 * manifest is rewritten as files arrive.
 */
function hellbreakDownloadResearchArt(string $sourceDir, bool $dryRun = false, string $set = 'DOT'): array
{
    $counts = ['fetched' => 0, 'current' => 0, 'failed' => 0, 'absent' => 0];
    ensureDirectory($sourceDir);
    $manifestPath = $sourceDir . '/manifest.json';
    $manifest = is_file($manifestPath)
        ? (json_decode((string)file_get_contents($manifestPath), true) ?: [])
        : [];
    $images = is_array($manifest['images'] ?? null) ? $manifest['images'] : [];

    // The work list comes from the CARD DICTIONARY, not the manifest. The research mirror is
    // gitignored, so on a fresh machine — prod especially — there is no manifest to read, and a
    // manifest-driven loop would quietly download nothing. The dictionary is always present.
    // The manifest is still used, for the sha256 that lets an unchanged file be skipped.
    $wanted = [];
    foreach (hellbreakResearchCardNumbers($set) as $number => $type) {
        $isLocation = strcasecmp($type, 'Location') === 0;
        $isMonster = strcasecmp($type, 'Monster') === 0;
        // Locations are published under both names; the plain one is the landscape original.
        $wanted[] = $number . '.webp';
        if ($isLocation) $wanted[] = $number . 'L.webp';
        if ($isMonster) $wanted[] = $number . 'b.webp';
    }
    // Anything the manifest already knows about stays in the list even if the dictionary lost it.
    foreach (array_keys($images) as $file) {
        if (preg_match('/^\d{1,3}L?b?\.webp$/i', (string)$file)) $wanted[] = (string)$file;
    }
    $wanted = array_values(array_unique($wanted));
    sort($wanted);

    $touched = false;
    foreach ($wanted as $file) {
        $path = $sourceDir . DIRECTORY_SEPARATOR . $file;
        $expected = strtolower(trim((string)($images[$file]['sha256'] ?? '')));
        if (is_file($path) && $expected !== '' && hash_file('sha256', $path) === $expected) {
            ++$counts['current'];
            continue;
        }
        if (is_file($path) && $expected === '') { ++$counts['current']; continue; } // present, unverifiable
        // The hub does not publish every name the dictionary asks for — a location has no plain
        // file, most cards have no back. Remember a 404 so a refresh does not re-ask every time.
        if (!empty($images[$file]['absent'])) { ++$counts['absent']; continue; }
        $url = trim((string)($images[$file]['url'] ?? ''))
            ?: 'https://hellbreakhub.com/cards/' . strtolower($set) . '/' . $file;
        if ($dryRun) { ++$counts['fetched']; continue; }
        try {
            $blob = downloadExternalImage($url);
            file_put_contents($path, $blob);
            $images[$file] = ['url' => $url, 'bytes' => strlen($blob), 'sha256' => hash('sha256', $blob)];
            $touched = true;
            ++$counts['fetched'];
        } catch (Throwable $error) {
            // A card the hub does not publish under this name is expected, not a failure to report
            // loudly — locations have no plain file, most cards have no back.
            if (str_contains($error->getMessage(), 'HTTP 404')) {
                $images[$file] = ['absent' => true, 'checkedAt' => gmdate('c')];
                $touched = true;
                ++$counts['absent'];
            } else {
                ++$counts['failed'];
            }
        }
    }
    if ($touched) {
        $manifest['images'] = $images;
        $manifest['source'] = $manifest['source'] ?? 'https://hellbreakhub.com/cards';
        $manifest['downloadedAt'] = gmdate('c');
        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }
    return $counts;
}

/** Base-card collector numbers for a set, as the hub names its files ("020", "011"). */
function hellbreakResearchCardNumbers(string $set): array
{
    $numbers = [];
    if (!function_exists('CardIds') && !function_exists('CardType')) return $numbers;
    for ($i = 1; $i <= 499; ++$i) {
        $cardID = strtoupper($set) . '_' . str_pad((string)$i, 3, '0', STR_PAD_LEFT);
        $type = function_exists('CardType') ? trim((string)CardType($cardID)) : '';
        if ($type === '') continue;
        if (function_exists('CardBaseCard') && trim((string)CardBaseCard($cardID)) !== '') continue;
        $numbers[str_pad((string)$i, 3, '0', STR_PAD_LEFT)] = $type;
    }
    return $numbers;
}

/**
 * Write mirror art into the card art root.
 *
 * $preferBetter replaces a card that already has art when the hub source is wider than the stored
 * image; without it only genuine gaps are filled. Nothing is ever replaced by a smaller source.
 */
function hellbreakImportResearchArt(array $options = []): array
{
    $repoRoot = dirname(__DIR__, 2);
    $target = $repoRoot . '/HellbreakSim';
    $sourceDir = (string)($options['source'] ?? $target . HELLBREAK_RESEARCH_IMAGE_DIR);
    $set = strtoupper((string)($options['set'] ?? 'DOT'));
    $onlyCard = strtoupper(trim((string)($options['card'] ?? '')));
    $dryRun = !empty($options['dry']);
    $preferBetter = !empty($options['preferBetter']);
    $log = [];

    $counts = ['written' => 0, 'upgraded' => 0, 'present' => 0, 'unknown' => 0,
               'variant' => 0, 'skipped' => 0, 'failed' => 0, 'lower' => 0];
    $sourceMapPath = $target . '/GeneratedCode/ArtSourceMap.json';
    $sourceMap = is_file($sourceMapPath)
        ? (json_decode((string)file_get_contents($sourceMapPath), true) ?: [])
        : [];
    if (!is_array($sourceMap)) $sourceMap = [];
    if (!is_dir($sourceDir)) { $counts['failed'] = -1; return ['counts' => $counts, 'log' => ['No research mirror at ' . $sourceDir]]; }
    if (!class_exists('Imagick')) { $counts['failed'] = -1; return ['counts' => $counts, 'log' => ['Imagick is unavailable.']]; }
    foreach (['WebpImages', 'concat', 'crops'] as $folder) ensureDirectory($target . DIRECTORY_SEPARATOR . $folder);

    // Group candidates per card first. The hub ships BOTH 013.webp and 013L.webp for some locations
    // — the same art landscape and rotated to portrait — and both map to DOT_013. Processing each in
    // turn makes them overwrite one another on alternate runs, and leaves the card in whichever
    // orientation happened to go last. Pick ONE file per card up front instead.
    $candidates = [];
    foreach ((scandir($sourceDir) ?: []) as $file) {
        if (!preg_match('/^(\d{1,3})(L?)(b?)\.webp$/i', $file, $matches)) {
            if (substr($file, -5) === '.webp') ++$counts['skipped'];
            continue;
        }
        $cardID = $set . '_' . str_pad($matches[1], 3, '0', STR_PAD_LEFT);
        $suffix = strtolower($matches[3]) === 'b' ? '_back' : '';
        $candidates[$cardID . '|' . $suffix][] = $file;
    }
    ksort($candidates);

    foreach ($candidates as $key => $group) {
        [$cardID, $suffix] = explode('|', $key, 2);
        if ($onlyCard !== '' && $cardID !== $onlyCard) continue;
        $file = count($group) === 1
            ? $group[0]
            : hellbreakPickBestCandidate($sourceDir, $group, strcasecmp((string)CardType($cardID), 'Location') === 0);

        // Only real base cards get art of their own; a variant shows its base card's.
        if (!function_exists('CardType') || trim((string)CardType($cardID)) === '') {
            ++$counts['unknown'];
            $log[] = "  unknown   {$file} -> {$cardID} (not in the card dictionary)";
            continue;
        }
        if (trim((string)CardBaseCard($cardID)) !== '') { ++$counts['variant']; continue; }

        $sourcePath = $sourceDir . DIRECTORY_SEPARATOR . $file;
        $destination = $target . '/WebpImages/' . $cardID . $suffix . '.webp';
        $sourceKey = $cardID . $suffix;
        $wantLandscape = strcasecmp((string)CardType($cardID), 'Location') === 0;
        $isUpgrade = false;

        // Orientation is correctness, not quality: a location stored portrait renders sideways on the
        // board. If what we hold is the wrong shape and this candidate is the right one, replace it
        // whatever the resolution says.
        $storedWrongWay = is_file($destination)
            && hellbreakImageIsLandscape($destination) !== $wantLandscape
            && hellbreakCandidateCanBeLandscape($sourcePath, $wantLandscape);
        if ($storedWrongWay) {
            $isUpgrade = true;
        } elseif (is_file($destination) && filesize($destination) >= 8000) {
            if (!$preferBetter) { ++$counts['present']; continue; }
            // Stored art is always capped at 900px wide, so "source is wider than stored" stays true
            // forever and would re-process the same file every run. The source map records which hub
            // file a card was last built from, which is what makes this converge.
            $sourceHash = hash_file('sha256', $sourcePath);
            if (($sourceMap[$sourceKey] ?? '') === $sourceHash) { ++$counts['present']; continue; }
            $sourceWidth = hellbreakImageWidth($sourcePath);
            $currentWidth = hellbreakImageWidth($destination);
            if ($sourceWidth <= 0 || $currentWidth <= 0 || $sourceWidth <= $currentWidth) {
                ++$counts[$sourceWidth > 0 && $currentWidth > 0 && $sourceWidth < $currentWidth ? 'lower' : 'present'];
                continue;
            }
            $isUpgrade = true;
        }

        $allowLandscape = strcasecmp((string)CardType($cardID), 'Location') === 0; // locations print landscape
        $verb = $isUpgrade ? 'upgrade' : 'write';
        if ($dryRun) {
            $log[] = "  would {$verb} {$file} -> {$cardID}{$suffix}";
            ++$counts[$isUpgrade ? 'upgraded' : 'written'];
            continue;
        }
        $blob = @file_get_contents($sourcePath);
        if ($blob === false || $blob === '') {
            ++$counts['failed'];
            $log[] = "  FAILED    {$file}: unreadable";
            continue;
        }
        $blob = hellbreakOrientBlob($blob, $wantLandscape);
        try {
            writeCardImages($blob, $target, $cardID . $suffix, $allowLandscape);
            // Hash the SOURCE FILE, not the blob — a rotated blob never matches the file we
            // compare against on the next run, so the card would re-process forever.
            $sourceMap[$sourceKey] = hash_file('sha256', $sourcePath);
            ++$counts[$isUpgrade ? 'upgraded' : 'written'];
            $log[] = "  " . str_pad($isUpgrade ? 'upgraded' : 'wrote', 9) . " {$file} -> {$cardID}{$suffix}";
        } catch (Throwable $error) {
            ++$counts['failed'];
            $log[] = "  FAILED    {$file} -> {$cardID}{$suffix}: " . $error->getMessage();
        }
    }
    if (!$dryRun && ($counts['written'] + $counts['upgraded']) > 0) {
        ksort($sourceMap);
        file_put_contents($sourceMapPath, json_encode($sourceMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }
    return ['counts' => $counts, 'log' => $log];
}

/**
 * Choose one mirror file when several map to the same card.
 *
 * Orientation decides it: a location is printed landscape and everything else portrait, so the
 * candidate matching the card's own shape wins regardless of size. Only then does resolution break
 * a tie. Without this the four GAMA locations flip between orientations on alternate runs.
 */
function hellbreakPickBestCandidate(string $sourceDir, array $files, bool $wantLandscape): string
{
    $best = null;
    $bestScore = [-1, -1];
    foreach ($files as $file) {
        try {
            $image = new Imagick($sourceDir . DIRECTORY_SEPARATOR . $file);
            $width = $image->getImageWidth();
            $height = $image->getImageHeight();
            $image->clear();
        } catch (Throwable $error) { continue; }
        if ($width < 1 || $height < 1) continue;
        $score = [($width > $height) === $wantLandscape ? 1 : 0, $width * $height];
        if ($score > $bestScore) { $bestScore = $score; $best = $file; }
    }
    return $best ?? $files[0];
}

function hellbreakImageIsLandscape(string $path): bool
{
    try {
        $image = new Imagick($path);
        $landscape = $image->getImageWidth() > $image->getImageHeight();
        $image->clear();
        return $landscape;
    } catch (Throwable $error) { return false; }
}

function hellbreakCandidateCanBeLandscape(string $path, bool $wantLandscape): bool
{
    // Every mirror file is either already the right shape or the same art rotated, so any readable
    // candidate can be oriented correctly.
    return hellbreakImageWidth($path) > 0;
}

/**
 * Put a blob the right way up for its card.
 *
 * The hub's "L" files are the landscape art rotated +90 (verified against the four locations that
 * ship both: rotating the portrait copy -90 reproduces the landscape one to within 0.0004 mean
 * absolute error). Locations print landscape; everything else portrait.
 */
function hellbreakOrientBlob(string $blob, bool $wantLandscape): string
{
    try {
        $image = new Imagick();
        $image->readImageBlob($blob);
        $isLandscape = $image->getImageWidth() > $image->getImageHeight();
        if ($isLandscape === $wantLandscape) { $image->clear(); return $blob; }
        $image->rotateImage(new ImagickPixel('none'), -90);
        $image->setImageFormat('webp');
        $rotated = $image->getImageBlob();
        $image->clear();
        return $rotated;
    } catch (Throwable $error) {
        return $blob;
    }
}

function hellbreakImageWidth(string $path): int
{
    try {
        $image = new Imagick($path);
        $width = $image->getImageWidth();
        $image->clear();
        return (int)$width;
    } catch (Throwable $error) {
        return 0;
    }
}

function hellbreakSummariseResearchArt(array $result, array $download = null): string
{
    $counts = $result['counts'];
    $lines = $result['log'];
    if ($download !== null) {
        $lines[] = "Hub download: {$download['fetched']} fetched, {$download['current']} already current, "
            . "{$download['absent']} not published by the hub, {$download['failed']} failed.";
    }
    $lines[] = "{$counts['written']} written · {$counts['upgraded']} upgraded · {$counts['present']} unchanged · "
        . "{$counts['lower']} kept (hub source is lower resolution) · {$counts['variant']} variant printings · "
        . "{$counts['unknown']} unknown ids · {$counts['skipped']} non-numeric files skipped · {$counts['failed']} failed";
    return implode("\n", $lines);
}

// ---------------------------------------------------------------------------
// CLI entry — skipped when this file is included by the admin endpoint.
// ---------------------------------------------------------------------------
if (realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    $repoRoot = dirname(__DIR__, 2);
    chdir($repoRoot);
    $args = getopt('', ['set::', 'card::', 'source::', 'dry', 'download', 'prefer-better', 'help']);
    if (isset($args['help'])) {
        echo "Usage: php DevTools/Hellbreak/import-research-art.php [--set=DOT] [--card=DOT_161]\n"
           . "       [--source=<dir>] [--download] [--prefer-better] [--dry]\n";
        exit(0);
    }
    require_once $repoRoot . '/HellbreakSim/GeneratedCode/GeneratedCardDictionaries.php';
    $sourceDir = (string)($args['source'] ?? $repoRoot . '/HellbreakSim' . HELLBREAK_RESEARCH_IMAGE_DIR);
    $download = isset($args['download'])
        ? hellbreakDownloadResearchArt($sourceDir, isset($args['dry']))
        : null;
    $result = hellbreakImportResearchArt([
        'set' => $args['set'] ?? 'DOT',
        'card' => $args['card'] ?? '',
        'source' => $sourceDir,
        'dry' => isset($args['dry']),
        'preferBetter' => isset($args['prefer-better']),
    ]);
    echo (isset($args['dry']) ? "[dry run]\n" : '') . hellbreakSummariseResearchArt($result, $download) . "\n";
    if (!isset($args['dry']) && ($result['counts']['written'] + $result['counts']['upgraded']) > 0) {
        echo "Art root is gitignored — move it with zzCodeGeneratorMain.php -> Generated card data -> Export archive.\n";
    }
}
