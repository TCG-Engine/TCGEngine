<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../vendor/autoload.php';
include_once './GamestateParser.php';
include_once './ZoneAccessors.php';
include_once './ZoneClasses.php';
include_once './GeneratedCode/GeneratedCardDictionaries.php';

// Lightweight endpoint: returns the current parsed deck leader/base (IDs and human names)
// Usage: call this script from the same gamestate context (it expects gamestate to be loaded the same way the PDF generator does)

try {
  ParseGamestate();
} catch (Exception $e) {
  echo json_encode(["error" => "Failed to parse gamestate: " . $e->getMessage()]);
  exit;
}

$leaderArr = &GetLeader(1);
$baseArr = &GetBase(1);

$leaderID = null;
$leaderName = null;
if (count($leaderArr) > 0) {
  $leaderID = strval($leaderArr[0]->CardID);
  $leaderName = CardTitle($leaderID) . (CardSubtitle($leaderID) ? ", " . CardSubtitle($leaderID) : "");
}

$baseID = null;
$baseName = null;
if (count($baseArr) > 0) {
  $baseID = strval($baseArr[0]->CardID);
  $baseName = CardTitle($baseID);
}

echo json_encode([
  'leaderID' => $leaderID,
  'leaderName' => $leaderName,
  'baseID' => $baseID,
  'baseName' => $baseName
]);

?>
