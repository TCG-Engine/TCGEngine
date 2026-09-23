<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swudeck-web-server-1 php DevTools/tdd-regression/test_deck_link_import.php
//
// Guards AppCore/SWU/DeckLinkImport.php — the deck-builder link import shared by SWUDeck
// (CreateDeck / RefreshImport) and SWUSim (DeckImport):
//   * every supported link shape maps to the right source + deck id, and look-alike paths don't;
//   * ids round-trip through ownership.assetSourceID (varchar 32): UUIDs lose their dashes to fit
//     and get them back for the fetch (swumetastats 404s on a dashless UUID);
//   * the melee.gg scraper reads leader/base from their categories, falls back to the page title,
//     splits sideboard from main deck, and reports names it could not resolve;
//   * the three import entry points actually route through the shared code.
//
// OFFLINE: no endpoint is called and nothing is written. Uses SWUDeck's dictionary (the scraper
// reads whichever dictionary the caller loaded).
header('Content-Type: text/plain');
require_once __DIR__ . '/../../SWUDeck/Custom/CardIdentifiers.php';
require_once __DIR__ . '/../../AppCore/SWU/DeckLinkImport.php';

$checks = [];
$ROOT = __DIR__ . '/../..';

// ── Link parsing ────────────────────────────────────────────────────────────
$uuid = '037352e4-01b6-497f-9a1a-b4c301070f7d';
$links = [
    ["https://melee.gg/Decklist/View/$uuid",                         SWU_DECK_SOURCE_MELEE,         $uuid],
    ["https://swubase.com/decks/$uuid",                              SWU_DECK_SOURCE_SWUBASE,       $uuid],
    ['https://protectthepod.com/pool/Ab_3-x9/deck/play',             SWU_DECK_SOURCE_PROTECTTHEPOD, 'Ab_3-x9'],
    ['https://swucardhub.fr/Karabast/1234',                          SWU_DECK_SOURCE_SWUCARDHUB,    '1234'],
    ['https://swucardhub.fr/deckview?deckId=5678',                   SWU_DECK_SOURCE_SWUCARDHUB,    '5678'],
    ['https://swuforge.com/decks/abcDEF123',                         SWU_DECK_SOURCE_SWUFORGE,      'abcDEF123'],
    ["https://www.swumetastats.com/decklists/$uuid?format=premier",  SWU_DECK_SOURCE_SWUMETASTATS,  $uuid],
    ['https://sw-unlimited-db.com/decks/162905',                     SWU_DECK_SOURCE_SWUNLIMITEDDB, '162905'],
    ['https://swudb.com/deck/eeFFtweXI',                             SWU_DECK_SOURCE_SWUDB,         'eeFFtweXI'],
];
foreach ($links as [$link, $source, $id]) {
    $p = SWUDeckLinkParse($link);
    $checks["parse $link"] = $p === ['source' => $source, 'id' => $id];
}
// sw-unlimited-db.com must not be mistaken for swudb.com (and vice versa).
$checks['sw-unlimited-db is not swudb'] = SWUDeckLinkParse('https://sw-unlimited-db.com/decks/1')['source'] === SWU_DECK_SOURCE_SWUNLIMITEDDB;
// A recognised host with no deck id yields an empty id, not a wrong one.
$checks['host without id → empty id']   = SWUDeckLinkParse('https://swucardhub.fr/cardlist') === ['source' => SWU_DECK_SOURCE_SWUCARDHUB, 'id' => ''];
$checks['unknown host → null']          = SWUDeckLinkParse('https://example.com/decks/1') === null;
$fail = SWUDeckLinkFetch('https://swucardhub.fr/cardlist');
$checks['fetch of id-less link fails cleanly'] = $fail['success'] === false && $fail['message'] !== '' && $fail['deck'] === null;
$checks['fetch-by-source rejects unsafe id']   = SWUDeckLinkFetchBySource(SWU_DECK_SOURCE_SWUFORGE, '../x?y')['success'] === false;
$checks['fetch-by-source rejects unknown source'] = SWUDeckLinkFetchBySource(99, 'abc')['success'] === false;

// ── Stored id (ownership.assetSourceID is varchar 32) ───────────────────────
$stored = SWUDeckLinkStoredID($uuid);
$checks['uuid stored dashless, 32 chars'] = $stored === str_replace('-', '', $uuid) && strlen($stored) === 32;
$checks['dashless uuid re-hyphenated for fetch'] = _SWUDeckLinkApiID($stored) === $uuid;
$checks['short id stored as-is']        = SWUDeckLinkStoredID('162905') === '162905';
$checks['short id fetched as-is']       = _SWUDeckLinkApiID('162905') === '162905';
$checks['over-long id not stored (no truncation)'] = SWUDeckLinkStoredID(str_repeat('a', 33)) === null;

// ── melee.gg scraper ────────────────────────────────────────────────────────
$record = fn($qty, $name) => "<div class=\"decklist-record\"><span class=\"decklist-record-quantity\">$qty</span><a class=\"decklist-record-name\" href=\"#\">$name</a></div>";
$category = fn($title, $records) => "<div class=\"decklist-category\"><div class=\"decklist-category-title\">$title</div>" . implode('', $records) . "</div>";
// ⚠ The <meta charset> is not decoration. DOMDocument::loadHTML() assumes ISO-8859-1 when a document
// declares nothing, so a UTF-8 card name arrives mangled ("Mesa Propose…" -> "Mesa Proposeâ€¦") and
// resolves to the wrong printing. The real melee.gg page DOES declare utf-8, so a fixture without it
// fails for a reason production never hits — which is exactly what happened when these cases were
// first written.
$page = fn($title, $cats) => "<html><head><meta charset=\"utf-8\"></head><body><div class=\"decklist-title\">$title</div>" . implode('', $cats) . "</body></html>";

$html = $page("Director Krennic, Amidst My Achievement - Daimyo&#39;s Palace", [
    $category('Leader (1)', [$record(1, 'Director Krennic | Amidst My Achievement')]),
    $category('Base (1)', [$record(1, "Daimyo's Palace")]),
    $category('Ground Unit (5)', [$record(3, 'Zeb Orellios | Spectre Four'), $record(2, 'Qqxzy Nonexistent')]),
    $category('Space Unit (2)', [$record(2, 'A-Wing')]),
    $category('Sideboard (1)', [$record(1, 'Vanquish')]),
]);
$unresolved = [];
$d = SWUMeleeDeckFromHtml($html, $unresolved);
$checks['melee: leader from category']  = ($d['leader']['id'] ?? null) === 'LAW_008';
$checks['melee: base from category']    = ($d['base']['id'] ?? null) === 'LAW_020';
$checks['melee: deck name']             = ($d['metadata']['name'] ?? '') === "Director Krennic, Amidst My Achievement - Daimyo's Palace";
$checks['melee: Zeb alias → LAW_045 x3'] = in_array(['id' => 'LAW_045', 'count' => 3], $d['deck'] ?? [], true);
$checks['melee: exact title beats substring (A-Wing → SEC_213)'] = in_array(['id' => 'SEC_213', 'count' => 2], $d['deck'] ?? [], true);
$checks['melee: main deck has only resolved cards'] = count($d['deck'] ?? []) === 2;
$checks['melee: sideboard split out']   = count($d['sideboard'] ?? []) === 1 && ($d['sideboard'][0]['count'] ?? 0) === 1;
$checks['melee: unresolved name reported'] = $unresolved === ['Qqxzy Nonexistent'];
$checks['melee: no secondleader for one leader'] = !isset($d['secondleader']);

// No Leader/Base categories → fall back to the page title.
$d2 = SWUMeleeDeckFromHtml($page("Director Krennic | Amidst My Achievement - Daimyo's Palace", [
    $category('Ground Unit (3)', [$record(3, 'Zeb Orellios | Spectre Four')]),
]));
$checks['melee: leader from title fallback'] = ($d2['leader']['id'] ?? null) === 'LAW_008';
$checks['melee: base from title fallback']   = ($d2['base']['id'] ?? null) === 'LAW_020';

$checks['melee: page without a decklist → null'] = SWUMeleeDeckFromHtml('<html><body>Not found</body></html>') === null;

// ⚠ PUNCTUATION DRIFT IN THE SUBTITLE. melee.gg writes a real U+2026 ellipsis ("Mesa Propose…");
// the card dictionary spells the same subtitle with three ASCII periods ("Mesa Propose..."). The
// subtitle comparison was a raw string equality, so it missed, fell through to the title-only retry,
// and returned whichever of the FOUR "Jar Jar Binks" printings sits first in the dictionary —
// TWI_202 'Foolish Gungan' instead of SEC_111. A wrong printing imported silently, with the card
// never appearing in $unresolved.
//
// Titles already survived this class (SWURankCardTitleMatches tier 2 compares a non-alphanumeric-
// stripped form); subtitles had no equivalent. Reported by the owner on decklist
// fe49d18b-875d-44b7-b53d-b4ca007b6b00, reproduced against the live page.
$ell = json_decode('"…"');   // U+2026 HORIZONTAL ELLIPSIS, as melee.gg serves it
$dJar = SWUMeleeDeckFromHtml($page('Ahsoka Tano, Snips - Fortress of the Great Mothers', [
    $category('Ground Unit (6)', [
        $record(3, 'Jar Jar Binks | Mesa Propose' . $ell),      // melee's spelling
        $record(3, 'Grand Admiral Thrawn | ' . $ell . 'How Unfortunate'),
    ]),
]));
$checks['melee: U+2026 subtitle resolves the right printing (SEC_111, not TWI_202)']
    = in_array(['id' => 'SEC_111', 'count' => 3], $dJar['deck'] ?? [], true);
$checks['melee: leading-ellipsis subtitle too (JTL_002)']
    = in_array(['id' => 'JTL_002', 'count' => 3], $dJar['deck'] ?? [], true);
// The ASCII spelling must keep working — the fix widens the match, it does not move it.
$dAscii = SWUMeleeDeckFromHtml($page('Ahsoka Tano, Snips - Fortress of the Great Mothers', [
    $category('Ground Unit (3)', [$record(3, 'Jar Jar Binks | Mesa Propose...')]),
]));
$checks['melee: ASCII "..." subtitle still resolves to SEC_111']
    = in_array(['id' => 'SEC_111', 'count' => 3], $dAscii['deck'] ?? [], true);
// A subtitle that matches NOTHING must still fall back to the title, not to a stripped near-miss.
$checks['melee: unknown subtitle still falls back to a printing of the title']
    = ($id = SWUFindCardIdByName('Jar Jar Binks | Not A Real Subtitle')) !== null
      && in_array($id, ['TWI_202', 'SEC_111', 'HMW_005', 'IC27_187'], true);

// ── Entry points route through the shared importer ──────────────────────────
function _codeOnlyDL($path) {
    $src = @file_get_contents($path);
    if ($src === false) return null;
    $out = '';
    foreach (token_get_all($src) as $t) {
        if (is_array($t)) { if ($t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT) continue; $out .= $t[1]; }
        else $out .= $t;
    }
    return $out;
}
$create  = _codeOnlyDL("$ROOT/SWUDeck/CreateDeck.php");
$refresh = _codeOnlyDL("$ROOT/SWUDeck/RefreshImport.php");
$sim     = _codeOnlyDL("$ROOT/SWUSim/Custom/DeckImport.php");
$checks['CreateDeck uses SWUDeckLinkFetch']            = $create !== null && strpos($create, 'SWUDeckLinkFetch(') !== false;
$checks['CreateDeck no longer scrapes melee inline']   = $create !== null && strpos($create, 'DOMDocument') === false;
$checks['RefreshImport uses SWUDeckLinkFetchBySource'] = $refresh !== null && strpos($refresh, 'SWUDeckLinkFetchBySource(') !== false;
$checks['SWUSim DeckImport uses SWUDeckLinkFetch']     = $sim !== null && strpos($sim, 'SWUDeckLinkFetch(') !== false;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
if ($fails) {
    echo "FAIL (" . count($fails) . "/" . count($checks) . "):\n";
    foreach ($fails as $f) echo "  - $f\n";
} else {
    echo "PASS (" . count($checks) . " checks)\n";
}
