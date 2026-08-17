<?php
// Generate a shareable image for a saved Hellbreak deck.
ob_start();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require_once __DIR__ . '/../Core/HTTPLibraries.php';
require_once __DIR__ . '/../Core/CoreZoneModifiers.php';
require_once __DIR__ . '/../Database/ConnectionManager.php';
require_once __DIR__ . '/../AccountFiles/AccountDatabaseAPI.php';
require_once __DIR__ . '/../HellbreakSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/GamestateParser.php';
require_once __DIR__ . '/ZoneAccessors.php';
require_once __DIR__ . '/ZoneClasses.php';
require_once __DIR__ . '/../Core/CardImageLoader.php';

$gameName = (string)TryGet('gameName', '');
if (!preg_match('/^\d+$/', $gameName)) {
  http_response_code(400); ob_end_clean(); echo 'Bad deck id'; exit;
}
$sort = strtolower(trim((string)TryGet('sort', 'cost')));
if (!in_array($sort, ['cost', 'aspect', 'type', 'name'], true)) $sort = 'cost';
$gamestate = __DIR__ . '/Games/' . $gameName . '/Gamestate.txt';
if (!is_file($gamestate)) {
  http_response_code(404); ob_end_clean(); echo 'Deck not found'; exit;
}
if (!function_exists('imagecreatetruecolor')) {
  http_response_code(500); ob_end_clean(); echo 'Image rendering is unavailable'; exit;
}

ParseGamestate(__DIR__ . '/');

$aggregate = function($zone) {
  $counts = [];
  foreach ($zone as $object) {
    $id = isset($object->CardID) ? (string)$object->CardID : '';
    if ($id === '' || $id === '-') continue;
    $counts[$id] = ($counts[$id] ?? 0) + 1;
  }
  return $counts;
};
$monster = $aggregate(GetMonster(1));
$locations = $aggregate(GetLocation(1));
$main = $aggregate(GetMainDeck(1));
$sideboard = $aggregate(GetSideboard(1));
if (!$monster && !$locations && !$main && !$sideboard) {
  http_response_code(404); ob_end_clean(); echo 'Deck is empty'; exit;
}

$asset = LoadAssetData(1, $gameName);
$title = trim((string)($asset['assetName'] ?? '')) ?: ('Hellbreak Deck #' . $gameName);
$nameOf = function($id) { $name = trim((string)CardName($id)); return $name !== '' ? $name : (string)$id; };
$costOf = function($id) { $cost = CardCost($id); return is_numeric($cost) ? intval($cost) : null; };
$typeOf = function($id) { $type = trim((string)CardType($id)); return $type !== '' ? $type : 'Other'; };
$aspectOf = function($id) { $aspect = trim((string)CardAspect($id)); return $aspect !== '' ? $aspect : 'Other'; };
$compare = function($a, $b) use ($sort, $nameOf, $costOf, $typeOf, $aspectOf) {
  if ($sort === 'type') { $cmp = strcasecmp($typeOf($a), $typeOf($b)); if ($cmp) return $cmp; }
  if ($sort === 'aspect') { $cmp = strcasecmp($aspectOf($a), $aspectOf($b)); if ($cmp) return $cmp; }
  if ($sort !== 'name') { $cmp = ($costOf($a) ?? PHP_INT_MAX) <=> ($costOf($b) ?? PHP_INT_MAX); if ($cmp) return $cmp; }
  $cmp = strcasecmp($nameOf($a), $nameOf($b));
  return $cmp ?: strcmp((string)$a, (string)$b);
};
$mainIDs = array_keys($main);
$sideIDs = array_keys($sideboard);
usort($mainIDs, $compare);
usort($sideIDs, $compare);
$group = function($ids) use ($sort, $costOf, $typeOf, $aspectOf) {
  if ($sort === 'name') return $ids ? ['A - Z' => $ids] : [];
  $groups = [];
  foreach ($ids as $id) {
    if ($sort === 'type') $label = $typeOf($id);
    elseif ($sort === 'aspect') $label = $aspectOf($id);
    else { $cost = $costOf($id); $label = $cost === null ? 'No cost' : 'Cost ' . $cost; }
    $groups[$label][] = $id;
  }
  return $groups;
};
$mainGroups = $group($mainIDs);
$sideGroups = $group($sideIDs);

// Hellbreak concat assets are square (450x450), unlike the portrait full-card images.
$W = 1440; $margin = 42; $cols = 8; $cardW = 151; $cardH = $cardW; $gap = 18; $rowGap = 42;
$headerH = 300; $footerH = 88;
$sectionHeight = function($groups) use ($cols, $cardH, $rowGap) {
  if (!$groups) return 0;
  $height = 64;
  foreach ($groups as $ids) $height += 34 + (int)ceil(count($ids) / $cols) * ($cardH + $rowGap);
  return $height;
};
$mainH = $sectionHeight($mainGroups);
$sideH = $sectionHeight($sideGroups);
$H = $headerH + $mainH + ($sideH ? 28 + $sideH : 0) + $footerH;
$img = imagecreatetruecolor($W, $H);
$bg = imagecolorallocate($img, 8, 21, 21); $panel = imagecolorallocate($img, 18, 39, 37);
$white = imagecolorallocate($img, 239, 234, 219); $muted = imagecolorallocate($img, 157, 181, 174);
$gold = imagecolorallocate($img, 190, 157, 92); $red = imagecolorallocate($img, 143, 48, 45);
$shadow = imagecolorallocatealpha($img, 0, 0, 0, 55); $badge = imagecolorallocate($img, 11, 25, 24);
imagefilledrectangle($img, 0, 0, $W, $H, $bg);
for ($y = 0; $y < $H; $y += 8) imageline($img, 0, $y, $W, $y, imagecolorallocatealpha($img, 255, 255, 255, 124));
$font = __DIR__ . '/../Assets/Montserrat.ttf';
$drawText = function($text, $size, $x, $baseline, $color) use ($img, $font) {
  if (is_file($font)) imagettftext($img, $size, 0, $x, $baseline, $color, $font, (string)$text);
  else imagestring($img, min(5, max(1, (int)round($size / 5))), $x, $baseline - $size, (string)$text, $color);
};
$fitText = function($text, $size, $maxWidth) use ($font) {
  if (!is_file($font)) return (string)$text;
  $text = (string)$text;
  while ($text !== '') {
    $box = imagettfbbox($size, 0, $font, $text);
    if (($box[2] - $box[0]) <= $maxWidth) return $text;
    $text = rtrim(substr($text, 0, -1));
  }
  return '';
};
$drawCard = function($id, $count, $x, $y) use ($img, $cardW, $cardH, $shadow, $badge, $white, $gold, $drawText, $fitText, $nameOf) {
  imagefilledrectangle($img, $x + 6, $y + 7, $x + $cardW + 6, $y + $cardH + 7, $shadow);
  // Match Azuki's normal path: decode WebP through the shared compatibility loader.
  // Hellbreak's pipeline also emits the same square composition as PNG, which is a
  // last-resort fallback when this host has none of the supported WebP decoders.
  $safeID = basename((string)$id);
  $webpPath = __DIR__ . '/../HellbreakSim/concat/' . $safeID . '.webp';
  $card = LoadCardImageAsGd($webpPath);
  if ($card === false) {
    $pngPath = __DIR__ . '/../HellbreakSim/crops/' . $safeID . '_cropped.png';
    $card = LoadCardImageAsGd($pngPath);
  }
  if ($card) {
    imagecopyresampled($img, $card, $x, $y, 0, 0, $cardW, $cardH, imagesx($card), imagesy($card));
    imagedestroy($card);
  } else {
    imagefilledrectangle($img, $x, $y, $x + $cardW, $y + $cardH, $badge);
    $drawText($fitText($nameOf($id), 11, $cardW - 16), 11, $x + 8, $y + 30, $white);
  }
  imagefilledellipse($img, $x + $cardW - 8, $y + 9, 38, 38, $badge);
  $drawText('x' . $count, 12, $x + $cardW - 23, $y + 14, $gold);
};

imagefilledrectangle($img, $margin, 36, $W - $margin, $headerH - 24, $panel);
$drawText('NORTH BEACH // DECK REGISTRY', 15, 72, 78, $gold);
$drawText($fitText($title, 34, 820), 34, 72, 132, $white);
$drawText(array_sum($main) . ' main deck cards' . ($sideboard ? '  //  ' . array_sum($sideboard) . ' sideboard' : ''), 15, 74, 168, $muted);
$featured = array_merge(array_keys($monster), array_keys($locations));
$fx = $W - 420;
foreach (array_slice($featured, 0, 2) as $index => $id) $drawCard($id, ($monster[$id] ?? $locations[$id] ?? 1), $fx + $index * 170, 58);
if (!$featured) $drawText('NO MONSTER / LOCATION SELECTED', 13, $W - 390, 150, $muted);

$drawSection = function($label, $groups, $counts, $startY) use ($img, $margin, $W, $cols, $cardW, $cardH, $gap, $rowGap, $panel, $gold, $muted, $drawText, $drawCard) {
  if (!$groups) return $startY;
  imagefilledrectangle($img, $margin, $startY, $W - $margin, $startY + 48, $panel);
  $drawText($label . '  //  ' . array_sum($counts) . ' CARDS', 18, $margin + 20, $startY + 32, $gold);
  $y = $startY + 62;
  foreach ($groups as $groupLabel => $ids) {
    $drawText(strtoupper((string)$groupLabel), 12, $margin + 4, $y + 16, $muted);
    $y += 28;
    foreach (array_values($ids) as $index => $id) {
      $col = $index % $cols; $row = intdiv($index, $cols);
      $drawCard($id, $counts[$id], $margin + 4 + $col * ($cardW + $gap), $y + $row * ($cardH + $rowGap));
    }
    $y += (int)ceil(count($ids) / $cols) * ($cardH + $rowGap) + 6;
  }
  return $y;
};
$y = $headerH;
$y = $drawSection('MAIN DECK', $mainGroups, $main, $y);
if ($sideGroups) $y = $drawSection('SIDEBOARD', $sideGroups, $sideboard, $y + 28);
$drawText('northbeach.gg  //  Hellbreak fan project', 13, $margin, $H - 34, $muted);
$drawText('Deck #' . $gameName, 13, $W - 180, $H - 34, $gold);

// Match Azuki's production-safe output path: GD always creates a PNG intermediate,
// then Imagick converts it to WebP when that extension is available.
ob_start();
imagepng($img);
$pngBlob = ob_get_clean();
imagedestroy($img);

ob_end_clean();
try {
  $imagick = new \Imagick();
  $imagick->readImageBlob($pngBlob);
  $imagick->setImageFormat('webp');
  $imagick->setImageCompressionQuality(88);
  $webpBlob = $imagick->getImageBlob();
  $imagick->clear();
  $imagick->destroy();
  header('Content-Type: image/webp');
  header('Content-Disposition: inline; filename="hellbreak-deck-' . $gameName . '.webp"');
  header('Cache-Control: no-store');
  echo $webpBlob;
} catch (\Throwable $error) {
  header('Content-Type: image/png');
  header('Content-Disposition: inline; filename="hellbreak-deck-' . $gameName . '.png"');
  header('Cache-Control: no-store');
  echo $pngBlob;
}
exit;
