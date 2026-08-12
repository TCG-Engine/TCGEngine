<?php // SWUSim/BookmarksInfo.php — the gamestate-bookmark list for the settings + end-game panels.
// A separate READ endpoint rather than part of the NextTurn payload: it must also serve the end-game
// overlay (after the game is over), and bookmark labels have no business in the hot per-action render
// path. Payloads are NEVER included — a payload is the full serialized gamestate, including both
// players' hidden zones. BookmarkList() strips them; do not add them back.
header('Content-Type: application/json');
include_once __DIR__ . '/../Core/GameAuth.php';
include_once __DIR__ . '/MatchFlow.php';                                  // SWUReadMatchRef / SWUReadMatch
include_once __DIR__ . '/GamestateParser.php';
include_once __DIR__ . '/ZoneAccessors.php';
include_once __DIR__ . '/ZoneClasses.php';
include_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/Custom/UndoStack.php';                           // UndoPayloadDecode
include_once __DIR__ . '/Custom/BookmarkStore.php';                       // BookmarkList

$gameName = preg_replace('/[^A-Za-z0-9_]/', '', $_GET['gameName'] ?? '');
$seatStr  = strval($_GET['playerID'] ?? '');
$authKey  = strval($_GET['authKey'] ?? '');
$isSpectator = ($seatStr === 'S');
$seat = intval($seatStr);

if ($gameName === '') { echo json_encode(['isPrivate' => false, 'bookmarks' => []]); exit; }

// Auth mirrors EndGameInfo.php: any seat the match actually has is valid (Twin Suns runs four —
// hardcoding 1|2 locked seats 3 and 4 out of their own end-game menu there). Spectators get a
// read-only list with no action buttons; the write modes reject them independently.
$ref = SWUReadMatchRef($gameName);
if (!$isSpectator && $ref !== null) {
    $m = SWUReadMatch($ref['matchId']);
    if (is_array($m)) {
        if ($seat < 1 || !isset($m['players'][strval($seat)])) { echo json_encode(['error' => 'bad seat', 'bookmarks' => []]); exit; }
        $expected = strval($m['players'][strval($seat)]['authKey'] ?? '');
        if ($expected === '' || !hash_equals($expected, $authKey)) { echo json_encode(['error' => 'auth', 'bookmarks' => []]); exit; }
    }
}

// Load the gamestate so the Versions zones (and therefore the bookmark store) are populated.
// ParseGamestate() reads the $gameName global, exactly as GetNextTurn.php does.
ob_start(); ParseGamestate(); ob_end_clean();

// Privacy is decided AFTER the parse, deliberately: SWUGameIsPrivate also accepts one-player modes
// (goldfish/hotseat), and the mode is a GlobalEffect that only exists once the gamestate is loaded.
// Checking isPrivate alone before the parse would hide the panel in any solo game older than the
// one-hour APCu TTL on the privacy flag. Empty list rather than an error in a public game, so the
// client renders "nothing here" without special-casing.
if (!SWUGameIsPrivate('SWUSim', $gameName)) {
    echo json_encode(['isPrivate' => false, 'bookmarks' => []]);
    exit;
}

$out = [];
foreach (BookmarkList() as $id => $r) {
    $out[] = [
        'id'    => intval($id),
        'round' => intval($r['round']),
        'phase' => strval($r['phase']),
        'seat'  => intval($r['seat']),
        'label' => strval($r['label']),
    ];
}
echo json_encode(['isPrivate' => true, 'bookmarks' => $out]);
