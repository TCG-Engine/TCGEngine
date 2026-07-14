<?php
// Cover-crop to (w:h), resize to exactly w x h, write WebP. Returns false on undecodable input.
// $vAnchor controls the vertical crop position when height is the cropped dimension:
// 'center' (default) trims top and bottom equally; 'top' keeps the top edge and trims the bottom.
// Imagick-only: XAMPP's bundled GD is compiled without WebP, so all asset processing goes
// through Imagick (see newhost/harden-webp.sh and zzImageConverter.php).
function SWUCosmeticProcessImage($srcPath, $destPath, $w, $h, $vAnchor = 'center') {
    if (!class_exists('Imagick')) return false;
    $data = @file_get_contents($srcPath);
    if ($data === false) return false;
    try {
        $img = new Imagick();
        $img->readImageBlob($data);
    } catch (Exception $e) {
        return false;
    }
    $sw = $img->getImageWidth(); $sh = $img->getImageHeight();
    if ($sw < 1 || $sh < 1) { $img->clear(); $img->destroy(); return false; }

    // cover crop: pick the largest centered sw'×sh' matching target aspect
    $targetAR = $w / $h; $srcAR = $sw / $sh;
    if ($srcAR > $targetAR) { $ch = $sh; $cw = (int)round($sh * $targetAR); }
    else                    { $cw = $sw; $ch = (int)round($sw / $targetAR); }
    $cx = (int)(($sw - $cw) / 2);
    $cy = $vAnchor === 'top' ? 0 : (int)(($sh - $ch) / 2);

    try {
        $img->cropImage($cw, $ch, $cx, $cy);       // cover-crop to target aspect
        $img->setImagePage($cw, $ch, 0, 0);         // reset virtual canvas after crop
        $img->resizeImage($w, $h, Imagick::FILTER_LANCZOS, 1);
        $img->setImageFormat('webp');
        $img->setImageCompressionQuality(80);
        $dir = dirname($destPath);
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $ok = $img->writeImage($destPath);
    } catch (Exception $e) {
        $ok = false;
    }
    $img->clear(); $img->destroy();
    return (bool)$ok;
}
