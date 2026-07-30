<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_friendly_deck_link.php
// SWUSim deck import must accept the friendly/short deck link SWUDeck now hands out
// (https://swustats.net/deck/<12-letter code>, plus the ?gameName=<code> variant) as well
// as the legacy numeric ?gameName=<id> link. LoadDeck.php resolves a 12-letter code itself,
// so extraction just needs to pass the code through.
header('Content-Type: text/plain');
include_once __DIR__ . '/../../SWUSim/Custom/DeckImport.php';

$cases = [
    // [input, expected extracted deck identifier]
    ['https://swustats.net/TCGEngine/NextTurn.php?gameName=12345&folderPath=SWUDeck', '12345'], // legacy numeric
    ['https://swustats.net/deck/ABCDEFGHIJKL',                                       'ABCDEFGHIJKL'], // Copy Link
    ['https://swustats.net/deck/ABCDEFGHIJKL?gameName=ABCDEFGHIJKL',                 'ABCDEFGHIJKL'], // Karabast link
    ['http://localhost:3100/deck/ABCDEFGHIJKL',                                      'ABCDEFGHIJKL'], // dev host
    ['swustats.net/deck/abcdefghijkl',                                               'abcdefghijkl'], // no scheme
    ['https://swustats.net/deck/TOOSHORT',                                           ''],             // not a code
    ['https://swustats.net/TCGEngine/NextTurn.php?folderPath=SWUDeck',               ''],             // nothing to extract
];

$fails = [];
foreach ($cases as [$input, $expected]) {
    $got = SWUExtractSWUStatsDeckId($input);
    if ($got !== $expected) $fails[] = "$input => '" . $got . "' (expected '$expected')";
}

// A bare 12-letter code (no URL) is a valid short-code paste and must route to the
// SWUStats importer rather than falling through to "Unsupported deck format".
$bare = SWUResolveDeckInput('ZZZZZZZZZZZZ');
if (($bare['success'] ?? true) !== false
    || strpos((string)($bare['message'] ?? ''), 'Unsupported deck format') !== false) {
    $fails[] = "bare code was not routed to the SWUStats importer: " . json_encode($bare);
}

echo empty($fails) ? "PASS\n" : "FAIL\n  " . implode("\n  ", $fails) . "\n";
