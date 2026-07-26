<?php
// A leading banner comment must move WITH the statement below it.
$whenPlayedAbilities['SOR_010'] = function($p){ return 1; };
$customDQHandlers['SOR_010#0'] = function($p,$x,$y){ return 2; };
// A family closure referencing two cards — must be tagged family.
$sharedThing = function($p) { return ['LOF_130','LOF_131']; };
$customDQHandlers['LOF_130#0'] = $sharedThing;
$customDQHandlers['LOF_131#0'] = $sharedThing;
function _SWUHelperUsedEverywhere($x){ return $x; }
$whenPlayedAbilities['SHD_030'] = function($p){ return 3; }; // reprint of SOR_033
$sorHelperLocal = 'x';
$whenPlayedAbilities['SOR_231:0'] = function($p) use ($sorHelperLocal){ return $sorHelperLocal; };
$whenPlayedAbilities['SOR_240:0'] = function($p){ return array_map(fn($v) => $v, [1]); }; // nested fn, no top-level use
