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
        foreach ($available as $bonus) if ($bonus !== $main && $cards[$bonus]['bonus'] !== '') foreach ($variants as $variant) $samples[] = ['plan' => ['main' => $main, 'bonuses' => [$bonus]] + $variant, 'weight' => 0.12 * $weight / count($variants)];
    }
    return $samples;
}

function DFGEnumeratePlans(array $view, int $seat): array {
    $cards = DFGCatalog();
    $available = DFGAvailable($view['players'][$seat], $view['players'][$seat]['color']);
    $plans = [];
    foreach ($available as $main) {
        $bonuses = array_values(array_filter($available, fn($id) => $id !== $main && $cards[$id]['bonus'] !== ''));
        foreach (DFGSpendVariants($view['players'][$seat], $main) as $variant) {
            $plans[] = ['main' => $main, 'bonuses' => []] + $variant;
            foreach ($bonuses as $id) $plans[] = ['main' => $main, 'bonuses' => [$id]] + $variant;
        }
    }
    return $plans;
}

function DFGBotPlanRanking(array $view, int $seat): array {
    $cards = DFGCatalog(); $other = 1 - $seat;
    $samples = DFGOpponentSamples($view, $seat);
    $ranking = [];
    foreach (DFGEnumeratePlans($view, $seat) as $plan) {
        $total = 0.0; $weights = 0.0; $worst = INF;
        foreach ($samples as $sample) {
            $plans = []; $plans[$seat] = $plan; $plans[$other] = $sample['plan'];
            $stats = DFGComputeRound($plans);
            $value = DFGCombatValue($stats, $view['players'], $seat);
            $objective = $cards[$plan['main']]['advantage']['earn'];
            $held = array_diff($view['players'][$seat]['advantages'] ?? [], $plan['advantages'] ?? []);
            if ($objective && !in_array($objective['color'], $held, true) && DFGObjectiveAchieved($plan, $sample['plan'], $stats, $seat)) $value += 1.1;
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
    $best = DFGCombatValue($current, $view['players'], $seat) + 0.15;
    $action = ['type' => 'pass'];
    foreach ($view['reactionOptions'] as $id) {
        $reactions = $view['reactions']; $reactions[] = ['seat' => $seat, 'card' => $id];
        $value = DFGCombatValue(DFGComputeRound($view['commitments'], $reactions), $view['players'], $seat) - DFGBurnCost($id, $view['players'][$seat]['life']);
        if ($value > $best) { $best = $value; $action = ['type' => 'react', 'card' => $id]; }
    }
    return $action;
}

function DFGBotSwitchRanking(array $view, int $seat): array {
    $other = 1 - $seat;
    // Both choices are still hidden. Consider every legal opposing next color, not its selected color.
    $opponentColors = DFGSwitchOptions($view, $other);
    $opponentCards = [];
    foreach ($opponentColors as $color) $opponentCards = array_merge($opponentCards, DFGAvailable($view['players'][$other], $color));
    $ranking = [];
    foreach ($view['switchOptions'] as $color) {
        $available = DFGAvailable($view['players'][$seat], $color); $best = -INF;
        foreach ($available as $main) {
            foreach (DFGSpendVariants($view['players'][$seat], $main) as $variant) {
                $score = 0.0;
                foreach ($opponentCards as $opponent) {
                    $plans = []; $plans[$seat] = ['main' => $main, 'bonuses' => []] + $variant; $plans[$other] = ['main' => $opponent, 'bonuses' => []];
                    $score += DFGCombatValue(DFGComputeRound($plans), $view['players'], $seat);
                }
                $best = max($best, $score / max(1, count($opponentCards)) - 0.65 * count($variant['advantages'] ?? []));
            }
        }
        $ranking[] = ['action' => ['type' => 'switch', 'color' => $color], 'score' => $best + 0.08 * count($available)];
    }
    usort($ranking, fn($a, $b) => $b['score'] <=> $a['score']);
    return $ranking;
}

function DFGBotChoose(array $view, int $seat, int &$rng): array {
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
            || ($state['phase'] === 'switching' && $state['switches'][1] === null);
        if (!$active) return $state;
        $view = DFGObserve($state, 1);
        $action = DFGBotChoose($view, 1, $state['seed']);
        $state = DFGApply($state, 1, $action);
    }
    throw new LogicException('Bot action limit exceeded');
}
