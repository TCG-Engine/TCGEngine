<?php

declare(strict_types=1);

$tcgRoot = dirname(__DIR__, 2);
$talisharImageRoot = $argv[1] ?? 'C:/xampp/htdocs/Card-Images/media/uploaded/public';
$squareSource = $talisharImageRoot . '/cardsquares/english';
$fullSource = $talisharImageRoot . '/cardimages/english';
$squareDestination = $tcgRoot . '/FaBSim/concat';
$fullDestination = $tcgRoot . '/FaBSim/WebpImages';

$cache = json_decode(
    file_get_contents($tcgRoot . '/FaBSim/GeneratedCode/cardArrayCache.json'),
    true,
    512,
    JSON_THROW_ON_ERROR
);

$result = [
    'squaresCopied' => 0,
    'squareSourcesMissing' => 0,
    'fullImagesCopied' => 0,
    'fullSourcesMissing' => 0,
];

foreach ($cache['cardArray'] as $card) {
    $cardID = $card['id'];
    $printingIDs = array_values(array_unique(array_filter(array_merge(
        [$card['printing_id'] ?? null],
        array_column($card['printings'] ?? [], 'id')
    ))));
    $sourceSquare = null;
    $sourceFull = null;

    foreach ($printingIDs as $printingID) {
        $candidateSquare = $squareSource . '/' . $printingID . '.webp';
        $candidateFull = $fullSource . '/' . $printingID . '.webp';
        if ($sourceSquare === null && is_file($candidateSquare)) $sourceSquare = $candidateSquare;
        if ($sourceFull === null && is_file($candidateFull)) $sourceFull = $candidateFull;
        if ($sourceSquare !== null && $sourceFull !== null) break;
    }

    if ($sourceSquare !== null) {
        copy($sourceSquare, $squareDestination . '/' . $cardID . '.webp');
        ++$result['squaresCopied'];
    } else {
        ++$result['squareSourcesMissing'];
    }

    if ($sourceFull !== null) {
        copy($sourceFull, $fullDestination . '/' . $cardID . '.webp');
        ++$result['fullImagesCopied'];
    } else {
        ++$result['fullSourcesMissing'];
    }
}

foreach ([
    [$squareSource . '/CardBack.webp', $squareDestination . '/CardBack.webp'],
    [$fullSource . '/CardBack.webp', $fullDestination . '/CardBack.webp'],
] as [$source, $destination]) {
    if (is_file($source)) {
        copy($source, $destination);
    }
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
