<?php
require __DIR__.'/relicanth-colress-tests.php';
$before=$checks;$key='relicanth-v7-lanas-aid';$deck=PokeNamedDeck($key);
colressCheck(array_sum(array_column($deck,'count'))===60&&!PokeValidateDeck($deck),'Exact v7 list is fully implemented');
colressCheck(PokeDetectDeck(array_reverse($deck))===$key,'Imported v7 retains its identity');
foreach([1,2] as $seat)foreach([0,1,3] as $amount){
    colressBoard();SetTurnPlayer($seat);$GLOBALS['playerID']=$seat;PokeSetVar('deckKey:'.$seat,$key);
    foreach(['me05-017','mee-003','me05-062','mee-003','me02-084','me05-072','sv09-159','sv06-167'] as $id)PokeAdd($seat,'Discard',$id);
    $lana=PokeAdd($seat,'Hand','sv06-155');$ref=PokeRef($seat,'Hand',$lana->mzIndex);
    colressCheck(PokeCanPlayTrainer($seat,$ref),'Lana is legal with recoverable discard cards');
    PokeApplyAction(['player'=>$seat,'type'=>'trainer','source'=>$ref]);
    $decision=PokeDecisionOptions($seat);
    colressCheck($decision['min']===0&&$decision['max']===3&&count($decision['choices'])===4,'Lana excludes Rule Boxes, fossil Items, and Special Energy');
    $ids=array_column($decision['choices'],'card');
    colressCheck($ids===['me05-017','mee-003','me05-062','mee-003'],'Correct eligible Pokemon and basic Energy');
    $chosen=array_slice($decision['choices'],0,$amount);
    PokeStateImport(PokeStateExport());
    PokeApplyAction(['player'=>$seat,'type'=>'decision','value'=>implode('&',array_column($chosen,'value'))?:'-']);
    colressCheck(array_values(array_column(PokeObjects($seat,'Hand'),'CardID'))===array_column($chosen,'card'),'Lana restores chosen cards after serialization');
    colressCheck(PokeCount($seat,'Discard')===9-$amount&&PokeCount($seat,'TempZone')===0,'Only chosen cards leave discard');
}
colressBoard();$lana=PokeAdd(1,'Hand','sv06-155');
colressCheck(!PokeCanPlayTrainer(1,PokeRef(1,'Hand',$lana->mzIndex)),'Lana is illegal with no eligible cards');
colressBoard();PokeSetVar('deckKey:1',$key);PokeAdd(1,'Hand','sv06-155');PokeAdd(1,'Discard','me05-017');PokeAdd(1,'Discard','mee-003');
$action=PokeBotChoose(PokeObservation(1));
colressCheck($action['type']==='trainer'&&GetZoneObject($action['source'])->CardID==='sv06-155','V7 bot uses Lana for a missing backup');
PokeApplyAction($action);$action=PokeBotChoose(PokeObservation(1));PokeApplyAction($action);
colressCheck(PokeCount(1,'Hand')===2,'Bot completes Lana recovery');
colressBoard();PokeSetVar('deckKey:1',$key);
foreach(array_slice(PokeObjects(2,'Prizes'),3) as $obj)$obj->Remove();
for($i=0;$i<7;++$i)PokeAdd(2,'Hand','mee-003');PokeAdd(1,'Hand','me04-082');
$action=PokeBotChoose(PokeObservation(1));
colressCheck($action['type']==='trainer'&&GetZoneObject($action['source'])->CardID==='me04-082','Bot plays a useful legal Special Red Card');
foreach([1,2] as $first)foreach([[$key,'dhelmise-v2'],['brisbane-lopunny',$key]] as [$deck1,$deck2]){
    $game=PokeSimulateGame(42,$first,1500,$deck1,$deck2);
    colressCheck($game['status']==='complete','V7 bot match completes in either seat: '.$game['reason']);
}
echo 'Lana Aid: '.($checks-$before)." checks passed\n";
