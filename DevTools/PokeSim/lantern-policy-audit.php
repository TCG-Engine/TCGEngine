<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
$options=getopt('',['seeds:','start:','output:','detail']);$seeds=(int)($options['seeds']??100);$start=(int)($options['start']??1);
if($seeds<1||$seeds>1000||$start<1)throw new InvalidArgumentException('Use 1–1000 seeds and a positive start');
$bins=[];$attacks=[];$traces=[];$completed=0;$wins=0;$errors=[];
foreach(range($start,$start+$seeds-1) as $seed)foreach([1,2] as $first){
    PokeCreateGame(PokeNamedDeck('ceruledge-lantern'),PokeNamedDeck('brisbane-lopunny'),$seed,$first);$trace=[];
    try{
        for($step=0;$step<1500&&!GetWinner();++$step){
            $p=PokePendingPlayer();$view=PokeObservation($p);$a=PokeBotChoose($view);
            if(!$a)throw new RuntimeException('No action');
            if($p===1 && GetPlayerTurns(1)<=4){
                $source=isset($a['source'])?GetZoneObject($a['source'])->CardID:null;
                $row=['phase'=>GetCurrentPhase(),'turn'=>GetPlayerTurns(1),'type'=>$a['type'],'source'=>$source,'target'=>isset($a['target'])?GetZoneObject($a['target'])->CardID:null];
                if($a['type']==='decision'){$row['prompt']=$view['decision']['prompt'];$row['choices']=array_column(array_filter($view['decision']['choices'],fn($c)=>in_array($c['value'],explode('&',$a['value']),true)),'card');}
                if($a['type']==='attack'){$row['index']=$a['index'];$key=($first===2?'second':'first').':'.GetPlayerTurns(1).':'.$source.':'.$a['index'];$attacks[$key]=($attacks[$key]??0)+1;}
                if(isset($options['detail']) && in_array($a['type'],['attack','end'],true)){
                    $row['hand']=array_count_values(array_column($view['players'][1]['Hand'],'id'));
                    $row['field']=array_map(fn($c)=>['id'=>$c['id'],'ref'=>$c['ref'],'energy'=>$c['energy']],array_merge($view['players'][1]['Active'],$view['players'][1]['Bench']));
                    $ctx=PokeCeruledgeLanternContext($view);$row['discardEnergy']=$ctx['discardEnergy'];$row['deckCount']=$ctx['own']['deckCount'];
                    $row['topScores']=array_map(fn($action)=>['action'=>$action,'score'=>PokeCeruledgeLanternActionScore($ctx,$action)],$view['actions']);
                    usort($row['topScores'],fn($a,$b)=>$b['score']<=>$a['score']);$row['topScores']=array_slice($row['topScores'],0,6);
                }
                $trace[]=$row;
            }
            PokeApplyAction($a);
        }
        if(!GetWinner())throw new RuntimeException('Action cap');++$completed;if(GetWinner()===1)++$wins;
        foreach(PokeVar('damageTurns',[]) as $row)if($row['player']===1 && $row['complete'])$bins[$row['order']][$row['turn']][]=$row['damage'];
        if($seed<$start+5 && $first===2)$traces[$seed]=$trace;
    }catch(Throwable $e){$errors[]=['seed'=>$seed,'first'=>$first,'error'=>$e->getMessage()];}
}
$summary=[];
foreach($bins as $order=>$turns)foreach($turns as $turn=>$damage){sort($damage);$n=count($damage);$summary[$order][$turn]=['samples'=>$n,'average'=>round(array_sum($damage)/$n,2),'median'=>($damage[intdiv($n-1,2)]+$damage[intdiv($n,2)])/2,'zero'=>count(array_filter($damage,fn($d)=>$d===0))];}
$result=['policy'=>PokeSimulationPolicy(),'start'=>$start,'seeds'=>$seeds,'completed'=>$completed,'wins'=>$wins,'errors'=>$errors,'damage'=>$summary,'attacks'=>$attacks,'traces'=>$traces];
if(isset($options['output']))file_put_contents($options['output'],json_encode($result,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL);
unset($result['traces']);echo json_encode($result,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;
exit($errors?1:0);
