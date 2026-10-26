<?php
// Profile → Saved Decks → "Import decks": several deck links saved to the account in one go (owner, 2026-10-26).
// The rows post one at a time to the SAME save action the menu uses, so the links-only rule and the
// name lookup cannot drift. What this pins: the block is rendered by the panel production calls, only
// where the site opts in; the library still renders a dropdown to import INTO when it is empty; and the
// save reply carries the deck's name so the dropdown can show it without a reload.
// ⚠ The live-save case fetches a real swudb deck (network) and writes to a SACRIFICIAL user id,
// cleaned up before and after.
// Run: docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_profile_deck_import.php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
chdir(dirname(__DIR__, 2));
require_once './Database/ConnectionManager.php';
require_once './Database/functions.inc.php';
require_once './SharedUI/Render/SiteDef.php';
require_once './SharedUI/Render/Profile.php';
require_once './SharedUI/Render/DeckLibrary.php';

const IMPORT_TEST_UID = 2147483601;
const IMPORT_TEST_LINK = 'https://swudb.com/deck/eeFFtweXI';
$PASS = 0; $FAIL = 0; $MSGS = [];
function check($name, $cond, $detail = '') { global $PASS, $FAIL, $MSGS; if ($cond) { $PASS++; return; } $FAIL++; $MSGS[] = "FAIL: $name" . ($detail !== '' ? "  [$detail]" : ''); }
$cleanup = function () { foreach (LoadSavedDecks(IMPORT_TEST_UID) as $r) DeleteSavedDeck(IMPORT_TEST_UID, $r['decklink']); };
$cleanup();

try {
    // ─── the block, as the profile panel renders it ──────────────────────────
    $def = LoadSiteDef('SWUSim');
    $panel = _ProfilePanelRegistry()['savedDecks'];
    check('SWUSim opts in', !empty($def['profile']['deckImport']));
    $html = $panel($def, ['userId' => IMPORT_TEST_UID], []);

    check('an import block is rendered', strpos($html, "class='dl-import'") !== false, substr($html, 0, 300));
    check('it starts with ONE link box', substr_count($html, "class='dl-import-input'") === 1);
    check('the box asks for a deck link', strpos($html, 'placeholder="Paste a deck link"') !== false);
    check('a plus button adds another', strpos($html, "class='dl-import-add'") !== false);
    check('the plus button says "Add another"', (bool)preg_match("#class='dl-import-add'[^>]*>.*Add another.*</button>#s", $html));
    check('an Import & Save button', (bool)preg_match("#class='dl-import-save'[^>]*>Import &amp; Save</button>#", $html));
    check('the cap is 10 boxes', strpos($html, "data-max='10'") !== false);
    check('it posts to the site\'s save endpoint', strpos($html, 'SWUSim/SavedDecks.php') !== false);
    check('the rows post the existing save action', strpos($html, "'action=save&deckInput='") !== false);
    check('the wiring runs once per page', strpos($html, '__deckImportWired') !== false);
    check('results are announced', strpos($html, "aria-live='polite'") !== false);

    // ⚠ The empty library used to render ONLY its "no saved decks" line — no dropdown — so a
    // first import would have had nothing to appear in.
    check('an EMPTY library still renders a dropdown', strpos($html, "class='dl-select'") !== false);
    check('…with its management buttons', strpos($html, 'data-action="delete"') !== false);
    check('…and still says it is empty', strpos($html, "<div class='deck-library-empty'>") !== false);

    $off = $def; unset($off['profile']['deckImport']);
    $plain = $panel($off, ['userId' => IMPORT_TEST_UID], []);
    check('a site that does not opt in gets no import block', strpos($plain, 'dl-import') === false);
    check('…and keeps the old empty state', strpos($plain, "class='dl-select'") === false);

    // ─── the save reply names the deck ───────────────────────────────────────
    $GLOBALS['__SAVEDECKS_TEST'] = true;
    if (session_status() === PHP_SESSION_NONE) @session_start();
    $_SESSION['userid'] = IMPORT_TEST_UID;
    $_POST = ['action' => 'save', 'deckInput' => IMPORT_TEST_LINK];
    $res = include './SWUSim/SavedDecks.php';
    check('the live save succeeds (needs network)', is_array($res) && !empty($res['success']), json_encode($res['error'] ?? $res));
    $row = null;
    foreach (LoadSavedDecks(IMPORT_TEST_UID) as $r) if ($r['decklink'] === ($res['decklink'] ?? '')) $row = $r;
    check('the reply carries a name', is_array($res) && array_key_exists('name', $res), json_encode(array_keys((array)$res)));
    check('…the name that was stored', $row !== null && ($res['name'] ?? null) === $row['name'],
        json_encode([$res['name'] ?? null, $row['name'] ?? null]));
    check('…and it is not blank', trim((string)($res['name'] ?? '')) !== '');
} finally {
    $cleanup();
}

echo "PASS=$PASS FAIL=$FAIL\n";
foreach ($MSGS as $m) echo $m . "\n";
echo $FAIL === 0 ? "PASS: test_profile_deck_import\n" : "FAIL: test_profile_deck_import\n";
