<?php
// FindCorruptedDecks.php — mod-gated page that finds SWUDeck decks whose sideboard the current parser
// can't read (the Leader1/Leader2 format-break corruption). Runs under Apache, so mysqli is available.
//
// It pulls every SWUDeck deck id from `ownership` (assetType=1), parses each deck exactly like
// LoadDeck.php (ParseGamestate + GetSideboard), and flags any deck whose sideboard contains a card
// that resolves to a null id (CardIDLookup) — i.e. LoadDeck's "sideboard id:null" signal. Read-only:
// ParseGamestate never writes the gamestate.
//
// Usage (as a logged-in mod):
//   https://swustats.net/TCGEngine/FindCorruptedDecks.php                 -> JSON { corruptedDeckIds, counts }
//   https://swustats.net/TCGEngine/FindCorruptedDecks.php?plain=1         -> newline-separated deck ids
//   https://swustats.net/TCGEngine/FindCorruptedDecks.php?offset=0&limit=1000   -> scan a batch (page through big sets)
//
// NOTE: run this AFTER the Leader1/Leader2 migration. Before migration it also flags INTACT old-format
// decks (their data is fine, just misread) — post-migration a flag means the deck is genuinely
// corrupted, so recover it from assetversions.

require_once "./Database/ConnectionManager.php";
include_once './AccountFiles/AccountSessionAPI.php';
include_once './AccountFiles/AccountDatabaseAPI.php';

$plain = isset($_GET['plain']) && ($_GET['plain'] === '1' || $_GET['plain'] === 'true');
if (!$plain) header('Content-Type: application/json');

$error = CheckLoggedInUserMod();
if ($error !== "") {
  if ($plain) { header('Content-Type: text/plain'); echo "error: $error\n"; }
  else echo json_encode(['error' => $error]);
  exit();
}

set_time_limit(0);
ignore_user_abort(true);

require_once "./SWUDeck/GeneratedCode/GeneratedCardDictionaries.php";
require_once "./SWUDeck/ZoneClasses.php";
require_once "./SWUDeck/ZoneAccessors.php";
require_once "./SWUDeck/GamestateParser.php";

$offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;
$limit  = isset($_GET['limit'])  ? max(0, intval($_GET['limit']))  : 0;   // 0 = all

// 1) Every SWUDeck deck id, from the DB.
$conn = GetLocalMySQLConnection();
if (!$conn) {
  if ($plain) { header('Content-Type: text/plain'); echo "error: DB connection failed\n"; }
  else echo json_encode(['error' => 'DB connection failed']);
  exit();
}
$ids = [];
$sql = "SELECT assetIdentifier FROM ownership WHERE assetType = 1 ORDER BY assetIdentifier";
if ($limit > 0) $sql .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
$res = mysqli_query($conn, $sql);
if ($res) { while ($row = mysqli_fetch_assoc($res)) $ids[] = $row['assetIdentifier']; }
$totalDecks = 0;
$cres = mysqli_query($conn, "SELECT COUNT(*) AS c FROM ownership WHERE assetType = 1");
if ($cres && ($crow = mysqli_fetch_assoc($cres))) $totalDecks = intval($crow['c']);
mysqli_close($conn);

// 2) Parse each deck like LoadDeck; flag a null-id sideboard entry.
global $gameName;
$corrupted = [];
$counts = ['total_in_db' => $totalDecks, 'scanned' => 0, 'corrupted' => 0, 'empty' => 0, 'ok' => 0, 'no_file' => 0];

foreach ($ids as $id) {
  $counts['scanned']++;
  if (!is_file("./SWUDeck/Games/" . $id . "/Gamestate.txt")) { $counts['no_file']++; continue; }
  $gameName = $id;
  ParseGamestate("./SWUDeck/");                 // read-only; InitializeGamestate() resets all zones first
  $sb = &GetSideboard(1);
  if (!is_array($sb) || count($sb) === 0) { $counts['empty']++; continue; }
  $nullIds = 0;
  foreach ($sb as $card) {
    $lookup = CardIDLookup($card->CardID);      // setId=true equivalent; garbage ("0") -> null
    if ($lookup === null || $lookup === '') $nullIds++;
  }
  if ($nullIds > 0) { $corrupted[] = $id; $counts['corrupted']++; }
  else $counts['ok']++;
}

// 3) Report.
if ($plain) {
  header('Content-Type: text/plain');
  echo implode("\n", $corrupted) . (count($corrupted) ? "\n" : "");
} else {
  echo json_encode([
    'corruptedDeckIds' => $corrupted,
    'counts' => $counts,
    'offset' => $offset,
    'limit'  => $limit,
    'note'   => 'Run after the Leader1/Leader2 migration; before it, intact old-format decks are flagged too.',
  ]);
}
