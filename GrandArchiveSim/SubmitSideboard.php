<?php
// Accept one seat's sideboarded deck (material / main / sideboard, as card-id arrays from the card-image
// editor) for a match's sideboard step; when both seats are in, spawn the next game (loser first).
// The player only REARRANGES their registered cards between zones — we enforce that the submitted pool
// matches their original deck's pool exactly, then run GA's structural legality check.
header('Content-Type: application/json');
include_once __DIR__ . '/../Core/NetworkingLibraries.php';
include_once __DIR__ . '/../Core/HTTPLibraries.php';
include_once __DIR__ . '/../Core/CoreZoneModifiers.php';
include_once __DIR__ . '/MatchFlow.php';   // Match model + spawn + CreateGame runtime (DeckImport + dictionaries)

$matchId = isset($_POST['matchId']) ? preg_replace('/[^A-Za-z0-9_]/', '', $_POST['matchId']) : '';
$seat    = intval($_POST['playerID'] ?? $_POST['seat'] ?? 0);
$authKey = strval($_POST['authKey'] ?? '');

$m = GAReadMatch($matchId);
if (!is_array($m)) { echo json_encode(['success' => false, 'message' => 'Match not found.']); exit; }
if (($m['state'] ?? '') !== 'sideboarding') { echo json_encode(['success' => false, 'message' => 'Not in sideboarding.']); exit; }
if ($seat !== 1 && $seat !== 2) { echo json_encode(['success' => false, 'message' => 'Invalid seat.']); exit; }

// Auth: the match stores each seat's authKey.
$expected = strval($m['players'][strval($seat)]['authKey'] ?? '');
if ($expected === '' || !hash_equals($expected, $authKey)) { echo json_encode(['success' => false, 'message' => 'Auth failed.']); exit; }

// The card-image editor posts three JSON arrays of card ids.
$material  = json_decode($_POST['material']  ?? '[]', true);
$mainDeck  = json_decode($_POST['mainDeck']  ?? '[]', true);
$sideboard = json_decode($_POST['sideboard'] ?? '[]', true);
if (!is_array($material) || !is_array($mainDeck) || !is_array($sideboard)) {
    echo json_encode(['success' => false, 'message' => 'Malformed deck submission.']); exit;
}
$material  = array_values(array_map('strval', $material));
$mainDeck  = array_values(array_map('strval', $mainDeck));
$sideboard = array_values(array_map('strval', $sideboard));

// Pool integrity: you may only REARRANGE your registered cards — the combined multiset must be unchanged.
$orig = $m['players'][strval($seat)]['originalDeck'] ?? [];
$origPool = array_merge($orig['material'] ?? [], $orig['mainDeck'] ?? [], $orig['sideboard'] ?? []);
$subPool  = array_merge($material, $mainDeck, $sideboard);
sort($origPool); sort($subPool);
if ($origPool !== $subPool) {
    echo json_encode(['success' => false, 'message' => 'Sideboarded deck must use exactly your registered cards.']); exit;
}

$resolved = ['success' => true, 'message' => '', 'material' => $material, 'mainDeck' => $mainDeck,
             'sideboard' => $sideboard, 'unresolved' => []];
$vr = GAValidateResolvedDeck($resolved, strval($m['format'] ?? 'standard'));
if (empty($vr['success'])) { echo json_encode(['success' => false, 'message' => $vr['message'] ?? 'Illegal deck.']); exit; }

GASubmitSideboardDeck($matchId, $seat, $resolved);
$next = GAMaybeSpawnAfterSideboard($matchId);   // spawns + clears sideboard state when both are in
$m = GAReadMatch($matchId);
$bothReady = (!empty($next)) || (is_array($m) && GASideboardBothReady($m));

echo json_encode([
    'success'      => true,
    'message'      => '',
    'bothReady'    => $bothReady,
    'waiting'      => !$bothReady,
    'nextGameName' => $next ?: null,
]);
