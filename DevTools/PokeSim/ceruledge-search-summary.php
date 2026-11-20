<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$data=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);
$out=['policy'=>$data['policy'],'seed'=>$data['seed'],'pairs'=>$data['pairs'],'baselineSHA256'=>$data['baselineSHA256'],
    'note'=>'Matched seeds and starting orders. Percentages count all games. Win intervals cluster the four games sharing each seed; approximate 95%, no adjustment for comparing multiple candidates.','variants'=>[]];
if(isset($data['variantPolicies']))$out['variantPolicies']=$data['variantPolicies'];
if(isset($data['note']))$out['sourceNote']=$data['note'];
foreach($data['variants'] as $name=>$variant){
    $games=[];$blocks=[];$matchups=[];
    foreach($variant['opponents'] as $opponent=>$record){
        $games=array_merge($games,$record['games']);
        $matchups[$opponent]=['games'=>count($record['games']),'wins'=>array_sum(array_column($record['games'],'won'))];
        foreach($record['games'] as $i=>$g){
            $b=$data['variants']['baseline']['opponents'][$opponent]['games'][$i];
            if($g['seed']!==$b['seed']||$g['firstPlayer']!==$b['firstPlayer'])throw new RuntimeException('Mismatched seed/order');
            $blocks[$g['seed']][]=(int)$g['won']-(int)$b['won'];
        }
    }
    $n=count($games);$row=['changes'=>$variant['changes'],'games'=>$n,'matchups'=>$matchups];
    foreach(['complete','won','by2ExAttack','by4Ready330','by5Ready330','legacyAttached','legacyTriggered'] as $key){
        $row[$key]=array_sum(array_column($games,$key));$row[$key.'Pct']=round(100*$row[$key]/$n,2);
    }
    $blocks=array_map(fn($v)=>array_sum($v)/count($v),$blocks);$m=array_sum($blocks)/count($blocks);
    $se=sqrt(array_sum(array_map(fn($v)=>($v-$m)**2,$blocks))/(count($blocks)-1)/count($blocks));
    $row['winDeltaPP']=round(100*$m,2);$row['seedCluster95ApproxPP']=[round(100*($m-1.96*$se),2),round(100*($m+1.96*$se),2)];
    $out['variants'][$name]=$row;
    echo json_encode(['variant'=>$name,'win'=>$row['wonPct'],'exT2'=>$row['by2ExAttackPct'],'readyT4'=>$row['by4Ready330Pct'],'readyT5'=>$row['by5Ready330Pct'],'winDeltaPP'=>$row['winDeltaPP'],'interval'=>$row['seedCluster95ApproxPP']]).PHP_EOL;
}
if(isset($argv[2]))file_put_contents($argv[2],json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL);
