<?php
// zzFindCorruptedDecks.php — mod-gated. Finds SWUDeck decks whose sideboard was destroyed by the
// Leader1/Leader2 format break, in two passes:
//
//   PASS 1 (fast, file analysis): walk each SWUDeck/Games/<id>/Gamestate.txt to its Sideboard zone
//     (format-aware — reads old AND new layouts correctly) and flag files whose sideboard contains a
//     non-card token. Because it reads old files correctly, intact-but-old decks are NOT flagged, so
//     only genuinely-corrupted files become candidates.
//
//   PASS 2 (authoritative double-check): re-check each candidate through the real LoadDeck.php API
//     (?deckID=..&setId=true). A deck is reported CORRUPTED only if BOTH passes agree (LoadDeck
//     returns a null sideboard id). Candidates LoadDeck does NOT confirm are listed separately.
//
// Read-only. Usage (as mod ninin/OotTheMonk; auto-allowed in local DEVENV):
//   https://swustats.net/TCGEngine/zzFindCorruptedDecks.php            -> JSON
//   https://swustats.net/TCGEngine/zzFindCorruptedDecks.php?plain=1    -> newline-separated deck ids
//   ...?offset=0&limit=20000     -> scan a slice of the Games folders (page big sets under CF's ~100s)
//   ...?loadDeckBase=http://localhost/TCGEngine   -> where to reach LoadDeck for pass 2 (default localhost)

require_once "./Database/ConnectionManager.php";
include_once './AccountFiles/AccountSessionAPI.php';
include_once './AccountFiles/AccountDatabaseAPI.php';

$plain = isset($_GET['plain']) && ($_GET['plain'] === '1' || $_GET['plain'] === 'true');
if (!$plain) header('Content-Type: application/json');

$error = CheckLoggedInUserMod();
if ($error !== "") { if ($plain) { header('Content-Type: text/plain'); echo "error: $error\n"; } else echo json_encode(['error'=>$error]); exit(); }

set_time_limit(0);
ignore_user_abort(true);
require_once "./SWUDeck/GeneratedCode/GeneratedCardDictionaries.php";

$gamesDir = "./SWUDeck/Games";
$offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;
$limit  = isset($_GET['limit'])  ? max(0, intval($_GET['limit']))  : 0;   // 0 = all
$loadDeckBase = isset($_GET['loadDeckBase']) ? rtrim($_GET['loadDeckBase'], '/') : 'http://localhost/TCGEngine';

// ---- PASS 1 helpers: format-aware Sideboard extraction from the raw file ----
function readZoneP1(array $lines, int &$idx): ?array {
    $n = count($lines); if ($idx >= $n) return null;
    $c1 = intval($lines[$idx++]); if ($c1 < 0 || $idx + $c1 > $n) return null;
    $p1 = []; for ($i=0;$i<$c1;$i++) $p1[] = trim($lines[$idx++]);
    if ($idx >= $n) return null;
    $c2 = intval($lines[$idx++]); if ($c2 < 0 || $idx + $c2 > $n) return null;
    $idx += $c2; return $p1;
}
function isValidSideboardCard(string $id): bool {
    if ($id === '' || $id === '-' || !ctype_digit($id)) return false;
    $t = CardType($id);
    if ($t === null || $t === '' || $t === 'Leader' || $t === 'Base') return false;
    return true;
}
// Returns Sideboard(p1) entries handling old(1 leader-pool)/new(3) layouts, or null if unparseable.
function extractSideboard(string $raw): ?array {
    $lines = explode("\n", $raw);
    if (count($lines) < 2) return null;
    $idx = 2;
    foreach (['Leader','Base','MainDeck','CardPane'] as $_z) if (readZoneP1($lines,$idx)===null) return null;
    if (readZoneP1($lines,$idx)===null) return null;                       // Leaders
    $n = count($lines);
    $peekCount = ($idx < $n) ? intval($lines[$idx]) : 0;
    $peekFirst = ($peekCount>0 && ($idx+1)<$n) ? trim($lines[$idx+1]) : '';
    $peekType = ($peekFirst!=='') ? CardType($peekFirst) : '';
    if ($peekType === 'Leader') { if (readZoneP1($lines,$idx)===null) return null; if (readZoneP1($lines,$idx)===null) return null; } // Leader1,Leader2
    else if ($peekType !== 'Base') return null;                            // unclassifiable
    if (readZoneP1($lines,$idx)===null) return null;                       // Bases
    if (readZoneP1($lines,$idx)===null) return null;                       // Cards
    return readZoneP1($lines,$idx);                                        // Sideboard
}

// ---- PASS 2: authoritative LoadDeck check ----
function loadDeckSideboardHasNull(string $base, string $id): ?bool {
    $url = $base . '/APIs/LoadDeck.php?deckID=' . rawurlencode($id) . '&setId=true';
    $ctx = stream_context_create(['http'=>['timeout'=>15,'ignore_errors'=>true]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) return null;                    // couldn't reach -> unknown
    $j = json_decode($raw, true);
    if (!is_array($j) || isset($j['error'])) return null;
    $sb = $j['sideboard'] ?? [];
    if (!is_array($sb) || count($sb) === 0) return false;
    foreach ($sb as $c) { $cid = $c['id'] ?? null; if ($cid === null || $cid === '') return true; }
    return false;
}

// Deck ids = folder names (no DB list iteration).
$dirs = glob($gamesDir . '/*', GLOB_ONLYDIR);
$allIds = [];
foreach ($dirs as $d) { $b = basename($d); if (ctype_digit($b)) $allIds[] = $b; }
sort($allIds, SORT_NATURAL);
$totalFolders = count($allIds);
$slice = ($limit > 0) ? array_slice($allIds, $offset, $limit) : array_slice($allIds, $offset);

$confirmed = []; $unconfirmed = [];
$counts = ['total_folders'=>$totalFolders,'scanned'=>0,'pass1_candidates'=>0,'confirmed'=>0,'unconfirmed'=>0,'empty'=>0,'ok'=>0,'unparseable'=>0];

foreach ($slice as $id) {
    $counts['scanned']++;
    $file = $gamesDir . '/' . $id . '/Gamestate.txt';
    if (!is_file($file)) { $counts['unparseable']++; continue; }
    $sb = extractSideboard(file_get_contents($file));
    if ($sb === null) { $counts['unparseable']++; continue; }
    if (count($sb) === 0) { $counts['empty']++; continue; }
    $bad = array_filter($sb, fn($c) => !isValidSideboardCard($c));
    if (count($bad) === 0) { $counts['ok']++; continue; }   // includes intact old decks (real sideboard)
    // Pass-1 candidate: file sideboard has garbage.
    $counts['pass1_candidates']++;
    $ld = loadDeckSideboardHasNull($loadDeckBase, $id);
    if ($ld === true) { $confirmed[] = $id; $counts['confirmed']++; }
    else { $unconfirmed[] = $id; $counts['unconfirmed']++; }
}

if ($plain) {
    header('Content-Type: text/plain');
    echo implode("\n", $confirmed) . (count($confirmed) ? "\n" : "");
} else {
    $nextOffset = $offset + count($slice);
    echo json_encode([
        'confirmedCorrupted' => $confirmed,               // both passes agree -> recover from assetversions
        'pass1OnlyUnconfirmed' => $unconfirmed,           // file flagged but LoadDeck didn't confirm (investigate)
        'counts' => $counts,
        'offset' => $offset, 'limit' => $limit,
        'nextOffset' => $nextOffset,                      // pass this as ?offset= for the next page
        'hasMore' => ($nextOffset < $totalFolders),       // false when the whole set has been scanned
        'note' => 'Big sets: page with ?limit=20000 and ?offset=nextOffset until hasMore=false (Cloudflare cuts requests at ~100s).',
    ]);
}
