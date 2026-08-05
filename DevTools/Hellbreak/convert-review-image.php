<?php

declare(strict_types=1);

if ($argc < 3) {
    fwrite(STDERR, "Usage: php convert-review-image.php input.webp output.png\n");
    exit(1);
}
$input = realpath($argv[1]);
if ($input === false || !is_file($input)) throw new RuntimeException('Input image not found.');
$output = $argv[2];
$image = new Imagick($input);
$image->setIteratorIndex(0);
$image->setImageFormat('png');
if (!$image->writeImage($output)) throw new RuntimeException('Could not write PNG.');
$image->clear();
