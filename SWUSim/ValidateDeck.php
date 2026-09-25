<?php
header('Content-Type: application/json');
error_reporting(0);

include_once '../Core/HTTPLibraries.php';
include_once './Custom/DeckImport.php';

$deckLink = trim(TryPost('deckLink', ''));

$format = strtolower(trim(TryPost('format', 'premier')));
if (SWUGetFormat($format) === null) $format = 'premier'; // guard unknown/garbage input

if ($deckLink === '') {
    echo json_encode(['success' => false, 'message' => 'No deck link provided.']);
    exit;
}

$result = SWUResolveDeckInput($deckLink);

if (!$result['success']) {
    echo json_encode(['success' => false, 'message' => $result['message']]);
    exit;
}

$leaderID   = $result['leader'];
$baseID     = $result['base'];
$mainDeck   = $result['mainDeck'];   // expanded: one entry per copy
$sideboard  = $result['sideboard'];  // expanded: one entry per copy
$deckCount  = count($mainDeck);
$unresolved = $result['unresolved'];

// $leaderID is a single CardID (standard decks) or an array of 2 (Twin Suns).
if (is_array($leaderID)) {
    $leaderName = implode(' / ', array_map(fn($id) => CardTitle($id) ?: $id, $leaderID));
} else {
    $leaderName = $leaderID ? (CardTitle($leaderID) ?: $leaderID) : '';
}
$baseName   = $baseID   ? (CardTitle($baseID)   ?: $baseID)   : '';

// ── Premier format validation ─────────────────────────────────────────────────
$formatErrors = SWUCheckFormat($format, $leaderID, $baseID, $mainDeck, $sideboard);

// ── Format detection (owner spec, 2026-09-25) ────────────────────────────────
// ADDITIVE: an extra response field, so every existing consumer is byte-compatible.
// Most restrictive first, first legal wins, Open is the floor — so this never fails.
// The menu uses it to set the card-pool chip when a deck link resolves.
$detectedFormat = SWUDetectFormat($leaderID, $baseID, $mainDeck, $sideboard);

// ── Import warnings (unresolved cards, missing slots) ────────────────────────
$warnings = [];
if (!$leaderID)      $warnings[] = 'No leader found.';
if (!$baseID)        $warnings[] = 'No base found.';
if ($deckCount === 0) $warnings[] = 'No deck cards found.';
if (!empty($unresolved)) {
    $sample = array_slice($unresolved, 0, 3);
    $extra  = count($unresolved) > 3 ? ' +' . (count($unresolved) - 3) . ' more' : '';
    $warnings[] = count($unresolved) . ' card(s) not recognized: ' . implode(', ', $sample) . $extra;
}

// ── May this input be saved to a deck library? (owner, 2026-09-25) ───────────
// ADDITIVE, like detectedFormat above: existing consumers are byte-compatible.
// The rule lives in SWUDeckInputIsLink() so the client never re-implements it — a second copy
// in JS would drift from the resolver the first time a deck site is added.
$savable  = SWUDeckInputIsLink($deckLink);
$deckName = trim((string)($result['name'] ?? ''));

echo json_encode([
    'success'      => true,
    'format'       => $format,
    'detectedFormat' => $detectedFormat,
    'savable'      => $savable,
    'deckName'     => $deckName,
    'leaderID'     => $leaderID,
    'leaderName'   => $leaderName,
    'baseID'       => $baseID,
    'baseName'     => $baseName,
    'deckCount'    => $deckCount,
    'sideboardCount' => count($sideboard),
    'warnings'     => $warnings,      // non-blocking import issues
    'formatErrors' => $formatErrors,  // blocking Premier rule violations
]);

// Format validation lives in SWUSim/Custom/DeckImport.php now:
//   SWUCheckFormat($formatId, ...) — config-driven, all formats
//   SWUCheckPremierFormat(...)     — back-compat wrapper for 'premier'
// (DeckImport.php is included at the top of this file.)
