<?php
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
$source=json_decode(file_get_contents(__DIR__.'/fixtures/ceruledge-lunar-energy-audit.json'),true,512,JSON_THROW_ON_ERROR);
$out=[];
foreach($source['variants']['baseline']['games'] as $g){
 if(!$g['lunarCycleTurnOne']['available']||$g['lunarCycleTurnOne']['activated'])continue;
 PokeCreateGame(PokeNamedDeck('brisbane-lopunny'),PokeNamedDeck('ceruledge-fighting'),$g['seed'],$g['first']);$trace=[];
 for($i=0;$i<1500&&!GetWinner();++$i){
  if(PokeVar('openingStats')[2]['lunarCycleTurnOne']['complete'])break;
  $v=PokeObservation(PokePendingPlayer());$a=PokeBotChoose($v);
  if($v['viewer']===2){$ctx=PokeCeruledgeFightingContext($v);$trace[]=['turn'=>$ctx['own']['turns'],'action'=>$a,
   'source'=>isset($a['source'])?(PokeBotCard($ctx,$a['source'])['id']??null):null,'hand'=>array_column($ctx['own']['Hand'],'id'),
   'field'=>array_map(fn($c)=>['id'=>$c['id'],'energy'=>$c['energy']],$ctx['field']),
   'prompt'=>$v['decision']['prompt']??null,'choices'=>$v['decision']['choices']??[],
   'lunarReady'=>$ctx['lunarReady'],'score'=>$v['decision']?null:PokeCeruledgeFightingActionScore($ctx,$a)];}
  PokeApplyAction($a);
 }
 $out[]=['seed'=>$g['seed'],'first'=>$g['first'],'steps'=>$trace];
}
file_put_contents(__DIR__.'/fixtures/lunar-opening-unused-traces.json',json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
foreach(array_slice($out,0,4) as $t){echo 'SEED '.$t['seed'].' first='.$t['first'].PHP_EOL;foreach($t['steps'] as $s)echo $s['action']['type'].' '.($s['source']??$s['prompt']).' f='.count(array_filter($s['hand'],fn($id)=>$id==='base1-97')).' ready='.(int)$s['lunarReady'].' score='.$s['score'].PHP_EOL;}
