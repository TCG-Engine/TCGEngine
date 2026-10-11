<?php
/** Paired Relicanth sustain probes; never saves session games. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
$options=getopt('',['pairs:','seed:','output:','variant:','trace:','opponent:']);
$variants=[
    'baseline'=>[],
    'recovery'=>['me05-073'=>-2,'me02.5-196'=>1,'sv10.5w-082'=>1],
    'draw-energy'=>['me05-073'=>-2,'sv07-129'=>-2,'me01-114'=>-1,'sv08-165'=>2,'mee-003'=>2,'me02.5-196'=>1],
    'lean'=>['me05-073'=>-2,'sv07-129'=>-2,'sv07-130'=>-2,'sv10.5w-080'=>-1,'me01-114'=>-1,'sv08-165'=>2,'mee-003'=>2,'me02.5-196'=>1,'sv10.5w-082'=>2,'me03-072'=>1],
    'lean-box'=>['me05-073'=>-2,'sv07-129'=>-2,'sv07-130'=>-2,'sv10.5w-080'=>-1,'me01-114'=>-2,'sv08-165'=>2,'mee-003'=>2,'me02.5-196'=>1,'sv10.5w-082'=>2,'me03-072'=>1,'sv06-163'=>1],
    'cycle-16'=>['me05-073'=>-1,'sv07-129'=>-1,'sv07-130'=>-2,'sv10.5w-080'=>-1,'me01-114'=>-2,'sv08-165'=>1,'mee-003'=>2,'me02.5-196'=>1,'sv10.5w-082'=>2,'sv06-163'=>1],
    'cycle-12'=>['me05-073'=>-3,'sv07-129'=>-3,'sv07-130'=>-2,'sv10.5w-080'=>-1,'me01-114'=>-2,'sv08-165'=>3,'mee-003'=>2,'me02.5-196'=>1,'sv10.5w-082'=>2,'me03-072'=>2,'sv06-163'=>1],
    'draw-box'=>['me05-073'=>-2,'sv07-129'=>-2,'me01-114'=>-2,'sv08-165'=>2,'mee-003'=>2,'me02.5-196'=>1,'sv06-163'=>1],
];
$variant=$options['variant']??'baseline';
if(!isset($variants[$variant]))throw new InvalidArgumentException('Unknown variant');
$deck=PokeParseDeckText(file_get_contents(__DIR__.'/fixtures/relicanth-sustain-baseline.txt'));
foreach($variants[$variant] as $id=>$delta){
    $found=false;foreach($deck as &$entry)if($entry['id']===$id){$entry['count']+=$delta;$found=true;break;}unset($entry);
    if(!$found)$deck[]=['id'=>$id,'count'=>$delta];
}
$deck=array_values(array_filter($deck,fn($e)=>$e['count']>0));
if($errors=PokeValidateDeck($deck))throw new InvalidArgumentException(implode('; ',$errors));
$policy=PokeSimulationPolicy();
$pairs=(int)($options['pairs']??100);$seed=(int)($options['seed']??200001);
if($pairs<1||$pairs>10000||$seed<1||$seed>2147483647-$pairs)throw new InvalidArgumentException('Invalid range');
$opponent=$options['opponent']??'dhelmise-v2';$rows=[];$turns=[];$traces=[];$attackFossils=[];
for($i=0;$i<$pairs;++$i)foreach([1,2] as $first){
    PokeCreateGame($deck,PokeNamedDeck($opponent),$seed+$i,$first);$steps=0;$trace=[];
    while(!GetWinner()&&$steps<1500){
        $view=PokeObservation(PokePendingPlayer());$action=PokeBotChoose($view);
        if($view['viewer']===1&&$action['type']==='attack'){
            $n=count(array_filter($view['players'][1]['Bench'],fn($c)=>PokeIsFossil($c['id'])));
            $attackFossils[$n]=($attackFossils[$n]??0)+1;
        }
        if(isset($options['trace'])&&$view['viewer']===1&&$view['phase']==='MAIN'){
            $ctx=PokeBotContext($view);
            $trace[]=['turn'=>$ctx['own']['turns'],'action'=>$action,'prompt'=>$view['decision']['prompt']??'',
                'active'=>$ctx['own']['Active'],'bench'=>array_column($ctx['own']['Bench'],'id'),
                'hand'=>$ctx['hand'],'discard'=>array_count_values(array_column($ctx['own']['Discard'],'id'))];
        }
        PokeApplyAction($action);++$steps;
    }
    $row=['seed'=>$seed+$i,'firstPlayer'=>$first,'winner'=>GetWinner(),'status'=>GetWinner()?'complete':'capped','turns'=>GetTurnNumber(),'actions'=>$steps];
    foreach(PokeVar('damageTurns',[]) as $t)if($t['player']===1&&$t['complete']){
        $key=$t['turn'];$turns[$key]??=['samples'=>0,'damage'=>0,'attacks'=>0];
        ++$turns[$key]['samples'];$turns[$key]['damage']+=$t['damage'];$turns[$key]['attacks']+=(int)($t['damage']>0);
    }
    $rows[]=$row;if(isset($options['trace']))$traces[]=['seed'=>$seed+$i,'first'=>$first,'trace'=>$trace];
}
ksort($turns);foreach($turns as &$t){$t['average']=$t['damage']/$t['samples'];$t['attackRate']=$t['attacks']/$t['samples'];}unset($t);
$out=['variant'=>$variant,'policy'=>$policy,'seed'=>$seed,'pairs'=>$pairs,'opponent'=>$opponent,'deck'=>$deck,
    'wins'=>count(array_filter($rows,fn($r)=>$r['winner']===1)),'incomplete'=>count(array_filter($rows,fn($r)=>$r['status']!=='complete')),'attackFossils'=>$attackFossils,'turns'=>$turns,'results'=>$rows,'traces'=>$traces];
if(isset($options['output'])){if(file_exists($options['output']))throw new RuntimeException('Output exists');file_put_contents($options['output'],json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));}
unset($out['results'],$out['traces'],$out['deck']);echo json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
