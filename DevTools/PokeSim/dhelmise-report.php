<?php
/** Per-deck opening and replacement consistency; uses only the normal bot action surface. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
$policy=PokeSimulationPolicy();
$options=getopt('',['pairs:','seed:','trace:','opponent:','output:']);
$pairs=(int)($options['pairs']??100);$start=(int)($options['seed']??1);
if($pairs<1||$pairs>10000||$start<1||$start>2147483647-$pairs+1)throw new InvalidArgumentException('Invalid seed range');
$summary=['games'=>0,'complete'=>0,'secondOpenings'=>0,'boostedSecondOpenings'=>0,'firstTurnDamage'=>0,'establishedTurns'=>0,'establishedDamage'=>0,'establishedBoostedAttacks'=>0,'poweredBackupTurns'=>0];$misses=[];$traces=[];
for($seed=$start;$seed<$start+$pairs;$seed++)foreach([1,2] as $first){
    PokeCreateGame(PokeNamedDeck('sinistcha'),PokeNamedDeck($options['opponent']??'brisbane-lopunny'),$seed,$first);
    $opening=null;$trace=[];$established=false;$turnRecords=[];
    for($step=0;$step<1500&&!GetWinner();$step++){
        $seat=PokePendingPlayer();$view=PokeObservation($seat);$action=PokeBotChoose($view);
        if(!$action)throw new RuntimeException('Bot stalled');
        if($seat===1&&$view['phase']==='MAIN'){
            $turn=GetPlayerTurns(1);$hide=PokeCountHideSneak(1);$established=$established||$hide>=4;
            $source=isset($action['source'])?GetZoneObject($action['source'])->CardID:null;
            $target=isset($action['target'])?GetZoneObject($action['target'])->CardID:null;
            if($action['type']==='attack'){
                if($turn===1)$opening=['id'=>$source,'hide'=>$hide];
                if($established)$turnRecords[$turn]=['boosted'=>$source==='me05-039'&&$hide>=4,'backup'=>count(array_filter($view['players'][1]['Bench'],fn($c)=>$c['id']==='me05-039'&&count($c['energy'])>0))>0];
            }
            if($action['type']==='end'&&$established)$turnRecords[$turn]=['boosted'=>false,'backup'=>false];
            if(isset($options['trace'])&&$seed===(int)$options['trace']&&$first===2){
                $choices=[];if($action['type']==='decision')foreach($view['decision']['choices']??[] as $c)if(in_array($c['value'],explode('&',$action['value']),true))$choices[]=$c['label'];
                $trace[]=['turn'=>$turn,'hide'=>$hide,'type'=>$action['type'],'source'=>$source,'target'=>$target,'choices'=>$choices,'hand'=>array_column($view['players'][1]['Hand'],'name'),'field'=>array_map(fn($c)=>[$c['name'],count($c['energy'])],array_merge($view['players'][1]['Active'],$view['players'][1]['Bench']))];
            }
        }
        PokeApplyAction($action);
    }
    ++$summary['games'];if(GetWinner())++$summary['complete'];
    $rows=PokeVar('damageTurns',[]);
    if($first===2){++$summary['secondOpenings'];if($opening&&$opening['id']==='me05-039'&&$opening['hide']>=4)++$summary['boostedSecondOpenings'];else $misses[]=['seed'=>$seed,'attack'=>$opening];
        foreach($rows as $r)if($r['player']===1&&$r['turn']===1)$summary['firstTurnDamage']+=$r['damage'];}
    foreach($rows as $r)if($r['player']===1&&isset($turnRecords[$r['turn']])){++$summary['establishedTurns'];$summary['establishedDamage']+=$r['damage'];$summary['establishedBoostedAttacks']+=(int)$turnRecords[$r['turn']]['boosted'];$summary['poweredBackupTurns']+=(int)$turnRecords[$r['turn']]['backup'];}
    if($trace)$traces=$trace;
}
$summary['boostedOpeningRate']=$summary['boostedSecondOpenings']/$summary['secondOpenings'];
$summary['averageOpeningDamage']=$summary['firstTurnDamage']/$summary['secondOpenings'];
$summary['averageEstablishedDamage']=$summary['establishedTurns']?$summary['establishedDamage']/$summary['establishedTurns']:null;
$summary['poweredBackupRate']=$summary['establishedTurns']?$summary['poweredBackupTurns']/$summary['establishedTurns']:null;
$report=['policy'=>$policy,'summary'=>$summary,'openingMisses'=>$misses,'trace'=>$traces];
if(isset($options['output']))file_put_contents($options['output'],json_encode($report,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
echo json_encode($report,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
