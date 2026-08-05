<?php
// ============================================================================
// zzCropTester.php — interactive crop tuner for generated card images.
//
//   Open:  http://localhost/TCGEngine/zzCropTester.php?app=AzukiSim
//
// Purpose: dial in the crop coordinates used by zzImageConverter.php (the
// concat/ square-art and crops/ tooltip-art pipelines) WITHOUT re-running the
// whole zzCardCodeGenerator. It reads an app's downloaded source webps,
// applies arbitrary crop sections live, overlays the crop
// rectangles on the source, compares against the currently-committed output,
// and prints the exact PHP snippet to paste back into zzImageConverter.php.
//
// Two roles in one file:
//   ?app=Root&render=1&card=ID&s=sx,sy,w,h&s=... -> streams the cropped image
//   (no params)                                -> the HTML control panel
// ============================================================================

ini_set('display_errors', 1);
error_reporting(E_ALL);

$ROOT = __DIR__;

// Build an allowlist from known schema roots that have downloaded source images.
$availableApps = [];
foreach (glob($ROOT . '/Schemas/*', GLOB_ONLYDIR) ?: [] as $schemaDirectory) {
    $candidate = basename($schemaDirectory);
    if (!preg_match('/^[A-Za-z0-9_-]+$/', $candidate)) continue;
    if (!is_dir($ROOT . '/' . $candidate . '/WebpImages')) continue;
    $availableApps[] = $candidate;
}
sort($availableApps, SORT_NATURAL | SORT_FLAG_CASE);

$requestedApp = isset($_GET['app']) ? (string)$_GET['app'] : '';
$app = in_array($requestedApp, $availableApps, true)
    ? $requestedApp
    : (in_array('SWUSim', $availableApps, true) ? 'SWUSim' : ($availableApps[0] ?? ''));
if ($app === '') {
    http_response_code(404);
    echo 'No apps with generated source images were found.';
    exit;
}

$APP_ROOT   = $ROOT . '/' . $app;
$IMG_BASE   = $APP_ROOT . '/WebpImages/';
$APP_WEB    = rawurlencode($app) . '/';
$IMG_WEB    = $APP_WEB . 'WebpImages/';
$CONCAT_WEB = $APP_WEB . 'concat/';
$CROP_WEB   = $APP_WEB . 'crops/';

function CropTesterParseSections($rawSections)
{
    $sections = [];
    foreach (array_slice((array)$rawSections, 0, 8) as $sstr) {
        if (!is_string($sstr)) continue;
        $p = array_map('intval', explode(',', $sstr));
        if (count($p) === 4 && $p[2] > 0 && $p[2] <= 4096 && $p[3] > 0 && $p[3] <= 4096) {
            $sections[] = $p;
        }
    }
    return $sections;
}

function CropTesterBuildImage($src, $sections, $resizeW = 0, $resizeH = 0)
{
    $outW = 0;
    $outH = 0;
    foreach ($sections as $p) {
        $outW = max($outW, $p[2]);
        $outH += $p[3];
    }
    if ($outW <= 0 || $outW > 4096 || $outH <= 0 || $outH > 8192) {
        throw new InvalidArgumentException('Requested output is too large');
    }
    if (!class_exists('Imagick')) {
        throw new RuntimeException('Imagick is required to regenerate images');
    }

    $imgsrc = new Imagick($src);
    $out = new Imagick();
    $out->newImage($outW, $outH, new ImagickPixel('transparent'));

    $y = 0;
    foreach ($sections as $p) {
        [$sx, $sy, $w, $h] = $p;
        $piece = clone $imgsrc;
        $piece->cropImage($w, $h, $sx, $sy);
        $piece->setImagePage($w, $h, 0, 0);
        $out->compositeImage($piece, Imagick::COMPOSITE_COPY, 0, $y);
        $piece->clear();
        $piece->destroy();
        $y += $h;
    }
    $imgsrc->clear();
    $imgsrc->destroy();

    $resizeW = min(4096, max(0, (int)$resizeW));
    $resizeH = min(4096, max(0, (int)$resizeH));
    if ($resizeW > 0 && $resizeH > 0 && ($resizeW !== $outW || $resizeH !== $outH)) {
        $out->resizeImage($resizeW, $resizeH, Imagick::FILTER_LANCZOS, 1, false);
    }
    $out->setImagePage(0, 0, 0, 0);
    return $out;
}

// ----------------------------------------------------------------------------
// Image endpoint: stack the requested sections vertically and stream the result.
// A "section" is sx,sy,w,h taken from the source; output is (max w) x (sum h).
// This single generalization reproduces every branch in zzImageConverter:
//   single-crop  = 1 section,  two-section = 2 sections,  art-crop = 1 section.
// ----------------------------------------------------------------------------
if (isset($_GET['render'])) {
    $card = isset($_GET['card']) && is_string($_GET['card']) ? $_GET['card'] : '';
    if ($card === '' || str_contains($card, '/') || str_contains($card, '\\') || str_contains($card, "\0")) {
        http_response_code(400);
        header('Content-Type: text/plain');
        echo 'Invalid card ID';
        exit;
    }
    $src  = $IMG_BASE . $card . '.webp';
    if ($card === '' || !is_file($src)) {
        http_response_code(404);
        header('Content-Type: text/plain');
        echo "No source image for '$card'";
        exit;
    }

    $sections = CropTesterParseSections($_GET['s'] ?? []);
    if (!$sections) {
        http_response_code(400);
        header('Content-Type: text/plain');
        echo "No valid sections";
        exit;
    }

    $fmt  = (($_GET['fmt'] ?? 'webp') === 'png') ? 'png' : 'webp';
    $resizeW = min(4096, max(0, (int)($_GET['ow'] ?? 0)));
    $resizeH = min(4096, max(0, (int)($_GET['oh'] ?? 0)));
    try {
        $out = CropTesterBuildImage($src, $sections, $resizeW, $resizeH);
    } catch (Throwable $e) {
        http_response_code(500);
        header('Content-Type: text/plain');
        echo 'Could not render crop: ' . $e->getMessage();
        exit;
    }

    if ($fmt === 'png') { $out->setImageFormat('png'); header('Content-Type: image/png'); }
    else                { $out->setImageFormat('webp'); header('Content-Type: image/webp'); }
    echo $out->getImageBlob();
    $out->clear(); $out->destroy();
    exit;
}

// ----------------------------------------------------------------------------
// Control panel: gather real sample cards per type from the card dictionary.
// ----------------------------------------------------------------------------
@include_once $APP_ROOT . '/GeneratedCode/GeneratedCardDictionaries.php';

// Dictionary variable names differ between apps. Normalize common names, then
// fall back to image filenames when an app has no compatible dictionary.
$cardTypes = isset($typeData) && is_array($typeData) ? $typeData
    : (isset($categoryData) && is_array($categoryData) ? $categoryData : []);
$cardTitles = isset($titleData) && is_array($titleData) ? $titleData
    : (isset($nameData) && is_array($nameData) ? $nameData : []);

$samplesByType = [];               // type => [cardID, ...]
$titlesById    = [];               // cardID => display title
if ($cardTypes) {
    foreach ($cardTypes as $id => $t) {
        $t = (string)$t;
        if (count($samplesByType[$t] ?? []) >= 32) continue;
        if (is_file($IMG_BASE . $id . '.webp')) {
            $samplesByType[$t][] = $id;
            $titlesById[$id] = isset($cardTitles[$id]) ? (string)$cardTitles[$id] : $id;
        }
    }
    // Synthesize LeaderUnit (_back) samples from Leader cards.
    foreach (($samplesByType['Leader'] ?? []) as $id) {
        if (count($samplesByType['LeaderUnit'] ?? []) >= 16) break;
        if (is_file($IMG_BASE . $id . '_back.webp')) {
            $samplesByType['LeaderUnit'][] = $id . '_back';
            $titlesById[$id . '_back'] = (isset($cardTitles[$id]) ? $cardTitles[$id] : $id) . ' (unit side)';
        }
    }
}

$allImageIds = [];
$allSamples = [];
foreach (glob($IMG_BASE . '*.webp') ?: [] as $imagePath) {
    $id = pathinfo($imagePath, PATHINFO_FILENAME);
    $allImageIds[] = $id;
    if (count($allSamples) < 500) {
        $allSamples[$id] = isset($cardTitles[$id]) ? (string)$cardTitles[$id] : $id;
    }
}

// Scenarios mirror the actual branches in zzImageConverter.php. Each carries
// its production-default sections so the panel opens reproducing production.
$swuScenarios = [
    'concat_unit' => [
        'label'    => 'concat · Unit / LeaderUnit (single crop)',
        'pipeline' => 'concat', 'fmt' => 'webp', 'srcW' => 450,
        'types'    => ['Unit', 'LeaderUnit'],
        'sections' => [[14, 14, 420, 420]], 'outW' => 450, 'outH' => 450,
        'snippet'  => '_concatSingleCrop',
    ],
    'concat_event' => [
        'label'    => 'concat · Event (two-section)',
        'pipeline' => 'concat', 'fmt' => 'webp', 'srcW' => 450,
        'types'    => ['Event'],
        'sections' => [[0, 20, 450, 140], [0, 310, 450, 260]], 'outW' => 450, 'outH' => 450,
        'snippet'  => '_concatTwoSection',
    ],
    'concat_upgrade' => [
        'label'    => 'concat · Upgrade / Token (two-section)',
        'pipeline' => 'concat', 'fmt' => 'webp', 'srcW' => 450,
        'types'    => ['Upgrade', 'Token Upgrade', 'Token Unit'],
        'sections' => [[0, 14, 450, 342], [0, 516, 450, 80]], 'outW' => 450, 'outH' => 450,
        'snippet'  => '_concatTwoSection',
    ],
    'concat_landscape' => [
        'label'    => 'concat · Leader / Base (landscape)',
        'pipeline' => 'concat', 'fmt' => 'webp', 'srcW' => 628,
        'types'    => ['Leader', 'Base'],
        'sections' => [[104, 15, 420, 420]], 'outW' => 450, 'outH' => 450,
        'snippet'  => '_concatSingleCrop',
    ],
    'crop_event' => [
        'label'    => 'crops · Event (art thumbnail)',
        'pipeline' => 'crop', 'fmt' => 'png', 'srcW' => 450,
        'types'    => ['Event'],
        'sections' => [[50, 326, 350, 246]],
        'snippet'  => 'cropImage',
    ],
    'crop_default' => [
        'label'    => 'crops · default (art thumbnail)',
        'pipeline' => 'crop', 'fmt' => 'png', 'srcW' => 450,
        'types'    => ['Unit', 'Upgrade'],
        'sections' => [[50, 100, 350, 270]],
        'snippet'  => 'cropImage',
    ],
    'crop_leader' => [
        'label'    => 'crops · Leader (portrait)',
        'pipeline' => 'crop', 'fmt' => 'png', 'srcW' => 628,
        'types'    => ['Leader'],
        'sections' => [[10, 60, 200, 350]],
        'snippet'  => 'cropImage',
    ],
    'crop_base' => [
        'label'    => 'crops · Base (identity banner)',
        'pipeline' => 'crop', 'fmt' => 'png', 'srcW' => 628,
        'types'    => ['Base'],
        'sections' => [[34, 125, 560, 175]],
        'snippet'  => 'cropImage',
    ],
];

$genericScenarios = [
    'concat_default' => [
        'label'    => 'concat · default (two-section)',
        'pipeline' => 'concat', 'fmt' => 'webp', 'srcW' => 450,
        'types'    => [],
        'sections' => [[0, 15, 450, 400], [0, 595, 450, 10]], 'outW' => 450, 'outH' => 450,
        'snippet'  => '_concatTwoSection',
    ],
    'crop_default' => [
        'label'    => 'crops · default (art thumbnail)',
        'pipeline' => 'crop', 'fmt' => 'png', 'srcW' => 450,
        'types'    => [],
        'sections' => [[50, 100, 350, 270]],
        'snippet'  => 'cropImage',
    ],
];
$scenarios = ($app === 'SWUSim' || $app === 'SWUDeck') ? $swuScenarios : $genericScenarios;

function CropTesterScenarioImageIds($scenario, $allImageIds, $cardTypes)
{
    $types = $scenario['types'] ?? [];
    if (!$types) return $allImageIds;

    $matches = [];
    foreach ($allImageIds as $id) {
        $type = isset($cardTypes[$id]) ? (string)$cardTypes[$id] : '';
        if ($type === '' && str_ends_with($id, '_back')) {
            $frontID = substr($id, 0, -5);
            if (($cardTypes[$frontID] ?? '') === 'Leader') $type = 'LeaderUnit';
        }
        if (in_array($type, $types, true)) $matches[] = $id;
    }
    return $matches;
}

function CropTesterWriteImage($image, $destination, $format)
{
    $directory = dirname($destination);
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Could not create output directory');
    }

    $temporary = tempnam($directory, '.crop-');
    if ($temporary === false) throw new RuntimeException('Could not create temporary output');
    $backup = null;
    try {
        $image->setImageFormat($format);
        if (!$image->writeImage($temporary) || !is_file($temporary)) {
            throw new RuntimeException('Could not write temporary output');
        }
        if (is_file($destination)) {
            $backup = $destination . '.bak-' . bin2hex(random_bytes(4));
            if (!rename($destination, $backup)) throw new RuntimeException('Could not prepare existing output for replacement');
        }
        if (!rename($temporary, $destination)) {
            if ($backup !== null && is_file($backup)) rename($backup, $destination);
            throw new RuntimeException('Could not install regenerated output');
        }
        if ($backup !== null && is_file($backup)) unlink($backup);
    } finally {
        if (is_file($temporary)) unlink($temporary);
    }
}

function CropTesterFindCodexCLI()
{
    $configured = getenv('CODEX_CLI_PATH');
    if (is_string($configured) && $configured !== '' && is_file($configured)) return $configured;

    $candidates = [];
    $home = getenv('USERPROFILE');
    if (is_string($home) && $home !== '') {
        $binRoot = $home . '/AppData/Local/OpenAI/Codex/bin';
        foreach (glob($binRoot . '/*/codex.exe') ?: [] as $candidate) $candidates[] = $candidate;
        if (is_file($binRoot . '/codex.exe')) $candidates[] = $binRoot . '/codex.exe';
    }
    foreach (glob('C:/Users/*/AppData/Local/OpenAI/Codex/bin/*/codex.exe') ?: [] as $candidate) $candidates[] = $candidate;
    foreach (glob('C:/Users/*/AppData/Local/OpenAI/Codex/bin/codex.exe') ?: [] as $candidate) $candidates[] = $candidate;
    $candidates = array_values(array_unique(array_filter($candidates, 'is_file')));
    usort($candidates, static function ($a, $b) {
        return (filemtime($b) ?: 0) <=> (filemtime($a) ?: 0);
    });
    if ($candidates) {
        return $candidates[0];
    }
    return null;
}

function CropTesterFindCodexModel()
{
    $configured = getenv('CODEX_IMAGE_MODEL');
    if (is_string($configured) && preg_match('/^[A-Za-z0-9._-]+$/', $configured)) return $configured;

    $configPaths = [];
    $home = getenv('USERPROFILE');
    if (is_string($home) && $home !== '') $configPaths[] = $home . '/.codex/config.toml';
    foreach (glob('C:/Users/*/.codex/config.toml') ?: [] as $path) $configPaths[] = $path;
    foreach (array_unique($configPaths) as $path) {
        if (!is_file($path)) continue;
        $contents = file_get_contents($path);
        if ($contents !== false && preg_match('/^model\s*=\s*["\']([A-Za-z0-9._-]+)["\']/m', $contents, $match)) {
            return $match[1];
        }
    }
    return 'gpt-5.6-sol';
}

function CropTesterGeneratedImageRoots($codexPath)
{
    $roots = [];
    $codexHome = getenv('CODEX_HOME');
    if (is_string($codexHome) && $codexHome !== '') $roots[] = rtrim($codexHome, '/\\') . '/generated_images';
    $normalized = str_replace('\\', '/', $codexPath);
    if (preg_match('~^([A-Za-z]:/Users/[^/]+)(?:/|$)~i', $normalized, $match)) {
        $roots[] = $match[1] . '/.codex/generated_images';
    }
    foreach (glob('C:/Users/*/.codex/generated_images', GLOB_ONLYDIR) ?: [] as $root) $roots[] = $root;
    return array_values(array_unique(array_filter($roots, 'is_dir')));
}

function CropTesterListGeneratedImages($roots)
{
    $images = [];
    foreach ($roots as $root) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            $extension = strtolower($file->getExtension());
            if (!in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true)) continue;
            $images[$file->getPathname()] = $file->getMTime();
        }
    }
    return $images;
}

function CropTesterCollectGeneratedImage($codexPath, $stdout, $beforeImages, $destination)
{
    $roots = CropTesterGeneratedImageRoots($codexPath);
    $threadID = null;
    foreach (preg_split('/\R/', $stdout) as $line) {
        $event = json_decode($line, true);
        if (is_array($event) && ($event['type'] ?? '') === 'thread.started' && isset($event['thread_id'])) {
            $threadID = (string)$event['thread_id'];
            break;
        }
    }

    $candidates = [];
    if ($threadID !== null && preg_match('/^[A-Za-z0-9_-]+$/', $threadID)) {
        foreach ($roots as $root) {
            $threadDirectory = $root . '/' . $threadID;
            if (!is_dir($threadDirectory)) continue;
            foreach (glob($threadDirectory . '/*.{png,jpg,jpeg,webp}', GLOB_BRACE) ?: [] as $path) {
                if (is_file($path)) $candidates[$path] = filemtime($path) ?: 0;
            }
        }
    }
    if (!$candidates) {
        $afterImages = CropTesterListGeneratedImages($roots);
        $candidates = array_diff_key($afterImages, $beforeImages);
    }
    if (!$candidates) return false;

    arsort($candidates, SORT_NUMERIC);
    $source = array_key_first($candidates);
    return is_string($source) && copy($source, $destination);
}

function CropTesterNormalizeGeneratedImage($path)
{
    $image = new Imagick($path);
    $srcW = $image->getImageWidth();
    $srcH = $image->getImageHeight();
    if ($srcW <= 0 || $srcH <= 0) throw new RuntimeException('Generated image has invalid dimensions');
    $scale = max(450 / $srcW, 450 / $srcH);
    $scaledW = (int)round($srcW * $scale);
    $scaledH = (int)round($srcH * $scale);
    $image->resizeImage($scaledW, $scaledH, Imagick::FILTER_LANCZOS, 1, false);
    $image->cropImage(450, 450, max(0, (int)round(($scaledW - 450) / 2)), max(0, (int)round(($scaledH - 450) / 2)));
    $image->setImagePage(0, 0, 0, 0);
    return $image;
}

function CropTesterRunCodexImageEdit($codexPath, $workingRoot, $source, $output, $userPrompt, $emitProgress, $cardID, $completed, $total)
{
    $beforeImages = CropTesterListGeneratedImages(CropTesterGeneratedImageRoots($codexPath));
    $instruction = '$imagegen Edit the attached card image and save the final edited image exactly to "' . str_replace('\\', '/', $output) . '". '
        . 'The result must be a clean square arena-display asset. Preserve the card identity, original illustration, top title bar, cost, and icons exactly. '
        . 'Do not invent or alter names, numbers, symbols, characters, or branding. Remove partial rules text and textbox fragments from the lower edge. '
        . 'Repair or extend only the artwork and frame needed to make all four border edges visually consistent. Do not modify any other file. '
        . 'Additional direction from the user: ' . trim($userPrompt);

    $command = [
        $codexPath, 'exec', '--ignore-user-config', '--ephemeral', '--sandbox', 'workspace-write', '--json',
        '--model', CropTesterFindCodexModel(), '--skip-git-repo-check', '--cd', $workingRoot, '--image', $source, '-',
    ];
    $pipes = [];
    $process = proc_open($command, [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes, $workingRoot, null, ['bypass_shell' => true]);
    if (!is_resource($process)) throw new RuntimeException('Could not start Codex CLI');
    fwrite($pipes[0], $instruction);
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $started = microtime(true);
    $lastHeartbeat = 0.0;
    $stdout = '';
    $stderr = '';
    $exitCode = -1;
    try {
        while (true) {
            $status = proc_get_status($process);
            $stdout .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);
            if (strlen($stdout) > 1048576) $stdout = substr($stdout, -1048576);
            if (strlen($stderr) > 1048576) $stderr = substr($stderr, -1048576);

            $now = microtime(true);
            if ($now - $lastHeartbeat >= 2.0) {
                $emitProgress([
                    'type' => 'active', 'completed' => $completed, 'total' => $total,
                    'card' => $cardID, 'elapsed' => (int)($now - $started),
                ]);
                $lastHeartbeat = $now;
            }
            if (!$status['running']) {
                $exitCode = (int)$status['exitcode'];
                break;
            }
            if ($now - $started > 1200) {
                proc_terminate($process);
                throw new RuntimeException('Codex image edit timed out after 20 minutes');
            }
            usleep(200000);
        }
    } finally {
        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $closeCode = proc_close($process);
        if ($exitCode < 0 && $closeCode >= 0) $exitCode = $closeCode;
    }

    if ($exitCode !== 0) {
        $detail = trim($stderr) ?: trim($stdout);
        if (strlen($detail) > 600) $detail = substr($detail, -600);
        throw new RuntimeException('Codex exited with code ' . $exitCode . ($detail !== '' ? ': ' . $detail : ''));
    }
    if ((!is_file($output) || filesize($output) === 0)
        && !CropTesterCollectGeneratedImage($codexPath, $stdout, $beforeImages, $output)) {
        $detail = trim($stdout);
        if (strlen($detail) > 600) $detail = substr($detail, -600);
        throw new RuntimeException('Codex completed without returning a collectible image' . ($detail !== '' ? ': ' . $detail : ''));
    }
}

// Build a JS-friendly bundle: per scenario, its defaults + an actual sample pool.
$jsScenarios = [];
foreach ($scenarios as $key => $s) {
    $pool = [];
    foreach ($s['types'] as $t) {
        foreach (($samplesByType[$t] ?? []) as $id) {
            $pool[$id] = ($titlesById[$id] ?? $id);
        }
    }
    if (!$pool) $pool = $allSamples;
    $scenarioImageIds = CropTesterScenarioImageIds($s, $allImageIds, $cardTypes);
    $reviewImages = [];
    foreach ($scenarioImageIds as $id) {
        $reviewImages[$id] = isset($cardTitles[$id]) ? (string)$cardTitles[$id] : ($titlesById[$id] ?? $id);
    }
    $s['regenerateCount'] = count($scenarioImageIds);
    $s['reviewImages'] = $reviewImages;
    $s['samples'] = $pool;
    $jsScenarios[$key] = $s;
}

$batchAction = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && in_array($batchAction, ['regenerate', 'regenerate_ai'], true)) {
    header('Content-Type: application/json; charset=utf-8');
    ob_start();
    include_once $ROOT . '/AccountFiles/AccountSessionAPI.php';
    ob_end_clean();
    $authError = CheckLoggedInUserMod();
    if ($authError !== '') {
        http_response_code(403);
        echo json_encode(['error' => $authError]);
        exit;
    }

    CheckSession();
    $sessionToken = isset($_SESSION['crop_tester_csrf']) ? (string)$_SESSION['crop_tester_csrf'] : '';
    $requestToken = isset($_POST['csrf']) && is_string($_POST['csrf']) ? $_POST['csrf'] : '';
    if ($sessionToken === '' || !hash_equals($sessionToken, $requestToken)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid security token; reload the crop tester and try again']);
        exit;
    }
    session_write_close();

    $scenarioKey = isset($_POST['scenario']) && is_string($_POST['scenario']) ? $_POST['scenario'] : '';
    if (!isset($scenarios[$scenarioKey])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid crop scenario']);
        exit;
    }
    $isAI = $batchAction === 'regenerate_ai';
    $sections = CropTesterParseSections($_POST['s'] ?? []);
    if (!$isAI && !$sections) {
        http_response_code(400);
        echo json_encode(['error' => 'No valid crop sections']);
        exit;
    }

    $scenario = $scenarios[$scenarioKey];
    $pipeline = $scenario['pipeline'];
    if ($isAI && $pipeline !== 'concat') {
        http_response_code(400);
        echo json_encode(['error' => 'AI image editing is available only for concat scenarios']);
        exit;
    }
    $aiPrompt = isset($_POST['prompt']) && is_string($_POST['prompt']) ? trim($_POST['prompt']) : '';
    if ($isAI && ($aiPrompt === '' || strlen($aiPrompt) > 4000)) {
        http_response_code(400);
        echo json_encode(['error' => 'Enter an AI image-editing prompt of 1–4000 characters']);
        exit;
    }
    $codexPath = $isAI ? CropTesterFindCodexCLI() : null;
    if ($isAI && $codexPath === null) {
        http_response_code(500);
        echo json_encode(['error' => 'Codex CLI was not found. Set CODEX_CLI_PATH for the Apache process.']);
        exit;
    }
    $format = $pipeline === 'crop' ? 'png' : 'webp';
    $suffix = $pipeline === 'crop' ? '_cropped.png' : '.webp';
    $destinationDirectory = $APP_ROOT . '/' . ($pipeline === 'crop' ? 'crops' : 'concat');
    $candidateIDs = CropTesterScenarioImageIds($scenario, $allImageIds, $cardTypes);
    $selectionMode = $isAI && (($_POST['selection_mode'] ?? '') === 'selected');
    if ($selectionMode) {
        $requestedIDs = $_POST['cards'] ?? [];
        if (!is_array($requestedIDs) || count($requestedIDs) > count($candidateIDs)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid review queue']);
            exit;
        }
        $requestedSet = [];
        foreach ($requestedIDs as $requestedID) {
            if (is_string($requestedID)) $requestedSet[$requestedID] = true;
        }
        $allowedSet = array_fill_keys($candidateIDs, true);
        if (!$requestedSet || array_diff_key($requestedSet, $allowedSet)) {
            http_response_code(400);
            echo json_encode(['error' => 'The review queue is empty or contains cards outside this scenario']);
            exit;
        }
        $candidateIDs = array_values(array_filter($candidateIDs, static function ($id) use ($requestedSet) {
            return isset($requestedSet[$id]);
        }));
    }
    $completed = 0;
    $failed = 0;
    $consecutiveFailures = 0;
    $stagingDirectory = null;
    if ($isAI) {
        $stagingDirectory = $APP_ROOT . '/TempImages/CodexConcat/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3));
        if (!mkdir($stagingDirectory, 0755, true) && !is_dir($stagingDirectory)) {
            http_response_code(500);
            echo json_encode(['error' => 'Could not create the Codex staging directory']);
            exit;
        }
    }
    set_time_limit(0);
    ignore_user_abort(true);

    ini_set('zlib.output_compression', '0');
    header('Content-Type: application/x-ndjson; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Accel-Buffering: no');
    while (ob_get_level() > 0) {
        if (!@ob_end_flush()) break;
    }
    $emitProgress = static function ($payload) {
        echo json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n";
        flush();
    };

    try {
        $emitProgress(['type' => 'start', 'total' => count($candidateIDs), 'pipeline' => $pipeline, 'app' => $app, 'mode' => $isAI ? 'ai' : 'crop']);
        foreach ($candidateIDs as $id) {
            $source = $IMG_BASE . $id . '.webp';
            $destination = $destinationDirectory . '/' . $id . $suffix;
            if ($isAI) {
                $stagedOutput = $stagingDirectory . '/' . $id . '.png';
                try {
                    CropTesterRunCodexImageEdit(
                        $codexPath, $stagingDirectory, $source, $stagedOutput, $aiPrompt,
                        $emitProgress, $id, $completed + $failed, count($candidateIDs)
                    );
                    $image = CropTesterNormalizeGeneratedImage($stagedOutput);
                    try {
                        CropTesterWriteImage($image, $destination, 'webp');
                    } finally {
                        $image->clear();
                        $image->destroy();
                    }
                    if (is_file($stagedOutput)) unlink($stagedOutput);
                    $completed++;
                    $consecutiveFailures = 0;
                    $emitProgress(['type' => 'progress', 'completed' => $completed, 'failed' => $failed, 'total' => count($candidateIDs), 'card' => $id]);
                } catch (Throwable $cardError) {
                    $failed++;
                    $consecutiveFailures++;
                    $emitProgress(['type' => 'failed', 'completed' => $completed, 'failed' => $failed, 'total' => count($candidateIDs), 'card' => $id, 'error' => $cardError->getMessage()]);
                    if ($consecutiveFailures >= 3) {
                        throw new RuntimeException('Stopped after 3 consecutive Codex failures. Last error: ' . $cardError->getMessage());
                    }
                }
                continue;
            }
            $image = CropTesterBuildImage($source, $sections, $scenario['outW'] ?? 0, $scenario['outH'] ?? 0);
            try {
                CropTesterWriteImage($image, $destination, $format);
            } finally {
                $image->clear();
                $image->destroy();
            }
            $completed++;
            $emitProgress(['type' => 'progress', 'completed' => $completed, 'total' => count($candidateIDs), 'card' => $id]);
        }
        if ($stagingDirectory !== null && is_dir($stagingDirectory)) @rmdir($stagingDirectory);
        $emitProgress(['type' => 'complete', 'ok' => true, 'count' => $completed, 'failed' => $failed, 'pipeline' => $pipeline, 'app' => $app]);
    } catch (Throwable $e) {
        if ($stagingDirectory !== null && is_dir($stagingDirectory) && count(glob($stagingDirectory . '/*') ?: []) === 0) {
            @rmdir($stagingDirectory);
        }
        $emitProgress(['type' => 'error', 'error' => 'Regeneration stopped after ' . $completed . ' images: ' . $e->getMessage()]);
    }
    exit;
}

ob_start();
include_once $ROOT . '/AccountFiles/AccountSessionAPI.php';
ob_end_clean();
CheckSession();
if (empty($_SESSION['crop_tester_csrf'])) $_SESSION['crop_tester_csrf'] = bin2hex(random_bytes(32));
$cropTesterCsrf = (string)$_SESSION['crop_tester_csrf'];
$cropTesterAuthError = CheckLoggedInUserMod();
$codexCliAvailable = CropTesterFindCodexCLI() !== null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($app, ENT_QUOTES, 'UTF-8') ?> Crop Tester</title>
<style>
  :root { --bg:#11151c; --panel:#1b2430; --line:#2d3a4a; --txt:#dfe7ef; --muted:#8aa0b5; --accent:#4fa3ff; }
  * { box-sizing: border-box; }
  body { margin:0; background:var(--bg); color:var(--txt); font:14px/1.5 system-ui,sans-serif; }
  header { padding:14px 20px; border-bottom:1px solid var(--line); background:var(--panel); }
  header h1 { margin:0; font-size:17px; }
  header p { margin:4px 0 0; color:var(--muted); font-size:12px; }
  .wrap { display:grid; grid-template-columns:320px 1fr; gap:0; min-height:calc(100vh - 60px); }
  .controls { padding:18px 20px; border-right:1px solid var(--line); background:var(--panel); }
  .stage { padding:18px 24px; overflow:auto; }
  label.fld { display:block; margin:0 0 4px; color:var(--muted); font-size:12px; text-transform:uppercase; letter-spacing:.4px; }
  select, input[type=text], textarea { width:100%; padding:7px 9px; margin-bottom:14px; background:#0e1218; color:var(--txt);
    border:1px solid var(--line); border-radius:6px; font:13px monospace; }
  textarea { min-height:116px; resize:vertical; font:12px/1.45 system-ui,sans-serif; }
  .sections { margin-bottom:12px; }
  .sec-row { display:grid; grid-template-columns:18px repeat(4,1fr) 26px; gap:5px; align-items:center; margin-bottom:6px; }
  .sec-row input { width:100%; padding:5px 4px; background:#0e1218; color:var(--txt); border:1px solid var(--line);
    border-radius:5px; font:12px monospace; text-align:center; margin:0; }
  .sec-row .tag { color:var(--muted); font:11px monospace; text-align:center; }
  .sec-head { display:grid; grid-template-columns:18px repeat(4,1fr) 26px; gap:5px; color:var(--muted);
    font:10px/1.2 monospace; text-transform:uppercase; margin-bottom:4px; }
  .sec-head span { text-align:center; }
  button { cursor:pointer; border:1px solid var(--line); background:#22303f; color:var(--txt);
    padding:6px 10px; border-radius:6px; font-size:12px; }
  button:hover:not(:disabled) { border-color:var(--accent); }
  button:disabled { cursor:not-allowed; opacity:.55; }
  button.x { background:#3a2230; padding:2px 6px; }
  .btnrow { display:flex; gap:8px; margin:6px 0 16px; }
  .regen { width:100%; padding:9px 12px; margin:0 0 8px; border-color:#287dcc; background:#1769aa; color:#fff; font-weight:700; }
  .regen-status { min-height:36px; margin:0 0 15px; color:var(--muted); font-size:12px; }
  .regen-status.success { color:#72d99c; }
  .regen-status.error { color:#ff8d8d; }
  .progress-wrap { margin:0 0 14px; }
  progress { width:100%; height:14px; accent-color:var(--accent); }
  .progress-meta { display:flex; justify-content:space-between; gap:10px; color:var(--muted); font:11px monospace; }
  .ai-tools { margin-top:16px; padding-top:15px; border-top:1px solid var(--line); }
  .ai-help { margin:-8px 0 10px; color:var(--muted); font-size:11px; }
  .out { color:var(--muted); font:12px monospace; }
  .panels { display:flex; gap:26px; flex-wrap:wrap; align-items:flex-start; }
  .view-tabs { display:flex; gap:6px; margin:0 0 18px; border-bottom:1px solid var(--line); }
  .view-tab { border:0; border-radius:6px 6px 0 0; background:transparent; color:var(--muted); padding:9px 14px; }
  .view-tab.active { background:#22303f; color:var(--txt); box-shadow:0 2px 0 var(--accent); }
  .review-toolbar { display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-bottom:14px; }
  .review-toolbar input { width:min(360px, 100%); margin:0; }
  .review-toolbar .spacer { flex:1; }
  .review-count { color:var(--muted); font:12px monospace; }
  .review-gallery { display:grid; grid-template-columns:repeat(auto-fill, minmax(155px, 1fr)); gap:12px; }
  .review-card { display:block; position:relative; padding:7px; border:1px solid var(--line); border-radius:8px; background:#111821; cursor:pointer; }
  .review-card:hover { border-color:#54718e; }
  .review-card.queued { border-color:var(--accent); background:#14283b; box-shadow:0 0 0 1px var(--accent) inset; }
  .review-card input { position:absolute; top:13px; right:13px; width:18px; height:18px; margin:0; accent-color:var(--accent); z-index:1; }
  .review-card img { display:block; width:100%; aspect-ratio:1; object-fit:cover; border-radius:5px; background:#090d12; }
  .review-card .review-id { margin-top:7px; color:var(--txt); font:11px/1.3 monospace; overflow-wrap:anywhere; }
  .review-card .review-title { margin-top:2px; color:var(--muted); font-size:11px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .review-empty { color:var(--muted); padding:30px 0; }
  .card-col h3 { margin:0 0 8px; font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:.4px; }
  .src-box { position:relative; display:inline-block; line-height:0; border:1px solid var(--line); border-radius:4px; overflow:hidden; }
  .src-box img { display:block; }
  .rect { position:absolute; border:2px solid var(--accent); background:rgba(79,163,255,.14);
    box-shadow:0 0 0 1px #000 inset; pointer-events:none; }
  .rect .num { position:absolute; top:-1px; left:-1px; background:var(--accent); color:#001; font:bold 11px monospace; padding:0 4px; }
  .preview img { display:block; border:1px solid var(--line); border-radius:4px; background:
    repeating-conic-gradient(#222 0% 25%, #2b2b2b 0% 50%) 50%/16px 16px; }
  .dims { color:var(--muted); font:11px monospace; margin-top:6px; }
  pre.snip { background:#0b0f14; border:1px solid var(--line); border-radius:6px; padding:12px;
    color:#a9d6a0; font:12px/1.5 monospace; white-space:pre-wrap; margin-top:20px; max-width:760px; }
  a { color:var(--accent); }
</style>
</head>
<body>
<header>
  <h1><?= htmlspecialchars($app, ENT_QUOTES, 'UTF-8') ?> Crop Tester</h1>
  <p>Reads local <code><?= htmlspecialchars($app, ENT_QUOTES, 'UTF-8') ?>/WebpImages/</code>. Tune the crop sections, compare against the committed output, then regenerate the app's matching local images directly from this page.</p>
</header>
<div class="wrap">
  <div class="controls">
    <label class="fld">Application</label>
    <select id="app">
      <?php foreach ($availableApps as $availableApp): ?>
        <option value="<?= htmlspecialchars($availableApp, ENT_QUOTES, 'UTF-8') ?>"<?= $availableApp === $app ? ' selected' : '' ?>><?= htmlspecialchars($availableApp, ENT_QUOTES, 'UTF-8') ?></option>
      <?php endforeach; ?>
    </select>

    <label class="fld">Scenario (zzImageConverter branch)</label>
    <select id="scenario"></select>

    <label class="fld">Sample card</label>
    <select id="card"></select>

    <label class="fld">Or any cardID</label>
    <input type="text" id="cardManual" placeholder="e.g. JTL_012 or SOR_005_back">

    <label class="fld">Crop sections — sx, sy, w, h (stacked top→bottom)</label>
    <div class="sec-head"><span>#</span><span>sx</span><span>sy</span><span>w</span><span>h</span><span></span></div>
    <div class="sections" id="sections"></div>
    <div class="btnrow">
      <button id="addSec">+ section</button>
      <button id="resetSec">reset to default</button>
    </div>
    <button class="regen" id="regenerateAll">Regenerate images</button>
    <div class="ai-tools" id="aiTools">
      <label class="fld" for="aiPrompt">Codex image-editing prompt</label>
      <textarea id="aiPrompt">Keep the composition and card identity unchanged. Remove the partial rules textbox at the bottom, naturally extend the existing illustration into that space, and make the black card border clean, continuous, and uniform on every edge.</textarea>
      <p class="ai-help">Runs one <code>$imagegen</code> edit per matching source card. Successful 450×450 results replace the corresponding concat WebP.</p>
      <button class="regen" id="regenerateAI">AI-regenerate concat images</button>
    </div>
    <div class="progress-wrap" id="progressWrap" hidden>
      <progress id="regenerateProgress" value="0" max="1"></progress>
      <div class="progress-meta"><span id="progressCount">0 / 0</span><span id="progressCard"></span></div>
    </div>
    <div class="regen-status" id="regenerateStatus" role="status" aria-live="polite"></div>
    <div class="out" id="outdims"></div>
  </div>

  <div class="stage">
    <div class="view-tabs" role="tablist" aria-label="Crop tester views">
      <button class="view-tab active" id="editorTab" type="button" role="tab" aria-selected="true">Crop editor</button>
      <button class="view-tab" id="reviewTab" type="button" role="tab" aria-selected="false">AI review &amp; retry</button>
    </div>
    <div id="editorView">
      <div class="panels">
      <div class="card-col">
        <h3>Source + crop overlay</h3>
        <div class="src-box" id="srcBox"><img id="srcImg" alt="source"></div>
        <div class="dims" id="srcDims"></div>
      </div>
      <div class="card-col preview">
        <h3>New output (live)</h3>
        <img id="prevImg" alt="preview">
        <div class="dims" id="prevDims"></div>
      </div>
      <div class="card-col preview">
        <h3>Committed (current)</h3>
        <img id="curImg" alt="current">
        <div class="dims">on disk</div>
      </div>
      </div>
      <pre class="snip" id="snippet"></pre>
    </div>
    <div id="reviewView" hidden>
      <div class="review-toolbar">
        <input type="search" id="reviewSearch" placeholder="Filter by card ID or title">
        <span class="review-count" id="reviewVisibleCount"></span>
        <span class="spacer"></span>
        <button type="button" id="queueVisible">Queue visible</button>
        <button type="button" id="clearQueue">Clear queue</button>
        <button type="button" id="retryQueued">Re-run selected (0)</button>
      </div>
      <div class="review-gallery" id="reviewGallery"></div>
      <div class="review-empty" id="reviewEmpty" hidden>No concat images match this filter.</div>
    </div>
  </div>
</div>

<script>
const SCEN = <?php echo json_encode($jsScenarios, JSON_UNESCAPED_SLASHES); ?>;
const CONCAT_WEB = <?php echo json_encode($CONCAT_WEB); ?>;
const CROP_WEB   = <?php echo json_encode($CROP_WEB); ?>;
const ACTIVE_APP = <?php echo json_encode($app); ?>;
const CROP_CSRF  = <?php echo json_encode($cropTesterCsrf); ?>;
const REGEN_AUTH_ERROR = <?php echo json_encode($cropTesterAuthError); ?>;
const CODEX_CLI_AVAILABLE = <?= $codexCliAvailable ? 'true' : 'false' ?>;

const $ = id => document.getElementById(id);
let curSections = [];
let reviewQueue = new Set();
let visibleReviewIds = [];
let reviewRevision = Date.now();
let activeQueuedBatch = null;
let activeQueueStorageKey = '';

// Populate scenario dropdown.
for (const [k, s] of Object.entries(SCEN)) {
  const o = document.createElement('option'); o.value = k; o.textContent = s.label;
  $('scenario').appendChild(o);
}

function loadScenario() {
  const s = SCEN[$('scenario').value];
  // cards
  $('card').innerHTML = '';
  const ids = Object.keys(s.samples);
  if (!ids.length) {
    const o = document.createElement('option'); o.textContent = '(no local samples)'; o.value = '';
    $('card').appendChild(o);
  }
  for (const id of ids) {
    const o = document.createElement('option'); o.value = id; o.textContent = id + ' — ' + s.samples[id];
    $('card').appendChild(o);
  }
  const scope = s.types && s.types.length ? 'matching' : 'all';
  const outputName = s.pipeline === 'crop' ? 'crop' : 'concat';
  $('regenerateAll').textContent = `Regenerate ${scope} ${s.regenerateCount} ${outputName} images`;
  $('regenerateAll').disabled = Boolean(REGEN_AUTH_ERROR);
  $('aiTools').hidden = s.pipeline !== 'concat';
  $('regenerateAI').textContent = `AI-regenerate ${scope} ${s.regenerateCount} concat images`;
  $('regenerateAI').disabled = Boolean(REGEN_AUTH_ERROR) || !CODEX_CLI_AVAILABLE || s.pipeline !== 'concat';
  $('reviewTab').disabled = s.pipeline !== 'concat';
  $('regenerateStatus').textContent = REGEN_AUTH_ERROR;
  $('regenerateStatus').className = REGEN_AUTH_ERROR ? 'regen-status error' : 'regen-status';
  if (s.pipeline !== 'concat') setView('editor');
  loadReviewQueue();
  resetSections();
}

function reviewStorageKey() {
  return `tcg-crop-review:${ACTIVE_APP}:${$('scenario').value}`;
}

function saveReviewQueue() {
  try { localStorage.setItem(reviewStorageKey(), JSON.stringify([...reviewQueue])); }
  catch (_) { /* The queue still works for this page session. */ }
}

function loadReviewQueue() {
  const valid = new Set(Object.keys(SCEN[$('scenario').value].reviewImages || {}));
  let saved = [];
  try { saved = JSON.parse(localStorage.getItem(reviewStorageKey()) || '[]'); }
  catch (_) { saved = []; }
  reviewQueue = new Set(Array.isArray(saved) ? saved.filter(id => valid.has(id)) : []);
  renderReviewGallery();
}

function setView(view) {
  const reviewing = view === 'review' && !$('reviewTab').disabled;
  $('editorView').hidden = reviewing;
  $('reviewView').hidden = !reviewing;
  $('editorTab').classList.toggle('active', !reviewing);
  $('reviewTab').classList.toggle('active', reviewing);
  $('editorTab').setAttribute('aria-selected', String(!reviewing));
  $('reviewTab').setAttribute('aria-selected', String(reviewing));
  if (reviewing) renderReviewGallery();
}

function syncReviewControls() {
  $('reviewVisibleCount').textContent = `${visibleReviewIds.length} shown · ${reviewQueue.size} queued`;
  $('retryQueued').textContent = `Re-run selected (${reviewQueue.size})`;
  $('retryQueued').disabled = !reviewQueue.size || Boolean(REGEN_AUTH_ERROR) || !CODEX_CLI_AVAILABLE;
  $('clearQueue').disabled = !reviewQueue.size;
  $('queueVisible').disabled = !visibleReviewIds.length;
}

function renderReviewGallery() {
  const s = SCEN[$('scenario').value];
  const images = s.reviewImages || {};
  const needle = $('reviewSearch').value.trim().toLowerCase();
  visibleReviewIds = Object.keys(images).filter(id => {
    return !needle || id.toLowerCase().includes(needle) || String(images[id]).toLowerCase().includes(needle);
  });
  const gallery = $('reviewGallery');
  gallery.innerHTML = '';
  for (const id of visibleReviewIds) {
    const card = document.createElement('label');
    card.className = 'review-card' + (reviewQueue.has(id) ? ' queued' : '');
    card.dataset.cardId = id;
    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.checked = reviewQueue.has(id);
    checkbox.setAttribute('aria-label', `Queue ${id} for regeneration`);
    checkbox.onchange = () => {
      if (checkbox.checked) reviewQueue.add(id); else reviewQueue.delete(id);
      card.classList.toggle('queued', checkbox.checked);
      saveReviewQueue();
      syncReviewControls();
    };
    const image = document.createElement('img');
    image.loading = 'lazy';
    image.alt = `${id} generated concat`;
    image.src = CONCAT_WEB + encodeURIComponent(id) + `.webp?_=${reviewRevision}`;
    const idText = document.createElement('div');
    idText.className = 'review-id'; idText.textContent = id;
    const title = document.createElement('div');
    title.className = 'review-title'; title.textContent = images[id]; title.title = images[id];
    card.append(checkbox, image, idText, title);
    gallery.appendChild(card);
  }
  $('reviewEmpty').hidden = visibleReviewIds.length !== 0;
  syncReviewControls();
}

function markQueuedCardComplete(cardID) {
  if (!activeQueuedBatch || !activeQueuedBatch.has(cardID)) return;
  activeQueuedBatch.delete(cardID);
  reviewQueue.delete(cardID);
  try { localStorage.setItem(activeQueueStorageKey, JSON.stringify([...reviewQueue])); }
  catch (_) {}
  const card = [...document.querySelectorAll('.review-card')].find(item => item.dataset.cardId === cardID);
  if (card) {
    card.classList.remove('queued');
    const checkbox = card.querySelector('input[type="checkbox"]');
    const image = card.querySelector('img');
    if (checkbox) checkbox.checked = false;
    if (image) image.src = CONCAT_WEB + encodeURIComponent(cardID) + `.webp?_=${Date.now()}`;
  }
  syncReviewControls();
}

function resetSections() {
  const s = SCEN[$('scenario').value];
  curSections = s.sections.map(r => r.slice());
  renderSectionInputs();
  refresh();
}

function renderSectionInputs() {
  const box = $('sections'); box.innerHTML = '';
  curSections.forEach((r, i) => {
    const row = document.createElement('div'); row.className = 'sec-row';
    const tag = document.createElement('div'); tag.className = 'tag'; tag.textContent = i + 1; row.appendChild(tag);
    r.forEach((v, j) => {
      const inp = document.createElement('input'); inp.type = 'number'; inp.value = v;
      inp.oninput = () => { curSections[i][j] = parseInt(inp.value || '0', 10); refresh(); };
      row.appendChild(inp);
    });
    const del = document.createElement('button'); del.className = 'x'; del.textContent = '×';
    del.onclick = () => { curSections.splice(i, 1); renderSectionInputs(); refresh(); };
    row.appendChild(del);
    box.appendChild(row);
  });
}

function activeCard() {
  return $('cardManual').value.trim() || $('card').value;
}

function sectionParams() {
  // NB: must be s[] — PHP keeps only the last value of a repeated bare key.
  return curSections.map(r => 's%5B%5D=' + r.join(',')).join('&');
}

function refresh() {
  const s = SCEN[$('scenario').value];
  const card = activeCard();
  if (!card) return;

  // Source image.
  $('srcImg').src = '<?php echo $IMG_WEB; ?>' + encodeURIComponent(card) + '.webp';

  // Live preview.
  const fmt = s.fmt;
  let resize = '';
  if (s.outW && s.outH) resize = '&ow=' + s.outW + '&oh=' + s.outH;
  $('prevImg').src = '?app=' + encodeURIComponent(ACTIVE_APP) + '&render=1&card=' + encodeURIComponent(card) + '&fmt=' + fmt + resize + '&' + sectionParams() + '&_=' + Date.now();

  // Committed file for comparison.
  if (s.pipeline === 'concat') $('curImg').src = CONCAT_WEB + encodeURIComponent(card) + '.webp?_=' + Date.now();
  else                         $('curImg').src = CROP_WEB + encodeURIComponent(card) + '_cropped.png?_=' + Date.now();

  // Output dims.
  let outW = 0, outH = 0;
  curSections.forEach(r => { outW = Math.max(outW, r[2]); outH += r[3]; });
  if (s.outW && s.outH) { outW = s.outW; outH = s.outH; }
  $('outdims').textContent = 'output ' + outW + '×' + outH + (curSections.length > 1 ? ' (' + curSections.length + ' stacked)' : '');
  $('prevDims').textContent = outW + '×' + outH;

  drawOverlay();
  writeSnippet(s, card);
}

function drawOverlay() {
  const img = $('srcImg'), box = $('srcBox');
  // remove old rects
  box.querySelectorAll('.rect').forEach(e => e.remove());
  if (!img.naturalWidth) return;
  const scale = img.clientWidth / img.naturalWidth;   // uniform (aspect preserved)
  $('srcDims').textContent = img.naturalWidth + '×' + img.naturalHeight + ' source  ·  shown @ ' + (scale * 100).toFixed(0) + '%';
  curSections.forEach((r, i) => {
    const d = document.createElement('div'); d.className = 'rect';
    d.style.left = (r[0] * scale) + 'px';
    d.style.top = (r[1] * scale) + 'px';
    d.style.width = (r[2] * scale) + 'px';
    d.style.height = (r[3] * scale) + 'px';
    const n = document.createElement('span'); n.className = 'num'; n.textContent = i + 1; d.appendChild(n);
    box.appendChild(d);
  });
}

function writeSnippet(s, card) {
  let code;
  if (s.snippet === '_concatSingleCrop') {
    const r = curSections[0];
    const output = s.outW && s.outH ? `, ${s.outW}, ${s.outH}` : '';
    code = `// concat single-crop\n_concatSingleCrop($filename, $concatFilename, $cardID, ${r[0]}, ${r[1]}, ${r[2]}, ${r[3]}${output});`;
  } else if (s.snippet === '_concatTwoSection') {
    const a = curSections[0], b = curSections[1] || [0,0,0,0];
    const output = s.outW && s.outH ? `, outW:${s.outW}, outH:${s.outH}` : '';
    code = `// concat two-section\n_concatTwoSection($filename, $concatFilename, $cardID,\n    topSrcY:${a[1]}, topH:${a[3]}, botSrcY:${b[1]}, botH:${b[3]}${output});`;
    if (curSections.length !== 2)
      code = `// NOTE: _concatTwoSection expects exactly 2 sections; you have ${curSections.length}.\n` + code;
  } else { // art crop
    const r = curSections[0];
    code = `// crops/ art thumbnail (Imagick)\n$image->cropImage(${r[2]}, ${r[3]}, ${r[0]}, ${r[1]});`;
  }
  $('snippet').textContent = code;
}

function resetProgress(total) {
  $('progressWrap').hidden = false;
  $('regenerateProgress').max = Math.max(1, total);
  $('regenerateProgress').value = 0;
  $('progressCount').textContent = `0 / ${total}`;
  $('progressCard').textContent = '';
}

function applyProgressEvent(event) {
  if (event.type === 'start') resetProgress(Number(event.total || 0));
  if (event.type === 'active') {
    $('regenerateProgress').value = Number(event.completed || 0) + Number(event.failed || 0);
    $('progressCard').textContent = `${event.card || ''} · ${event.elapsed || 0}s`;
  }
  if (event.type === 'progress' || event.type === 'failed') {
    const handled = Number(event.completed || 0) + Number(event.failed || 0);
    $('regenerateProgress').value = handled;
    $('progressCount').textContent = `${handled} / ${event.total}${event.failed ? ` · ${event.failed} failed` : ''}`;
    $('progressCard').textContent = event.card || '';
    if (event.type === 'failed') {
      $('regenerateStatus').textContent = `${event.card}: ${event.error}`;
      $('regenerateStatus').className = 'regen-status error';
    }
    if (event.type === 'progress') markQueuedCardComplete(event.card || '');
  }
  if (event.type === 'complete') {
    const handled = Number(event.count || 0) + Number(event.failed || 0);
    $('regenerateProgress').value = handled;
    $('progressCount').textContent = `${handled} / ${$('regenerateProgress').max}${event.failed ? ` · ${event.failed} failed` : ''}`;
    $('progressCard').textContent = 'Complete';
  }
  if (event.type === 'error') throw new Error(event.error || 'Batch regeneration failed.');
}

async function consumeBatchResponse(response) {
  const contentType = response.headers.get('content-type') || '';
  if (!response.ok || !contentType.includes('application/x-ndjson')) {
    let payload;
    try { payload = await response.json(); }
    catch (_) { payload = { error: `HTTP ${response.status}` }; }
    if (!response.ok || !payload.ok) throw new Error(payload.error || `HTTP ${response.status}`);
    return payload;
  }

  const reader = response.body.getReader();
  const decoder = new TextDecoder();
  let buffer = '';
  let completedEvent = null;
  while (true) {
    const { value, done } = await reader.read();
    buffer += decoder.decode(value || new Uint8Array(), { stream: !done });
    const lines = buffer.split('\n');
    buffer = lines.pop() || '';
    for (const line of lines) {
      if (!line.trim()) continue;
      const event = JSON.parse(line);
      applyProgressEvent(event);
      if (event.type === 'complete') completedEvent = event;
    }
    if (done) break;
  }
  if (buffer.trim()) {
    const event = JSON.parse(buffer);
    applyProgressEvent(event);
    if (event.type === 'complete') completedEvent = event;
  }
  if (!completedEvent) throw new Error('The batch ended without a completion result.');
  return completedEvent;
}

async function runBatch(useAI, selectedIDs = null) {
  if (REGEN_AUTH_ERROR) return;
  const s = SCEN[$('scenario').value];
  const selectedQueue = Array.isArray(selectedIDs) ? [...new Set(selectedIDs)] : null;
  const count = selectedQueue ? selectedQueue.length : Number(s.regenerateCount || 0);
  if (!count) {
    $('regenerateStatus').textContent = 'No source images match this scenario.';
    $('regenerateStatus').className = 'regen-status error';
    return;
  }
  if (useAI && !CODEX_CLI_AVAILABLE) {
    $('regenerateStatus').textContent = 'Codex CLI was not found. Set CODEX_CLI_PATH for Apache.';
    $('regenerateStatus').className = 'regen-status error';
    return;
  }
  const destination = useAI || s.pipeline === 'concat' ? `${ACTIVE_APP}/concat/` : `${ACTIVE_APP}/crops/`;
  const confirmation = useAI
    ? `Run ${count} Codex $imagegen edit${count === 1 ? '' : 's'} and replace successful images in ${destination}? This consumes image-generation usage and may take a long time.`
    : `Replace ${count} generated images in ${destination} using the crop sections currently shown?`;
  if (!window.confirm(confirmation)) return;

  const button = selectedQueue ? $('retryQueued') : (useAI ? $('regenerateAI') : $('regenerateAll'));
  const status = $('regenerateStatus');
  $('regenerateAll').disabled = true;
  $('regenerateAI').disabled = true;
  $('retryQueued').disabled = true;
  $('app').disabled = true;
  $('scenario').disabled = true;
  button.disabled = true;
  status.className = 'regen-status';
  status.textContent = useAI ? `Starting ${count} Codex image edits…` : `Regenerating ${count} images…`;
  resetProgress(count);
  const form = new FormData();
  form.set('action', useAI ? 'regenerate_ai' : 'regenerate');
  form.set('csrf', CROP_CSRF);
  form.set('scenario', $('scenario').value);
  if (useAI) form.set('prompt', $('aiPrompt').value);
  if (selectedQueue) {
    form.set('selection_mode', 'selected');
    selectedQueue.forEach(id => form.append('cards[]', id));
    activeQueuedBatch = new Set(selectedQueue);
    activeQueueStorageKey = reviewStorageKey();
  }
  curSections.forEach(section => form.append('s[]', section.join(',')));

  try {
    const url = new URL(window.location.href);
    url.search = '';
    url.searchParams.set('app', ACTIVE_APP);
    const response = await fetch(url, { method: 'POST', body: form, credentials: 'same-origin' });
    const payload = await consumeBatchResponse(response);
    status.textContent = payload.failed
      ? `Finished: ${payload.count} regenerated, ${payload.failed} failed. Successful images are in ${destination}`
      : `Regenerated ${payload.count} images in ${destination}`;
    status.className = payload.failed ? 'regen-status error' : 'regen-status success';
    refresh();
    reviewRevision = Date.now();
  } catch (error) {
    status.textContent = error.message || 'Image regeneration failed.';
    status.className = 'regen-status error';
  } finally {
    $('regenerateAll').disabled = Boolean(REGEN_AUTH_ERROR);
    $('regenerateAI').disabled = Boolean(REGEN_AUTH_ERROR) || !CODEX_CLI_AVAILABLE || s.pipeline !== 'concat';
    $('app').disabled = false;
    $('scenario').disabled = false;
    activeQueuedBatch = null;
    activeQueueStorageKey = '';
    syncReviewControls();
  }
}

function regenerateAll() { return runBatch(false); }
function regenerateAllAI() { return runBatch(true); }
function regenerateQueuedAI() { return runBatch(true, [...reviewQueue]); }

$('scenario').onchange = loadScenario;
$('app').onchange = () => {
  const url = new URL(window.location.href);
  url.search = '';
  url.searchParams.set('app', $('app').value);
  window.location.href = url;
};
$('card').onchange = () => { $('cardManual').value = ''; refresh(); };
$('cardManual').oninput = refresh;
$('addSec').onclick = () => { curSections.push([0, 0, SCEN[$('scenario').value].srcW || 450, 50]); renderSectionInputs(); refresh(); };
$('resetSec').onclick = resetSections;
$('regenerateAll').onclick = regenerateAll;
$('regenerateAI').onclick = regenerateAllAI;
$('retryQueued').onclick = regenerateQueuedAI;
$('editorTab').onclick = () => setView('editor');
$('reviewTab').onclick = () => setView('review');
$('reviewSearch').oninput = renderReviewGallery;
$('queueVisible').onclick = () => {
  visibleReviewIds.forEach(id => reviewQueue.add(id));
  saveReviewQueue();
  renderReviewGallery();
};
$('clearQueue').onclick = () => {
  reviewQueue.clear();
  saveReviewQueue();
  renderReviewGallery();
};
$('srcImg').onload = drawOverlay;
window.addEventListener('resize', drawOverlay);

loadScenario();
</script>
</body>
</html>
