<?php
// Test: reprint-aware statement router.
require __DIR__ . '/../../../GeneratedCode/GeneratedCardDictionaries.php';
require __DIR__ . '/../../../../AppCore/SWU/Overrides.php';
require __DIR__ . '/../../../../AppCore/SWU/DeckValidation.php';
require __DIR__ . '/../Scanner.php';
require __DIR__ . '/../Router.php';

$mk = fn($text,$ids,$kind,$uses=[],$lhs='$x') =>
    ['text'=>$text,'cardIDs'=>$ids,'kind'=>$kind,'lhs'=>$lhs,'topLevelUses'=>$uses,'span'=>[0,0]];

// SOR_033's own printing → moves to sor.
$r = splitter_route($mk("\$whenPlayedAbilities['SOR_033:0']=function(){};", ['SOR_033'], 'assign', [], "\$whenPlayedAbilities['SOR_033:0']"), 'SOR');
assert($r['action']==='move' && $r['baseCardID']==='SOR_033' && $r['set']==='sor', 'SOR_033: '.json_encode($r));

// SHD_030 is a reprint of SOR_033 → when splitting SOR, it moves to the SOR file (by owner base).
$r = splitter_route($mk("\$whenPlayedAbilities['SHD_030:0']=function(){};", ['SHD_030'], 'assign', [], "\$whenPlayedAbilities['SHD_030:0']"), 'SOR');
assert($r['action']==='move' && $r['baseCardID']==='SOR_033' && $r['set']==='sor', 'SHD_030: '.json_encode($r));

// A function def is left.
$r = splitter_route($mk('function foo(){}', [], 'function'), 'SOR');
assert($r['action']==='leave', 'fn: '.json_encode($r));

// Bare-var LHS (shared closure) → left; no owner card in the LHS key.
$r = splitter_route($mk('$sharedThing = function(){};', ['LOF_130','LOF_131'], 'assign', [], '$sharedThing'), 'LOF');
assert($r['action']==='leave' && str_contains($r['reason'],'no card key'), 'sharedvar: '.json_encode($r));

// A registration OWNED by one card whose body merely references others → MOVES by owner.
$r = splitter_route($mk("\$onAttackEndAbilities['LOF_038:0'] = function(){ return ['LOF_044','LOF_047']; };", ['LOF_038','LOF_044','LOF_047'], 'assign', [], "\$onAttackEndAbilities['LOF_038:0']"), 'LOF');
assert($r['action']==='move' && $r['baseCardID']==='LOF_038', 'owner-move: '.json_encode($r));

// Other set → left when target is SOR (owner JTL_041).
$r = splitter_route($mk("\$x['JTL_041']=function(){};", ['JTL_041'], 'assign', [], "\$x['JTL_041']"), 'SOR');
assert($r['action']==='leave', 'other set: '.json_encode($r));

// Top-level use() capture → left (would break on move), even though owner is SOR_231.
$r = splitter_route($mk("\$whenPlayedAbilities['SOR_231:0']=function(\$p) use (\$h){};", ['SOR_231'], 'assign', ['h'], "\$whenPlayedAbilities['SOR_231:0']"), 'SOR');
assert($r['action']==='leave' && str_contains($r['reason'],'captures local'), 'uses: '.json_encode($r));

// Bare-variable alias RHS → left (shared closure reference).
$r = splitter_route($mk("\$whenPlayedAbilities['SOR_050:0'] = \$sharedThing;", ['SOR_050'], 'assign', [], "\$whenPlayedAbilities['SOR_050:0']"), 'SOR');
assert($r['action']==='leave' && str_contains($r['reason'],'alias'), 'alias: '.json_encode($r));

// Value-copy reader (array-element alias) → left.
$r = splitter_route($mk("\$unitAbilities['TWI_120'] = \$unitAbilities['SOR_093'];", ['TWI_120','SOR_093'], 'assign', [], "\$unitAbilities['TWI_120']"), 'TWI');
assert($r['action']==='leave' && str_contains($r['reason'],'alias'), 'array-alias: '.json_encode($r));

// A definition read-by-value elsewhere is pinned → left even though it's a clean SOR closure.
$stmts = [
    $mk("\$unitAbilities['SOR_093'] = function(\$p){ return 1; };", ['SOR_093'], 'assign', [], "\$unitAbilities['SOR_093']"),
    $mk("\$unitAbilities['TWI_120'] = \$unitAbilities['SOR_093'];", ['TWI_120','SOR_093'], 'assign', [], "\$unitAbilities['TWI_120']"),
];
$pinned = splitter_pinned_keys($stmts);
assert(isset($pinned['unitAbilities::SOR_093']), 'SOR_093 should be pinned: '.json_encode($pinned));
$r = splitter_route($stmts[0], 'SOR', $pinned);
assert($r['action']==='leave' && str_contains($r['reason'],'read by value'), 'pinned-def: '.json_encode($r));

// A normal SOR def NOT read elsewhere still moves under the same pinned set.
$r = splitter_route($mk("\$whenPlayedAbilities['SOR_050:0'] = function(){};", ['SOR_050'], 'assign', [], "\$whenPlayedAbilities['SOR_050:0']"), 'SOR', $pinned);
assert($r['action']==='move' && $r['baseCardID']==='SOR_050', 'unpinned-move: '.json_encode($r));

echo "OK\n";
