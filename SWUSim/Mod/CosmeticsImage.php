<?php
// Cover-crop to (w:h), resize to exactly w x h, write WebP. Returns false on undecodable input.
function SWUCosmeticProcessImage($srcPath, $destPath, $w, $h) {
    $data = @file_get_contents($srcPath);
    if ($data === false) return false;
    $src = @imagecreatefromstring($data);
    if (!$src) return false;
    $sw = imagesx($src); $sh = imagesy($src);
    if ($sw < 1 || $sh < 1) { imagedestroy($src); return false; }

    // cover crop: pick the largest centered sw'×sh' matching target aspect
    $targetAR = $w / $h; $srcAR = $sw / $sh;
    if ($srcAR > $targetAR) { $ch = $sh; $cw = (int)round($sh * $targetAR); }
    else                    { $cw = $sw; $ch = (int)round($sw / $targetAR); }
    $cx = (int)(($sw - $cw) / 2); $cy = (int)(($sh - $ch) / 2);

    $dst = imagecreatetruecolor($w, $h);
    imagealphablending($dst, false); imagesavealpha($dst, true);
    imagecopyresampled($dst, $src, 0, 0, $cx, $cy, $w, $h, $cw, $ch);

    $dir = dirname($destPath);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $ok = imagewebp($dst, $destPath, 80);
    imagedestroy($src); imagedestroy($dst);
    return (bool)$ok;
}
