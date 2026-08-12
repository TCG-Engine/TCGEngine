<?php

declare(strict_types=1);

if ($argc < 3) {
    fwrite(STDERR, "Usage: php GenerateImage.php <card-id> <image-url> [type]\n");
    exit(2);
}

$tcgRoot = dirname(__DIR__, 2);
require_once $tcgRoot . '/zzImageConverter.php';

CheckImage(
    $argv[1],
    $argv[2],
    $argv[3] ?? 'Token',
    rootPath: $tcgRoot . '/FaBSim'
);
