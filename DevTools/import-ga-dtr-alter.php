<?php
// Import the reviewed DTR Alter metadata snapshot, then run zzCardCodeGenerator.php.
// This updates the generator's input cache, never its generated dictionaries.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('memory_limit', '512M');
$root = dirname(__DIR__);
$source = json_decode(file_get_contents(__DIR__ . '/ga-fixtures/dtr-alter/catalog.json'), true, 512, JSON_THROW_ON_ERROR);
$path = $root . '/GrandArchiveSim/GeneratedCode/cardArrayCache.json';
$cache = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
if (count($source['cards']) !== 40 || empty($cache['cardArray'])) throw new RuntimeException('Invalid catalog');
$indices = [];
foreach ($cache['cardArray'] as $index => $card) $indices[$card['uuid']] = $index;
$added = 0;
foreach ($source['cards'] as $card) {
    // The API-loading phase normally supplies this normalized identifier.
    $card['id'] = $card['uuid'];
    if (isset($indices[$card['uuid']])) {
        $index = $indices[$card['uuid']];
        $cache['cardArray'][$index]['id'] ??= $card['uuid'];
        continue;
    }
    $cache['cardArray'][] = $card;
    ++$added;
}
$temp = $path . '.dtr-alter.tmp';
file_put_contents($temp, json_encode($cache, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
rename($temp, $path);
echo "Imported $added DTR Alter cards; existing card metadata preserved.\n";
