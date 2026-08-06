<?php
// Live deck-legality check for the deck builder's status badge. Reads the deck's ALREADY-SAVED
// gamestate (SWUDeck autosaves on every change) and runs the shared, config-driven format engine
// SWUCheckFormat() over it — the same rules SWUSim uses at import — returning a compact pass/fail +
// issue list. Polled (debounced) by the editor on deck mutations. Open format has no legality
// constraints, so it reports applicable:false and the badge stays hidden.
header('Content-Type: application/json; charset=utf-8');

include_once './GamestateParser.php';
include_once './ZoneAccessors.php';
include_once './ZoneClasses.php';
include_once './GeneratedCode/GeneratedCardDictionaries.php';
include_once '../Core/HTTPLibraries.php';               // TryGet
include_once '../Database/ConnectionManager.php';
include_once '../AccountFiles/AccountDatabaseAPI.php';  // LoadAssetData
include_once '../AccountFiles/AccountSessionAPI.php';    // IsUserLoggedIn / LoggedInUser
include_once '../Assets/patreon-php-master/src/PatreonLibraries.php';  // IsPatron (patron-visibility check)
include_once '../AppCore/SWU/Formats.php';               // SWUGetFormat
include_once '../AppCore/SWU/DeckValidation.php';        // SWUCheckFormat
include_once './Custom/DeckFormats.php';                 // SWUDeckFormatDisplayName

$gameName = TryGet('gameName', '');
if ($gameName === '') {
  echo json_encode(['applicable' => false, 'error' => 'missing gameName']);
  exit;
}

if (!IsUserLoggedIn()) {
  echo json_encode(['applicable' => false, 'error' => 'not logged in']);
  exit;
}

$assetData = LoadAssetData(1, $gameName);
if ($assetData == null) {
  echo json_encode(['applicable' => false, 'error' => 'no such deck']);
  exit;
}
// Access: owner always; otherwise only if the deck is publicly/patron visible (mirrors CreatePDF).
$loggedInUser = LoggedInUser();
if ($loggedInUser != $assetData['assetOwner']) {
  $vis = (int)($assetData['assetVisibility'] ?? 0);
  $allowed = ($vis > 10000) ? IsPatron($vis) : ($vis != 0);
  if (!$allowed) {
    echo json_encode(['applicable' => false, 'error' => 'forbidden']);
    exit;
  }
}

$format = $assetData['format'] ?? 'premier';
// Open decks are unconstrained — nothing to validate, badge hidden.
if ($format === 'open') {
  echo json_encode(['applicable' => false, 'format' => 'open']);
  exit;
}

try {
  ParseGamestate();
} catch (Exception $e) {
  echo json_encode(['applicable' => false, 'error' => 'parse failed']);
  exit;
}

global $aspectData;

// Publish the SET_NNN card universe the shared reprint resolution reads (so a deck card stored as
// an older/illegal printing is still recognised as legal when it has a legal reprint).
SWUDeckSetReprintUniverse();

// Since 2026-08-04 SWUDeck's dictionaries are SET_NNN-keyed — the same scheme SWUCheckFormat and
// the reprint/ban maps speak — so no translation layer is needed. Deck FILES still hold UUIDs
// until the identity migration re-keys them, so each stored id is normalised once on the way in;
// an id that resolves to nothing passes through unchanged and surfaces as "not legal", which is
// the behaviour the old $toSet fallback had.
$norm = fn($id) => function_exists('SWUNormalizeDictionaryKey')
    ? SWUNormalizeDictionaryKey((string)$id) : (string)$id;

$leaderArr = &GetLeader(1);
$leaders = [];
$leaderAspects = [];
foreach ($leaderArr as $l) {
  if ($l->Removed()) continue;
  $id = $norm($l->CardID);
  $leaders[] = $id;
  $leaderAspects[$id] = $aspectData[$id] ?? '';
}

$base = '';
$baseArr = &GetBase(1);
foreach ($baseArr as $b) {
  if ($b->Removed()) continue;
  $base = $norm($b->CardID);
  break;
}

$mainDeck = [];
$mainArr = &GetMainDeck(1);
foreach ($mainArr as $c) { if (!$c->Removed()) $mainDeck[] = $norm($c->CardID); }

$sideboard = [];
$sideArr = &GetSideboard(1);
foreach ($sideArr as $c) { if (!$c->Removed()) $sideboard[] = $norm($c->CardID); }

$issues = SWUCheckFormat($format, $leaders, $base, $mainDeck, $sideboard, $leaderAspects);

echo json_encode([
  'applicable'  => true,
  'format'      => $format,
  'formatName'  => SWUDeckFormatDisplayName($format),
  'legal'       => empty($issues),
  'issueCount'  => count($issues),
  'issues'      => array_values($issues),
]);
