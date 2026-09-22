<?php
// EditDeckCard accepts a cardID from an external caller and stores it with no normalisation. Publishing
// latest-printing ids makes it far likelier to be exercised, because a consumer will now naturally echo
// back the printing we just exported. Fold to canonical on the way in — CardIDOverride is idempotent,
// so a caller sending the canonical id is unaffected.
$root = realpath(__DIR__ . '/../..');
$code = preg_replace('~//[^\n]*~', '', file_get_contents($root . '/APIs/EditDeckCard.php'));
$checks = [];

$checks['EditDeckCard normalizes input and stored ids'] = substr_count($code, 'SWUDeckEditCardID(') >= 2;
$checks['EditDeckCard loads shared identity helper'] = strpos($code, 'DeckEditCardID.php') !== false;
// It must NOT reach for the display map — this is a write path, and the two directions must not mix.
$checks['write path never uses the display map'] = strpos($code, 'SWUDisplayCardID') === false;

// Behavioural: the fold must actually move a latest-printing id back to canonical, and be idempotent.
require_once $root . '/SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
require_once $root . '/AppCore/SWU/Overrides.php';
require_once $root . '/AppCore/SWU/DeckEditCardID.php';
// The generated gamestate parser includes DeckValidation.php (plain include), so
// the identity helper must not load it first or the live endpoint redeclares its functions.
$checks['identity helper leaves deck validation for gamestate parser'] = strpos(file_get_contents($root . '/AppCore/SWU/DeckEditCardID.php'), 'DeckValidation.php') === false;
require_once $root . '/SWUDeck/Custom/DeckValidation.php';
$checks['LOF_164 folds back to SOR_164']  = CardIDOverride('LOF_164') === 'SOR_164';
$checks['fold is idempotent']             = CardIDOverride(CardIDOverride('LOF_164')) === 'SOR_164';
$checks['FFG UID maps to canonical printing'] = SWUDeckEditCardID('7965404100') === 'SOR_033';
$checks['numeric JSON UID maps to canonical printing'] = SWUDeckEditCardID(7965404100) === 'SOR_033';
$checks['reported remove UID maps to canonical printing'] = SWUDeckEditCardID('8318404945') === 'SOR_176';
$checks['printing and UID match same stored card'] = SWUDeckEditCardID('SEC_030') === SWUDeckEditCardID('7965404100');
$checks['canonical ID remains canonical'] = SWUDeckEditCardID('SOR_033') === 'SOR_033';
$storedMain = [(object)['CardID' => '7965404100'], (object)['CardID' => 'SOR_033']];
$storedSide = [(object)['CardID' => 'SEC_030']];
$checks['copy count combines zones and identity forms'] = SWUDeckEditCopyCount($storedMain, $storedSide, 'SOR_033') === 3;
$checks['premier copy limit is three'] = SWUDeckMaxCopies('SOR_033', 'premier') === 3;
$checks['twin suns copy limit is one'] = SWUDeckMaxCopies('SOR_033', 'twinsuns') === 1;
$checks['printed copy exception applies'] = SWUDeckMaxCopies('JTL_256', 'premier') === 15;
$checks['open has no copy limit'] = SWUDeckMaxCopies('SOR_033', 'open') === PHP_INT_MAX;

$bulkCode = file_get_contents($root . '/APIs/EditDeckCards.php');
$checks['bulk edit normalizes input and both remove comparisons'] = substr_count($bulkCode, 'SWUDeckEditCardID(') === 3;
$checks['bulk edit checks copy limits before write'] = strpos($bulkCode, 'Copy limit exceeded') < strpos($bulkCode, 'WriteGamestate(');

$fail = array_keys(array_filter($checks, fn($v) => !$v));
if ($fail) { fwrite(STDERR, "FAIL (" . count($fail) . "/" . count($checks) . "):\n  - " . implode("\n  - ", $fail) . "\n"); exit(1); }
echo "PASS (" . count($checks) . " checks)\n";
