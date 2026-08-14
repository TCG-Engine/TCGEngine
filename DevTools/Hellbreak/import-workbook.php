<?php

declare(strict_types=1);

const HELLBREAK_SOURCE_URL = 'https://onedrive.live.com/:x:/g/personal/8ef2128400f307a7/IQD9qTaoxlAYQZq9RRsDNjXsAaGNuTGXKyi3vU49-R5V7nE?rtime=eIZ9Ihj63kg&redeem=aHR0cHM6Ly8xZHJ2Lm1zL3gvYy84ZWYyMTI4NDAwZjMwN2E3L0lRRDlxVGFveGxBWVFacTlSUnNETmpYc0FhR051VEdYS3lpM3ZVNDktUjVWN25FP2U9RjNOS05D';

function importHellbreakWorkbook(
    string $source,
    string $sheetName = '01 - Dawn of Terror',
    string $targetRoot = 'HellbreakSim',
    bool $extractImages = true,
    string $sourceLabel = ''
): array {
    $repoRoot = dirname(__DIR__, 2);
    $targetRoot = preg_replace('/[^A-Za-z0-9_-]/', '', $targetRoot);
    if ($targetRoot === '') throw new InvalidArgumentException('Invalid target root.');
    $source = trim($source);
    $sheetName = trim($sheetName);
    $temporaryFile = null;

    try {
        $xlsxPath = resolveWorkbook($source, $temporaryFile);
        $import = readWorkbook($xlsxPath, $sheetName);
        [$cards, $warnings, $rowToCard, $dataAudit] = normalizeRows($import['rows']);

        if (!$cards) {
            throw new RuntimeException('No card rows were found. Check the selected sheet and its headers.');
        }

        $dataAudit['reviewedCardFaces'] = applyReviewedCardFaces($cards, $repoRoot, $warnings);
        $dataAudit['cardFaceReviewQueue'] = applyCardFaceReviewQueue($cards, $repoRoot, $warnings);

        $target = $repoRoot . DIRECTORY_SEPARATOR . $targetRoot;
        $generated = $target . DIRECTORY_SEPARATOR . 'GeneratedCode';
        ensureDirectory($generated);

        $imageReport = ['enabled' => $extractImages, 'embedded' => 0, 'external' => 0, 'failed' => 0];
        if ($extractImages) {
            $imageReport = ['enabled' => true] + importImages($xlsxPath, $import, $rowToCard, $cards, $target, $warnings);
        }
        $imageReport += imageInventory($cards, $target);

        $payload = [
            'cardArray' => array_values($cards),
            'reprintMap' => new stdClass(),
            'leaderUnitByUUIDMap' => new stdClass(),
        ];
        writeJson($generated . DIRECTORY_SEPARATOR . 'cardArrayCache.json', $payload);

        $report = [
            'source' => $sourceLabel !== '' ? $sourceLabel : $source,
            'sheet' => $import['sheetName'],
            'importedAt' => gmdate('c'),
            'cards' => count($cards),
            'data' => $dataAudit,
            'images' => $imageReport,
            'warnings' => $warnings,
        ];
        writeJson($generated . DIRECTORY_SEPARATOR . 'HellbreakImportReport.json', $report);

        return $report;
    } finally {
        if ($temporaryFile && is_file($temporaryFile)) @unlink($temporaryFile);
    }
}

function printHellbreakImportSummary(array $report): void
{
    $cardCount = intval($report['cards'] ?? 0);
    $sheetName = (string)($report['sheet'] ?? 'unknown sheet');
    $front = is_array($report['images']['front'] ?? null) ? $report['images']['front'] : [];
    $valid = intval($front['valid'] ?? 0);
    $sources = intval($front['sources'] ?? 0);
    $invalid = intval($front['invalid'] ?? 0);
    $warnings = is_array($report['warnings'] ?? null) ? $report['warnings'] : [];
    echo "Imported {$cardCount} Hellbreak cards from {$sheetName}.\n";
    echo "Images: {$valid} playable fronts from {$sources} source links; {$invalid} linked fronts are unusable.\n";
    if ($warnings) echo count($warnings) . " warning(s); see HellbreakImportReport.json.\n";
}

if (realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    $options = getopt('', ['source:', 'sheet:', 'root:', 'no-images', 'help']);
    if (isset($options['help'])) {
        echo "Usage: php DevTools/Hellbreak/import-workbook.php --source=cards.xlsx [--sheet=\"01 - Dawn of Terror\"] [--root=HellbreakSim] [--no-images]\n";
        exit(0);
    }

    try {
        $report = importHellbreakWorkbook(
            trim((string)($options['source'] ?? HELLBREAK_SOURCE_URL)),
            trim((string)($options['sheet'] ?? '01 - Dawn of Terror')),
            (string)($options['root'] ?? 'HellbreakSim'),
            !isset($options['no-images'])
        );
        printHellbreakImportSummary($report);
    } catch (Throwable $e) {
        fwrite(STDERR, "Hellbreak import failed: {$e->getMessage()}\n");
        exit(1);
    }
}

function resolveWorkbook(string $source, ?string &$temporaryFile): string
{
    if (is_file($source)) return realpath($source) ?: $source;
    if (!preg_match('#^https?://#i', $source)) {
        throw new RuntimeException("Workbook not found: {$source}");
    }
    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP cURL is unavailable. Download the workbook and pass its local path.');
    }

    $temporaryFile = tempnam(sys_get_temp_dir(), 'hellbreak-');
    if ($temporaryFile === false) throw new RuntimeException('Could not create a temporary workbook file.');
    $downloadUrl = $source . (str_contains($source, '?') ? '&' : '?') . 'download=1';
    $handle = fopen($temporaryFile, 'wb');
    $curl = curl_init($downloadUrl);
    curl_setopt_array($curl, [
        CURLOPT_FILE => $handle,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 8,
        CURLOPT_COOKIEFILE => '',
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/140.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/octet-stream;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
        ],
        CURLOPT_FAILONERROR => false,
    ]);
    $ok = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    fclose($handle);
    $signature = is_file($temporaryFile) ? file_get_contents($temporaryFile, false, null, 0, 4) : '';
    if (!$ok || $status >= 400 || !str_starts_with((string)$signature, 'PK')) {
        throw new RuntimeException("OneDrive did not return an XLSX file (HTTP {$status}{$error}). Download it in the browser and pass --source=path\\to\\file.xlsx.");
    }
    return $temporaryFile;
}

function readWorkbook(string $path, string $preferredSheet): array
{
    if (!class_exists('ZipArchive')) throw new RuntimeException('PHP ZipArchive is required.');
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) throw new RuntimeException('The source is not a readable XLSX workbook.');
    $workbook = xmlFromZip($zip, 'xl/workbook.xml');
    $relationships = relationshipMap(xmlFromZip($zip, 'xl/_rels/workbook.xml.rels'));
    $sheets = [];
    foreach ($workbook->xpath('//*[local-name()="sheet"]') ?: [] as $sheet) {
        $attributes = $sheet->attributes();
        $relation = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $name = (string)$attributes['name'];
        $target = $relationships[(string)$relation['id']] ?? '';
        if ($target !== '') $sheets[$name] = normalizeZipPath('xl/' . $target);
    }
    if (!$sheets) throw new RuntimeException('No worksheets were found.');
    $selectedName = array_key_exists($preferredSheet, $sheets) ? $preferredSheet : array_key_first($sheets);
    $sheetPath = $sheets[$selectedName];
    $sharedStrings = readSharedStrings($zip);
    $sheetXml = xmlFromZip($zip, $sheetPath);
    $rows = [];
    foreach ($sheetXml->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [] as $row) {
        $rowNumber = (int)$row['r'];
        $values = [];
        foreach ($row->xpath('./*[local-name()="c"]') ?: [] as $cell) {
            $reference = (string)$cell['r'];
            preg_match('/^[A-Z]+/', strtoupper($reference), $match);
            $column = columnNumber($match[0] ?? 'A');
            $values[$column] = cellValue($cell, $sharedStrings);
        }
        if ($values) $rows[$rowNumber] = $values;
    }
    $sheetRelationshipsPath = dirname($sheetPath) . '/_rels/' . basename($sheetPath) . '.rels';
    $sheetRelationships = $zip->locateName($sheetRelationshipsPath) !== false
        ? relationshipMap(xmlFromZip($zip, $sheetRelationshipsPath))
        : [];
    foreach ($sheetXml->xpath('//*[local-name()="hyperlinks"]/*[local-name()="hyperlink"]') ?: [] as $hyperlink) {
        $reference = strtoupper((string)$hyperlink['ref']);
        $relation = $hyperlink->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $url = $sheetRelationships[(string)$relation['id']] ?? '';
        if ($url === '' || !preg_match('/^([A-Z]+)(\d+)$/', $reference, $match)) continue;
        $rows[(int)$match[2]][columnNumber($match[1])] = html_entity_decode($url, ENT_QUOTES | ENT_XML1);
    }
    $drawingPath = findDrawingPath($zip, $sheetPath, $sheetXml);
    $zip->close();
    return ['sheetName' => $selectedName, 'sheetPath' => $sheetPath, 'rows' => $rows, 'drawingPath' => $drawingPath];
}

function normalizeRows(array $rows): array
{
    $aliases = headerAliases();
    $bestRow = 0;
    $bestMap = [];
    $bestScore = 0;
    foreach (array_slice($rows, 0, 30, true) as $rowNumber => $values) {
        $map = [];
        foreach ($values as $column => $value) {
            $header = normalizeHeader((string)$value);
            foreach ($aliases as $field => $fieldAliases) {
                if (in_array($header, $fieldAliases, true) && !isset($map[$field])) $map[$field] = $column;
            }
        }
        $score = count($map) + (isset($map['name']) ? 3 : 0) + (isset($map['type']) ? 2 : 0);
        if ($score > $bestScore) { $bestScore = $score; $bestRow = (int)$rowNumber; $bestMap = $map; }
    }
    if ($bestScore < 4 || !isset($bestMap['name'])) {
        throw new RuntimeException('Could not identify a header row with at least a card name and two known fields.');
    }
    if (!isset($bestMap['collectorNumber'])) {
        $candidateCounts = [];
        foreach ($rows as $rowNumber => $values) {
            if ($rowNumber <= $bestRow || $rowNumber > $bestRow + 60) continue;
            foreach ($values as $column => $value) {
                if (preg_match('/^#?\s*\d{1,4}(?:\s*\/\s*\d+)?$/', trim((string)$value))) {
                    $candidateCounts[$column] = ($candidateCounts[$column] ?? 0) + 1;
                }
            }
        }
        if ($candidateCounts) {
            arsort($candidateCounts);
            $bestMap['collectorNumber'] = (int)array_key_first($candidateCounts);
        }
    }

    $cards = [];
    $warnings = [];
    $rowToCard = [];
    $coverageFields = [
        'collectorNumber', 'type', 'rarity', 'name', 'aspect', 'cost', 'loyalty',
        'intellectualProperty', 'imageSource', 'imageBackSource', 'tokenSource',
    ];
    $coverage = array_fill_keys($coverageFields, 0);
    $dataRows = 0;
    $placeholderRows = 0;
    foreach ($rows as $rowNumber => $values) {
        if ($rowNumber <= $bestRow) continue;
        if (!array_filter($values, fn($value) => trim((string)$value) !== '')) continue;
        ++$dataRows;
        $raw = [];
        foreach ($bestMap as $field => $column) $raw[$field] = trim((string)($values[$column] ?? ''));
        $name = trim($raw['name'] ?? '');
        if ($name === '' || normalizeHeader($name) === 'name') {
            ++$placeholderRows;
            continue;
        }
        foreach ($coverageFields as $field) {
            $value = trim((string)($raw[$field] ?? ''));
            if ($value !== '' && $value !== '-') ++$coverage[$field];
        }
        $type = canonicalType($raw['type'] ?? '');
        $collector = trim($raw['collectorNumber'] ?? $raw['id'] ?? '');
        $set = strtoupper(trim($raw['set'] ?? 'DOT'));
        $id = canonicalId($collector, $set, $name);
        $baseId = $id;
        $suffix = 2;
        while (isset($cards[$id]) && strcasecmp((string)$cards[$id]['name'], $name) !== 0) $id = $baseId . '_' . $suffix++;
        if (isset($cards[$id])) {
            $rowToCard[(int)$rowNumber] = $id;
            continue;
        }
        $card = [
            'id' => $id,
            'name' => $name,
            'subtitle' => $raw['subtitle'] ?? '',
            'collectorNumber' => $collector,
            'set' => $set,
            'rarity' => $raw['rarity'] ?? '',
            'type' => $type,
            'cost' => integerValue($raw['cost'] ?? ''),
            'combat' => integerValue($raw['combat'] ?? ''),
            'health' => integerValue($raw['health'] ?? ''),
            'aspect' => normalizeList($raw['aspect'] ?? ''),
            'loyalty' => integerValue($raw['loyalty'] ?? ''),
            'intellectualProperty' => $raw['intellectualProperty'] ?? '',
            'resources' => normalizeList($raw['resources'] ?? ''),
            'scheme' => normalizeList($raw['scheme'] ?? ''),
            'traits' => normalizeList($raw['traits'] ?? ''),
            'text' => $raw['text'] ?? '',
            'unique' => booleanValue($raw['unique'] ?? ''),
            'revealed' => array_key_exists('revealed', $raw) ? booleanValue($raw['revealed']) : true,
            'imageSource' => $raw['imageSource'] ?? '',
            'imageBackSource' => $raw['imageBackSource'] ?? '',
            'tokenSource' => $raw['tokenSource'] ?? '',
        ];
        if ($type === '') $warnings[] = "Row {$rowNumber} ({$name}) has no recognized card type.";
        $cards[$id] = $card;
        $rowToCard[(int)$rowNumber] = $id;
    }
    return [$cards, $warnings, $rowToCard, [
        'sourceRows' => $dataRows,
        'namedCards' => count($cards),
        'placeholderRows' => $placeholderRows,
        'fieldCoverage' => $coverage,
        'typeCounts' => array_count_values(array_column($cards, 'type')),
    ]];
}

function imageInventory(array $cards, string $target): array
{
    $variants = [
        'front' => ['field' => 'imageSource', 'suffix' => ''],
        'back' => ['field' => 'imageBackSource', 'suffix' => '_back'],
        'token' => ['field' => 'tokenSource', 'suffix' => '_token'],
    ];
    $inventory = [];
    $unusableFronts = [];
    foreach ($variants as $name => $definition) {
        $sources = 0;
        $valid = 0;
        $validLinked = 0;
        foreach ($cards as $cardID => $card) {
            $source = trim((string)($card[$definition['field']] ?? ''));
            if ($source !== '') ++$sources;
            $path = $target . DIRECTORY_SEPARATOR . 'concat' . DIRECTORY_SEPARATOR . $cardID . $definition['suffix'] . '.webp';
            $isRejected = ($card['reviewStatus'] ?? '') === 'rejected';
            $isValid = !$isRejected && is_file($path) && filesize($path) >= 8000;
            if ($isValid) ++$valid;
            if ($source !== '' && $isValid) ++$validLinked;
            if ($name === 'front' && $source !== '' && !$isValid) {
                $unusableFronts[] = ['id' => $cardID, 'name' => $card['name'], 'source' => $source];
            }
        }
        $inventory[$name] = [
            'sources' => $sources,
            'valid' => $valid,
            'invalid' => $sources - $validLinked,
            'missingSource' => count($cards) - $sources,
        ];
    }
    $inventory['unusableFronts'] = $unusableFronts;
    return $inventory;
}

function applyReviewedCardFaces(array &$cards, string $repoRoot, array &$warnings): array
{
    $path = $repoRoot . DIRECTORY_SEPARATOR . 'HellbreakSim' . DIRECTORY_SEPARATOR . 'CardData' . DIRECTORY_SEPARATOR . 'ReviewedCardFaces.json';
    if (!is_file($path)) return ['cards' => 0, 'fieldCoverage' => new stdClass()];
    $review = json_decode((string)file_get_contents($path), true);
    if (!is_array($review) || !is_array($review['cards'] ?? null)) {
        throw new RuntimeException('ReviewedCardFaces.json is not valid reviewed card data.');
    }

    $coverage = array_fill_keys(['combat', 'health', 'traits', 'resources', 'scheme', 'text', 'threshold', 'faces'], 0);
    $applied = 0;
    foreach ($review['cards'] as $cardID => $reviewed) {
        if (!isset($cards[$cardID])) {
            $warnings[] = "Reviewed transcription {$cardID} does not match an imported card.";
            continue;
        }
        if (!is_array($reviewed)) continue;
        $base = $reviewed;
        if (isset($reviewed['faces']['lurking']) && is_array($reviewed['faces']['lurking'])) {
            $base = array_replace($reviewed, $reviewed['faces']['lurking']);
            $cards[$cardID]['faces'] = json_encode($reviewed['faces'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            ++$coverage['faces'];
        }
        foreach (['combat', 'health', 'threshold'] as $field) {
            if (!array_key_exists($field, $base)) continue;
            $cards[$cardID][$field] = max(0, intval($base[$field]));
            ++$coverage[$field];
        }
        foreach (['resources', 'scheme'] as $field) {
            if (!array_key_exists($field, $base)) continue;
            $cards[$cardID][$field] = json_encode($base[$field], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            ++$coverage[$field];
        }
        if (isset($base['traits']) && is_array($base['traits'])) {
            $cards[$cardID]['traits'] = implode(', ', array_map('strval', $base['traits']));
            ++$coverage['traits'];
        }
        if (array_key_exists('text', $base)) {
            $cards[$cardID]['text'] = trim((string)$base['text']);
            ++$coverage['text'];
        }
        if (array_key_exists('unique', $reviewed)) $cards[$cardID]['unique'] = (bool)$reviewed['unique'];
        $imagePath = $repoRoot . DIRECTORY_SEPARATOR . 'HellbreakSim' . DIRECTORY_SEPARATOR . 'WebpImages' . DIRECTORY_SEPARATOR . $cardID . '.webp';
        $cards[$cardID]['reviewStatus'] = 'reviewed';
        $cards[$cardID]['transcriptionSource'] = 'HellbreakSim/WebpImages/' . $cardID . '.webp';
        $cards[$cardID]['transcriptionImageSha256'] = is_file($imagePath) ? hash_file('sha256', $imagePath) : '';
        ++$applied;
    }
    return [
        'cards' => $applied,
        'reviewedAt' => (string)($review['reviewedAt'] ?? ''),
        'method' => (string)($review['method'] ?? ''),
        'fieldCoverage' => $coverage,
    ];
}

function applyCardFaceReviewQueue(array &$cards, string $repoRoot, array &$warnings): array
{
    $path = $repoRoot . DIRECTORY_SEPARATOR . 'HellbreakSim' . DIRECTORY_SEPARATOR . 'CardData' . DIRECTORY_SEPARATOR . 'CardFaceReviewQueue.json';
    if (!is_file($path)) return ['cards' => 0, 'confidence' => new stdClass()];
    $queue = json_decode((string)file_get_contents($path), true);
    if (!is_array($queue) || !is_array($queue['cards'] ?? null)) {
        throw new RuntimeException('CardFaceReviewQueue.json is not valid review queue data.');
    }
    $confidence = ['high' => 0, 'medium' => 0, 'low' => 0, 'manual' => 0];
    $statuses = ['needs_review' => 0, 'rejected' => 0, 'stale_source' => 0];
    $applied = 0;
    $stale = 0;
    foreach ($queue['cards'] as $cardID => $candidate) {
        if (!isset($cards[$cardID]) || !is_array($candidate)) continue;
        if (($cards[$cardID]['reviewStatus'] ?? '') === 'reviewed') {
            $warnings[] = "Review queue {$cardID} overlaps a manually reviewed card; the reviewed record won.";
            continue;
        }
        $source = trim((string)($candidate['image'] ?? ''));
        $imagePath = $repoRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $source);
        $currentHash = is_file($imagePath) ? hash_file('sha256', $imagePath) : '';
        $queuedHash = strtolower(trim((string)($candidate['imageSha256'] ?? '')));
        $requestedStatus = strtolower(trim((string)($candidate['status'] ?? 'needs_review')));
        if (!in_array($requestedStatus, ['needs_review', 'rejected'], true)) $requestedStatus = 'needs_review';
        $status = $currentHash !== '' && hash_equals($queuedHash, strtolower($currentHash)) ? $requestedStatus : 'stale_source';
        if ($status === 'stale_source') ++$stale;
        $level = strtolower(trim((string)($candidate['identityConfidence'] ?? 'low')));
        if (!isset($confidence[$level])) $level = 'low';
        ++$confidence[$level];
        $cards[$cardID]['reviewStatus'] = $status;
        $cards[$cardID]['identityConfidence'] = $level;
        $cards[$cardID]['ocrText'] = trim((string)($candidate['ocrText'] ?? ''));
        $cards[$cardID]['reviewReason'] = trim((string)($candidate['reason'] ?? ''));
        $cards[$cardID]['transcriptionSource'] = $source;
        $cards[$cardID]['transcriptionImageSha256'] = $queuedHash;
        ++$applied;
        ++$statuses[$status];
    }
    return [
        'cards' => $applied,
        'staleSources' => $stale,
        'confidence' => $confidence,
        'statuses' => $statuses,
        'method' => (string)($queue['method'] ?? ''),
        'extractedAt' => (string)($queue['extractedAt'] ?? ''),
    ];
}

function importImages(string $xlsxPath, array $import, array $rowToCard, array $cards, string $target, array &$warnings): array
{
    $report = ['embedded' => 0, 'external' => 0, 'failed' => 0];
    if (!class_exists('Imagick')) {
        $warnings[] = 'Imagick is unavailable; card data was imported without images.';
        return $report;
    }
    foreach (['WebpImages', 'concat', 'crops'] as $folder) ensureDirectory($target . DIRECTORY_SEPARATOR . $folder);
    $zip = new ZipArchive();
    if ($zip->open($xlsxPath) !== true) return $report;
    $drawingPath = $import['drawingPath'];
    if ($drawingPath !== '') {
        foreach (drawingImages($zip, $drawingPath) as $image) {
            $sheetRow = $image['row'] + 1;
            $cardID = $rowToCard[$sheetRow] ?? $rowToCard[$sheetRow + 1] ?? null;
            if (!$cardID) continue;
            $blob = $zip->getFromName($image['path']);
            if ($blob === false) continue;
            $variant = $image['variant'] > 0 ? '_back' : '';
            try {
                $allowLandscape = strcasecmp((string)($cards[$cardID]['type'] ?? ''), 'Location') === 0;
                writeCardImages($blob, $target, $cardID . $variant, $allowLandscape);
                ++$report['embedded'];
            } catch (Throwable $e) {
                ++$report['failed'];
                $warnings[] = "Image for {$cardID} failed: {$e->getMessage()}";
            }
        }
    }
    $zip->close();
    foreach ($cards as $cardID => $card) {
        $sources = [
            '' => trim((string)($card['imageSource'] ?? '')),
            '_back' => trim((string)($card['imageBackSource'] ?? '')),
            '_token' => trim((string)($card['tokenSource'] ?? '')),
        ];
        foreach ($sources as $suffix => $url) {
            if ($url === '') continue;
            $existingPath = $target . DIRECTORY_SEPARATOR . 'WebpImages' . DIRECTORY_SEPARATOR . $cardID . $suffix . '.webp';
            if (is_file($existingPath) && filesize($existingPath) >= 8000) {
                ++$report['external'];
                continue;
            }
            try {
                $blob = downloadExternalImage($url);
                $allowLandscape = strcasecmp((string)($card['type'] ?? ''), 'Location') === 0;
                writeCardImages($blob, $target, $cardID . $suffix, $allowLandscape);
                ++$report['external'];
            } catch (Throwable $e) {
                ++$report['failed'];
                $warnings[] = "External image for {$cardID}{$suffix} failed ({$url}): {$e->getMessage()}";
            }
        }
    }
    return $report;
}

function downloadExternalImage(string $url): string
{
    if (!function_exists('curl_init')) throw new RuntimeException('PHP cURL is unavailable.');
    if (preg_match('~^https?://(?:www\.)?imgur\.com/([A-Za-z0-9]+)(?:[/?#].*)?$~i', $url, $match)) {
        $url = 'https://i.imgur.com/' . $match[1] . '.png';
    }
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 6,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 35,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; TCGEngine Hellbreak importer/1.0)',
        CURLOPT_HTTPHEADER => ['Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8'],
    ]);
    $blob = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $contentType = strtolower((string)curl_getinfo($curl, CURLINFO_CONTENT_TYPE));
    $error = curl_error($curl);
    curl_close($curl);
    if (!is_string($blob) || $blob === '' || $status >= 400 || (!str_starts_with($contentType, 'image/') && !str_starts_with($blob, "\x89PNG") && !str_starts_with($blob, "\xFF\xD8"))) {
        throw new RuntimeException("HTTP {$status}" . ($error !== '' ? ": {$error}" : '') . ($contentType !== '' ? "; {$contentType}" : ''));
    }
    if (strlen($blob) < 8000) throw new RuntimeException('Downloaded image is an empty or low-resolution placeholder.');
    return $blob;
}

function drawingImages(ZipArchive $zip, string $drawingPath): array
{
    $xml = xmlFromZip($zip, $drawingPath);
    $directory = dirname($drawingPath);
    $relsPath = $directory . '/_rels/' . basename($drawingPath) . '.rels';
    $relationships = relationshipMap(xmlFromZip($zip, $relsPath));
    $result = [];
    $variants = [];
    foreach (['twoCellAnchor', 'oneCellAnchor'] as $anchorName) {
        foreach ($xml->xpath('/*[local-name()="wsDr"]/*[local-name()="' . $anchorName . '"]') ?: [] as $anchor) {
            $fromRow = $anchor->xpath('./*[local-name()="from"]/*[local-name()="row"]');
            $row = (int)($fromRow[0] ?? 0);
            $blips = $anchor->xpath('.//*[local-name()="blip"]');
            $blip = $blips[0] ?? null;
            if ($blip === null) continue;
            $relation = $blip->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $target = $relationships[(string)$relation['embed']] ?? '';
            if ($target === '') continue;
            $variant = $variants[$row] ?? 0;
            $variants[$row] = $variant + 1;
            $result[] = ['row' => $row, 'path' => normalizeZipPath($directory . '/' . $target), 'variant' => $variant];
        }
    }
    return $result;
}

function writeCardImages(string $blob, string $target, string $id, bool $allowLandscape = false): void
{
    $image = new Imagick();
    $image->readImageBlob($blob);
    $image->setIteratorIndex(0);
    $image->setImageColorspace(Imagick::COLORSPACE_SRGB);
    $width = $image->getImageWidth();
    $height = $image->getImageHeight();
    if ($height < 1 || (!$allowLandscape && ($width / $height) > 0.9)) {
        $image->clear();
        throw new RuntimeException('Downloaded image is a reference diagram or other non-card aspect ratio.');
    }
    $channelStats = $image->getImageChannelMean(Imagick::CHANNEL_ALL);
    $standardDeviation = (float)($channelStats['standardDeviation'] ?? $channelStats['standard_deviation'] ?? 0);
    if ($standardDeviation < 500) {
        $image->clear();
        throw new RuntimeException('Downloaded image is a blank placeholder.');
    }

    $full = clone $image;
    $full->thumbnailImage(900, 1256, true, !$allowLandscape);
    $full->setImageFormat('webp');
    $full->setImageCompressionQuality(88);
    $full->writeImage($target . DIRECTORY_SEPARATOR . 'WebpImages' . DIRECTORY_SEPARATOR . $id . '.webp');

    $crop = clone $image;
    $crop->cropThumbnailImage(450, 450);
    $crop->setImageFormat('webp');
    $crop->setImageCompressionQuality(86);
    $crop->writeImage($target . DIRECTORY_SEPARATOR . 'concat' . DIRECTORY_SEPARATOR . $id . '.webp');
    $crop->setImageFormat('png');
    $crop->writeImage($target . DIRECTORY_SEPARATOR . 'crops' . DIRECTORY_SEPARATOR . $id . '_cropped.png');
    $image->clear(); $full->clear(); $crop->clear();
}

function findDrawingPath(ZipArchive $zip, string $sheetPath, SimpleXMLElement $sheetXml): string
{
    $drawings = $sheetXml->xpath('//*[local-name()="drawing"]');
    if (!$drawings) return '';
    $relation = $drawings[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
    $id = (string)$relation['id'];
    $relsPath = dirname($sheetPath) . '/_rels/' . basename($sheetPath) . '.rels';
    if ($zip->locateName($relsPath) === false) return '';
    $relationships = relationshipMap(xmlFromZip($zip, $relsPath));
    return isset($relationships[$id]) ? normalizeZipPath(dirname($sheetPath) . '/' . $relationships[$id]) : '';
}

function readSharedStrings(ZipArchive $zip): array
{
    if ($zip->locateName('xl/sharedStrings.xml') === false) return [];
    $xml = xmlFromZip($zip, 'xl/sharedStrings.xml');
    $strings = [];
    foreach ($xml->xpath('/*[local-name()="sst"]/*[local-name()="si"]') ?: [] as $item) {
        $text = '';
        foreach ($item->xpath('.//*[local-name()="t"]') ?: [] as $part) $text .= (string)$part;
        $strings[] = $text;
    }
    return $strings;
}

function cellValue(SimpleXMLElement $cell, array $sharedStrings): string
{
    $type = (string)$cell['t'];
    if ($type === 'inlineStr') {
        $parts = $cell->xpath('.//*[local-name()="t"]') ?: [];
        return implode('', array_map(fn($part) => (string)$part, $parts));
    }
    $value = (string)$cell->v;
    if ($type === 's') return (string)($sharedStrings[(int)$value] ?? '');
    if ($type === 'b') return $value === '1' ? 'true' : 'false';
    return $value;
}

function headerAliases(): array
{
    return [
        'id' => ['id', 'card id', 'cardid'],
        'name' => ['name', 'card name', 'cardname', 'title'],
        'subtitle' => ['subtitle', 'sub title'],
        'collectorNumber' => ['collector number', 'collector #', 'collector no', 'card number', 'card #', 'number', 'set number', 'collection data'],
        'set' => ['set', 'set code', 'expansion'],
        'rarity' => ['rarity'],
        'type' => ['type', 'card type'],
        'cost' => ['cost', 'blood cost', 'blood'],
        'combat' => ['combat', 'power', 'attack', 'combat value'],
        'health' => ['health', 'hp'],
        'aspect' => ['aspect', 'aspects'],
        'loyalty' => ['loyalty', 'loyalty required', 'required loyalty'],
        'intellectualProperty' => ['ip', 'intellectual property', 'franchise', 'license'],
        'resources' => ['resource', 'resources', 'resource bar'],
        'scheme' => ['scheme', 'scheme bar'],
        'traits' => ['trait', 'traits', 'subtype', 'subtypes'],
        'text' => ['text', 'rules text', 'card text', 'ability', 'abilities', 'ability text', 'effect'],
        'unique' => ['unique', 'is unique'],
        'revealed' => ['revealed', 'published', 'public'],
        'imageSource' => ['image', 'image url', 'image source', 'card image', 'front', 'front image'],
        'imageBackSource' => ['back', 'back image', 'reverse', 'reverse image'],
        'tokenSource' => ['token', 'tokens', 'token image', 'token images'],
    ];
}

function canonicalType(string $type): string
{
    $value = strtolower(trim($type));
    foreach (['monster', 'minion', 'asset', 'event', 'location'] as $known) {
        if (str_contains($value, $known)) return ucfirst($known);
    }
    return $type === '' ? '' : ucwords(strtolower($type));
}

function canonicalId(string $collector, string $set, string $name): string
{
    $upper = strtoupper(trim($collector));
    if (preg_match('/([A-Z]{1,8})[^A-Z0-9]*0*([0-9]{1,4})/', $upper, $match)) {
        return $match[1] . '_' . str_pad($match[2], 3, '0', STR_PAD_LEFT);
    }
    if (preg_match('/0*([0-9]{1,4})/', $upper, $match)) {
        return (preg_replace('/[^A-Z0-9]/', '', $set) ?: 'DOT') . '_' . str_pad($match[1], 3, '0', STR_PAD_LEFT);
    }
    $slug = strtoupper(trim(preg_replace('/[^A-Za-z0-9]+/', '_', $name), '_'));
    return (preg_replace('/[^A-Z0-9]/', '', $set) ?: 'DOT') . '_' . ($slug ?: substr(sha1($name), 0, 10));
}

function integerValue(string $value): int
{
    return preg_match('/-?\d+/', $value, $match) ? (int)$match[0] : 0;
}

function booleanValue(string $value): bool
{
    return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'y', 'x', 'unique', 'revealed'], true);
}

function normalizeList(string $value): string
{
    return trim((string)preg_replace('/\s*[,;|\/]\s*/', ', ', $value));
}

function normalizeHeader(string $value): string
{
    $value = strtolower(trim($value));
    return trim((string)preg_replace('/[^a-z0-9#]+/', ' ', $value));
}

function relationshipMap(SimpleXMLElement $xml): array
{
    $map = [];
    foreach ($xml->xpath('/*[local-name()="Relationships"]/*[local-name()="Relationship"]') ?: [] as $relationship) {
        $map[(string)$relationship['Id']] = (string)$relationship['Target'];
    }
    return $map;
}

function xmlFromZip(ZipArchive $zip, string $path): SimpleXMLElement
{
    $contents = $zip->getFromName($path);
    if ($contents === false) throw new RuntimeException("Missing XLSX part: {$path}");
    $xml = simplexml_load_string($contents);
    if ($xml === false) throw new RuntimeException("Invalid XML in XLSX part: {$path}");
    return $xml;
}

function normalizeZipPath(string $path): string
{
    $parts = [];
    foreach (explode('/', str_replace('\\', '/', $path)) as $part) {
        if ($part === '' || $part === '.') continue;
        if ($part === '..') array_pop($parts); else $parts[] = $part;
    }
    return implode('/', $parts);
}

function columnNumber(string $letters): int
{
    $number = 0;
    foreach (str_split($letters) as $letter) $number = $number * 26 + ord($letter) - 64;
    return $number;
}

function ensureDirectory(string $directory): void
{
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException("Could not create directory: {$directory}");
    }
}

function writeJson(string $path, mixed $value): void
{
    $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false || file_put_contents($path, $json . PHP_EOL) === false) {
        throw new RuntimeException("Could not write {$path}");
    }
}
