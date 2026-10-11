<?php
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
set_error_handler(function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);});
$checks=0;
function colressCheck(bool $ok,string $message): void {global $checks;++$checks;if(!$ok)throw new RuntimeException($message);}
function colressField(int $seat,string $zone,string $id) {
    $obj=PokeAdd($seat,$zone,$id);$obj->Controller=$seat;$obj->EnteredTurn=1;return $obj;
}
function colressBoard(): void {
    InitializeGamestate();$GLOBALS['playerID']=1;$GLOBALS['currentPlayer']=1;
    SetTurnNumber(6);SetTurnPlayer(1);SetFirstPlayer(2);SetCurrentPhase('MAIN');SetRandomState(42);
    foreach([1,2] as $seat){AddPlayerTurns($seat,3);AddSetupReady($seat,true);
        for($i=0;$i<6;++$i)PokeAdd($seat,'Prizes','mee-003');
        for($i=0;$i<20;++$i)PokeAdd($seat,'Deck','mee-003');
        colressField($seat,'Active','me05-017')->Energy=['mee-003'];
        colressField($seat,'Bench','me05-072');
    }
    PokeSetVar('deckKey:1','relicanth-v4-colress');
}
$deck=PokeNamedDeck('relicanth-v4-colress');
colressCheck(array_sum(array_column($deck,'count'))===60&&!PokeValidateDeck($deck),'All 60 supplied cards are implemented');
colressCheck(PokeDetectDeck(array_reverse($deck))==='relicanth-v4-colress','Import preserves v4 identity');
colressCheck(PokeOpeningProfile('relicanth-v4-colress')['name']==='Fossil Beatdown','Opening statistics use fossil goals');
colressBoard();$jaw=PokeAdd(1,'Hand','me03-068');
PokeApplyAction(['player'=>1,'type'=>'trainer','source'=>PokeRef(1,'Hand',$jaw->mzIndex)]);
colressCheck(PokeFossilBenchCount(1)===2&&PokeIsFossil('me03-068'),'Jaw is playable and counts for Relicanth');
foreach([false,true] as $ignore){
    colressBoard();GetZoneObject('p1Active-0')->CardID='me03-068';
    PokeDealAttackDamage(2,'p2Active-0',50,$ignore);
    colressCheck(GetZoneObject('p1Active-0')->Damage===($ignore?50:20),'Jaw reduction respects attacks that ignore effects');
}
colressBoard();GetZoneObject('p1Active-0')->CardID='me03-068';PokeDealAttackDamage(2,'p2Active-0',20);
colressCheck(GetZoneObject('p1Active-0')->Damage===0,'Jaw cannot produce negative damage');
colressBoard();GetZoneObject('p1Active-0')->CardID='me03-068';GetZoneObject('p1Active-0')->Counters['noAbilities']=true;
PokeDealAttackDamage(2,'p2Active-0',50);colressCheck(GetZoneObject('p1Active-0')->Damage===50,'Suppressed Jaw has no reduction');
foreach([20,120] as $damage){
    colressBoard();GetZoneObject('p1Active-0')->Energy=['sv09-159','sv09-159'];
    PokeDealAttackDamage(2,'p2Active-0',$damage);
    colressCheck(GetZoneObject('p2Active-0')->Damage===40,'Spiky stacks and triggers on lethal damage');
    colressCheck(PokeHasAttackEnergy(GetZoneObject('p1Active-0'),['Colorless']),'Spiky pays Colorless costs');
    colressCheck(!PokeHasAttackEnergy(GetZoneObject('p1Active-0'),['Water']),'Spiky does not provide Water');
}
colressBoard();GetZoneObject('p1Bench-0')->Energy=['sv09-159'];PokeDealAttackDamage(2,'p2Active-0',30,false,'p1Bench-0');
colressCheck(GetZoneObject('p2Active-0')->Damage===0,'Spiky does not trigger on Bench damage');
colressBoard();GetZoneObject('p1Active-0')->Energy=['sv09-159'];PokePlaceDamageCounters('p2Active-0','p1Active-0',2);
colressCheck(GetZoneObject('p2Active-0')->Damage===0,'Spiky does not trigger from damage counters');
colressBoard();GetZoneObject('p1Active-0')->CardID='me05-072';GetZoneObject('p1Active-0')->Energy=['sv09-159'];
PokeDealAttackDamage(2,'p2Active-0',10);
colressCheck(GetZoneObject('p2Active-0')->Damage===0,'Spiky does not trigger when damage is reduced to zero');
colressBoard();$stamp=PokeAdd(1,'Hand','sv06-165');$stampRef=PokeRef(1,'Hand',$stamp->mzIndex);
colressCheck(!PokeCanPlayTrainer(1,$stampRef),'Stamp is illegal without a prior opposing-turn knockout');
SetTurnNumber(5);SetTurnPlayer(2);PokeDealAttackDamage(2,'p2Active-0',120);PokeResolveKnockouts();
colressCheck(PokeVar('knockedOutOnOpponentTurn:1')===5,'Opposing-turn knockout is recorded');
PokeStateImport(PokeStateExport());SetTurnNumber(6);SetTurnPlayer(1);
$GLOBALS['p1DecisionQueue']=[];$GLOBALS['p2DecisionQueue']=[];
colressCheck(PokeCanPlayTrainer(1,$stampRef),'Stamp prerequisite survives serialization');
for($i=0;$i<7;++$i)PokeAdd(2,'Hand','mee-003');
PokeApplyAction(['player'=>1,'type'=>'trainer','source'=>$stampRef]);
colressCheck(PokeCount(1,'Hand')===5&&PokeCount(2,'Hand')===2,'Stamp draws five and two');
SetTurnNumber(8);$stamp=PokeAdd(1,'Hand','sv06-165');
colressCheck(!PokeCanPlayTrainer(1,PokeRef(1,'Hand',$stamp->mzIndex)),'Stamp expires after the next turn');
colressBoard();PokeAdd(1,'Hand','me02.5-192');
colressCheck((GetZoneObject(PokeRelicanthColressChoose(PokeObservation(1))['source'])->CardID??'')==='me02.5-192','Bot uses supplied Lillie printing');
colressBoard();$colress=PokeAdd(1,'Hand','sv06.5-057');PokeAdd(1,'Deck','me05-076');PokeAdd(1,'Deck','sv09-159');
PokeApplyAction(['player'=>1,'type'=>'trainer','source'=>PokeRef(1,'Hand',$colress->mzIndex)]);
PokeStateImport(PokeStateExport());PokeApplyAction(PokeRelicanthColressChoose(PokeObservation(1)));
PokeStateImport(PokeStateExport());$energyChoice=PokeRelicanthColressChoose(PokeObservation(1));
colressCheck(GetZoneObject($energyChoice['value'])->CardID==='sv09-159','Colress searches for Spiky through a serialized choice');
PokeApplyAction($energyChoice);
colressCheck(count(array_filter(PokeObjects(1,'Hand'),fn($c)=>$c->CardID==='sv09-159'))===1,'Colress resolves Spiky into hand');
foreach([1,2] as $first){
    $game=PokeSimulateGame(42,$first,1500,'relicanth-v4-colress','dhelmise-v2');
    colressCheck($game['status']==='complete','V4 completes a bot match from starting seat '.$first.': '.$game['reason']);
    $game=PokeSimulateGame(43,$first,1500,'brisbane-lopunny','relicanth-v4-colress');
    colressCheck($game['status']==='complete','V4 completes a seat-two match from starting seat '.$first.': '.$game['reason']);
}
echo "Relicanth Colress: $checks checks passed\n";
