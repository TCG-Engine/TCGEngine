<?php
declare(strict_types=1);
if(PHP_SAPI !== 'cli') { http_response_code(404);exit; }
$runtimeRoot=$argv[2]??__DIR__.'/../../DFGSim';
require_once $runtimeRoot.'/Custom/GameLogic.php';
$folder=$argv[1]??__DIR__.'/results/pairs-v'.DFG_RULES_VERSION;
$data=json_decode(file_get_contents($folder.'/data.json'),true,64,JSON_THROW_ON_ERROR);
if($data['rulesVersion']!==DFG_RULES_VERSION||$data['catalogHash']!==hash_file('sha256',$runtimeRoot.'/data/cards.json')) throw new RuntimeException('Analysis uses a different rule or card snapshot');
$expected=[];$cards=DFGCatalog();
foreach($cards as $main){$expected[]=$main['id'].':none';foreach($cards as $bonus)if($bonus['id']!==$main['id']&&$bonus['color']===$main['color']&&$bonus['bonus']!=='')$expected[]=$main['id'].':'.$bonus['id'];}
$actual=array_map(fn($p)=>$p['main'].':'.($p['bonus']??'none'),$data['pairs']);
if($actual!==$expected)throw new RuntimeException('Missing, duplicated, or illegal pair');
$n=count($actual);if(count($data['cells'])!==$n*$n)throw new RuntimeException('Incomplete matrix');
foreach($data['pairs'] as $i=>$a)foreach($data['pairs'] as $j=>$b){
 $s=DFGNewGame($a['color'],$b['color']);
 foreach([$a,$b] as $seat=>$p)$s=DFGApply($s,$seat,['type'=>'commit','main'=>$p['main'],'bonuses'=>$p['bonus']===null?[]:[$p['bonus']]]);
 while($s['phase']==='reactions')$s=DFGApply($s,$s['reactionTurn'],['type'=>'pass']);
 $stats=$s['history'][0]['stats'];
 $recovery=[$s['players'][0]['life']-max(0,40-$stats[1]['damage']),$s['players'][1]['life']-max(0,40-$stats[0]['damage'])];
 $mask=fn($seat)=>array_sum(array_map(fn($color)=>1<<array_search($color,DFG_COLORS,true),DFGSwitchOptions($s,$seat)));
 $resolved=[$stats[0]['damage'],$stats[1]['damage'],(int)(count($s['players'][0]['advantages'])>0),(int)(count($s['players'][1]['advantages'])>0),$recovery[0],$recovery[1],$mask(0),$mask(1)];
 if(array_slice($resolved,0,count($data['cells'][$i*$n+$j]))!==$data['cells'][$i*$n+$j])throw new RuntimeException("Engine replay mismatch $i/$j");
}
echo "PASS: all $n legal plans covered exactly once; all ".($n*$n)." outcomes independently verified by full commit/pass/damage resolution.\n";
