<?php
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
set_error_handler(function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);});
$checks=0;
function fossilCheck(bool $value,string $message): void {global $checks;++$checks;if(!$value)throw new RuntimeException($message);}
function fossilField(int $seat,string $zone,string $id){$obj=PokeAdd($seat,$zone,$id);$obj->Controller=$seat;$obj->EnteredTurn=1;return $obj;}
function fossilBoard(string $enemy='me02-084'): void {
    InitializeGamestate();$GLOBALS['playerID']=1;$GLOBALS['currentPlayer']=1;
    SetTurnNumber(4);SetTurnPlayer(1);SetFirstPlayer(2);SetCurrentPhase('MAIN');SetRandomState(42);
    foreach([1,2] as $p){AddPlayerTurns($p,2);AddSetupReady($p,true);for($i=0;$i<6;++$i)PokeAdd($p,'Prizes','mee-003');for($i=0;$i<20;++$i)PokeAdd($p,'Deck','mee-003');}
    fossilField(1,'Active','me05-017')->Energy=['mee-003'];fossilField(2,'Active',$enemy);
    PokeSetVar('deckKey:1','relicanth-fossils');
}
$deck=PokeNamedDeck('relicanth-fossils');
fossilCheck(array_sum(array_column($deck,'count'))===60&&!PokeValidateDeck($deck),'All 60 cards are implemented');
foreach([1,2] as $seat){
    fossilBoard();SetTurnPlayer($seat);GetZoneObject("p{$seat}Active-0")->CardID='me05-072';
    $relicanth=fossilField($seat,'Bench','me05-017');$relicanth->Energy=['mee-003'];$relicanth->Tool='sv10.5w-080';$relicanth->Damage=10;
    foreach(['sv07-129','sv07-130','sv10.5w-079'] as $id)fossilField($seat,'Bench',$id);
    PokeApplyAction(['type'=>'ability','source'=>"p{$seat}Active-0",'index'=>0,'player'=>$seat]);
    fossilCheck(count(array_filter(GetDecisionQueue($seat),fn($d)=>$d->Type==='MZCHOOSE'))===1,'Fossil release queues exactly one promotion');
    // The browser answers in a later request, after the state was serialized.
    PokeStateImport(PokeStateExport());
    PokeApplyAction(['type'=>'decision','player'=>$seat,'value'=>"p{$seat}Bench-0"]);
    $active=GetZoneObject(PokeFirstRef($seat,'Active'));
    fossilCheck($active->CardID==='me05-017'&&$active->Energy===['mee-003']&&$active->Tool==='sv10.5w-080'&&$active->Damage===10,'Promoted Relicanth retains its Energy, Tool and damage');
    fossilCheck(PokeDecisionOptions($seat)===null&&!GetDecisionQueue($seat),'Promotion finishes without a second fossil chooser');
    fossilCheck(PokeCount($seat,'Bench')===3&&GetPrizeClaims(3-$seat)===0,'Fossil release preserves the remaining Bench and awards no Prizes');
    $customDQHandlers['PokePromote']($seat,[],"p{$seat}Bench-0");
    fossilCheck(GetZoneObject(PokeFirstRef($seat,'Active'))->CardID==='me05-017'&&PokeCount($seat,'Bench')===3,'Stale promotion cannot replace the chosen Active Pokémon');
}
fossilBoard();foreach(['me05-072','me05-073','sv10.5w-079','sv07-130','sv07-129'] as $id)fossilField(1,'Bench',$id);
PokeDealAttackDamage(1,'p1Active-0',10+30*PokeFossilBenchCount(1));fossilCheck(GetZoneObject('p2Active-0')->Damage===160,'Five fossils yield 160 damage');
GetZoneObject('p1Active-0')->Tool='sv10.5w-080';PokeDealAttackDamage(1,'p1Active-0',160);fossilCheck(GetZoneObject('p2Active-0')->Damage===350,'Bangle adds 30 against ex');
fossilBoard();PokeAdd(1,'Hand','me05-072');PokeApplyAction(['type'=>'trainer','source'=>'p1Hand-0','player'=>1]);
fossilCheck(PokeCount(1,'Bench')===1&&EffectiveCardType(GetZoneObject('p1Bench-0'))==='Pokemon','Item fossil enters Bench as a Pokémon');
fossilCheck(PokeCandidates(1,'Discard','pokemonOrEnergy')==='','Played fossil does not stay in discard');
fossilBoard('sv07-130');fossilCheck(!array_filter(PokeLegalActions(1),fn($a)=>$a['type']==='attack'),'Root fossil increases Basic attack cost');
GetZoneObject('p1Active-0')->Energy[]='mee-003';fossilCheck((bool)array_filter(PokeLegalActions(1),fn($a)=>$a['type']==='attack'),'Second Energy pays Root tax');
fossilBoard('me05-072');PokeDealAttackDamage(1,'p1Active-0',40);fossilCheck(GetZoneObject('p2Active-0')->Damage===30,'Armor reduces final damage');
fossilBoard('me05-073');PokeDealAttackDamage(1,'p1Active-0',60);fossilCheck(GetZoneObject('p1Active-0')->Damage===30,'Skull reflects three counters before knockout');
fossilBoard('sv07-129');PokePlaceDamageCounters('p1Active-0','p2Active-0',4);fossilCheck(GetZoneObject('p2Active-0')->Damage===0,'Cover prevents attack effects');
fossilBoard('me05-072');PokeSetCondition('p1Active-0','p2Active-0','Poisoned');fossilCheck(!GetZoneObject('p2Active-0')->Conditions&&PokeRetreatCost(GetZoneObject('p2Active-0'))===99,'Fossils resist conditions and cannot retreat');
fossilBoard();PokeAdd(1,'Hand','me05-076');PokeAdd(1,'Deck','me05-072');PokeAdd(1,'Deck','sv10.5w-079');
PokeApplyAction(['type'=>'trainer','source'=>'p1Hand-0','player'=>1]);
$quarry=array_values(array_filter(PokeLegalActions(1),fn($a)=>($a['source']??'')==='Stadium-0'))[0];PokeApplyAction($quarry);
PokeApplyAction(PokeBotChoose(PokeObservation(1)));
fossilCheck(PokeCount(1,'Bench')===2,'Quarry benches two Antique Items');
fossilCheck(!array_filter(PokeLegalActions(1),fn($a)=>($a['source']??'')==='Stadium-0'),'Quarry can only be used once per turn');
SetTurnPlayer(2);fossilCheck((bool)array_filter(PokeLegalActions(2),fn($a)=>($a['source']??'')==='Stadium-0'),'Opponent may use Quarry on their turn');
fossilBoard();PokeAdd(1,'Hand','sv06.5-057');PokeAdd(1,'Deck','me05-076');
PokeApplyAction(['type'=>'trainer','source'=>'p1Hand-0','player'=>1]);PokeApplyAction(PokeBotChoose(PokeObservation(1)));PokeApplyAction(PokeBotChoose(PokeObservation(1)));
fossilCheck(in_array('me05-076',array_column(PokeObservation(1)['players'][1]['Hand'],'id'),true)&&in_array('mee-003',array_column(PokeObservation(1)['players'][1]['Hand'],'id'),true),'Colress finds a Stadium and Energy');
fossilBoard();GetZoneObject('p1Active-0')->CardID='me05-072';GetZoneObject('p1Active-0')->Counters['noAbilities']=true;fossilField(1,'Bench','me05-017');
$release=PokeBotChoose(PokeObservation(1));fossilCheck($release['type']==='ability','Bot releases an Active fossil');PokeApplyAction($release);PokeApplyAction(PokeBotChoose(PokeObservation(1)));
fossilCheck(GetZoneObject('p1Active-0')->CardID==='me05-017'&&GetPrizeClaims(2)===0,'Fossil discard promotes Relicanth without awarding Prizes');
fossilBoard();fossilField(2,'Bench','sv10.5w-079');PokeDealAttackDamage(1,'p1Active-0',100,false,'p2Bench-0');fossilCheck(GetZoneObject('p2Bench-0')->Damage===0,'Plume prevents Bench damage');
foreach(['sinistcha','brisbane-lopunny','relicanth-fossils'] as $opponent)foreach([1,2] as $first){
    $game=PokeSimulateGame(42,$first,1500,'relicanth-fossils',$opponent);
    fossilCheck($game['status']==='complete',"Relicanth vs $opponent first $first: ".$game['reason']);
}
// Five fossils reach a 160-damage knockout; an unnecessary backup would lose it.
fossilBoard('me05-034');GetZoneObject('p2Active-0')->Energy=['mee-005'];PokeAdd(1,'Hand','me05-017');
foreach(['me05-072','me05-073','sv10.5w-079','sv07-130'] as $id)fossilField(1,'Bench',$id);
PokeAdd(1,'Hand','sv07-129');$a=PokeBotChoose(PokeObservation(1));
fossilCheck($a['type']==='trainer','Bot chooses fifth fossil over a spare Relicanth');PokeApplyAction($a);
fossilCheck(PokeFossilBenchCount(1)===5&&PokeBotChoose(PokeObservation(1))['type']==='attack','Full fossil Bench immediately attacks for the KO');
foreach([false,true] as $quarry){
    fossilBoard();GetZoneObject('p2Active-0')->Energy=['mee-005'];
    PokeAdd(1,'Hand','me05-017');PokeAdd(1,'Hand','mee-003');PokeAdd(1,'Hand','me01-119');
    foreach(['me05-072','me05-073','sv10.5w-079','sv07-130','sv07-129'] as $id)fossilField(1,'Bench',$id);
    if($quarry){MZAddZone(0,'Stadium','me05-076');PokeAdd(1,'Deck','me05-072');}
    else PokeAdd(1,'Hand','me05-072');
    $a=PokeBotChoose(PokeObservation(1));
    fossilCheck($a['type']==='attack','Threatened Relicanth attacks with five Antiques and holds its replacement and Energy');
    fossilCheck(PokeCount(1,'Hand')===($quarry?3:4),'Replacement hand is preserved before the attack');
    // Resolve an opposing knockout, then serialize each promotion decision.
    SetTurnPlayer(2);GetZoneObject('p1Active-0')->Damage=10000;PokeResolveKnockouts();
    while((new DecisionQueueController())->AnyQueuePending()){
        PokeStateImport(PokeStateExport());$seat=PokePendingPlayer();PokeApplyAction(PokeBotChoose(PokeObservation($seat)));
    }
    fossilCheck(PokeIsFossil(GetZoneObject('p1Active-0')->CardID)&&PokeCount(1,'Bench')===4,'Knockout promotes an Antique and opens a Bench slot');
    SetTurnPlayer(1);
    $a=PokeBotChoose(PokeObservation(1));fossilCheck($a['type']==='bench','Replacement Relicanth is benched before Quarry fills the slot');PokeApplyAction($a);
    $claims=GetPrizeClaims(2);$prizes=PokeCount(2,'Prizes');
    $a=PokeBotChoose(PokeObservation(1));fossilCheck($a['type']==='ability'&&$a['source']==='p1Active-0','Active Antique releases to promote the replacement');PokeApplyAction($a);
    PokeStateImport(PokeStateExport());PokeApplyAction(PokeBotChoose(PokeObservation(1)));
    fossilCheck(GetZoneObject('p1Active-0')->CardID==='me05-017'&&GetPrizeClaims(2)===$claims&&PokeCount(2,'Prizes')===$prizes,'Antique release promotes Relicanth without an extra Prize');
    for($i=0;$i<12;++$i){
        $a=PokeBotChoose(PokeObservation(1));if($a['type']==='attack')break;PokeApplyAction($a);
    }
    fossilCheck($a['type']==='attack'&&PokeFossilBenchCount(1)===5&&GetZoneObject('p1Active-0')->Energy===['mee-003'],'Replacement attaches and restores all five Antiques before attacking');
    $before=GetZoneObject('p2Active-0')->Damage;PokeApplyAction($a);
    fossilCheck(GetZoneObject('p2Active-0')->Damage-$before===160,'Replacement cycle delivers 160 damage');
}
fossilBoard('sv07-130');PokeAdd(1,'Hand','mee-003');$a=PokeBotChoose(PokeObservation(1));
fossilCheck($a['type']==='attach'&&$a['target']==='p1Active-0','Root tax receives the second Energy');PokeApplyAction($a);
fossilCheck(PokeBotChoose(PokeObservation(1))['type']==='attack','Bot attacks after paying Root tax');
fossilBoard();GetZoneObject('p1Active-0')->CardID='me05-072';fossilField(1,'Bench','sv07-129');
fossilCheck(PokeBotChoose(PokeObservation(1))['type']==='end','Bot keeps a fossil Active until it has a Relicanth to promote');
fossilBoard();GetZoneObject('p2Active-0')->Energy=['mee-005'];
foreach(['me05-072','me05-073','sv10.5w-079','sv07-130'] as $id)fossilField(1,'Bench',$id);
PokeAdd(1,'Hand','sv07-129');fossilCheck(PokeBotChoose(PokeObservation(1))['type']==='trainer','Bot fills the fifth slot until a backup is actually available');
$attackTurns=0;$legalTurns=0;$completed=0;
fossilBoard();PokeAdd(1,'Hand','me05-017');
fossilCheck(PokeBotChoose(PokeObservation(1))['type']==='bench','Bot benches a safety backup when no Antique can survive a knockout');
// A fossil Active with no live attacker must recover Relicanth before Energy.
fossilBoard();GetZoneObject('p1Active-0')->CardID='me05-072';
PokeAdd(1,'Discard','mee-003');PokeAdd(1,'Discard','me05-017');PokeAdd(1,'Hand','me02.5-196');
PokeApplyAction(PokeBotChoose(PokeObservation(1)));$choice=PokeBotChoose(PokeObservation(1));
fossilCheck(GetZoneObject($choice['value'])->CardID==='me05-017','Recovery restores an attacker before an Energy when no Relicanth survives');
PokeApplyAction($choice);
// A powered Active still needs next turn's attachment for its backup.
fossilBoard();fossilField(1,'Bench','me05-017');PokeAdd(1,'Hand','me03-072');
$a=PokeBotChoose(PokeObservation(1));fossilCheck($a['type']==='trainer','Search prepares Energy for an unpowered backup');
// Junk hand size must not suppress draw when the replacement chain is empty.
fossilBoard();foreach(['me05-072','me05-073','sv10.5w-079','sv07-130','sv07-129'] as $id)fossilField(1,'Bench',$id);
for($i=0;$i<6;++$i)PokeAdd(1,'Hand','me05-072');PokeAdd(1,'Hand','me01-119');
fossilCheck(PokeBotChoose(PokeObservation(1))['type']==='trainer','Draw seeks a backup even with a full Bench and a large fossil hand');
// Secret Box pays exactly three cards and each tutor survives request serialization.
fossilBoard();PokeAdd(1,'Hand','sv06-163');
foreach(['me05-072','me05-073','sv07-129','me05-017','mee-003'] as $id)PokeAdd(1,'Hand',$id);
foreach(['me03-081','sv10.5w-080','me01-119','me05-076'] as $id)PokeAdd(1,'Deck',$id);
PokeApplyAction(['type'=>'trainer','source'=>'p1Hand-0','player'=>1]);
$choice=PokeBotChoose(PokeObservation(1));$cost=explode('&',$choice['value']);
fossilCheck(count($cost)===3&&!array_filter($cost,fn($ref)=>!PokeIsFossil(GetZoneObject($ref)->CardID)),'Secret Box discards fossils while retaining Relicanth and Energy');
PokeApplyAction($choice);
foreach(['Item','Tool','Supporter','Stadium'] as $type){
    PokeStateImport(PokeStateExport());$d=PokeDecisionOptions(1);
    fossilCheck($d!==null&&!array_filter($d['choices'],fn($c)=>CardTrainerType($c['card'])!==$type),'Secret Box exposes only '.$type.' candidates');
    PokeApplyAction(PokeBotChoose(PokeObservation(1)));
}
fossilCheck(!PokeDecisionOptions(1)&&PokeCount(1,'Hand')===6,'Secret Box completes all four searches across saved await frames');
foreach(['me05-017','mee-003','me03-081','sv10.5w-080','me01-119','me05-076'] as $id)
    fossilCheck(in_array($id,array_column(PokeObservation(1)['players'][1]['Hand'],'id'),true),'Secret Box retains or finds '.$id);
foreach(['sinistcha','brisbane-lopunny'] as $opponent)foreach([1,2] as $first)foreach([1,7,19] as $seed){
    PokeCreateGame($deck,PokeNamedDeck($opponent),$seed,$first);$available=[];$attacked=[];
    for($step=0;$step<1500&&!GetWinner();++$step){
        $seat=PokePendingPlayer();$view=PokeObservation($seat);$a=PokeBotChoose($view);
        if($seat===1&&$view['phase']==='MAIN'&&!$view['decision']){
            $canAttack=(bool)array_filter($view['actions'],fn($x)=>$x['type']==='attack');
            if($canAttack)$available[$view['turn']]=true;
            fossilCheck(!($canAttack&&$a['type']==='end'),'Relicanth never ends a turn with a legal attack available');
            $ctx=PokeBotContext($view);
            if($a['type']==='attack'&&PokeFossilBenchCount(1)<5&&count($ctx['own']['Bench'])<5){
                $refill=array_filter($view['actions'],fn($x)=>
                    ($x['type']==='trainer'&&PokeIsFossil(PokeBotCard($ctx,$x['source'])['id']))
                    ||($x['type']==='ability'&&str_starts_with($x['source'],'Stadium-')));
                $target=$ctx['enemy']['Active'][0]??null;
                $winning=$target&&PokeRelicanthDamage($ctx,$target)>=$target['hp']-$target['damage']&&PokePrizeValue($target['id'])>=$ctx['own']['prizeCount'];
                fossilCheck(!$refill||$winning,'Bot refills available Antiques before a non-winning attack');
            }
            if($a['type']==='attack')$attacked[$view['turn']]=true;
        }
        PokeApplyAction($a);
    }
    fossilCheck((bool)GetWinner(),"Sustained attack match completes: $opponent seed $seed first $first");
    ++$completed;$legalTurns+=count($available);$attackTurns+=count($attacked);
}
fossilCheck($attackTurns===$legalTurns&&$attackTurns>0,'All turns that reached a legal attack converted to an attack');
echo "$completed sustained matches: $attackTurns attacks in $legalTurns turns with legal attacks\n";
echo "$checks Relicanth checks passed\n";
