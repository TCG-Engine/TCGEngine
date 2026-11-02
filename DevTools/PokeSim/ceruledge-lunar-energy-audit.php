<?php
/** Replay only own turn one; join its telemetry to existing full-match results. */
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
set_error_handler(function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);});
$source='ceruledge-fighting-energy-splits-501.json';
$data=json_decode(file_get_contents(__DIR__.'/fixtures/'.$source),true,512,JSON_THROW_ON_ERROR);
$out=['source'=>$source,'sourcePolicy'=>$data['baselinePolicy'],'instrumentedPolicy'=>PokeSimulationPolicy(),'variants'=>[]];
foreach($data['variants'] as $name=>$variant){
 $groups=[];$rows=[];
 foreach($variant['games'] as $game){
  PokeCreateGame(PokeNamedDeck('brisbane-lopunny'),$variant['deck'],$game['seed'],$game['first']);
  for($step=0;$step<1500&&!GetWinner();++$step){
   $m=PokeVar('openingStats')[2]['lunarCycleTurnOne'];if($m['complete'])break;
   if(GetPlayerTurns(2)>1)throw new RuntimeException('First-turn completion not captured');
   $action=PokeBotChoose(PokeObservation(PokePendingPlayer()));PokeApplyAction($action);
  }
  $m=PokeVar('openingStats')[2]['lunarCycleTurnOne'];
  if(!$m['complete']&&!GetWinner())throw new RuntimeException('First-turn replay capped');
  if(count($rows)<2){
   $full=PokeSimulateGame($game['seed'],$game['first'],1500,'brisbane-lopunny','ceruledge-fighting',null,$variant['deck']);
   if($full['status']!=='complete'||$full['winner']!==$game['winner']||$full['openingStats'][1]['lunarCycleTurnOne']!==$m)
    throw new RuntimeException('Full-game result or Lunar metric differs from first-turn replay');
  }
  $order=$game['first']===2?'first':'second';
  if(!isset($groups[$order]))$groups[$order]=['games'=>0,'reached'=>0,'available'=>0,'activated'=>0,'blockers'=>[],
   'conversion'=>['activated'=>['games'=>0,'wins'=>0],'missed'=>['games'=>0,'wins'=>0]]];
  $g=&$groups[$order];++$g['games'];foreach(['reached','available','activated'] as $flag)$g[$flag]+=(int)$m[$flag];
  $bucket=$m['activated']?'activated':'missed';++$g['conversion'][$bucket]['games'];$g['conversion'][$bucket]['wins']+=(int)($game['winner']===2);
  if(!$m['activated'])foreach($m['blockers'] as $b)$g['blockers'][$b]=($g['blockers'][$b]??0)+1;unset($g);
  $rows[]=['seed'=>$game['seed'],'first'=>$game['first'],'fullMatchWinner'=>$game['winner'],'lunarCycleTurnOne'=>$m];
 }
 $out['variants'][$name]=['deck'=>$variant['deck'],'summary'=>$groups,'games'=>$rows];
 echo $name.' '.json_encode($groups).PHP_EOL;
 file_put_contents(__DIR__.'/fixtures/ceruledge-lunar-energy-audit.json',json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
}
