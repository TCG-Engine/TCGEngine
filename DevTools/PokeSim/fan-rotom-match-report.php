<?php
require __DIR__.'/fan-rotom-experiment.php';
$variants=fanVariants();$variant=$argv[1]??'no-jamming';
if(!isset($variants[$variant]))throw new InvalidArgumentException('Unknown variant');$rows=[];
foreach(['original',$variant] as $key)foreach(['brisbane-lopunny','dhelmise-v2'] as $enemy)foreach([1,2] as $first)for($i=0;$i<25;$i++){
 $r=PokeSimulateGame(60001+$i,$first,1500,'fan-rotom-lopunny',$enemy,$variants[$key]);
 $rows[]=['variant'=>$key,'opponent'=>$enemy,'first'=>$first,'seed'=>$r['seed'],'winner'=>$r['winner'],'status'=>$r['status'],'reason'=>$r['reason']];
}
$out=['policy'=>PokeSimulationPolicy(),'games'=>$rows];
file_put_contents(__DIR__.'/fixtures/fan-rotom-full-'.$variant.'.json',json_encode($out,JSON_PRETTY_PRINT)."\n");
foreach(['original',$variant] as $key){$rs=array_values(array_filter($rows,fn($r)=>$r['variant']===$key));echo json_encode(['variant'=>$key,'games'=>count($rs),'complete'=>count(array_filter($rs,fn($r)=>$r['status']==='complete')),'wins'=>count(array_filter($rs,fn($r)=>$r['winner']===1))])."\n";}
