<?php
// Test: tokenizer-based statement scanner.
require __DIR__ . '/../Scanner.php';
$src = file_get_contents(__DIR__ . '/fixtures/mini_monolith.php');
$stmts = splitter_scan($src);
$byText = fn($needle) => array_values(array_filter($stmts, fn($s)=>str_contains($s['text'],$needle)));

$wp = $byText("whenPlayedAbilities['SOR_010']")[0];
assert($wp['kind'] === 'assign', 'SOR_010 kind: '.$wp['kind']);
assert($wp['cardIDs'] === ['SOR_010'], 'SOR_010 ids: '.implode(',',$wp['cardIDs']));
assert($wp['lhs'] === "\$whenPlayedAbilities['SOR_010']", 'SOR_010 lhs: ['.$wp['lhs'].']');
assert(str_contains($wp['text'], 'banner comment'), 'leading comment not attached: ['.$wp['text'].']');

$fn = $byText('function _SWUHelperUsedEverywhere')[0];
assert($fn['kind'] === 'function', 'fn kind: '.$fn['kind']);

$l130 = $byText("customDQHandlers['LOF_130#0']")[0];
assert($l130['cardIDs'] === ['LOF_130'], 'LOF_130 ids: '.implode(',',$l130['cardIDs']));

$shared = $byText('$sharedThing =')[0];
assert($shared['cardIDs'] === ['LOF_130','LOF_131'], 'shared ids: '.implode(',',$shared['cardIDs']));

// Top-level use() capture recorded; nested fn use() not.
$uses = $byText("['SOR_231:0']")[0];
assert($uses['topLevelUses'] === ['sorHelperLocal'], 'topLevelUses: '.implode(',',$uses['topLevelUses']));
$nested = $byText("['SOR_240:0']")[0];
assert($nested['topLevelUses'] === [], 'nested should have no top-level use: '.implode(',',$nested['topLevelUses']));

// Spans are exact & non-overlapping: removing every span (reverse order) leaves
// no statement content — no assignments, no function decls, no CardIDs. Only the
// <?php tag and inter-statement whitespace may remain.
$reconstructed = $src;
foreach (array_reverse($stmts) as $s) {
    $reconstructed = substr($reconstructed, 0, $s['span'][0]) . substr($reconstructed, $s['span'][1]);
}
assert(!preg_match('/\b[A-Z0-9]{2,4}_\d+\b/', $reconstructed), 'CardID left behind: ['.$reconstructed.']');
assert(!str_contains($reconstructed, 'whenPlayedAbilities'), 'assignment left behind');
assert(!str_contains($reconstructed, 'function _SWUHelper'), 'function decl left behind');
// Spans must not overlap.
$prevEnd = 0;
foreach ($stmts as $s) { assert($s['span'][0] >= $prevEnd, 'overlapping spans'); $prevEnd = $s['span'][1]; }

echo "OK\n";
