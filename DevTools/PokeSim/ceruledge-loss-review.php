<?php
require __DIR__.'/ceruledge-deck-study.php';
$prior=json_decode(file_get_contents(__DIR__.'/fixtures/ceruledge-naveen-count-comparison.json'),true,512,JSON_THROW_ON_ERROR);
$out=['policy'=>PokeSimulationPolicy(),'deck'=>PokeNamedDeck('ceruledge-ex'),'games'=>[]];
foreach($prior['variants']['lillie-to-naveen']['opponents'] as $opponent=>$record)foreach([1,2] as $first){
    $losses=array_values(array_filter($record['games'],fn($g)=>!$g['won']&&$g['firstPlayer']===$first));
    foreach(array_slice($losses,0,4) as $loss){
        $trace=[];
        $result=cerStudyGame($out['deck'],$opponent,$loss['seed'],$first,function($view,$action)use(&$trace){
            if($view['viewer']!==2)return;
            $ctx=PokeCeruledgeContext($view);$selected=[];
            if($view['decision'])foreach($view['decision']['choices'] as $c)if(in_array($c['value'],explode('&',$action['value']??''),true))$selected[]=$c['card'];
            $trace[]=['turn'=>$ctx['own']['turns'],'phase'=>$view['phase'],'action'=>$action,'card'=>isset($action['source'])?(PokeBotCard($ctx,$action['source'])['id']??null):null,
                'prompt'=>$view['decision']['prompt']??null,'selected'=>$selected,'discard'=>$ctx['discardEnergy'],'fire'=>$ctx['fire'],'deck'=>$ctx['own']['deckCount'],'hand'=>$ctx['hand'],
                'own'=>array_map(fn($c)=>array_intersect_key($c,array_flip(['ref','id','energy','damage','hp','conditions'])),$ctx['field']),
                'enemy'=>array_merge($ctx['enemy']['Active'],$ctx['enemy']['Bench']),'prizes'=>[$ctx['own']['prizeCount'],$ctx['enemy']['prizeCount']],
                'scored'=>array_map(fn($a)=>['action'=>$a,'score'=>PokeCeruledgeActionScore($ctx,$a)],$view['actions'])];
        });
        $out['games'][]=['opponent'=>$opponent,'seed'=>$loss['seed'],'firstPlayer'=>$first,'result'=>$result,'trace'=>$trace];
    }
}
file_put_contents($argv[1]??__DIR__.'/fixtures/ceruledge-3-1-loss-review.json',json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL);
echo 'Reviewed '.count($out['games']).' games; losses '.count(array_filter($out['games'],fn($g)=>!$g['result']['won'])).PHP_EOL;
