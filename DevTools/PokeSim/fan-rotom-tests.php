<?php
require __DIR__.'/fan-rotom-experiment.php';
$checks=0;
function check($ok,string $label): void {global $checks;++$checks;if(!$ok)throw new RuntimeException($label);}
function fanBoard(): void {
 InitializeGamestate();$GLOBALS['playerID']=1;$GLOBALS['currentPlayer']=1;
 SetCurrentPhase('MAIN');SetTurnPlayer(1);SetTurnNumber(2);SetFirstPlayer(2);SetRandomState(42);
 foreach([1,2] as $p){AddPlayerTurns($p,1);for($i=0;$i<6;$i++)PokeAdd($p,'Prizes','mee-005');for($i=0;$i<20;$i++)PokeAdd($p,'Deck','mee-005');$a=PokeAdd($p,'Active','sv07-118');$a->Controller=$p;$a->Energy=['mee-005'];}
 PokeSetVar('deckKey:1','fan-rotom-lopunny');
}
check(PokeDetectDeck(PokeNamedDeck('fan-rotom-lopunny'))==='fan-rotom-lopunny','Registered deck uses dedicated policy');
foreach(['sv08.5-079','me02.5-183','me01-132'] as $id)check(array_column(PokeNamedDeck('fan-rotom-lopunny'),'count','id')[$id]===4,'Requested four-copy core '.$id);
foreach(fanVariants() as $name=>$deck)check(!PokeValidateDeck($deck),'Valid variant '.$name);
fanBoard();GetZoneObject('p1Active-0')->Tool='me02.5-181';check(PokeRetreatCost(GetZoneObject('p1Active-0'))===0,'Balloon reduces retreat');
PokeAdd(1,'Hand','sv06-153');PokeApplyAction(['type'=>'trainer','player'=>1,'source'=>'p1Hand-0']);check(PokeRetreatCost(GetZoneObject('p1Active-0'))===1,'Jamming disables own Balloon');
PokeAdd(1,'Hand','me02-085');SetTurnNumber(3);PokeApplyAction(['type'=>'trainer','player'=>1,'source'=>'p1Hand-0']);check(PokeRetreatCost(GetZoneObject('p1Active-0'))===0,'Replacing Tower reactivates Balloon');
fanBoard();$d=PokeAdd(1,'Bench','sv08.5-079');$d->Controller=1;check(PokeRetreatCost($d)===0,'PRE Dunsparce free retreat');
$d->TurnEffects['digThrough']=3;PokeDealAttackDamage(2,'p2Active-0',70,false,'p1Bench-0');check($d->Damage===0,'Dig prevents damage');SetTurnNumber(4);PokeDealAttackDamage(2,'p2Active-0',70,false,'p1Bench-0');check($d->Damage===70,'Dig expires');
fanBoard();$s=PokeAdd(1,'Bench','me05-062');$s->Controller=1;$hp=PokeCurrentHP($s);PokeAdd(1,'Hand','sv08-177');PokeApplyAction(['type'=>'trainer','player'=>1,'source'=>'p1Hand-0']);check(PokeCurrentHP($s)===$hp-30,'Gravity reduces Stage 2 HP');
fanBoard();PokeAdd(1,'Hand','sv06.5-057');check(PokeBotChoose(PokeObservation(1))['source']==='p1Hand-0','Colress prioritized for missing Stadium');PokeSetVar('FanCall:1',GetTurnNumber());check(PokeBotChoose(PokeObservation(1))['source']==='p1Hand-0','Colress prioritized for missing Stadium');
fanBoard();PokeSetVar('FanCall:1',GetTurnNumber());GetZoneObject('p1Active-0')->Energy=[];PokeAdd(1,'Hand','me03-072');check(PokeBotChoose(PokeObservation(1))['source']==='p1Hand-0','Energy Search supplies missing attachment');
check(PokeOpeningProfile('fan-rotom-lopunny')['targets']===['sv07-118'=>[0]],'Opening metric targets Rotom');
echo "PASS $checks Fan Rotom checks\n";
