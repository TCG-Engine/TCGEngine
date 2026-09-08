<?php
// docker exec otmtcge-swustats-web-server-1 php /var/www/html/TCGEngine/DevTools/tdd-regression/test_swudeck_main_deck_copy_limit.php
//
// SWUDeck's main-deck ADD gate must enforce the same copy limit the SAVE-time validator
// (AppCore/SWU/DeckValidation.php's SWUCheckFormat) enforces: the deck's format maxCopies,
// overridden by that format's copyExceptions, counted by CANONICAL printing.
//
// Regression: the gate hardcoded `$cardMax = 3` with one special case keyed on the pre-migration
// FFG UUID "2177194044" (Swarming Vulture Droid). The 2026-08-06 SET_NNN migration re-keyed every
// card to SET_NNN ("JTL_256"), so that comparison became permanently false and the builder capped
// the 15-copy card at 3.

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');
chdir(dirname(__DIR__, 2));

$gameName = 'tdd-regression';

// ── Engine stubs, defined BEFORE the validators load so nothing real is required ────────────────
$GLOBALS['__mainDeck']  = [];
$GLOBALS['__sideboard'] = [];
$GLOBALS['__format']    = 'premier';

function &GetMainDeck($player)  { return $GLOBALS['__mainDeck']; }
function &GetSideboard($player) { return $GLOBALS['__sideboard']; }
function LoadAssetData($player, $gameName) { return ['format' => $GLOBALS['__format']]; }

class _StubCard {
    public $CardID;
    private $removed;
    public function __construct($cardID, $removed = false) { $this->CardID = $cardID; $this->removed = $removed; }
    public function Removed() { return $this->removed; }
}
function _deckOf($cardID, $n) {
    $out = [];
    for ($i = 0; $i < $n; $i++) $out[] = new _StubCard($cardID);
    return $out;
}

// The real card dictionary: SWUDeckCanonicalCardID needs SWUNormalizeDictionaryKey to resolve the
// legacy FFG UUIDs that pre-migration deck files still store.
include_once './SWUDeck/Custom/CardIdentifiers.php';
include_once './SWUDeck/Custom/DeckValidation.php';

$failures = 0; $checks = 0;
$check = function($condition, string $message) use (&$failures, &$checks): void {
    ++$checks;
    $ok = boolval($condition);
    echo ($ok ? 'PASS' : 'FAIL') . ': ' . $message . PHP_EOL;
    if (!$ok) ++$failures;
};

// ── 1. The pure rule: SWUDeckMaxCopies($cardID, $formatId) ──────────────────────────────────────
$check(SWUDeckMaxCopies('JTL_033', 'premier') === 3,  'premier caps an ordinary card at 3');
$check(SWUDeckMaxCopies('JTL_256', 'premier') === 15, 'premier allows 15x Swarming Vulture Droid (JTL_256)');
$check(SWUDeckMaxCopies('JTLW_020', 'premier') === 15, 'the JTLW_020 reprint gets the canonical printing\'s 15-copy limit');
$check(SWUDeckMaxCopies('JTL_256', 'eternal') === 15, 'eternal allows 15x Swarming Vulture Droid');
$check(SWUDeckMaxCopies('JTL_256', 'padawan') === 15, 'padawan (Common-only) keeps the 15-copy exception');
// Unrestricted formats have no ceiling at all — not even the card's own 15. USER RULING 2026-09-08:
// "Open format should allow any and all lists ... no copies min or max."
$check(SWUDeckMaxCopies('JTL_256', 'open') === PHP_INT_MAX, 'open has no copy cap for the vulture');
$check(SWUDeckMaxCopies('JTL_033', 'open') === PHP_INT_MAX, 'open has no copy cap for an ordinary card either');
$check(SWUDeckMaxCopies('JTL_033', 'goldfish') === PHP_INT_MAX, 'the local solo modes are unrestricted too');
$check(SWUDeckMaxCopies('JTL_033', 'twinsuns') === 1, 'twinsuns is highlander: 1 copy of an ordinary card');
$check(SWUDeckMaxCopies('JTL_256', 'twinsuns') === 15, 'twinsuns keeps the vulture exception over its 1-copy default');
$check(SWUDeckMaxCopies('JTL_033', 'nonsense') === 3, 'an unknown format falls back to premier: 3 for an ordinary card');
$check(SWUDeckMaxCopies('JTL_256', 'nonsense') === 15, 'an unknown format still honours printed card text');

// ── 1b. Legacy FFG UUIDs — what pre-migration deck files actually hold ───────────────────────────
$check(function_exists('SWUNormalizeDictionaryKey'), 'the dictionary is loaded, so the UUID checks below mean something');
$check(SWUNormalizeDictionaryKey('2177194044') === 'JTL_256', 'fixture: 2177194044 is Swarming Vulture Droid');
$check(SWUNormalizeDictionaryKey('4236013558') === 'LOF_070', 'fixture: 4236013558 is Anakin Skywalker (LOF_070)');
$check(SWUDeckMaxCopies('2177194044', 'premier') === 15, 'a legacy UUID still resolves to the 15-copy exception');

// ── 2. The gate itself ──────────────────────────────────────────────────────────────────────────
$GLOBALS['__format'] = 'premier';

$GLOBALS['__mainDeck'] = _deckOf('JTL_256', 3); $GLOBALS['__sideboard'] = [];
$check(ValidateMainDeckAddition('JTL_256') === true, 'a 4th Swarming Vulture Droid is accepted');

$GLOBALS['__mainDeck'] = _deckOf('JTL_256', 14);
$check(ValidateMainDeckAddition('JTL_256') === true, 'a 15th Swarming Vulture Droid is accepted');

$GLOBALS['__mainDeck'] = _deckOf('JTL_256', 15);
$check(ValidateMainDeckAddition('JTL_256') === false, 'a 16th Swarming Vulture Droid is rejected');

// The exception must not leak to other cards.
$GLOBALS['__mainDeck'] = _deckOf('JTL_033', 3);
$check(ValidateMainDeckAddition('JTL_033') === false, 'a 4th copy of an ordinary card is still rejected');
$GLOBALS['__mainDeck'] = _deckOf('JTL_033', 2);
$check(ValidateMainDeckAddition('JTL_033') === true,  'a 3rd copy of an ordinary card is still accepted');

// The sideboard still shares the limit, and removed cards still do not count.
$GLOBALS['__mainDeck'] = _deckOf('JTL_033', 2); $GLOBALS['__sideboard'] = _deckOf('JTL_033', 1);
$check(ValidateMainDeckAddition('JTL_033') === false, 'the limit spans main deck + sideboard');
$GLOBALS['__mainDeck'] = array_merge(_deckOf('JTL_033', 2), [new _StubCard('JTL_033', true)]);
$GLOBALS['__sideboard'] = [];
$check(ValidateMainDeckAddition('JTL_033') === true, 'a Removed() copy does not count toward the limit');

// Counting is by CANONICAL printing, exactly like SWUCheckFormat — otherwise the builder accepts a
// deck the save-time validator then rejects.
$GLOBALS['__mainDeck'] = _deckOf('SHD_030', 3); $GLOBALS['__sideboard'] = [];   // reprint of SOR_033
$check(ValidateMainDeckAddition('SOR_033') === false, 'copies of a reprint count toward the canonical card\'s limit');

// A deck can hold BOTH spellings of the same card — legacy UUIDs from before the migration, SET_NNN
// for anything added since — so counting must normalize, not compare raw ids.
$GLOBALS['__mainDeck'] = _deckOf('4236013558', 3); $GLOBALS['__sideboard'] = [];
$check(ValidateMainDeckAddition('LOF_070') === false, 'copies stored as a legacy UUID count toward a SET_NNN add');
$GLOBALS['__mainDeck'] = array_merge(_deckOf('4236013558', 2), _deckOf('LOF_070', 1));
$check(ValidateMainDeckAddition('LOF_070') === false, 'a deck mixing both spellings counts them as one card');

// An Open deck accepts a 4th copy of an ordinary card, which every other format refuses.
$GLOBALS['__format'] = 'open';
$GLOBALS['__mainDeck'] = _deckOf('JTL_033', 3); $GLOBALS['__sideboard'] = [];
$check(ValidateMainDeckAddition('JTL_033') === true, 'open accepts a 4th copy of an ordinary card');
$GLOBALS['__mainDeck'] = _deckOf('JTL_256', 15);
$check(ValidateMainDeckAddition('JTL_256') === true, 'open accepts a 16th vulture droid');

// Twin Suns decks are highlander in the builder too.
$GLOBALS['__format'] = 'twinsuns';
$GLOBALS['__mainDeck'] = _deckOf('JTL_033', 1);
$check(ValidateMainDeckAddition('JTL_033') === false, 'twinsuns rejects a 2nd copy of an ordinary card');
$GLOBALS['__mainDeck'] = [];
$check(ValidateMainDeckAddition('JTL_033') === true, 'twinsuns accepts the 1st copy');

echo ($failures === 0 ? "PASS" : "FAIL") . " ($checks checks, $failures failures)\n";
exit($failures === 0 ? 0 : 1);
