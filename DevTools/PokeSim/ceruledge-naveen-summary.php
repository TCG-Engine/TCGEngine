<?php
/** Summarize the matched-seed Naveen count experiment. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$file=$argv[1]??__DIR__.'/fixtures/ceruledge-naveen-count-comparison.json';
$data=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR);
$out=['seed'=>$data['seed'],'pairs'=>$data['pairs'],'policy'=>$data['policy'],
    'note'=>'Rates count every game. Intervals are approximate paired 95% intervals for win-rate differences; no multiple-comparison adjustment.','variants'=>[]];
foreach($data['variants'] as $name=>$variant){
    $games=[];$diff=[];$opponents=[];
    foreach($variant['opponents'] as $opponent=>$record){
        $games=array_merge($games,$record['games']);
        $opponents[$opponent]=['games'=>count($record['games']),'wins'=>array_sum(array_column($record['games'],'won'))];
        foreach($record['games'] as $i=>$g){
            $control=$data['variants']['baseline']['opponents'][$opponent]['games'][$i];
            if($g['seed']!==$control['seed']||$g['firstPlayer']!==$control['firstPlayer'])throw new RuntimeException('Mismatched seeds');
            $diff[]=(int)$g['won']-(int)$control['won'];
        }
    }
    $n=count($games);$row=['lillie'=>4+($variant['changes']['me02.5-192']??0),'naveen'=>$variant['changes']['me03-079']??0,'games'=>$n];
    foreach(['complete','won','by2ExAttack','by4Ready330','by5Ready330'] as $metric){
        $row[$metric]=array_sum(array_column($games,$metric));
        $row[$metric.'Pct']=round(100*$row[$metric]/$n,2);
    }
    $row['opponents']=$opponents;
    $row['naveenEarly']=['seen'=>0,'uses'=>0,'discardedEnergy'=>0];
    foreach($games as $g)foreach($row['naveenEarly'] as $key=>$value)$row['naveenEarly'][$key]+=$g['cards']['me03-079'][$key]??0;
    $mean=array_sum($diff)/$n;
    $se=sqrt(array_sum(array_map(fn($v)=>($v-$mean)**2,$diff))/($n-1)/$n);
    $row['pairedWinDeltaPP']=round(100*$mean,2);
    $row['pairedWinDelta95ApproxPP']=[round(100*($mean-1.96*$se),2),round(100*($mean+1.96*$se),2)];
    $out['variants'][$name]=$row;
}
$json=json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;
if(isset($argv[2]))file_put_contents($argv[2],$json);
echo $json;
