<?php

// Admin endpoint behind the Generator Workspace "Generated card data" panel.
//
// Export streams <App>/GeneratedCode/cardArrayCache.json as a zip; import replaces it from an
// uploaded archive. Only the cache moves — the caller re-runs the card-data generator afterwards so
// the dictionaries are rebuilt by THIS checkout's generator. See GeneratedCardDataArchive.php.

// An art bundle regenerates two Imagick derivatives per image, which runs well past the default
// 30s for a full set. Matches zzCardCodeGenerator.php, which raises the limit for the same reason.
set_time_limit(10800);

include_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/GeneratedCardDataArchive.php';
include_once __DIR__ . '/CardArtDerivatives.php';

$authError = CheckLoggedInUserMod();
if ($authError !== '') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => $authError]);
    exit;
}

$repoRoot = dirname(__DIR__);
$action = isset($_REQUEST['action']) ? (string)$_REQUEST['action'] : '';
$rootName = isset($_REQUEST['app']) ? (string)$_REQUEST['app'] : '';

if (!preg_match('/^[A-Za-z0-9_-]+$/', $rootName) || !is_dir($repoRoot . '/Schemas/' . $rootName)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid or unknown app']);
    exit;
}

// Mirrors how zzCodeGeneratorMain.php decides an app has a card-data step. HellbreakDeck reflects
// HellbreakSim's card data and has no cache of its own.
if (!is_file($repoRoot . '/Schemas/' . $rootName . '/ImportSchema.txt') || $rootName === 'HellbreakDeck') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => $rootName . ' has no card-data step, so it has no card cache to transfer']);
    exit;
}

$cacheDirectory = $repoRoot . '/' . $rootName . '/GeneratedCode';
$cachePath = $cacheDirectory . '/' . GeneratedCardDataArchive::CACHE_FILE_NAME;

try {
    if ($action === 'export') {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') throw new InvalidArgumentException('Export requires GET');
        if (!is_file($cachePath)) {
            throw new InvalidArgumentException($rootName . ' has no ' . GeneratedCardDataArchive::CACHE_FILE_NAME . ' yet — run its card-data step first');
        }
        $cacheJson = file_get_contents($cachePath);
        if ($cacheJson === false) throw new RuntimeException('Could not read the card cache');

        // Art is opt-in: it is by far the biggest part of a corpus, and it is only worth moving when
        // the receiving machine cannot get the images any other way.
        $artFiles = [];
        if (!empty($_GET['includeArt'])) {
            $artFiles = GeneratedCardDataArchive::selectArtFiles(
                $repoRoot . '/' . $rootName . '/WebpImages',
                GeneratedCardDataArchive::cardIdsFromCache($cacheJson)
            );
            if (!$artFiles) {
                throw new InvalidArgumentException($rootName . ' has no card art in WebpImages/ to bundle');
            }
            $artBytes = 0;
            foreach ($artFiles as $artPath) $artBytes += (int)filesize($artPath);
            if ($artBytes > GeneratedCardDataArchive::MAX_ARCHIVE_BYTES) {
                throw new InvalidArgumentException(sprintf(
                    "%s's card art is %d MB, over the %d MB transfer limit. Export without art and move the images another way.",
                    $rootName,
                    (int)round($artBytes / 1024 / 1024),
                    (int)(GeneratedCardDataArchive::MAX_ARCHIVE_BYTES / 1024 / 1024)
                ));
            }
        }

        $archive = GeneratedCardDataArchive::export($rootName, $cacheJson, gmdate('Y-m-d\TH:i:s\Z'), $artFiles);

        $fileName = $rootName . '-carddata' . ($artFiles ? '-with-art' : '') . '-' . gmdate('Y-m-d-His') . '.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . strlen($archive));
        header('X-Content-Type-Options: nosniff');
        echo $archive;
        exit;
    }

    if ($action === 'import') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new InvalidArgumentException('Import requires POST');

        // PHP silently discards the ENTIRE body — $_POST and $_FILES both — when it exceeds
        // post_max_size. Without this check an oversized art bundle fails the CSRF test below and
        // reports a bogus "invalid security token" instead of the real problem.
        if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            throw new InvalidArgumentException(sprintf(
                'The upload (%d MB) exceeded this server\'s post_max_size of %s. Raise post_max_size and upload_max_filesize in php.ini, or export without art.',
                (int)round(((int)$_SERVER['CONTENT_LENGTH']) / 1024 / 1024),
                (string)ini_get('post_max_size')
            ));
        }

        CheckSession();
        $sessionToken = isset($_SESSION['generator_admin_csrf']) ? (string)$_SESSION['generator_admin_csrf'] : '';
        $requestToken = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';
        if ($sessionToken === '' || !hash_equals($sessionToken, $requestToken)) {
            throw new InvalidArgumentException('Invalid import security token; reload the admin page and try again');
        }
        if (!isset($_FILES['archiveFile']) || $_FILES['archiveFile']['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Choose a valid card data archive');
        }
        if ((int)$_FILES['archiveFile']['size'] > GeneratedCardDataArchive::MAX_ARCHIVE_BYTES) {
            throw new InvalidArgumentException(sprintf(
                'Import file exceeds the %d MB limit',
                (int)(GeneratedCardDataArchive::MAX_ARCHIVE_BYTES / 1024 / 1024)
            ));
        }

        $cacheJson = GeneratedCardDataArchive::extractCardCache(
            $_FILES['archiveFile']['tmp_name'],
            (string)$_FILES['archiveFile']['name'],
            $rootName
        );
        $cardCount = GeneratedCardDataArchive::validateCacheJson($cacheJson);

        if (!is_dir($cacheDirectory) && !mkdir($cacheDirectory, 0777, true) && !is_dir($cacheDirectory)) {
            throw new RuntimeException('Could not create ' . $rootName . '/GeneratedCode');
        }

        // Keep one generation of the previous cache: an import that turns out to be the wrong build
        // is otherwise unrecoverable, because */GeneratedCode is gitignored.
        $backupCreated = false;
        if (is_file($cachePath)) {
            $backupCreated = copy($cachePath, $cachePath . '.bak');
            if (!$backupCreated) throw new RuntimeException('Could not back up the existing card cache');
        }

        // Write-then-rename so a failure mid-write cannot leave a truncated cache behind.
        $stagedPath = $cachePath . '.tmp';
        if (file_put_contents($stagedPath, $cacheJson) === false || !rename($stagedPath, $cachePath)) {
            @unlink($stagedPath);
            throw new RuntimeException('Could not write the imported card cache');
        }

        // Art is optional in the archive; a cache-only import simply finds none. Each image lands
        // in WebpImages/ under its bare basename, then its 450x450 derivatives are rebuilt here
        // rather than shipped.
        $artWritten = 0;
        $derivativesFailed = 0;
        $art = GeneratedCardDataArchive::extractArt(
            $_FILES['archiveFile']['tmp_name'],
            (string)$_FILES['archiveFile']['name'],
            GeneratedCardDataArchive::cardIdsFromCache($cacheJson)
        );
        if ($art) {
            $appDirectory = $repoRoot . '/' . $rootName;
            $artDirectory = $appDirectory . '/WebpImages';
            if (!is_dir($artDirectory) && !mkdir($artDirectory, 0777, true) && !is_dir($artDirectory)) {
                throw new RuntimeException('Could not create ' . $rootName . '/WebpImages');
            }
            foreach ($art as $baseName => $imageBytes) {
                if (file_put_contents($artDirectory . '/' . basename((string)$baseName), $imageBytes) === false) continue;
                ++$artWritten;
                if (!RegenerateCardArtDerivatives($appDirectory, (string)$baseName)) ++$derivativesFailed;
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'app' => $rootName,
            'cardCount' => $cardCount,
            'backupCreated' => $backupCreated,
            'artWritten' => $artWritten,
            'derivativesFailed' => $derivativesFailed,
        ]);
        exit;
    }

    throw new InvalidArgumentException('Unknown transfer action');
} catch (InvalidArgumentException $error) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => $error->getMessage()]);
} catch (Throwable $error) {
    error_log('AdminGeneratedCardDataTransfer error: ' . $error->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Card data transfer failed']);
}
