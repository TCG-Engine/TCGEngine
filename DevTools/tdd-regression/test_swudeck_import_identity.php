<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swustats-web-server-1 php DevTools/tdd-regression/test_swudeck_import_identity.php
//
// Guards the identity contract of SWUDeck/Custom/CardIdentifiers.php and the deck import paths:
// everything speaks SET_NNN, and nothing converts back to an FFG UID.
//
// Two distinct regressions, both live on prod on 2026-08-06, both caused by the same thing —
// $titleData was re-keyed from UUID to SET_NNN on 2026-08-04 and its readers were not:
//
//   1. SILENT DEATH. FindCardSetCode()/FindCardMatches() looped `$titleData as $uuid => $title`
//      and returned CardIDLookup($uuid). CardIDLookup is UUID-keyed, so handing it a SET_NNN key
//      returns null — every name-based lookup returned null and melee.gg import plus the swudb
//      HTML-scrape branch of CreateDeck resolved no cards at all, without erroring.
//
//   2. IDENTITY DRIFT. The import paths ended in UUIDLookup(), which STORED the UUID into the
//      deck file and ownership.keyIndicator1/2/3. The 2026-08-06 migration rewrote 105,086 deck
//      files to SET_NNN; every import re-introduced the old identity, so the corpus diverged
//      again immediately. A deck built in the editor was SET_NNN, the same deck imported was not.
//
// READ-ONLY: pure dictionary lookups and a source scan. Nothing is written and no endpoint is
// called, so this cannot touch the prod-clone stats tables.
header('Content-Type: text/plain');
require_once __DIR__ . '/../../SWUDeck/Custom/CardIdentifiers.php';

$checks = [];
$ROOT = __DIR__ . '/../..';

$isSetNnn = fn($v) => is_string($v) && preg_match('/^[A-Z0-9]+_[0-9]{2,3}$|^[A-Z0-9]+_T[0-9]{2}$/', $v) === 1;
$isUuid   = fn($v) => is_string($v) && preg_match('/^\d{10}$/', $v) === 1;

// ── Precondition: the dictionary really is SET_NNN-keyed, and CardIDLookup really does
// ── reject those keys. This is the trap itself — if it ever stops being true, the comments
// ── in CardIdentifiers.php are stale and the reasoning below needs revisiting.
global $titleData;
$checks['precondition: titleData is SET_NNN-keyed']   = isset($titleData['SOR_005']);
$checks['precondition: CardIDLookup rejects SET_NNN'] = CardIDLookup('SOR_005') === null;
$checks['precondition: CardIDLookup accepts a UUID']  = CardIDLookup(UUIDLookup('SOR_033')) === 'SOR_033';

// ── Regression 1: name-based lookup resolves at all ──────────────────────────
// "Administrator's Tower" is a single-printing card, so the expected id is unambiguous.
$checks['FindCardSetCode resolves a name']   = FindCardSetCode("Administrator's Tower") === 'SOR_029';
$checks['FindCardMatches resolves a name']   = in_array('SOR_029', FindCardMatches("Administrator's Tower"), true);
$checks['FindCardMatches yields no nulls']   = !in_array(null, FindCardMatches('Vanquish'), true);
$checks['FindCard resolves a set code']      = FindCard('SOR_033') === ['SOR_033'];

// A reprinted name resolves to SOME real printing — which one depends on dictionary order, so
// assert the shape and that the title actually matches, not a specific id.
$vanquish = FindCardSetCode('Vanquish');
$checks['reprinted name resolves']           = $isSetNnn($vanquish) && ($titleData[$vanquish] ?? '') === 'Vanquish';

// ── Regression 1: leader/base helpers ───────────────────────────────────────
// Exact "Title, Subtitle" is Method 1, so this is deterministic.
$checks['GetLeaderCardID exact match']       = GetLeaderCardID('Luke Skywalker, Faithful Friend') === 'SOR_005';
$checks['GetBaseCardID resolves']            = GetBaseCardID("Administrator's Tower") === 'SOR_029';
$checks['GetLeaderCardID rejects nonsense']  = GetLeaderCardID('Nonexistent Card Qqxzy') === null;

// ── THE REGRESSION THAT MATTERS: nothing here may return an FFG UID ──────────
// Before the fix GetLeaderCardID returned SET_NNN down some branches and a UUID down others,
// and its result is written to meleetournamentdeck.leader — a table the migration re-keyed.
$returned = [
    'FindCardSetCode'  => FindCardSetCode("Administrator's Tower"),
    'GetLeaderCardID'  => GetLeaderCardID('Luke Skywalker, Faithful Friend'),
    'GetBaseCardID'    => GetBaseCardID("Administrator's Tower"),
    'FindCard[0]'      => FindCard('SOR_033')[0] ?? null,
    'FindCardMatches[0]' => FindCardMatches("Administrator's Tower")[0] ?? null,
];
$uuidReturners = array_keys(array_filter($returned, $isUuid));
$checks['no helper returns a 10-digit UUID'] = $uuidReturners === [];
$checks['every helper returns SET_NNN']      = array_keys(array_filter($returned, $isSetNnn)) === array_keys($returned);

// ── Regression 2: the import resolver stores SET_NNN, and still rejects unknowns ─────
$checks['import keeps SET_NNN']              = SWUDeckImportCardID('SOR_033') === 'SOR_033';
$checks['import does NOT return a UUID']     = !$isUuid(SWUDeckImportCardID('SOR_033'));
$checks['import rejects an unknown id']      = SWUDeckImportCardID('zzzzzzz001') === null;
$checks['import rejects null']               = SWUDeckImportCardID(null) === null;
$checks['import rejects empty']              = SWUDeckImportCardID('') === null;
// The unknown-id rejection is load-bearing: a null CardID pushed into a zone renders as a
// broken card image, which is why UUIDLookup survives as an existence test.
$checks['import rejects a UUID as input']    = SWUDeckImportCardID('2579145458') === null;
// TS26 uses 2-digit padding; normalisation must happen before the existence test.
$checks['import normalises TS26 padding']    = SWUDeckImportCardID('TS26_4') === 'TS26_04';
$checks['import leaves 3-digit sets alone']  = SWUDeckImportCardID('SOR_033') === 'SOR_033';

// ── Source scan: the conversion must not come back ───────────────────────────
// Re-derived from source each run rather than asserted once, because the drift was reintroduced
// by five near-identical call sites and any new import path would repeat them.
//
// Comments are stripped first. These files must DISCUSS the old calls to explain why they are
// gone, so a raw substring scan reports a function name appearing in prose as if it were a call.
function _codeOnly($path) {
    $src = @file_get_contents($path);
    if ($src === false) return null;
    $out = '';
    foreach (token_get_all($src) as $t) {
        if (is_array($t)) {
            if ($t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT) continue;
            $out .= $t[1];
        } else $out .= $t;
    }
    return $out;
}

foreach (['SWUDeck/CreateDeck.php', 'SWUDeck/RefreshImport.php'] as $rel) {
    $code = _codeOnly("$ROOT/$rel");
    $checks["$rel does not call UUIDLookup"] = $code !== null && strpos($code, 'UUIDLookup(') === false;
    $checks["$rel uses the import resolver"] = $code !== null && strpos($code, 'SWUDeckImportCardID(') !== false;
}
// CardIdentifiers may still call UUIDLookup — as an existence test — but never CardIDLookup,
// which is the call that silently returned null on every SET_NNN key.
$ciCode = _codeOnly("$ROOT/SWUDeck/Custom/CardIdentifiers.php");
$checks['CardIdentifiers does not call CardIDLookup'] = $ciCode !== null && strpos($ciCode, 'CardIDLookup(') === false;
// ...and the scan is only meaningful if stripping left the real code behind.
$checks['source scan sees real code'] = $ciCode !== null && strpos($ciCode, 'function FindCardSetCode(') !== false;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
if ($fails) {
    echo "FAIL (" . count($fails) . "/" . count($checks) . "):\n";
    foreach ($fails as $f) echo "  - $f\n";
    if ($uuidReturners) echo "  UUID-returning helpers: " . implode(', ', $uuidReturners) . "\n";
} else {
    echo "PASS (" . count($checks) . " checks)\n";
}
