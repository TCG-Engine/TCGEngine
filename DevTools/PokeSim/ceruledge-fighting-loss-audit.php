<?php
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
$mode=$argv[1]??'original';
if($mode==='original'){
 $source=json_decode(file_get_contents(__DIR__.'/fixtures/ceruledge-fighting-lopunny-comparison.json'),true,512,JSON_THROW_ON_ERROR);
 $games=$source['games']['ceruledge-fighting'];
}else{
 $source=json_decode(file_get_contents(__DIR__.'/fixtures/ceruledge-fighting-loss-study-'.(int)$mode.'.json'),true,512,JSON_THROW_ON_ERROR);
 $games=$source['variants'][$argv[2]??'baseline']['games'];
}
$out=['policy'=>PokeSimulationPolicy(),'traces'=>[]];
foreach($games as $game){
 if($game['winner']===2)continue;
 $first=$game['firstPlayer']??$game['first'];
 PokeCreateGame(PokeNamedDeck('brisbane-lopunny'),PokeNamedDeck('ceruledge-fighting'),$game['seed'],$first);
 $trace=['seed'=>$game['seed'],'first'=>$first,'steps'=>[]];
 for($i=0;$i<1500&&!GetWinner();++$i){
  $seat=PokePendingPlayer();$v=PokeObservation($seat);$a=PokeBotChoose($v);
  if($seat===2){
   $ctx=PokeCeruledgeFightingContext($v);
   $fmt=fn($c)=>['id'=>$c['id'],'energy'=>$c['energy'],'damage'=>$c['damage']];
   $step=['turn'=>$ctx['own']['turns'],'deck'=>$ctx['own']['deckCount'],'prizes'=>[$ctx['own']['prizeCount'],$ctx['enemy']['prizeCount']],
    'hand'=>array_column($ctx['own']['Hand'],'id'),'discardEnergy'=>$ctx['discardEnergy'],'active'=>array_map($fmt,$ctx['own']['Active']),'bench'=>array_map($fmt,$ctx['own']['Bench']),
    'enemy'=>array_map($fmt,$ctx['enemy']['Active']),'action'=>$a,'source'=>isset($a['source'])?(PokeBotCard($ctx,$a['source'])['id']??null):null];
   if($v['decision']){$step['prompt']=$v['decision']['prompt'];$step['choices']=$v['decision']['choices'];}
   $trace['steps'][]=$step;
  }
  PokeApplyAction($a);
 }
 $trace['winner']=GetWinner();$trace['log']=PokeVar('log',[]);$out['traces'][]=$trace;
}
file_put_contents(__DIR__.'/fixtures/ceruledge-fighting-loss-traces'.($mode==='original'?'':'-'.$mode).'.json',json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
foreach($out['traces'] as $t){
 $last=end($t['steps']);$end=[];
 foreach($t['steps'] as $s)if(in_array($s['action']['type'],['attack','end']))$end[]='T'.$s['turn'].':'.($s['source']??'end').'/'.$s['discardEnergy'].'E/'.$s['deck'].'D';
 echo $t['seed'].' first='.$t['first'].' '.implode(' ',$end).' final='.json_encode($last['prizes']).PHP_EOL;
}
