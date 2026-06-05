<?php
include_once './AccountFiles/AccountSessionAPI.php';

// Skip auth check for CLI invocations (e.g., code generator running from MCP server).
// HTTP requests always require a valid mod session.
$isHTTPRequest = php_sapi_name() !== 'cli' && !empty($_SERVER['REQUEST_METHOD']);

$response = new stdClass();
if ($isHTTPRequest) {
    $error = CheckLoggedInUserMod();
    if ($error !== "") {
        $response->error = $error;
        echo json_encode($response);
        exit();
    }
}

// Card dimensions after resize: 450×628 (portrait) or 628×450 (landscape for Leader/Base).
//
// Concat crop specs (all produce 450×450 output from a 450×628 portrait card):
//   Unit    — skip top 14px chrome; take 450×450 from y=14
//   Event   — top section: skip 14px, take 184px (y=14); bottom section: skip bottom 44px, take 266px (y=318); stack top@0, bottom@184
//   Upgrade — top section: skip 14px, take 370px (y=14); bottom section: skip bottom 32px, take 80px (y=516); stack top@0, bottom@370

function CheckImage($cardID, $url, $definedType, $isBack = false, $set = "SOR", $rootPath = "", $squareCards = false, $overwriteImages = false)
{
    $filename      = $rootPath . "WebpImages/" . $cardID . ".webp";
    $filenameNew   = $rootPath . "UnimplementedCards/" . $cardID . ".webp";
    $concatFilename = $rootPath . "concat/" . $cardID . ".webp";
    $cropFilename  = $rootPath . "crops/" . $cardID . "_cropped.png";
    $isNew = false;

    if ($overwriteImages) {
        foreach ([$filename, $filenameNew, $concatFilename, $cropFilename] as $f) {
            if (file_exists($f)) unlink($f);
        }
    }

    // ── Download + resize to webp ──────────────────────────────────────────────
    if (!file_exists($filename)) {
        echo "Image for $cardID does not exist.<br>";

        $tempDir = $rootPath . "TempImages/";
        if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
        $urlExtension = pathinfo($url, PATHINFO_EXTENSION);
        $tempName = $tempDir . $cardID . "." . $urlExtension;

        $ch = curl_init($url);
        $fp = fopen($tempName, 'w');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if ($curlError) {
            echo "Failed to download image for $cardID from $url: $curlError<br>";
            if (file_exists($tempName)) unlink($tempName);
            return;
        }

        echo "Image for $cardID successfully retrieved.<br>";

        if (class_exists('Imagick')) {
            try {
                $image = new Imagick($tempName);
                if (!$squareCards) {
                    if ($definedType == "Base" || $definedType == "Leader") {
                        if ($image->getImageHeight() > $image->getImageWidth()) {
                            $image->rotateimage(new ImagickPixel('none'), -90);
                        }
                        $image->resizeImage(628, 450, Imagick::FILTER_LANCZOS, 1, true);
                    } elseif ($definedType == "LeaderUnit") {
                        // Leader unit-side arrives landscape; rotate to portrait before resizing.
                        if ($image->getImageWidth() > $image->getImageHeight()) {
                            $image->rotateimage(new ImagickPixel('none'), 90);
                        }
                        $image->resizeImage(450, 628, Imagick::FILTER_LANCZOS, 1, true);
                    } else {
                        $image->resizeImage(450, 628, Imagick::FILTER_LANCZOS, 1, true);
                    }
                }
                $image->setImageFormat('webp');
                if (!$image->writeImage($filename)) throw new Exception("Imagick failed to write webp.");
                $image->clear(); $image->destroy();
                unlink($tempName);
                $isNew = true;
            } catch (Exception $e) {
                echo "Imagick processing failed for $cardID: " . $e->getMessage() . "<br>";
            }
        }

        if (!$isNew) {
            $imageInfo = @getimagesize($tempName);
            if ($imageInfo === false) {
                echo "Failed to get image info for $cardID. Deleting file.<br>";
                unlink($tempName);
                return;
            }
            $mime = $imageInfo['mime'];
            $image = false;
            if ($mime === 'image/webp')       $image = @imagecreatefromwebp($tempName);
            elseif ($mime === 'image/png')    $image = @imagecreatefrompng($tempName);
            elseif ($mime === 'image/jpeg')   $image = @imagecreatefromjpeg($tempName);
            else {
                echo "Unsupported MIME type: $mime for $cardID. Deleting file.<br>";
                unlink($tempName);
                return;
            }
            if ($image === false) {
                echo "Failed to create image resource for $cardID. Deleting file.<br>";
                unlink($tempName);
                return;
            }
            if (!$squareCards) {
                if ($definedType == "Base" || $definedType == "Leader") {
                    if (imagesy($image) > imagesx($image)) $image = imagerotate($image, -90, 0);
                    $image = imagescale($image, 628, 450);
                } else {
                    $image = imagescale($image, 450, 628);
                }
            }
            if (!imagewebp($image, $filename)) {
                echo "Failed to convert image to webp for $cardID.<br>";
                imagedestroy($image);
                unlink($tempName);
                return;
            }
            imagedestroy($image);
            unlink($tempName);
            $isNew = true;
        }
    }

    // ── Copy to UnimplementedCards ─────────────────────────────────────────────
    if ($isNew && !file_exists($filenameNew)) {
        echo "Converting image for $cardID to new format.<br>";
        if (class_exists('Imagick')) {
            try {
                $image = new Imagick($filename);
                $image->setImageFormat('webp');
                $image->writeImage($filenameNew);
                $image->clear(); $image->destroy();
            } catch (Exception $e) {
                echo "Imagick failed converting new format for $cardID: " . $e->getMessage() . "<br>";
            }
        }
        if (!file_exists($filenameNew)) {
            try { $image = imagecreatefromwebp($filename); }
            catch (Exception $e) { $image = imagecreatefrompng($filename); }
            imagewebp($image, $filenameNew);
            imagedestroy($image);
        }
    }

    // ── Concat (450×450 square for arena display) ──────────────────────────────
    if (!file_exists($concatFilename) && file_exists($filename)) {
        echo "Concat image for $cardID does not exist. Converting: $filename<br>";

        if ($squareCards) {
            copy($filename, $concatFilename);
            echo "Image for $cardID successfully copied to concat (square cards).<br>";
        } elseif ($definedType === "Unit" || $definedType === "LeaderUnit") {
            // Inset 14px on top/left; take 420×420 (trims the card border off the
            // art), then scale back up to a uniform 450×450 like the other types.
            _concatSingleCrop($filename, $concatFilename, $cardID, 14, 14, 420, 420, 450, 450);
        } elseif ($definedType === "Event") {
            // Top 140px from y=20; bottom 260px from y=310; stacked → 400px,
            // then scaled up to a uniform 450×450 like the other types.
            _concatTwoSection($filename, $concatFilename, $cardID,
                topSrcY:20, topH:140, botSrcY:310, botH:260, outW:450, outH:450);
        } elseif ($definedType === "Upgrade" || $definedType === "Token Upgrade" || $definedType === "Token Unit") {
            // Top 342px from y=14; bottom 80px from y=516 (keeps the bottom stat boxes);
            // stacked → 422px, then scaled up to a uniform 450×450. Token Upgrades
            // (Experience SOR_T01) AND Token Units print their stats at the BOTTOM like
            // upgrades, so they use this crop — not the unit single-crop (which would
            // chop the stats) or the legacy default.
            _concatTwoSection($filename, $concatFilename, $cardID,
                topSrcY:14, topH:342, botSrcY:516, botH:80, outW:450, outH:450);
        } else {
            // Leader / Base / fallback: top 400px from y=15 + bottom 10px from
            // y=595; stacked → 410px, then scaled up to a uniform 450×450.
            _concatTwoSection($filename, $concatFilename, $cardID,
                topSrcY:15, topH:400, botSrcY:595, botH:10, outW:450, outH:450);
        }
    }

    // ── Crop (art thumbnail for hover/tooltip) ─────────────────────────────────
    if (!file_exists($cropFilename) && file_exists($filename)) {
        echo "Crop image for $cardID does not exist.<br>";
        if (class_exists('Imagick')) {
            try {
                $image = new Imagick($filename);
                if ($definedType == "Event") {
                    $image->cropImage(350, 246, 50, 326);
                } else {
                    $image->cropImage(350, 270, 50, 100);
                }
                $image->setImageFormat('png');
                $image->writeImage($cropFilename);
                $image->clear(); $image->destroy();
                if (file_exists($cropFilename)) echo "Image for $cardID successfully converted to crops.<br>";
            } catch (Exception $e) {
                echo "Imagick crop conversion failed for $cardID: " . $e->getMessage() . "<br>";
            }
        }
        if (!file_exists($cropFilename)) {
            try { $image = imagecreatefromwebp($filename); }
            catch (Exception $e) { $image = imagecreatefrompng($filename); }
            if ($definedType == "Event") {
                $image = imagecrop($image, ['x' => 50, 'y' => 326, 'width' => 350, 'height' => 246]);
            } else {
                $image = imagecrop($image, ['x' => 50, 'y' => 100, 'width' => 350, 'height' => 270]);
            }
            imagepng($image, $cropFilename);
            imagedestroy($image);
            if (file_exists($cropFilename)) echo "Image for $cardID successfully converted to crops (GD fallback).<br>";
        }
    }
}

// Single straight crop → ($outW×$outH) output. $outW/$outH default to the crop
// size; when larger, the crop is scaled up to fill (e.g. 420 crop → 450 output).
function _concatSingleCrop($src, $dest, $cardID, $srcX, $srcY, $w, $h, $outW = null, $outH = null)
{
    $outW = $outW ?? $w;
    $outH = $outH ?? $h;
    if (class_exists('Imagick')) {
        try {
            $image = new Imagick($src);
            $image->cropImage($w, $h, $srcX, $srcY);
            $image->setImagePage($w, $h, 0, 0);
            if ($outW !== $w || $outH !== $h) {
                $image->resizeImage($outW, $outH, Imagick::FILTER_LANCZOS, 1);
            }
            $image->setImageFormat('webp');
            $image->writeImage($dest);
            $image->clear(); $image->destroy();
            if (file_exists($dest)) { echo "Image for $cardID successfully converted to concat.<br>"; return; }
        } catch (Exception $e) {
            echo "Imagick single-crop failed for $cardID: " . $e->getMessage() . "<br>";
        }
    }
    // GD fallback
    $image = imagecreatefromwebp($src);
    $out = imagecreatetruecolor($outW, $outH);
    imagecopyresampled($out, $image, 0, 0, $srcX, $srcY, $outW, $outH, $w, $h);
    imagewebp($out, $dest);
    imagedestroy($image); imagedestroy($out);
    if (file_exists($dest)) echo "Image for $cardID successfully converted to concat (GD fallback).<br>";
}

// Two-section stacked concat. Sections stack to 450×(topH+botH); when $outW/$outH
// are given and differ, the stacked result is scaled to that final size.
function _concatTwoSection($src, $dest, $cardID, $topSrcY, $topH, $botSrcY, $botH, $outW = null, $outH = null)
{
    $stackH = $topH + $botH;
    $finalW = $outW ?? 450;
    $finalH = $outH ?? $stackH;
    if (class_exists('Imagick')) {
        try {
            $image = new Imagick($src);

            $imageTop = $image->clone();
            $imageTop->cropImage(450, $topH, 0, $topSrcY);
            $imageTop->setImagePage(450, $topH, 0, 0);

            $imageBot = $image->clone();
            $imageBot->cropImage(450, $botH, 0, $botSrcY);
            $imageBot->setImagePage(450, $botH, 0, 0);

            $out = new Imagick();
            $out->newImage(450, $stackH, new ImagickPixel('transparent'));
            $out->compositeImage($imageTop, Imagick::COMPOSITE_DEFAULT, 0, 0);
            $out->compositeImage($imageBot,  Imagick::COMPOSITE_DEFAULT, 0, $topH);
            if ($finalW !== 450 || $finalH !== $stackH) {
                $out->resizeImage($finalW, $finalH, Imagick::FILTER_LANCZOS, 1);
            }
            $out->setImageFormat('webp');
            $out->writeImage($dest);

            $image->clear();    $image->destroy();
            $imageTop->clear(); $imageTop->destroy();
            $imageBot->clear(); $imageBot->destroy();
            $out->clear();      $out->destroy();

            if (file_exists($dest)) { echo "Image for $cardID successfully converted to concat.<br>"; return; }
        } catch (Exception $e) {
            echo "Imagick two-section concat failed for $cardID: " . $e->getMessage() . "<br>";
        }
    }
    // GD fallback
    $image = imagecreatefromwebp($src);
    $stack = imagecreatetruecolor(450, $stackH);
    imagealphablending($stack, false); imagesavealpha($stack, true);
    imagecopy($stack, $image, 0,     0, 0, $topSrcY, 450, $topH);
    imagecopy($stack, $image, 0, $topH, 0, $botSrcY, 450, $botH);
    if ($finalW !== 450 || $finalH !== $stackH) {
        $out = imagecreatetruecolor($finalW, $finalH);
        imagealphablending($out, false); imagesavealpha($out, true);
        imagecopyresampled($out, $stack, 0, 0, 0, 0, $finalW, $finalH, 450, $stackH);
        imagedestroy($stack);
    } else {
        $out = $stack;
    }
    imagewebp($out, $dest);
    imagedestroy($image); imagedestroy($out);
    if (file_exists($dest)) echo "Image for $cardID successfully converted to concat (GD fallback).<br>";
}
