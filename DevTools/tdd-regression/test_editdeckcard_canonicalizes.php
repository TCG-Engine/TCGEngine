<?php
// EditDeckCard accepts a cardID from an external caller and stores it with no normalisation. Publishing
// latest-printing ids makes it far likelier to be exercised, because a consumer will now naturally echo
// back the printing we just exported. Fold to canonical on the way in — CardIDOverride is idempotent,
// so a caller sending the canonical id is unaffected.
$root = realpath(__DIR__ . '/../..');
$code = preg_replace('~//[^\n]*~', '', file_get_contents($root . '/APIs/EditDeckCard.php'));
$checks = [];

$checks['EditDeckCard folds the input id'] = preg_match('~CardIDOverride\(~', $code) === 1;
$checks['EditDeckCard requires Overrides'] = strpos($code, 'Overrides.php') !== false;
// It must NOT reach for the display map — this is a write path, and the two directions must not mix.
$checks['write path never uses the display map'] = strpos($code, 'SWUDisplayCardID') === false;

// Behavioural: the fold must actually move a latest-printing id back to canonical, and be idempotent.
require_once $root . '/SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
require_once $root . '/AppCore/SWU/Overrides.php';
$checks['LOF_164 folds back to SOR_164']  = CardIDOverride('LOF_164') === 'SOR_164';
$checks['fold is idempotent']             = CardIDOverride(CardIDOverride('LOF_164')) === 'SOR_164';

$fail = array_keys(array_filter($checks, fn($v) => !$v));
if ($fail) { fwrite(STDERR, "FAIL (" . count($fail) . "/" . count($checks) . "):\n  - " . implode("\n  - ", $fail) . "\n"); exit(1); }
echo "PASS (" . count($checks) . " checks)\n";
