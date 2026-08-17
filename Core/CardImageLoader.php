<?php
// Decode card art into a GD image resource across development and production PHP builds.
// Production LAMPP may lack GD WebP support, so WebP decoding falls back to Imagick and dwebp.

if (!function_exists('LoadCardImageAsGd')) {
  /**
   * @param string $path Absolute path to a card image.
   * @return \GdImage|resource|false
   */
  function LoadCardImageAsGd($path) {
    if (!is_string($path) || $path === '' || !is_file($path)) return false;

    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($extension !== 'webp') {
      $bytes = @file_get_contents($path);
      return $bytes === false ? false : @imagecreatefromstring($bytes);
    }

    if (function_exists('imagecreatefromwebp')) {
      $image = @imagecreatefromwebp($path);
      if ($image !== false) return $image;
    }

    if (extension_loaded('imagick')) {
      try {
        $imagick = new \Imagick();
        $imagick->readImage($path);
        $imagick->setImageFormat('png');
        $blob = $imagick->getImageBlob();
        $imagick->clear();
        $imagick->destroy();
        if ($blob !== '') {
          $image = @imagecreatefromstring($blob);
          if ($image !== false) return $image;
        }
      } catch (\Throwable $error) {
        // Continue to the CLI fallback.
      }
    }

    $dwebp = trim((string)@shell_exec('command -v dwebp 2>/dev/null'));
    if ($dwebp !== '') {
      $png = @shell_exec(escapeshellarg($dwebp) . ' -quiet ' . escapeshellarg($path) . ' -o - 2>/dev/null');
      if ($png !== null && $png !== '') {
        $image = @imagecreatefromstring($png);
        if ($image !== false) return $image;
      }
    }

    return false;
  }
}
