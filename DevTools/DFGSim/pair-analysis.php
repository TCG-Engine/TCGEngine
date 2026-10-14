<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$runtimeRoot = $argv[2] ?? __DIR__ . '/../../DFGSim';
require_once $runtimeRoot . '/Custom/GameLogic.php';

// Exhaustive one-round comparison. No reactions or pre-existing tokens; both players pass.
$folder = $argv[1] ?? __DIR__ . '/results/pairs-v' . DFG_RULES_VERSION;
if (!is_dir($folder) && !mkdir($folder, 0777, true)) throw new RuntimeException('Cannot create result directory');
$cards = DFGCatalog(); $pairs = []; $plain = [];
foreach ($cards as $main) {
    $plain[$main['id']] = count($pairs);
    foreach (array_merge([null], array_values(array_filter(array_keys($cards), fn($id) => $id !== $main['id'] && $cards[$id]['color'] === $main['color'] && $cards[$id]['bonus'] !== ''))) as $bonus) {
        $pairs[] = ['main' => $main['id'], 'bonus' => $bonus, 'color' => $main['color'], 'label' => $main['name'] . ' + ' . ($bonus === null ? 'no burn' : $cards[$bonus]['name'])];
    }
}
$n = count($pairs); $cells = []; $summaries = []; $mainGrid = array_fill(0, 36, array_fill(0, 36, ['sum'=>0,'count'=>0]));
$csv = fopen($folder . '/matchups.csv', 'wb');
fputcsv($csv, ['rowPlan','columnPlan','rowMain','rowBonus','columnMain','columnBonus','rowAttack','rowBlock','rowPierce','columnAttack','columnBlock','columnPierce','rowDamage','columnDamage','netDamage','rowEarnedToken','columnEarnedToken','bonusNetGain']);
foreach ($pairs as $i => $a) {
    $wins = $ties = $losses = $earned = $sum = $dealt = $taken = $gain = 0; $worst = PHP_INT_MAX; $best = PHP_INT_MIN;
    $planA = ['main'=>$a['main'], 'bonuses'=>$a['bonus'] === null ? [] : [$a['bonus']]];
    foreach ($pairs as $j => $b) {
        $planB = ['main'=>$b['main'], 'bonuses'=>$b['bonus'] === null ? [] : [$b['bonus']]];
        $stats = DFGComputeRound([$planA,$planB]);
        $ea = DFGObjectiveAchieved($planA,$planB,$stats,0); $eb = DFGObjectiveAchieved($planB,$planA,$stats,1);
        $net = $stats[0]['damage']-$stats[1]['damage'];
        $cells[] = [$stats[0]['damage'],$stats[1]['damage'],(int)$ea,(int)$eb];
        $baseline = DFGComputeRound([['main'=>$a['main'],'bonuses'=>[]],$planB]);
        $delta = $net-($baseline[0]['damage']-$baseline[1]['damage']);
        $sum += $net; $dealt += $stats[0]['damage']; $taken += $stats[1]['damage']; $gain += $delta; $earned += (int)$ea;
        if ($net > 0) ++$wins; elseif ($net < 0) ++$losses; else ++$ties;
        $worst = min($worst,$net); $best = max($best,$net);
        $mainGrid[$a['main']-1][$b['main']-1]['sum'] += $net; ++$mainGrid[$a['main']-1][$b['main']-1]['count'];
        fputcsv($csv,[$i+1,$j+1,$a['main'],$a['bonus'],$b['main'],$b['bonus'],$stats[0]['attack'],$stats[0]['block'],$stats[0]['pierce'],$stats[1]['attack'],$stats[1]['block'],$stats[1]['pierce'],$stats[0]['damage'],$stats[1]['damage'],$net,$ea?$cards[$a['main']]['advantage']['earn']['color']:'',$eb?$cards[$b['main']]['advantage']['earn']['color']:'',$delta]);
    }
    $summaries[] = ['pair'=>$i,'meanNet'=>round($sum/$n,4),'meanDealt'=>round($dealt/$n,4),'meanTaken'=>round($taken/$n,4),'positive'=>$wins,'equal'=>$ties,'negative'=>$losses,'objectiveRate'=>round($earned/$n,4),'meanBonusGain'=>round($gain/$n,4),'worst'=>$worst,'best'=>$best];
}
fclose($csv);
// Verify all mirror outcomes, not a statistical sample. Identity matches must be net zero.
foreach ($cells as $index => $cell) {
    $i = intdiv($index,$n); $j = $index % $n; $mirror = $cells[$j*$n+$i];
    if ($cell !== [$mirror[1],$mirror[0],$mirror[3],$mirror[2]]) throw new RuntimeException("Asymmetric result $i/$j");
}
$data = ['rulesVersion'=>DFG_RULES_VERSION,'catalogHash'=>hash_file('sha256',$runtimeRoot.'/data/cards.json'),'generatedAt'=>gmdate('c'),'pairCount'=>$n,'matchupCount'=>$n*$n,'assumptions'=>['Single round, all cards initially available','Optional one same-hand bonus burn; main cannot be its own burn','No reactions; no held advantage tokens or token spending','Objectives checked after damage, with empty inventories','Opponent plans weighted equally; damage edges are not match win rates'], 'cards'=>array_values(array_map('DFGCardPublic',$cards)),'pairs'=>$pairs,'plain'=>$plain,'cells'=>$cells,'summaries'=>$summaries,'mainGrid'=>array_map(fn($row)=>array_map(fn($cell)=>round($cell['sum']/$cell['count'],4),$row),$mainGrid)];
if(realpath($runtimeRoot)!==realpath(__DIR__.'/../../DFGSim')) {
    $data['trial']=basename($folder);
    array_unshift($data['assumptions'],'Isolated trial catalog; live game unchanged');
}
$json = json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
file_put_contents($folder.'/data.json',$json);
$template = file_get_contents(__DIR__.'/pair-analysis-template.html');
file_put_contents($folder.'/explorer.html',str_replace('__PAIR_DATA__',str_replace('</','<\/',$json),$template));
$ranking=$summaries; usort($ranking,fn($a,$b)=>$b['meanNet']<=>$a['meanNet']);
$text = "# Card-pair analysis — rules v".DFG_RULES_VERSION."\n\n$n legal plans; ".($n*$n)." ordered matchups. All mirror outcomes verified.\n\n";
$text .= "One-round, no reactions or pre-existing tokens. A positive damage edge means dealing more than receiving, not winning the match. Opponents are uniformly weighted over legal plans. Objective rewards and burn costs are separate from net damage.\n\n| Plan | Mean damage edge | Objective frequency | Bonus gain vs same main without burn | Worst edge |\n|---|---:|---:|---:|---:|\n";
foreach(array_slice($ranking,0,12) as $row) $text.='| '.$pairs[$row['pair']]['label'].' | '.round($row['meanNet'],2).' | '.round($row['objectiveRate']*100,1).'% | '.round($row['meanBonusGain'],2).' | '.$row['worst']." |\n";
file_put_contents($folder.'/report.md',$text);
echo "$n plans, ".($n*$n)." ordered matchups, all mirror outcomes verified.\n";
echo "Outputs: $folder\n";
foreach(array_slice($ranking,0,5) as $row) echo $pairs[$row['pair']]['label'].': mean net '.$row['meanNet'].', objective '.round($row['objectiveRate']*100,1)."%\n";
