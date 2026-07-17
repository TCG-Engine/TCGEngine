<?php

require_once __DIR__ . '/../Database/ConnectionManager.php';

function AzukiDeckLoadOwnedDecks($userID) {
  $userID = trim((string)$userID);
  if ($userID === '') return [];

  $conn = GetLocalMySQLConnection();
  if (!$conn) return [];

  $stmt = $conn->prepare(
    'SELECT * FROM ownership WHERE assetType = 1 AND assetOwner = ? AND assetStatus = 1 ORDER BY assetIdentifier DESC'
  );
  if (!$stmt) {
    $conn->close();
    return [];
  }

  $stmt->bind_param('s', $userID);
  $stmt->execute();
  $result = $stmt->get_result();
  $decks = [];
  while ($row = $result->fetch_assoc()) {
    $decks[] = $row;
  }
  $stmt->close();
  $conn->close();
  return $decks;
}

function AzukiDeckLoadOwnedDeck($deckID, $userID) {
  $deckID = trim((string)$deckID);
  $userID = trim((string)$userID);
  if (!preg_match('/^\d+$/', $deckID) || $userID === '') return null;

  $conn = GetLocalMySQLConnection();
  if (!$conn) return null;

  $stmt = $conn->prepare(
    'SELECT * FROM ownership WHERE assetType = 1 AND assetIdentifier = ? AND assetOwner = ? AND assetStatus = 1 LIMIT 1'
  );
  if (!$stmt) {
    $conn->close();
    return null;
  }

  $stmt->bind_param('ss', $deckID, $userID);
  $stmt->execute();
  $result = $stmt->get_result();
  $deck = $result->fetch_assoc() ?: null;
  $stmt->close();
  $conn->close();
  return $deck;
}

function AzukiDeckReadDeckState($deckID) {
  $deckID = trim((string)$deckID);
  $failure = [
    'success' => false,
    'message' => 'Could not load the selected AzukiDeck deck.',
    'leader' => '',
    'gate' => '',
    'mainDeck' => [],
    'unresolved' => []
  ];

  if (!preg_match('/^\d+$/', $deckID)) return $failure;

  $filename = __DIR__ . '/Games/' . $deckID . '/Gamestate.txt';
  if (!is_file($filename)) return $failure;

  $lines = file($filename, FILE_IGNORE_NEW_LINES);
  if (!is_array($lines) || count($lines) < 8) return $failure;

  $position = 2; // currentPlayer and updateNumber
  $readZone = function() use (&$lines, &$position) {
    if (!isset($lines[$position])) return null;
    $countLine = trim((string)$lines[$position++]);
    if (!preg_match('/^\d+$/', $countLine)) return null;

    $count = intval($countLine);
    $cards = [];
    for ($i = 0; $i < $count; ++$i) {
      if (!isset($lines[$position])) return null;
      $serialized = trim((string)$lines[$position++]);
      $parts = preg_split('/\s+/', $serialized);
      $cardID = trim((string)($parts[0] ?? ''));
      if ($cardID !== '') $cards[] = $cardID;
    }
    return $cards;
  };

  $leaders = $readZone();
  $opponentLeaders = $readZone();
  $gates = $readZone();
  $opponentGates = $readZone();
  $mainDeck = $readZone();
  $opponentMainDeck = $readZone();

  if ($leaders === null || $opponentLeaders === null || $gates === null || $opponentGates === null || $mainDeck === null || $opponentMainDeck === null) {
    return $failure;
  }

  $leader = $leaders[0] ?? '';
  $gate = $gates[0] ?? '';
  if ($leader === '' || $gate === '' || empty($mainDeck)) {
    $failure['message'] = 'The selected AzukiDeck deck needs a leader, gate, and at least one main-deck card.';
    $failure['leader'] = $leader;
    $failure['gate'] = $gate;
    $failure['mainDeck'] = $mainDeck;
    return $failure;
  }

  return [
    'success' => true,
    'message' => '',
    'leader' => $leader,
    'gate' => $gate,
    'mainDeck' => $mainDeck,
    'unresolved' => []
  ];
}

function AzukiDeckResolveOwnedDeck($deckID, $userID) {
  if (!AzukiDeckLoadOwnedDeck($deckID, $userID)) {
    return [
      'success' => false,
      'message' => 'That AzukiDeck deck is unavailable or does not belong to your account.',
      'leader' => '',
      'gate' => '',
      'mainDeck' => [],
      'unresolved' => []
    ];
  }

  return AzukiDeckReadDeckState($deckID);
}

?>
