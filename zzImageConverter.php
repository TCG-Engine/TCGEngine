<?php
include_once './AccountFiles/AccountSessionAPI.php';

$response = new stdClass();
$error = CheckLoggedInUserMod();
if($error !== "") {
  $response->error = $error;
  echo json_encode($response);
  exit();
}

//TODO: Make the card image generation size customizable in a schema file
function CheckImage($cardID, $url, $definedType, $isBack=false, $set="SOR", $rootPath="")
{
  $filename = $rootPath . "WebpImages/" . $cardID . ".webp";
  $filenameNew = "UnimplementedCards/" . $cardID . ".webp";
  $concatFilename = $rootPath . "concat/" . $cardID . ".webp";
  $cropFilename = $rootPath . "crops/" . $cardID . "_cropped.png";
  $isNew = false;
  if(!file_exists($filename))
  {
    $imageURL = $url;
    $urlExtension = pathinfo($imageURL, PATHINFO_EXTENSION);
    $tempName = $rootPath . "TempImages/" . $cardID . "." . $urlExtension;
    echo("Image for " . $cardID . " does not exist.<BR>");
    $handler = fopen($tempName, "w");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $imageURL);
    curl_setopt($ch, CURLOPT_FILE, $handler);
    curl_exec($ch);
    curl_close($ch);
    //if(filesize($filename) < 10000) { unlink($filename); return; }
    if(file_exists($tempName))
    {
      echo("Image for " . $cardID . " successfully retrieved.<BR>");
      echo("Normalizing file size for " . $cardID . ".<BR>");
      // Robust image type detection
      $imageInfo = @getimagesize($tempName);
      if ($imageInfo === false) {
        echo("Failed to get image info for $cardID. Deleting file.<BR>");
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
        echo("Unsupported or unrecognized image MIME type: $mime for $cardID. Deleting file.<BR>");
        unlink($tempName);
        return;
      }
      if ($image === false) {
        echo("Failed to create image resource for $cardID. Deleting file.<BR>");
        unlink($tempName);
        return;
      }
      if($definedType == "Base" || $definedType == "Leader") {
        if(imagesy($image) > imagesx($image)) $image = imagerotate($image, -90, 0);
        $image = imagescale($image, 628, 450);
      }
      else $image = imagescale($image, 450, 628);
      if (!imagewebp($image, $filename)) {
        echo("Failed to convert image to webp format for " . $cardID . ".<BR>");
        return;
      }
      // Free up memory
      imagedestroy($image);
    }
    $isNew = true;
  }
  if($isNew && !file_exists($filenameNew)) {
    echo("Converting image for " . $cardID . " to new format.<BR>");
    try {
      $image = imagecreatefromwebp($filename);
    } catch(Exception $e) {
      $image = imagecreatefrompng($filename);
    }
    imagewebp($image, $filenameNew);
    imagedestroy($image);
  }
  if(!file_exists($concatFilename))
  {
    echo("Concat image for " . $cardID . " does not exist. Converting: $filename<BR>");
    if(file_exists($filename))
    {
      echo("Attempting to convert image for " . $cardID . " to concat.<BR>");

      $image = imagecreatefromwebp($filename);
      //$image = imagecreatefrompng($filename);

      if($definedType == "Event") {
        $imageTop = imagecrop($image, ['x' => 0, 'y' => 0, 'width' => 450, 'height' => 110]);
        $imageBottom = imagecrop($image, ['x' => 0, 'y' => 320, 'width' => 450, 'height' => 628]);

        $dest = imagecreatetruecolor(450, 450);
        imagecopy($dest, $imageTop, 0, 0, 0, 0, 450, 110);
        imagecopy($dest, $imageBottom, 0, 111, 0, 0, 450, 404);
      }
      else {
        //$imageTop = imagecrop($image, ['x' => 0, 'y' => 0, 'width' => 450, 'height' => 372]);
        //$imageBottom = imagecrop($image, ['x' => 0, 'y' => 570, 'width' => 450, 'height' => 628]);
        $imageTop = imagecrop($image, ['x' => 0, 'y' => 0, 'width' => 450, 'height' => 397]);
        $imageBottom = imagecrop($image, ['x' => 0, 'y' => 595, 'width' => 450, 'height' => 628]);

        $dest = imagecreatetruecolor(450, 450);
        imagecopy($dest, $imageTop, 0, 0, 0, 0, 450, 397);
        imagecopy($dest, $imageBottom, 0, 398, 0, 0, 450, 53);
      }

      imagewebp($dest, $concatFilename);
      // Free up memory
      imagedestroy($image);
      imagedestroy($dest);
      imagedestroy($imageTop);
      imagedestroy($imageBottom);
      if(file_exists($concatFilename)) echo("Image for " . $cardID . " successfully converted to concat.<BR>");
    }
  }
  if(!file_exists($cropFilename))
  {
    $imageURL = $url;
    $urlExtension = pathinfo($imageURL, PATHINFO_EXTENSION);
    $tempName = $rootPath . "TempImages/" . $cardID . "." . $urlExtension;
    echo("Crop image for " . $cardID . " does not exist.<BR>");
    if(file_exists($filename))
    {
      echo("Attempting to convert image for " . $cardID . " to crops.<BR>");
      try {
        // Use Imagick for WebP
        if (class_exists('Imagick')) {
            $imagick = new Imagick();
            $imagick->readImage($filename);
            $imagick->clear();
            $imagick->destroy();
        } else {
          $image = imagecreatefromwebp($filename);
        }
      } catch(Exception $e) {
        $image = imagecreatefrompng($filename);
      }
      $imageToCrop = imagecreatefrompng($tempName);
      if($definedType == "Event") $image = imagecrop($imageToCrop, ['x' => 50, 'y' => 326, 'width' => 350, 'height' => 246]);
      else $image = imagecrop($imageToCrop, ['x' => 50, 'y' => 100, 'width' => 350, 'height' => 270]);
      imagepng($image, $cropFilename);
      imagedestroy($image);
      if(file_exists($cropFilename)) echo("Image for " . $cardID . " successfully converted to crops.<BR>");
    }
  }
}


?>
