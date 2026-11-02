<?php
/** Experimental policies are observation-only and do not replace the live bot.
 * Results record their live baseline fingerprint; pre-adoption results used the
 * former bot. New experiments run on top of the currently registered bot.
 */
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
function fightStudyScore(array $ctx,array $a,string $variant): float {
 $base=PokeCeruledgeFightingActionScore($ctx,$a);
 $card=isset($a['source'])?PokeBotCard($ctx,$a['source']):null;
 if($variant==='blender-buffer'&&$a['type']==='trainer'&&$card['id']==='sv08-164'&&$ctx['own']['deckCount']<=2)return -20;
 if(in_array($variant,['fire-reserve','lunar-reserve','resource-reserves'],true)&&$a['type']==='trainer'&&$card['id']==='me01-131'){
  $after=$ctx;$remaining=$ctx['own']['Hand'];
  foreach($remaining as $i=>$c)if($c['ref']===$a['source']){unset($remaining[$i]);--$after['hand'][$c['id']];break;}
  for($i=0;$i<2;++$i){
   usort($remaining,fn($a,$b)=>fightStudyDiscard($after,$b['id'],$variant)<=>fightStudyDiscard($after,$a['id'],$variant));
   $c=array_shift($remaining);if(!$c||fightStudyDiscard($after,$c['id'],$variant)<=0)return -20;
   --$after['hand'][$c['id']];if($c['id']==='mee-002')--$after['fire'];if(PokeCeruledgeIsFightingEnergy($c['id']))--$after['fighting'];
  }
 }
 if($variant!=='retreat'){
  $draw=0;$deck=$ctx['own']['deckCount'];
  if($a['type']==='ability'&&$card['id']==='me01-074')$draw=3;
  if($a['type']==='trainer'){
   if($card['id']==='sv06-145'||$card['id']==='me03-079')$draw=5;
   if($card['id']==='sv08.5-107')$draw=6;
   if($card['id']==='me02.5-192'){$draw=$ctx['own']['prizeCount']===6?8:6;$deck+=count($ctx['own']['Hand'])-1;}
  }
  if($draw&&$deck-$draw<2)return -20;
 }
 if($variant!=='conserve'&&$a['type']==='attach'){
  $active=$ctx['own']['Active'][0]??null;
  if($active&&$a['target']===$active['ref']&&count($active['energy'])<(int)CardRetreat($active['id'])){
   foreach($ctx['own']['Bench'] as $b){
    $after=PokeCeruledgeFightingSwitchContext($ctx,$b);
    if(PokeCeruledgeAttackValue($after,$b)>PokeCeruledgeAttackValue($ctx,$active)+30){
     // Preserve Fire for the main attacker when Fighting can pay retreat.
     if($card['id']==='mee-002'&&$ctx['fighting']>0)return -20;
     return 380;
    }
   }
  }
 }
 return $base;
}
function fightStudyDiscard(array $ctx,string $id,string $variant): float {
 if(in_array($variant,['fire-reserve','resource-reserves'],true)&&$id==='mee-002'&&$ctx['fire']<=1){
  foreach($ctx['field'] as $c)if(in_array($c['id'],['me02-019','sv08-036'],true)&&!PokeHasAttackEnergy((object)['Energy'=>$c['energy']],['Fire']))return -180;
 }
 if(in_array($variant,['lunar-reserve','resource-reserves'],true)&&PokeCeruledgeIsFightingEnergy($id)&&$ctx['fighting']<=1&&PokeBotFieldCount($ctx,['me01-074'])&&$ctx['own']['deckCount']>=5)return -180;
 return PokeCeruledgeDiscardScore($ctx,$id);
}
function fightStudyChoose(array $v,string $variant): ?array {
 if($v['viewer']!==2||$variant==='baseline'||str_starts_with($variant,'energy-'))return PokeBotChoose($v);
 $ctx=PokeCeruledgeFightingContext($v);
 if($v['decision']){
  $d=$v['decision'];
  if($variant==='blender-buffer'&&str_contains($d['prompt'],'Brilliant Blender')){
   $d['max']=min($d['max'],max(0,$ctx['own']['deckCount']-2));
   return ['type'=>'decision','player'=>2,'value'=>PokeCeruledgeFightingDecision($ctx,$d)];
  }
  if(in_array($variant,['fire-reserve','lunar-reserve','resource-reserves'],true)&&(str_contains($d['prompt'],'Ultra Ball: discard')||str_contains($d['prompt'],'Lunar Cycle: discard'))){
   $choices=$d['choices'];$selected=[];$max=$d['type']==='MZMULTICHOOSE'?$d['max']:1;
   while($choices&&count($selected)<$max){
    usort($choices,fn($a,$b)=>fightStudyDiscard($ctx,$b['card'],$variant)<=>fightStudyDiscard($ctx,$a['card'],$variant));$c=array_shift($choices);
    if(count($selected)>=$d['min']&&fightStudyDiscard($ctx,$c['card'],$variant)<0)break;
    $selected[]=$c['value'];--$ctx['hand'][$c['card']];if($c['card']==='mee-002')--$ctx['fire'];if(PokeCeruledgeIsFightingEnergy($c['card']))--$ctx['fighting'];
   }
   return ['type'=>'decision','player'=>2,'value'=>implode('&',$selected)?:'-'];
  }
  return PokeBotChoose($v);
 }
 $actions=$v['actions'];usort($actions,fn($a,$b)=>fightStudyScore($ctx,$b,$variant)<=>fightStudyScore($ctx,$a,$variant));return $actions[0]??null;
}
$pairs=(int)($argv[1]??100);$start=(int)($argv[2]??1);
$variants=isset($argv[3])?explode(',',$argv[3]):['baseline','blender-buffer','fire-reserve','lunar-reserve','resource-reserves'];
$studyName=$argv[4]??'loss-study';
if(!preg_match('/^[a-z-]+$/D',$studyName))throw new InvalidArgumentException('Invalid study name');
$out=['baselinePolicy'=>PokeSimulationPolicy(),'pairs'=>$pairs,'start'=>$start,'variants'=>[]];
foreach($variants as $variant){
 $deck=PokeNamedDeck('ceruledge-fighting');
 if(in_array($variant,['energy-more-fire','energy-more-fighting'],true)){
  $delta=$variant==='energy-more-fire'?1:-1;
  foreach($deck as &$entry){if($entry['id']==='mee-002')$entry['count']+=$delta;if($entry['id']==='base1-97')$entry['count']-=$delta;}unset($entry);
 }
 if(str_starts_with($variant,'sol-to-')){
  foreach($deck as &$entry)if($entry['id']==='me01-075')--$entry['count'];unset($entry);
  $deck[]=['id'=>$variant==='sol-to-naveen'?'me03-079':'me02-089','count'=>1];
 }
 $errors=PokeValidateDeck($deck);if($errors)throw new RuntimeException(implode(';',$errors));
 $rows=[];
 for($seed=$start;$seed<$start+$pairs;++$seed)foreach([1,2] as $first){
  PokeCreateGame(PokeNamedDeck('brisbane-lopunny'),$deck,$seed,$first);
  $r=['seed'=>$seed,'first'=>$first,'ready330By4'=>false,'ready330By5'=>false,'attackBy2'=>false];
  for($i=0;$i<1500&&!GetWinner();++$i){
   $v=PokeObservation(PokePendingPlayer());$a=fightStudyChoose($v,$variant);
   if($v['viewer']===2&&$v['phase']==='MAIN'&&$v['players'][2]['turns']<=5){
    $ctx=PokeCeruledgeFightingContext($v);$can=(bool)array_filter($v['actions'],fn($x)=>$x['type']==='attack'&&(PokeBotCard($ctx,$x['source'])['id']??'')==='sv08-036');
    if($can&&$ctx['own']['turns']<=2)$r['attackBy2']=true;
    if($can&&$ctx['discardEnergy']>=15){$r['ready330By5']=true;if($ctx['own']['turns']<=4)$r['ready330By4']=true;}
   }
   PokeApplyAction($a);
  }
  if(!GetWinner())throw new RuntimeException('Incomplete game');
  $r['winner']=GetWinner();$r['reason']='';foreach(array_reverse(PokeVar('log',[])) as $event)if($event['event']==='game-over'){$r['reason']=$event['reason'];break;}
  $lunar=PokeVar('openingStats',[])[2]['lunarCycleTurnOne']??null;
  $r['lunarCycleTurnOne']=$lunar['activated']??false;$r['lunarCycleAvailableTurnOne']=$lunar['available']??false;
  $rows[]=$r;
 }
 $sum=['games'=>count($rows),'wins'=>0,'deckoutLosses'=>0,'attackBy2'=>0,'ready330By4'=>0,'ready330By5'=>0,'lunarCycleTurnOne'=>0,'lunarCycleAvailableTurnOne'=>0];
 foreach($rows as $r){$sum['wins']+=(int)($r['winner']===2);$sum['deckoutLosses']+=(int)($r['winner']===1&&$r['reason']==='deck-out');foreach(['attackBy2','ready330By4','ready330By5','lunarCycleTurnOne','lunarCycleAvailableTurnOne'] as $k)$sum[$k]+=(int)$r[$k];}
 $out['variants'][$variant]=['deck'=>$deck,'summary'=>$sum,'games'=>$rows];
 echo $variant.' '.json_encode($sum).PHP_EOL;
 file_put_contents(__DIR__.'/fixtures/ceruledge-fighting-'.$studyName.'-'.$start.'.json',json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
}
