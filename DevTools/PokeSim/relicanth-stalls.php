<?php
/** Diagnostic-only audit of zero damage. Hidden own cards are read for reporting,
 * never passed into the bot policy. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
$options=getopt('',['pairs:','seed:','opponent:','output:']);
$pairs=(int)($options['pairs']??100);$seed=(int)($options['seed']??260001);$opponent=$options['opponent']??'dhelmise-v2';
if($pairs<1||$pairs>10000||$seed<1||$seed>2147483647-$pairs)throw new InvalidArgumentException('Invalid range');
$policy=PokeSimulationPolicy();$counts=[];$turns=[];$stalls=[];$completed=0;
for($i=0;$i<$pairs;++$i)foreach([1,2] as $first){
    PokeCreateGame(PokeNamedDeck('relicanth-fossils'),PokeNamedDeck($opponent),$seed+$i,$first);$snapshots=[];
    for($step=0;$step<1500&&!GetWinner();++$step){
        $view=PokeObservation(PokePendingPlayer());$a=PokeBotChoose($view);
        if($view['viewer']===1&&$view['phase']==='MAIN'&&!$view['decision']&&in_array($a['type'],['end','attack'],true)){
            $ctx=PokeBotContext($view);$active=$ctx['own']['Active'][0]??null;
            $legalAttack=(bool)array_filter($view['actions'],fn($x)=>$x['type']==='attack');
            $reason=$first===1&&$ctx['own']['turns']===1?'first-turn restriction':
                (!$active||$active['id']!=='me05-017'?'no Active Relicanth':
                (PokeRelicanthEnergyNeeded($ctx)>0?'missing attack Energy':
                (!empty($active['conditions']['Asleep'])||!empty($active['conditions']['Paralyzed'])?'status condition':
                ($a['type']==='attack'?'attack dealt zero':($legalAttack?'legal attack skipped':'other restriction')))));
            $locations=[];foreach(['Active','Bench','Hand','Deck','Discard','Prizes'] as $zone)
                $locations[$zone]=count(array_filter(PokeObjects(1,$zone),fn($c)=>$c->CardID==='me05-017'));
            $trainers=[];foreach($view['actions'] as $x)if($x['type']==='trainer'){
                $id=PokeBotCard($ctx,$x['source'])['id'];$trainers[]=['id'=>$id,'score'=>PokeRelicanthActionScore($ctx,$x)];
            }
            $snapshots[count(PokeVar('damageTurns',[]))-1]=['seed'=>$seed+$i,'firstPlayer'=>$first,
                'turn'=>$ctx['own']['turns'],'reason'=>$reason,'action'=>$a['type'],'active'=>$active,
                'relicanthLocations'=>$locations,'hand'=>$ctx['hand'],'energyUsed'=>$ctx['own']['energyUsed'],
                'supporterUsed'=>$ctx['own']['supporterUsed'],'legalTrainers'=>$trainers];
        }
        PokeApplyAction($a);
    }
    if(!GetWinner())throw new RuntimeException('Incomplete game');++$completed;
    foreach(PokeVar('damageTurns',[]) as $index=>$row)if($row['player']===1&&$row['complete']){
        $turns[$row['turn']]??=['samples'=>0,'zero'=>0];++$turns[$row['turn']]['samples'];
        if($row['damage']!==0)continue;++$turns[$row['turn']]['zero'];
        $stall=$snapshots[$index]??['reason'=>'unclassified','seed'=>$seed+$i,'turn'=>$row['turn']];
        $counts[$stall['reason']]=($counts[$stall['reason']]??0)+1;$stalls[]=$stall;
    }
}
ksort($turns);$out=['policy'=>$policy,'games'=>$completed,'seed'=>$seed,'opponent'=>$opponent,'counts'=>$counts,'turns'=>$turns,'stalls'=>$stalls];
if(isset($options['output'])){if(file_exists($options['output']))throw new RuntimeException('Output exists');file_put_contents($options['output'],json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));}
unset($out['stalls']);echo json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
