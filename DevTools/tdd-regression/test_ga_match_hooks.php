<?php
// http://localhost:3200/TCGEngine/DevTools/tdd-regression/test_ga_match_hooks.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../GrandArchiveSim/CreateGame.php';       // defines GASetupGame
include_once __DIR__ . '/../../GrandArchiveSim/Custom/DeckImport.php'; // GAValidateResolvedDeck / GrandArchiveResolveDeckInput
include_once __DIR__ . '/../../GrandArchiveSim/MatchHooks.php';
$checks = [];
$checks['required setupGame registered']    = MatchHookExists('GrandArchiveSim', 'setupGame');
$checks['required validateDeck registered'] = MatchHookExists('GrandArchiveSim', 'validateDeck');
$checks['required resolve registered']      = MatchHookExists('GrandArchiveSim', 'resolveLobbyDecks');
$checks['queueTypes bo1+bo3']               = MatchConfig('GrandArchiveSim','queueTypes',[]) === ['bo1','bo3'];
$checks['sideboardUrl set']                 = MatchConfig('GrandArchiveSim','sideboardUrl','') === 'Sideboard.php';
$fail=0; foreach($checks as $k=>$v){ echo ($v?'PASS ':'FAIL ').$k."\n"; if(!$v)$fail++; }
echo ($fail===0?"ALL GREEN\n":"$fail FAILED\n");
