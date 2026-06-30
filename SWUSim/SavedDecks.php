<?php
// Saved-deck-links endpoint (SWUSim-local, swusim DB). Actions: save / favorite / rename / delete.
// Identity is the deck's `decklink` (raw decks: 'raw:'+sha1, base64 JSON in deckContent).
$__test = !empty($GLOBALS['__SAVEDECKS_TEST']);
// Buffer everything: a stray notice/warning from the require/auth chain (only emitted for a
// real authenticated session, with display_errors on in dev) would otherwise prepend to the
// JSON body and make the client's JSON.parse fail — surfacing as a misleading "unknown".
if (!$__test) ob_start();
require_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
require_once __DIR__ . '/../Database/ConnectionManager.php';
require_once __DIR__ . '/../Database/functions.inc.php';
require_once __DIR__ . '/Custom/DeckImport.php';   // SWUResolveDeckInput (pure lib)
require_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';   // titleData for matchup labels

$respond = function($arr) use ($__test) {
    if ($__test) return $arr;
    while (ob_get_level() > 0) { ob_end_clean(); }  // drop any stray output so the JSON is clean
    header('Content-Type: application/json'); echo json_encode($arr); exit;
};

CheckSession();   // AccountSessionAPI only DEFINES helpers — must start the session before reading it
$uid = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
if ($uid === 0) return $respond(['success'=>false,'error'=>'not_logged_in']);
$action = $_POST['action'] ?? '';

if ($action === 'save') {
    $input = trim($_POST['deckInput'] ?? '');
    if ($input === '') return $respond(['success'=>false,'error'=>'empty']);
    $resolved = SWUResolveDeckInput($input);
    // Resolved shape: {success, message, leader, base, mainDeck, sideboard, unresolved} — no name/format.
    if (empty($resolved['success']) || empty($resolved['leader'])) {
        return $respond(['success'=>false,'error'=>$resolved['message'] ?? 'unresolvable']);
    }
    $leader = $resolved['leader'];
    $base   = $resolved['base'] ?? '';
    $format = 'premier';
    // Name comes from the deck JSON's metadata.name (SWUDB/SWUStats/raw all carry it); blank → user renames.
    $name   = trim((string)($resolved['name'] ?? ''));
    $decklink = SWUComputeDeckIdentity($input);
    $content  = (strpos($decklink, 'raw:') === 0) ? base64_encode($input) : null;
    $ok = AddSavedDeck($uid, $decklink, $name, $leader, $base, $format, $content);
    if (!$ok) return $respond(['success'=>false,'error'=>'db_insert_failed']);
    return $respond(['success'=>true,'decklink'=>$decklink,'leader'=>$leader,'base'=>$base]);
}
if ($action === 'favorite') return $respond(['success'=>SetSavedDeckFavorite($uid, $_POST['decklink'] ?? '', (int)($_POST['value'] ?? 1))]);
if ($action === 'rename')   return $respond(['success'=>RenameSavedDeck($uid, $_POST['decklink'] ?? '', trim($_POST['name'] ?? ''))]);
if ($action === 'delete')   return $respond(['success'=>DeleteSavedDeck($uid, $_POST['decklink'] ?? '')]);
if ($action === 'matchups') {
    $decklink = trim($_POST['decklink'] ?? $_GET['decklink'] ?? '');
    if ($decklink === '') return $respond(['success'=>false,'error'=>'missing_decklink']);
    $overall = ['wins'=>0, 'losses'=>0];
    foreach (LoadSavedDecks($uid) as $d) {
        if (($d['decklink'] ?? '') === $decklink) { $overall = ['wins'=>(int)$d['wins'], 'losses'=>(int)$d['losses']]; break; }
    }
    $rows = LoadSavedDeckMatchups($uid, $decklink);
    $matchups = [];
    foreach ($rows as $r) {
        $matchups[] = [
            'oppLeader'      => $r['oppLeader'],
            'oppLeaderTitle' => $GLOBALS['titleData'][$r['oppLeader']] ?? $r['oppLeader'],
            'oppBase'        => $r['oppBase'],
            'oppBaseLabel'   => SWUMatchupBaseLabel($r['oppBase']),
            'wins'           => (int)$r['wins'],
            'losses'         => (int)$r['losses'],
        ];
    }
    return $respond(['success'=>true, 'overall'=>$overall, 'matchups'=>$matchups]);
}
return $respond(['success'=>false,'error'=>'unknown_action']);
