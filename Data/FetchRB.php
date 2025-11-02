<?php
/**
 * fetch_rb_cards.php
 *
 * Fetches Riftbound card data from dotgg indexed API and converts it to
 * an object where `data` is an array of objects with named properties.
 *
 * Saves output to RB.json in the same directory.
 *
 * Usage (from project root):
 * php Data/fetch_rb_cards.php
 */

$apiUrl = 'https://api.dotgg.gg/cgfw/getcards?game=riftbound&mode=indexed';

function get_url_contents($url)
{
    // Use curl if available for better control
    if (function_exists('curl_version')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'TCGEngine-fetcher/1.0');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $data = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($data === false) {
            throw new RuntimeException("cURL error: $err");
        }
        if ($code < 200 || $code >= 300) {
            throw new RuntimeException("HTTP error code $code when fetching $url");
        }
        return $data;
    }

    // Fallback to file_get_contents
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: TCGEngine-fetcher/1.0\r\n",
            'timeout' => 30,
        ],
    ];
    $context = stream_context_create($opts);
    $data = @file_get_contents($url, false, $context);
    if ($data === false) {
        $error = error_get_last();
        throw new RuntimeException('file_get_contents failed: ' . ($error['message'] ?? 'unknown'));
    }
    return $data;
}

try {
    echo "Fetching $apiUrl...\n";
    $raw = get_url_contents($apiUrl);
    $json = json_decode($raw, true);
    if ($json === null) {
        throw new RuntimeException('Failed to decode JSON: ' . json_last_error_msg());
    }

    if (!isset($json['names']) || !isset($json['data']) || !is_array($json['names']) || !is_array($json['data'])) {
        throw new RuntimeException('Unexpected JSON structure from API');
    }

    $names = $json['names'];
    $output = [
        'names' => $names,
        'data' => [],
    ];

    foreach ($json['data'] as $row) {
        // Some rows may be shorter/longer than names. Map available values.
        $obj = new stdClass();
        for ($i = 0; $i < count($names); $i++) {
            $key = $names[$i];
            $val = array_key_exists($i, $row) ? $row[$i] : null;
            // Convert empty strings that represent numbers to actual numbers where appropriate? Keep as-is.
            $obj->{$key} = $val;
        }
        $output['data'][] = $obj;
    }

    $outPath = __DIR__ . DIRECTORY_SEPARATOR . 'RB.json';
    $encoded = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        throw new RuntimeException('Failed to encode output JSON: ' . json_last_error_msg());
    }

    $written = file_put_contents($outPath, $encoded);
    if ($written === false) {
        throw new RuntimeException("Failed to write file $outPath");
    }

    echo "Wrote $outPath (" . strlen($encoded) . " bytes)\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}

return 0;
