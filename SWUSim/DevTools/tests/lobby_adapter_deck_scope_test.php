<?php
// A lobby endpoint that reaches SWUSim ONLY through LobbyAdapterFor() must still be able to read a
// deck.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/lobby_adapter_deck_scope_test.php
//
// ─── WHY THIS TEST EXISTS (prod, 2026-09-22) ────────────────────────────────────────────────────
// "Change Deck" in a Twin Suns room rejected a perfectly legal list with
//     twinsuns requires exactly 2 leaders; found 1.; Deck has 79 cards; twinsuns minimum is 80…
// while the SAME link loaded fine when the room was CREATED. Two independent defects stacked:
//
//   1. LobbyAdapterFor() `require`s the adapter FROM FUNCTION SCOPE. A required file's top-level
//      `$var = ...` binds to the INCLUDING SCOPE, so SWUSim/LobbyAdapter.php -> Custom/DeckImport.php
//      -> GeneratedCardDictionaries.php assigned $titleData (and every other dictionary) into
//      LobbyAdapterFor()'s LOCALS, which vanish on return. JoinQueue.php happens to include
//      DeckImport.php at global scope first, so it never saw this; UpdateLobbyDeck.php does not, and
//      ran the whole deck importer against an EMPTY dictionary.
//   2. With no dictionary, SWUIsAcceptableCardID() falls back to a regex — and that regex demanded a
//      LETTERS-ONLY set code (`[A-Z]{2,5}`). Two whole sets have digits in their code: TS26 (the Twin
//      Suns set, 88 cards, 2-digit card numbers) and IC27 (19). So the Twin Suns leader TS26_01 and
//      the deck card TS26_56 were silently dropped -> "found 1" leader, "79 cards".
//      DecomposeCardID() in the generated dictionaries already had the right grammar: `[A-Z0-9]{2,5}`.
//
// ⚠ THE INCLUDE SET BELOW IS THE TEST. It deliberately mirrors APIs/Lobbies/UpdateLobbyDeck.php and
// must NOT require SWUSim/LobbyAdapter.php (or any dictionary) directly — doing so sets the globals
// by hand and the guard silently stops guarding. SWUSim/DevTools/tests/lobby_adapter_test.php loads
// the adapter that way on purpose, which is exactly why it could never catch this.
error_reporting(E_ALL & ~E_DEPRECATED);

$FAILS = 0;
function check($cond, $msg) {
    global $FAILS;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . "\n";
    if (!$cond) $FAILS++;
}

$root = __DIR__ . '/../../..';
require_once $root . '/APIs/Lobbies/Classes/Player.php';
require_once $root . '/APIs/Lobbies/Classes/TeamRooms.php';
require_once $root . '/APIs/Lobbies/Classes/LobbyAdapter.php';

$adapter = LobbyAdapterFor('SWUSim');
check($adapter instanceof LobbyAdapter, 'LobbyAdapterFor resolves the SWUSim adapter');
if ($adapter === null) { echo "\n1 FAILED\n"; exit(1); }

echo "── the adapter's include chain reaches global scope ──\n";
// The dictionaries are GLOBALS, read via `global $titleData` from inside IsSWUCardID/CardTitle/…, so
// "did the include land at global scope" is exactly "is $GLOBALS['titleData'] populated".
check(isset($GLOBALS['titleData']) && is_array($GLOBALS['titleData']) && count($GLOBALS['titleData']) > 1000,
      'the card dictionary is visible as a GLOBAL after LobbyAdapterFor()');
check(function_exists('IsSWUCardID') && IsSWUCardID('TS26_01'),
      'IsSWUCardID sees a real card (TS26_01) through the adapter-only include path');

echo "── SWUIsAcceptableCardID accepts every real set code ──\n";
// Digit-bearing set codes are the whole point; the letters-only cases are here so a widened regex
// cannot quietly start accepting junk.
foreach (['TS26_01', 'IC27_001', 'SOR_014', 'LAW_008', 'ASH_023'] as $id) {
    check(SWUIsAcceptableCardID($id), "accepts $id");
}
foreach (['', 'TS26_', '_001', 'NotACard', 'sor_014', 'TS26_123456'] as $id) {
    check(!SWUIsAcceptableCardID($id), "rejects '" . $id . "'");
}
// …and the fallback alone must be right, dictionary or not: that is the branch prod actually ran.
// (Checked by asking about an id the dictionary cannot possibly hold, in the same SET_NNN shape.)
check(SWUIsAcceptableCardID('TS26_99'), 'the no-dictionary REGEX fallback accepts a TS26-shaped id');

echo "── a Twin Suns deck whose leader is a TS26 card validates ──\n";
// The exact list from the prod report (swudb.com/deck/cUAfVMNYv), on disk: TS26_01 + LAW_008 over 80
// singleton cards, one of which (TS26_56) also carries the digit-bearing set code. Local file, not a
// fetch — the network is not what is under test.
$deck = trim(file_get_contents(__DIR__ . '/fixtures/twinsuns_ts26_deck.json'));
$lobby = new stdClass();
$lobby->rootName  = 'SWUSim';
$lobby->format    = 'twinsuns';
$lobby->isPrivate = true;
$v = $adapter->validateDeck($lobby, $deck);
check(!empty($v['ok']), 'validateDeck accepts the TS26-leader Twin Suns deck: ' . ($v['message'] ?: 'no message'));

$leaders = [];
$base    = '';
foreach (($v['identity']['cards'] ?? []) as $c) {
    if ($c['kind'] === 'leader')                   $leaders[] = $c['id'];
    elseif ($c['kind'] === 'base' && $base === '') $base      = $c['id'];
}
check($leaders === ['TS26_01', 'LAW_008'], 'both leaders survive, in order: ' . json_encode($leaders));
check($base === 'ASH_023', "the base is ASH_023, got '$base'");

// The identity strip is the second casualty of an empty dictionary: CardTitle() returns nothing and
// _identityCards falls back to the raw id, so every roster tile would read "TS26_01" with a grey
// ring. A NAME that differs from the id proves the dictionary was actually readable.
$first = ($v['identity']['cards'][0] ?? ['id' => '', 'name' => '', 'colors' => []]);
check($first['name'] !== '' && $first['name'] !== $first['id'],
      "the roster identity carries a real card NAME, not the id: '" . $first['name'] . "'");
check(!empty($first['colors']) && $first['colors'] !== ['#4b5b6d'],
      'the roster identity carries real aspect colours, not the neutral fallback');

echo $FAILS === 0 ? "\nALL PASS\n" : "\n$FAILS FAILED\n";
exit($FAILS === 0 ? 0 : 1);
