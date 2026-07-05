<?php
// GameLayoutDevice.php - phone vs desktop/tablet detection for GrandArchiveSim.
//
// Phones get the compact split board in GameLayoutMobile.php. Tablets keep the
// desktop board because the existing Grand Archive layout has enough room there.
//
// Manual override for local testing:
//   ?gaLayout=mobile
//   ?gaLayout=desktop

if (!function_exists('GrandArchiveSimIsMobileRequest')) {
  function GrandArchiveSimIsMobileRequest() {
    if (isset($_GET['gaLayout'])) return $_GET['gaLayout'] === 'mobile';
    if (isset($_GET['grandArchiveLayout'])) return $_GET['grandArchiveLayout'] === 'mobile';

    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if ($ua === '') return false;

    if (preg_match('/iPad/i', $ua)) return false;
    if (preg_match('/Android/i', $ua) && !preg_match('/Mobile/i', $ua)) return false;

    return (bool) preg_match('/iPhone|iPod|Android.*Mobile|Windows Phone|BlackBerry|webOS|Opera Mini|IEMobile/i', $ua);
  }
}
