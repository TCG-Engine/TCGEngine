<?php
// Buffer all output so stray notices/warnings (e.g. PHP deprecations from the GD
// code below) can't corrupt the streamed image: we discard the buffer right before
// sending the image header. Without this, warnings emitted mid-render land in the
// response body ahead of header(), forcing Content-Type: text/html and a broken image.
ob_start();
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
include_once __DIR__ . '/lib/qr/QRRenderer.php';


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
// Note: deck-image generation is intentionally allowed for private decks too — a
// "private" deck should still produce an image (owners share/copy their own decks).
// Discord bots pass $fromBot and are unaffected.
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
$leaderIDs = [];
foreach ($arr as $c) { $leaderIDs[] = $c->CardID; }
$leaderID = count($leaderIDs) > 0 ? $leaderIDs[0] : ""; // first leader (kept for compat)
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
// ---------- Layout: [ leader(s)  base ] header, then Main Deck + Sideboard grids ----------
$cardW         = 150;                          // uniform card width (never scaled)
$cardTileRatio = 628 / 449;                    // full card portrait aspect (WebpImages 449x628)
$cardH         = (int) round($cardW * $cardTileRatio);
$gap           = 16;
$margin        = 50;
$cols          = 10;                           // fixed columns => fixed image width => uniform cards
$headerW       = 200;                          // leader/base width (kept modest so the main deck is the focus)
$titleFontSize = 54;
$labelFontSize = 30;
$labelH        = 52;                           // vertical space reserved for a section label
$qtyFontSize   = 14;
$fontPath      = __DIR__ . '/../Assets/Montserrat.ttf';
if ($ownerName == "RebelResource") $fontPath = __DIR__ . '/../Assets/Soloist.otf';

// ----- Sort helper (units + events + upgrades together) -----
$sortKey = strtolower(TryGet("sort", "cost"));
$buildRows = function ($qi) use ($sortKey) {
  $rows = [];
  foreach ($qi as $cardID => $qty) {
    $aspect = CardAspect($cardID);
    $rows[] = [
      'id'     => $cardID,
      'qty'    => $qty,
      'cost'   => (int) CardCost($cardID),
      'power'  => (int) CardPower($cardID),
      'set'    => (string) CardSet($cardID),
      'num'    => (int) CardCardNumber($cardID),
      'aspect' => is_array($aspect) ? implode(",", $aspect) : (string) $aspect,
      'name'   => (string) CardTitle($cardID),
    ];
  }
  usort($rows, function ($a, $b) use ($sortKey) {
    switch ($sortKey) {
      case 'setnum': return [$a['set'], $a['num'], $a['name']] <=> [$b['set'], $b['num'], $b['name']];
      case 'power':
        if ($a['power'] != $b['power']) return $b['power'] - $a['power']; // high -> low
        return [$a['cost'], $a['name']] <=> [$b['cost'], $b['name']];
      case 'aspect': return [$a['aspect'], $a['cost'], $a['name']] <=> [$b['aspect'], $b['cost'], $b['name']];
      case 'name':   return $a['name'] <=> $b['name'];
      case 'cost':
      default:       return [$a['cost'], $a['name']] <=> [$b['cost'], $b['name']];
    }
  });
  return $rows;
};
$mainRows = $buildRows($quantityIndex);
$sideRows = $buildRows($sideboardQuantityIndex);

// ----- Header images: leaders then base -----
$headerIDs = array_values(array_filter(array_merge($leaderIDs, [$baseID]), function ($v) { return $v !== ""; }));
$headerImgs = [];
foreach ($headerIDs as $hid) {
  $p = __DIR__ . '/WebpImages/' . $hid . '.webp';
  $img = file_exists($p) ? imagecreatefromwebp($p) : false;
  if ($img === false) { $img = imagecreatetruecolor($headerW, $headerW); imagefilledrectangle($img, 0, 0, $headerW, $headerW, imagecolorallocate($img, 200, 200, 200)); }
  $ow = imagesx($img); $oh = imagesy($img);
  $nh = (int) round($headerW * $oh / $ow);
  $headerImgs[] = ['img' => $img, 'ow' => $ow, 'oh' => $oh, 'nw' => $headerW, 'nh' => $nh];
}
$headerH = 0; $headerTotalW = 0;
foreach ($headerImgs as $h) { $headerH = max($headerH, $h['nh']); $headerTotalW += $h['nw']; }
$headerTotalW += $gap * max(0, count($headerImgs) - 1);

// ----- Vertical layout (fixed width; height grows with the grids) -----
$gridW     = $cols * $cardW + ($cols - 1) * $gap;
$width     = $gridW + 2 * $margin;
$rowH      = $cardH + $gap;
$titleTop  = 110;
$headerTop = 170;

$mainRowsN = (int) ceil(max(0, count($mainRows)) / $cols);
$sideRowsN = count($sideRows) > 0 ? (int) ceil(count($sideRows) / $cols) : 0;

$mainLabelTop = $headerTop + $headerH + $gap * 2;
$mainGridTop  = $mainLabelTop + $labelH;
$mainBottom   = $mainGridTop + $mainRowsN * $rowH;
if ($sideRowsN > 0) {
  $sideLabelTop = $mainBottom + $gap;
  $sideGridTop  = $sideLabelTop + $labelH;
  $contentBottom = $sideGridTop + $sideRowsN * $rowH;
} else {
  $contentBottom = $mainBottom;
}

// ----- QR code prep (encodes the deck's friendly share link; drawn bottom-right in a footer) -----
// Built before the canvas so we can reserve a footer band and the QR never overlaps the last card
// row. Any failure (no friendly code, encoder error) degrades gracefully: no QR, image still renders.
$qrImg = null; $qrW = $qrH = 0; $qrPanelW = $qrPanelH = 0;
$qrPad = 16; $qrCaptionH = 26; $qrCaption = "swustats.net";
try {
  $friendlyCode = function_exists("AssignFriendlyCode") ? AssignFriendlyCode(1, (int) $gameName) : null;
  if (!empty($friendlyCode)) {
    $qrLink = "https://swustats.net/deck/" . $friendlyCode;
    $qrImg  = RenderQR($qrLink, 200, 3); // GD image: black modules on white, quiet zone baked in
    $qrW = imagesx($qrImg); $qrH = imagesy($qrImg);
    $qrPanelW = $qrW + 2 * $qrPad;
    $qrPanelH = $qrH + 2 * $qrPad + $qrCaptionH;
  }
} catch (\Throwable $e) {
  if (is_resource($qrImg) || $qrImg instanceof \GdImage) { imagedestroy($qrImg); }
  $qrImg = null; $qrPanelW = $qrPanelH = 0;
}

$footerH = $qrImg ? ($qrPanelH + $gap) : 0;
$height = $contentBottom + $footerH + $margin;

// ----- Canvas + background (space starfield, matching the main menu's gamebg) -----
$image = imagecreatetruecolor($width, $height);
// Solid dark fill first: matches the darkest tone of gamebg, and shows through if the
// asset is missing/unreadable so text contrast is preserved either way.
imagefilledrectangle($image, 0, 0, $width, $height, imagecolorallocate($image, 10, 20, 40));
$bgPath = __DIR__ . '/../Assets/Images/gamebg.jpg';
if (file_exists($bgPath) && ($bg = @imagecreatefromjpeg($bgPath)) !== false) {
  // COVER-fit (same as the menu's `background-size: cover; background-position: center;`):
  // scale the source so it fills the whole canvas, then center-crop the overflow.
  $bw = imagesx($bg); $bh = imagesy($bg);
  $scale = max($width / $bw, $height / $bh);
  $sw = (int) round($width / $scale);   // width of the source rect to sample
  $sh = (int) round($height / $scale);  // height of the source rect to sample
  $sx = (int) round(($bw - $sw) / 2);   // center horizontally
  $sy = (int) round(($bh - $sh) / 2);   // center vertically
  imagecopyresampled($image, $bg, 0, 0, $sx, $sy, $width, $height, $sw, $sh);
  imagedestroy($bg);
}
$white = imagecolorallocate($image, 255, 255, 255);

// ----- Title (auto-shrink to fit width) -----
$text = ($ownerName == "RebelResource") ? (string) $assetName : ($assetName . " by " . $ownerName);
$maxTitleW = $width - 2 * $margin;
$tfs = $titleFontSize;
$bbox = imagettfbbox($tfs, 0, $fontPath, $text);
while (($bbox[2] - $bbox[0]) > $maxTitleW && $tfs > 18) { $tfs -= 2; $bbox = imagettfbbox($tfs, 0, $fontPath, $text); }
imagettftext($image, $tfs, 0, ($width - ($bbox[2] - $bbox[0])) / 2, $titleTop, $white, $fontPath, $text);

// ----- Header row: leaders + base, centered, bottom-aligned -----
$hx = ($width - $headerTotalW) / 2;
foreach ($headerImgs as $h) {
  $resized = imagecreatetruecolor($h['nw'], $h['nh']);
  imagealphablending($resized, false); imagesavealpha($resized, true);
  imagecopyresampled($resized, $h['img'], 0, 0, 0, 0, $h['nw'], $h['nh'], $h['ow'], $h['oh']);
  imagedestroy($h['img']);
  $hy = $headerTop + ($headerH - $h['nh']); // bottom-align across the header row
  imagecopy($image, $resized, (int) $hx, (int) $hy, 0, 0, $h['nw'], $h['nh']);
  imagedestroy($resized);
  $hx += $h['nw'] + $gap;
}

// ----- Section label + grid drawers -----
$drawLabel = function ($labelText, $topY) use (&$image, $fontPath, $labelFontSize, $margin, $white) {
  imagettftext($image, $labelFontSize, 0, $margin, $topY + $labelFontSize, $white, $fontPath, $labelText);
};
$drawGrid = function ($rows, $gy0) use (&$image, $cardW, $cardH, $cols, $gap, $margin, $fontPath, $qtyFontSize, $white) {
  $col = 0; $gx = $margin; $gy = $gy0;
  foreach ($rows as $r) {
    $p = __DIR__ . '/WebpImages/' . $r['id'] . '.webp';
    $card = file_exists($p) ? imagecreatefromwebp($p) : false;
    if ($card === false) { $card = imagecreatetruecolor($cardW, $cardH); imagefilledrectangle($card, 0, 0, $cardW, $cardH, imagecolorallocate($card, 200, 200, 200)); }
    $resized = imagecreatetruecolor($cardW, $cardH);
    imagealphablending($resized, false); imagesavealpha($resized, true);
    imagecopyresampled($resized, $card, 0, 0, 0, 0, $cardW, $cardH, imagesx($card), imagesy($card));
    imagedestroy($card);
    imagecopy($image, $resized, $gx, $gy, 0, 0, $cardW, $cardH);
    imagedestroy($resized);

    $qtyText = "x" . $r['qty'];
    $bb = imagettfbbox($qtyFontSize, 0, $fontPath, $qtyText);
    $tw = $bb[2] - $bb[0];
    $tx = $gx + ($cardW - $tw) / 2;
    $ty = $gy + $cardH - 8;
    imagefilledrectangle($image, $tx - 3, $ty + $bb[7] - 2, $tx + $tw + 3, $ty + $bb[1] + 2, imagecolorallocatealpha($image, 0, 0, 0, 60));
    imagettftext($image, $qtyFontSize, 0, $tx, $ty, $white, $fontPath, $qtyText);

    $col++;
    if ($col >= $cols) { $col = 0; $gx = $margin; $gy += $cardH + $gap; }
    else { $gx += $cardW + $gap; }
  }
};

// ----- Main Deck -----
$drawLabel("Main Deck", $mainLabelTop);
$drawGrid($mainRows, $mainGridTop);

// ----- Sideboard -----
if ($sideRowsN > 0) {
  $drawLabel("Sideboard", $sideLabelTop);
  $drawGrid($sideRows, $sideGridTop);
}


// ----- QR panel: white rounded quiet-zone panel, bottom-right in the reserved footer band -----
if ($qrImg) {
  $panelX = $width - $margin - $qrPanelW;
  $panelY = $contentBottom + $gap;
  $panelBg = imagecolorallocate($image, 255, 255, 255);
  $panelInk = imagecolorallocate($image, 20, 30, 55); // caption ink (dark, on white)
  // Rounded rectangle via straight fills + corner discs (GD has no native rounded rect).
  $rad = 14;
  imagefilledrectangle($image, $panelX + $rad, $panelY, $panelX + $qrPanelW - $rad, $panelY + $qrPanelH, $panelBg);
  imagefilledrectangle($image, $panelX, $panelY + $rad, $panelX + $qrPanelW, $panelY + $qrPanelH - $rad, $panelBg);
  imagefilledellipse($image, $panelX + $rad, $panelY + $rad, 2 * $rad, 2 * $rad, $panelBg);
  imagefilledellipse($image, $panelX + $qrPanelW - $rad, $panelY + $rad, 2 * $rad, 2 * $rad, $panelBg);
  imagefilledellipse($image, $panelX + $rad, $panelY + $qrPanelH - $rad, 2 * $rad, 2 * $rad, $panelBg);
  imagefilledellipse($image, $panelX + $qrPanelW - $rad, $panelY + $qrPanelH - $rad, 2 * $rad, 2 * $rad, $panelBg);
  // QR (centered horizontally in the panel), then the swustats.net wordmark beneath it.
  $qx = $panelX + (int) (($qrPanelW - $qrW) / 2);
  $qy = $panelY + $qrPad;
  imagecopy($image, $qrImg, $qx, $qy, 0, 0, $qrW, $qrH);
  imagedestroy($qrImg);
  $capFs = 13;
  $cbb = imagettfbbox($capFs, 0, $fontPath, $qrCaption);
  $cw = $cbb[2] - $cbb[0];
  $cx = $panelX + ($qrPanelW - $cw) / 2;
  $cy = $qy + $qrH + $qrCaptionH - 6;
  imagettftext($image, $capFs, 0, $cx, $cy, $panelInk, $fontPath, $qrCaption);
}

imagejpeg($image, $destFile);
imagedestroy($image);

if(!isset($fromBot)) {
  // Discard any buffered notices/warnings so they don't precede the image bytes.
  while (ob_get_level()) { ob_end_clean(); }
  header("Content-Type: image/jpeg");
  header("Content-Length: " . filesize($destFile));
  readfile($destFile);
}

/*
imagewebp($image, './Games/' . $gameName . '/DeckImage.webp');
imagedestroy($image);

header("Content-Type: image/webp");
readfile('./Games/' . $gameName . '/DeckImage.webp');
*/
?>