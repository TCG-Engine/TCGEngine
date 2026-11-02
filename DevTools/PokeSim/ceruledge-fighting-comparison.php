<?php
/** Matched current selectable Ceruledge policies versus Brisbane Lopunny. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
$pairs=(int)($argv[1]??200);
if($pairs<1||$pairs>10000)throw new InvalidArgumentException('Invalid pairs');
$out=['policy'=>PokeSimulationPolicy(),'opponent'=>'brisbane-lopunny','pairs'=>$pairs,'decks'=>[],'games'=>[]];
foreach(['ceruledge-ex','ceruledge-fighting'] as $deck){
    $out['decks'][$deck]=PokeNamedDeck($deck);
    for($seed=1;$seed<=$pairs;++$seed)foreach([1,2] as $first){
        $metrics=['attackBy2'=>false,'ready330By4'=>false,'ready330By5'=>false];
        $r=PokeSimulateGame($seed,$first,1500,'brisbane-lopunny',$deck,null,null,function($action)use(&$metrics){
            $view=PokeObservation(2);
            if($view['phase']!=='MAIN'||$view['turnPlayer']!==2||$view['players'][2]['turns']>5)return;
            $ctx=PokeCeruledgeContext($view);$turn=$ctx['own']['turns'];
            $canAttack=(bool)array_filter($view['actions'],fn($a)=>$a['type']==='attack'&&(PokeBotCard($ctx,$a['source'])['id']??'')==='sv08-036');
            if($canAttack&&$turn<=2)$metrics['attackBy2']=true;
            if($canAttack&&$ctx['discardEnergy']>=15){$metrics['ready330By5']=true;if($turn<=4)$metrics['ready330By4']=true;}
        });
        unset($r['openingStats'],$r['damageTurns']);$out['games'][$deck][]=array_merge($r,$metrics);
        if($r['status']!=='complete')throw new RuntimeException(json_encode($r));
        if($first===2&&$seed%25===0)echo "$deck: $seed/$pairs seed pairs complete\n";
    }
}
$sums=[];
foreach($out['games'] as $deck=>$rows){
    $s=['games'=>count($rows),'wins'=>0,'firstWins'=>0,'secondWins'=>0,'attackBy2'=>0,'ready330By4'=>0,'ready330By5'=>0];
    foreach($rows as $r){$won=$r['winner']===2;$s['wins']+=(int)$won;$s[$r['firstPlayer']===2?'firstWins':'secondWins']+=(int)$won;foreach(['attackBy2','ready330By4','ready330By5'] as $k)$s[$k]+=(int)$r[$k];}
    $sums[$deck]=$s;
}
// Pair starting orders within each seed before estimating uncertainty.
$deltas=[];$a=$out['games']['ceruledge-ex'];$b=$out['games']['ceruledge-fighting'];
for($i=0;$i<count($a);$i+=2)$deltas[]=((int)($b[$i]['winner']===2)+(int)($b[$i+1]['winner']===2)-(int)($a[$i]['winner']===2)-(int)($a[$i+1]['winner']===2))/2;
$mean=array_sum($deltas)/$pairs;$variance=$pairs>1?array_sum(array_map(fn($x)=>($x-$mean)**2,$deltas))/($pairs-1):0;
$out['summary']=$sums;$out['pairedWinDelta']=['estimate'=>$mean,'approx95CI'=>[$mean-1.96*sqrt($variance/$pairs),$mean+1.96*sqrt($variance/$pairs)]];
$path=__DIR__.'/fixtures/ceruledge-fighting-lopunny-comparison.json';
file_put_contents($path,json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL);
echo json_encode(['summary'=>$sums,'pairedWinDelta'=>$out['pairedWinDelta']],JSON_PRETTY_PRINT).PHP_EOL;
