<?php
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
set_error_handler(function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);});
$read=fn($name)=>json_decode(file_get_contents(__DIR__.'/fixtures/'.$name.'.json'),true,512,JSON_THROW_ON_ERROR);
$screen=$read('lunar-opening-full-501');$fresh=$read('lunar-opening-full-701');
$prefix=$read('lunar-opening-prefix-501');$energy=$read('ceruledge-fighting-energy-splits-501');
$baseline=[];
foreach($energy['variants']['baseline']['games'] as $i=>$r){$r['lunar']=$prefix['variants']['baseline']['games'][$i]['lunar'];$baseline[]=$r;}
$baseline=array_merge($baseline,$fresh['variants']['baseline']['games']);
$updated=array_merge($screen['variants']['t1-fuel']['games'],$fresh['variants']['t1-fuel']['games']);
$checked=0;$fullChecked=0;
foreach($updated as $i=>$r){
    PokeCreateGame(PokeNamedDeck('brisbane-lopunny'),PokeNamedDeck('ceruledge-fighting'),$r['seed'],$r['first']);
    for($step=0;$step<1500&&!GetWinner();++$step){
        if(PokeVar('openingStats')[2]['lunarCycleTurnOne']['complete'])break;
        PokeApplyAction(PokeBotChoose(PokeObservation(PokePendingPlayer())));
    }
    if(PokeVar('openingStats')[2]['lunarCycleTurnOne']!==$r['lunar'])throw new RuntimeException('Opening differs from tested policy: seed '.$r['seed'].' first '.$r['first']);
    ++$checked;
    if($i%50===0){
        $full=PokeSimulateGame($r['seed'],$r['first'],1500,'brisbane-lopunny','ceruledge-fighting');
        if($full['status']!=='complete'||$full['winner']!==$r['winner']||$full['openingStats'][1]['lunarCycleTurnOne']!==$r['lunar'])throw new RuntimeException('Full replay differs from trial');
        ++$fullChecked;
    }
}
$sum=function($rows){
    $s=['games'=>count($rows),'wins'=>0,'activated'=>0,'firstActivated'=>0,'secondActivated'=>0,'attackBy2'=>0,'ready330By5'=>0];
    foreach($rows as $r){$s['wins']+=(int)($r['winner']===2);$s['activated']+=(int)$r['lunar']['activated'];$s[$r['first']===2?'firstActivated':'secondActivated']+=(int)$r['lunar']['activated'];foreach(['attackBy2','ready330By5'] as $k)$s[$k]+=(int)$r[$k];}
    return $s;
};
$out=['adoptedPolicy'=>PokeSimulationPolicy(),'baseline'=>$sum($baseline),'updated'=>$sum($updated),'verifiedOpenings'=>$checked,'verifiedFullGames'=>$fullChecked,'sources'=>['ceruledge-fighting-energy-splits-501','lunar-opening-prefix-501','lunar-opening-full-501','lunar-opening-full-701']];
foreach(['win','lunar'] as $metric){
    $d=[];for($i=0;$i<count($baseline);$i+=2){$delta=0;foreach([$i,$i+1] as $j)$delta+=$metric==='win'?(int)($updated[$j]['winner']===2)-(int)($baseline[$j]['winner']===2):(int)$updated[$j]['lunar']['activated']-(int)$baseline[$j]['lunar']['activated'];$d[]=$delta/2;}
    $mean=array_sum($d)/count($d);$variance=array_sum(array_map(fn($x)=>($x-$mean)**2,$d))/(count($d)-1);$e=1.96*sqrt($variance/count($d));
    $out[$metric.'Delta']=['estimate'=>$mean,'approx95CI'=>[$mean-$e,$mean+$e]];
}
file_put_contents(__DIR__.'/fixtures/lunar-opening-adoption-summary.json',json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
echo "PASS $checked adopted opening replays; $fullChecked full matches agree with the tested policy\n".json_encode($out,JSON_PRETTY_PRINT).PHP_EOL;
