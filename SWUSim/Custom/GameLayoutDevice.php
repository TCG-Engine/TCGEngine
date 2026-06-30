<?php
// GameLayoutDevice.php — phone vs desktop/tablet detection for SWUSim layout
// routing (GameLayout.php picks the layout based on this).
//
// iPads / Android tablets are intentionally treated as DESKTOP: the wide board
// fits them well, and they're rarely used in portrait for this. Only true phones
// get the vertical-stack GameLayoutMobile.php.
//
// Manual override for testing (works on any device/browser, incl. desktop devtools):
//   ?swuLayout=mobile   force the phone stack
//   ?swuLayout=desktop  force the wide board

// ── Board background ─────────────────────────────────────────────────────────
// Webp is enforced. Convention: a board lives at <base>.webp for the desktop/tablet
// layout and <base>-mobile.webp for the phone layout. To swap boards, change the
// $base below (or, once boards are user-selectable, derive it from the selection) —
// both variants follow automatically. Use the png-to-webp tool/skill to produce both.
if (!function_exists('SWUBoardBackground')) {
  function SWUBoardBackground($mobile = false) {
    $base = './Assets/Boards/SWUSim/default';
    return $base . ($mobile ? '-mobile' : '') . '.webp';
  }
}

if (!function_exists('SWUSimIsMobileRequest')) {
  function SWUSimIsMobileRequest() {
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
