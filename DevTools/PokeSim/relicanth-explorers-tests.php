<?php
// Reuse the Colress fixtures and run its existing card-effect regressions once.
require __DIR__.'/relicanth-colress-tests.php';
$before=$checks;
$key='relicanth-v6-explorers-guidance';
$deck=PokeNamedDeck($key);
colressCheck(array_sum(array_column($deck,'count'))===60&&!PokeValidateDeck($deck),'Exact v6 list is fully implemented');
colressCheck(PokeDetectDeck(array_reverse($deck))===$key,'Imported v6 retains its identity');
foreach([1,2] as $seat)foreach([1,2,3,6,7] as $size){
    colressBoard();SetTurnPlayer($seat);$GLOBALS['playerID']=$seat;
    PokeSetVar('deckKey:'.$seat,$key);
    $GLOBALS['p'.$seat.'Deck']=[];
    $cards=['me05-017','me01-125','me05-062','mee-003','sv10.5w-080','sv06-158','me05-076'];
    foreach(array_slice($cards,0,$size) as $id)PokeAdd($seat,'Deck',$id);
    $explorer=PokeAdd($seat,'Hand','sv08.5-107');
    PokeApplyAction(['player'=>$seat,'type'=>'trainer','source'=>PokeRef($seat,'Hand',$explorer->mzIndex)]);
    $decision=PokeDecisionOptions($seat);$keep=min(2,$size);
    colressCheck($decision['min']===$keep&&$decision['max']===$keep&&count($decision['choices'])===min(6,$size),'Choose exactly two or all available on a short deck');
    colressCheck(PokeObservation(3-$seat)['players'][$seat]['TempZone']===[],'Top six remain private to the choosing player');
    $chosen=implode('&',array_column(array_slice($decision['choices'],0,$keep),'value'));
    PokeStateImport(PokeStateExport());
    PokeApplyAction(['player'=>$seat,'type'=>'decision','value'=>$chosen]);
    colressCheck(array_values(array_column(PokeObjects($seat,'Hand'),'CardID'))===array_slice($cards,0,$keep),'Serialized chooser keeps the selected cards');
    colressCheck(PokeCount($seat,'Discard')===1+min(6,$size)-$keep&&PokeCount($seat,'TempZone')===0,'Unchosen cards and Supporter are discarded');
    colressCheck(array_values(array_column(PokeObjects($seat,'Deck'),'CardID'))===array_slice(array_slice($cards,0,$size),6),'Remaining deck order is unchanged');
    colressCheck(!array_filter(PokeVar('log',[]),fn($row)=>$row['event']==='reveal-search'),'Kept cards are not revealed in the public log');
}
colressBoard();$GLOBALS['p1Deck']=[];$explorer=PokeAdd(1,'Hand','sv08.5-107');
colressCheck(!PokeCanPlayTrainer(1,PokeRef(1,'Hand',$explorer->mzIndex)),'Explorer cannot be played into an empty deck');
colressBoard();PokeSetVar('deckKey:1',$key);PokeAdd(1,'Hand','sv08.5-107');
$action=PokeBotChoose(PokeObservation(1));
colressCheck($action['type']==='trainer'&&GetZoneObject($action['source'])->CardID==='sv08.5-107','V6 bot uses Explorer to find missing resources');
PokeApplyAction($action);$action=PokeBotChoose(PokeObservation(1));
colressCheck(count(explode('&',$action['value']))===2,'Bot chooses exactly two cards');PokeApplyAction($action);
foreach([1,2] as $first)foreach([[ $key,'dhelmise-v2'],['brisbane-lopunny',$key]] as [$deck1,$deck2]){
    $game=PokeSimulateGame(42,$first,1500,$deck1,$deck2);
    colressCheck($game['status']==='complete','V6 bot match completes in either seat: '.$game['reason']);
}
echo 'Explorer Guidance: '.($checks-$before)." checks passed\n";
