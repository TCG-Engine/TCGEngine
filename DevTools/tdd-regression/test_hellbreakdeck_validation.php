<?php

// HellbreakDeck's add-validators must FAIL CLOSED on an unknown card type.
//
// 2026-08-17: an empty card dictionary made CardType() return '' for every card. The two key-card
// validators use POSITIVE tests (`$type !== 'monster'` -> reject), so they rejected everything. The
// main-deck validator used a NEGATIVE test:
//
//     return $type !== 'monster' && $type !== 'location';   // '' passes
//
// so it accepted everything. The builder therefore half-worked for three days: regular cards added
// fine, Monster/Location silently refused, and nothing in the logs said "the dictionary is empty".
// Had the main-deck path rejected '' too, the very first click on ANY card would have failed and
// pointed straight at the card data.

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');
chdir(dirname(__DIR__, 2));

$gameName = 'tdd-regression';   // the validators read this as a global for SetAssetKeyIdentifier

// Stub the dictionary BEFORE loading the validators, so the type under test is the only variable.
// The real GeneratedCardDictionaries.php is never included here -- that is what makes it a stub.
$GLOBALS['__stubCardType'] = '';
function CardType($cardID) { return $GLOBALS['__stubCardType']; }

include_once './HellbreakDeck/Custom/DeckValidation.php';

$failures = 0;
$checks = 0;
$check = function($condition, string $message) use (&$failures, &$checks): void {
    ++$checks;
    $ok = boolval($condition);
    echo ($ok ? 'PASS' : 'FAIL') . ': ' . $message . PHP_EOL;
    if(!$ok) ++$failures;
};

// DOT_006 (Jaws) has real art: 23,964 bytes, well over the 8000 gate. Assert that up front --
// otherwise every rejection below could be the IMAGE gate firing and the type tests would pass
// while proving nothing.
$card = 'DOT_006';
$check(HellbreakDeckHasValidImage($card), "fixture card $card passes the art gate, so type is the only variable");

// ---------------------------------------------------------------------------
// The regression: an unknown type must be rejected, not waved through.
// ---------------------------------------------------------------------------
$GLOBALS['__stubCardType'] = '';
$check(ValidateMainDeckAddition($card) === false,
    'an EMPTY card type is rejected from the main deck (fails closed)');

$GLOBALS['__stubCardType'] = '   ';
$check(ValidateMainDeckAddition($card) === false,
    'a whitespace-only card type is rejected from the main deck');

// ---------------------------------------------------------------------------
// ...without breaking the types that legitimately belong in the main deck.
// ---------------------------------------------------------------------------
foreach (['Minion', 'Asset', 'Event'] as $type) {
    $GLOBALS['__stubCardType'] = $type;
    $check(ValidateMainDeckAddition($card) === true, "a $type is still accepted into the main deck");
}

// Key cards stay excluded from the main deck — they have their own slots.
foreach (['Monster', 'Location'] as $type) {
    $GLOBALS['__stubCardType'] = $type;
    $check(ValidateMainDeckAddition($card) === false, "a $type is still excluded from the main deck");
}

// Casing must not decide the outcome: the dictionary ships 'Monster', the validators compare
// lowercase, and a future data source could ship either.
$GLOBALS['__stubCardType'] = 'MONSTER';
$check(ValidateMainDeckAddition($card) === false, 'type matching stays case-insensitive');

if($failures > 0) {
    fwrite(STDERR, PHP_EOL . "FAILED: {$failures} of {$checks} checks." . PHP_EOL);
    exit(1);
}
echo PHP_EOL . "ALL PASS ({$checks} checks)" . PHP_EOL;

?>
