<?php
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
set_error_handler(function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);});
$checks=0;
function drawCheck(bool $ok,string $message): void {global $checks;++$checks;if(!$ok)throw new RuntimeException($message);}
function drawField(int $seat,string $zone,string $id){$c=PokeAdd($seat,$zone,$id);$c->Controller=$seat;return $c;}
function drawBoard(int $seat=1): void {
    InitializeGamestate();$GLOBALS['playerID']=$seat;$GLOBALS['currentPlayer']=$seat;
    SetCurrentPhase('MAIN');SetTurnPlayer($seat);SetFirstPlayer(3-$seat);SetTurnNumber(5);SetRandomState(42);
    foreach([1,2] as $p){AddPlayerTurns($p,3);AddSetupReady($p,true);for($i=0;$i<6;++$i)PokeAdd($p,'Prizes','mee-003');for($i=0;$i<20;++$i)PokeAdd($p,'Deck','mee-003');}
    drawField($seat,'Active','me05-017')->Energy=['mee-003'];drawField($seat,'Bench','me05-072');
    drawField(3-$seat,'Active','me05-039')->Energy=['mee-005'];drawField(3-$seat,'Bench','me05-072');
    PokeSetVar('deckKey:'.$seat,'relicanth-v2-draw');
}
function drawPlay(int $seat,string $id): void {
    $c=PokeAdd($seat,'Hand',$id);PokeApplyAction(['type'=>'trainer','source'=>PokeRef($seat,'Hand',$c->mzIndex),'player'=>$seat]);
}
function drawDrain(): void {
    while((new DecisionQueueController())->AnyQueuePending()){
        PokeStateImport(PokeStateExport());$seat=PokePendingPlayer();PokeApplyAction(PokeBotChoose(PokeObservation($seat)));
    }
}
$deck=PokeNamedDeck('relicanth-v2-draw');
drawCheck(array_sum(array_column($deck,'count'))===60&&!PokeValidateDeck($deck),'Exact 60-card draw list is implemented');
drawCheck(PokeDetectDeck(array_reverse($deck))==='relicanth-v2-draw','Imported draw list selects the separate bot');
drawCheck(PokeDetectDeck(PokeNamedDeck('relicanth-fossils'))==='relicanth-fossils','Original list retains its bot');
drawCheck(PokeDeckName('relicanth-v2-draw')==='relicanth v2 - draw','Requested selector name');
foreach([1,2] as $seat){
    foreach([4=>4,3=>8] as $prizes=>$size){
        drawBoard($seat);foreach(array_slice(PokeObjects(3-$seat,'Prizes'),$prizes) as $c)$c->Remove();
        PokeAdd($seat,'Hand','me05-017');drawPlay($seat,'sv07-139');
        drawCheck(PokeCount($seat,'Hand')===$size,'Lacey uses opposing Prize threshold for seat '.$seat);
    }
    drawBoard($seat);foreach(['me05-017','mee-003','me05-073'] as $id)PokeAdd($seat,'Hand',$id);
    drawPlay($seat,'me02.5-190');PokeStateImport(PokeStateExport());$a=PokeBotChoose(PokeObservation($seat));
    drawCheck(GetZoneObject($a['value'])->CardID==='me05-073','Iris pays with a fossil and retains attacker and Energy');PokeApplyAction($a);
    drawCheck(PokeCount($seat,'Hand')===6&&count(array_filter(PokeObjects($seat,'Hand'),fn($c)=>$c->CardID==='me05-017'))===1,'Iris draws to six after paying its serialized discard cost');
    drawBoard($seat);PokeAdd($seat,'Hand','me02.5-190');
    drawCheck(!array_filter(PokeLegalActions($seat),fn($a)=>$a['type']==='trainer'),'Iris cannot be played without another card');
    drawBoard($seat);for($i=0;$i<7;++$i)PokeAdd(3-$seat,'Hand','me05-073');PokeAdd($seat,'Hand','me05-017');drawPlay($seat,'me03-076');
    drawCheck(PokeCount($seat,'Hand')===4&&PokeCount(3-$seat,'Hand')===4,'Judge shuffles and draws four for both players');
    drawBoard($seat);drawPlay($seat,'sv06-158');PokeStateImport(PokeStateExport());PokeApplyAction(PokeBotChoose(PokeObservation($seat)));
    $active=GetZoneObject(PokeFirstRef($seat,'Active'));
    drawCheck($active->Tool==='sv06-158','Helmet chooser equips Active Relicanth');
    PokeDealAttackDamage(3-$seat,PokeFirstRef(3-$seat,'Active'),20);
    drawCheck(PokeCount($seat,'Hand')===2,'Helmet draws on opposing attack damage');
    PokeDealAttackDamage(3-$seat,PokeFirstRef(3-$seat,'Active'),100);
    drawCheck(PokeCount($seat,'Hand')===4,'Helmet draws on lethal damage before knockout');
    PokeResolveKnockouts();drawCheck(PokeCount($seat,'Hand')===4,'Helmet knockout does not double-trigger draw');
}
drawBoard();GetZoneObject('p1Bench-0')->Tool='sv06-158';PokeDealAttackDamage(2,'p2Active-0',20,false,'p1Bench-0');
drawCheck(PokeCount(1,'Hand')===0,'Helmet does not draw for Bench damage');
drawBoard();GetZoneObject('p1Active-0')->Tool='sv06-158';PokePlaceDamageCounters('p2Active-0','p1Active-0',2);
drawCheck(PokeCount(1,'Hand')===0,'Damage counters do not trigger Helmet');
drawBoard();GetZoneObject('p1Active-0')->CardID='me05-072';GetZoneObject('p1Active-0')->Tool='sv06-158';
GetZoneObject('p2Active-0')->CardID='me05-017';PokeDealAttackDamage(2,'p2Active-0',10);
drawCheck(PokeCount(1,'Hand')===0,'Zero damage after Armor reduction does not trigger Helmet');
foreach([1,2] as $seat){
    drawBoard($seat);$active=GetZoneObject(PokeFirstRef($seat,'Active'));$active->Energy=['sv06-167'];
    drawCheck(PokeHasAttackEnergy($active,['Water'])&&PokeHasAttackEnergy($active,['Darkness']),'Legacy provides every Energy type');
    drawCheck(!PokeHasAttackEnergy($active,['Water','Colorless']),'Legacy provides only one Energy at a time');
    PokeDealAttackDamage(3-$seat,PokeFirstRef(3-$seat,'Active'),100);PokeResolveKnockouts();
    drawCheck(GetPrizeClaims(3-$seat)===0&&PokeVar('legacyEnergyUsed:'.$seat,false),'First Legacy damage knockout awards zero Prizes for Relicanth');
    drawDrain();PokeStateImport(PokeStateExport());
    $active=GetZoneObject(PokeFirstRef($seat,'Active'));$active->CardID='me05-017';$active->Damage=0;$active->Energy=['sv06-167'];drawField($seat,'Bench','me05-072');
    PokeDealAttackDamage(3-$seat,PokeFirstRef(3-$seat,'Active'),100);PokeResolveKnockouts();
    drawCheck(GetPrizeClaims(3-$seat)===1,'Legacy reduction remains spent after serialization and reattachment');
}
drawBoard();GetZoneObject('p1Active-0')->Energy=['sv06-167'];PokePlaceDamageCounters('p2Active-0','p1Active-0',100);PokeResolveKnockouts();
drawCheck(GetPrizeClaims(2)===1&&!PokeVar('legacyEnergyUsed:1',false),'Counter knockout neither reduces Prizes nor consumes Legacy');
drawBoard();$c=GetZoneObject('p1Active-0');$c->Energy=['sv06-167'];$c->Damage=CardHp($c->CardID)-10;$c->Conditions=['Poisoned'=>10];PokeCheckupPhase();
drawCheck(GetPrizeClaims(2)===1&&!PokeVar('legacyEnergyUsed:1',false),'Poison knockout does not consume Legacy');
drawBoard();GetZoneObject('p1Active-0')->Energy=['sv06-167'];PokeDealAttackDamage(1,'p1Active-0',100,false,'p1Active-0');PokeResolveKnockouts();
drawCheck(GetPrizeClaims(2)===1&&!PokeVar('legacyEnergyUsed:1',false),'Self damage knockout does not consume Legacy');
foreach(['me03-062'=>1,'me02-084'=>2] as $id=>$prizes){
    drawBoard();$c=GetZoneObject('p1Active-0');$c->CardID=$id;$c->Energy=['sv06-167'];
    PokeDealAttackDamage(2,'p2Active-0',10000);PokeResolveKnockouts();
    drawCheck(GetPrizeClaims(2)===$prizes,'Legacy subtracts one Prize from '.$id);
}
drawBoard();PokeAdd(1,'Discard','sv06-167');
drawCheck(!str_contains(PokeCandidates(1,'Discard','pokemonOrEnergy'),'p1Discard-0')&&!str_contains(PokeCandidates(1,'Discard','basicEnergy'),'p1Discard-0'),'Night Stretcher and Energy Retrieval cannot recover Legacy');
foreach([1,2] as $seat){
    drawBoard($seat);$active=GetZoneObject(PokeFirstRef($seat,'Active'));$active->Energy=['sv06-167'];$active->Tool='sv06-158';
    foreach(['me05-073','sv10.5w-079','sv07-130','sv07-129'] as $id)drawField($seat,'Bench',$id);
    PokeAdd($seat,'Hand','me05-017');PokeAdd($seat,'Hand','mee-003');
    MZAddZone(0,'Stadium','me05-076');PokeAdd($seat,'Deck','me05-072');
    drawCheck(PokeBotChoose(PokeObservation($seat))['type']==='attack','Draw bot holds a prepared replacement beside five Antiques');
    PokeDealAttackDamage(3-$seat,PokeFirstRef(3-$seat,'Active'),100);PokeResolveKnockouts();drawDrain();
    drawCheck(PokeIsFossil(GetZoneObject(PokeFirstRef($seat,'Active'))->CardID)&&PokeCount($seat,'Hand')===4,'Helmet and Legacy resolve before Antique promotion');
    SetTurnPlayer($seat);
    for($i=0;$i<20;++$i){$a=PokeBotChoose(PokeObservation($seat));if($a['type']==='attack')break;PokeApplyAction($a);drawDrain();}
    $active=GetZoneObject(PokeFirstRef($seat,'Active'));
    drawCheck($a['type']==='attack'&&$active->CardID==='me05-017'&&count($active->Energy)===1&&PokeFossilBenchCount($seat)===5,'Draw policy completes replacement, attachment and Quarry refill on either seat');
}
drawBoard();PokeAdd(1,'Hand','mee-003');PokeAdd(1,'Hand','sv06-167');GetZoneObject('p1Active-0')->Energy=[];
$a=PokeBotChoose(PokeObservation(1));drawCheck($a['type']==='attach'&&GetZoneObject($a['source'])->CardID==='sv06-167','Draw bot prioritizes unspent Legacy over basic Energy');
drawBoard();GetZoneObject('p1Prizes-0')->Remove();foreach(['me05-073','sv10.5w-079','sv07-129'] as $id)drawField(1,'Bench',$id);
foreach(['me05-017','me02.5-190','me01-119'] as $id)PokeAdd(1,'Hand',$id);
$a=PokeBotChoose(PokeObservation(1));drawCheck($a['type']==='trainer'&&GetZoneObject($a['source'])->CardID==='me02.5-190','Draw bot chooses Iris to preserve a held replacement');PokeApplyAction($a);drawDrain();
drawCheck((bool)array_filter(PokeObjects(1,'Hand'),fn($c)=>$c->CardID==='me05-017'),'Iris sequence retains replacement Relicanth');
drawBoard();GetZoneObject('p1Prizes-0')->Remove();foreach(array_slice(PokeObjects(2,'Prizes'),3) as $c)$c->Remove();
PokeAdd(1,'Hand','sv07-139');PokeAdd(1,'Hand','me01-119');$a=PokeBotChoose(PokeObservation(1));
drawCheck(GetZoneObject($a['source'])->CardID==='sv07-139','Late Lacey draws eight ahead of six-card Lillie');
foreach(['dhelmise-v2','brisbane-lopunny','relicanth-fossils','relicanth-v2-draw'] as $opponent)foreach([1,2] as $first){
    $game=PokeSimulateGame(42,$first,1500,'relicanth-v2-draw',$opponent);
    drawCheck($game['status']==='complete','Draw bot completes '.$opponent.' starting order '.$first.': '.$game['reason']);
}
echo "$checks Relicanth draw checks passed\n";
