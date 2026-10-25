<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once ($argv[1]??__DIR__.'/../../DFGSim').'/Custom/GameLogic.php';
$cards=DFGCatalog();$pairs=[];$counts=[];
foreach($cards as $main){$pairs[]=['main'=>$main['id'],'bonuses'=>[]];$counts[$main['id']]=0;foreach($cards as $bonus)if($bonus['id']!==$main['id']&&$bonus['color']===$main['color']&&$bonus['bonus']!==''){$pairs[]=['main'=>$main['id'],'bonuses'=>[$bonus['id']]];++$counts[$main['id']];}}
$variants=[2=>[[],['Red']],5=>[[],['Red'],['Red','Purple'],['Green','Blue'],['Red','Blue']]];
foreach($variants as $id=>$spends)foreach($spends as $spend){$sum=0.;foreach($pairs as $own){if($own['main']!==$id)continue;$a=$own['bonuses']===[]?.5:.5/$counts[$id];$own['advantages']=$spend;foreach($pairs as $opponent){$b=$opponent['bonuses']===[]?.5/36:.5/(36*$counts[$opponent['main']]);$stats=DFGComputeRound([$own,$opponent]);$sum+=$a*$b*($stats[0]['damage']-$stats[1]['damage']);}}echo $cards[$id]['name'].' / '.($spend?implode('+',$spend):'none').': '.round($sum,3)."\n";}
