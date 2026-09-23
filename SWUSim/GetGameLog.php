<?php
// Read-only game log for a FINISHED game, for the Sideboard screen.
//
// ⚠ HIDDEN INFORMATION. This serves a game that is over, addressed by a matchId anyone could guess,
// so it authenticates the caller's SEAT and filters every line through the ONE shared visibility
// rule (SWUFilterGameLogForViewer, pinned to the generated reader by
// SWUSim/DevTools/tests/gamelog_visibility_parity_test.php). Never widen this to "the game is over,
// show everything" — a Bo3's game 1 is over while games 2 and 3 are still to be played, and what
// seat 1 drew is exactly the information sideboarding must not leak.
header('Content-Type: application/json');
include_once __DIR__ . '/../Core/HTTPLibraries.php';
include_once __DIR__ . '/../Core/NetworkingLibraries.php';
include_once __DIR__ . '/../Core/ViewerIdentity.php';
include_once __DIR__ . '/../Core/ChatScopeAuth.php';
include_once __DIR__ . '/MatchFlow.php';

$empty = ['gameNumber' => 0, 'lines' => []];
$matchId  = preg_replace('/[^A-Za-z0-9_]/', '', strval($_GET['matchId'] ?? ''));
$authKey  = strval($_GET['authKey'] ?? '');
$viewer   = NormalizeViewerIdentity(strval($_GET['playerID'] ?? ''), 2);
if ($matchId === '' || $viewer['viewerID'] === '') { echo json_encode($empty); exit; }
if (!ChatScopeAuthOk('match', 'm:' . $matchId, $viewer, $authKey, 'SWUSim')) { echo json_encode($empty); exit; }

$m = SWUReadMatch($matchId);
if (!is_array($m) || empty($m['games'])) { echo json_encode($empty); exit; }

// The most recently COMPLETED game: the last entry that has a winner. A game still in progress has
// none, and its log is the live game page's job, not this endpoint's.
$chosen = null;
foreach ($m['games'] as $g) { if (isset($g['winner']) && $g['winner'] !== null) $chosen = $g; }
if ($chosen === null) { echo json_encode($empty); exit; }

// Load the finished game's state through the SANCTIONED parser. Gamestate.txt is serialised
// POSITIONALLY with no delimiters, so reading the log out of the raw file would break the next time
// a zone is added to GameSchema.txt — the exact failure that destroyed SWUDeck sideboards once.
// This runs ONCE per Sideboard page load, not on a poll, so the parser's cost is fine here.
$logGame = preg_replace('/[^A-Za-z0-9_-]/', '', strval($chosen['gameName']));
if ($logGame === '' || !is_file(__DIR__ . "/Games/$logGame/Gamestate.txt")) { echo json_encode($empty); exit; }

// Animation stubs: the parse path may reference them outside a real turn (same preamble as
// SWUSim/DevTools/bugreport-load-state.php).
if (!function_exists('ConvertMzIDToAbsolute'))         { function ConvertMzIDToAbsolute($m, $p): string { return ''; } }
if (!function_exists('QueueDamageAnimation'))          { function QueueDamageAnimation($t, $a): void {} }
if (!function_exists('QueueRestoreAnimation'))         { function QueueRestoreAnimation($t, $a): void {} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t): void {} }
if (!function_exists('QueueShieldBreakAnimation'))     { function QueueShieldBreakAnimation($t): void {} }
foreach (['DeterministicRNG', 'CoreZoneModifiers', 'GameAuth'] as $f) include_once __DIR__ . "/../Core/$f.php";
include_once __DIR__ . '/ZoneClasses.php';
include_once __DIR__ . '/ZoneAccessors.php';
include_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/GamestateParser.php';
include_once __DIR__ . '/Custom/GameLogEvents.php';

global $gameName, $playerID, $gGameLog;
$gameName = $logGame; $GLOBALS['gameName'] = $logGame; $playerID = intval($viewer['viewerSeat']);

// ⚠ READ-ONLY. This endpoint must NEVER call WriteGamestate(): ParseGamestate alone does not write,
// and an accidental write here would rewrite a finished game's state on every Sideboard load.
// Pinned by DevTools/tdd-regression/test_swusim_getgamelog.php ("five reads left the gamestate
// byte-identical").
ParseGamestate(__DIR__ . '/');

$lines = SWUFilterGameLogForViewer(strval($gGameLog ?? ''), intval($viewer['viewerSeat']), false);

// Card NAMES for every [[SET_NNN]] token in the lines this viewer may see.
//
// ⚠ THE CLIENT CANNOT DO THIS. The Sideboard page builds a title map from the cards in YOUR deck, so
// substituting there leaves every card you do not own — i.e. the opponent's whole board, which is
// most of what a log is about — rendering as a raw id. The dictionary is already loaded here.
//
// ⚠ Built from $lines, NOT from the raw log: a name map covering a line this seat cannot see would
// hand back exactly the hidden information the filter just removed.
$names = [];
foreach ($lines as $line) {
    if (!preg_match_all('/\[\[([A-Z0-9]{2,5}_[A-Z0-9]{2,4})\]\]/', $line, $mm)) continue;
    foreach ($mm[1] as $cid) {
        if (isset($names[$cid])) continue;
        $t = function_exists('CardTitle') ? (string)CardTitle($cid) : '';
        if ($t === '') continue;
        $sub = function_exists('CardSubtitle') ? (string)CardSubtitle($cid) : '';
        $names[$cid] = ($sub !== '') ? "$t - $sub" : $t;
    }
}

echo json_encode([
    'gameNumber' => intval($chosen['gameNumber'] ?? 0),
    'lines'      => $lines,
    'names'      => (object)$names,
]);
