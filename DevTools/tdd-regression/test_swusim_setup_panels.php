<?php
// The main-menu setup modals must be built from REAL data, not the mockup's fixtures.
//
// Three sources feed the four modals:
//   saved decks    -> favoritedeck rows (LoadSavedDecks)
//   Twin Suns      -> SWUSim/Custom/TwinSunsPreCons.json
//   Arenabot       -> SWUSim/Custom/BotDeckLabels.json + Tests/BotFixtures/meta-2026-09/*.txt
//
// The load-bearing assertion in here is not "the list has N rows" — it is that every row's
// `input` string RESOLVES through SWUResolveDeckInput(), the same function the queue uses.
// A pre-con that renders beautifully and cannot be played is the bug this file exists to catch.
//
// Run: curl http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_setup_panels.php
header('Content-Type: text/plain');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

chdir(dirname(__DIR__, 2));
require_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once './SWUSim/Custom/DeckImport.php';
require_once './SWUSim/Custom/SetupPanels.php';

$PASS = 0; $FAIL = 0; $MSGS = [];
function check($name, $cond, $detail = '') {
    global $PASS, $FAIL, $MSGS;
    if ($cond) { $PASS++; return; }
    $FAIL++; $MSGS[] = "FAIL: $name" . ($detail !== '' ? "  [$detail]" : '');
}

// ─── card labels ─────────────────────────────────────────────────────────────
check('SWUSetupCardLabel joins title and subtitle',
    SWUSetupCardLabel('TS26_02') === 'Anakin Skywalker, Protect Her At All Costs',
    SWUSetupCardLabel('TS26_02'));
check('SWUSetupCardLabel omits an absent subtitle',
    SWUSetupCardLabel('TS26_11') === "Executioner's Arena",
    SWUSetupCardLabel('TS26_11'));
check('SWUSetupCardLabel falls back to the id it cannot name',
    SWUSetupCardLabel('NOPE_999') === 'NOPE_999');

// ─── Twin Suns pre-cons ──────────────────────────────────────────────────────
$ts = SWUSetupTwinSunsPreCons();
$tsRaw = json_decode(file_get_contents('./SWUSim/Custom/TwinSunsPreCons.json'), true);
check('every pre-con in the JSON is offered', count($ts) === count($tsRaw),
    count($ts) . ' offered vs ' . count($tsRaw) . ' in the file');
check('more than the single mockup fixture is offered', count($ts) > 1, count($ts) . ' offered');

foreach ($ts as $i => $p) {
    check("TS pre-con $i is named", !empty($p['name']));
    // `author` is OPTIONAL metadata — the file has carried it both ways. The row renderer must
    // simply not print a dangling "by" when it is blank.
    check("TS pre-con $i prints no empty byline",
        empty($p['author'])
            ? strpos(SWUSetupPreConRow('ts-precon', 'ts', $p, true), 'pc__by') === false
            : strpos(SWUSetupPreConRow('ts-precon', 'ts', $p, true), $p['author']) !== false,
        'author=[' . $p['author'] . ']');
    check("TS pre-con $i has TWO leaders", count($p['leaders']) === 2,
        $p['name'] . ': ' . count($p['leaders']));
    check("TS pre-con $i has a base", !empty($p['base']));
    // The whole point of Twin Suns: it must actually resolve through the queue's own importer.
    $r = SWUResolveDeckInput($p['input']);
    check("TS pre-con $i RESOLVES", !empty($r['success']),
        $p['name'] . ': ' . ($r['message'] ?? '?'));
    if (!empty($r['success'])) {
        check("TS pre-con $i resolves to two leaders",
            is_array($r['leader']) && count($r['leader']) === 2,
            $p['name'] . ': ' . json_encode($r['leader']));
        check("TS pre-con $i resolves the card count it advertises",
            count($r['mainDeck']) === $p['count'],
            $p['name'] . ': resolved ' . count($r['mainDeck']) . ' vs advertised ' . $p['count']);
        check("TS pre-con $i leaves nothing unresolved",
            empty($r['unresolved']),
            $p['name'] . ': ' . json_encode($r['unresolved'] ?? []));
    }
}

// ─── Arenabot bot pre-cons ───────────────────────────────────────────────────
$bots = SWUSetupBotPreCons();
$botRaw = json_decode(file_get_contents('./SWUSim/Custom/BotDeckLabels.json'), true);
check('every labelled bot deck is offered', count($bots) === count($botRaw['decks']),
    count($bots) . ' offered vs ' . count($botRaw['decks']) . ' labelled');

$seenStyles = [];
foreach ($bots as $i => $p) {
    $seenStyles[$p['style']] = true;
    check("bot pre-con $i is named", !empty($p['name']));
    check("bot pre-con $i has a human style label", !empty($p['styleLabel']));
    $r = SWUResolveDeckInput($p['input']);
    check("bot pre-con $i RESOLVES", !empty($r['success']),
        $p['name'] . ': ' . ($r['message'] ?? '?'));
    if (!empty($r['success'])) {
        check("bot pre-con $i resolves to its labelled leader",
            (is_array($r['leader']) ? ($r['leader'][0] ?? '') : $r['leader']) === $p['leader'],
            $p['name'] . ': ' . json_encode($r['leader']) . ' vs ' . $p['leader']);
        check("bot pre-con $i resolves to its labelled base", $r['base'] === $p['base'],
            $p['name'] . ": {$r['base']} vs {$p['base']}");
        check("bot pre-con $i resolves the card count it advertises",
            count($r['mainDeck']) === $p['count'],
            $p['name'] . ': resolved ' . count($r['mainDeck']) . ' vs advertised ' . $p['count']);
    }
}
check('bot pre-cons name a style the engine understands',
    empty(array_diff(array_keys($seenStyles),
        ['hyperaggro', 'softaggro', 'midrange', 'softcontrol', 'hardcontrol'])),
    implode(',', array_keys($seenStyles)));
check('the style label is the one the modal select offers',
    SWUSetupStyleLabel('softaggro') === 'Soft Aggro' &&
    SWUSetupStyleLabel('hardcontrol') === 'Hard Control',
    SWUSetupStyleLabel('softaggro') . '/' . SWUSetupStyleLabel('hardcontrol'));

// ─── what may be SAVED ───────────────────────────────────────────────────────
// Owner, 2026-09-25: a pasted JSON blob or a free-text deck list must not be savable. Only a
// link has a source to go back to — a saved blob is a snapshot that can never be re-synced, and
// it cannot be shown in the Deck Link box when the deck is chosen.
check('SWUDeckInputIsLink() exists', function_exists('SWUDeckInputIsLink'));
if (function_exists('SWUDeckInputIsLink')) {
    $savable = [
        'https://swudb.com/deck/eeFFtweXI',
        'https://swudeck.com/decks/abc123',
        'https://melee.gg/Decklist/View/73b660ce-7055-4a3b-bef4-b4bd00482ebd',
        'https://swustats.net/TCGEngine/NextTurn.php?gameName=x&folderPath=SWUDeck',
        'ABCDEFGHIJKL',                       // a bare friendly code
        '  https://swudb.com/deck/eeFFtweXI  ',  // padded
    ];
    foreach ($savable as $s) check('savable: ' . substr($s, 0, 44), SWUDeckInputIsLink($s) === true);

    $notSavable = [
        '{"leader":{"id":"SOR_001","count":1}}',                 // pasted JSON
        "3 | SOR_001\n2 | SOR_002",                              // free-text list
        "Leader: Director Krennic\r\nBase: Echo Base",           // free text, CRLF
        '',                                                      // nothing
        '   ',
        'just some words',
        'SOR_001',                                               // a card id is not a deck
    ];
    foreach ($notSavable as $s)
        check('NOT savable: ' . substr(str_replace(["\n", "\r"], '\\n', $s), 0, 44),
              SWUDeckInputIsLink($s) === false);

    // The predicate must AGREE with the resolver: anything it calls savable has to be a thing
    // SWUResolveDeckInput would actually route to a link importer, or the two drift and we save
    // links that cannot be read back.
    check('a savable input is never treated as JSON by the resolver',
        !SWUDeckInputIsLink('{"leader":{}}'));
}

// The endpoint must enforce it, not just the UI — the UI is not a security boundary and an
// older client will keep POSTing whatever it likes.
// ⚠ A SACRIFICIAL user id, never a real one. While this assertion was red the endpoint did
// exactly what it was asked and wrote a raw: row onto a live account. The row is cleaned up
// below whatever the outcome, so a future regression cannot quietly litter the table either.
const SETUP_TEST_UID = 2147483600;
$GLOBALS['__SAVEDECKS_TEST'] = true;
if (session_status() === PHP_SESSION_NONE) @session_start();
$_SESSION['userid'] = SETUP_TEST_UID;
$_POST = ['action' => 'save', 'deckInput' => '{"leader":{"id":"SOR_001","count":1}}'];
$res = include __DIR__ . '/../../SWUSim/SavedDecks.php';
check('the save endpoint REFUSES pasted JSON', is_array($res) && empty($res['success']),
    json_encode($res));
check('the refusal names the reason', is_array($res) && ($res['error'] ?? '') === 'not_a_link',
    json_encode($res['error'] ?? null));

$_POST = ['action' => 'save', 'deckInput' => "3 | SOR_001\n2 | SOR_002"];
$res = include __DIR__ . '/../../SWUSim/SavedDecks.php';
check('the save endpoint REFUSES a free-text list', is_array($res) && empty($res['success']),
    json_encode($res));

$leftovers = 0;
foreach (LoadSavedDecks(SETUP_TEST_UID) as $row) {
    $leftovers++;
    DeleteSavedDeck(SETUP_TEST_UID, $row['decklink']);
}
check('the refused saves wrote NOTHING to the database', $leftovers === 0,
    "$leftovers row(s) had to be cleaned up");

// ─── saved decks ─────────────────────────────────────────────────────────────
// A guest has no rows. The modal must render an empty state rather than the mockup's
// three invented decks, which is exactly what this asserts.
check('a guest has no saved decks', SWUSetupSavedDecks(0) === []);

// ─── last deck used (owner, 2026-09-25) ──────────────────────────────────────
// "Auto-fill the deck link with the last deck used; if that deck is no longer available, leave
// it blank." Remembered in BOTH places — the account when logged in, this browser otherwise —
// with the account authoritative on read so the two can never meaningfully disagree.
check('SetLastDeck() exists', function_exists('SetLastDeck'));
check('LoadLastDeck() exists', function_exists('LoadLastDeck'));

if (function_exists('SetLastDeck') && function_exists('LoadLastDeck')) {
    $L = SETUP_TEST_UID;
    DeleteLastDeck($L);
    check('a user with no history has no last deck', LoadLastDeck($L) === null);

    check('a deck link is remembered',
        SetLastDeck($L, 'https://swudb.com/deck/eeFFtweXI', 'premier', 1, 'Krennic Blue') === true);
    $row = LoadLastDeck($L);
    check('the remembered deck comes back', is_array($row) && $row['deckInput'] === 'https://swudb.com/deck/eeFFtweXI',
        json_encode($row));
    check('its format comes back', ($row['format'] ?? '') === 'premier', $row['format'] ?? '');
    check('its leader count comes back', (int)($row['leaders'] ?? 0) === 1, json_encode($row['leaders'] ?? null));
    check('its name comes back', ($row['deckName'] ?? '') === 'Krennic Blue', $row['deckName'] ?? '');

    // ONE row per user: playing a second deck replaces the first, it does not accumulate.
    SetLastDeck($L, 'https://swudb.com/deck/kWzBQPfCopFMV', 'twinsuns', 2, 'Jabba Combo');
    $row = LoadLastDeck($L);
    check('the newest deck replaces the previous one',
        is_array($row) && $row['deckInput'] === 'https://swudb.com/deck/kWzBQPfCopFMV', json_encode($row));
    check('replacing carries the new leader count', (int)($row['leaders'] ?? 0) === 2, json_encode($row));

    // Same rule as saving: only a LINK has a source to return to, and only a link can be shown
    // in the Deck Link box — which is the entire point of the feature.
    check('a pasted JSON deck is NOT remembered',
        SetLastDeck($L, '{"leader":{"id":"SOR_001","count":1}}', 'open', 1, 'x') === false);
    check('a free-text list is NOT remembered',
        SetLastDeck($L, "3 | SOR_001\n2 | SOR_002", 'open', 1, 'x') === false);
    check('a refused write leaves the previous deck alone',
        (LoadLastDeck($L)['deckInput'] ?? '') === 'https://swudb.com/deck/kWzBQPfCopFMV');

    // A guest has no account row to write to — that is what localStorage is for.
    check('a guest is not written to the database', SetLastDeck(0, 'https://swudb.com/deck/eeFFtweXI', 'premier', 1, 'x') === false);
    check('a guest has no last deck', LoadLastDeck(0) === null);

    check('forgetting removes it', DeleteLastDeck($L) === true && LoadLastDeck($L) === null);
}

// ─── the rendered markup ─────────────────────────────────────────────────────
// Whatever the source, a row must carry the string the submission will send. Without it the
// picker is decoration: it changes what you SEE and not what you play.
$pick = SWUSetupDeckPicker('pvp-saved', [
    ['key' => 'k1', 'name' => 'A deck', 'leaders' => ['TS26_02'], 'base' => 'TS26_11',
     'count' => 50, 'input' => 'https://swudb.com/deck/abc'],
]);
check('a picker carries its deck input', strpos($pick, 'https://swudb.com/deck/abc') !== false);
check('a picker keeps the id the page asked for', strpos($pick, 'id="pvp-saved"') !== false);
check('a picker escapes its markup', strpos(
    SWUSetupDeckPicker('x', [['key' => 'x', 'name' => '<script>x</script>', 'leaders' => [],
                              'base' => '', 'count' => 0, 'input' => '']]), '<script>x') === false);
// An empty picker renders NO <select> at all — not a disabled one. A dropdown a guest cannot
// use still reads as a control that should do something, and the listbox enhancement dressed
// it into a blank bar. It must offer no deck either way.
$empty = SWUSetupDeckPicker('e', []);
check('an empty picker offers no deck', strpos($empty, 'data-deck-input') === false);
check('an empty picker renders no dropdown', strpos($empty, '<select') === false);
check('an empty picker is marked for the enhancer to skip', strpos($empty, 'data-empty') !== false);
check('an empty picker says why it is empty', strpos($empty, 'No saved decks yet') !== false);

// Every pre-con row must carry its deck input too — the Twin Suns and Arenabot wells are the
// only way to play those decks, so a row without one is a dead control.
foreach ($ts as $p) {
    $row = SWUSetupPreConRow('ts-precon', 'ts', $p, true);
    check('TS row "' . $p['name'] . '" carries its deck input',
        strpos($row, 'data-deck-input="' . htmlspecialchars($p['input'], ENT_QUOTES, 'UTF-8') . '"') !== false);
}
foreach ($bots as $p) {
    $row = SWUSetupPreConRow('ab-precon', 'ab', $p, false);
    check('bot row "' . $p['name'] . '" carries its deck input',
        strpos($row, 'data-deck-input="' . htmlspecialchars($p['input'], ENT_QUOTES, 'UTF-8') . '"') !== false);
    check('bot row "' . $p['name'] . '" shows its style', strpos($row, $p['styleLabel']) !== false);
}

echo ($FAIL === 0 ? "ALL GREEN" : "$FAIL FAILED") . " — $PASS passed, $FAIL failed\n";
foreach ($MSGS as $m) echo "  $m\n";
