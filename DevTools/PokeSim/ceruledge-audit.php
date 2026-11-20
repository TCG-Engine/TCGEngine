<?php
/** Reproducible pacing audit; does not read or modify browser session games. */
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
$options=getopt('', ['pairs:','seed:','out:']);
$pairs=(int)($options['pairs']??100);$startSeed=(int)($options['seed']??1);
$output=['policy'=>PokeSimulationPolicy(),'pairs'=>$pairs,'startSeed'=>$startSeed,'games'=>[],'summary'=>[]];
foreach(range($startSeed,$startSeed+$pairs-1) as $seed)foreach([1,2] as $first){
    PokeCreateGame(PokeNamedDeck('brisbane-lopunny'),PokeNamedDeck('ceruledge-ex'),$seed,$first);
    $game=['seed'=>$seed,'order'=>$first===2?'first':'second','turns'=>[],'trace'=>[]];$steps=0;
    while(!GetWinner()&&$steps++<1500){
        $seat=PokePendingPlayer();$view=PokeObservation($seat);$action=PokeBotChoose($view);
        if(!$action)throw new RuntimeException('No action');
        if($seat===2&&$view['phase']==='MAIN'&&$view['turnPlayer']===2){
            $turn=$view['players'][2]['turns'];$ctx=PokeCeruledgeContext($view);
            $entry=['turn'=>$turn,'action'=>$action['type'],'card'=>isset($action['source'])?(PokeBotCard($ctx,$action['source'])['id']??null):null,
                'discard'=>$ctx['discardEnergy'],'handFire'=>$ctx['fire'],'deck'=>$ctx['own']['deckCount'],'hand'=>$ctx['hand'],
                'active'=>$ctx['own']['Active'][0]['id']??null,'lines'=>$ctx['lines']];
            if($view['decision']){
                $entry['prompt']=$view['decision']['prompt'];$entry['selected']=[];
                foreach(explode('&',$action['value']??'') as $ref)foreach($view['decision']['choices'] as $choice)if($choice['value']===$ref)$entry['selected'][]=$choice['card'];
            }
            $game['trace'][]=$entry;
            $canAttackEx=(bool)array_filter($view['actions'],fn($a)=>$a['type']==='attack'&&PokeBotCard($ctx,$a['source'])['id']==='sv08-036');
            $game['turns'][$turn]=['discard'=>$ctx['discardEnergy'],'baseDamage'=>30+20*$ctx['discardEnergy'],'handFire'=>$ctx['fire'],'deck'=>$ctx['own']['deckCount'],
                'hasEx'=>(bool)PokeBotFieldCount($ctx,['sv08-036']),'canAttackEx'=>$canAttackEx];
            $game['turns'][$turn]['ready330']=($game['ready330'][$turn]??false)||($canAttackEx&&$ctx['discardEnergy']>=15);
            $game['ready330'][$turn]=$game['turns'][$turn]['ready330'];
        }
        PokeApplyAction($action);
    }
    $game['winner']=GetWinner();$game['complete']=GetWinner()>0;$game['damageTurns']=PokeVar('damageTurns',[]);
    foreach($game['damageTurns'] as $row)if($row['player']===2&&isset($game['turns'][$row['turn']]))$game['turns'][$row['turn']]['damage']=$row['damage'];
    $output['games'][]=$game;
}
foreach(['first','second'] as $order){
    $games=array_values(array_filter($output['games'],fn($g)=>$g['order']===$order));
    $summary=['games'=>count($games),'completed'=>count(array_filter($games,fn($g)=>$g['complete'])),'wins'=>count(array_filter($games,fn($g)=>$g['winner']===2)),'turns'=>[]];
    foreach(range(1,8) as $turn){
        $rows=[];foreach($games as $g)if(isset($g['turns'][$turn]))$rows[]=$g['turns'][$turn];
        if(!$rows)continue;$n=count($rows);
        $summary['turns'][$turn]=['reached'=>$n,'avgDiscard'=>round(array_sum(array_column($rows,'discard'))/$n,2),
            'avgDamage'=>round(array_sum(array_column($rows,'damage'))/$n,1),
            'discard15'=>count(array_filter($rows,fn($r)=>$r['discard']>=15)),
            'ready330'=>count(array_filter($rows,fn($r)=>$r['ready330'])),
            'ex'=>count(array_filter($rows,fn($r)=>$r['hasEx']))];
    }
    $output['summary'][$order]=$summary;
}
if(isset($options['out']))file_put_contents($options['out'],json_encode($output,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n");
echo json_encode($output['summary'],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
