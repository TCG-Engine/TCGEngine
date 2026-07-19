<?php
// Self-contained QR rendering for the baked-in deck-image QR code.
//
// Uses the QR encoder that already ships with tcpdf (`TCPDF2DBarcode`, autoloaded via the
// Composer classmap that CreateImage.php already pulls in through vendor/autoload.php). tcpdf is a
// permanent dependency of this app (deck-PDF export), so this adds NO new dependency, no
// composer.json/lock change, and makes no network call at render time — the encoder runs in-process.
//
// RenderQR() returns a GD image of the QR: black modules on a solid white background, with a
// quiet-zone border baked in so the code stays scannable when composited onto the dark deck art.
// Module size is snapped to an integer so modules land on exact pixel boundaries (crisp, no blur);
// the returned canvas is therefore the largest integer-module square that fits within $sizePx.

/**
 * Render a QR code for $data as a GD image.
 *
 * @param string $data          The string to encode (e.g. a deck share URL).
 * @param int    $sizePx        Target square size in pixels (the result is <= this, snapped to whole modules).
 * @param int    $quietModules  Width of the white quiet-zone border, in modules (spec minimum is 4).
 * @return \GdImage|resource     A GD image (caller owns it; call imagedestroy when done).
 * @throws \RuntimeException      If encoding fails or produces an empty matrix.
 */
function RenderQR($data, $sizePx, $quietModules = 4) {
  $barcode = new \TCPDF2DBarcode($data, 'QRCODE,M'); // medium error correction: tolerant + compact
  $arr = $barcode->getBarcodeArray();
  if (empty($arr) || empty($arr['num_rows']) || empty($arr['num_cols']) || empty($arr['bcode'])) {
    throw new \RuntimeException('RenderQR: QR encoding produced no matrix for the given data.');
  }

  $rows = (int) $arr['num_rows'];
  $cols = (int) $arr['num_cols'];
  $totalModules = max($rows, $cols) + 2 * $quietModules; // symbol is square; include quiet zone
  $moduleSize = (int) floor($sizePx / $totalModules);
  if ($moduleSize < 1) { $moduleSize = 1; }              // never collapse below 1px/module
  $canvas = $moduleSize * $totalModules;

  $img = imagecreatetruecolor($canvas, $canvas);
  $white = imagecolorallocate($img, 255, 255, 255);
  $black = imagecolorallocate($img, 0, 0, 0);
  imagefilledrectangle($img, 0, 0, $canvas, $canvas, $white); // white field (also the quiet zone)

  for ($r = 0; $r < $rows; $r++) {
    for ($c = 0; $c < $cols; $c++) {
      if (empty($arr['bcode'][$r][$c])) { continue; }
      $x = ($c + $quietModules) * $moduleSize;
      $y = ($r + $quietModules) * $moduleSize;
      imagefilledrectangle($img, $x, $y, $x + $moduleSize - 1, $y + $moduleSize - 1, $black);
    }
  }

  return $img;
}
