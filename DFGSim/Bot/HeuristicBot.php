<?php
declare(strict_types=1);
require_once __DIR__ . '/../Custom/GameLogic.php';

// The bot receives DFGObserve(), never hidden commitments or hidden next-hand choices.
function DFGBurnCost(int $id, int $life): float {
    $card = DFGCatalog()[$id];
    return (1.1 + 0.08 * ($card['attack'] + $card['block'])) * ($life <= 12 ? 0.65 : 1.0);
}

function DFGCombatValue(array $stats, array $players, int $seat): float {
    $other = 1 - $seat;
    $dealt = $stats[$seat]['damage']; $taken = $stats[$other]['damage'];
    $win = $dealt >= $players[$other]['life']; $lose = $taken >= $players[$seat]['life'];
    if ($win || $lose) return $win && $lose ? 0.0 : ($win ? 5000.0 : -5000.0);
    return 1.2 * $dealt - ($players[$seat]['life'] <= 12 ? 1.35 : 0.9) * $taken;
}

// Value non-damage payoffs using only the sampled/revealed plans and public resources.
function DFGStrategicValue(array $plans, array $reactions, array $stats, array $players, int $seat): float {
    $specials = [DFGTriggeredSpecials($plans, $stats, 0), DFGTriggeredSpecials($plans, $stats, 1)];
    $value = 0.0;
    foreach ([0, 1] as $actor) {
        $score = 0.0; $effects = $specials[$actor];
        $objective = DFGCatalog()[$plans[$actor]['main']]['advantage']['earn'];
        $held = array_diff($players[$actor]['advantages'] ?? [], $plans[$actor]['advantages'] ?? []);
        $pending = $players[$actor]['pendingObjective'] ?? null;
        if ($pending && $stats[$actor]['damage'] >= ($pending['minimumDamage'] ?? 4) && !in_array($pending['color'], $held, true)
            && !in_array('hit_deny_earn', $specials[1-$actor], true)) $score += 1.1;
        if ($objective && $objective['condition'] === 'damage_next_round' && !in_array($objective['color'], $held, true)) $score += 0.4;
        if ($objective && !in_array('hit_deny_earn', $specials[1-$actor], true) && !in_array($objective['color'], $held, true)
            && DFGObjectiveAchieved($plans[$actor], $plans[1-$actor], $stats, $actor, $reactions)) $score += 1.1;
        if (!in_array('brace_reset',array_merge(...$specials),true)) $score += 0.65 * DFGNextAttackFromSpecials($effects);
        if (in_array('miss_recovery',$effects,true)) $score -= 0.8;
        if (in_array('bonus_relocate', $effects, true) && !in_array($plans[1-$actor]['main'], $players[1-$actor]['discarded'], true)) $score += 1.2;
        if (in_array('hit_burn_lock',$effects,true)) $score += 1.2;
        $enemyHeld=array_diff($players[1-$actor]['advantages']??[], $plans[1-$actor]['advantages']??[]);
        $score += 1.1 * min(count($enemyHeld),count(array_intersect(['hit_strip','guard_strip'],$effects)));
        if (in_array('hit_force_color',$effects,true)) {
            $routes=array_filter(DFGCatalog()[$plans[1-$actor]['main']]['switches'],fn($color)=>DFGAvailable($players[1-$actor],$color));
            if(count($routes)>1)$score+=0.9;
        }
        if (in_array('hit_heal', $effects, true) && $players[$actor]['life'] > $stats[1-$actor]['damage']
            && $players[$actor]['life'] - $stats[1-$actor]['damage'] < 40) $score += 0.9;
        if (in_array('hit_burn_enemy_main', $effects, true)) $score += DFGBurnCost($plans[1-$actor]['main'], $players[1-$actor]['life']);
        // Route value is intentionally modest; future matchups remain uncertain.
        if (array_intersect(['safe_switch', 'hit_switch', 'free_switch'], $effects)) $score += 0.4;
        if (in_array('stay_green', $effects, true) && DFGAvailable($players[$actor], 'Green')) $score += 0.3;
        if (array_intersect(['hit_cut_routes', 'clean_force_move'], $effects)) {
            $enemyRoutes = array_values(array_filter(DFGCatalog()[$plans[1-$actor]['main']]['switches'], fn($color) => DFGAvailable($players[1-$actor], $color)));
            $blocked = [$players[1-$actor]['color']];
            if (in_array('hit_cut_routes', $effects, true) ? count($enemyRoutes)>1 : (array_intersect($enemyRoutes, $blocked) && array_diff($enemyRoutes, $blocked))) $score += 0.5;
        }
        $value += $actor === $seat ? $score : -$score;
    }
    return $value;
}

function DFGOpponentSamples(array $view, int $seat): array {
    $other = 1 - $seat;
    $available = DFGAvailable($view['players'][$other], $view['players'][$other]['color']);
    $cards = DFGCatalog();
    $frequency = [];
    foreach (array_slice($view['history'], -8) as $round) $frequency[$round['main'][$other]] = ($frequency[$round['main'][$other]] ?? 0) + 1;
    $samples = [];
    foreach ($available as $main) {
        $weight = 1 + 0.4 * ($frequency[$main] ?? 0);
        $variants = DFGSpendVariants($view['players'][$other], $main);
        foreach ($variants as $variant) $samples[] = ['plan' => ['main' => $main, 'bonuses' => []] + $variant, 'weight' => $weight / count($variants)];
        // Anticipate an occasional single bonus; do not read the opponent's actual committed burns.
        if (!DFGBurnLocked($view,$other)) foreach ($available as $bonus) if ($bonus !== $main && $cards[$bonus]['bonus'] !== '') foreach ($variants as $variant) $samples[] = ['plan' => ['main' => $main, 'bonuses' => [$bonus]] + $variant, 'weight' => 0.12 * $weight / count($variants)];
    }
    return $samples;
}

function DFGEnumeratePlans(array $view, int $seat): array {
    $cards = DFGCatalog();
    $available = DFGAvailable($view['players'][$seat], $view['players'][$seat]['color']);
    $plans = [];
    foreach ($available as $main) {
        $bonuses = DFGBurnLocked($view,$seat) ? [] : array_values(array_filter($available, fn($id) => $id !== $main && $cards[$id]['bonus'] !== ''));
        foreach (DFGSpendVariants($view['players'][$seat], $main) as $variant) {
            $plans[] = ['main' => $main, 'bonuses' => []] + $variant;
            foreach ($bonuses as $id) $plans[] = ['main' => $main, 'bonuses' => [$id]] + $variant;
        }
    }
    return $plans;
}

// Compare keeping the revealed main with each legal replacement, charging the permanent burn.
function DFGBotCancelRanking(array $plans,array $players,int $seat,array $candidates): array {
    $ranking=[];
    foreach ($candidates as $main) {
        $trial=$plans;$trial[$seat]=DFGCancelledPlan($plans[$seat],$main);
        $stats=DFGComputeRound($trial);
        $score=DFGCombatValue($stats,$players,$seat)+DFGStrategicValue($trial,[],$stats,$players,$seat);
        if ($main!==$plans[$seat]['main']) $score-=DFGBurnCost($plans[$seat]['main'],$players[$seat]['life']);
        $ranking[]=['action'=>['type'=>'cancel','main'=>$main],'score'=>$score];
    }
    usort($ranking,fn($a,$b)=>$b['score']<=>$a['score']);
    return $ranking;
}
function DFGBotForecastValue(array $view,array $plans,int $seat): float {
    if (!DFGBurnLocked($view,$seat) && in_array('cancel_main',DFGPlanSpecials($plans[$seat]),true)) {
        return DFGBotCancelRanking($plans,$view['players'],$seat,DFGCancelCandidates($view['players'][$seat],$plans[$seat]))[0]['score'];
    }
    $stats=DFGComputeRound($plans);
    return DFGCombatValue($stats,$view['players'],$seat)+DFGStrategicValue($plans,[],$stats,$view['players'],$seat);
}

function DFGBotPlanRanking(array $view, int $seat): array {
    $cards = DFGCatalog(); $other = 1 - $seat;
    $samples = DFGOpponentSamples($view, $seat);
    $ranking = [];
    foreach (DFGEnumeratePlans($view, $seat) as $plan) {
        $total = 0.0; $weights = 0.0; $worst = INF;
        foreach ($samples as $sample) {
            $plans = []; $plans[$seat] = $plan; $plans[$other] = $sample['plan'];
            $value = DFGBotForecastValue($view,$plans,$seat);
            $total += $value * $sample['weight']; $weights += $sample['weight']; $worst = min($worst, $value);
        }
        $burnCost = array_sum(array_map(fn($id) => DFGBurnCost($id, $view['players'][$seat]['life']), $plan['bonuses']));
        $remaining = count(DFGAvailable($view['players'][$seat], $view['players'][$seat]['color'])) - count($plan['bonuses']);
        $flexibility = 0.08 * count($cards[$plan['main']]['switches']);
        $tokenCost = 0.65 * count($plan['advantages'] ?? []);
        $score = 0.92 * ($total / max(1, $weights)) + 0.08 * $worst - $burnCost - $tokenCost - ($remaining === 1 ? 0.8 : 0) + $flexibility + (isset($plan['extraSwitch']) ? 0.2 : 0);
        $ranking[] = ['action' => ['type' => 'commit'] + $plan, 'score' => $score];
    }
    usort($ranking, fn($a, $b) => $b['score'] <=> $a['score']);
    return $ranking;
}

function DFGBotReaction(array $view, int $seat): array {
    $current = DFGComputeRound($view['commitments'], $view['reactions']);
    $best = DFGCombatValue($current, $view['players'], $seat) + DFGStrategicValue($view['commitments'], $view['reactions'], $current, $view['players'], $seat) + 0.15;
    $action = ['type' => 'pass'];
    foreach ($view['reactionOptions'] as $id) {
        $reactions = $view['reactions']; $reactions[] = ['seat' => $seat, 'card' => $id];
        $stats = DFGComputeRound($view['commitments'], $reactions);
        $value = DFGCombatValue($stats, $view['players'], $seat) + DFGStrategicValue($view['commitments'], $reactions, $stats, $view['players'], $seat) - DFGBurnCost($id, $view['players'][$seat]['life']);
        if ($value > $best) { $best = $value; $action = ['type' => 'react', 'card' => $id]; }
    }
    return $action;
}

function DFGBotSwitchRanking(array $view, int $seat): array {
    $other = 1 - $seat;
    $futureView=$view; ++$futureView['round'];
    // Both choices are still hidden. Consider every legal opposing next color, not its selected color.
    $opponentColors = DFGSwitchOptions($view, $other);
    $opponentCards = [];
    foreach ($opponentColors as $color) $opponentCards = array_merge($opponentCards, DFGAvailable($view['players'][$other], $color));
    $ranking = [];
    foreach ($view['switchOptions'] as $color) {
        $available = DFGAvailable($view['players'][$seat], $color); $best = -5000.0;
        foreach ($available as $main) {
            foreach (DFGSpendVariants($view['players'][$seat], $main) as $variant) {
                $score = 0.0;
                foreach ($opponentCards as $opponent) {
                    $plans = []; $plans[$seat] = ['main' => $main, 'bonuses' => []] + $variant; $plans[$other] = ['main' => $opponent, 'bonuses' => [], 'momentum' => $view['players'][$other]['nextAttack'] ?? 0, 'blockPenalty'=>$view['players'][$other]['nextBlockPenalty']??0];
                    $score += DFGBotForecastValue($futureView,$plans,$seat);
                }
                $best = max($best, $score / max(1, count($opponentCards)) - 0.65 * count($variant['advantages'] ?? []));
            }
        }
        $ranking[] = ['action' => ['type' => 'switch', 'color' => $color], 'score' => $best + 0.08 * count($available)];
    }
    usort($ranking, fn($a, $b) => $b['score'] <=> $a['score']);
    return $ranking;
}

function DFGBotCut(array $view, int $seat): array {
    $best = INF; $action = null;
    foreach ($view['cutOptions'] as $color) {
        $trial = DFGApply($view, $seat, ['type'=>'cut','color'=>$color]);
        $trial['phase'] = 'switching';
        $ranking = DFGBotSwitchRanking(DFGObserve($trial, 1-$seat), 1-$seat);
        $score = $ranking[0]['score'] ?? 0;
        if ($score < $best) { $best = $score; $action = ['type'=>'cut','color'=>$color]; }
    }
    if ($action === null) throw new LogicException('Bot has no legal route cut');
    return $action;
}

function DFGBotForce(array $view,int $seat): array {
    $best=INF;$action=null;
    foreach($view['forceOptions'] as $color){
        $trial=DFGApply($view,$seat,['type'=>'direct','color'=>$color]);$trial['phase']='switching';
        $ranking=DFGBotSwitchRanking(DFGObserve($trial,1-$seat),1-$seat);
        $score=$ranking[0]['score']??0;
        if($score<$best){$best=$score;$action=['type'=>'direct','color'=>$color];}
    }
    if($action===null)throw new LogicException('Bot has no legal forced destination');
    return $action;
}

function DFGBotRelocate(array $view, int $seat): array {
    $victim = 1 - $seat;
    $main = $view['commitments'][$victim]['main'];
    $current = DFGCardHand($view['players'][$victim], $main);
    // Prefer depleting a hand while placing the main outside its own next routes.
    $best = 0.0; $choice = 'keep';
    $routes = DFGSwitchOptions($view, $victim, false, false);
    foreach ($view['relocateOptions'] as $color) {
        $score = (in_array($current, $routes, true) ? 2.0 : 0.5) / max(1, count(DFGAvailable($view['players'][$victim], $current)))
            - (in_array($color, $routes, true) ? 1.5 : 0.0);
        if ($score > $best) { $best = $score; $choice = $color; }
    }
    return ['type'=>'relocate', 'color'=>$choice];
}

function DFGBotChoose(array $view, int $seat, int &$rng): array {
    if (($view['difficulty'] ?? 'tactical') === 'pro') return DFGProChoose($view,$seat,$rng);
    if ($view['phase'] === 'relocating') return DFGBotRelocate($view, $seat);
    if ($view['phase']==='cancelling') return DFGBotCancelRanking($view['commitments'],$view['players'],$seat,$view['cancelOptions'])[0]['action'];
    if ($view['phase'] === 'stripping') {
        // Prefer denying spends available in the opposing legal next hands.
        $colors=DFGSwitchOptions($view,1-$seat);
        $ranking=[];
        foreach ($view['stripOptions'] as $color) {
            $value=0;
            foreach ($colors as $hand) foreach (DFGAvailable($view['players'][1-$seat],$hand) as $id) {
                $rule=DFGCatalog()[$id]['advantage']['spend'];
                if ($rule && in_array($color,$rule['colors'],true)) $value += $rule['flexible'] ? 1 : 2;
            }
            $ranking[$color]=$value;
        }
        arsort($ranking);
        return ['type'=>'strip','color'=>array_key_first($ranking)];
    }
    if ($view['phase'] === 'directing') return DFGBotForce($view,$seat);
    if ($view['phase'] === 'routing') return DFGBotCut($view, $seat);
    if ($view['phase'] === 'reactions') return DFGBotReaction($view, $seat);
    $ranking = $view['phase'] === 'planning' ? DFGBotPlanRanking($view, $seat) : DFGBotSwitchRanking($view, $seat);
    if (!$ranking) throw new LogicException('Bot has no legal actions');
    $threshold = $view['difficulty'] === 'casual' ? 3.0 : 0.75;
    $choices = array_values(array_filter($ranking, fn($entry) => $entry['score'] >= $ranking[0]['score'] - $threshold));
    // Seeded bot variation only; cards, damage, and combat have no random outcomes.
    $rng = (int)(($rng * 48271) % 2147483647);
    return $choices[$rng % count($choices)]['action'];
}

function DFGRunBot(array $state): array {
    for ($steps = 0; $steps < 20; ++$steps) {
        $active = ($state['phase'] === 'planning' && $state['commitments'][1] === null)
            || ($state['phase'] === 'reactions' && $state['reactionTurn'] === 1)
            || ($state['phase'] === 'cancelling' && DFGCancelOptions($state,1))
            || ($state['phase'] === 'stripping' && DFGStripOptions($state,1))
            || ($state['phase'] === 'relocating' && DFGRelocateOptions($state,1))
            || ($state['phase'] === 'routing' && DFGCutOptions($state, 1))
            || ($state['phase'] === 'directing' && DFGForceOptions($state, 1))
            || ($state['phase'] === 'switching' && $state['switches'][1] === null);
        if (!$active) return $state;
        $view = DFGObserve($state, 1);
        $action = DFGBotChoose($view, 1, $state['seed']);
        $state = DFGApply($state, 1, $action);
    }
    throw new LogicException('Bot action limit exceeded');
}

require_once __DIR__ . '/ProBot.php';
