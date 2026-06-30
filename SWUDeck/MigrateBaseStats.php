<?php
// TEMPORARY one-off migration — DELETE AFTER RUNNING ON PROD.
// Brings opponentdeckstats / opponentnamedbasestats up to the schema the
// "upgraded stats accept bases" + "improved stats read" features require.
// Without these, DeckStats.php's prepared statement fails -> fatal -> blank page.
//
// Mod-guarded. Idempotent: only adds what's missing; safe to run repeatedly.
// Run: https://swustats.net/TCGEngine/SWUDeck/MigrateBaseStats.php

header('Content-Type: text/plain');

include_once '../Core/HTTPLibraries.php';
include_once '../Database/ConnectionManager.php';
include_once '../AccountFiles/AccountDatabaseAPI.php';
include_once '../AccountFiles/AccountSessionAPI.php';

$modError = CheckLoggedInUserMod();
if ($modError !== "") {
  http_response_code(403);
  echo $modError . "\n";
  exit;
}

$conn = GetLocalMySQLConnection();

// --- 1. opponentdeckstats: add the Standard/Force/Splash wins/total columns ---
$colors = ['Green', 'Blue', 'Red', 'Yellow', 'Colorless'];
$types  = ['Standard', 'Force', 'Splash'];

$existing = [];
$res = $conn->query("SHOW COLUMNS FROM opponentdeckstats");
if ($res === false) {
  http_response_code(500);
  echo "FAILED: could not read opponentdeckstats columns: " . $conn->error . "\n";
  exit;
}
while ($row = $res->fetch_assoc()) { $existing[$row['Field']] = true; }

$adds = [];
foreach ($types as $type) {
  foreach ($colors as $color) {
    foreach (['wins', 'total'] as $kind) {
      $col = "{$kind}Vs{$color}{$type}";
      if (!isset($existing[$col])) {
        $adds[] = "ADD COLUMN `$col` int(11) NOT NULL DEFAULT 0";
      }
    }
  }
}

if ($adds) {
  $sql = "ALTER TABLE `opponentdeckstats` " . implode(", ", $adds);
  if ($conn->query($sql) === false) {
    http_response_code(500);
    echo "FAILED altering opponentdeckstats: " . $conn->error . "\n";
    echo "SQL: $sql\n";
    exit;
  }
  echo "opponentdeckstats: added " . count($adds) . " column(s).\n";
} else {
  echo "opponentdeckstats: already up to date (no columns added).\n";
}

// --- 2. opponentnamedbasestats: create if missing ---
$createNamed = "CREATE TABLE IF NOT EXISTS `opponentnamedbasestats` (
  `deckID` int(11) NOT NULL,
  `leaderID` varchar(16) NOT NULL,
  `baseID` varchar(16) NOT NULL,
  `source` int(11) NOT NULL DEFAULT 0,
  `wins` int(11) NOT NULL DEFAULT 0,
  `total` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`deckID`,`leaderID`,`baseID`,`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($createNamed) === false) {
  http_response_code(500);
  echo "FAILED creating opponentnamedbasestats: " . $conn->error . "\n";
  exit;
}
echo "opponentnamedbasestats: present (created if it was missing).\n";

$conn->close();
echo "\nMIGRATION DONE. Reload DeckStats, confirm it works, then DELETE this file.\n";
?>
