<?php
// Generate a shareable deck image for an AzukiDeck deck, with a baked-in QR share link.
// Output buffering guards against stray warnings corrupting the streamed image bytes.
ob_start();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Core/HTTPLibraries.php';
require_once __DIR__ . '/../Core/CoreZoneModifiers.php';
require_once __DIR__ . '/../Database/ConnectionManager.php';
require_once __DIR__ . '/../AccountFiles/AccountDatabaseAPI.php';
require_once __DIR__ . '/../AzukiSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/GamestateParser.php';
require_once __DIR__ . '/ZoneAccessors.php';
require_once __DIR__ . '/ZoneClasses.php';
require_once __DIR__ . '/lib/CardImageLoader.php';
require_once __DIR__ . '/lib/qr/QRRenderer.php';

$gameName = TryGet('gameName', '');
if (!preg_match('/^\d+$/', (string)$gameName)) {
  http_response_code(400);
  ob_end_clean();
  echo 'Bad deck id';
  exit;
}
$sort = strtolower(trim((string)TryGet('sort', 'cost')));
if (!in_array($sort, ['cost', 'name', 'type', 'element'], true)) $sort = 'cost';

if (!is_file(__DIR__ . '/Games/' . $gameName . '/Gamestate.txt')) {
  http_response_code(404);
  ob_end_clean();
  echo 'Deck not found';
  exit;
}

ParseGamestate(__DIR__ . '/');

$aggregateZone = function($arr) {
  $counts = [];
  foreach ($arr as $obj) {
    if (!isset($obj)) continue;
    $cardID = isset($obj->CardID) ? (string)$obj->CardID : '';
    if ($cardID === '' || $cardID === '-') continue;
    $counts[$cardID] = ($counts[$cardID] ?? 0) + 1;
  }
  return $counts;
};

$leaderArr = &GetLeader(1);
$gateArr = &GetGate(1);
$leaderID = count($leaderArr) && isset($leaderArr[0]->CardID) ? (string)$leaderArr[0]->CardID : '';
$gateID = count($gateArr) && isset($gateArr[0]->CardID) ? (string)$gateArr[0]->CardID : '';
$mainCounts = $aggregateZone(GetMainDeck(1));

if ($leaderID === '' && $gateID === '' && empty($mainCounts)) {
  http_response_code(404);
  ob_end_clean();
  echo 'Deck is empty';
  exit;
}

$asset = LoadAssetData(1, $gameName);
$title = trim((string)($asset['assetName'] ?? '')) !== ''
  ? (string)$asset['assetName']
  : ('Azuki Deck #' . $gameName);

$cardName = function($cardID) {
  $name = trim((string)CardName($cardID));
  return $name !== '' ? $name : (string)$cardID;
};
$cardCost = function($cardID) {
  $cost = CardIkzCost($cardID);
  return is_numeric($cost) && intval($cost) >= 0 ? intval($cost) : null;
};
$cardType = function($cardID) {
  $type = trim((string)CardCategory($cardID));
  return $type !== '' ? $type : 'Other';
};
$cardElement = function($cardID) {
  $element = trim((string)CardElement($cardID));
  return $element !== '' ? $element : 'Other';
};
$cardCompare = function($a, $b) use ($sort, $cardName, $cardCost, $cardType, $cardElement) {
  if ($sort === 'type') {
    $typeOrder = ['Entity' => 0, 'Spell' => 1, 'Weapon' => 2];
    $aType = $cardType($a);
    $bType = $cardType($b);
    $typeCompare = ($typeOrder[$aType] ?? 99) <=> ($typeOrder[$bType] ?? 99);
    if ($typeCompare === 0) $typeCompare = strcasecmp($aType, $bType);
    if ($typeCompare !== 0) return $typeCompare;
  }
  if ($sort === 'element') {
    $elementOrder = ['Earth' => 0, 'Fire' => 1, 'Lightning' => 2, 'Water' => 3, 'Neutral' => 4];
    $aElement = $cardElement($a);
    $bElement = $cardElement($b);
    $elementCompare = ($elementOrder[$aElement] ?? 99) <=> ($elementOrder[$bElement] ?? 99);
    if ($elementCompare === 0) $elementCompare = strcasecmp($aElement, $bElement);
    if ($elementCompare !== 0) return $elementCompare;
  }
  if ($sort === 'cost' || $sort === 'type' || $sort === 'element') {
    $costCompare = ($cardCost($a) ?? PHP_INT_MAX) <=> ($cardCost($b) ?? PHP_INT_MAX);
    if ($costCompare !== 0) return $costCompare;
  }
  $nameCompare = strcasecmp($cardName($a), $cardName($b));
  return $nameCompare !== 0 ? $nameCompare : strcmp((string)$a, (string)$b);
};

$mainCards = array_keys($mainCounts);
usort($mainCards, $cardCompare);

$groupCards = function($cards) use ($sort, $cardCost, $cardType, $cardElement) {
  if ($sort === 'name') return empty($cards) ? [] : ['A - Z' => $cards];
  $groups = [];
  foreach ($cards as $cardID) {
    if ($sort === 'type') {
      $label = $cardType($cardID);
    } elseif ($sort === 'element') {
      $label = $cardElement($cardID);
    } else {
      $cost = $cardCost($cardID);
      $label = $cost === null ? 'Costless' : ('Cost ' . $cost);
    }
    if (!isset($groups[$label])) $groups[$label] = [];
    $groups[$label][] = $cardID;
  }
  return $groups;
};

$mainGroups = $groupCards($mainCards);

// Canvas measurements.
$W = 1440;
$margin = 40;
$cols = 8;
$cardW = 153;
$cardH = 214;
$cardGap = 16;
$rowGap = 20;
$headerH = 260;
$footerH = 160;
$sectionGap = 20;

$sectionHeight = function($groups) use ($cols, $cardH, $rowGap, $sort) {
  if (empty($groups)) return 0;
  $cardCount = 0;
  foreach ($groups as $cards) $cardCount += count($cards);
  $rows = max(1, (int)ceil($cardCount / $cols));
  $groupBarSpace = $sort === 'name' ? 0 : 26;
  return 72 + $rows * ($cardH + $rowGap + $groupBarSpace) + 12;
};

$mainSectionH = $sectionHeight($mainGroups);
$H = $headerH + $sectionGap + $mainSectionH + $sectionGap + $footerH;

$img = imagecreatetruecolor($W, $H);
imagealphablending($img, true);
$bg = imagecolorallocate($img, 7, 17, 30);
imagefilledrectangle($img, 0, 0, $W, $H, $bg);
$white = imagecolorallocate($img, 242, 247, 255);
$muted = imagecolorallocate($img, 157, 176, 197);
$grey = imagecolorallocate($img, 83, 101, 120);
$gold = imagecolorallocate($img, 221, 174, 80);
$cyan = imagecolorallocate($img, 82, 190, 229);
$costBar = imagecolorallocate($img, 31, 91, 122);
$panel = imagecolorallocatealpha($img, 14, 31, 49, 18);
$panelStrong = imagecolorallocatealpha($img, 20, 43, 65, 8);
$line = imagecolorallocatealpha($img, 105, 174, 210, 58);
$shadow = imagecolorallocatealpha($img, 0, 0, 0, 62);
$badge = imagecolorallocatealpha($img, 7, 17, 28, 8);

$bgPath = __DIR__ . '/../Assets/Backgrounds/bg_radial_emblem.png';
if (is_file($bgPath)) {
  $bgImg = @imagecreatefrompng($bgPath);
  if ($bgImg) {
    $bw = imagesx($bgImg);
    $bh = imagesy($bgImg);
    $scale = max($W / $bw, $H / $bh);
    $dw = (int)($bw * $scale);
    $dh = (int)($bh * $scale);
    imagecopyresampled(
      $img,
      $bgImg,
      (int)(($W - $dw) / 2),
      (int)(($H - $dh) / 2),
      0,
      0,
      $dw,
      $dh,
      $bw,
      $bh
    );
    imagedestroy($bgImg);
  }
}

$font = __DIR__ . '/../Assets/Montserrat.ttf';
$haveFont = is_file($font);

$roundedRect = function($x1, $y1, $x2, $y2, $radius, $color) use (&$img) {
  $radius = max(1, min($radius, (int)(($x2 - $x1) / 2), (int)(($y2 - $y1) / 2)));
  imagefilledrectangle($img, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
  imagefilledrectangle($img, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
  imagefilledellipse($img, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
  imagefilledellipse($img, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
  imagefilledellipse($img, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
  imagefilledellipse($img, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
};
$drawText = function($text, $size, $x, $baseline, $color) use (&$img, $haveFont, $font) {
  if ($haveFont) {
    imagettftext($img, $size, 0, $x, $baseline, $color, $font, (string)$text);
  } else {
    imagestring(
      $img,
      min(5, max(1, (int)round($size / 5))),
      $x,
      $baseline - $size,
      (string)$text,
      $color
    );
  }
};
$fitText = function($text, $size, $maxWidth) use ($haveFont, $font) {
  $text = trim((string)$text);
  if (!$haveFont || $text === '') return $text;
  $fits = function($candidate) use ($font, $size, $maxWidth) {
    $box = imagettfbbox($size, 0, $font, $candidate);
    return $box && abs($box[2] - $box[0]) <= $maxWidth;
  };
  if ($fits($text)) return $text;
  while (mb_strlen($text) > 1 && !$fits($text . '...')) {
    $text = mb_substr($text, 0, -1);
  }
  return rtrim($text) . '...';
};
$drawCard = function($cardID, $x, $y, $w, $h) use (&$img, $grey) {
  $path = __DIR__ . '/../AzukiSim/WebpImages/' . $cardID . '.webp';
  $card = LoadCardImageAsGd($path);
  if ($card) {
    imagecopyresampled($img, $card, $x, $y, 0, 0, $w, $h, imagesx($card), imagesy($card));
    imagedestroy($card);
  } else {
    imagefilledrectangle($img, $x, $y, $x + $w, $y + $h, $grey);
  }
};

// Header.
$mainTotal = array_sum($mainCounts);
$roundedRect($margin, 28, $W - $margin, $headerH - 10, 18, $panelStrong);
imagefilledrectangle($img, $margin, 28, $margin + 6, $headerH - 10, $gold);
$drawText($fitText($title, 36, 780), 36, $margin + 28, 78, $white);
$summary = $mainTotal . ' MAIN  |  ' . count($mainCards) . ' UNIQUE';
$drawText($summary, 14, $margin + 30, 110, $muted);

$categoryCounts = [];
$costCounts = [];
foreach ($mainCounts as $cardID => $quantity) {
  $category = trim((string)CardCategory($cardID));
  if ($category === '') $category = 'Other';
  $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + $quantity;
  $cost = $cardCost($cardID);
  $costKey = $cost === null ? '-' : (string)$cost;
  $costCounts[$costKey] = ($costCounts[$costKey] ?? 0) + $quantity;
}
uksort($costCounts, function($a, $b) {
  return ($a === '-' ? PHP_INT_MAX : intval($a)) <=> ($b === '-' ? PHP_INT_MAX : intval($b));
});

$curveX = $margin + 30;
$curveY = 136;
$curveW = 690;
$curveH = 48;
$drawText('COST CURVE', 12, $curveX, $curveY, $cyan);
$maxCostCount = max(1, empty($costCounts) ? 1 : max($costCounts));
$barAreaY = $curveY + 12;
$barGap = 8;
$barCount = max(1, count($costCounts));
$barW = min(62, (int)(($curveW - ($barCount - 1) * $barGap) / $barCount));
$barX = $curveX;
foreach ($costCounts as $costLabel => $quantity) {
  $barH = max(4, (int)round(($quantity / $maxCostCount) * $curveH));
  $roundedRect($barX, $barAreaY + $curveH - $barH, $barX + $barW, $barAreaY + $curveH, 5, $cyan);
  $drawText($costLabel, 10, $barX + 5, $barAreaY + $curveH + 16, $muted);
  $drawText($quantity . ' cards', 9, $barX + 5, $barAreaY + $curveH + 32, $white);
  $barX += $barW + $barGap;
}

$categoryParts = [];
foreach ($categoryCounts as $category => $quantity) {
  $categoryParts[] = strtoupper($category) . ' ' . $quantity;
}
$drawText(implode('   |   ', $categoryParts), 11, $margin + 770, 218, $muted);

$identityW = 104;
$identityH = 146;
$identityGap = 16;
$identityX = $W - $margin - ($identityW * 2 + $identityGap) - 20;
$identityY = 72;
foreach ([['LEADER', $leaderID], ['GATE', $gateID]] as $identityIndex => $identity) {
  [$identityLabel, $identityCardID] = $identity;
  $x = $identityX + $identityIndex * ($identityW + $identityGap);
  $drawText($identityLabel, 10, $x, 57, $gold);
  $roundedRect($x + 4, $identityY + 5, $x + $identityW + 4, $identityY + $identityH + 5, 7, $shadow);
  if ($identityCardID !== '') {
    $drawCard($identityCardID, $x, $identityY, $identityW, $identityH);
  } else {
    $roundedRect($x, $identityY, $x + $identityW, $identityY + $identityH, 7, $grey);
  }
}

$drawSection = function($label, $groups, $counts, $sectionY, $sectionH)
  use (
    &$img,
    $margin,
    $W,
    $cols,
    $cardW,
    $cardH,
    $cardGap,
    $rowGap,
    $drawCard,
    $drawText,
    $roundedRect,
    $panel,
    $line,
    $white,
    $muted,
    $gold,
    $costBar,
    $shadow,
    $badge,
    $sort
  ) {
    $roundedRect($margin, $sectionY, $W - $margin, $sectionY + $sectionH, 18, $panel);
    $total = array_sum($counts);
    $drawText($label, 20, $margin + 24, $sectionY + 36, $white);
    $drawText($total . ' CARDS  |  ' . count($counts) . ' UNIQUE', 11, $margin + 210, $sectionY + 34, $muted);
    imagefilledrectangle($img, $margin + 20, $sectionY + 50, $W - $margin - 20, $sectionY + 52, $line);
    $items = [];
    foreach ($groups as $groupLabel => $cards) {
      foreach ($cards as $cardID) $items[] = [$groupLabel, $cardID];
    }
    $gridY = $sectionY + 78;

    $segments = [];
    foreach ($items as $i => $item) {
      [$groupLabel] = $item;
      $row = intdiv($i, $cols);
      $col = $i % $cols;
      $lastSegmentIndex = count($segments) - 1;
      $continuesSegment = $lastSegmentIndex >= 0
        && $segments[$lastSegmentIndex]['row'] === $row
        && $segments[$lastSegmentIndex]['label'] === $groupLabel
        && $segments[$lastSegmentIndex]['endCol'] === $col - 1;
      if ($continuesSegment) {
        $segments[$lastSegmentIndex]['endCol'] = $col;
      } else {
        $segments[] = [
          'label' => $groupLabel,
          'row' => $row,
          'startCol' => $col,
          'endCol' => $col
        ];
      }
    }

    if ($sort !== 'name') {
      foreach ($segments as $segment) {
        $barX = $margin + 12 + $segment['startCol'] * ($cardW + $cardGap);
        $barRight = $margin + 12 + $segment['endCol'] * ($cardW + $cardGap) + $cardW;
        $barY = $gridY + $segment['row'] * ($cardH + $rowGap + 26) + 2;
        $roundedRect($barX, $barY, $barRight, $barY + 21, 7, $costBar);
        imagefilledrectangle($img, $barX, $barY, $barX + 5, $barY + 21, $gold);
        $drawText(strtoupper($segment['label']), 10, $barX + 12, $barY + 16, $white);
      }
    }

    $groupBarSpace = $sort === 'name' ? 0 : 26;
    foreach ($items as $i => $item) {
      [, $cardID] = $item;
      $col = $i % $cols;
      $row = intdiv($i, $cols);
      $x = $margin + 12 + $col * ($cardW + $cardGap);
      $y = $gridY + $groupBarSpace + $row * ($cardH + $rowGap + $groupBarSpace);
      $roundedRect($x + 4, $y + 6, $x + $cardW + 4, $y + $cardH + 6, 8, $shadow);
      $roundedRect($x - 2, $y - 2, $x + $cardW + 2, $y + $cardH + 2, 8, $gold);
      $drawCard($cardID, $x, $y, $cardW, $cardH);

      $quantity = intval($counts[$cardID] ?? 0);
      $badgeW = 42;
      $badgeH = 29;
      $badgeX = $x + $cardW - $badgeW - 5;
      $badgeY = $y + $cardH - $badgeH - 5;
      $roundedRect($badgeX, $badgeY, $badgeX + $badgeW, $badgeY + $badgeH, 10, $badge);
      $drawText('x' . $quantity, 12, $badgeX + 8, $badgeY + 20, $white);
    }
  };

$mainY = $headerH + $sectionGap;
$drawSection('MAIN DECK', $mainGroups, $mainCounts, $mainY, $mainSectionH);

// Compact share footer.
$footerY = $mainY + $mainSectionH + $sectionGap;
$roundedRect($margin, $footerY, $W - $margin, $footerY + $footerH - 12, 18, $panelStrong);
$drawText('ZENDO.GG', 18, $margin + 28, $footerY + 46, $gold);
$drawText('Scan to view, copy, or play this deck.', 15, $margin + 28, $footerY + 78, $white);
$drawText('Generated decklist  |  Azuki TCG', 11, $margin + 28, $footerY + 108, $muted);

try {
  $code = AssignFriendlyCode(1, $gameName);
  if (!empty($code)) {
    $qrLink = 'https://zendo.gg/deck/' . $code;
    $qr = RenderQR($qrLink, 112, 3);
    $qs = imagesx($qr);
    $qrPad = 10;
    $px = $W - $margin - $qs - $qrPad * 2 - 20;
    $py = $footerY + 8;
    $panelWhite = imagecolorallocate($img, 255, 255, 255);
    $roundedRect($px, $py, $px + $qs + $qrPad * 2, $py + $qs + $qrPad * 2, 8, $panelWhite);
    imagecopy($img, $qr, $px + $qrPad, $py + $qrPad, 0, 0, $qs, $qs);
    imagedestroy($qr);
    $drawText('zendo.gg/deck/' . $code, 12, $W - $margin - 500, $footerY + 77, $cyan);
  }
} catch (\Throwable $e) {
  error_log('AzukiDeck CreateImage QR failed: ' . $e->getMessage());
}

// Output as WebP via Imagick (GD imagewebp is fatal on production).
$gamesDir = __DIR__ . '/Games/' . $gameName;
if (!is_dir($gamesDir)) @mkdir($gamesDir, 0775, true);

ob_start();
imagepng($img);
$pngBlob = ob_get_clean();
imagedestroy($img);

ob_end_clean();
try {
  $im = new \Imagick();
  $im->readImageBlob($pngBlob);
  $im->setImageFormat('webp');
  $im->setImageCompressionQuality(84);
  $webp = $im->getImageBlob();
  $im->clear();
  $im->destroy();
  @file_put_contents($gamesDir . '/DeckImage.webp', $webp);
  header('Content-Type: image/webp');
  header('Cache-Control: no-store');
  echo $webp;
} catch (\Throwable $e) {
  header('Content-Type: image/png');
  header('Cache-Control: no-store');
  echo $pngBlob;
}
exit;
