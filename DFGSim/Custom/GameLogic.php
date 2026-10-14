<?php
declare(strict_types=1);
require_once __DIR__ . '/CardCatalog.php';
const DFG_RULES_VERSION = 14;

function DFGNewGame(string $humanColor = 'Red', string $botColor = 'Purple', int $seed = 1, int $first = 0, string $difficulty = 'tactical'): array {
    if (!in_array($humanColor, DFG_COLORS, true) || !in_array($botColor, DFG_COLORS, true) || !in_array($first, [0, 1], true)
        || !in_array($difficulty, ['casual', 'tactical'], true) || $seed < 1 || $seed > 2147483647) throw new InvalidArgumentException('Invalid match settings');
    DFGCatalog();
    return ['rulesVersion' => DFG_RULES_VERSION, 'revision' => 0, 'round' => 1, 'phase' => 'planning', 'seed' => $seed, 'initialSeed' => $seed, 'first' => $first, 'difficulty' => $difficulty,
        'players' => [['life' => 40, 'color' => $humanColor, 'discarded' => [], 'advantages' => []], ['life' => 40, 'color' => $botColor, 'discarded' => [], 'advantages' => []]],
        'commitments' => [null, null], 'switches' => [null, null], 'reactions' => [], 'reactionTurn' => $first, 'passes' => 0,
        'winner' => null, 'history' => [], 'log' => [['round' => 1, 'text' => 'Fight! Choose a main card and optional bonus burns.']]];
}

function DFGAvailable(array $player, ?string $color = null): array {
    return array_values(array_filter(array_keys(DFGCatalog()), fn($id) => !in_array($id, $player['discarded'], true) && ($color === null || DFGCatalog()[$id]['color'] === $color)));
}

// Inspect only the committed bonus's resolved changes for its owner's block.
// Opponent gifts and reaction burns do not count as defensive bonus commitments.
function DFGCommittedBlockBonus(int $seat, array $plans, array $cards): bool {
    foreach ($plans[$seat]['bonuses'] as $id) {
        $effect=$cards[$id]['effects']['bonus'];
        $branch=DFGConditionMatches($effect['condition'],$seat,$plans,$cards)?$effect['yes']:$effect['no'];
        foreach($branch as $change) if(!$change['opponent'] && $change['stat']==='block' && $change['amount']>0) return true;
    }
    return false;
}
function DFGConditionMatches(?array $condition, int $seat, array $plans, array $cards): bool {
    if ($condition === null) return true;
    $other = 1 - $seat;
    if ($condition['type'] === 'block_bonus') return DFGCommittedBlockBonus($other,$plans,$cards);
    if ($condition['type'] === 'burns') {
        $count = count($plans[$condition['opponent'] ? $other : $seat]['bonuses']);
        return $condition['exact'] ? $count === $condition['minimum'] : $count >= $condition['minimum'];
    }
    $card = $cards[$plans[$other]['main']];
    if ($condition['type'] === 'equal') return $card['attack'] === $card['block'];
    return $condition['comparison'] === 'more' ? $card[$condition['stat']] >= $condition['value'] : $card[$condition['stat']] <= $condition['value'];
}

// All current cards are additive. Printed conditions never inspect modified stats or future reactions.
function DFGComputeRound(array $plans, array $reactions = []): array {
    $cards = DFGCatalog();
    $stats = [];
    foreach ([0, 1] as $seat) {
        $main = $cards[$plans[$seat]['main']];
        $stats[$seat] = ['attack' => $main['attack'], 'block' => $main['block'], 'pierce' => 0];
    }
    $effects = [];
    foreach ([0, 1] as $seat) {
        $effects[] = [$seat, $cards[$plans[$seat]['main']]['effects']['ability']];
        foreach ($plans[$seat]['bonuses'] as $id) $effects[] = [$seat, $cards[$id]['effects']['bonus']];
    }
    foreach ($reactions as $reaction) $effects[] = [$reaction['seat'], $cards[$reaction['card']]['effects']['reaction']];
    foreach ($effects as [$seat, $effect]) {
        $changes = DFGConditionMatches($effect['condition'], $seat, $plans, $cards) ? $effect['yes'] : $effect['no'];
        foreach ($changes as $change) $stats[$change['opponent'] ? 1 - $seat : $seat][$change['stat']] += $change['amount'];
    }
    foreach ([0, 1] as $seat) {
        foreach (DFGAdvantageChanges($plans[$seat]) as $change) $stats[$seat][$change['stat']] += $change['amount'];
    }
    foreach ([0, 1] as $seat) {
        $stats[$seat]['effectiveOpponentBlock'] = max(0, $stats[1 - $seat]['block'] - $stats[$seat]['pierce']);
        $stats[$seat]['damage'] = max(0, $stats[$seat]['attack'] - $stats[$seat]['effectiveOpponentBlock']);
    }
    return $stats;
}

function DFGAdvantageChanges(array $plan): array {
    $spend = $plan['advantages'] ?? [];
    if (!$spend) return [];
    $rule = DFGCatalog()[$plan['main']]['advantage']['spend'];
    if (!$rule) throw new LogicException('Main card cannot spend advantage');
    if (!$rule['flexible']) return $rule['changes'];
    $changes = [];
    foreach ($spend as $color) {
        $stat = match ($color) { 'Red', 'Purple' => 'attack', 'Green', 'Blue' => 'block', 'Orange' => 'pierce', 'Yellow' => null };
        if ($stat !== null) $changes[] = ['opponent' => false, 'stat' => $stat, 'amount' => 1];
    }
    if (count($spend) === 2) $changes[] = ['opponent' => false, 'stat' => 'attack', 'amount' => 1];
    return $changes;
}

function DFGObjectiveAchieved(array $plan, array $opponent, array $stats, int $seat, array $reactions = []): bool {
    $objective = DFGCatalog()[$plan['main']]['advantage']['earn'];
    if (!$objective) return false;
    $enemy = DFGCatalog()[$opponent['main']];
    return match ($objective['condition']) {
        'punish_block_bonus' => $stats[$seat]['damage'] > 0 && DFGCommittedBlockBonus(1-$seat,$seat===0?[$plan,$opponent]:[$opponent,$plan],DFGCatalog()),
        'damage_vs_block' => $stats[$seat]['damage'] > 0 && $enemy['block'] >= 4,
        'guard_high' => $stats[1-$seat]['damage'] === 0 && $enemy['attack'] >= 5,
        'punish_bonus' => $stats[$seat]['damage'] > 0 && count($opponent['bonuses']) > 0,
        'heavy_unassisted' => $stats[$seat]['damage'] >= 4 && !$plan['bonuses'],
        'clean_counter' => $stats[$seat]['damage'] > 0 && $stats[1-$seat]['damage'] === 0,
        'punish_low_attack' => $stats[$seat]['damage'] > 0 && $enemy['attack'] <= 3,
        'guard_bonus' => $stats[1-$seat]['damage'] === 0 && count($opponent['bonuses']) > 0,
        'efficient_hit' => $stats[$seat]['damage'] > 0 && !$plan['bonuses'] && !array_filter($reactions, fn($reaction) => $reaction['seat'] === $seat),
        'punish_high_attack' => $stats[$seat]['damage'] > 0 && $enemy['attack'] >= 5,
        'break_heavy_guard' => $stats[$seat]['damage'] > 0 && $enemy['block'] >= 5
    };
}

function DFGValidateAdvantageSpend(array $state, int $seat, int $main, mixed $spend, mixed $extra): void {
    $rule = DFGCatalog()[$main]['advantage']['spend'];
    if (!is_array($spend) || !array_is_list($spend) || count($spend) > ($rule && $rule['flexible'] ? 2 : 1)) throw new InvalidArgumentException('Choose at most the permitted number of advantages');
    $seen = [];
    foreach ($spend as $color) {
        if (!is_string($color) || !$rule || !in_array($color, $rule['colors'], true) || !in_array($color, $state['players'][$seat]['advantages'], true) || in_array($color, $seen, true)) throw new InvalidArgumentException('Spend distinct, owned advantages allowed by your main card');
        $seen[] = $color;
    }
    $yellow = $rule && $rule['flexible'] && in_array('Yellow', $spend, true);
    if ($yellow) {
        if (!is_string($extra) || $extra === $state['players'][$seat]['color'] || !in_array($extra, DFG_COLORS, true) || in_array($extra, DFGCatalog()[$main]['switches'], true) || !DFGAvailable($state['players'][$seat], $extra)) throw new InvalidArgumentException('Yellow advantage needs one additional available switch color different from your active hand');
    } elseif ($extra !== null) throw new InvalidArgumentException('An extra switch color requires spending Yellow on Combination Strike');
}

// Shared legal choices for bots and tests; tokens can be spent only through the main card.
function DFGSpendVariants(array $player, int $main): array {
    $rule = DFGCatalog()[$main]['advantage']['spend']; $variants = [[]];
    if (!$rule) return $variants;
    $colors = array_values(array_intersect($rule['colors'], $player['advantages'] ?? []));
    $sets = array_map(fn($color) => [$color], $colors);
    if ($rule['flexible']) for ($a = 0; $a < count($colors); ++$a) for ($b = $a + 1; $b < count($colors); ++$b) $sets[] = [$colors[$a], $colors[$b]];
    foreach ($sets as $set) {
        if ($rule['flexible'] && in_array('Yellow', $set, true)) {
            foreach (array_diff(DFG_COLORS, DFGCatalog()[$main]['switches'], [DFGCatalog()[$main]['color']]) as $color) if (DFGAvailable($player, $color)) $variants[] = ['advantages' => $set, 'extraSwitch' => $color];
        } else $variants[] = ['advantages' => $set];
    }
    return $variants;
}

function DFGLog(array &$state, string $text): void {
    $state['log'][] = ['round' => $state['round'], 'text' => $text];
    $state['log'] = array_slice($state['log'], -300);
}

function DFGReveal(array &$state): void {
    $cards = DFGCatalog();
    foreach ([0, 1] as $seat) {
        $plan = $state['commitments'][$seat];
        $spend = $plan['advantages'] ?? [];
        $state['players'][$seat]['advantages'] = array_values(array_diff($state['players'][$seat]['advantages'], $spend));
        $state['players'][$seat]['discarded'] = array_merge($state['players'][$seat]['discarded'], $plan['bonuses']);
        $names = array_map(fn($id) => $cards[$id]['name'], $plan['bonuses']);
        DFGLog($state, ($seat === 0 ? 'You reveal ' : 'Bot reveals ') . $cards[$plan['main']]['name'] . ($names ? ' + bonus burns: ' . implode(', ', $names) : ' (no bonus burns).'));
        if ($spend) DFGLog($state, ($seat === 0 ? 'You spend ' : 'Bot spends ') . implode(' + ', $spend) . ' advantage.' . (isset($plan['extraSwitch']) ? ' Extra switch: ' . $plan['extraSwitch'] . '.' : ''));
    }
    $state['phase'] = 'reactions';
    $state['reactionTurn'] = ($state['first'] + $state['round'] - 1) % 2;
    $state['passes'] = 0;
}

function DFGResolve(array &$state): void {
    $stats = DFGComputeRound($state['commitments'], $state['reactions']);
    foreach ([0, 1] as $seat) $state['players'][$seat]['life'] = max(0, $state['players'][$seat]['life'] - $stats[1 - $seat]['damage']);
    DFGLog($state, 'Damage resolves together: you deal ' . $stats[0]['damage'] . ', bot deals ' . $stats[1]['damage'] . '. Life: ' . $state['players'][0]['life'] . ' / ' . $state['players'][1]['life'] . '.');
    foreach ([0, 1] as $seat) {
        if (DFGObjectiveAchieved($state['commitments'][$seat], $state['commitments'][1-$seat], $stats, $seat, $state['reactions'])) {
            $color = DFGCatalog()[$state['commitments'][$seat]['main']]['advantage']['earn']['color'];
            if (!in_array($color, $state['players'][$seat]['advantages'], true)) {
                $state['players'][$seat]['advantages'][] = $color;
                DFGLog($state, ($seat === 0 ? 'You earn ' : 'Bot earns ') . $color . ' advantage: objective achieved.');
            }
        }
    }
    $state['history'][] = ['round' => $state['round'], 'main' => array_column($state['commitments'], 'main'), 'stats' => $stats];
    $state['history'] = array_slice($state['history'], -100);
    $dead = array_map(fn($player) => $player['life'] <= 0, $state['players']);
    if ($dead[0] || $dead[1]) {
        $state['phase'] = 'finished';
        $state['winner'] = $dead[0] && $dead[1] ? 'draw' : ($dead[0] ? 1 : 0);
        DFGLog($state, $state['winner'] === 'draw' ? 'Double knockout — draw.' : ($state['winner'] === 0 ? 'You win!' : 'Bot wins.'));
    } else {
        $state['phase'] = 'switching';
        $state['switches'] = [null, null];
    }
}

function DFGSwitchOptions(array $state, int $seat): array {
    if ($state['commitments'][$seat] === null) return [];
    $colors = DFGCatalog()[$state['commitments'][$seat]['main']]['switches'];
    if (isset($state['commitments'][$seat]['extraSwitch'])) $colors[] = $state['commitments'][$seat]['extraSwitch'];
    return array_values(array_filter(array_unique($colors), fn($color) => count(DFGAvailable($state['players'][$seat], $color)) > 0));
}

function DFGReactionOptions(array $state, int $seat): array {
    if ($state['phase'] !== 'reactions') return [];
    return array_values(array_filter(DFGAvailable($state['players'][$seat], $state['players'][$seat]['color']),
        fn($id) => $id !== $state['commitments'][$seat]['main'] && DFGCatalog()[$id]['reaction'] !== ''));
}

// Copy-on-write: failed actions cannot partially burn cards or change a session state.
function DFGApply(array $state, int $seat, array $action): array {
    if (!in_array($seat, [0, 1], true)) throw new InvalidArgumentException('Invalid player');
    if ($state['phase'] === 'finished') throw new LogicException('This match has finished');
    $type = $action['type'] ?? '';
    if ($type === 'resign') {
        $state['phase'] = 'finished'; $state['winner'] = 1 - $seat;
        DFGLog($state, ($seat === 0 ? 'You resign.' : 'Bot resigns.'));
    } elseif ($type === 'commit') {
        if ($state['phase'] !== 'planning' || $state['commitments'][$seat] !== null) throw new LogicException('You cannot commit cards now');
        $main = $action['main'] ?? null;
        $bonuses = $action['bonuses'] ?? [];
        $available = DFGAvailable($state['players'][$seat], $state['players'][$seat]['color']);
        if (!is_int($main) || !in_array($main, $available, true) || !is_array($bonuses) || !array_is_list($bonuses)) throw new InvalidArgumentException('Choose an available main card from your active hand');
        if (count($bonuses) > 1) throw new InvalidArgumentException('You may burn at most one bonus card per round');
        if (count(array_unique($bonuses, SORT_REGULAR)) !== count($bonuses)) throw new InvalidArgumentException('A card can only be burned once');
        foreach ($bonuses as $id) if (!is_int($id) || $id === $main || !in_array($id, $available, true) || DFGCatalog()[$id]['bonus'] === '') throw new InvalidArgumentException('Bonus burns must be different available cards with bonus effects in your active hand');
        $spend = $action['advantages'] ?? []; $extra = $action['extraSwitch'] ?? null;
        DFGValidateAdvantageSpend($state, $seat, $main, $spend, $extra);
        $state['commitments'][$seat] = ['main' => $main, 'bonuses' => $bonuses];
        if ($spend) $state['commitments'][$seat]['advantages'] = $spend;
        if ($extra !== null) $state['commitments'][$seat]['extraSwitch'] = $extra;
        DFGLog($state, ($seat === 0 ? 'You commit face down.' : 'Bot commits face down.'));
        if ($state['commitments'][0] !== null && $state['commitments'][1] !== null) DFGReveal($state);
    } elseif ($type === 'pass' || $type === 'react') {
        if ($state['phase'] !== 'reactions' || $state['reactionTurn'] !== $seat) throw new LogicException('It is not your reaction turn');
        if ($type === 'pass') {
            ++$state['passes']; DFGLog($state, ($seat === 0 ? 'You pass.' : 'Bot passes.'));
        } else {
            $id = $action['card'] ?? null;
            if (!is_int($id) || !in_array($id, DFGReactionOptions($state, $seat), true)) throw new InvalidArgumentException('Choose an available reaction card from your active hand');
            $state['players'][$seat]['discarded'][] = $id;
            $state['reactions'][] = ['seat' => $seat, 'card' => $id];
            $state['passes'] = 0;
            DFGLog($state, ($seat === 0 ? 'You burn ' : 'Bot burns ') . DFGCatalog()[$id]['name'] . ' as a reaction.');
        }
        if ($state['passes'] === 2) DFGResolve($state);
        else $state['reactionTurn'] = 1 - $seat;
    } elseif ($type === 'switch') {
        if ($state['phase'] !== 'switching' || $state['switches'][$seat] !== null) throw new LogicException('You cannot choose a hand now');
        $color = $action['color'] ?? null;
        if (!is_string($color) || !in_array($color, DFGSwitchOptions($state, $seat), true)) throw new InvalidArgumentException('Choose one of your main card’s listed hand colors');
        $state['switches'][$seat] = $color;
        if ($state['switches'][0] !== null && $state['switches'][1] !== null) {
            foreach ([0, 1] as $player) $state['players'][$player]['color'] = $state['switches'][$player];
            ++$state['round'];
            $state['phase'] = 'planning'; $state['commitments'] = [null, null]; $state['switches'] = [null, null]; $state['reactions'] = []; $state['passes'] = 0;
            DFGLog($state, 'Next hands: ' . $state['players'][0]['color'] . ' / ' . $state['players'][1]['color'] . '. Normal cards are back in hand.');
        }
    } else throw new InvalidArgumentException('Unknown action');
    ++$state['revision'];
    return $state;
}

function DFGObserve(array $state, int $seat): array {
    $view = $state;
    unset($view['seed']);
    foreach ([0, 1] as $player) {
        $view['players'][$player]['available'] = DFGAvailable($state['players'][$player]);
        if ($state['phase'] === 'planning' && $player !== $seat && $state['commitments'][$player] !== null) $view['commitments'][$player] = ['hidden' => true];
        if ($state['phase'] === 'switching' && $player !== $seat && $state['switches'][$player] !== null) $view['switches'][$player] = 'hidden';
    }
    $view['seat'] = $seat;
    $view['stats'] = in_array($state['phase'], ['reactions', 'switching', 'finished'], true) && $state['commitments'][0] !== null && $state['commitments'][1] !== null ? DFGComputeRound($state['commitments'], $state['reactions']) : null;
    $view['reactionOptions'] = DFGReactionOptions($state, $seat);
    $view['switchOptions'] = $state['phase'] === 'switching' ? DFGSwitchOptions($state, $seat) : [];
    return $view;
}
