<?php
// TDD guard for the Generator Workspace "Generated card data" transfer.
//
// The point of this feature is moving a sim's normalized card cache between machines WITHOUT an
// official API: */GeneratedCode is gitignored, so cardArrayCache.json cannot ride in the repo.
// The archive layer must therefore (a) round-trip byte-exactly, (b) accept a hand-rolled tarball
// rooted at GeneratedCode/ so an existing tar needs no repacking, and (c) refuse anything that
// would blank the dictionaries or cross-contaminate apps.
//
//   docker exec -w /var/www/html/TCGEngine otmtcge-hellbreaksim-web-server-1 \
//     php DevTools/tdd-regression/test_generated_card_data_transfer.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
include_once './DevTools/GeneratedCardDataArchive.php';

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

// Rejections are the bulk of this suite; assert on the message so a wrong-but-passing guard
// (e.g. "empty cache" caught by the JSON check) still fails the test.
$rejects = function (callable $run, string $expectFragment, string $msg) use ($check) {
    try {
        $run();
        $check(false, "$msg (no exception thrown)");
    } catch (Throwable $error) {
        $check(
            stripos($error->getMessage(), $expectFragment) !== false,
            "$msg — got: " . $error->getMessage()
        );
    }
};

$work = sys_get_temp_dir() . '/gcdt-' . getmypid();
@mkdir($work, 0777, true);
$path = function (string $name) use ($work) { return $work . '/' . $name; };

// A realistic cache: the generator only ever cares that cardArray is a non-empty list, but the
// bytes must survive untouched — the dictionaries are generated straight off these values.
$cacheJson = json_encode([
    'cardArray' => [
        ['id' => 'HB_001', 'name' => 'Ashen Herald', 'text' => "Line one\nLine \"two\" — em dash & ampersand"],
        ['id' => 'HB_002', 'name' => 'Grave Tithe', 'text' => 'Unicode: ✦ ☠ 日本語'],
    ],
    'reprintMap' => [],
    'leaderUnitByUUIDMap' => [],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

// Builds a .tar (optionally gzipped) with each [entryName => contents]. Mirrors how a human would
// tar up a GeneratedCode directory by hand.
$makeTar = function (string $baseName, array $entries, bool $gzip = false) use ($work) {
    $tarPath = $work . '/' . $baseName . '.tar';
    @unlink($tarPath); @unlink($tarPath . '.gz');
    $archive = new PharData($tarPath);
    foreach ($entries as $entryName => $contents) $archive->addFromString($entryName, $contents);
    unset($archive);
    if (!$gzip) return $tarPath;
    $reopened = new PharData($tarPath);
    $reopened->compress(Phar::GZ);
    unset($reopened);
    return $tarPath . '.gz';
};

$makeZip = function (string $baseName, array $entries) use ($work) {
    $zipPath = $work . '/' . $baseName . '.zip';
    @unlink($zipPath);
    $zip = new ZipArchive();
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($entries as $entryName => $contents) $zip->addFromString($entryName, $contents);
    $zip->close();
    return $zipPath;
};

// ── export ───────────────────────────────────────────────────────────────────
$exported = GeneratedCardDataArchive::export('HellbreakSim', $cacheJson, '2026-08-14T17:09:56Z');
$exportPath = $path('exported.zip');
file_put_contents($exportPath, $exported);

$zip = new ZipArchive();
$opened = $zip->open($exportPath) === true;
$check($opened, 'export() produces a readable zip');
$manifest = $opened ? json_decode((string)$zip->getFromName('manifest.json'), true) : null;
$check($opened && $zip->getFromName('cardArrayCache.json') === $cacheJson, 'exported zip carries the cache byte-exactly');
$check(is_array($manifest) && ($manifest['app'] ?? '') === 'HellbreakSim', 'manifest names the source app');
$check(is_array($manifest) && ($manifest['cardCount'] ?? -1) === 2, 'manifest reports the card count (2)');
$check(is_array($manifest) && ($manifest['exportedAt'] ?? '') === '2026-08-14T17:09:56Z', 'manifest carries the injected timestamp');
$check(is_array($manifest) && ($manifest['format'] ?? '') === GeneratedCardDataArchive::FORMAT, 'manifest declares the format version');
if ($opened) $zip->close();

// ── accepted inputs ──────────────────────────────────────────────────────────
$check(
    GeneratedCardDataArchive::extractCardCache($exportPath, 'exported.zip', 'HellbreakSim') === $cacheJson,
    'round-trips its own export byte-exactly'
);

$bareJson = $path('cardArrayCache.json');
file_put_contents($bareJson, $cacheJson);
$check(
    GeneratedCardDataArchive::extractCardCache($bareJson, 'cardArrayCache.json', 'HellbreakSim') === $cacheJson,
    'accepts a bare .json cache file'
);

// The real-world case: `tar -cf GeneratedCode.tar GeneratedCode/` on the source machine.
$nestedTar = $makeTar('nested', ['GeneratedCode/cardArrayCache.json' => $cacheJson, 'GeneratedCode/GeneratedMacroCount.js' => 'ignored']);
$check(
    GeneratedCardDataArchive::extractCardCache($nestedTar, 'GeneratedCode.tar', 'HellbreakSim') === $cacheJson,
    'accepts a .tar rooted at GeneratedCode/ (no repacking needed)'
);

$gzTar = $makeTar('nestedgz', ['GeneratedCode/cardArrayCache.json' => $cacheJson], true);
$check(
    GeneratedCardDataArchive::extractCardCache($gzTar, 'GeneratedCode.tar.gz', 'HellbreakSim') === $cacheJson,
    'accepts a .tar.gz'
);

// Depth tie-break: a backup copy buried deeper must not win over the real one.
$twoCaches = $makeZip('twocaches', [
    'cardArrayCache.json' => $cacheJson,
    'backup/old/cardArrayCache.json' => json_encode(['cardArray' => [['id' => 'STALE']]]),
]);
$check(
    GeneratedCardDataArchive::extractCardCache($twoCaches, 'twocaches.zip', 'HellbreakSim') === $cacheJson,
    'picks the shallowest cardArrayCache.json when several are present'
);

// ── rejections ───────────────────────────────────────────────────────────────
$emptyCache = $makeZip('empty', ['cardArrayCache.json' => '{"cardArray":[],"reprintMap":[],"leaderUnitByUUIDMap":[]}']);
$rejects(
    function () use ($emptyCache) { GeneratedCardDataArchive::extractCardCache($emptyCache, 'empty.zip', 'HellbreakSim'); },
    'empty',
    'refuses an empty cardArray — importing it would blank the dictionaries'
);

$noCardArray = $makeZip('nocardarray', ['cardArrayCache.json' => '{"reprintMap":[]}']);
$rejects(
    function () use ($noCardArray) { GeneratedCardDataArchive::extractCardCache($noCardArray, 'nocardarray.zip', 'HellbreakSim'); },
    'cardArray',
    'refuses a cache with no cardArray key'
);

$badJson = $makeZip('badjson', ['cardArrayCache.json' => '{"cardArray": [oops']);
$rejects(
    function () use ($badJson) { GeneratedCardDataArchive::extractCardCache($badJson, 'badjson.zip', 'HellbreakSim'); },
    'JSON',
    'refuses a cache that is not valid JSON'
);

$noCache = $makeZip('nocache', ['GeneratedCode/GeneratedMacroCount.js' => 'var x = 1;']);
$rejects(
    function () use ($noCache) { GeneratedCardDataArchive::extractCardCache($noCache, 'nocache.zip', 'HellbreakSim'); },
    'cardArrayCache.json',
    'refuses an archive containing no cardArrayCache.json'
);

$wrongApp = $makeZip('wrongapp', [
    'cardArrayCache.json' => $cacheJson,
    'manifest.json' => json_encode(['app' => 'SWUSim', 'format' => GeneratedCardDataArchive::FORMAT]),
]);
$rejects(
    function () use ($wrongApp) { GeneratedCardDataArchive::extractCardCache($wrongApp, 'wrongapp.zip', 'HellbreakSim'); },
    'SWUSim',
    'refuses an archive whose manifest names a different app'
);

$rejects(
    function () use ($exportPath) { GeneratedCardDataArchive::extractCardCache($exportPath, 'exported.rar', 'HellbreakSim'); },
    'format',
    'refuses an unsupported file extension'
);

// ── no archive entry is ever written to disk under its own name ──────────────
// The importer reads only the entries it wants and writes to one fixed path, so a hostile entry
// name has nothing to traverse. Assert the classic zip-slip target never appears.
$slipTarget = $work . '/slipped.json';
@unlink($slipTarget);
$slip = $makeZip('slip', [
    'cardArrayCache.json' => $cacheJson,
    '../slipped.json' => 'pwned',
]);
$slipResult = null;
try { $slipResult = GeneratedCardDataArchive::extractCardCache($slip, 'slip.zip', 'HellbreakSim'); } catch (Throwable $error) { $slipResult = null; }
$check(!is_file($slipTarget), 'a traversal-style entry name is never written to disk');
$check($slipResult === $cacheJson, 'a traversal-style sibling entry does not block the real cache');

// ── opt-in art bundle ────────────────────────────────────────────────────────
// Card art is the one part of a card corpus that cannot be regenerated from the cache: for most
// Hellbreak cards the only source is an image embedded in the workbook. crops/ and concat/ are NOT
// carried — they are derived from these 900x1256 originals on import.
$cardIds = ['HB_001', 'HB_002'];
$makeWebp = function (string $name, int $width = 24, int $height = 32) use ($work) {
    $image = new Imagick();
    $image->newImage($width, $height, new ImagickPixel('rgb(120,20,40)'));
    $image->setImageFormat('webp');
    $filePath = $work . '/' . $name;
    $image->writeImage($filePath);
    $image->clear();
    return $filePath;
};
$frontArt = $makeWebp('HB_001.webp');
$backArt = $makeWebp('HB_001_back.webp');
$frontBytes = file_get_contents($frontArt);

$withArt = GeneratedCardDataArchive::export('HellbreakSim', $cacheJson, '2026-08-14T17:09:56Z', [
    'HB_001.webp' => $frontArt,
    'HB_001_back.webp' => $backArt,
]);
$withArtPath = $path('with-art.zip');
file_put_contents($withArtPath, $withArt);

$zip = new ZipArchive();
$opened = $zip->open($withArtPath) === true;
$artManifest = $opened ? json_decode((string)$zip->getFromName('manifest.json'), true) : null;
$check($opened && $zip->getFromName('WebpImages/HB_001.webp') === $frontBytes, 'art rides under WebpImages/ byte-exactly');
$check(is_array($artManifest) && ($artManifest['artCount'] ?? -1) === 2, 'manifest reports the art count');
$check($opened && $zip->getFromName('cardArrayCache.json') === $cacheJson, 'an art bundle still carries the cache');
if ($opened) $zip->close();

$check(
    GeneratedCardDataArchive::extractCardCache($withArtPath, 'with-art.zip', 'HellbreakSim') === $cacheJson,
    'the cache still extracts from an art bundle'
);

$art = GeneratedCardDataArchive::extractArt($withArtPath, 'with-art.zip', $cardIds);
$check(count($art) === 2, 'extractArt returns both art files (got ' . count($art) . ')');
$check(($art['HB_001.webp'] ?? null) === $frontBytes, 'extracted art is byte-exact');
$check(isset($art['HB_001_back.webp']), 'a _back variant is accepted (its prefix is a known card ID)');

$check(
    GeneratedCardDataArchive::extractArt($exportPath, 'exported.zip', $cardIds) === [],
    'a cache-only archive yields no art'
);

// A whole-app tarball carries concat/HB_001.webp too — a DIFFERENT 450x450 image with the SAME
// basename. Requiring a WebpImages/ path segment is what stops the crop from overwriting the full art.
$wholeApp = $makeZip('wholeapp', [
    'cardArrayCache.json' => $cacheJson,
    'WebpImages/HB_001.webp' => $frontBytes,
    'concat/HB_001.webp' => 'THE-450x450-CROP',
    'crops/HB_001_cropped.png' => 'THE-PNG-CROP',
]);
$wholeAppArt = GeneratedCardDataArchive::extractArt($wholeApp, 'wholeapp.zip', $cardIds);
$check(count($wholeAppArt) === 1 && ($wholeAppArt['HB_001.webp'] ?? null) === $frontBytes,
    'concat/ and crops/ entries are ignored — only WebpImages/ art is taken');

$junkArt = $makeZip('junkart', [
    'cardArrayCache.json' => $cacheJson,
    'WebpImages/HB_001.webp' => $frontBytes,
    'WebpImages/NOT_A_CARD.webp' => 'unrelated set',
    'WebpImages/notes.txt' => 'not an image',
    'WebpImages/../../escape.webp' => 'traversal attempt',
]);
$junkResult = GeneratedCardDataArchive::extractArt($junkArt, 'junkart.zip', $cardIds);
$check(count($junkResult) === 1 && isset($junkResult['HB_001.webp']),
    'art not matching a card ID, non-webp files, and traversal names are all dropped (got ' . implode(',', array_keys($junkResult)) . ')');
foreach (array_keys($junkResult) as $writtenName) {
    $check(basename($writtenName) === $writtenName && strpos($writtenName, '..') === false,
        "returned art name is a bare basename: $writtenName");
}

// ── export-side art selection ────────────────────────────────────────────────
$check(GeneratedCardDataArchive::cardIdsFromCache($cacheJson) === ['HB_001', 'HB_002'], 'card IDs read from the cache');

$artDirectory = $work . '/WebpImages';
@mkdir($artDirectory, 0777, true);
foreach (['HB_001.webp', 'HB_001_back.webp', 'HB_002.webp', 'ZZ_999.webp'] as $artName) {
    copy($frontArt, $artDirectory . '/' . $artName);
}
file_put_contents($artDirectory . '/README.txt', 'not art');
$selected = GeneratedCardDataArchive::selectArtFiles($artDirectory, ['HB_001', 'HB_002']);
$check(array_keys($selected) === ['HB_001.webp', 'HB_001_back.webp', 'HB_002.webp'],
    'export selects only art belonging to cards in the cache (got ' . implode(',', array_keys($selected)) . ')');
$check(GeneratedCardDataArchive::selectArtFiles($work . '/does-not-exist', ['HB_001']) === [],
    'a missing art directory selects nothing rather than throwing');

// ── derivative regeneration ──────────────────────────────────────────────────
// crops/ and concat/ are 450x450 renders of the full art, so they are rebuilt locally instead of
// being shipped — that is what keeps the art bundle at 15MB rather than 54MB.
include_once './DevTools/CardArtDerivatives.php';
$appDirectory = $work . '/app';
@mkdir($appDirectory . '/WebpImages', 0777, true);
copy($makeWebp('source-full.webp', 900, 1256), $appDirectory . '/WebpImages/HB_001.webp');

$regenerated = RegenerateCardArtDerivatives($appDirectory, 'HB_001.webp');
$check($regenerated, 'RegenerateCardArtDerivatives reports success');
$concatPath = $appDirectory . '/concat/HB_001.webp';
$cropPath = $appDirectory . '/crops/HB_001_cropped.png';
$check(is_file($concatPath), 'concat/ webp is written');
$check(is_file($cropPath), 'crops/ png is written');
if (is_file($concatPath)) {
    $concatImage = new Imagick($concatPath);
    $check($concatImage->getImageWidth() === 450 && $concatImage->getImageHeight() === 450,
        'concat art is 450x450 (got ' . $concatImage->getImageWidth() . 'x' . $concatImage->getImageHeight() . ')');
    $check(strtolower($concatImage->getImageFormat()) === 'webp', 'concat art is webp');
    $concatImage->clear();
}
if (is_file($cropPath)) {
    $cropImage = new Imagick($cropPath);
    $check(strtolower($cropImage->getImageFormat()) === 'png', 'crop art is png');
    $cropImage->clear();
}
foreach (glob($appDirectory . '/*/*') ?: [] as $leftover) @unlink($leftover);
foreach (glob($appDirectory . '/*') ?: [] as $leftover) @rmdir($leftover);
@rmdir($appDirectory);
foreach (glob($artDirectory . '/*') ?: [] as $leftover) @unlink($leftover);
@rmdir($artDirectory);

// ── cleanup ──────────────────────────────────────────────────────────────────
foreach (glob($work . '/*') ?: [] as $leftover) @unlink($leftover);
@rmdir($work);

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
