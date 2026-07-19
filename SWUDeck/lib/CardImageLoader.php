<?php
// Decode a card image (WebP on disk) into a GD image resource, portably.
//
// Card art lives as `.webp` under WebpImages/. GD's imagecreatefromwebp() only exists when GD was
// compiled `--with-webp` — true in dev/Docker, but NOT on the prod LAMPP PHP 8.2 build (gd_info()
// reports "WebP Support" off), where imagecreatefromwebp() is undefined and calling it is a fatal.
//
// LoadCardImageAsGd() tries, in order: native GD WebP → Imagick (PNG blob → GD) → the dwebp CLI
// (PNG on stdout → GD). It returns a GD image, or false if every path fails — callers already treat
// false by drawing a gray placeholder, so a single unreadable card never aborts the render.

if (!function_exists('LoadCardImageAsGd')) {
  /**
   * @param string $path Absolute path to a card image (typically .webp).
   * @return \GdImage|resource|false GD image on success, false if it can't be decoded.
   */
  function LoadCardImageAsGd($path) {
    if (!is_string($path) || $path === '' || !file_exists($path)) {
      return false;
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    if ($ext !== 'webp') {
      // Non-webp (jpeg/png/etc): GD reads these natively on every build.
      $bytes = @file_get_contents($path);
      return $bytes === false ? false : @imagecreatefromstring($bytes);
    }

    // 1) Native GD WebP — fast path for builds compiled --with-webp (dev/Docker).
    if (function_exists('imagecreatefromwebp')) {
      $img = @imagecreatefromwebp($path);
      if ($img !== false) {
        return $img;
      }
    }

    // 2) Imagick — read the webp, hand GD a PNG blob. Preferred prod path (in-process, no shell).
    if (extension_loaded('imagick')) {
      try {
        $im = new \Imagick();
        $im->readImage($path);
        $im->setImageFormat('png');
        $blob = $im->getImageBlob();
        $im->clear();
        $im->destroy();
        if ($blob !== '') {
          $img = @imagecreatefromstring($blob);
          if ($img !== false) {
            return $img;
          }
        }
      } catch (\Throwable $e) {
        // fall through to the CLI backstop
      }
    }

    // 3) dwebp CLI — decode to PNG on stdout, then into GD. Backstop when GD+Imagick can't.
    $dwebp = trim((string) @shell_exec('command -v dwebp 2>/dev/null'));
    if ($dwebp !== '') {
      $png = @shell_exec(escapeshellarg($dwebp) . ' -quiet ' . escapeshellarg($path) . ' -o - 2>/dev/null');
      if ($png !== null && $png !== '') {
        $img = @imagecreatefromstring($png);
        if ($img !== false) {
          return $img;
        }
      }
    }

    return false;
  }
}
