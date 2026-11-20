<?php
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
set_error_handler(function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);});
$out=['policy'=>PokeSimulationPolicy(),'games'=>[],'choices'=>[],'notes'=>[
 'Discarding a named card is not automatically a mistake. Flags describe visible resource conflicts, not counterfactual losses.',
 'Uncovered evolution means a Charcadet is in play with no Ceruledge ex held or kept. It does not reveal the hidden deck or Prizes.']];
for($seed=901;$seed<1001;++$seed)foreach([1,2] as $first){
 PokeCreateGame(PokeNamedDeck('brisbane-lopunny'),PokeNamedDeck('ceruledge-fighting'),$seed,$first);
 for($step=0;$step<1500&&!GetWinner();++$step){
  $v=PokeObservation(PokePendingPlayer());$a=PokeBotChoose($v);
  if($v['viewer']===2&&str_contains($v['decision']['prompt']??'',"Explorer's Guidance: keep")){
   $ctx=PokeCeruledgeFightingContext($v);$kept=[];$milled=[];
   foreach($v['decision']['choices'] as $c){if(in_array($c['value'],explode('&',$a['value']),true))$kept[]=$c['card'];else $milled[]=$c['card'];}
   $counts=array_count_values($milled);$keptCounts=array_count_values($kept);
   $noEvolution=($ctx['hand']['sv08-036']??0)+($keptCounts['sv08-036']??0)===0;
   $uncovered=PokeBotFieldCount($ctx,['me02-019'])>0&&$noEvolution;
   $hasDiscardEx=(bool)array_filter($ctx['own']['Discard'],fn($c)=>$c['id']==='sv08-036');
   $out['choices'][]=['seed'=>$seed,'first'=>$first,'turn'=>$ctx['own']['turns'],'discardEnergy'=>$ctx['discardEnergy'],
    'hand'=>$ctx['hand'],'field'=>array_column($ctx['field'],'id'),'kept'=>$kept,'discarded'=>$milled,
    'energyMilled'=>count(array_filter($milled,fn($id)=>CardType($id)==='Energy')),
    'flags'=>['ceruledgeExDiscarded'=>isset($counts['sv08-036']),
     'uncoveredEvolutionDiscarded'=>$uncovered&&isset($counts['sv08-036']),
     'nightStretcherDiscarded'=>isset($counts['me02.5-196']),
     'recoveryConflict'=>$uncovered&&$hasDiscardEx&&isset($counts['me02.5-196'])&&($ctx['hand']['me02.5-196']??0)+($keptCounts['me02.5-196']??0)===0,
     'blenderDiscarded'=>isset($counts['sv08-164']),
     'blenderBefore15Energy'=>isset($counts['sv08-164'])&&$ctx['discardEnergy']<15]];
  }
  PokeApplyAction($a);
 }
 if(!GetWinner())throw new RuntimeException('Incomplete match');$out['games'][]=['seed'=>$seed,'first'=>$first,'winner'=>GetWinner()];
}
$sum=['games'=>count($out['games']),'wins'=>count(array_filter($out['games'],fn($g)=>$g['winner']===2)),'uses'=>count($out['choices']),'energyMilled'=>0,'cardsMilled'=>0,'flags'=>[],'milledCards'=>[]];
foreach($out['choices'] as $c){$sum['energyMilled']+=$c['energyMilled'];$sum['cardsMilled']+=count($c['discarded']);foreach($c['flags'] as $k=>$b)$sum['flags'][$k]=($sum['flags'][$k]??0)+(int)$b;foreach($c['discarded'] as $id)$sum['milledCards'][$id]=($sum['milledCards'][$id]??0)+1;}
$out['summary']=$sum;
file_put_contents(__DIR__.'/fixtures/ceruledge-fighting-explorer-audit.json',json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
echo json_encode($sum,JSON_PRETTY_PRINT).PHP_EOL;
