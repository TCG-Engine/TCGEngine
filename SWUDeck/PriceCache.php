<?php
// Simple server-side cache for tcgcsv price data (productId -> midPrice)
// Cache refreshed once per day. Returns application/json.

$cacheDir = __DIR__ . DIRECTORY_SEPARATOR . 'cache';
$cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'price_cache.json';
$cacheTtl = 60 * 60 * 24; // 24 hours

if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}

$cacheValid = false;
if (file_exists($cacheFile)) {
    $stat = stat($cacheFile);
    if ($stat && (time() - $stat['mtime']) < $cacheTtl) $cacheValid = true;
}

if ($cacheValid) {
    header('Content-Type: application/json');
    readfile($cacheFile);
    exit(0);
}

// Not valid - fetch fresh data from tcgcsv
// Note: adjust $priceCategory if you need to change game id; use the same value as in Initialize.php
$priceCategory = isset($GLOBALS['priceCategory']) ? intval($GLOBALS['priceCategory']) : 79;

$baseUrl = 'https://tcgcsv.com/tcgplayer/' . $priceCategory;

function safe_fetch_json($url) {
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: TCGEngine/1.0\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $txt = @file_get_contents($url, false, $context);
    if ($txt === false) return null;
    $j = json_decode($txt, true);
    if (json_last_error() !== JSON_ERROR_NONE) return null;
    return $j;
}

$groups = safe_fetch_json($baseUrl . '/groups');
$results = [];
$priceDetails = [];
$products = [];
if ($groups && isset($groups['results']) && is_array($groups['results'])) {
    foreach ($groups['results'] as $g) {
        if (!isset($g['groupId'])) continue;
        // Fetch product metadata for this group
        $prods = safe_fetch_json($baseUrl . '/' . $g['groupId'] . '/products');
        if ($prods && isset($prods['results']) && is_array($prods['results'])) {
            foreach ($prods['results'] as $prod) {
                if (isset($prod['productId'])) {
                    // Keep raw product metadata; we can later pick which fields to use for matching
                    $products[strval($prod['productId'])] = $prod;
                }
            }
        }

        // Fetch prices for this group
        $prices = safe_fetch_json($baseUrl . '/' . $g['groupId'] . '/prices');
        if ($prices && isset($prices['results']) && is_array($prices['results'])) {
            foreach ($prices['results'] as $p) {
                if (isset($p['productId']) && isset($p['midPrice'])) {
                    $pid = strval($p['productId']);
                    $results[$pid] = $p['midPrice'];
                    // capture lowPrice if present
                    $priceDetails[$pid] = [
                        'midPrice' => $p['midPrice'],
                        'lowPrice' => isset($p['lowPrice']) ? $p['lowPrice'] : null
                    ];
                }
            }
        }
    }
}

// Write cache atomically
// Build merged items map: productId -> merged metadata + midPrice
$items = [];
foreach ($products as $pid => $pdata) {
    $items[$pid] = $pdata;
    if (isset($priceDetails[$pid])) {
        $items[$pid]['midPrice'] = $priceDetails[$pid]['midPrice'];
        $items[$pid]['lowPrice'] = $priceDetails[$pid]['lowPrice'];
    } else {
        $items[$pid]['midPrice'] = isset($results[$pid]) ? $results[$pid] : null;
        $items[$pid]['lowPrice'] = null;
    }
}
// Include any price-only entries that weren't in products
foreach ($results as $pid => $price) {
    if (!isset($items[$pid])) {
        $items[$pid] = ['productId' => $pid, 'midPrice' => $price, 'lowPrice' => (isset($priceDetails[$pid]) ? $priceDetails[$pid]['lowPrice'] : null)];
    }
}

$tmp = $cacheFile . '.tmp';
@file_put_contents($tmp, json_encode(['generated' => time(), 'items' => $items]));
@rename($tmp, $cacheFile);

header('Content-Type: application/json');
echo json_encode(['generated' => time(), 'items' => $items]);
exit(0);
