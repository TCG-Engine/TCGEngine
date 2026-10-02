<?php
/** TCGdex adapter: full card records, cached source snapshots and local artwork. */
function PokeImportWriteJson(string $path, $value): void {
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
    $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if (file_put_contents($path . '.tmp', $json) === false || !rename($path . '.tmp', $path)) {
        throw new RuntimeException("Cannot write $path");
    }
}

function PokeTCGdexRequest(string $url, ?array $body = null, bool $allowPartial = false): array {
    if (!preg_match('#^https://(api|assets)\.tcgdex\.net/#', $url)) throw new InvalidArgumentException('Unexpected TCGdex URL');
    for ($attempt = 0; $attempt < 4; ++$attempt) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 90, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'User-Agent: TCGEngine-PokeSim/1.0'],
            CURLOPT_SSL_VERIFYPEER => true]);
        if ($body !== null) { curl_setopt($curl, CURLOPT_POST, true); curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR)); }
        $raw = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if ($status === 200 && is_string($raw)) {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (isset($data['errors']) && !$allowPartial) throw new RuntimeException('TCGdex GraphQL: ' . json_encode($data['errors'][0]));
            return $data;
        }
        if ($status === 404) throw new RuntimeException("TCGdex record not found: $url");
        if ($attempt < 3) sleep(1 << $attempt);
    }
    throw new RuntimeException("TCGdex request failed ($status): $error; $url");
}

function PokeTCGdexNormalize(array $card): array {
    foreach (['id', 'name', 'category', 'set', 'localId'] as $field) {
        if (empty($card[$field])) throw new RuntimeException('Incomplete TCGdex card: ' . ($card['id'] ?? '?') . " ($field)");
    }
    if (!preg_match('/^[A-Za-z0-9.!%_-]+$/D', $card['id'])) throw new RuntimeException('Unsafe card identifier');
    $card['type'] = $card['category'];
    $card['releaseDate'] = $card['set']['releaseDate'] ?? '';
    $card['set'] = $card['set']['id'];
    $card['types'] = implode(',', $card['types'] ?? []);
    // Reviewed against the TCGdex card face: POR 088 is Special Energy, despite
    // the current upstream energyType=Normal. Preserve the raw source separately.
    if ($card['id'] === 'me03-088') $card['energyType'] = 'Special';
    if (str_ends_with($card['name'], ' ex') && empty($card['suffix'])) $card['suffix'] = 'ex';
    if ($card['category'] === 'Energy' && $card['types'] === '' && str_contains($card['name'], 'Psychic')) $card['types'] = 'Psychic';
    return $card;
}

function PokeTCGdexImport(bool $refresh = false, ?array $ids = null, bool $images = true): int {
    $root = dirname(__DIR__);
    $cachePath = "$root/GeneratedCode/cardArrayCache.json";
    $old = is_file($cachePath) ? json_decode(file_get_contents($cachePath), true, 512, JSON_THROW_ON_ERROR) : [];
    $cards = [];
    foreach ($old['cardArray'] ?? [] as $card) {
        if (str_ends_with($card['name'], ' ex') && empty($card['suffix'])) $card['suffix']='ex';
        $cards[$card['id']] = $card;
    }
    if ($ids !== null) {
        foreach (array_unique($ids) as $id) {
            if (!preg_match('/^[A-Za-z0-9.!%_-]+$/D', $id)) throw new InvalidArgumentException('Unsafe card identifier');
            if (!isset($cards[$id]) || $refresh || is_file("$root/GeneratedCode/tcgdex/cards/$id.json")) {
                $rawPath = "$root/GeneratedCode/tcgdex/cards/$id.json";
                $card = !$refresh && is_file($rawPath) ? json_decode(file_get_contents($rawPath), true, 512, JSON_THROW_ON_ERROR)
                    : PokeTCGdexRequest('https://api.tcgdex.net/v2/en/cards/' . rawurlencode($id));
                $setPath = "$root/GeneratedCode/tcgdex/sets/" . $card['set']['id'] . '.json';
                if (!is_file($setPath) || $refresh) PokeImportWriteJson($setPath, PokeTCGdexRequest('https://api.tcgdex.net/v2/en/sets/' . $card['set']['id']));
                $set = json_decode(file_get_contents($setPath), true, 512, JSON_THROW_ON_ERROR);
                $card['set']['releaseDate'] = $set['releaseDate'] ?? '';
                $cards[$id] = PokeTCGdexNormalize($card);
                PokeImportWriteJson("$root/GeneratedCode/tcgdex/cards/$id.json", $card);
                echo "Imported $id: {$card['name']}\n";
            }
        }
    } else {
        // GraphQL returns full records, unlike REST /cards which only returns briefs.
        $selection = 'id localId name category hp types stage evolveFrom retreat regulationMark suffix rarity image effect trainerType energyType attacks { name cost damage effect } abilities { name type effect } weaknesses { type value } resistances { type value } legal { standard expanded } set { id name releaseDate tcgOnline }';
        $seen = []; $cards = [];
        // The current upstream resolver treats pagination/sort objects as filters
        // and fails with value.indexOf. An argument-free query returns the catalog.
        {
            $pagePath = "$root/GeneratedCode/tcgdex/catalog.json";
            if ($refresh || !is_file($pagePath)) {
                $query = '{ cards @locale(lang: "en") { ' . $selection . ' } }';
                $data = PokeTCGdexRequest('https://api.tcgdex.net/v2/graphql', ['query' => $query], true);
                if (!is_array($data['data']['cards'] ?? null)) throw new RuntimeException('TCGdex returned no card array');
                if (!empty($data['errors'])) {
                    PokeImportWriteJson("$root/GeneratedCode/tcgdex/graphql-errors.json", $data['errors']);
                    $briefs = PokeTCGdexRequest('https://api.tcgdex.net/v2/graphql', ['query'=>'{ cards @locale(lang: "en") { id } }'])['data']['cards'];
                    $indices = [];
                    foreach ($data['errors'] as $error) {
                        if (($error['path'][0] ?? '') !== 'cards' || !isset($error['path'][1])) throw new RuntimeException('Unexpected GraphQL catalog error');
                        $indices[(int)$error['path'][1]] = true;
                    }
                    foreach (array_keys($indices) as $index) {
                        $id = $briefs[$index]['id'];
                        if (isset($data['data']['cards'][$index]['id']) && $data['data']['cards'][$index]['id'] !== $id) throw new RuntimeException('Catalog order changed; retry');
                        $data['data']['cards'][$index] = PokeTCGdexRequest('https://api.tcgdex.net/v2/en/cards/' . rawurlencode($id));
                        echo "Repaired GraphQL record via REST: $id\n";
                    }
                }
                PokeImportWriteJson($pagePath, $data['data']['cards']);
            }
            $rows = json_decode(file_get_contents($pagePath), true, 512, JSON_THROW_ON_ERROR);
            if (!$rows) throw new RuntimeException('Empty TCGdex import; existing catalog preserved');
            foreach ($rows as $card) {
                if (str_contains($card['image'] ?? '', '/tcgp/') || preg_match('/^(?:[A-Z]\d|P-[A-Z])/', $card['set']['id'])) continue;
                if (isset($seen[$card['id']])) throw new RuntimeException('Repeated card across TCGdex pages; retry with refresh=1');
                $seen[$card['id']] = true;
                $cards[$card['id']] = PokeTCGdexNormalize($card);
            }
            echo "TCGdex source catalog: " . count($rows) . " records\n";
        }
        if (!$seen) throw new RuntimeException('Empty TCGdex import; existing catalog preserved');
    }
    ksort($cards);
    // Replace the usable snapshot only after every requested record succeeds.
    PokeImportWriteJson($cachePath, ['source' => 'TCGdex', 'fetchedAt' => gmdate('c'), 'language' => 'en',
        'cardArray' => array_values($cards), 'reprintMap' => [], 'leaderUnitByUUIDMap' => []]);
    if ($images) {
        $manifest = [];
        foreach ($cards as $id => $card) {
            if ($ids !== null && !in_array($id, $ids, true)) continue;
            $sourceId = $id; $image = $card['image'] ?? '';
            // MEE 005 has no published art. Keep its printing/data identity and
            // explicitly record the equivalent basic Energy art used for display.
            if ($image === '' && $id === 'mee-005') {
                $sourceId = 'swsh12.5-156';
                $fallback = $cards[$sourceId] ?? PokeTCGdexRequest('https://api.tcgdex.net/v2/en/cards/' . $sourceId);
                if (($fallback['name'] ?? '') !== $card['name']) throw new RuntimeException('Energy image fallback name mismatch');
                $image = $fallback['image'] ?? '';
            }
            $manifest[$id] = ['sourceCard'=>$sourceId,'url'=>$image ? $image.'/high.webp' : null,'status'=>$image ? 'available' : 'missing'];
            if ($image !== '') {
                try { PokeTCGdexDownloadImage($id, $image, $refresh); }
                catch (RuntimeException $error) {
                    $manifest[$id]['status']='failed'; $manifest[$id]['error']=$error->getMessage();
                    error_log($error->getMessage());
                }
            }
        }
        $manifestPath = "$root/GeneratedCode/imageManifest.json";
        $previous = is_file($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : [];
        PokeImportWriteJson($manifestPath, array_replace($previous, $manifest));
        $unavailable = count(array_filter($manifest, fn($entry)=>$entry['status'] !== 'available'));
        if ($unavailable) echo "Artwork unavailable for $unavailable cards; see GeneratedCode/imageManifest.json\n";
    }
    return count($cards);
}

function PokeTCGdexDownloadImage(string $id, string $baseUrl, bool $refresh = false): void {
    if (!preg_match('/^[A-Za-z0-9.!%_-]+$/D', $id) || !preg_match('#^https://assets\.tcgdex\.net/[A-Za-z0-9/._!%?-]+$#D', $baseUrl)) throw new RuntimeException('Unsafe image source');
    $dir = dirname(__DIR__) . '/WebpImages';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $path = "$dir/$id.webp";
    if (!$refresh && is_file($path) && getimagesize($path)) return;
    $curl = curl_init($baseUrl . '/high.webp');
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 20, CURLOPT_TIMEOUT => 60]);
    $bytes = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($status !== 200 || !is_string($bytes) || !getimagesizefromstring($bytes)) throw new RuntimeException("Invalid/missing TCGdex artwork for $id");
    if (file_put_contents($path . '.tmp', $bytes) === false || !rename($path . '.tmp', $path)) throw new RuntimeException("Cannot save artwork for $id");
}
