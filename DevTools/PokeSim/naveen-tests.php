<?php
require __DIR__.'/ceruledge-tests.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
function naveenPlay(): void { ceruledgeplay('me03-079'); }
ceruledgeverify(PokeCardImplemented('me03-079'),'Naveen implemented');
ceruledgeboard(); naveenPlay();
ceruledgeverify(PokeCount(1,'Hand')===5 && GetSupporterUsed(1),'Empty hand draws five and spends Supporter');
ceruledgeboard(); PokeAdd(1,'Hand','sv08-036'); PokeAdd(1,'Hand','mee-002'); naveenPlay();
$options=PokeDecisionOptions(1);
ceruledgeverify($options['min']===0 && $options['max']===2,'May discard zero or all cards');
ceruledgechoose('-');
ceruledgeverify(PokeCount(1,'Hand')===5 && PokeCount(1,'Discard')===1,'Keeping cards draws only to five');
ceruledgeboard(); PokeAdd(1,'Hand','sv08-036');
for($i=0;$i<7;++$i)PokeAdd(1,'Hand','mee-002');
naveenPlay(); $options=PokeDecisionOptions(1);
ceruledgeverify($options['min']===4,'Large hand must discard enough to draw');
$fire=array_filter($options['choices'],fn($c)=>$c['card']==='mee-002');
ceruledgechoose(implode('&',array_column($fire,'value')));
ceruledgeverify(PokeCount(1,'Hand')===5 && PokeCount(1,'Discard')===8,'Selective Energy discard and draw resolves across serialization');
ceruledgeverify(count(array_filter(PokeObjects(1,'Hand'),fn($c)=>$c->CardID==='sv08-036'))===1,'Kept evolution survives');
ceruledgeboard(); $GLOBALS['p1Deck']=[]; $card=PokeAdd(1,'Hand','me03-079');
ceruledgeverify(!PokeCanPlayTrainer(1,PokeRef(1,'Hand',$card->mzIndex)),'Cannot play without any cards to draw');
ceruledgeboard(); SetFirstPlayer(1); AddPlayerTurns(1,1); $card=PokeAdd(1,'Hand','me03-079');
ceruledgeverify(!PokeCanPlayTrainer(1,PokeRef(1,'Hand',$card->mzIndex)),'No Carmine-style first-turn exception');
ceruledgeboard(); $GLOBALS['p1Deck']=[]; PokeAdd(1,'Deck','mee-002'); naveenPlay();
ceruledgeverify(PokeCount(1,'Hand')===1 && !GetWinner(),'Partial effect draw does not cause deck-out');
// The policy discards surplus fuel while keeping a needed evolution and attachment.
ceruledgeboard('me02-019'); PokeSetVar('deckKey:1','ceruledge-ex');
GetZoneObject('p1Active-0')->Energy=[];
PokeAdd(1,'Hand','sv08-036'); for($i=0;$i<3;++$i)PokeAdd(1,'Hand','mee-002');
naveenPlay(); $action=PokeBotChoose(PokeObservation(1));
$ids=array_map(fn($ref)=>GetZoneObject($ref)->CardID,explode('&',$action['value']));
ceruledgeverify($ids===['mee-002','mee-002'],'Bot preserves last attachment and needed evolution');
ceruledgechoose($action['value']);
echo "PASS $checks total card and Naveen checks\n";
