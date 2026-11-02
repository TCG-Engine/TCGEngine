<?php
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
set_error_handler(function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);});
$checks=0;
function ftCheck(bool $ok,string $label): void {global $checks;++$checks;if(!$ok)throw new RuntimeException($label);}
function ftBoard(string $active='me01-075'): void {
    InitializeGamestate();$GLOBALS['playerID']=1;$GLOBALS['currentPlayer']=1;
    SetTurnNumber(6);SetTurnPlayer(1);SetFirstPlayer(2);SetCurrentPhase('MAIN');SetRandomState(42);
    foreach([1,2] as $seat){
        AddPlayerTurns($seat,3);AddSetupReady($seat,true);
        $c=PokeAdd($seat,'Active',$seat===1?$active:'me02-084');$c->Controller=$seat;$c->EnteredTurn=1;$c->Energy=['base1-97','base1-97'];
        for($i=0;$i<6;++$i)PokeAdd($seat,'Prizes','mee-002');
        for($i=0;$i<20;++$i)PokeAdd($seat,'Deck','mee-002');
    }
    PokeSetVar('deckKey:1','ceruledge-fighting');
}
function ftBench(string $id): string {$c=PokeAdd(1,'Bench',$id);$c->Controller=1;$c->EnteredTurn=1;return PokeRef(1,'Bench',$c->mzIndex);}
function ftPlay(string $id): void {$c=PokeAdd(1,'Hand',$id);PokeApplyAction(['type'=>'trainer','player'=>1,'source'=>PokeRef(1,'Hand',$c->mzIndex)]);}
function ftAnswer(string $value): void {PokeStateImport(PokeStateExport());PokeApplyAction(['type'=>'decision','player'=>1,'value'=>$value]);}
function ftAbilities(): array {return array_values(array_filter(PokeLegalActions(1),fn($a)=>$a['type']==='ability'));}
$deck=PokeNamedDeck('ceruledge-fighting');$counts=array_column($deck,'count','id');
ftCheck(!PokeValidateDeck($deck)&&array_sum($counts)===60,'Exact 60-card list validates');
ftCheck($counts['base1-97']===11&&$counts['mee-002']===10&&$counts['me02.5-192']===4,'Requested printings and Energy counts preserved');
ftCheck(PokeDetectDeck(array_reverse($deck))==='ceruledge-fighting','Imported Fighting list retains its identity');
foreach(['me01-074','me01-075','me01-116'] as $id)ftCheck(PokeCardImplemented($id),'Implemented '.$id);
ftBoard('me01-074');PokeAdd(1,'Hand','base1-97');
ftCheck(!ftAbilities(),'Lunar Cycle requires Solrock');ftBench('me01-075');
ftCheck(count(ftAbilities())===1,'Solrock enables Lunar Cycle');
PokeAdd(1,'Hand','mee-002');PokeAdd(1,'Hand','sv06-167');
PokeApplyAction(ftAbilities()[0]);$options=PokeDecisionOptions(1);
ftCheck(array_column($options['choices'],'card')===['base1-97'],'Only Basic Fighting Energy can pay Lunar Cycle');
ftAnswer($options['choices'][0]['value']);
ftCheck(PokeCount(1,'Hand')===5&&PokeCount(1,'Discard')===1,'Lunar Cycle discards one and draws three');
ftCheck(!GetSupporterUsed(1)&&PokeObservation(1)['players'][1]['lunarCycleUsed'],'Ability does not use Supporter and publishes used flag');
ftBench('me01-074');PokeAdd(1,'Hand','mee-006');
ftCheck(!ftAbilities(),'All Lunatone share the once-per-turn limit');
PokeStateImport(PokeStateExport());ftCheck(!ftAbilities(),'Shared limit survives serialization');
SetTurnNumber(8);ftCheck(count(ftAbilities())===2,'Limit resets next turn and supports alternate Fighting printing');
ftBoard('me01-074');ftBench('me01-075');PokeAdd(1,'Hand','mee-002');
ftCheck(!ftAbilities(),'Fire cannot pay Lunar Cycle');
ftBoard('me01-074');ftBench('me01-075');PokeAdd(1,'Hand','base1-97');$GLOBALS['p1Deck']=[];
ftCheck(!ftAbilities(),'Cannot activate a draw ability with an empty deck');
ftBoard();PokeApplyAction(['type'=>'attack','player'=>1,'source'=>'p1Active-0','index'=>0]);
ftCheck(GetZoneObject('p2Active-0')->Damage===0,'Cosmic Beam does nothing without benched Lunatone');
foreach(['me02-084','sv01-079'] as $target){
    ftBoard();ftBench('me01-074');GetZoneObject('p2Active-0')->CardID=$target;
    PokeApplyAction(['type'=>'attack','player'=>1,'source'=>'p1Active-0','index'=>0]);
    ftCheck(GetZoneObject('p2Active-0')->Damage===70,'Cosmic Beam ignores Weakness/Resistance against '.$target);
}
ftBoard();ftBench('me01-074');GetZoneObject('p2Active-0')->TurnEffects['digThrough']=GetTurnNumber();
PokeApplyAction(['type'=>'attack','player'=>1,'source'=>'p1Active-0','index'=>0]);
ftCheck(GetZoneObject('p2Active-0')->Damage===0,'Cosmic Beam still respects damage prevention');
ftBoard('me01-074');PokeApplyAction(['type'=>'attack','player'=>1,'source'=>'p1Active-0','index'=>0]);
ftCheck(GetZoneObject('p2Active-0')->Damage===100,'Power Gem still applies normal Weakness');
ftBoard();foreach(['me01-074','me01-075','base1-97','sv06-167','sv08-036'] as $id)PokeAdd(1,'Deck',$id);
ftPlay('me01-116');$options=PokeDecisionOptions(1);$ids=array_column($options['choices'],'card');sort($ids);
ftCheck($ids===['base1-97','me01-074','me01-075'],'Gong offers Fighting Basics and Basic Fighting Energy only');
ftAnswer($options['choices'][0]['value']);ftCheck(PokeCount(1,'Hand')===1,'Gong resolves search across serialization');
ftBoard();ftPlay('me01-116');ftAnswer('-');ftCheck(PokeCount(1,'Hand')===0,'Gong may fail a search');
ftBoard('sv08-036');GetZoneObject('p1Active-0')->Energy=[];ftBench('me01-074');ftBench('me01-075');
PokeAdd(1,'Hand','base1-97');PokeAdd(1,'Hand','mee-002');PokeAdd(1,'Hand','sv06-145');
$choice=PokeBotChoose(PokeObservation(1));
ftCheck($choice['type']==='ability'&&GetZoneObject($choice['source'])->CardID==='me01-074','Bot uses Lunar Cycle before hand-reset Supporter');
$ctx=PokeCeruledgeContext(PokeObservation(1));
ftCheck(PokeCeruledgeActionScore($ctx,['type'=>'attach','source'=>'p1Hand-0','target'=>'p1Active-0'])<0,'Bot does not use Fighting to pay Ceruledge Fire requirement');
ftCheck(PokeCeruledgeDiscardScore($ctx,'base1-97')<0,'Bot preserves last Fighting for unused Lunar Cycle');
PokeAdd(1,'Hand','base1-97');$ctx=PokeCeruledgeContext(PokeObservation(1));
ftCheck(PokeCeruledgeSafeDiscards($ctx)===false,'Optional cost planning preserves both required Fire and Lunar resources');
$lunarUses=0;$cerAttacks=0;
foreach(['brisbane-lopunny','dhelmise-v2'] as $opponent)foreach([1,42,77] as $seed)foreach([1,2] as $first){
    $r=PokeSimulateGame($seed,$first,1500,'ceruledge-fighting',$opponent,null,null,function($a)use(&$lunarUses,&$cerAttacks){
        if($a['player']!==1)return;
        if($a['type']==='ability'&&GetZoneObject($a['source'])->CardID==='me01-074')++$lunarUses;
        if($a['type']==='attack'&&GetZoneObject($a['source'])->CardID==='sv08-036')++$cerAttacks;
    });
    ftCheck($r['status']==='complete','Completed match vs '.$opponent.' seed '.$seed.' first '.$first.': '.$r['reason']);
}
ftCheck($lunarUses>0&&$cerAttacks>0,'Smoke games use Lunar Cycle and attack with Ceruledge');
echo "PASS $checks Fighting checks; 12 completed matches; $lunarUses Lunar Cycles; $cerAttacks Ceruledge attacks\n";
