<?php
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
require __DIR__.'/SupporterHandAudit.php';
set_error_handler(function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);});
$checks=0;
function handCheck($ok,$label){global $checks;++$checks;if(!$ok)throw new RuntimeException($label);}
function handBoard(array $hand,array $deck=[],int $first=2): void {
    InitializeGamestate();$GLOBALS['playerID']=1;$GLOBALS['currentPlayer']=1;SetRandomState(42);SetFirstPlayer($first);SetTurnPlayer(1);SetCurrentPhase('MAIN');SetTurnNumber(2);
    foreach([1,2] as $seat){AddPlayerTurns($seat,1);AddSetupReady($seat,true);$c=PokeAdd($seat,'Active','me02-019');$c->Controller=$seat;$c->Energy=['mee-002'];for($i=0;$i<6;++$i)PokeAdd($seat,'Prizes','mee-002');}
    foreach($hand as $id)PokeAdd(1,'Hand',$id);
    foreach($deck?:array_fill(0,20,'mee-002') as $id)PokeAdd(1,'Deck',$id);
    for($i=0;$i<20;++$i)PokeAdd(2,'Deck','mee-002');
    PokeDamageStartTurn(1);
}
function handAct(PokeSupporterHandAudit $audit,array $action): void {$audit->beforeAction(PokeObservation($action['player']),$action);PokeApplyAction($action);$audit->afterAction();}
handBoard(['sv06-145','me02.5-192','me02.5-192','me02.5-183']);$audit=new PokeSupporterHandAudit(1);
$state=PokeStateExport();$audit->beforeAction(PokeObservation(1),['type'=>'trainer','player'=>1,'source'=>'p1Hand-0']);
handCheck(PokeStateExport()===$state,'Instrumentation leaves authoritative state unchanged');
$audit=new PokeSupporterHandAudit(1);
handAct($audit,['type'=>'trainer','player'=>1,'source'=>'p1Hand-0']);$r=$audit->finish();
handCheck(count($r['visits'])===4,'Identical copies remain separate hand visits');
handCheck(count(array_filter($r['visits'],fn($v)=>$v['exit']==='played'))===1&&count(array_filter($r['visits'],fn($v)=>$v['exit']==='discarded'))===3,'Carmine itself is played; its discarded Supporters are not');
handCheck($r['effects'][0]['cardsGained']===5,'Carmine draw contribution captured');
handCheck($r['competition']['me02.5-192']['sv06-145']===1,'Duplicate copies do not double-count a lost Supporter slot');
handBoard(['me02.5-192','me02.5-192','me02.5-183'],array_fill(0,6,'mee-002'));$audit=new PokeSupporterHandAudit(1);
handAct($audit,['type'=>'trainer','player'=>1,'source'=>'p1Hand-0']);$r=$audit->finish();
handCheck(count($r['visits'])===5,'Shuffling and redrawing creates new hand visits');
handCheck(count(array_filter($r['visits'],fn($v)=>$v['exit']==='shuffled'))===2&&count(array_filter($r['visits'],fn($v)=>$v['exit']==='remaining'))===2,'Redrawn cards are not conflated with shuffled-out copies');
handCheck($r['effects'][0]['cardsGained']===8,'Lillie draw contribution counts the redrawn hand');
handBoard(['me03-081','me02.5-192'],['me02-019','mee-002']);$audit=new PokeSupporterHandAudit(1);
handAct($audit,['type'=>'trainer','player'=>1,'source'=>'p1Hand-0']);handAct($audit,['type'=>'decision','player'=>1,'value'=>'-']);$r=$audit->finish();
handCheck(count($r['visits'])===1,'Hand compaction does not reset residence');
handBoard(['me02.5-192']);$audit=new PokeSupporterHandAudit(1);
foreach([1,2,1] as $seat)handAct($audit,['type'=>'end','player'=>$seat]);$r=$audit->finish();
handCheck($r['visits'][0]['carryovers']===2&&count($r['visits'][0]['legalTurns'])===2,'Counts completed own turns, not opposing turns or actions');
handCheck($r['visits'][0]['legalCarryovers']===2&&$r['visits'][0]['supporterLimitCarryovers']===0,'Legal unused turns are separated from the Supporter limit');
handBoard(['sv06-145'],array_merge(array_fill(0,5,'me02.5-192'),array_fill(0,10,'mee-002')));$audit=new PokeSupporterHandAudit(1);
handAct($audit,['type'=>'trainer','player'=>1,'source'=>'p1Hand-0']);handAct($audit,['type'=>'end','player'=>1]);$r=$audit->finish();
$held=array_values(array_filter($r['visits'],fn($v)=>$v['card']==='me02.5-192'));
handCheck(count($held)===5&&array_sum(array_column($held,'supporterLimitCarryovers'))===5&&array_sum(array_column($held,'legalCarryovers'))===0,'Supporters drawn after Carmine are blocked, not passed over while legal');
handBoard(['sv08.5-107'],array_fill(0,10,'mee-002'));$audit=new PokeSupporterHandAudit(1);
handAct($audit,['type'=>'trainer','player'=>1,'source'=>'p1Hand-0']);
handCheck(!$audit->effects,'Interactive Supporter effect is not counted before its choices finish');
$d=PokeDecisionOptions(1);handAct($audit,['type'=>'decision','player'=>1,'value'=>implode('&',array_slice(array_column($d['choices'],'value'),0,2))]);$r=$audit->finish();
handCheck($r['effects'][0]['cardsGained']===2&&$r['effects'][0]['energyDiscardDelta']===4,'Explorer contribution includes kept cards and milled Energy after resolution');
$path=__DIR__.'/fixtures/supporter-hand-audit-901.json';
if(is_file($path)){
    $data=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
    foreach(array_slice($data['games'],0,6) as $g){$plain=PokeSimulateGame($g['seed'],$g['first'],1500,'brisbane-lopunny','ceruledge-fighting');handCheck($plain['status']==='complete'&&$plain['winner']===$g['winner'],'Audited match winner agrees with an uninstrumented replay');}
}
echo "PASS $checks Supporter hand audit checks\n";
