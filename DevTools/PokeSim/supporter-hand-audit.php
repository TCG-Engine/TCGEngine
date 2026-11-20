<?php
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
require __DIR__.'/SupporterHandAudit.php';
set_error_handler(function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);});
$pairs=(int)($argv[1]??100);$seedStart=(int)($argv[2]??901);
if($pairs<1||$pairs>1000||$seedStart<1)throw new InvalidArgumentException('Invalid study size');
$out=['policy'=>PokeSimulationPolicy(),'deck'=>PokeNamedDeck('ceruledge-fighting'),'opponent'=>'brisbane-lopunny','games'=>[],
    'notes'=>['Each entry is one continuous hand visit, not a unique physical card. Shuffling and redrawing starts a new visit.',
    'Carryovers count completed own turns with a usable action window while the card was held; prize draws after attacking do not count for that turn.',
    'Remaining cards are censored at game end. Low usage alone is not evidence a situational card should be cut.',
    'Legal card-turns are deduplicated across copies; legal carryovers are counted per hand visit. These denominators differ.']];
for($seed=$seedStart;$seed<$seedStart+$pairs;++$seed)foreach([1,2] as $first){
    PokeCreateGame(PokeNamedDeck('brisbane-lopunny'),PokeNamedDeck('ceruledge-fighting'),$seed,$first);$audit=new PokeSupporterHandAudit(2);
    for($i=0;$i<1500&&!GetWinner();++$i){$v=PokeObservation(PokePendingPlayer());$a=PokeBotChoose($v);$audit->beforeAction($v,$a);PokeApplyAction($a);$audit->afterAction();}
    if(!GetWinner())throw new RuntimeException('Incomplete audit game');
    $out['games'][]=['seed'=>$seed,'first'=>$first,'winner'=>GetWinner()]+$audit->finish();
}
$cards=[];
foreach($out['games'] as $g){
    $seen=[];$played=[];
    foreach($g['visits'] as $v){$id=$v['card'];$seen[$id]=true;
        if(!isset($cards[$id]))$cards[$id]=['name'=>CardName($id),'entries'=>0,'seenGames'=>0,'playedGames'=>0,'legalCardTurns'=>0,'carryovers'=>[],'carriedTwoPlus'=>0,'legalCarryovers'=>0,'supporterLimitCarryovers'=>0,'firstTurnRuleCarryovers'=>0,'exits'=>['played'=>0,'discarded'=>0,'shuffled'=>0,'remaining'=>0,'other'=>0],'effects'=>0,'cardsGained'=>0,'energyDiscardDelta'=>0,'playedBy3'=>0,'competition'=>[]];
        $s=&$cards[$id];++$s['entries'];$s['carryovers'][]=$v['carryovers'];$s['carriedTwoPlus']+=(int)($v['carryovers']>=2);++$s['exits'][$v['exit']];
        foreach(['legalCarryovers','supporterLimitCarryovers','firstTurnRuleCarryovers'] as $k)$s[$k]+=$v[$k];
        if($v['exit']==='played'){$played[$id]=true;$s['playedBy3']+=(int)($v['exitTurn']<=3);}unset($s);
    }
    foreach(array_keys($seen) as $id)++$cards[$id]['seenGames'];foreach(array_keys($played) as $id)++$cards[$id]['playedGames'];
    foreach($g['legalCardTurns'] as $id=>$turns)$cards[$id]['legalCardTurns']+=count($turns);
    foreach($g['effects'] as $e){$id=$e['card'];++$cards[$id]['effects'];$cards[$id]['cardsGained']+=$e['cardsGained'];$cards[$id]['energyDiscardDelta']+=$e['energyDiscardDelta'];}
    foreach($g['competition'] as $id=>$wins)foreach($wins as $other=>$n)$cards[$id]['competition'][$other]=($cards[$id]['competition'][$other]??0)+$n;
}
foreach($cards as &$s){sort($s['carryovers']);$n=count($s['carryovers']);$s['averageCarryovers']=array_sum($s['carryovers'])/$n;$s['medianCarryovers']=($s['carryovers'][intdiv($n-1,2)]+$s['carryovers'][intdiv($n,2)])/2;unset($s['carryovers']);}unset($s);
$out['summary']=['games'=>count($out['games']),'wins'=>count(array_filter($out['games'],fn($g)=>$g['winner']===2)),'cards'=>$cards];
file_put_contents(__DIR__.'/fixtures/supporter-hand-audit-'.$seedStart.'.json',json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
echo json_encode($out['summary'],JSON_PRETTY_PRINT).PHP_EOL;
