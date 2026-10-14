<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$source=$argv[2]??__DIR__.'/../../DFGSim';
require_once $source.'/Custom/GameLogic.php';
if(DFGCatalog()[12]['name']!=='Chain Grapple') throw new LogicException('These trials target historical Chain Grapple. Pass the archived v7 baseline/runtime as the second argument; do not apply them to Counter Throw.');
$out=$argv[1]??__DIR__.'/results/opportunities-v'.DFG_RULES_VERSION;
if(!is_dir($out))mkdir($out,0777,true);
$original=json_decode(file_get_contents($source.'/data/cards.json'),true,64,JSON_THROW_ON_ERROR);$hash=hash_file('sha256',$source.'/data/cards.json');
$changes=[
 'baseline'=>[],
 'chain-trigger-2'=>[12=>['ability'=>'If you committed at least one bonus burn this round, gain +2 attack this round.']],
 'watchful-block-4'=>[27=>['block'=>4]],
 'watchful-clean-objective'=>[27=>['ability'=>"If your opponent committed at least one bonus burn this round, gain +2 attack this round.\nObjective: Deal damage and take no damage against a bonus burn: gain Red advantage."]],
 'covering-read-5'=>[6=>['ability'=>"If your opponent's main card has a printed attack value of 5 or more, gain +2 block this round."]],
 'covering-big-read'=>[6=>['ability'=>"If your opponent's main card has a printed attack value of 6 or more, gain +3 block this round."]],
 'jab-hybrid-bonus'=>[1=>['bonus'=>'Gain +1 attack and +1 block this round.']],
 'combined-trial'=>[12=>['ability'=>'If you committed at least one bonus burn this round, gain +2 attack this round.'],27=>['block'=>4],6=>['ability'=>"If your opponent's main card has a printed attack value of 5 or more, gain +2 block this round."]]
];
function runOpportunity(array $command):string{
 $process=proc_open($command,[1=>['pipe','w'],2=>['pipe','w']],$pipes);
 if(!is_resource($process))throw new RuntimeException('Cannot start isolated comparison');
 $stdout=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$code=proc_close($process);
 if($code!==0)throw new RuntimeException($stderr."\n".$stdout);return $stdout;
}
function profiles(array $d):array{
 $counts=[];foreach($d['pairs'] as $p)if($p['bonus']!==null)$counts[$p['main']]=($counts[$p['main']]??0)+1;
 $weights=['uniform'=>[],'half-burn'=>[],'no-burn'=>[],'burn-only'=>[]];
 foreach($d['pairs'] as $p){$burn=$p['bonus']!==null;$k=$counts[$p['main']];$weights['uniform'][]=1/$d['pairCount'];$weights['half-burn'][]=$burn?.5/(36*$k):.5/36;$weights['no-burn'][]=$burn?0:1/36;$weights['burn-only'][]=$burn?1/(36*$k):0;}
 $result=[];$n=$d['pairCount'];
 foreach($weights as $profile=>$w){
  $rows=[];foreach($d['pairs'] as $i=>$p){$net=$positive=$obj=$bonus=0.;$worst=PHP_INT_MAX;$negative=0;
   foreach($d['pairs'] as $j=>$q){$cell=$d['cells'][$i*$n+$j];$edge=$cell[0]-$cell[1];$base=$d['cells'][$d['plain'][$p['main']]*$n+$j];$net+=$w[$j]*$edge;$positive+=$w[$j]*($edge>0);$obj+=$w[$j]*$cell[2];$bonus+=$w[$j]*($edge-($base[0]-$base[1]));if($w[$j]>0){$worst=min($worst,$edge);$negative+=($edge<0);}}
   $rows[]=['pair'=>$i,'main'=>$p['main'],'bonus'=>$p['bonus'],'label'=>$p['label'],'meanNet'=>round($net,4),'positiveRate'=>round($positive,4),'objectiveRate'=>round($obj,4),'bonusGain'=>round($bonus,4),'worst'=>$worst,'negativeCount'=>$negative];
  }$result[$profile]=$rows;
 }return $result;
}
$results=[];
foreach($changes as $name=>$edits){
 $dir=$out.'/'.$name;$runtime=$dir.'/runtime';foreach([$runtime.'/Custom',$runtime.'/data'] as $path)if(!is_dir($path))mkdir($path,0777,true);
 $snapshot=$original;foreach($snapshot['cards'] as &$card)if(isset($edits[$card['id']]))$card=array_replace($card,$edits[$card['id']]);unset($card);
 file_put_contents($runtime.'/data/cards.json',json_encode($snapshot,JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
 foreach(['CardCatalog.php','GameLogic.php'] as $file){$code=file_get_contents($source.'/Custom/'.$file);
  if($name==='watchful-clean-objective'){
   if($file==='CardCatalog.php')$code=str_replace("'Deal damage against a bonus burn' => 'punish_bonus',","'Deal damage and take no damage against a bonus burn' => 'clean_bonus',\n        'Deal damage against a bonus burn' => 'punish_bonus',",$code);
   else $code=str_replace("'punish_bonus' =>", "'clean_bonus' => \$stats[\$seat]['damage'] > 0 && \$stats[1-\$seat]['damage'] === 0 && count(\$opponent['bonuses']) > 0,\n        'punish_bonus' =>",$code);
  }file_put_contents($runtime.'/Custom/'.$file,$code);
 }
 runOpportunity([PHP_BINARY,__DIR__.'/pair-analysis.php',$dir,$runtime]);
 $verified=runOpportunity([PHP_BINARY,__DIR__.'/verify-pair-analysis.php',$dir,$runtime]);
 $d=json_decode(file_get_contents($dir.'/data.json'),true,64,JSON_THROW_ON_ERROR);$results[$name]=['changes'=>$edits,'catalogHash'=>$d['catalogHash'],'profiles'=>profiles($d),'verification'=>trim($verified)];
 echo "$name: all ".$d['matchupCount']." outcomes replay-verified.\n";
}
if(hash_file('sha256',$source.'/data/cards.json')!==$hash)throw new RuntimeException('Live snapshot changed during analysis');
file_put_contents($out.'/comparison.json',json_encode(['rulesVersion'=>DFG_RULES_VERSION,'sourceHash'=>$hash,'scenarios'=>$results],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES));
echo "Saved comparisons to $out/comparison.json. Live catalog unchanged.\n";
