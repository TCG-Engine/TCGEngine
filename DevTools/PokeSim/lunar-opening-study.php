<?php
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
require __DIR__.'/LunarOpeningPolicy.php';
set_error_handler(function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);});
$pairs=(int)($argv[1]??200);$start=(int)($argv[2]??501);$mode=$argv[3]??'prefix';
$variants=explode(',',$argv[4]??'baseline,t1-order,t1-search,t1-fuel');
$studyName=$argv[5]??'lunar-opening';
if($pairs<1||$start<1||!preg_match('/^[a-z-]+$/D',$studyName)||!in_array($mode,['prefix','full'],true)||array_diff($variants,['baseline','t1-order','t1-search','t1-fuel','deck-lillie-to-pad','deck-explorer-to-pad','deck-explorer-to-naveen']))throw new InvalidArgumentException('Invalid study options');
$out=['policy'=>PokeSimulationPolicy(),'trialHash'=>hash_file('sha256',__DIR__.'/LunarOpeningPolicy.php'),'pairs'=>$pairs,'start'=>$start,'mode'=>$mode,'variants'=>[]];
foreach($variants as $variant){
    $deck=PokeNamedDeck('ceruledge-fighting');
    if($variant==='deck-lillie-to-pad'){
        foreach($deck as &$entry){if($entry['id']==='me02.5-192')--$entry['count'];if($entry['id']==='me03-081')++$entry['count'];}unset($entry);
    }
    if(in_array($variant,['deck-explorer-to-pad','deck-explorer-to-naveen'],true)){
        foreach($deck as &$entry){if($entry['id']==='sv08.5-107')--$entry['count'];if($variant==='deck-explorer-to-pad'&&$entry['id']==='me03-081')++$entry['count'];}unset($entry);
        if($variant==='deck-explorer-to-naveen')$deck[]=['id'=>'me03-079','count'=>1];
    }
    $errors=PokeValidateDeck($deck);if($errors)throw new RuntimeException(implode('; ',$errors));
    $rows=[];
    for($seed=$start;$seed<$start+$pairs;++$seed)foreach([1,2] as $first){
        PokeCreateGame(PokeNamedDeck('brisbane-lopunny'),$deck,$seed,$first);
        $r=['seed'=>$seed,'first'=>$first,'attackBy2'=>false,'ready330By5'=>false];
        for($i=0;$i<1500&&!GetWinner();++$i){
            if($mode==='prefix'&&PokeVar('openingStats')[2]['lunarCycleTurnOne']['complete'])break;
            $v=PokeObservation(PokePendingPlayer());
            $a=$v['viewer']===2&&str_starts_with($variant,'t1-')?lunarTrialChoose($v,$variant):PokeBotChoose($v);
            if($mode==='full'&&$v['viewer']===2&&$v['phase']==='MAIN'&&$v['players'][2]['turns']<=5){
                $ctx=PokeCeruledgeFightingContext($v);$can=(bool)array_filter($v['actions'],fn($x)=>$x['type']==='attack'&&(PokeBotCard($ctx,$x['source'])['id']??'')==='sv08-036');
                if($can&&$ctx['own']['turns']<=2)$r['attackBy2']=true;
                if($can&&$ctx['discardEnergy']>=15)$r['ready330By5']=true;
            }
            PokeApplyAction($a);
        }
        $m=PokeVar('openingStats')[2]['lunarCycleTurnOne'];
        if($mode==='full'&&!GetWinner()||$mode==='prefix'&&!$m['complete']&&!GetWinner())throw new RuntimeException('Incomplete study game');
        $r['winner']=$mode==='full'?GetWinner():null;$r['lunar']=$m;$rows[]=$r;
    }
    $summary=['games'=>count($rows),'wins'=>$mode==='full'?count(array_filter($rows,fn($r)=>$r['winner']===2)):null,'available'=>0,'activated'=>0,'firstActivated'=>0,'secondActivated'=>0,'attackBy2'=>0,'ready330By5'=>0];
    foreach($rows as $r){$summary['available']+=(int)$r['lunar']['available'];$summary['activated']+=(int)$r['lunar']['activated'];$summary[$r['first']===2?'firstActivated':'secondActivated']+=(int)$r['lunar']['activated'];foreach(['attackBy2','ready330By5'] as $k)$summary[$k]+=(int)$r[$k];}
    $out['variants'][$variant]=['deck'=>$deck,'summary'=>$summary,'games'=>$rows];echo $variant.' '.json_encode($summary).PHP_EOL;
    file_put_contents(__DIR__.'/fixtures/'.$studyName.'-'.$mode.'-'.$start.'.json',json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
}
