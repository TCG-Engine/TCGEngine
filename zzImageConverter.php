<?php
include_once './AccountFiles/AccountSessionAPI.php';

$response = new stdClass();
$error = CheckLoggedInUserMod();
if ($error !== "") {
    $response->error = $error;
    echo json_encode($response);
    exit();
}

function CheckImage($cardID, $url, $definedType, $isBack = false, $set = "SOR", $rootPath = "")
{
    $filename = $rootPath . "WebpImages/" . $cardID . ".webp";
    $filenameNew = $rootPath . "UnimplementedCards/" . $cardID . ".webp";
    $concatFilename = $rootPath . "concat/" . $cardID . ".webp";
    $cropFilename = $rootPath . "crops/" . $cardID . "_cropped.png";
    $isNew = false;

    if (!file_exists($filename)) {
        echo "Image for $cardID does not exist.<br>";

        $tempDir = $rootPath . "TempImages/";
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $urlExtension = pathinfo($url, PATHINFO_EXTENSION);
        $tempName = $tempDir . $cardID . "." . $urlExtension;

        // Download the image
        $ch = curl_init($url);
        $fp = fopen($tempName, 'w');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if ($curlError) {
            echo "Failed to download image for $cardID: $curlError<br>";
            if (file_exists($tempName)) unlink($tempName);
            return;
        }

        echo "Image for $cardID successfully retrieved.<br>";

        // Try Imagick first
        if (class_exists('Imagick')) {
            try {
                $image = new Imagick($tempName);

                if ($definedType == "Base" || $definedType == "Leader") {
                    if ($image->getImageHeight() > $image->getImageWidth()) {
                        $image->rotateimage(new ImagickPixel('none'), -90);
                    }
                    $image->resizeImage(628, 450, Imagick::FILTER_LANCZOS, 1, true);
                } else {
                    $image->resizeImage(450, 628, Imagick::FILTER_LANCZOS, 1, true);
                }

                $image->setImageFormat('webp');
                if (!$image->writeImage($filename)) {
                    throw new Exception("Imagick failed to write webp.");
                }
                $image->clear();
                $image->destroy();
                unlink($tempName);
                $isNew = true;
            } catch (Exception $e) {
                echo "Imagick processing failed for $cardID: " . $e->getMessage() . "<br>";
                // fallback to GD below
            }
        }

        // Fallback to GD if needed or if Imagick not loaded
        if (!$isNew) {
            $imageInfo = @getimagesize($tempName);
            if ($imageInfo === false) {
                echo("Failed to get image info for $cardID. Deleting file.<br>");
                unlink($tempName);
                return;
            }
            $mime = $imageInfo['mime'];
            $image = false;
            if ($mime === 'image/webp') {
                $image = @imagecreatefromwebp($tempName);
            } elseif ($mime === 'image/png') {
                $image = @imagecreatefrompng($tempName);
            } elseif ($mime === 'image/jpeg') {
                $image = @imagecreatefromjpeg($tempName);
            } else {
                echo("Unsupported or unrecognized image MIME type: $mime for $cardID. Deleting file.<br>");
                unlink($tempName);
                return;
            }
            if ($image === false) {
                echo("Failed to create image resource for $cardID. Deleting file.<br>");
                unlink($tempName);
                return;
            }
            if ($definedType == "Base" || $definedType == "Leader") {
                if (imagesy($image) > imagesx($image)) $image = imagerotate($image, -90, 0);
                $image = imagescale($image, 628, 450);
            } else {
                $image = imagescale($image, 450, 628);
            }
            if (!imagewebp($image, $filename)) {
                echo("Failed to convert image to webp format for $cardID.<br>");
                imagedestroy($image);
                unlink($tempName);
                return;
            }
            imagedestroy($image);
            unlink($tempName);
            $isNew = true;
        }
    }

    // The rest of your original function's image processing steps
    // also update them similarly: try Imagick first, fallback to GD if needed

    if ($isNew && !file_exists($filenameNew)) {
        echo "Converting image for $cardID to new format.<br>";
        if (class_exists('Imagick')) {
            try {
                $image = new Imagick($filename);
                $image->setImageFormat('webp');
                $image->writeImage($filenameNew);
                $image->clear();
                $image->destroy();
                $image->
            } catch (Exception $e) {
                echo "Imagick failed converting new format for $cardID: " . $e->getMessage() . "<br>";
            }
        }
        if (!file_exists($filenameNew)) {
            // fallback GD
            try {
                $image = imagecreatefromwebp($filename);
            } catch (Exception $e) {
                $image = imagecreatefrompng($filename);
            }
            imagewebp($image, $filenameNew);
            imagedestroy($image);
        }
    }

    if (!file_exists($concatFilename)) {
        echo "Concat image for $cardID does not exist. Converting: $filename<br>";
        if (file_exists($filename)) {
            if (class_exists('Imagick')) {
                try {
                    $image = new Imagick($filename);

                    if ($definedType == "Event") {
                        $imageTop = $image->clone();
                        $imageTop->cropImage(450, 110, 0, 0);

                        $imageBottom = $image->clone();
                        $imageBottom->cropImage(450, 628 - 320, 0, 320);

                        $dest = new Imagick();
                        $dest->newImage(450, 450, new ImagickPixel('transparent'));
                        $dest->compositeImage($imageTop, Imagick::COMPOSITE_DEFAULT, 0, 0);
                        $dest->compositeImage($imageBottom, Imagick::COMPOSITE_DEFAULT, 0, 111);
                    } else {
                        $imageTop = $image->clone();
                        $imageTop->cropImage(450, 397, 0, 0);

                        $imageBottom = $image->clone();
                        $imageBottom->cropImage(450, 628 - 595, 0, 595);

                        $dest = new Imagick();
                        $dest->newImage(450, 450, new ImagickPixel('transparent'));
                        $dest->compositeImage($imageTop, Imagick::COMPOSITE_DEFAULT, 0, 0);
                        $dest->compositeImage($imageBottom, Imagick::COMPOSITE_DEFAULT, 0, 398);
                    }

                    $dest->setImageFormat('webp');
                    $dest->writeImage($concatFilename);

                    $image->clear();
                    $image->destroy();
                    $dest->clear();
                    $dest->destroy();
                    $imageTop->clear();
                    $imageTop->destroy();
                    $imageBottom->clear();
                    $imageBottom->destroy();

                    if (file_exists($concatFilename)) {
                        echo "Image for $cardID successfully converted to concat.<br>";
                    }
                } catch (Exception $e) {
                    echo "Imagick concat conversion failed for $cardID: " . $e->getMessage() . "<br>";
                    // fallback GD below
                }
            }
            if (!file_exists($concatFilename)) {
                // fallback GD
                $image = imagecreatefromwebp($filename);
                if ($definedType == "Event") {
                    $imageTop = imagecrop($image, ['x' => 0, 'y' => 0, 'width' => 450, 'height' => 110]);
                    $imageBottom = imagecrop($image, ['x' => 0, 'y' => 320, 'width' => 450, 'height' => 628]);
                    $dest = imagecreatetruecolor(450, 450);
                    imagecopy($dest, $imageTop, 0, 0, 0, 0, 450, 110);
                    imagecopy($dest, $imageBottom, 0, 111, 0, 0, 450, 404);
                } else {
                    $imageTop = imagecrop($image, ['x' => 0, 'y' => 0, 'width' => 450, 'height' => 397]);
                    $imageBottom = imagecrop($image, ['x' => 0, 'y' => 595, 'width' => 450, 'height' => 628]);
                    $dest = imagecreatetruecolor(450, 450);
                    imagecopy($dest, $imageTop, 0, 0, 0, 0, 450, 397);
                    imagecopy($dest, $imageBottom, 0, 398, 0, 0, 450, 53);
                }
                imagewebp($dest, $concatFilename);
                imagedestroy($image);
                imagedestroy($dest);
                imagedestroy($imageTop);
                imagedestroy($imageBottom);
                if (file_exists($concatFilename)) {
                    echo "Image for $cardID successfully converted to concat (GD fallback).<br>";
                }
            }
        }
    }

    if (!file_exists($cropFilename)) {
        echo "Crop image for $cardID does not exist.<br>";
        if (file_exists($filename)) {
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
                    $image->clear();
                    $image->destroy();
                    if (file_exists($cropFilename)) {
                        echo "Image for $cardID successfully converted to crops.<br>";
                    }
                } catch (Exception $e) {
                    echo "Imagick crop conversion failed for $cardID: " . $e->getMessage() . "<br>";
                    // fallback to GD below
                }
            }
            if (!file_exists($cropFilename)) {
                // fallback GD
                try {
                    $image = imagecreatefromwebp($filename);
                } catch (Exception $e) {
                    $image = imagecreatefrompng($filename);
                }
                if ($definedType == "Event") {
                    $image = imagecrop($image, ['x' => 50, 'y' => 326, 'width' => 350, 'height' => 246]);
                } else {
                    $image = imagecrop($image, ['x' => 50, 'y' => 100, 'width' => 350, 'height' => 270]);
                }
                imagepng($image, $cropFilename);
                imagedestroy($image);
                if (file_exists($cropFilename)) {
                    echo "Image for $cardID successfully converted to crops (GD fallback).<br>";
                }
            }
        }
    }
}
?>
