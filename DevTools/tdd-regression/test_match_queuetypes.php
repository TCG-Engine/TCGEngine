<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_match_queuetypes.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../Core/Match/QueueTypes.php';
$checks = [];
$checks['bo1 bestOf 1']       = MatchGetQueueType('bo1')['bestOf'] === 1;
$checks['bo1 no sideboard']   = MatchGetQueueType('bo1')['sideboard'] === false;
$checks['bo3 bestOf 3']       = MatchGetQueueType('bo3')['bestOf'] === 3;
$checks['bo3 sideboard']      = MatchGetQueueType('bo3')['sideboard'] === true;
$checks['case-insensitive']   = MatchGetQueueType(' BO3 ')['bestOf'] === 3;
$checks['unknown -> null']    = MatchGetQueueType('bo99') === null;
$fail = 0; foreach ($checks as $k=>$v){ echo ($v?'PASS ':'FAIL ').$k."\n"; if(!$v)$fail++; }
echo ($fail===0?"ALL GREEN\n":"$fail FAILED\n");
