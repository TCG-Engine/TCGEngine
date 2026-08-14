<?php

// Rebuilds an app's derived card-art files from the full-size art in WebpImages/.
//
// concat/<id>.webp and crops/<id>_cropped.png are both 450x450 renders of the same source, so they
// are regenerated after a card-data art import rather than shipped in the archive — that keeps a
// Hellbreak art bundle at ~15MB instead of ~54MB. The geometry and quality settings mirror the
// workbook importer's renderCardImages() so imported art matches art produced there.
//
// Imagick only: prod's GD is compiled WITHOUT WebP support, so imagecreatefromwebp()/imagewebp()
// are undefined there and calling them is a fatal.

// Returns true when both derivatives were written. $baseName is an art file name ("DOT_001.webp").
function RegenerateCardArtDerivatives(string $appDirectory, string $baseName): bool
{
    if (!class_exists('Imagick')) return false;

    $baseName = basename($baseName);
    if (!preg_match('/^([A-Za-z0-9_-]+)\.webp$/', $baseName, $match)) return false;
    $cardStem = $match[1];

    $sourcePath = $appDirectory . '/' . 'WebpImages' . '/' . $baseName;
    if (!is_file($sourcePath)) return false;

    foreach (['concat', 'crops'] as $folder) {
        $target = $appDirectory . '/' . $folder;
        if (!is_dir($target) && !mkdir($target, 0777, true) && !is_dir($target)) return false;
    }

    $crop = null;
    try {
        $crop = new Imagick($sourcePath);
        $crop->cropThumbnailImage(450, 450);

        $crop->setImageFormat('webp');
        $crop->setImageCompressionQuality(86);
        $crop->writeImage($appDirectory . '/concat/' . $cardStem . '.webp');

        $crop->setImageFormat('png');
        $crop->writeImage($appDirectory . '/crops/' . $cardStem . '_cropped.png');
        return true;
    } catch (Throwable $error) {
        error_log('RegenerateCardArtDerivatives failed for ' . $baseName . ': ' . $error->getMessage());
        return false;
    } finally {
        if ($crop instanceof Imagick) $crop->clear();
    }
}
