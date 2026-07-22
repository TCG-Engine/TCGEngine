<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

require_once __DIR__ . '/../Core/HTTPLibraries.php';
require_once __DIR__ . '/../AzukiSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/GamestateParser.php';
require_once __DIR__ . '/ZoneClasses.php';
require_once __DIR__ . '/ZoneAccessors.php';
require_once __DIR__ . '/../Database/ConnectionManager.php';
require_once __DIR__ . '/../AccountFiles/AccountDatabaseAPI.php';

$deckID = trim((string)TryGet('deckID', ''));
$format = strtolower(trim((string)TryGet('format', 'json')));

if (preg_match('/^[A-Za-z]{12}$/', $deckID)) {
  $resolvedDeckID = ResolveFriendlyCode($deckID);
  if ($resolvedDeckID !== null) $deckID = (string)$resolvedDeckID;
}

if (!preg_match('/^\d+$/', $deckID)) {
  AzukiDeckError('Missing or invalid deckID.', 400);
}

$gamestateFile = __DIR__ . '/Games/' . $deckID . '/Gamestate.txt';
if (!is_file($gamestateFile)) {
  AzukiDeckError('Deck not found.', 404);
}

$gameName = $deckID;
ParseGamestate(__DIR__ . '/');

$leader = GetLeader(1);
$gate = GetGate(1);
$mainDeck = GetMainDeck(1);
$sideboard = GetSideboard(1);

if (empty($leader) || empty($gate)) {
  AzukiDeckError('Deck is missing a leader or gate.', 422);
}

$leaderID = (string)$leader[0]->CardID;
$gateID = (string)$gate[0]->CardID;
$mainCounts = AzukiDeckCardCounts($mainDeck);
$sideboardCounts = AzukiDeckCardCounts($sideboard);

if ($format === 'sha256') {
  $hashDeck = [
    'leader' => $leaderID,
    'gate' => $gateID,
    'mainDeck' => AzukiDeckExpandedSortedCards($mainCounts),
    'sideboard' => AzukiDeckExpandedSortedCards($sideboardCounts)
  ];
  header('Content-Type: text/plain; charset=utf-8');
  echo hash('sha256', json_encode($hashDeck));
  exit;
}

if ($format === 'text') {
  header('Content-Type: text/plain; charset=utf-8');
  echo "Leader\r\n1 " . CardName($leaderID) . "\r\n\r\n";
  echo "Gate\r\n1 " . CardName($gateID) . "\r\n\r\n";
  AzukiDeckWriteTextSection('Main Deck', $mainCounts);
  AzukiDeckWriteTextSection('Sideboard', $sideboardCounts);
  exit;
}

if ($format !== 'json') {
  header('Content-Type: text/plain; charset=utf-8');
  echo $gateID . ' ' . $leaderID . "\r\n";
  echo implode(' ', AzukiDeckExpandedCards($mainCounts));
  exit;
}

$response = new stdClass();
$response->metadata = new stdClass();
$response->metadata->name = AzukiDeckAssetName($deckID);
$response->leader = (object)['id' => $leaderID, 'count' => 1];
$response->gate = (object)['id' => $gateID, 'count' => 1];
$response->deck = AzukiDeckCountObjects($mainCounts);
$response->sideboard = AzukiDeckCountObjects($sideboardCounts);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);

function AzukiDeckError($message, $status) {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error' => $message]);
  exit;
}

function AzukiDeckCardCounts($cards) {
  $counts = [];
  foreach ($cards as $card) {
    $cardID = trim((string)($card->CardID ?? ''));
    if ($cardID === '' || $cardID === '-') continue;
    $counts[$cardID] = ($counts[$cardID] ?? 0) + 1;
  }
  return $counts;
}

function AzukiDeckCountObjects($counts) {
  $cards = [];
  foreach ($counts as $cardID => $quantity) {
    $cards[] = (object)['id' => $cardID, 'count' => $quantity];
  }
  return $cards;
}

function AzukiDeckExpandedCards($counts) {
  $cards = [];
  foreach ($counts as $cardID => $quantity) {
    for ($i = 0; $i < $quantity; ++$i) $cards[] = $cardID;
  }
  return $cards;
}

function AzukiDeckExpandedSortedCards($counts) {
  $cards = AzukiDeckExpandedCards($counts);
  sort($cards);
  return $cards;
}

function AzukiDeckWriteTextSection($label, $counts) {
  echo $label . "\r\n";
  foreach ($counts as $cardID => $quantity) {
    echo $quantity . ' ' . CardName($cardID) . "\r\n";
  }
  echo "\r\n";
}

function AzukiDeckAssetName($deckID) {
  $fallback = 'Azuki Deck #' . $deckID;
  $conn = GetLocalMySQLConnection();
  if (!$conn) return $fallback;

  $query = $conn->prepare('SELECT assetName FROM ownership WHERE assetType = 1 AND assetIdentifier = ?');
  if (!$query) {
    $conn->close();
    return $fallback;
  }

  $query->bind_param('i', $deckID);
  $query->execute();
  $query->bind_result($assetName);
  $query->fetch();
  $query->close();
  $conn->close();

  $assetName = trim((string)$assetName);
  return $assetName !== '' ? $assetName : $fallback;
}

?>
