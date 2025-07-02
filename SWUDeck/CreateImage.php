<?php
require_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/GamestateParser.php';
include_once __DIR__ . '/ZoneAccessors.php';
include_once __DIR__ . '/ZoneClasses.php';
include_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/../Core/HTTPLibraries.php';
include_once __DIR__ . '/../Database/ConnectionManager.php';
include_once __DIR__ . '/../AccountFiles/AccountDatabaseAPI.php';
include_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../Assets/patreon-php-master/src/PatreonLibraries.php';
include_once __DIR__ . '/../Assets/patreon-php-master/src/PatreonDictionary.php';


if(!isset($gameName)) {
  $gameName = TryGet("gameName", "");
}

if($gameName == "") {
  echo("You must provide a game name to generate this image.");
  exit;
}
/*
if(!IsUserLoggedIn()) {
  echo("You must be logged in to generate this image.");
  exit;
}
$loggedInUser = LoggedInUser();
*/
$assetData = LoadAssetData(1, $gameName);
if($assetData == null) {
  echo("This game asset does not exist.");
  exit;
}
if($assetData["assetVisibility"] == 0 && !isset($fromBot)) {
  echo("This game asset is private.");
  exit;
}
/*
$assetOwner = $assetData["assetOwner"];
if($loggedInUser != $assetOwner) {
  if($assetData["assetVisibility"] > 10000) {
    if(!IsPatron($assetData["assetVisibility"])){
      echo("You must be a patron to generate this image.");
      exit;
    }
  } else if($assetData["assetVisibility"] == 0) {
    echo("You must own this asset to generate this image.");
    exit;
  }
}
  */

$destFile = __DIR__ . "/Games/{$gameName}/DeckImage.jpg";

if(file_exists($destFile)) {
  unlink($destFile);
}

ParseGamestate(__DIR__ . "/");


$arr = &GetLeader(1);
$leaderID = count($arr) > 0 ? $arr[0]->CardID : "";
$arr = &GetBase(1);
$baseID = count($arr) > 0 ? $arr[0]->CardID : "";

$quantityIndex = [];
$arr = &GetMainDeck(1);
foreach ($arr as $card) {
  $cardID = $card->CardID;
  if (isset($quantityIndex[$cardID])) {
    $quantityIndex[$cardID]++;
  } else {
    $quantityIndex[$cardID] = 1;
  }
}

$numUnits = 0;
$numEvents = 0;
$numUpgrades = 0;
$groundUnits = [];
$spaceUnits = [];
$eventsAndUpgrades = [];
foreach ($quantityIndex as $cardID => $quantity) {
  $cardID = strval($cardID);
  $cardTitle = str_replace("\\", "", CardTitle($cardID));
  $cardType = CardType($cardID); // Assuming CardType function returns 'unit', 'event', or 'upgrade'
  $setID = str_replace("_", "", CardIDLookup($cardID));
  $cardRarity = CardRarity($cardID);
  $cardString = "{$cardTitle} ({$setID} - {$cardRarity}) x{$quantity}";
  switch ($cardType) {
    case 'Unit':
      $arena = CardArena($cardID);
      if($arena == "Ground") {
        //$groundUnits[] = $cardID;
        $groundUnits[$cardID] = $quantity;
      } else {
        //$spaceUnits[] = $cardID;
        $spaceUnits[$cardID] = $quantity;
      }
      $numUnits += $quantity;
      break;
    case 'Event':
      //$eventsAndUpgrades[] = $cardID;
      $eventsAndUpgrades[$cardID] = $quantity;
      $numEvents += $quantity;
      break;
    case 'Upgrade':
      //$eventsAndUpgrades[] = $cardID;
      $eventsAndUpgrades[$cardID] = $quantity;
      $numUpgrades += $quantity;
      break;
  }
}

$sideboard = [];
$numSideboard = 0;
$arr = &GetSideboard(1);
$sideboardQuantityIndex = [];
foreach ($arr as $card) {
  $cardID = $card->CardID;
  if (isset($sideboardQuantityIndex[$cardID])) {
    $sideboardQuantityIndex[$cardID]++;
  } else {
    $sideboardQuantityIndex[$cardID] = 1;
  }
}
foreach ($sideboardQuantityIndex as $cardID => $quantity) {
  $cardID = strval($cardID);
  $cardTitle = str_replace("\\", "", CardTitle($cardID));
  $cardType = CardType($cardID); // Assuming CardType function returns 'unit', 'event', or 'upgrade'
  $setID = str_replace("_", "", CardIDLookup($cardID));
  $cardRarity = CardRarity($cardID);
  $cardString = "{$cardTitle} ({$setID} - {$cardRarity}) x{$quantity}";
  $numSideboard += $quantity;
  $sideboard[] = $cardString;
}
// Sort sideboard alphabetically
sort($sideboard);

// Deck Link
$assetName = isset($assetData["assetName"]) ? $assetData["assetName"] : "Deck #" . $gameName;
$ownerData = LoadUserDataFromId($assetData["assetOwner"]);
$ownerName = isset($ownerData["usersUid"]) ? $ownerData["usersUid"] : "Unknown";
$width = 1298;
$height = 747;
$image = imagecreatetruecolor($width, $height);
//$background = imagecreatefromwebp('../Assets/Images/deckImageBackground.webp');
if($ownerName == "RebelResource") $background = imagecreatefromjpeg(__DIR__ . '/../Assets/Images/DeckBacks/RebelResourceDeck.jpg');
else $background = imagecreatefromjpeg(__DIR__ . '/../Assets/Images/deckImageBackground.jpg');


// Directly copy the background image without resampling
imagecopy($image, $background, 0, 0, 0, 0, $width, $height);
imagedestroy($background);

if($ownerName != "RebelResource") $text = $assetName . " by " . $ownerName;
$fontSize = 34;
$angle = 0;
$fontPath = __DIR__ . '/../Assets/Montserrat.ttf'; // Update this path to your TTF font file if needed
if($ownerName == "RebelResource") $fontPath = __DIR__ . '/../Assets/Soloist.otf'; // Update this path to your TTF font file if needed
//$fontPath = '../Assets/ShadowOfXizor.ttf'; // Update this path to your TTF font file if needed
//$fontPath = '../Assets/StarJedi.ttf'; // Update this path to your TTF font file if needed
$textColor = imagecolorallocate($image, 255, 255, 255);
$bbox = imagettfbbox($fontSize, $angle, $fontPath, $text);
$textWidth = $bbox[2] - $bbox[0];
$x = ($width - $textWidth) / 2;
$y = 70; // Vertical position from the top
imagettftext($image, $fontSize, $angle, $x, $y, $textColor, $fontPath, $text);




//Leader and base images
$iconSize = 200; // Target width for each image

// Load leader image
//$leaderImagePath = './WebpImages/' . $leaderID . '.webp';
$leaderImagePath = __DIR__ . '/./jpg/fullsize/' . $leaderID . '.jpg';
if (file_exists($leaderImagePath)) {
  //$leaderImage = imagecreatefromwebp($leaderImagePath);
  $leaderImage = imagecreatefromjpeg($leaderImagePath);
} else {
  $leaderImage = imagecreatetruecolor($iconSize, $iconSize);
  $placeholderColor = imagecolorallocate($leaderImage, 200, 200, 200);
  imagefilledrectangle($leaderImage, 0, 0, $iconSize, $iconSize, $placeholderColor);
}

// Load base image
//$baseImagePath = './WebpImages/' . $baseID . '.webp';
$baseImagePath = __DIR__ . '/./jpg/fullsize/' . $baseID . '.jpg';
if (file_exists($baseImagePath)) {
  //$baseImage = imagecreatefromwebp($baseImagePath);
  $baseImage = imagecreatefromjpeg($baseImagePath);
} else {
  $baseImage = imagecreatetruecolor($iconSize, $iconSize);
  $placeholderColor = imagecolorallocate($baseImage, 200, 200, 200);
  imagefilledrectangle($baseImage, 0, 0, $iconSize, $iconSize, $placeholderColor);
}

// Get original dimensions and compute new dimensions maintaining aspect ratio
$leaderOrigWidth = imagesx($leaderImage);
$leaderOrigHeight = imagesy($leaderImage);
$leaderNewWidth = $iconSize;
$leaderNewHeight = intval($iconSize * $leaderOrigHeight / $leaderOrigWidth);

$baseOrigWidth = imagesx($baseImage);
$baseOrigHeight = imagesy($baseImage);
$baseNewWidth = $iconSize;
$baseNewHeight = intval($iconSize * $baseOrigHeight / $baseOrigWidth);

// Resize leader image
$leaderResized = imagecreatetruecolor($leaderNewWidth, $leaderNewHeight);
imagealphablending($leaderResized, false);
imagesavealpha($leaderResized, true);
imagecopyresampled(
  $leaderResized, $leaderImage,
  0, 0, 0, 0,
  $leaderNewWidth, $leaderNewHeight,
  $leaderOrigWidth, $leaderOrigHeight
);
imagedestroy($leaderImage);

// Resize base image
$baseResized = imagecreatetruecolor($baseNewWidth, $baseNewHeight);
imagealphablending($baseResized, false);
imagesavealpha($baseResized, true);
imagecopyresampled(
  $baseResized, $baseImage,
  0, 0, 0, 0,
  $baseNewWidth, $baseNewHeight,
  $baseOrigWidth, $baseOrigHeight
);
imagedestroy($baseImage);

// Compute placement coordinates to center the leader image on the main image
$leaderX = ($width - $leaderNewWidth) / 2;
$leaderY = ($height - $leaderNewHeight) / 2 + 60;

// Place leader image
imagecopy($image, $leaderResized, $leaderX, $leaderY, 0, 0, $leaderNewWidth, $leaderNewHeight);
imagedestroy($leaderResized);

// Set a spacing value for separation between base and leader images
$spacing = 10;
// Compute placement coordinates to center the base image horizontally and place it above the leader image
$baseX = ($width - $baseNewWidth) / 2;
$baseY = $leaderY - $baseNewHeight - $spacing;
imagecopy($image, $baseResized, $baseX, $baseY, 0, 0, $baseNewWidth, $baseNewHeight);
imagedestroy($baseResized);
//Space units
$spaceIconSize = 100;
$spaceSpacing = 10;
// Starting coordinates for the grid
$spaceX = $spaceSpacing + 65;
$spaceY = 135; // Adjust as needed (below the title)
// Calculate the number of columns based on the overall image width
$columns = floor(($width / 2 - $baseNewWidth / 2 - $spaceSpacing) / ($spaceIconSize + $spaceSpacing));
//$columns = 4;
$colCount = 0;

foreach ($spaceUnits as $cardID => $quantity) {
  //$cardImagePath = "./concat/" . $cardID . ".webp";
  $cardImagePath = __DIR__ . "/./jpg/concat/" . $cardID . ".jpg";
  if (file_exists($cardImagePath)) {
    //$cardImage = imagecreatefromwebp($cardImagePath);
    $cardImage = imagecreatefromjpeg($cardImagePath);
  } else {
    $cardImage = imagecreatetruecolor($spaceIconSize, $spaceIconSize);
    $placeholderColor = imagecolorallocate($cardImage, 200, 200, 200);
    imagefilledrectangle($cardImage, 0, 0, $spaceIconSize, $spaceIconSize, $placeholderColor);
  }
  
  $resizedCard = imagecreatetruecolor($spaceIconSize, $spaceIconSize);
  imagealphablending($resizedCard, false);
  imagesavealpha($resizedCard, true);
  imagecopyresampled(
    $resizedCard, $cardImage,
    0, 0, 0, 0,
    $spaceIconSize, $spaceIconSize,
    imagesx($cardImage), imagesy($cardImage)
  );
  imagedestroy($cardImage);
  
  // Place the resized card at the calculated position
  imagecopy($image, $resizedCard, $spaceX, $spaceY, 0, 0, $spaceIconSize, $spaceIconSize);
  
  // Add quantity indicator at the bottom middle of the card tile
  $qtyText = "x" . $quantity;
  $qtyFontSize = 14;
  // Set text color to white
  $qtyColor = imagecolorallocate($image, 255, 255, 255);
  // Generate bounding box for text
  $bbox = imagettfbbox($qtyFontSize, 0, $fontPath, $qtyText);
  $textWidth = $bbox[2] - $bbox[0];
  $textX = $spaceX + ($spaceIconSize - $textWidth) / 2;
  $textY = $spaceY + $spaceIconSize - 5; // 5 pixel margin from the bottom
  
  // Draw dark partially transparent background rectangle
  $bgColor = imagecolorallocatealpha($image, 0, 0, 0, 60);
  $bgPadding = 2;
  $x_min = min($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
  $x_max = max($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
  $y_min = min($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
  $y_max = max($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
  $rectX1 = $textX + $x_min - $bgPadding;
  $rectY1 = $textY + $y_min - $bgPadding;
  $rectX2 = $textX + $x_max + $bgPadding;
  $rectY2 = $textY + $y_max + $bgPadding;
  imagefilledrectangle($image, $rectX1, $rectY1, $rectX2, $rectY2, $bgColor);
  
  // Add the quantity text over the background
  imagettftext($image, $qtyFontSize, 0, $textX, $textY, $qtyColor, $fontPath, $qtyText);
  
  imagedestroy($resizedCard);
  
  // Move to the next column
  $colCount++;
  if ($colCount >= $columns) {
    // Reset to the first column and move down a row
    $colCount = 0;
    $spaceX = $spaceSpacing + 65;
    $spaceY += $spaceIconSize + $spaceSpacing;
  } else {
    $spaceX += $spaceIconSize + $spaceSpacing;
  }
}


//Ground units (placed starting from the right of the base and leader)
$groundIconSize = 100;
$groundSpacing = 10;

// Calculate the right edge of the base and leader images
$baseRight = $baseX + $baseNewWidth;
$leaderRight = $leaderX + $leaderNewWidth;
$startX = max($baseRight, $leaderRight) + $groundSpacing + 30;

// Starting coordinates for the grid
$groundX = $startX;
$groundY = 135; // Now matching the space units' starting Y position

// Calculate the number of columns that fit in the remaining space
$groundColumns = floor(($width - $groundSpacing - $startX) / ($groundIconSize + $groundSpacing));
if ($groundColumns < 1) {
  $groundColumns = 1;
}

$groundColCount = 0;

foreach ($groundUnits as $cardID => $quantity) {
  //$cardImagePath = "./concat/" . $cardID . ".webp";
  $cardImagePath = __DIR__ . "/./jpg/concat/" . $cardID . ".jpg";
  if (file_exists($cardImagePath)) {
    //$cardImage = imagecreatefromwebp($cardImagePath);
    $cardImage = imagecreatefromjpeg($cardImagePath);
  } else {
    $cardImage = imagecreatetruecolor($groundIconSize, $groundIconSize);
    $placeholderColor = imagecolorallocate($cardImage, 200, 200, 200);
    imagefilledrectangle($cardImage, 0, 0, $groundIconSize, $groundIconSize, $placeholderColor);
  }
  
  $resizedCard = imagecreatetruecolor($groundIconSize, $groundIconSize);
  imagealphablending($resizedCard, false);
  imagesavealpha($resizedCard, true);
  imagecopyresampled(
    $resizedCard, $cardImage,
    0, 0, 0, 0,
    $groundIconSize, $groundIconSize,
    imagesx($cardImage), imagesy($cardImage)
  );
  imagedestroy($cardImage);
  
  imagecopy($image, $resizedCard, $groundX, $groundY, 0, 0, $groundIconSize, $groundIconSize);
  
  // Add quantity indicator at the bottom middle of the card tile
  $qtyText = "x" . $quantity;
  $qtyFontSize = 14;
  // Set text color to white
  $qtyColor = imagecolorallocate($image, 255, 255, 255);
  $bbox = imagettfbbox($qtyFontSize, 0, $fontPath, $qtyText);
  $textWidth = $bbox[2] - $bbox[0];
  $textX = $groundX + ($groundIconSize - $textWidth) / 2;
  $textY = $groundY + $groundIconSize - 5;
  
  // Draw dark partially transparent background rectangle
  $bgColor = imagecolorallocatealpha($image, 0, 0, 0, 60);
  $bgPadding = 2;
  $x_min = min($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
  $x_max = max($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
  $y_min = min($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
  $y_max = max($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
  $rectX1 = $textX + $x_min - $bgPadding;
  $rectY1 = $textY + $y_min - $bgPadding;
  $rectX2 = $textX + $x_max + $bgPadding;
  $rectY2 = $textY + $y_max + $bgPadding;
  imagefilledrectangle($image, $rectX1, $rectY1, $rectX2, $rectY2, $bgColor);
  
  imagettftext($image, $qtyFontSize, 0, $textX, $textY, $qtyColor, $fontPath, $qtyText);
  
  imagedestroy($resizedCard);
  
  $groundColCount++;
  if ($groundColCount >= $groundColumns) {
    $groundColCount = 0;
    $groundX = $startX;
    $groundY += $groundIconSize + $groundSpacing;
  } else {
    $groundX += $groundIconSize + $groundSpacing;
  }
}


//Events and upgrades
$iconSize = 100;
$spacing = 10;
$x = $spacing;
$y = $height - $iconSize - $spacing - 20;
$totalIcons = count($eventsAndUpgrades);
$totalWidth = $totalIcons * $iconSize + ($totalIcons - 1) * $spacing;
$x = ($width - $totalWidth) / 2;

foreach ($eventsAndUpgrades as $cardID => $quantity) {
  
  // Build the path to the card image (adjust naming if required)
  //$cardImagePath = "./concat/" . $cardID . ".webp";
  $cardImagePath = __DIR__ . "/./jpg/concat/" . $cardID . ".jpg";
  
  if (file_exists($cardImagePath)) {
    //$cardImage = imagecreatefromwebp($cardImagePath);
    $cardImage = imagecreatefromjpeg($cardImagePath);
  } else {
    // Create a placeholder if the image doesn't exist
    $cardImage = imagecreatetruecolor($iconSize, $iconSize);
    $placeholderColor = imagecolorallocate($cardImage, 200, 200, 200);
    imagefilledrectangle($cardImage, 0, 0, $iconSize, $iconSize, $placeholderColor);
  }
  
  // Resize the card image to fit the icon size
  $resizedCard = imagecreatetruecolor($iconSize, $iconSize);
  imagecopyresampled(
    $resizedCard, $cardImage,
    0, 0, 0, 0,
    $iconSize, $iconSize,
    imagesx($cardImage), imagesy($cardImage)
  );
  imagedestroy($cardImage);
  
  // Place the resized image along the bottom of the main image
  imagecopy($image, $resizedCard, $x, $y, 0, 0, $iconSize, $iconSize);
  
  // Add quantity indicator at the bottom middle of the card tile
  $qtyText = "x" . $quantity;
  $qtyFontSize = 14;
  // Set text color to white
  $qtyColor = imagecolorallocate($image, 255, 255, 255);
  $bbox = imagettfbbox($qtyFontSize, 0, $fontPath, $qtyText);
  $textWidth = $bbox[2] - $bbox[0];
  $textX = $x + ($iconSize - $textWidth) / 2;
  $textY = $y + $iconSize - 5;
  
  // Draw dark partially transparent background rectangle
  $bgColor = imagecolorallocatealpha($image, 0, 0, 0, 60);
  $bgPadding = 2;
  $x_min = min($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
  $x_max = max($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
  $y_min = min($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
  $y_max = max($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
  $rectX1 = $textX + $x_min - $bgPadding;
  $rectY1 = $textY + $y_min - $bgPadding;
  $rectX2 = $textX + $x_max + $bgPadding;
  $rectY2 = $textY + $y_max + $bgPadding;
  imagefilledrectangle($image, $rectX1, $rectY1, $rectX2, $rectY2, $bgColor);
  
  imagettftext($image, $qtyFontSize, 0, $textX, $textY, $qtyColor, $fontPath, $qtyText);
  
  imagedestroy($resizedCard);
  
  $x += $iconSize + $spacing;
}

imagejpeg($image, $destFile);
imagedestroy($image);

if(!isset($fromBot)) {
  header("Content-Type: image/jpeg");
  readfile($destFile);
}

/*
imagewebp($image, './Games/' . $gameName . '/DeckImage.webp');
imagedestroy($image);

header("Content-Type: image/webp");
readfile('./Games/' . $gameName . '/DeckImage.webp');
*/
?>