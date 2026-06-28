<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_validatedeck_format.php
// Exercises the ValidateDeck.php endpoint's `format` param via an in-container HTTP hop.
header('Content-Type: text/plain');

function _post($format, $deck) {
    $ch = curl_init('http://localhost/TCGEngine/SWUSim/ValidateDeck.php');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['deckLink' => $deck, 'format' => $format]),
        CURLOPT_TIMEOUT => 20,
    ]);
    $out = curl_exec($ch);
    curl_close($ch);
    return json_decode($out, true);
}

// A deck with a card legal only in an older (SOR) set: illegal in Premier, legal in Open.
$deck = "Leader\nJTL_001\nBase\nJTL_023\nDeck\n3 SOR_100\n" . str_repeat("3 JTL_100\n", 16);

$premier = _post('premier', $deck);
$open    = _post('open', $deck);

$checks = [];
$checks['premier echoes format'] = ($premier['format'] ?? null) === 'premier';
$checks['open echoes format']    = ($open['format'] ?? null) === 'open';
$checks['unknown falls back']    = (_post('zzz', $deck)['format'] ?? null) === 'premier';
$premierBlob = json_encode($premier['formatErrors'] ?? []);
$openBlob    = json_encode($open['formatErrors'] ?? []);
$checks['premier flags SOR'] = strpos($premierBlob, 'SOR_100') !== false || strpos($premierBlob, 'not legal') !== false;
$checks['open allows SOR']   = strpos($openBlob, 'SOR_100') === false;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . " premier=" . json_encode($premier) . " open=" . json_encode($open) . "\n";
