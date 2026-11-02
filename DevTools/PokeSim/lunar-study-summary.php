<?php
/** Summarize a fixed-policy composition comparison, clustering starting orders by seed. */
if(PHP_SAPI!=='cli')exit;
$path=$argv[1]??__DIR__.'/fixtures/lillie-pad-full-1101.json';
$data=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
if($data['mode']!=='full'||!isset($data['variants']['baseline']))throw new InvalidArgumentException('Full comparison with baseline required');
$baseline=$data['variants']['baseline']['games'];$out=['source'=>basename($path),'policy'=>$data['policy'],'variants'=>[]];
$value=fn($r,$metric)=>$metric==='win'?(int)($r['winner']===2):($metric==='lunar'?(int)$r['lunar']['activated']:(int)$r[$metric]);
foreach($data['variants'] as $name=>$variant){
    $rows=$variant['games'];$n=count($rows);
    if($n!==count($baseline)||$n%2)throw new RuntimeException('Unmatched comparison');
    $summary=['games'=>$n,'deck'=>$variant['deck']??null,'counts'=>[],'rates'=>[],'order'=>[],'pairedDeltas'=>[]];
    foreach(['win','lunar','attackBy2','ready330By5'] as $metric){
        $total=array_sum(array_map(fn($r)=>$value($r,$metric),$rows));$summary['counts'][$metric]=$total;$summary['rates'][$metric]=$total/$n;
        foreach([1=>'second',2=>'first'] as $first=>$order){$subset=array_values(array_filter($rows,fn($r)=>$r['first']===$first));$summary['order'][$order][$metric]=array_sum(array_map(fn($r)=>$value($r,$metric),$subset))/count($subset);}
        if($name==='baseline')continue;
        $d=[];
        for($i=0;$i<$n;$i+=2){$delta=0;foreach([$i,$i+1] as $j){if($rows[$j]['seed']!==$baseline[$j]['seed']||$rows[$j]['first']!==$baseline[$j]['first'])throw new RuntimeException('Seed/order mismatch');$delta+=$value($rows[$j],$metric)-$value($baseline[$j],$metric);}$d[]=$delta/2;}
        $mean=array_sum($d)/count($d);$variance=count($d)>1?array_sum(array_map(fn($x)=>($x-$mean)**2,$d))/(count($d)-1):0;$margin=1.96*sqrt($variance/count($d));
        $summary['pairedDeltas'][$metric]=['estimate'=>$mean,'approx95CI'=>[$mean-$margin,$mean+$margin]];
    }
    $out['variants'][$name]=$summary;
}
$dest=preg_replace('/\.json$/D','-summary.json',$path);if($dest===$path)throw new InvalidArgumentException('JSON path required');
file_put_contents($dest,json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
echo json_encode($out,JSON_PRETTY_PRINT).PHP_EOL;
