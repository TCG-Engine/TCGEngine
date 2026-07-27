<?php

$query = [];
$legacyError = trim((string)($_GET['error'] ?? ''));
if ($legacyError !== '') {
  $query['deckError'] = $legacyError;
}

$location = '/TCGEngine/SharedUI/Sites/AzukiSim/MainMenu.php';
if (!empty($query)) {
  $location .= '?' . http_build_query($query);
}

header('Location: ' . $location, true, 302);
exit();
