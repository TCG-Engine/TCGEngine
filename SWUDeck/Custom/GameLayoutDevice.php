<?php
// GameLayoutDevice.php — phone vs desktop/tablet detection for the SWUDeck deck-builder
// layout (GameLayout.php picks desktop vs mobile based on this). Mirrors SWUSim's
// GameLayoutDevice.php so both sites share the same detection contract.
//
// iPads / Android tablets are intentionally treated as DESKTOP: the wide three-column
// builder fits them well. Only true phones get the vertical-stack GameLayoutMobile.php.
//
// Manual override for testing (works on any device/browser, incl. desktop devtools):
//   ?swuLayout=mobile   force the phone stack
//   ?swuLayout=desktop  force the wide board

if (!function_exists('SWUDeckIsMobileRequest')) {
  function SWUDeckIsMobileRequest() {
    if (isset($_GET['swuLayout'])) return $_GET['swuLayout'] === 'mobile';

    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if ($ua === '') return false;

    // Tablets → desktop layout.
    if (preg_match('/iPad/i', $ua)) return false;
    // Android tablets lack the "Mobile" token that Android phones carry.
    if (preg_match('/Android/i', $ua) && !preg_match('/Mobile/i', $ua)) return false;

    // Phones.
    return (bool) preg_match('/iPhone|iPod|Android.*Mobile|Windows Phone|BlackBerry|webOS|Opera Mini|IEMobile/i', $ua);
  }
}
