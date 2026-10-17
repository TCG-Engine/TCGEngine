<?php
declare(strict_types=1);
require_once __DIR__ . '/CardCatalog.php';
const DFG_RULES_VERSION = 43;

function DFGNewGame(string $humanColor = 'Red', string $botColor = 'Purple', int $seed = 1, int $first = 0, string $difficulty = 'tactical'): array {
    if (!in_array($humanColor, DFG_COLORS, true) || !in_array($botColor, DFG_COLORS, true) || !in_array($first, [0, 1], true)
        || !in_array($difficulty, ['casual', 'tactical', 'pro'], true) || $seed < 1 || $seed > 2147483647) throw new InvalidArgumentException('Invalid match settings');
    DFGCatalog();
    return ['rulesVersion' => DFG_RULES_VERSION, 'revision' => 0, 'round' => 1, 'phase' => 'planning', 'seed' => $seed, 'initialSeed' => $seed, 'first' => $first, 'difficulty' => $difficulty,
        'players' => [['life' => 40, 'color' => $humanColor, 'discarded' => [], 'advantages' => []], ['life' => 40, 'color' => $botColor, 'discarded' => [], 'advantages' => []]],
        'commitments' => [null, null], 'switches' => [null, null], 'routeCuts' => [null, null], 'forcedColors' => [null, null], 'reactions' => [], 'reactionTurn' => $first, 'passes' => 0,
        'winner' => null, 'history' => [], 'log' => [['round' => 1, 'text' => 'Fight! Choose a main card and optional bonus burns.']]];
}

function DFGCardHand(array $player, int $id): string {
    return $player['cardHands'][$id] ?? DFGCatalog()[$id]['color'];
}

function DFGAvailable(array $player, ?string $color = null): array {
    return array_values(array_filter(array_keys(DFGCatalog()), fn($id) => !in_array($id, $player['discarded'], true) && ($color === null || DFGCardHand($player, $id) === $color)));
}

// Inspect only the committed bonus's resolved changes for its owner's block.
// Opponent gifts and reaction burns do not count as defensive bonus commitments.
function DFGCommittedBlockBonus(int $seat, array $plans, array $cards): bool {
    foreach ($plans[$seat]['bonuses'] as $id) {
        $effect=$cards[$id]['effects']['bonus'];
        $branch=DFGEffectChanges($effect,$seat,$plans,$cards);
        foreach($branch as $change) if(!$change['opponent'] && $change['stat']==='block' && $change['amount']>0) return true;
    }
    return false;
}
function DFGConditionMatches(?array $condition, int $seat, array $plans, array $cards): bool {
    if ($condition === null) return true;
    $other = 1 - $seat;
    if ($condition['type'] === 'block_bonus') return DFGCommittedBlockBonus($other,$plans,$cards);
    if ($condition['type'] === 'opposing_momentum') return ($plans[$other]['momentum'] ?? 0) > 0;
    if ($condition['type'] === 'opposing_advantage_spend') return !empty($plans[$other]['advantages']);
    if ($condition['type'] === 'burns') {
        $count = count($plans[$condition['opponent'] ? $other : $seat]['bonuses']);
        return $condition['exact'] ? $count === $condition['minimum'] : $count >= $condition['minimum'];
    }
    $card = $cards[$plans[$other]['main']];
    if ($condition['type'] === 'equal') return $card['attack'] === $card['block'];
    return match ($condition['comparison']) {
        'more' => $card[$condition['stat']] >= $condition['value'],
        'less' => $card[$condition['stat']] <= $condition['value'],
        'exact' => $card[$condition['stat']] === $condition['value']
    };
}

function DFGEffectChanges(array $effect, int $seat, array $plans, array $cards): array {
    if (isset($effect['parts'])) return array_merge(...array_map(fn($part)=>DFGEffectChanges($part,$seat,$plans,$cards), $effect['parts']));
    return DFGConditionMatches($effect['condition'],$seat,$plans,$cards) ? $effect['yes'] : $effect['no'];
}

function DFGBurnLocked(array $state, int $seat): bool {
    return ($state['players'][$seat]['burnLockedRound'] ?? 0) === $state['round'];
}

function DFGPlanSpecials(array $plan): array {
    $card = DFGCatalog()[$plan['main']];
    return array_merge($card['special'], empty($plan['advantages']) || isset($plan['cancelledFrom']) ? [] : ($card['advantage']['spend']['special'] ?? []));
}

// Evaluate triggers against final simultaneous damage, before recovery or objective awards.
function DFGTriggeredSpecials(array $plans, array $stats, int $seat): array {
    return array_values(array_filter(DFGPlanSpecials($plans[$seat]), fn($effect) => match ($effect) {
        'hit_switch', 'hit_heal', 'hit_momentum', 'hit_momentum_one', 'hit_cut_routes', 'hit_deny_earn', 'hit_burn_enemy_main', 'hit_force_color', 'hit_strip', 'hit_burn_lock' => $stats[$seat]['damage'] > 0,
        'guard_strip', 'guard_momentum_one' => $stats[1-$seat]['damage'] === 0,
        'miss_recovery' => $stats[$seat]['combatDamage'] === 0,
        'glancing_momentum' => $stats[1-$seat]['damage'] === 1,
        'bonus_relocate' => count($plans[1-$seat]['bonuses']) > 0,
        'guard_retaliation' => $stats[1-$seat]['combatDamage'] === 0,
        'safe_switch' => $stats[1-$seat]['damage'] === 0,
        'clean_force_move' => $stats[$seat]['damage'] > 0 && $stats[1-$seat]['damage'] === 0,
        default => true
    }));
}

// Pressure lasts one round; multiple qualifying sources use the largest amount.
function DFGNextAttackFromSpecials(array $specials): int {
    if (array_intersect(['hit_momentum','glancing_momentum'], $specials)) return 2;
    return array_intersect(['hit_momentum_one', 'guard_momentum_one'], $specials) ? 1 : 0;
}

// Printed conditions never inspect modified stats or future reactions.
function DFGComputeRound(array $plans, array $reactions = []): array {
    $cards = DFGCatalog();
    $stats = [];
    foreach ([0, 1] as $seat) {
        $main = $cards[$plans[$seat]['main']];
        $reset = in_array('brace_reset', DFGPlanSpecials($plans[1-$seat]), true);
        $stats[$seat] = ['attack' => $main['attack'] + ($reset ? 0 : ($plans[$seat]['momentum'] ?? 0)), 'block' => $main['block'], 'pierce' => 0];
    }
    $effects = [];
    foreach ([0, 1] as $seat) {
        $effects[] = [$seat, $cards[$plans[$seat]['main']]['effects']['ability']];
        foreach ($plans[$seat]['bonuses'] as $id) $effects[] = [$seat, $cards[$id]['effects']['bonus']];
    }
    foreach ($reactions as $reaction) $effects[] = [$reaction['seat'], $cards[$reaction['card']]['effects']['reaction']];
    foreach ($effects as [$seat, $effect]) {
        $changes = DFGEffectChanges($effect, $seat, $plans, $cards);
        foreach ($changes as $change) $stats[$change['opponent'] ? 1 - $seat : $seat][$change['stat']] += $change['amount'];
    }
    foreach ([0, 1] as $seat) {
        foreach (DFGAdvantageChanges($plans[$seat]) as $change) $stats[$seat][$change['stat']] += $change['amount'];
        if (!$plans[$seat]['bonuses'] && in_array('conserve_reaction_pierce', DFGPlanSpecials($plans[$seat]), true)) {
            $reactionBlock = 0;
            foreach ($reactions as $reaction) {
                $changes = DFGEffectChanges($cards[$reaction['card']]['effects']['reaction'], $reaction['seat'], $plans, $cards);
                foreach ($changes as $change) {
                    $recipient = $change['opponent'] ? 1 - $reaction['seat'] : $reaction['seat'];
                    if ($recipient === 1 - $seat && $change['stat'] === 'block') $reactionBlock += $change['amount'];
                }
            }
            $stats[$seat]['pierce'] += max(0, $reactionBlock);
        }
        if (in_array('bonus_pierce', DFGPlanSpecials($plans[$seat]), true)) {
            foreach ($plans[1-$seat]['bonuses'] as $id) {
                $effect = $cards[$id]['effects']['bonus'];
                $changes = DFGEffectChanges($effect, 1-$seat, $plans, $cards);
                foreach ($changes as $change) if (!$change['opponent'] && $change['stat'] === 'block') $stats[$seat]['pierce'] += $change['amount'];
            }
        }
    }
    foreach ([0, 1] as $seat) $stats[$seat]['block'] = max(0, $stats[$seat]['block'] - ($plans[$seat]['blockPenalty'] ?? 0));
    foreach ([0, 1] as $seat) {
        $stats[$seat]['effectiveOpponentBlock'] = max(0, $stats[1 - $seat]['block'] - $stats[$seat]['pierce']);
        $stats[$seat]['damage'] = max(0, $stats[$seat]['attack'] - $stats[$seat]['effectiveOpponentBlock']);
        $stats[$seat]['combatDamage'] = $stats[$seat]['damage'];
        $stats[$seat]['directDamage'] = 0;
    }
    // Retaliations check the same completed combat exchange, then resolve simultaneously.
    // Direct damage bypasses block and cannot recursively trigger another retaliation.
    foreach ([0, 1] as $seat) {
        if (in_array('guard_retaliation', DFGPlanSpecials($plans[$seat]), true) && $stats[1-$seat]['combatDamage'] === 0) {
            $stats[$seat]['directDamage'] = 2;
            $stats[$seat]['damage'] += 2;
        }
    }
    return $stats;
}

function DFGAdvantageChanges(array $plan): array {
    if (isset($plan['cancelledFrom'])) return [];
    $spend = $plan['advantages'] ?? [];
    if (!$spend) return [];
    $rule = DFGCatalog()[$plan['main']]['advantage']['spend'];
    if (!$rule) throw new LogicException('Main card cannot spend advantage');
    if (isset($rule['byCount'])) return $rule['byCount'][count($spend)] ?? [];
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
        'damage_next_round' => false,
        'take_one' => $stats[1-$seat]['damage'] === 1,
        'punish_block_bonus' => $stats[$seat]['damage'] > 0 && DFGCommittedBlockBonus(1-$seat,$seat===0?[$plan,$opponent]:[$opponent,$plan],DFGCatalog()),
        'break_guard_or_momentum' => ($stats[$seat]['damage'] > 0 && DFGCommittedBlockBonus(1-$seat,$seat===0?[$plan,$opponent]:[$opponent,$plan],DFGCatalog()))
            || ($stats[1-$seat]['damage'] === 0 && ($opponent['momentum'] ?? 0) > 0),
        'damage_vs_block' => $stats[$seat]['damage'] > 0 && $enemy['block'] >= 4,
        'guard_high' => $stats[1-$seat]['damage'] === 0 && $enemy['attack'] >= 5,
        'punish_bonus' => $stats[$seat]['damage'] > 0 && count($opponent['bonuses']) > 0,
        'heavy_unassisted' => $stats[$seat]['damage'] >= 4 && !$plan['bonuses'],
        'clean_counter' => $stats[$seat]['damage'] > 0 && $stats[1-$seat]['damage'] === 0,
        'deal_damage' => $stats[$seat]['damage'] > 0,
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
    $yellow = $rule && $rule['flexible'] && !isset($rule['byCount']) && in_array('Yellow', $spend, true);
    if ($yellow) {
        if (!is_string($extra) || $extra === $state['players'][$seat]['color'] || !in_array($extra, DFG_COLORS, true) || in_array($extra, DFGCatalog()[$main]['switches'], true) || !DFGAvailable($state['players'][$seat], $extra)) throw new InvalidArgumentException('Yellow advantage needs one additional available switch color different from your active hand');
    } elseif ($extra !== null) throw new InvalidArgumentException('An extra switch color requires spending Yellow on Combination Strike');
}

// Shared legal choices for bots and tests; tokens can be spent only through the main card.
function DFGSpendVariants(array $player, int $main): array {
    $carry = empty($player['nextAttack']) ? [] : ['momentum' => $player['nextAttack']];
    if (!empty($player['nextBlockPenalty'])) $carry['blockPenalty'] = $player['nextBlockPenalty'];
    $rule = DFGCatalog()[$main]['advantage']['spend']; $variants = [$carry];
    if (!$rule) return $variants;
    $colors = array_values(array_intersect($rule['colors'], $player['advantages'] ?? []));
    $sets = array_map(fn($color) => [$color], $colors);
    if ($rule['flexible']) for ($a = 0; $a < count($colors); ++$a) for ($b = $a + 1; $b < count($colors); ++$b) $sets[] = [$colors[$a], $colors[$b]];
    foreach ($sets as $set) {
        if ($rule['flexible'] && !isset($rule['byCount']) && in_array('Yellow', $set, true)) {
            foreach (array_diff(DFG_COLORS, DFGCatalog()[$main]['switches'], [DFGCardHand($player, $main)]) as $color) if (DFGAvailable($player, $color)) $variants[] = ['advantages' => $set, 'extraSwitch' => $color] + $carry;
        } else $variants[] = ['advantages' => $set] + $carry;
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
    $state['cancelChoices'] = [null,null];
    foreach ([0,1] as $seat) if (!DFGCanCancel($state,$seat) || count(DFGCancelCandidates($state['players'][$seat],$state['commitments'][$seat]))<2) $state['cancelChoices'][$seat]=$state['commitments'][$seat]['main'];
    if (in_array(null,$state['cancelChoices'],true)) $state['phase']='cancelling';
}

function DFGCanCancel(array $state,int $seat): bool {
    return $state['commitments'][$seat]!==null && !DFGBurnLocked($state,$seat)
        && in_array('cancel_main',DFGPlanSpecials($state['commitments'][$seat]),true);
}
function DFGCancelCandidates(array $player,array $plan): array {
    return array_values(array_unique(array_merge([$plan['main']], array_diff(DFGAvailable($player,DFGCatalog()[$plan['main']]['color']),$plan['bonuses']))));
}
function DFGCancelOptions(array $state,int $seat): array {
    return $state['phase']==='cancelling' && ($state['cancelChoices'][$seat]??null)===null && DFGCanCancel($state,$seat)
        ? DFGCancelCandidates($state['players'][$seat],$state['commitments'][$seat]) : [];
}
function DFGCancelledPlan(array $plan,int $main): array {
    if ($main!==$plan['main']) { $plan['cancelledFrom']=$plan['main'];$plan['main']=$main; }
    return $plan;
}

function DFGResolve(array &$state): void {
    $stats = DFGComputeRound($state['commitments'], $state['reactions']);
    foreach ([0, 1] as $seat) $state['players'][$seat]['life'] = max(0, $state['players'][$seat]['life'] - $stats[1 - $seat]['damage']);
    DFGLog($state, 'Damage resolves together: you deal ' . $stats[0]['damage'] . ', bot deals ' . $stats[1]['damage'] . '. Life: ' . $state['players'][0]['life'] . ' / ' . $state['players'][1]['life'] . '.');
    foreach ([0,1] as $seat) if ($stats[$seat]['directDamage']) DFGLog($state, ($seat === 0 ? 'Your ' : 'Bot’s ') . 'Guarded Strike deals 2 direct damage after taking no combat damage.');
    $specials = [DFGTriggeredSpecials($state['commitments'], $stats, 0), DFGTriggeredSpecials($state['commitments'], $stats, 1)];
    // Determine both knockouts before recovery; no card can revive a knocked-out fighter.
    $knockedOut = [$state['players'][0]['life'] <= 0, $state['players'][1]['life'] <= 0];
    $reset = in_array('brace_reset', array_merge(...$specials), true);
    foreach ([0, 1] as $seat) {
        $state['players'][$seat]['nextAttack'] = $reset ? 0 : DFGNextAttackFromSpecials($specials[$seat]);
        $state['players'][$seat]['nextBlockPenalty'] = in_array('miss_recovery',$specials[$seat],true) ? 2 : 0;
        if ($state['players'][$seat]['nextBlockPenalty']) DFGLog($state, ($seat===0?'You have':'Bot has').' -2 block next round after Haymaker misses (minimum 0).');
        if (in_array('hit_burn_lock',$specials[$seat],true)) {
            $state['players'][1-$seat]['burnLockedRound'] = $state['round']+1;
            DFGLog($state, ($seat===0?'Your':'Bot’s').' Clinch Throw prevents opposing bonus and reaction burns next round.');
        }
        if (in_array('hit_heal', $specials[$seat], true) && !$knockedOut[$seat]) {
            $state['players'][$seat]['life'] = min(40, $state['players'][$seat]['life'] + 1);
            DFGLog($state, ($seat === 0 ? 'You recover' : 'Bot recovers') . ' 1 life from Driving Palm (maximum 40).');
        }
        if (in_array('hit_burn_enemy_main', $specials[$seat], true)) {
            $victim = 1-$seat;
            $id = $state['commitments'][$victim]['main'];
            if (!in_array($id, $state['players'][$victim]['discarded'], true)) $state['players'][$victim]['discarded'][] = $id;
            DFGLog($state, ($seat === 0 ? 'You burn the opposing ' : 'Bot burns your ') . DFGCatalog()[$id]['name'] . ' after Charged Fist hits.');
        }
        if ($state['players'][$seat]['nextAttack']) DFGLog($state, ($seat === 0 ? 'You gain' : 'Bot gains') . ' +' . $state['players'][$seat]['nextAttack'] . ' attack next round from ' . DFGCatalog()[$state['commitments'][$seat]['main']]['name'] . '.');
        if (in_array('hit_deny_earn', $specials[$seat], true)) DFGLog($state, ($seat === 0 ? 'You prevent' : 'Bot prevents') . ' the opponent from earning advantage this round.');
    }
    if ($reset) DFGLog($state, 'Brace prevents both fighters from gaining attack bonuses for next round.');
    $state['stripsRemaining'] = array_map(fn($effects)=>count(array_intersect(['hit_strip','guard_strip'],$effects)), $specials);
    DFGUpdateStripPhase($state);
}

// Remove only tokens held after reveal, before this exchange awards new objectives.
function DFGStripOptions(array $state, int $seat): array {
    return $state['phase']==='stripping' && ($state['stripsRemaining'][$seat]??0)>0 ? $state['players'][1-$seat]['advantages'] : [];
}
function DFGUpdateStripPhase(array &$state): void {
    $state['phase']='stripping';
    if (!DFGStripOptions($state,0) && !DFGStripOptions($state,1)) DFGFinishResolution($state);
}
function DFGFinishResolution(array &$state): void {
    $stats=DFGComputeRound($state['commitments'],$state['reactions']);
    $specials=[DFGTriggeredSpecials($state['commitments'],$stats,0),DFGTriggeredSpecials($state['commitments'],$stats,1)];
    foreach ([0, 1] as $seat) {
        $objective = DFGCatalog()[$state['commitments'][$seat]['main']]['advantage']['earn'];
        $pending = $state['players'][$seat]['pendingObjective'] ?? null;
        $awards = [];
        if ($pending && $pending['round'] === $state['round'] && $stats[$seat]['damage'] >= ($pending['minimumDamage'] ?? 4)) $awards[] = $pending['color'];
        if (DFGObjectiveAchieved($state['commitments'][$seat], $state['commitments'][1-$seat], $stats, $seat, $state['reactions'])) $awards[] = $objective['color'];
        // Previous setup expires after this round, even on a miss or a denied award.
        $state['players'][$seat]['pendingObjective'] = $objective && $objective['condition'] === 'damage_next_round'
            ? ['round'=>$state['round']+1, 'color'=>$objective['color'], 'source'=>$state['commitments'][$seat]['main']] + $objective['delayed'] : null;
        if ($next = $state['players'][$seat]['pendingObjective']) DFGLog($state, ($seat === 0 ? 'Your ' : 'Bot’s ') . DFGCatalog()[$next['source']]['name'] . ' objective: ' . $next['requirement'] . ' next round to earn ' . $next['color'] . ' advantage.');
        foreach (array_unique($awards) as $color) {
            if (in_array('hit_deny_earn', $specials[1-$seat], true)) continue;
            if (!in_array($color, $state['players'][$seat]['advantages'], true)) {
                $state['players'][$seat]['advantages'][] = $color;
                DFGLog($state, ($seat === 0 ? 'You earn ' : 'Bot earns ') . $color . ' advantage: objective achieved.');
            }
        }
    }
    $state['history'][] = ['round' => $state['round'], 'main' => array_column($state['commitments'], 'main'), 'stats' => $stats];
    $state['history'] = array_slice($state['history'], -100);
    if (!DFGCheckDefeat($state)) {
        $state['phase'] = 'switching';
        $state['switches'] = [null, null];
        $state['routeCuts'] = [null, null];
        $state['forcedColors'] = [null, null];
        $state['relocations'] = [null, null];
        $state['phase'] = 'relocating';
        foreach ([0, 1] as $seat) if (!DFGRelocateOptions($state, $seat)) $state['relocations'][$seat] = 'keep';
        DFGFinishRelocations($state);
    }
}

// Move surviving opposing mains before route cuts and destination choices.
function DFGRelocateOptions(array $state, int $seat): array {
    if ($state['phase'] !== 'relocating' || ($state['relocations'][$seat] ?? null) !== null) return [];
    $victim = 1 - $seat;
    $id = $state['commitments'][$victim]['main'];
    if (in_array($id, $state['players'][$victim]['discarded'], true)
        || !in_array('bonus_relocate', DFGPlanSpecials($state['commitments'][$seat]), true)
        || !$state['commitments'][$victim]['bonuses']) return [];
    return array_values(array_diff(DFG_COLORS, [DFGCardHand($state['players'][$victim], $id)]));
}

function DFGFinishRelocations(array &$state): void {
    if (in_array(null, $state['relocations'], true)) return;
    foreach ([0, 1] as $seat) {
        $color = $state['relocations'][$seat];
        if ($color === 'keep') continue;
        $victim = 1 - $seat;
        $id = $state['commitments'][$victim]['main'];
        $state['players'][$victim]['cardHands'][$id] = $color;
        DFGLog($state, ($seat === 0 ? 'You move the opposing ' : 'Bot moves your ') . DFGCatalog()[$id]['name'] . ' into the ' . $color . ' hand with Bait and Slip.');
    }
    DFGUpdateDestinationPhase($state);
}

function DFGCheckDefeat(array &$state, bool $checkActiveHand = false): bool {
    $dead = array_map(fn($player) => $player['life'] <= 0 || !DFGAvailable($player)
        || ($checkActiveHand && !DFGAvailable($player, $player['color'])), $state['players']);
    if (!$dead[0] && !$dead[1]) return false;
    foreach ([0, 1] as $seat) if ($checkActiveHand && !DFGAvailable($state['players'][$seat], $state['players'][$seat]['color']))
        DFGLog($state, ($seat === 0 ? 'You enter ' : 'Bot enters ') . $state['players'][$seat]['color'] . ' with no cards and lose.');
    $state['phase'] = 'finished';
    $state['winner'] = $dead[0] && $dead[1] ? 'draw' : ($dead[0] ? 1 : 0);
    DFGLog($state, $state['winner'] === 'draw' ? 'Both fighters are defeated — draw.' : ($state['winner'] === 0 ? 'You win!' : 'Bot wins.'));
    return true;
}

function DFGSwitchOptions(array $state, int $seat, bool $applyCut = true, bool $applyForce = true): array {
    if ($state['commitments'][$seat] === null) return [];
    $colors = DFGCatalog()[$state['commitments'][$seat]['main']]['switches'];
    if (isset($state['commitments'][$seat]['extraSwitch'])) $colors[] = $state['commitments'][$seat]['extraSwitch'];
    $stats = DFGComputeRound($state['commitments'], $state['reactions']);
    $own = DFGTriggeredSpecials($state['commitments'], $stats, $seat);
    $enemy = DFGTriggeredSpecials($state['commitments'], $stats, 1-$seat);
    if (array_intersect(['safe_switch', 'hit_switch', 'free_switch'], $own)) $colors = array_merge($colors, array_filter(array_diff(DFG_COLORS, [$state['players'][$seat]['color']]), fn($color) => DFGAvailable($state['players'][$seat], $color)));
    if (in_array('stay_green', $own, true)) $colors[] = 'Green';
    // Printed destinations remain legal when empty; entering one loses the fight.
    $colors = array_values(array_unique($colors));
    if (in_array('clean_force_move', $enemy, true)) {
        $restricted = array_values(array_diff($colors, [$state['players'][$seat]['color']]));
        if ($restricted) $colors = $restricted;
    }
    $cut = $state['routeCuts'][1-$seat] ?? null;
    if ($applyCut && $cut !== null && count($colors) > 1) $colors = array_values(array_diff($colors, [$cut]));
    $forced = $state['forcedColors'][$seat] ?? null;
    if ($applyForce && $forced !== null && in_array($forced,$colors,true)) return [$forced];
    return $colors;
}

function DFGUpdateDestinationPhase(array &$state): void {
    $state['phase']='switching';
    if (DFGCutOptions($state,0) || DFGCutOptions($state,1)) $state['phase']='routing';
    elseif (DFGForceOptions($state,0) || DFGForceOptions($state,1)) $state['phase']='directing';
}

// Forced destinations are public choices after every cut, before either hidden hand choice.
function DFGForceOptions(array $state,int $seat): array {
    if (!in_array($state['phase'],['directing','switching'],true) || ($state['forcedColors'][1-$seat]??null)!==null) return [];
    $stats=DFGComputeRound($state['commitments'],$state['reactions']);
    if (!in_array('hit_force_color',DFGTriggeredSpecials($state['commitments'],$stats,$seat),true)) return [];
    $routes=DFGSwitchOptions($state,1-$seat,true,false);
    return count($routes)>1?$routes:[];
}

// Choose after damage and all automatic effects, before either player selects a next hand.
function DFGCutOptions(array $state, int $seat): array {
    if (!in_array($state['phase'], ['routing','switching'], true) || ($state['routeCuts'][$seat] ?? null) !== null) return [];
    $stats = DFGComputeRound($state['commitments'], $state['reactions']);
    if (!in_array('hit_cut_routes', DFGTriggeredSpecials($state['commitments'], $stats, $seat), true)) return [];
    $routes = DFGSwitchOptions($state, 1-$seat, false);
    return count($routes) > 1 ? $routes : [];
}

function DFGReactionOptions(array $state, int $seat): array {
    if ($state['phase'] !== 'reactions' || DFGBurnLocked($state,$seat)) return [];
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
        if ($bonuses && DFGBurnLocked($state,$seat)) throw new InvalidArgumentException('Clinch Throw prevents bonus and reaction burns this round');
        if (count(array_unique($bonuses, SORT_REGULAR)) !== count($bonuses)) throw new InvalidArgumentException('A card can only be burned once');
        foreach ($bonuses as $id) if (!is_int($id) || $id === $main || !in_array($id, $available, true) || DFGCatalog()[$id]['bonus'] === '') throw new InvalidArgumentException('Bonus burns must be different available cards with bonus effects in your active hand');
        $spend = $action['advantages'] ?? []; $extra = $action['extraSwitch'] ?? null;
        DFGValidateAdvantageSpend($state, $seat, $main, $spend, $extra);
        $state['commitments'][$seat] = ['main' => $main, 'bonuses' => $bonuses];
        if (!empty($state['players'][$seat]['nextAttack'])) $state['commitments'][$seat]['momentum'] = $state['players'][$seat]['nextAttack'];
        if (!empty($state['players'][$seat]['nextBlockPenalty'])) $state['commitments'][$seat]['blockPenalty'] = $state['players'][$seat]['nextBlockPenalty'];
        if ($spend) $state['commitments'][$seat]['advantages'] = $spend;
        if ($extra !== null) $state['commitments'][$seat]['extraSwitch'] = $extra;
        DFGLog($state, ($seat === 0 ? 'You commit face down.' : 'Bot commits face down.'));
        if ($state['commitments'][0] !== null && $state['commitments'][1] !== null) DFGReveal($state);
    } elseif ($type === 'cancel') {
        $main=$action['main']??null;
        if (!is_int($main) || !in_array($main,DFGCancelOptions($state,$seat),true)) throw new InvalidArgumentException('Keep Darting Strike or choose another available Yellow main');
        $state['cancelChoices'][$seat]=$main;
        // Both replacement decisions remain hidden until every eligible player has chosen.
        if (!in_array(null,$state['cancelChoices'],true)) {
            foreach ([0,1] as $actor) {
                $original=$state['commitments'][$actor]['main'];$replacement=$state['cancelChoices'][$actor];
                if ($replacement!==$original) {
                    $state['players'][$actor]['discarded'][]=$original;
                    $state['commitments'][$actor]=DFGCancelledPlan($state['commitments'][$actor],$replacement);
                    DFGLog($state,($actor===0?'You cancel ':'Bot cancels ').DFGCatalog()[$original]['name'].' into '.DFGCatalog()[$replacement]['name'].', permanently burning the original main.');
                } elseif (in_array('cancel_main',DFGPlanSpecials($state['commitments'][$actor]),true)) DFGLog($state,($actor===0?'You keep ':'Bot keeps ').DFGCatalog()[$original]['name'].'; Yellow advantage remains spent.');
            }
            $state['phase']='reactions';
        }
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
    } elseif ($type === 'strip') {
        $color=$action['color']??null;
        if (!is_string($color) || !in_array($color,DFGStripOptions($state,$seat),true)) throw new InvalidArgumentException('Choose an opposing held advantage to remove');
        $state['players'][1-$seat]['advantages']=array_values(array_diff($state['players'][1-$seat]['advantages'],[$color]));
        --$state['stripsRemaining'][$seat];
        DFGLog($state, ($seat===0?'You remove ':'Bot removes ').$color.' opposing advantage with Mirror Step.');
        DFGUpdateStripPhase($state);
    } elseif ($type === 'relocate') {
        $color = $action['color'] ?? null;
        $options = DFGRelocateOptions($state, $seat);
        if (!$options || !is_string($color) || ($color !== 'keep' && !in_array($color, $options, true))) throw new InvalidArgumentException('Choose a different hand for the opposing main, or keep its current hand');
        $state['relocations'][$seat] = $color;
        DFGFinishRelocations($state);
    } elseif ($type === 'cut') {
        $color = $action['color'] ?? null;
        if ($state['phase'] !== 'routing' || !is_string($color) || !in_array($color, DFGCutOptions($state, $seat), true)) throw new InvalidArgumentException('Choose one available opposing next color to prohibit');
        $state['routeCuts'][$seat] = $color;
        DFGLog($state, ($seat === 0 ? 'You cut off ' : 'Bot cuts off ') . $color . ' as the opponent\'s next hand.');
        DFGUpdateDestinationPhase($state);
    } elseif ($type === 'direct') {
        $color=$action['color']??null;
        if ($state['phase']!=='directing' || !is_string($color) || !in_array($color,DFGForceOptions($state,$seat),true)) throw new InvalidArgumentException('Choose an opposing legal destination after route cuts');
        $state['forcedColors'][1-$seat]=$color;
        DFGLog($state,($seat===0?'You force the opponent into ':'Bot forces you into ').$color.'.');
        DFGUpdateDestinationPhase($state);
    } elseif ($type === 'switch') {
        if ($state['phase'] !== 'switching' || $state['switches'][$seat] !== null) throw new LogicException('You cannot choose a hand now');
        $color = $action['color'] ?? null;
        if (!is_string($color) || !in_array($color, DFGSwitchOptions($state, $seat), true)) throw new InvalidArgumentException('Choose one of your main card’s listed hand colors');
        $state['switches'][$seat] = $color;
        if ($state['switches'][0] !== null && $state['switches'][1] !== null) {
            foreach ([0, 1] as $player) $state['players'][$player]['color'] = $state['switches'][$player];
            if (DFGCheckDefeat($state, true)) { ++$state['revision']; return $state; }
            ++$state['round'];
            $state['phase'] = 'planning'; $state['commitments'] = [null, null]; $state['switches'] = [null, null]; $state['routeCuts'] = [null, null]; $state['forcedColors'] = [null, null]; $state['reactions'] = []; $state['passes'] = 0;
            DFGLog($state, 'Next hands: ' . $state['players'][0]['color'] . ' / ' . $state['players'][1]['color'] . '. Normal cards are back in hand.');
        }
    } else throw new InvalidArgumentException('Unknown action');
    ++$state['revision'];
    return $state;
}

function DFGObserve(array $state, int $seat): array {
    $view = $state;
    unset($view['seed']);
    if ($state['phase']==='cancelling' && ($state['cancelChoices'][1-$seat]??null)!==null) $view['cancelChoices'][1-$seat]='hidden';
    foreach ([0, 1] as $player) {
        $view['players'][$player]['available'] = DFGAvailable($state['players'][$player]);
        if ($state['phase'] === 'planning' && $player !== $seat && $state['commitments'][$player] !== null) $view['commitments'][$player] = ['hidden' => true];
        if ($state['phase'] === 'switching' && $player !== $seat && $state['switches'][$player] !== null) $view['switches'][$player] = 'hidden';
    }
    $view['seat'] = $seat;
    $view['stats'] = in_array($state['phase'], ['cancelling', 'reactions', 'stripping', 'relocating', 'routing', 'directing', 'switching', 'finished'], true) && $state['commitments'][0] !== null && $state['commitments'][1] !== null ? DFGComputeRound($state['commitments'], $state['reactions']) : null;
    $view['relocateOptions'] = DFGRelocateOptions($state, $seat);
    $view['cancelOptions'] = DFGCancelOptions($state,$seat);
    $view['stripOptions'] = DFGStripOptions($state,$seat);
    $view['reactionOptions'] = DFGReactionOptions($state, $seat);
    $view['switchOptions'] = $state['phase'] === 'switching' ? DFGSwitchOptions($state, $seat) : [];
    $view['cutOptions'] = $state['phase'] === 'routing' ? DFGCutOptions($state, $seat) : [];
    $view['forceOptions'] = $state['phase'] === 'directing' ? DFGForceOptions($state,$seat) : [];
    return $view;
}
