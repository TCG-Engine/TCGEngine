<?php

// Moves a sim's normalized card cache (<App>/GeneratedCode/cardArrayCache.json) between machines.
//
// */GeneratedCode is gitignored, so the cache cannot ride in the repo — and Hellbreak has no
// official card API to re-fetch from. This layer is the transport: it packages the cache with a
// manifest on the way out, and on the way in it validates hard enough that a bad archive can never
// blank a live dictionary set.
//
// Only the cache travels. The dictionaries, macro code, and UI data are regenerated locally from it
// by zzCardCodeGenerator.php, so an archive never carries generated output from a different (and
// possibly older) generator version.
//
// SECURITY: archive entries are never extracted to disk under their own names. This class reads the
// one or two entries it cares about into memory and the caller writes to a fixed path, so a hostile
// entry name ("../../evil.php") has nothing to traverse.
final class GeneratedCardDataArchive
{
    public const FORMAT = 'tcgengine-card-data-1';
    public const CACHE_FILE_NAME = 'cardArrayCache.json';
    public const MANIFEST_FILE_NAME = 'manifest.json';

    // Art rides under this directory INSIDE the archive, and the segment is required on the way back
    // in. A whole-app tarball also contains concat/<id>.webp — a different 450x450 crop with the same
    // basename — so matching on basename alone would let the crop overwrite the full card art.
    public const ART_DIRECTORY = 'WebpImages';

    // A real cache is well under a megabyte; this is a decompression-bomb ceiling, not a target.
    public const MAX_CACHE_BYTES = 64 * 1024 * 1024;

    // Per-image ceiling. Card art is ~100KB; anything near this is not a card.
    public const MAX_ART_BYTES = 8 * 1024 * 1024;

    // Whole-archive ceiling, applied on BOTH sides so an export can always be re-imported. An app
    // whose art exceeds this cannot ship an art bundle — the cache-only export is unaffected.
    public const MAX_ARCHIVE_BYTES = 64 * 1024 * 1024;

    // Builds the downloadable zip. $exportedAt is injected rather than read from the clock so the
    // output is reproducible under test. $artFiles is [basename => absolute path]; when empty the
    // export is the small cache-only archive.
    public static function export(string $rootName, string $cacheJson, string $exportedAt, array $artFiles = []): string
    {
        $cardCount = self::validateCacheJson($cacheJson);

        $manifest = json_encode([
            'format' => self::FORMAT,
            'app' => $rootName,
            'exportedAt' => $exportedAt,
            'cardCount' => $cardCount,
            'artCount' => count($artFiles),
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $zipPath = self::temporaryPath('.zip');
        try {
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Could not create the export archive');
            }
            $zip->addFromString(self::MANIFEST_FILE_NAME, $manifest);
            $zip->addFromString(self::CACHE_FILE_NAME, $cacheJson);
            foreach ($artFiles as $baseName => $sourcePath) {
                // addFile streams from disk, so a large art set never lands in memory all at once.
                $zip->addFile($sourcePath, self::ART_DIRECTORY . '/' . basename((string)$baseName));
            }
            if (!$zip->close()) throw new RuntimeException('Could not finalize the export archive');

            $bytes = file_get_contents($zipPath);
            if ($bytes === false) throw new RuntimeException('Could not read the export archive');
            return $bytes;
        } finally {
            @unlink($zipPath);
        }
    }

    // Reads an uploaded archive (or bare .json) and returns the validated cache contents, byte-exact.
    // $fileNameHint is the ORIGINAL upload name: a PHP upload tmp file has no extension, and PharData
    // dispatches on extension, so the hint is what tells us which reader to use.
    public static function extractCardCache(string $archivePath, string $fileNameHint, string $rootName): string
    {
        $kind = self::archiveKind($fileNameHint);

        if ($kind === 'json') {
            $cacheJson = self::readFileCapped($archivePath);
            self::validateCacheJson($cacheJson);
            return $cacheJson;
        }

        $selector = function (string $entryName) {
            $base = basename(str_replace('\\', '/', $entryName));
            return ($base === self::CACHE_FILE_NAME || $base === self::MANIFEST_FILE_NAME) ? $base : '';
        };
        $entries = $kind === 'zip'
            ? self::readZipEntries($archivePath, $selector, self::MAX_CACHE_BYTES)
            : self::readTarEntries($archivePath, $kind, $selector, self::MAX_CACHE_BYTES);

        if (!isset($entries[self::CACHE_FILE_NAME])) {
            throw new InvalidArgumentException(
                'No ' . self::CACHE_FILE_NAME . ' found in the archive. Export it from the source machine, '
                . 'or tar the app\'s GeneratedCode directory.'
            );
        }

        // The manifest is optional — a hand-rolled tarball will not have one — but when it IS present
        // and names another game, importing would silently cross-contaminate two apps' card data.
        if (isset($entries[self::MANIFEST_FILE_NAME])) {
            $manifest = json_decode($entries[self::MANIFEST_FILE_NAME], true);
            $manifestApp = is_array($manifest) ? trim((string)($manifest['app'] ?? '')) : '';
            if ($manifestApp !== '' && $manifestApp !== $rootName) {
                throw new InvalidArgumentException(
                    'This archive was exported from ' . $manifestApp . ', not ' . $rootName
                    . '. Select ' . $manifestApp . ' in the sidebar, or import an archive for ' . $rootName . '.'
                );
            }
        }

        $cacheJson = $entries[self::CACHE_FILE_NAME];
        self::validateCacheJson($cacheJson);
        return $cacheJson;
    }

    // Returns [basename => image bytes] for card art in the archive, or [] when it carries none.
    //
    // Every returned key is a bare basename, so the caller writes into one fixed directory and an
    // entry name can never escape it. An entry has to clear three gates to survive: it must sit under
    // a WebpImages/ path segment, be a plainly-named .webp, and its stem must resolve to a card ID in
    // $cardIds (which admits "<id>_back" and "<id>_token" variants). That last gate is what keeps a
    // different set's art out of this app's folder.
    public static function extractArt(string $archivePath, string $fileNameHint, array $cardIds): array
    {
        $kind = self::archiveKind($fileNameHint);
        if ($kind === 'json') return [];

        $cardIdSet = array_fill_keys($cardIds, true);
        $selector = function (string $entryName) use ($cardIdSet) {
            $normalized = str_replace('\\', '/', $entryName);
            if (strpos($normalized, '..') !== false) return '';

            $segments = explode('/', $normalized);
            $base = array_pop($segments);
            if (!in_array(self::ART_DIRECTORY, $segments, true)) return '';
            if (!preg_match('/^([A-Za-z0-9_-]+)\.webp$/', (string)$base, $match)) return '';

            return self::artStemMatchesCard($match[1], $cardIdSet) ? (string)$base : '';
        };

        return $kind === 'zip'
            ? self::readZipEntries($archivePath, $selector, self::MAX_ART_BYTES)
            : self::readTarEntries($archivePath, $kind, $selector, self::MAX_ART_BYTES);
    }

    // The card IDs in a cache, in cache order.
    public static function cardIdsFromCache(string $cacheJson): array
    {
        $cache = json_decode($cacheJson, true);
        if (!is_array($cache) || !is_array($cache['cardArray'] ?? null)) return [];
        $ids = [];
        foreach ($cache['cardArray'] as $card) {
            $id = is_array($card) ? trim((string)($card['id'] ?? '')) : '';
            if ($id !== '') $ids[] = $id;
        }
        return $ids;
    }

    // [basename => absolute path] for the art belonging to $cardIds. Applies the same card-ID gate as
    // the import side, so an export never ships art from a set this app no longer carries.
    public static function selectArtFiles(string $artDirectory, array $cardIds): array
    {
        if (!is_dir($artDirectory)) return [];
        $cardIdSet = array_fill_keys($cardIds, true);
        $selected = [];
        foreach (glob($artDirectory . '/*.webp') ?: [] as $filePath) {
            $baseName = basename($filePath);
            if (!preg_match('/^([A-Za-z0-9_-]+)\.webp$/', $baseName, $match)) continue;
            if (!self::artStemMatchesCard($match[1], $cardIdSet)) continue;
            $selected[$baseName] = $filePath;
        }
        ksort($selected);
        return $selected;
    }

    // "HB_001" matches itself; "HB_001_back" matches by trimming suffixes right-to-left.
    private static function artStemMatchesCard(string $stem, array $cardIdSet): bool
    {
        if (isset($cardIdSet[$stem])) return true;
        $candidate = $stem;
        while (($separator = strrpos($candidate, '_')) !== false) {
            $candidate = substr($candidate, 0, $separator);
            if (isset($cardIdSet[$candidate])) return true;
        }
        return false;
    }

    // Returns the card count. Throws on anything zzCardCodeGenerator.php would choke on — an empty
    // cardArray is the dangerous case, because for most apps it generates empty dictionaries.
    public static function validateCacheJson(string $cacheJson): int
    {
        if (strlen($cacheJson) > self::MAX_CACHE_BYTES) {
            throw new InvalidArgumentException('The card cache exceeds the ' . (int)(self::MAX_CACHE_BYTES / 1024 / 1024) . ' MB limit');
        }

        $cache = json_decode($cacheJson, true);
        if (!is_array($cache)) {
            throw new InvalidArgumentException(self::CACHE_FILE_NAME . ' is not valid JSON: ' . json_last_error_msg());
        }
        if (!isset($cache['cardArray']) || !is_array($cache['cardArray'])) {
            throw new InvalidArgumentException(self::CACHE_FILE_NAME . ' has no cardArray list');
        }
        if (count($cache['cardArray']) < 1) {
            throw new InvalidArgumentException(
                self::CACHE_FILE_NAME . ' is empty (zero cards). Importing it would blank the generated '
                . 'dictionaries, so the existing card data was left untouched.'
            );
        }
        return count($cache['cardArray']);
    }

    private static function archiveKind(string $fileNameHint): string
    {
        $name = strtolower(trim($fileNameHint));
        if (substr($name, -7) === '.tar.gz' || substr($name, -4) === '.tgz') return 'tar.gz';
        if (substr($name, -4) === '.tar') return 'tar';
        if (substr($name, -4) === '.zip') return 'zip';
        if (substr($name, -5) === '.json') return 'json';
        throw new InvalidArgumentException(
            'Unsupported archive format. Choose a .zip, .tar, .tar.gz, or a bare ' . self::CACHE_FILE_NAME . ' file.'
        );
    }

    // Returns [key => contents] for entries $selector accepts, preferring the SHALLOWEST match:
    // a deeper copy is a backup ("backup/old/cardArrayCache.json"), not the payload.
    private static function readZipEntries(string $archivePath, callable $selector, int $maxBytes): array
    {
        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new InvalidArgumentException('Could not read the uploaded archive — it may be corrupt or not a zip file');
        }
        try {
            $best = [];
            $found = [];
            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $stat = $zip->statIndex($i);
                if (!$stat) continue;
                $entryName = (string)$stat['name'];
                if (substr($entryName, -1) === '/') continue;

                $wanted = (string)$selector($entryName);
                if ($wanted === '') continue;
                if ((int)$stat['size'] > $maxBytes) {
                    throw new InvalidArgumentException('Archive entry ' . $wanted . ' exceeds the size limit');
                }

                $depth = substr_count($entryName, '/');
                if (isset($best[$wanted]) && $best[$wanted] <= $depth) continue;
                $contents = $zip->getFromIndex($i, $maxBytes);
                if ($contents === false) continue;
                $best[$wanted] = $depth;
                $found[$wanted] = $contents;
            }
            return $found;
        } finally {
            $zip->close();
        }
    }

    private static function readTarEntries(string $archivePath, string $kind, callable $selector, int $maxBytes): array
    {
        // PharData dispatches on the file EXTENSION, and an upload tmp file has none, so stage a copy
        // under a name it will accept.
        $stagedPath = self::temporaryPath($kind === 'tar.gz' ? '.tar.gz' : '.tar');
        if (!copy($archivePath, $stagedPath)) {
            @unlink($stagedPath);
            throw new RuntimeException('Could not stage the uploaded archive for reading');
        }

        try {
            $archive = new PharData($stagedPath);
            $best = [];
            $found = [];
            foreach (new RecursiveIteratorIterator($archive) as $file) {
                /** @var PharFileInfo $file */
                $entryName = self::pharRelativeName($file->getPathname(), $stagedPath);
                $wanted = (string)$selector($entryName);
                if ($wanted === '') continue;
                if ($file->getSize() > $maxBytes) {
                    throw new InvalidArgumentException('Archive entry ' . $wanted . ' exceeds the size limit');
                }

                $depth = substr_count($entryName, '/');
                if (isset($best[$wanted]) && $best[$wanted] <= $depth) continue;
                $contents = file_get_contents($file->getPathname());
                if ($contents === false) continue;
                $best[$wanted] = $depth;
                $found[$wanted] = $contents;
            }
            return $found;
        } catch (UnexpectedValueException $error) {
            throw new InvalidArgumentException('Could not read the uploaded archive — it may be corrupt or not a tar file');
        } finally {
            @unlink($stagedPath);
        }
    }

    private static function pharRelativeName(string $pathName, string $stagedPath): string
    {
        $prefix = 'phar://' . $stagedPath . '/';
        return strpos($pathName, $prefix) === 0 ? substr($pathName, strlen($prefix)) : basename($pathName);
    }

    private static function readFileCapped(string $path): string
    {
        if (filesize($path) > self::MAX_CACHE_BYTES) {
            throw new InvalidArgumentException('The uploaded file exceeds the size limit');
        }
        $contents = file_get_contents($path);
        if ($contents === false) throw new RuntimeException('Could not read the uploaded file');
        return $contents;
    }

    private static function temporaryPath(string $suffix): string
    {
        return sys_get_temp_dir() . '/tcgengine-carddata-' . bin2hex(random_bytes(8)) . $suffix;
    }
}
