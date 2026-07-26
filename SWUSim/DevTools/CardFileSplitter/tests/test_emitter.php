<?php
// Test: emitter + monolith rewriter + index builder.
require __DIR__ . '/../../../GeneratedCode/GeneratedCardDictionaries.php';
require __DIR__ . '/../../../../AppCore/SWU/Overrides.php';
require __DIR__ . '/../../../../AppCore/SWU/DeckValidation.php';
require __DIR__ . '/../HeaderGen.php';
require __DIR__ . '/../Scanner.php';
require __DIR__ . '/../Router.php';
require __DIR__ . '/../Emitter.php';

$testMap = splitter_build_testname_map(__DIR__ . '/../../../Tests/Cases');
$src = file_get_contents(__DIR__ . '/fixtures/mini_monolith.php');
$plan = splitter_emit_plan($src, 'SOR', $testMap);

// SOR_010 file emitted with both of its registrations.
assert(isset($plan['files']['SOR_010']), 'no SOR_010 file');
$f = $plan['files']['SOR_010'];
assert($f['set'] === 'sor', 'SOR_010 set: '.$f['set']);
assert(str_contains($f['body'], "whenPlayedAbilities['SOR_010']"), 'SOR_010 body missing whenPlayed');
assert(str_contains($f['body'], "customDQHandlers['SOR_010#0']"), 'SOR_010 body missing customDQ');

// SHD_030 reprint routed into SOR_033's file.
assert(isset($plan['files']['SOR_033']), 'no SOR_033 file');
assert(str_contains($plan['files']['SOR_033']['body'], "whenPlayedAbilities['SHD_030']"), 'SOR_033 body missing SHD_030');
assert(in_array('SHD_030', $plan['files']['SOR_033']['reprints'], true), 'SOR_033 reprints missing SHD_030');
assert($plan['files']['SOR_033']['basename'] === 'DeathTrooper', 'SOR_033 basename: '.$plan['files']['SOR_033']['basename']);

// Left in monolith: shared closure, helper fn, captures-local card.
assert(str_contains($plan['remaining'], '$sharedThing'), 'sharedThing not in remaining');
assert(str_contains($plan['remaining'], 'function _SWUHelperUsedEverywhere'), 'helper not in remaining');
assert(str_contains($plan['remaining'], "['SOR_231:0']"), 'SOR_231 (captures local) should stay');
// Moved statements gone from remaining.
assert(!str_contains($plan['remaining'], "whenPlayedAbilities['SOR_010']"), 'SOR_010 still in remaining');
assert(!str_contains($plan['remaining'], "whenPlayedAbilities['SHD_030']"), 'SHD_030 still in remaining');

// Index maps every printing of an emitted card to its path.
assert($plan['index']['SHD_030'] === 'sor/DeathTrooper.php', 'index SHD_030: '.($plan['index']['SHD_030'] ?? 'MISSING'));
assert($plan['index']['SOR_033'] === 'sor/DeathTrooper.php', 'index SOR_033: '.($plan['index']['SOR_033'] ?? 'MISSING'));

// Remaining must still be valid PHP.
assert(splitter_php_lints($plan['remaining']), 'remaining is not valid PHP');

echo "OK\n";
