<?php
// Generate a shareable deck image (WebP) for an AzukiDeck deck, with a baked-in QR share link.
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
if (!preg_match('/^\d+$/', (string)$gameName)) { http_response_code(400); ob_end_clean(); echo 'Bad deck id'; exit; }
$sort = TryGet('sort', 'cost');

if (!is_file(__DIR__ . '/Games/' . $gameName . '/Gamestate.txt')) { http_response_code(404); ob_end_clean(); echo 'Deck not found'; exit; }

// Load the deck through the engine (mirrors SWUDeck/CreateImage), so main deck AND sideboard
// come from the canonical zone accessors rather than positional file parsing.
ParseGamestate(__DIR__ . '/');

// Aggregate a zone into [cardID => qty], preserving first-seen order; skips blanks/placeholders.
$aggregateZone = function ($arr) {
  $counts = [];
  foreach ($arr as $obj) {
    if (!isset($obj)) continue;
    $cid = isset($obj->CardID) ? (string)$obj->CardID : '';
    if ($cid === '' || $cid === '-') continue;
    $counts[$cid] = ($counts[$cid] ?? 0) + 1;
  }
  return $counts;
};

$leaderArr = &GetLeader(1);
$gateArr   = &GetGate(1);
$leaderID  = (count($leaderArr) && isset($leaderArr[0]->CardID)) ? (string)$leaderArr[0]->CardID : '';
$gateID    = (count($gateArr)   && isset($gateArr[0]->CardID))   ? (string)$gateArr[0]->CardID   : '';
$mainCounts = $aggregateZone(GetMainDeck(1));
$sideCounts = $aggregateZone(GetSideboard(1));

if ($leaderID === '' && $gateID === '' && empty($mainCounts)) {
  http_response_code(404); ob_end_clean(); echo 'Deck is empty'; exit;
}

$asset = LoadAssetData(1, $gameName);
$title = trim((string)($asset['assetName'] ?? '')) !== '' ? $asset['assetName'] : ('Azuki Deck #' . $gameName);

// Sort each grid. Default keeps deck order; 'name' sorts by card id.
$mainCards = array_keys($mainCounts);
$sideCards = array_keys($sideCounts);
if (strtolower((string)$sort) === 'name') {
  usort($mainCards, fn($a, $b) => strcmp($a, $b));
  usort($sideCards, fn($a, $b) => strcmp($a, $b));
}

// --- Canvas layout ---
$W = 1200;
$cols = 10;
$cardW = 110; $cardH = 154; $pad = 12; $rowH = $cardH + 28;
$headerH = 240; $footerH = 240; // header holds title + leader/gate art (ends ~210) + a "Main Deck" label band
$mainRows = (int)ceil(max(1, count($mainCards)) / $cols);
$gridMainH = $mainRows * $rowH + $pad;
$sideHeaderH = count($sideCards) ? 44 : 0;
$sideRows = count($sideCards) ? (int)ceil(count($sideCards) / $cols) : 0;
$gridSideH = $sideRows * $rowH + ($sideRows ? $pad : 0);
$H = $headerH + $gridMainH + $sideHeaderH + $gridSideH + $footerH;

$img = imagecreatetruecolor($W, $H);
$bg = imagecolorallocate($img, 8, 19, 33); // #081321 — AzukiSim site navy
imagefilledrectangle($img, 0, 0, $W, $H, $bg);
$white = imagecolorallocate($img, 238, 244, 255);
$grey  = imagecolorallocate($img, 120, 140, 160);

// Radial emblem overlay (cover-fit, centered) composited over the navy — mirrors the AzukiSim
// site background (bg_radial_emblem.png over #081321). PNG with alpha, so blend it onto the fill.
$bgPath = __DIR__ . '/../Assets/Backgrounds/bg_radial_emblem.png';
if (is_file($bgPath)) {
  $bgImg = @imagecreatefrompng($bgPath);
  if ($bgImg) {
    imagealphablending($img, true); // blend the emblem's transparency over the navy
    $bw = imagesx($bgImg); $bh = imagesy($bgImg);
    $scale = max($W / $bw, $H / $bh);
    $dw = (int)($bw * $scale); $dh = (int)($bh * $scale);
    imagecopyresampled($img, $bgImg, (int)(($W - $dw) / 2), (int)(($H - $dh) / 2), 0, 0, $dw, $dh, $bw, $bh);
    imagedestroy($bgImg);
  }
}

$font = __DIR__ . '/../Assets/Montserrat.ttf';
$haveFont = is_file($font);

// --- Title ---
if ($haveFont) imagettftext($img, 34, 0, 24, 52, $white, $font, $title);
else imagestring($img, 5, 24, 30, $title, $white);

// --- Card drawer (WebP art via Imagick-backed loader; gray placeholder on miss) ---
$drawCard = function($cardID, $x, $y, $w, $h) use ($img, $grey) {
  $path = __DIR__ . '/../AzukiSim/WebpImages/' . $cardID . '.webp';
  $card = LoadCardImageAsGd($path);
  if ($card) {
    imagecopyresampled($img, $card, $x, $y, 0, 0, $w, $h, imagesx($card), imagesy($card));
    imagedestroy($card);
  } else {
    imagefilledrectangle($img, $x, $y, $x + $w, $y + $h, $grey);
  }
};

// --- Header: leader + gate art ---
$hx = 24; $hy = 80;
if ($leaderID !== '') { $drawCard($leaderID, $hx, $hy, 100, 140); $hx += 116; }
if ($gateID !== '')   { $drawCard($gateID,   $hx, $hy, 100, 140); }

// --- Grid drawer (a card grid with xN quantity labels), starting at $gy0 ---
$drawGrid = function($cards, $counts, $gy0) use (&$img, $drawCard, $cols, $cardW, $cardH, $pad, $rowH, $haveFont, $font, $white) {
  $i = 0;
  foreach ($cards as $cardID) {
    $col = $i % $cols; $row = intdiv($i, $cols);
    $x = 24 + $col * ($cardW + $pad);
    $y = $gy0 + $row * $rowH;
    $drawCard($cardID, $x, $y, $cardW, $cardH);
    $label = 'x' . $counts[$cardID];
    if ($haveFont) imagettftext($img, 13, 0, $x + 4, $y + $cardH + 20, $white, $font, $label);
    else imagestring($img, 3, $x + 4, $y + $cardH + 6, $label, $white);
    $i++;
  }
};
$onegap = 16;
// --- Main deck grid (with section label) ---
if ($haveFont) imagettftext($img, 22, 0, 24, $headerH + $onegap, $white, $font, 'Main Deck');
else imagestring($img, 5, 24, $headerH, 'Main Deck', $white);
$drawGrid($mainCards, $mainCounts, $headerH + 2*$onegap);

// --- Sideboard grid (only if present) ---
if (count($sideCards)) {
  $sideLabelY = $headerH + $gridMainH + 2*$onegap;
  if ($haveFont) imagettftext($img, 22, 0, 24, $sideLabelY + $onegap, $white, $font, 'Sideboard');
  else imagestring($img, 5, 24, $sideLabelY + 12, 'Sideboard', $white);
  $drawGrid($sideCards, $sideCounts, $sideLabelY + $sideHeaderH);
}

// --- QR footer (degrade gracefully on any failure) ---
try {
  $code = AssignFriendlyCode(1, $gameName);
  if (!empty($code)) {
    $qrLink = 'https://zendo.gg/deck/' . $code;
    $qr = RenderQR($qrLink, 190, 3);
    $qs = imagesx($qr);
    $panel = 18;
    $px = $W - $qs - $panel * 2 - 12;
    $py = $H - $qs - $panel * 2 - 12;
    $panelWhite = imagecolorallocate($img, 255, 255, 255);
    imagefilledrectangle($img, $px, $py, $px + $qs + $panel * 2, $py + $qs + $panel * 2, $panelWhite);
    imagecopy($img, $qr, $px + $panel, $py + $panel, 0, 0, $qs, $qs);
    imagedestroy($qr);
    $capBlack = imagecolorallocate($img, 20, 20, 20);
    if ($haveFont) imagettftext($img, 12, 0, $px + $panel, $py + $qs + $panel + 0.5*$onegap, $capBlack, $font, 'zendo.gg');
    else imagestring($img, 3, $px + $panel, $py + $qs + $panel, 'zendo.gg', $capBlack);
  }
} catch (\Throwable $e) {
  error_log('AzukiDeck CreateImage QR failed: ' . $e->getMessage());
}

// --- Output as WebP via Imagick (GD imagewebp is fatal on prod) ---
$gamesDir = __DIR__ . '/Games/' . $gameName;
if (!is_dir($gamesDir)) @mkdir($gamesDir, 0775, true);

ob_start();
imagepng($img);          // GD -> PNG blob (PNG encode is always available)
$pngBlob = ob_get_clean();
imagedestroy($img);

ob_end_clean();          // discard the outer warning-guard buffer before streaming bytes
try {
  $im = new \Imagick();
  $im->readImageBlob($pngBlob);
  $im->setImageFormat('webp');
  $im->setImageCompressionQuality(82);
  $webp = $im->getImageBlob();
  $im->clear(); $im->destroy();
  @file_put_contents($gamesDir . '/DeckImage.webp', $webp);
  header('Content-Type: image/webp');
  header('Cache-Control: no-store');
  echo $webp;
} catch (\Throwable $e) {
  // Last-resort fallback: serve PNG (still valid image/*).
  header('Content-Type: image/png');
  header('Cache-Control: no-store');
  echo $pngBlob;
}
exit;
