<?php
// zzProfileImage.php — TEMPORARY diagnostic. Times the phases of deck-image generation to find the
// prod slowdown (local native-GD is instant; prod decodes WebP via Imagick). Prints a plain-text
// phase breakdown; does NOT emit an image. Safe to delete after profiling.
//
//   curl "http://localhost/TCGEngine/SWUDeck/zzProfileImage.php?gameName=<deckID>"
//
// Reports: DB/asset load, ParseGamestate, friendly-code assign, QR render, and the per-card WebP
// decode loop (total + average + slowest), plus a head-to-head of one card via native GD vs Imagick
// vs dwebp so we can see the true per-decode cost of each backend on THIS box.

header("Content-Type: text/plain");
$T0 = microtime(true);
function ms($a, $b) { return number_format(($b - $a) * 1000, 1) . " ms"; }

require_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/GamestateParser.php';
include_once __DIR__ . '/ZoneAccessors.php';
include_once __DIR__ . '/ZoneClasses.php';
include_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/../Core/HTTPLibraries.php';
include_once __DIR__ . '/../Database/ConnectionManager.php';
include_once __DIR__ . '/../AccountFiles/AccountDatabaseAPI.php';
include_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/lib/qr/QRRenderer.php';
include_once __DIR__ . '/lib/CardImageLoader.php';
$T_includes = microtime(true);

$gameName = TryGet("gameName", "");
if ($gameName === "") { echo "provide ?gameName=<deckID>\n"; exit; }

echo "=== Deck-image generation profile: gameName={$gameName} ===\n\n";
echo "Backends available on this box:\n";
echo "  GD native WebP (imagecreatefromwebp): " . (function_exists('imagecreatefromwebp') ? "YES" : "no") . "\n";
echo "  Imagick extension:                    " . (extension_loaded('imagick') ? "YES" : "no") . "\n";
$dwebp = trim((string) @shell_exec('command -v dwebp 2>/dev/null'));
echo "  dwebp CLI:                            " . ($dwebp !== '' ? "YES ($dwebp)" : "no") . "\n\n";

$t = microtime(true);
$assetData = LoadAssetData(1, $gameName);
if ($assetData == null) { echo "asset not found\n"; exit; }
$T_asset = microtime(true);

ParseGamestate(__DIR__ . "/");
$T_parse = microtime(true);

// Collect every card id the real generator would draw (leaders + base + main + sideboard).
$ids = [];
foreach (GetLeader(1) as $c) { $ids[] = $c->CardID; }
foreach (GetBase(1) as $c) { $ids[] = $c->CardID; }
foreach (GetMainDeck(1) as $c) { $ids[] = $c->CardID; }
foreach (GetSideboard(1) as $c) { $ids[] = $c->CardID; }
$uniqueIds = array_values(array_unique($ids));
echo "Cards to draw: " . count($ids) . " total, " . count($uniqueIds) . " unique image files\n\n";

// Friendly code + QR (both new this session).
$T_beforeCode = microtime(true);
$code = AssignFriendlyCode(1, (int) $gameName);
$T_code = microtime(true);
$qrImg = null;
try { $qrImg = RenderQR("https://swustats.net/deck/" . $code, 200, 3); } catch (\Throwable $e) {}
$T_qr = microtime(true);
if ($qrImg) { imagedestroy($qrImg); }

// The suspected hot loop: decode every unique card image the way the generator does.
$decodeTimes = [];
$loopStart = microtime(true);
foreach ($uniqueIds as $id) {
  $p = __DIR__ . '/WebpImages/' . $id . '.webp';
  $ct = microtime(true);
  $img = LoadCardImageAsGd($p);
  $decodeTimes[$id] = (microtime(true) - $ct) * 1000;
  if ($img !== false) { imagedestroy($img); }
}
$loopEnd = microtime(true);
arsort($decodeTimes);
$avg = count($decodeTimes) ? array_sum($decodeTimes) / count($decodeTimes) : 0;

// Head-to-head on a single real card: native GD vs Imagick vs dwebp.
$sample = null;
foreach ($uniqueIds as $id) { $c = __DIR__ . '/WebpImages/' . $id . '.webp'; if (file_exists($c)) { $sample = $c; break; } }
echo "=== Phase timings ===\n";
echo "  includes/bootstrap : " . ms($T0, $T_includes) . "\n";
echo "  LoadAssetData (DB) : " . ms($T_includes, $T_asset) . "\n";
echo "  ParseGamestate     : " . ms($T_asset, $T_parse) . "\n";
echo "  AssignFriendlyCode : " . ms($T_beforeCode, $T_code) . "\n";
echo "  RenderQR           : " . ms($T_code, $T_qr) . "\n";
echo "  >>> CARD DECODE LOOP: " . ms($loopStart, $loopEnd) . "  (" . count($uniqueIds) . " files, avg " . number_format($avg, 1) . " ms/file)\n\n";

echo "Slowest 5 card decodes:\n";
$i = 0; foreach ($decodeTimes as $id => $mst) { echo "  " . number_format($mst, 1) . " ms  {$id}\n"; if (++$i >= 5) break; }
echo "\n";

if ($sample) {
  echo "=== Single-card backend comparison ($sample) ===\n";
  if (function_exists('imagecreatefromwebp')) {
    $a = microtime(true); $g = @imagecreatefromwebp($sample); $b = microtime(true);
    echo "  GD native  : " . ms($a, $b) . ($g !== false ? "" : "  (FAILED)") . "\n"; if ($g) imagedestroy($g);
  } else { echo "  GD native  : n/a (no webp in GD)\n"; }
  if (extension_loaded('imagick')) {
    $a = microtime(true);
    try { $im = new \Imagick(); $im->readImage($sample); $im->setImageFormat('png'); $blob = $im->getImageBlob(); $im->clear(); $im->destroy(); $g = @imagecreatefromstring($blob); }
    catch (\Throwable $e) { $g = false; }
    $b = microtime(true);
    echo "  Imagick    : " . ms($a, $b) . ($g !== false ? "" : "  (FAILED)") . "\n"; if ($g) imagedestroy($g);
  } else { echo "  Imagick    : n/a\n"; }
  if ($dwebp !== '') {
    $a = microtime(true); $png = @shell_exec(escapeshellarg($dwebp) . ' -quiet ' . escapeshellarg($sample) . ' -o - 2>/dev/null'); $g = $png ? @imagecreatefromstring($png) : false; $b = microtime(true);
    echo "  dwebp CLI  : " . ms($a, $b) . ($g !== false ? "" : "  (FAILED)") . "\n"; if ($g) imagedestroy($g);
  } else { echo "  dwebp CLI  : n/a\n"; }
}

echo "\nTotal profiled: " . ms($T0, microtime(true)) . "\n";
