<?php
// Localized card art for SWUSim (es / it / fr). A SEPARATE step from zzCardCodeGenerator.php: it never
// touches card data, dictionaries or English art, and it is not part of the generator admin page's
// "Run build pipeline".
//
//   php zzCardI18nImageGenerator.php rootName=SWUSim locale=es [overwriteImages=1|HMW] [cards=SOR_005,SOR_010]
//
// Output: AppCore/SWU/Images/i18n/<locale>/{WebpImages,concat,crops} + manifest.json (rebuilt from disk).
// Requires the English cache from a normal card-generator run (cardUid -> CardID join; see
// AppCore/SWU/CardI18nImages.php for why the IDs cannot be derived from localized records).
// Design: docs/superpowers/specs/2026-09-17-swusim-card-i18n-images-design.md §1

set_time_limit(10800);

include './zzImageConverter.php';   // CheckImage(); also enforces auth for HTTP requests
include './Core/HTTPLibraries.php';
include_once './AccountFiles/AccountSessionAPI.php';
require_once './AppCore/SWU/CardI18nImages.php';

$isHTTPRequest = php_sapi_name() !== 'cli' && !empty($_SERVER['REQUEST_METHOD']);
if (!$isHTTPRequest) {
    foreach ($argv as $arg) {
        if (strpos($arg, '=') !== false) {
            list($key, $value) = explode('=', $arg, 2);
            $_GET[$key] = $value;
        }
    }
}

$error = CheckLoggedInUserMod();
if ($error !== '') { echo "ERROR: $error\n"; exit(); }

function i18nLog($msg) { echo $msg . "<br>\n"; @ob_flush(); @flush(); }

$rootName  = TryGET('rootName', '');
$locale    = TryGET('locale', '');
$overwrite = ImageOverwriteSpec(TryGET('overwriteImages', ''));   // 1 = every image, HMW = that set only
$onlyCards = array_values(array_filter(array_map('trim', explode(',', (string)TryGET('cards', '')))));

if ($rootName !== 'SWUSim') { i18nLog("ERROR: localized card images are only generated for SWUSim (got '" . htmlspecialchars($rootName, ENT_QUOTES, 'UTF-8') . "')."); exit(); }
if ($overwrite['error'] !== null) { i18nLog("ERROR: " . $overwrite['error']); exit(); }
if (!SWUI18nIsLocale($locale)) { i18nLog("ERROR: locale must be one of " . implode(', ', SWUI18nLocales()) . " (got '" . htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') . "')."); exit(); }

$cachePath = __DIR__ . '/SWUSim/GeneratedCode/cardArrayCache.json';
$cache = is_file($cachePath) ? json_decode((string)file_get_contents($cachePath), true) : null;
if (!is_array($cache) || !is_array($cache['cardArray'] ?? null) || count($cache['cardArray']) === 0) {
    i18nLog("ERROR: $cachePath is missing or empty. Run the SWUSim 'Card data & images' step first.");
    exit();
}
$index = SWUI18nEnglishIndex($cache['cardArray']);
unset($cache);

$localeRoot = SWUI18nLocaleRoot($locale);
foreach (['WebpImages', 'concat', 'crops'] as $dir) {
    if (!is_dir("$localeRoot/$dir") && !mkdir("$localeRoot/$dir", 0755, true)) {
        i18nLog("ERROR: could not create $localeRoot/$dir");
        exit();
    }
}
$skipDerived = SWUI18nSkipDerivedTypes($locale);

i18nLog("=== Localized card images: locale=$locale overwrite=" . ($overwrite['mode'] === 'set' ? $overwrite['set'] : $overwrite['mode'])
    . (count($onlyCards) ? ' cards=' . implode(',', $onlyCards) : '') . " ===");

$baseUrl = 'https://admin.starwarsunlimited.com/api/cards?locale=' . rawurlencode($locale)
    . '&pagination[pageSize]=100&filters[variantOf][id][$null]=true&pagination[page]=';

$stats = ['fetched' => 0, 'planned' => 0, 'images' => 0, 'failed' => 0, 'skipped' => []];
$page = 1;
$pageCount = 1;
do {
    $body = null;
    for ($attempt = 1; $attempt <= 3 && $body === null; $attempt++) {
        $ch = curl_init($baseUrl . $page);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw !== false && $status === 200) {
            if (substr($raw, 0, 3) === "\xEF\xBB\xBF") $raw = substr($raw, 3);
            $body = json_decode($raw);
        } else {
            i18nLog("Page $page attempt $attempt failed (HTTP $status); retrying.");
            sleep(3 * $attempt);
        }
    }
    if (!is_object($body) || !is_array($body->data ?? null)) {
        i18nLog("ERROR: could not fetch page $page after 3 attempts.");
        break;
    }
    $pageCount = (int)($body->meta->pagination->pageCount ?? $page);
    i18nLog("Page $page / $pageCount: " . count($body->data) . " records");

    foreach ($body->data as $record) {
        $stats['fetched']++;
        $plan = SWUI18nPlanRecord($record, $index);
        if (isset($plan['skip'])) { $stats['skipped'][$plan['skip']] = ($stats['skipped'][$plan['skip']] ?? 0) + 1; continue; }
        if (count($onlyCards) && !in_array($plan['cardID'], $onlyCards, true)) continue;
        $stats['planned']++;

        $jobs = [[$plan['cardID'], $plan['frontUrl'], $plan['type']]];
        if ($plan['backUrl'] !== null) $jobs[] = [$plan['backID'], $plan['backUrl'], $plan['backType']];
        foreach ($jobs as [$id, $url, $type]) {
            // CheckImage() does not throw on a failed curl download (zzImageConverter.php ~139-143):
            // it echoes a warning and returns, having written nothing. So success/failure must be
            // judged by whether the full-card webp actually landed on disk, not by whether an
            // exception was thrown — an exception during a later stage (concat/crop) can fire AFTER
            // the webp download itself already succeeded, which is still a success for this count.
            $exception = null;
            try {
                CheckImage($id, $url, $type, false, '', rootPath: $localeRoot . '/', overwriteImages: ImageOverwriteRequested($overwrite, $id));
            } catch (Throwable $e) {
                $exception = $e;
            }
            if (file_exists("$localeRoot/WebpImages/$id.webp")) {
                $stats['images']++;
                if ($exception !== null) i18nLog("WARNING: $id: " . $exception->getMessage());
            } else {
                $stats['failed']++;
                i18nLog("WARNING: $id failed" . ($exception !== null ? (': ' . $exception->getMessage()) : ' (no webp written)') . ".");
                continue;
            }
            if (in_array($type, $skipDerived, true)) {
                @unlink("$localeRoot/concat/$id.webp");
                @unlink("$localeRoot/crops/{$id}_cropped.png");
            }
        }
    }
    unset($body);
    // Rebuild the manifest after every processed page (not just at the end) so a killed HTTP run
    // (page count can be in the hundreds) still leaves manifest.json listing exactly the files that
    // made it to disk, instead of no manifest at all.
    $manifest = SWUI18nBuildManifest($locale, $localeRoot, gmdate('Y-m-d\TH:i:s\Z'));
    file_put_contents("$localeRoot/manifest.json", json_encode($manifest, JSON_UNESCAPED_SLASHES));
    $page++;
} while ($page <= $pageCount);

$manifest = SWUI18nBuildManifest($locale, $localeRoot, gmdate('Y-m-d\TH:i:s\Z'));
file_put_contents("$localeRoot/manifest.json", json_encode($manifest, JSON_UNESCAPED_SLASHES));

$skippedText = [];
foreach ($stats['skipped'] as $reason => $n) $skippedText[] = "$reason=$n";
i18nLog("=== Done: fetched {$stats['fetched']}, planned {$stats['planned']}, images {$stats['images']}, failed {$stats['failed']}"
    . ", skipped [" . implode(', ', $skippedText) . "]"
    . "; manifest lists " . count($manifest['WebpImages']) . " full / " . count($manifest['concat']) . " tile / " . count($manifest['crops']) . " crop ===");
