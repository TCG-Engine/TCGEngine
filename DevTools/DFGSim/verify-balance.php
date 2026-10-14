<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../../DFGSim/Custom/GameLogic.php';
$folder = $argv[1] ?? __DIR__ . '/results/balance-v2';
$summary = json_decode(file_get_contents($folder . '/summary.json'), true, 64, JSON_THROW_ON_ERROR);
if ($summary['catalogHash'] !== hash_file('sha256', __DIR__ . '/../../DFGSim/data/cards.json')) throw new RuntimeException('Historical report uses a different catalog; restore its matching card snapshot before verification');
$count = 0; $failures = 0; $checks = 0; $expected = [];
$activity = array_fill_keys(DFG_COLORS, ['earned'=>0,'spent'=>0]); $combos = 0;
function verifyBalance(bool $ok, string $message): void {
    global $failures, $checks; ++$checks;
    if (!$ok) { ++$failures; echo 'FAIL: ' . $message . "\n"; }
}
function verifyReplay(array $match): void {
    $state = DFGNewGame($match['opening'][0], $match['opening'][1], $match['seed'], $match['first']);
    if ($match['removedFromSeat0'] !== null) $state['players'][0]['discarded'][] = $match['removedFromSeat0'];
    try {
        foreach ($match['rounds'] as $round) {
            verifyBalance(array_column($state['players'], 'color') === $round['colors'], 'replayed active hands');
            verifyBalance(array_column($state['players'], 'advantages') === $round['advantagesBefore'], 'tokens carry between rounds');
            foreach ([0,1] as $seat) $state = DFGApply($state, $seat, ['type'=>'commit'] + $round['plans'][$seat]);
            foreach ($round['reactions'] as $reaction) {
                if ($state['reactionTurn'] !== $reaction['seat']) $state = DFGApply($state, $state['reactionTurn'], ['type'=>'pass']);
                $state = DFGApply($state, $reaction['seat'], ['type'=>'react','card'=>$reaction['card']]);
            }
            while ($state['phase'] === 'reactions') $state = DFGApply($state, $state['reactionTurn'], ['type'=>'pass']);
            verifyBalance(array_column($state['players'], 'life') === $round['lifeAfter'] && array_column($state['players'], 'advantages') === $round['advantagesAfter'], 'engine replay reproduces damage and objective awards');
            foreach ($round['switches'] as $seat=>$color) $state = DFGApply($state, $seat, ['type'=>'switch','color'=>$color]);
        }
        verifyBalance(array_column($state['players'], 'life') === $match['finalLife'] && array_map(fn($p)=>count($p['discarded']), $state['players']) === $match['burns'], 'replayed final life and card resources');
        verifyBalance(($state['phase'] === 'finished' ? $state['winner'] : 'capped') === $match['winner'], 'replayed outcome');
    } catch (Throwable $error) { verifyBalance(false, 'trace contains an illegal action: ' . $error->getMessage()); }
}
$raw = fopen($folder . '/baseline.jsonl', 'rb');
while (($line = fgets($raw)) !== false) {
    $match = json_decode($line, true, 64, JSON_THROW_ON_ERROR); ++$count;
    verifyReplay($match);
    $life = [40, 40]; $discarded = [[], []]; $seen = [[], []];
    foreach ($match['rounds'] as $round) {
        verifyBalance($round['lifeBefore'] === $life, 'consecutive life accounting');
        $reactions = array_map(fn($r) => ['seat' => $r['seat'], 'card' => $r['card']], $round['reactions']);
        verifyBalance(DFGComputeRound($round['plans'], $reactions) === $round['stats'], 'recorded damage reproduces current card rules');
        foreach ([0,1] as $seat) {
            $plan = $round['plans'][$seat];
            foreach ($plan['advantages'] ?? [] as $color) ++$activity[$color]['spent'];
            $remaining = array_diff($round['advantagesBefore'][$seat], $plan['advantages'] ?? []);
            foreach (array_diff($round['advantagesAfter'][$seat], $remaining) as $color) ++$activity[$color]['earned'];
            if (count($plan['advantages'] ?? []) === 2) ++$combos;
            verifyBalance(count($plan['bonuses']) <= 1 && !in_array($plan['main'], $discarded[$seat], true), 'legal main and bonus limit');
            verifyBalance(DFGCatalog()[$plan['main']]['color'] === $round['colors'][$seat], 'main comes from active hand');
            $seen[$seat]['main'][$plan['main']] = true;
            foreach ($plan['bonuses'] as $id) {
                verifyBalance($id !== $plan['main'] && !in_array($id,$discarded[$seat],true) && DFGCatalog()[$id]['color'] === $round['colors'][$seat], 'legal permanent bonus burn');
                $discarded[$seat][] = $id; $seen[$seat]['bonus'][$id] = true;
            }
        }
        foreach ($round['reactions'] as $r) {
            verifyBalance($r['card'] !== $round['plans'][$r['seat']]['main'] && !in_array($r['card'],$discarded[$r['seat']],true) && DFGCatalog()[$r['card']]['color'] === $round['colors'][$r['seat']], 'legal permanent reaction burn');
            $discarded[$r['seat']][] = $r['card']; $seen[$r['seat']]['reaction'][$r['card']] = true;
        }
        foreach ([0,1] as $seat) $life[$seat] = max(0,$life[$seat]-$round['stats'][1-$seat]['damage']);
        verifyBalance($round['lifeAfter'] === $life, 'simultaneous damage life accounting');
    }
    verifyBalance($match['finalLife'] === $life && $match['burns'] === array_map('count',$discarded), 'final life and permanent resource totals');
    foreach ([0,1] as $seat) foreach ($seen[$seat] as $role=>$ids) foreach (array_keys($ids) as $id) $expected[$id][$role] = ($expected[$id][$role] ?? 0)+1;
}
fclose($raw);
verifyBalance($count === $summary['matches'] && $count === $summary['decisive']+$summary['draws']+$summary['capped'], 'baseline outcome totals');
verifyBalance($activity === $summary['advantages'] && $combos === $summary['combinationSpends'], 'advantage summary agrees with raw token transitions');
verifyBalance(array_sum(array_column($summary['openings'],'matches')) === 2*$count, 'opening samples count both players exactly once');
foreach ($summary['cards'] as $id=>$card) foreach (['main','bonus','reaction'] as $role) {
    $m=$card[$role];
    verifyBalance($m['matches'] === ($expected[$id][$role] ?? 0), 'card samples deduplicate repeated uses in one player-game');
    verifyBalance($m['matches'] === $m['wins']+$m['losses']+$m['draws']+$m['capped'], 'card outcome denominator');
}
$pairs=[];$raw=fopen($folder.'/ablation.jsonl','rb');
while(($line=fgets($raw))!==false) {
    $record=json_decode($line,true,64,JSON_THROW_ON_ERROR);$a=$record['baseline'];$b=$record['removed'];
    verifyReplay($a); verifyReplay($b);
    verifyBalance($a['seed']===$b['seed'] && $a['opening']===$b['opening'] && $a['first']===$b['first'] && $b['removedFromSeat0']===$record['card'], 'paired removal preserves seed, opening and priority');
    if($a['winner']==='capped'||$b['winner']==='capped')continue;
    $scoreA=$a['winner']==='draw'?.5:($a['winner']===0?1:0);$scoreB=$b['winner']==='draw'?.5:($b['winner']===0?1:0);
    $pairs[$record['card']][]=$scoreA-$scoreB;
}
fclose($raw);
foreach($summary['ablations'] as $a) {
    $differences=$pairs[$a['id']]??[];
    verifyBalance(count($differences)===$a['pairs'] && (!$differences || abs(array_sum($differences)/count($differences)-$a['difference'])<1e-10), 'paired difference matches raw outcomes');
}
verifyBalance($summary['catalogHash']===hash_file('sha256',__DIR__.'/../../DFGSim/data/cards.json'), 'report uses current card snapshot');
echo "$checks recorded-data checks, $failures failures across $count matches.\n";
exit($failures?1:0);
