<?php
// GameLayoutDevice.php - phone vs desktop/tablet detection for AzukiSim.
//
// Phones get the responsive portrait/landscape board in GameLayoutMobile.php. Tablets keep the
// desktop board because the existing Azuki layout has enough room there.
//
// Manual override for local testing:
//   ?azukiLayout=mobile
//   ?azukiLayout=desktop

if (!function_exists('AzukiSimIsMobileRequest')) {
  function AzukiSimIsMobileRequest() {
    if (isset($_GET['azukiLayout'])) return $_GET['azukiLayout'] === 'mobile';

    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if ($ua === '') return false;

    if (preg_match('/iPad/i', $ua)) return false;
    if (preg_match('/Android/i', $ua) && !preg_match('/Mobile/i', $ua)) return false;

    return (bool) preg_match('/iPhone|iPod|Android.*Mobile|Windows Phone|BlackBerry|webOS|Opera Mini|IEMobile/i', $ua);
  }
}

// Tutorial presentation is game-specific and shared by both Azuki layouts. Keeping the include
// here avoids touching generated InitialLayout.php and ensures the mobile early-return still loads it.
if(function_exists('AzukiTutorialIsActive') && AzukiTutorialIsActive()) {
  $tutorialCssVersion = filemtime(dirname(__DIR__) . '/Tutorial/tutorial.css');
  $tutorialJsVersion = filemtime(dirname(__DIR__) . '/Tutorial/tutorial-client.js');
  echo '<link rel="stylesheet" href="./AzukiSim/Tutorial/tutorial.css?v=' . intval($tutorialCssVersion) . '">';
  echo '<script defer src="./AzukiSim/Tutorial/tutorial-client.js?v=' . intval($tutorialJsVersion) . '"></script>';
}
