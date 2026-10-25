<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$options = getopt('', ['repeats::', 'profiles::', 'max-rounds::', 'ablation-cards::', 'ablation-repeats::', 'candidate-ids::', 'seed-offset::', 'out::', 'runtime-root::']);
$runtimeRoot = $options['runtime-root'] ?? __DIR__ . '/../../DFGSim';
require_once $runtimeRoot . '/Bot/HeuristicBot.php';

function balanceRandom(int &$rng, int $count): int {
    $rng = (int)(($rng * 48271) % 2147483647);
    return $rng % $count;
}
function balanceAction(array $view, int $seat, int &$rng, string $profile): array {
    if ($profile !== 'explore' || $view['phase'] === 'reactions') {
        $view['difficulty'] = in_array($profile,['casual','pro'],true) ? $profile : 'tactical';
        return DFGBotChoose($view, $seat, $rng);
    }
    if ($view['phase'] === 'switching') return ['type' => 'switch', 'color' => $view['switchOptions'][balanceRandom($rng, count($view['switchOptions']))]];
    if ($view['phase'] === 'routing') return ['type' => 'cut', 'color' => $view['cutOptions'][balanceRandom($rng, count($view['cutOptions']))]];
    if ($view['phase'] === 'directing') return ['type'=>'direct','color'=>$view['forceOptions'][balanceRandom($rng,count($view['forceOptions']))]];
    if ($view['phase'] === 'relocating') { $colors = array_merge(['keep'], $view['relocateOptions']); return ['type'=>'relocate','color'=>$colors[balanceRandom($rng,count($colors))]]; }
    if ($view['phase'] === 'stripping') return ['type'=>'strip','color'=>$view['stripOptions'][balanceRandom($rng,count($view['stripOptions']))]];
    if ($view['phase'] === 'cancelling') return ['type'=>'cancel','main'=>$view['cancelOptions'][balanceRandom($rng,count($view['cancelOptions']))]];
    $available = DFGAvailable($view['players'][$seat], $view['players'][$seat]['color']);
    $main = $available[balanceRandom($rng, count($available))];
    $bonusOptions = DFGBurnLocked($view,$seat) ? [] : array_values(array_filter($available, fn($id) => $id !== $main && DFGCatalog()[$id]['bonus'] !== ''));
    $bonuses = $bonusOptions && balanceRandom($rng, 4) === 0 ? [$bonusOptions[balanceRandom($rng, count($bonusOptions))]] : [];
    $variants = DFGSpendVariants($view['players'][$seat], $main);
    $variant = $variants[balanceRandom($rng, count($variants))];
    return ['type' => 'commit', 'main' => $main, 'bonuses' => $bonuses] + $variant;
}
function balanceMatch(string $a, string $b, int $seed, int $first, string $profile, int $cap, ?int $remove = null): array {
    $state = DFGNewGame($a, $b, $seed, $first);
    if ($remove !== null) $state['players'][0]['discarded'][] = $remove;
    $rng = [$seed, ($seed + 104729) % 2147483646 + 1];
    $rounds = [];
    while ($state['phase'] !== 'finished' && $state['round'] <= $cap) {
        $record = ['round' => $state['round'], 'colors' => array_column($state['players'], 'color'), 'lifeBefore' => array_column($state['players'], 'life'), 'advantagesBefore' => array_column($state['players'], 'advantages')];
        foreach ([0, 1] as $seat) $state = DFGApply($state, $seat, balanceAction(DFGObserve($state, $seat), $seat, $rng[$seat], $profile));
        $record['plans'] = $state['commitments']; $record['reactions'] = [];
        $record['cancels']=[];
        while ($state['phase']==='cancelling') foreach ([0,1] as $seat) if (DFGCancelOptions($state,$seat)) {
            $action=balanceAction(DFGObserve($state,$seat),$seat,$rng[$seat],$profile);
            $record['cancels'][]=['seat'=>$seat,'main'=>$action['main']];
            $state=DFGApply($state,$seat,$action);
        }
        $record['combatPlans']=$state['commitments'];
        $steps = 0;
        while ($state['phase'] === 'reactions') {
            if (++$steps > 72) throw new RuntimeException('Reaction loop exceeded legal card resources');
            $seat = $state['reactionTurn'];
            $action = balanceAction(DFGObserve($state, $seat), $seat, $rng[$seat], $profile);
            if ($action['type'] === 'react') {
                $before = DFGComputeRound($state['commitments'], $state['reactions']);
                $afterReactions = $state['reactions']; $afterReactions[] = ['seat' => $seat, 'card' => $action['card']];
                $after = DFGComputeRound($state['commitments'], $afterReactions);
                $record['reactions'][] = ['seat' => $seat, 'card' => $action['card'], 'immediateNetDamageGain' => ($after[$seat]['damage'] - $after[1-$seat]['damage']) - ($before[$seat]['damage'] - $before[1-$seat]['damage'])];
            }
            $state = DFGApply($state, $seat, $action);
        }
        $record['strips']=[];
        while ($state['phase']==='stripping') foreach ([0,1] as $seat) if (DFGStripOptions($state,$seat)) {
            $action=balanceAction(DFGObserve($state,$seat),$seat,$rng[$seat],$profile);
            $record['strips'][]=['seat'=>$seat,'color'=>$action['color']];
            $state=DFGApply($state,$seat,$action);
        }
        $record['relocations'] = [];
        while ($state['phase'] === 'relocating') foreach ([0,1] as $seat) if (DFGRelocateOptions($state,$seat)) {
            $action = balanceAction(DFGObserve($state,$seat),$seat,$rng[$seat],$profile);
            $record['relocations'][] = ['seat'=>$seat,'color'=>$action['color']];
            $state = DFGApply($state,$seat,$action);
        }
        $record['stats'] = DFGComputeRound($record['combatPlans'], $state['reactions']);
        $record['lifeAfter'] = array_column($state['players'], 'life');
        $record['advantagesAfter'] = array_column($state['players'], 'advantages');
        $record['routeCuts'] = [];
        while ($state['phase'] === 'routing') foreach ([0,1] as $seat) if (DFGCutOptions($state,$seat)) {
            $action = balanceAction(DFGObserve($state,$seat),$seat,$rng[$seat],$profile);
            $record['routeCuts'][] = ['seat'=>$seat,'color'=>$action['color']];
            $state = DFGApply($state,$seat,$action);
        }
        $record['switches'] = [];
        $record['forcedDestinations'] = [];
        while ($state['phase'] === 'directing') foreach ([0,1] as $seat) if (DFGForceOptions($state,$seat)) {
            $action=balanceAction(DFGObserve($state,$seat),$seat,$rng[$seat],$profile);
            $record['forcedDestinations'][]=['seat'=>$seat,'color'=>$action['color']];
            $state=DFGApply($state,$seat,$action);
        }
        if ($state['phase'] === 'switching') foreach ([0, 1] as $seat) {
            $action = balanceAction(DFGObserve($state, $seat), $seat, $rng[$seat], $profile);
            $record['switches'][$seat] = $action['color'];
            $state = DFGApply($state, $seat, $action);
        }
        $rounds[] = $record;
    }
    return ['opening' => [$a, $b], 'seed' => $seed, 'first' => $first, 'profile' => $profile, 'removedFromSeat0' => $remove,
        'winner' => $state['phase'] === 'finished' ? $state['winner'] : 'capped', 'roundCount' => count($rounds),
        'finalLife' => array_column($state['players'], 'life'), 'burns' => array_map(fn($p) => count($p['discarded']), $state['players']), 'rounds' => $rounds];
}
function balanceScore(array $match, int $seat): ?float {
    if ($match['winner'] === 'capped') return null;
    return $match['winner'] === 'draw' ? 0.5 : ($match['winner'] === $seat ? 1.0 : 0.0);
}
function balanceMetric(): array {
    return ['events' => 0, 'matches' => 0, 'wins' => 0, 'losses' => 0, 'draws' => 0, 'capped' => 0, 'damageDealt' => 0, 'damageTaken' => 0, 'netDamageGain' => 0];
}
function balanceOutcome(array &$metric, array $match, int $seat): void {
    ++$metric['matches'];
    if ($match['winner'] === 'capped') ++$metric['capped'];
    elseif ($match['winner'] === 'draw') ++$metric['draws'];
    elseif ($match['winner'] === $seat) ++$metric['wins'];
    else ++$metric['losses'];
}
function balanceAccumulate(array &$summary, array $match): void {
    ++$summary['matches']; $summary['rounds'] += $match['roundCount'];
    ++$summary['profiles'][$match['profile']][$match['winner'] === 'capped' ? 'capped' : ($match['winner'] === 'draw' ? 'draws' : 'decisive')];
    if ($match['winner'] === 'capped') ++$summary['capped']; elseif ($match['winner'] === 'draw') ++$summary['draws']; else ++$summary['decisive'];
    balanceOutcome($summary['firstReaction'], $match, $match['first']);
    foreach ([0, 1] as $seat) {
        balanceOutcome($summary['openings'][$match['opening'][$seat]], $match, $seat);
        balanceOutcome($summary['matchups'][$match['opening'][$seat]][$match['opening'][1-$seat]], $match, $seat);
        $seen = ['main' => [], 'bonus' => [], 'reaction' => []];
        foreach ($match['rounds'] as $round) {
            $plan = $round['plans'][$seat]; $id = $plan['main'];
            $remainingTokens = array_diff($round['advantagesBefore'][$seat], $plan['advantages'] ?? []);
            foreach ($round['strips']??[] as $choice) if ($choice['seat']!==$seat) $remainingTokens=array_diff($remainingTokens,[$choice['color']]);
            foreach ($plan['advantages'] ?? [] as $color) ++$summary['advantages'][$color]['spent'];
            foreach (array_diff($round['advantagesAfter'][$seat], $remainingTokens) as $color) ++$summary['advantages'][$color]['earned'];
            if (count($plan['advantages'] ?? []) === 2) ++$summary['combinationSpends'];
            $metric = &$summary['cards'][$id]['main'];
            ++$metric['events']; $metric['damageDealt'] += $round['stats'][$seat]['damage']; $metric['damageTaken'] += $round['stats'][1-$seat]['damage'];
            $seen['main'][$id] = true; unset($metric);
            foreach ($plan['bonuses'] as $id) {
                $metric = &$summary['cards'][$id]['bonus']; ++$metric['events'];
                $without = $round['combatPlans']??$round['plans']; $without[$seat]['bonuses'] = [];
                $reactions = array_map(fn($r) => ['seat' => $r['seat'], 'card' => $r['card']], $round['reactions']);
                $counterfactual = DFGComputeRound($without, $reactions);
                $metric['netDamageGain'] += ($round['stats'][$seat]['damage'] - $round['stats'][1-$seat]['damage']) - ($counterfactual[$seat]['damage'] - $counterfactual[1-$seat]['damage']);
                $seen['bonus'][$id] = true; unset($metric);
            }
            foreach ($round['reactions'] as $reaction) if ($reaction['seat'] === $seat) {
                $id = $reaction['card']; $metric = &$summary['cards'][$id]['reaction'];
                ++$metric['events']; $metric['netDamageGain'] += $reaction['immediateNetDamageGain'];
                $seen['reaction'][$id] = true; unset($metric);
            }
        }
        foreach ($seen as $role => $ids) foreach (array_keys($ids) as $id) balanceOutcome($summary['cards'][$id][$role], $match, $seat);
    }
}
function balanceDerived(array $metric): array {
    $completed = $metric['wins'] + $metric['losses'] + $metric['draws'];
    $metric['completed'] = $completed;
    $metric['scoreRate'] = $completed ? ($metric['wins'] + 0.5 * $metric['draws']) / $completed : null;
    $metric['playWinRate'] = $completed ? $metric['wins'] / $completed : null;
    $decisive = $metric['wins'] + $metric['losses'];
    $metric['decisiveWinRate'] = $decisive ? $metric['wins'] / $decisive : null;
    $metric['averageDamageDealt'] = $metric['events'] ? $metric['damageDealt'] / $metric['events'] : null;
    $metric['averageNetDamage'] = $metric['events'] ? ($metric['damageDealt'] - $metric['damageTaken']) / $metric['events'] : null;
    $metric['averageNetDamageGain'] = $metric['events'] ? $metric['netDamageGain'] / $metric['events'] : null;
    return $metric;
}
function balanceBootstrap(array $differences, int $seed): array {
    if (!$differences) return [null, null];
    $means = []; $n = count($differences);
    for ($i = 0; $i < 1000; ++$i) {
        $total = 0.0; for ($j = 0; $j < $n; ++$j) $total += $differences[balanceRandom($seed, $n)];
        $means[] = $total / $n;
    }
    sort($means); return [$means[24], $means[975]];
}
function balancePct(?float $value): string { return $value === null ? '—' : number_format(100*$value, 1) . '%'; }
function balanceReport(array $summary): string {
    $text = "# DFG Core Set balance exploration\n\n";
    $text .= "Rules version {$summary['rulesVersion']}; 40 life; one bonus burn; unlimited alternating reactions; listed-color switching. Catalog SHA-256: `{$summary['catalogHash']}`.\n\n";
    $text .= "{$summary['matches']} baseline matches: {$summary['decisive']} decisive, {$summary['draws']} draws, {$summary['capped']} capped at {$summary['maxRounds']} rounds. Mean rounds: " . number_format($summary['rounds']/$summary['matches'], 2) . ". Initial reaction-priority score: " . balancePct($summary['firstReaction']['scoreRate']) . ".\n\n";
    $text .= "Score = win 1, draw 0.5, loss 0. Capped matches are reported separately and excluded from score rates. Card outcome samples count each player's use of a card in a role once per match, even if it was played repeatedly. Main damage statistics count individual resolved plays.\n\n";
    $text .= "## Advantage activity\n\n| Color | Earned | Spent |\n|---|---:|---:|\n";
    foreach ($summary['advantages'] as $color => $activity) $text .= "| $color | {$activity['earned']} | {$activity['spent']} |\n";
    $text .= "\nTwo-color Combination Strike spends: {$summary['combinationSpends']}. Tokens are earned after damage and consumed on reveal.\n\n";
    $text .= "## Starting hands\n\n| Hand | Completed player-games | Score | Wins | Draws | Capped |\n|---|---:|---:|---:|---:|---:|\n";
    foreach ($summary['openings'] as $color => $m) $text .= "| $color | {$m['completed']} | " . balancePct($m['scoreRate']) . " | {$m['wins']} | {$m['draws']} | {$m['capped']} |\n";
    $text .= "\n## Starting-hand matchups\n\n| Starts as / opponent | " . implode(' | ', DFG_COLORS) . " |\n|---|" . str_repeat('---:|', 6) . "\n";
    foreach (DFG_COLORS as $color) $text .= "| $color | " . implode(' | ', array_map(fn($other) => balancePct($summary['matchups'][$color][$other]['scoreRate']), DFG_COLORS)) . " |\n";
    foreach (['main', 'bonus', 'reaction'] as $role) {
        $rows = array_values($summary['cards']);
        usort($rows, fn($a,$b) => ($b[$role]['scoreRate'] ?? -1) <=> ($a[$role]['scoreRate'] ?? -1));
        $text .= "\n## Cards used as $role\n\n| Card | Hand | Events | Completed player-games | Score when used | " . ($role === 'main' ? 'Mean damage / net damage' : 'Mean immediate net damage gain') . " |\n|---|---|---:|---:|---:|---:|\n";
        foreach ($rows as $row) {
            $m = $row[$role]; $value = $role === 'main' ? ($m['events'] ? number_format($m['averageDamageDealt'],2) . ' / ' . number_format($m['averageNetDamage'],2) : '—') : ($m['events'] ? number_format($m['averageNetDamageGain'],2) : '—');
            $text .= "| {$row['name']} | {$row['color']} | {$m['events']} | {$m['completed']} | " . balancePct($m['scoreRate']) . " | $value |\n";
        }
    }
    $text .= "\n## Paired card-removal experiments\n\nFor each selected card, replay the same tactical-bot opening, seed, and initial reaction priority with that card removed from seat 0's available pool. Positive change means having the card improved that player's score. Both seats have identical policies; seeds and colors are balanced across both initial priority settings. This removes all roles of the card, not only its main effect.\n\n| Card | Completed pairs | Score with card | Score without | Change | Approx. paired-bootstrap 95% interval | Capped pairs |\n|---|---:|---:|---:|---:|---:|---:|\n";
    foreach ($summary['ablations'] as $a) $text .= "| {$a['name']} | {$a['pairs']} | " . balancePct($a['withScore']) . ' | ' . balancePct($a['withoutScore']) . ' | ' . ($a['difference'] === null ? '—' : sprintf('%+.1f pp',100*$a['difference'])) . ' | ' . ($a['interval'][0] === null ? '—' : sprintf('%+.1f to %+.1f pp',100*$a['interval'][0],100*$a['interval'][1])) . " | {$a['cappedPairs']} |\n";
    $text .= "\n## How to interpret this\n\n- High score **when used** is an association. Winners can reach certain hands more often, or save a card for a lethal turn. It does not establish that the card caused the win.\n- Bonus marginal values hold the actual later reactions fixed; reaction values measure the immediate change before responses. Neither estimates long-term value of losing a reusable card.\n- Tactical and casual profiles use the existing live bot with different move thresholds. Explore chooses a uniform legal main, a bonus with 25% probability when possible, and a uniform listed next hand; reactions still use the heuristic. Inspect profile-specific rates in summary.json before generalizing to human play.\n- Card removals provide stronger evidence **for this bot and these matchups**, but change the bot's choices and resource pool. The bootstrap is an exploratory interval over balanced seeded matchup scenarios, with no multiple-comparison correction. A small interval away from zero is a lead for more testing, not a universal card-strength claim.\n- Samples were used to choose removal candidates. Confirm interesting effects with new seeds before changing balance. Unused cards may be overlooked by the heuristic rather than weak. Stalled matches are not silently declared draws.\n- Game rules and live bot/card data were not changed by this experiment. Raw JSONL traces record seeds, openings, plans, reactions, damage, life, and outcomes for reproducibility.\n";
    return $text;
}

$repeats = (int)($options['repeats'] ?? 4); $cap = (int)($options['max-rounds'] ?? 60); $ablationCount = (int)($options['ablation-cards'] ?? 6); $ablationRepeats = (int)($options['ablation-repeats'] ?? 1);
$profiles = explode(',', $options['profiles'] ?? 'tactical,casual,explore');
if (in_array('pro',$profiles,true) && !function_exists('DFGProChoose')) throw new InvalidArgumentException('Selected runtime snapshot does not include the Pro bot');
$seedOffset = (int)($options['seed-offset'] ?? 0);
if ($seedOffset < 0 || $seedOffset > 2000000000) throw new InvalidArgumentException('Invalid seed offset');
if ($repeats < 1 || $repeats > 100 || $cap < 1 || $cap > 100 || $ablationCount < 0 || $ablationCount > 36 || $ablationRepeats < 1 || $ablationRepeats > 100 || array_diff($profiles, ['tactical','casual','explore','pro'])) throw new InvalidArgumentException('Invalid experiment settings');
$out = $options['out'] ?? __DIR__ . '/results/balance-v2';
if (!is_dir($out) && !mkdir($out, 0775, true)) throw new RuntimeException('Cannot create results folder');
$summary = ['rulesVersion' => DFG_RULES_VERSION, 'catalogHash' => hash_file('sha256', $runtimeRoot . '/data/cards.json'), 'maxRounds' => $cap, 'repeats' => $repeats, 'seedOffset' => $seedOffset, 'matches' => 0, 'rounds' => 0, 'decisive' => 0, 'draws' => 0, 'capped' => 0, 'cards' => [], 'openings' => [], 'matchups' => [], 'firstReaction' => balanceMetric(), 'profiles' => [], 'ablations' => []];
$summary['advantages'] = array_fill_keys(DFG_COLORS, ['earned' => 0, 'spent' => 0]); $summary['combinationSpends'] = 0;
foreach (DFGCatalog() as $id => $card) $summary['cards'][$id] = ['id' => $id, 'name' => $card['name'], 'color' => $card['color'], 'main' => balanceMetric(), 'bonus' => balanceMetric(), 'reaction' => balanceMetric(), 'byProfile' => []];
foreach (DFG_COLORS as $color) { $summary['openings'][$color] = balanceMetric(); foreach (DFG_COLORS as $other) $summary['matchups'][$color][$other] = balanceMetric(); }
$raw = fopen($out . '/baseline.jsonl', 'wb');
if (!$raw) throw new RuntimeException('Cannot open raw match output');
$started = microtime(true);
foreach ($profiles as $p => $profile) {
    $summary['profiles'][$profile] = ['decisive' => 0, 'draws' => 0, 'capped' => 0];
    foreach (DFG_COLORS as $ai => $a) foreach (DFG_COLORS as $bi => $b) foreach ([0,1] as $first) for ($repeat = 0; $repeat < $repeats; ++$repeat) {
        $seed = $seedOffset + 100003 + $repeat*1009 + $ai*7919 + $bi*65537 + $first*99991 + $p*104729;
        $match = balanceMatch($a,$b,$seed,$first,$profile,$cap);
        fwrite($raw, json_encode($match, JSON_THROW_ON_ERROR) . "\n");
        balanceAccumulate($summary,$match);
        // Separate profile exposure totals retain the same per-player-match deduplication.
        foreach ([0,1] as $seat) foreach (['main','bonus','reaction'] as $role) {
            $used = [];
            foreach ($match['rounds'] as $r) {
                if ($role === 'main') $used[$r['plans'][$seat]['main']] = true;
                elseif ($role === 'bonus') foreach ($r['plans'][$seat]['bonuses'] as $id) $used[$id] = true;
                else foreach ($r['reactions'] as $reaction) if ($reaction['seat'] === $seat) $used[$reaction['card']] = true;
            }
            foreach (array_keys($used) as $id) { $summary['cards'][$id]['byProfile'][$profile][$role] ??= balanceMetric(); balanceOutcome($summary['cards'][$id]['byProfile'][$profile][$role],$match,$seat); }
        }
        if ($summary['matches'] % 72 === 0) echo 'Baseline: ' . $summary['matches'] . ' matches (' . $profile . '), ' . round(microtime(true)-$started,1) . "s\n";
    }
}
fclose($raw);
foreach ($summary['cards'] as &$row) { foreach (['main','bonus','reaction'] as $role) $row[$role] = balanceDerived($row[$role]); foreach ($row['byProfile'] as &$profile) foreach ($profile as &$metric) $metric = balanceDerived($metric); unset($profile,$metric); } unset($row);
foreach ($summary['openings'] as &$metric) $metric = balanceDerived($metric); unset($metric);
foreach ($summary['matchups'] as &$row) foreach ($row as &$metric) $metric = balanceDerived($metric); unset($row,$metric);
$summary['firstReaction'] = balanceDerived($summary['firstReaction']);
// Two candidates per role, then fill by main-card association. All candidates must have enough exposure.
$candidates = [];
foreach (['main','bonus','reaction','main'] as $role) {
    $ranking = array_values(array_filter($summary['cards'], fn($c) => $c[$role]['completed'] >= max(12, $repeats*6)));
    usort($ranking, fn($a,$b) => $b[$role]['scoreRate'] <=> $a[$role]['scoreRate']);
    $added = 0;
    foreach ($ranking as $c) if (!in_array($c['id'],$candidates,true) && count($candidates) < $ablationCount) { $candidates[]=$c['id']; if (++$added === 2 && $role !== 'main') break; if ($role === 'main' && $added === 2 && count($candidates) <= 2) break; }
}
$ablationRaw = fopen($out . '/ablation.jsonl', 'wb');
if (isset($options['candidate-ids'])) {
    $candidates = array_values(array_unique(array_map('intval',explode(',',$options['candidate-ids']))));
    foreach ($candidates as $id) if (!isset(DFGCatalog()[$id])) throw new InvalidArgumentException('Unknown candidate card');
}
$references = [];
if ($candidates) foreach (DFG_COLORS as $ai=>$a) foreach (DFG_COLORS as $bi=>$b) foreach ([0,1] as $first) for($repeat=0;$repeat<$ablationRepeats;++$repeat) {
    $seed=$seedOffset+7000003+$repeat*1009+$ai*7919+$bi*65537+$first*99991;
    $references[] = balanceMatch($a,$b,$seed,$first,'tactical',$cap);
}
foreach ($candidates as $id) {
    $with=0.0; $without=0.0; $differences=[]; $capped=0;
    foreach ($references as $reference) {
        $removed=balanceMatch($reference['opening'][0],$reference['opening'][1],$reference['seed'],$reference['first'],'tactical',$cap,$id);
        fwrite($ablationRaw,json_encode(['card'=>$id,'baseline'=>$reference,'removed'=>$removed],JSON_THROW_ON_ERROR)."\n");
        $a=balanceScore($reference,0);$b=balanceScore($removed,0);
        if($a===null||$b===null){++$capped;continue;}
        $with+=$a;$without+=$b;$differences[]=$a-$b;
    }
    $n=count($differences);
    $summary['ablations'][]=['id'=>$id,'name'=>DFGCatalog()[$id]['name'],'pairs'=>$n,'cappedPairs'=>$capped,'withScore'=>$n?$with/$n:null,'withoutScore'=>$n?$without/$n:null,'difference'=>$n?array_sum($differences)/$n:null,'interval'=>balanceBootstrap($differences,12345+$id)];
    echo 'Removal: '.DFGCatalog()[$id]['name'].', '.$n.' completed pairs, '.round(microtime(true)-$started,1)."s\n";
}
fclose($ablationRaw);
$summary['additionalMatches'] = count($references) * (count($candidates)+1);
$summary['elapsedSeconds']=round(microtime(true)-$started,2);
file_put_contents($out.'/summary.json',json_encode($summary,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n");
file_put_contents($out.'/balance-report.md',balanceReport($summary));
echo 'Saved '.$summary['matches'].' baseline + '.$summary['additionalMatches'].' experiment matches to '.$out.' in '.$summary['elapsedSeconds']."s\n";
