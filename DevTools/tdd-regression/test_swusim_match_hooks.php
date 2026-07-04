<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_match_hooks.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../SWUSim/MatchFlow.php';   // shim → Core/Match + MatchHooks (registers SWUSim)
$checks = [];
foreach (['resolveLobbyDecks','validateDeck','setupGame'] as $h) {
    $checks["required $h"] = MatchHookExists('SWUSim', $h);
}
$checks['optional recordDeckStats present']   = MatchHookExists('SWUSim','recordDeckStats');
$checks['optional submitResults present']     = MatchHookExists('SWUSim','submitResults');
$checks['optional flashMatchResult present']  = MatchHookExists('SWUSim','flashMatchResult');
$checks['queueTypes bo1+bo3']                 = MatchConfig('SWUSim','queueTypes',[]) === ['bo1','bo3'];
// Delegators resolve to the shared framework.
$checks['SWUReadMatchRef delegates']          = function_exists('SWUReadMatchRef') && function_exists('MatchReadRef');
$checks['SWUAfterActionMatchHook delegates']  = function_exists('SWUAfterActionMatchHook') && function_exists('MatchAfterActionHook');
$fail=0; foreach($checks as $k=>$v){ echo ($v?'PASS ':'FAIL ').$k."\n"; if(!$v)$fail++; }
echo ($fail===0?"ALL GREEN\n":"$fail FAILED\n");
