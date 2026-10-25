<?php
declare(strict_types=1);

const DFG_COLORS = ['Red', 'Orange', 'Yellow', 'Green', 'Blue', 'Purple'];
const DFG_STYLES = ['Red' => 'Pressure', 'Orange' => 'Grappling', 'Yellow' => 'Footwork', 'Green' => 'Defense', 'Blue' => 'Counterplay', 'Purple' => 'Power'];

function DFGCompileEffect(string $text): array {
    $text = trim($text);
    $parts = preg_split('/(?<=\.)\s+(?=If )/', $text);
    if (count($parts) > 1) return ['parts' => array_map('DFGCompileEffect', $parts)];
    if ($text === '') return ['condition' => null, 'yes' => [], 'no' => []];
    $condition = null;
    if (str_starts_with($text, 'If ')) {
        [$clause, $text] = explode(', ', $text, 2);
        if ($clause === 'If your opponent committed a block-increasing bonus burn this round') {
            $condition = ['type'=>'block_bonus'];
        } elseif ($clause === 'If your opponent has an attack bonus carried into this round') {
            $condition = ['type'=>'opposing_momentum'];
        } elseif ($clause === 'If your opponent spent advantage this round') {
            $condition = ['type'=>'opposing_advantage_spend'];
        } elseif (preg_match('/^If (you|your opponent) committed (no|at least one|at least two) bonus burns? this round$/D', $clause, $m)) {
            $condition = ['type' => 'burns', 'opponent' => $m[1] !== 'you', 'minimum' => ['no' => 0, 'at least one' => 1, 'at least two' => 2][$m[2]], 'exact' => $m[2] === 'no'];
        } elseif (preg_match("/^If your opponent's main card has a printed (attack|block) value of (\d+) or (more|less)$/D", $clause, $m)) {
            $condition = ['type' => 'printed', 'stat' => $m[1], 'value' => (int)$m[2], 'comparison' => $m[3]];
        } elseif (preg_match("/^If your opponent's main card has a printed (attack|block) value of (\d+)$/D", $clause, $m)) {
            $condition = ['type'=>'printed', 'stat'=>$m[1], 'value'=>(int)$m[2], 'comparison'=>'exact'];
        } elseif ($clause === "If your opponent's main card has equal printed attack and printed block values") {
            $condition = ['type' => 'equal'];
        } else throw new RuntimeException('Unsupported condition: ' . $clause);
    }
    $branches = explode(' Otherwise, ', $text, 2);
    return ['condition' => $condition, 'yes' => DFGCompileChanges($branches[0]), 'no' => isset($branches[1]) ? DFGCompileChanges($branches[1]) : []];
}

function DFGCompileChanges(string $text): array {
    $changes = [];
    foreach (preg_split('/(?<=\.)\s+/', trim($text)) as $sentence) {
        if (preg_match('/^(?:Gain|gain) \+(\d+) attack and ignore (\d+) opposing block this round\.$/D', $sentence, $m)) {
            $changes[] = ['opponent'=>false, 'stat'=>'attack', 'amount'=>(int)$m[1]];
            $changes[] = ['opponent'=>false, 'stat'=>'pierce', 'amount'=>(int)$m[2]];
        } elseif (preg_match('/^(Gain|gain|Your opponent gains) \+(\d+) (attack|block)(?: and \+(\d+) (attack|block))? this round\.$/D', $sentence, $m)) {
            $changes[] = ['opponent' => $m[1] === 'Your opponent gains', 'stat' => $m[3], 'amount' => (int)$m[2]];
            if (isset($m[4]) && $m[4] !== '') $changes[] = ['opponent' => $m[1] === 'Your opponent gains', 'stat' => $m[5], 'amount' => (int)$m[4]];
        } elseif (preg_match("/^For your damage calculation this round, treat your opponent's block as (\d+) lower, to a minimum of 0\.$/D", $sentence, $m)) {
            $changes[] = ['opponent' => false, 'stat' => 'pierce', 'amount' => (int)$m[1]];
        } else throw new RuntimeException('Unsupported effect: ' . $sentence);
    }
    return $changes;
}

function DFGCompileAdvantage(string $text): array {
    $mechanic = ['earn' => null, 'spend' => null]; $combat = []; $special = [];
    $specialLines = [
        "If you committed no bonus burns this round, for your damage calculation ignore block gained from reaction burns this round." => 'conserve_reaction_pierce',
        "If your opponent committed at least one bonus burn this round, you may return their main card to a different hand of your choice after damage." => 'bonus_relocate',
        'If you deal no combat damage, you have -2 block next round, to a minimum of 0.' => 'miss_recovery',
        'If you take no combat damage, deal 2 damage to your opponent after combat damage.' => 'guard_retaliation',
        'If you deal damage, gain +1 attack next round.' => 'hit_momentum_one',
        'If you take no damage, gain +1 attack next round.' => 'guard_momentum_one',
        'If you take exactly 1 damage, gain +2 attack next round.' => 'glancing_momentum',
        'If you take no damage, you may switch to any other available color this round.' => 'safe_switch',
        'If you deal damage, you may switch to any other available color this round.' => 'hit_switch',
        'If you deal damage and take no damage, your opponent cannot stay in their current color this round if another destination is available.' => 'clean_force_move',
        'If you deal damage, recover 1 life after damage. This cannot prevent a knockout.' => 'hit_heal',
        "For your damage calculation this round, ignore block gained from your opponent's bonus burn." => 'bonus_pierce'
    ];
    $specialSpends = [
        'After reveal, before reactions, you may permanently burn this card and replace it with another available main from your Yellow hand. Keep your committed bonus. The replacement cannot spend advantage.' => ['changes'=>[], 'special'=>['cancel_main']],
        "Your opponent's carried attack bonus does not apply this round. Neither player can gain attack bonuses for next round." => ['changes'=>[], 'special'=>['brace_reset']],
        'If you deal damage, remove one opposing held advantage of your choice. If you take no damage, remove one opposing held advantage of your choice.' => ['changes'=>[], 'special'=>['hit_strip', 'guard_strip']],
        'If you deal damage, your opponent cannot burn bonus or reaction cards next round.' => ['changes'=>[], 'special'=>['hit_burn_lock']],
        "If you deal damage, choose your opponent's next hand from their legal destinations." => ['changes' => [], 'special' => ['hit_force_color']],
        'If you deal damage, gain +2 attack next round.' => ['changes' => [], 'special' => ['hit_momentum']],
        'Ignore 2 opposing block this round. If you deal damage, choose one opposing legal next color to prohibit if more than one is available.' => ['changes' => [['opponent'=>false,'stat'=>'pierce','amount'=>2]], 'special' => ['hit_cut_routes']],
        'Gain +2 block this round. You may switch to any other available color this round.' => ['changes' => [['opponent'=>false,'stat'=>'block','amount'=>2]], 'special' => ['free_switch']],
        'Gain +2 block this round. You may stay in Green this round.' => ['changes' => [['opponent'=>false,'stat'=>'block','amount'=>2]], 'special' => ['stay_green']],
        'Gain +1 attack and +1 block this round. If you deal damage, your opponent cannot earn advantage this round.' => ['changes' => [['opponent'=>false,'stat'=>'attack','amount'=>1],['opponent'=>false,'stat'=>'block','amount'=>1]], 'special' => ['hit_deny_earn']],
        "If you deal damage, permanently burn your opponent's main card after damage." => ['changes' => [], 'special' => ['hit_burn_enemy_main']]
    ];
    $objectives = [
        'Take exactly 1 damage' => 'take_one',
        'Deal damage against a block-increasing bonus burn' => 'punish_block_bonus',
        'Deal damage against a block-increasing bonus burn, or take no damage against an opponent with an attack bonus carried into this round' => 'break_guard_or_momentum',
        'Deal damage against printed block 4+' => 'damage_vs_block',
        'Take no damage against printed attack 5+' => 'guard_high',
        'Deal damage against a bonus burn' => 'punish_bonus',
        'Deal 4+ damage without a bonus burn' => 'heavy_unassisted',
        'Deal damage and take no damage' => 'clean_counter',
        'Deal damage' => 'deal_damage',
        'Deal damage against printed attack 3 or less' => 'punish_low_attack',
        'Take no damage against a bonus burn' => 'guard_bonus',
        'Deal damage without a bonus or reaction burn' => 'efficient_hit',
        'Deal damage against printed attack 5+' => 'punish_high_attack',
        'Deal damage against printed block 5+' => 'break_heavy_guard'
    ];
    foreach (explode("\n", trim($text)) as $line) {
        $line = trim($line); if ($line === '') continue;
        if (isset($specialLines[$line])) {
            $special[] = $specialLines[$line];
        } elseif (preg_match('/^Objective: (.+): gain (Red|Orange|Yellow|Green|Blue|Purple) advantage\.$/D', $line, $m)) {
            $delayed = preg_match('/^Deal ([1-9][0-9]*)\+ damage next round$/D', $m[1], $threshold);
            if ($mechanic['earn'] !== null || (!$delayed && !isset($objectives[$m[1]]))) throw new RuntimeException('Unsupported advantage objective');
            $mechanic['earn'] = ['color' => $m[2], 'condition' => $delayed ? 'damage_next_round' : $objectives[$m[1]]];
            if ($delayed) $mechanic['earn']['delayed'] = ['minimumDamage'=>(int)$threshold[1], 'requirement'=>'deal '.$threshold[1].'+ damage'];
        } elseif (preg_match('/^Spend (Red|Orange|Yellow|Green|Blue|Purple) advantage: (.+)$/D', $line, $m)) {
            if ($mechanic['spend'] !== null) throw new RuntimeException('Duplicate advantage spender');
            $mechanic['spend'] = ['colors' => [$m[1]], 'flexible' => false] + ($specialSpends[$m[2]] ?? ['changes' => DFGCompileChanges($m[2]), 'special' => []]);
        } elseif ($line === 'Spend up to two different advantages: One spent: +1 attack. Two spent instead: +4 attack, +2 block, and ignore 2 opposing block this round.') {
            if ($mechanic['spend'] !== null) throw new RuntimeException('Duplicate advantage spender');
            $mechanic['spend'] = ['colors'=>DFG_COLORS, 'flexible'=>true, 'changes'=>[], 'byCount'=>[
                1=>[['opponent'=>false,'stat'=>'attack','amount'=>1]],
                2=>[['opponent'=>false,'stat'=>'attack','amount'=>4],['opponent'=>false,'stat'=>'block','amount'=>2],['opponent'=>false,'stat'=>'pierce','amount'=>2]]
            ]];
        } elseif (in_array($line, ['Spend up to two different advantages: Red/Purple +1 attack; Green/Blue +1 block; Orange ignore 1 block; Yellow add one switch color. Two spent: +1 attack.', 'Spend up to two different advantages: Red/Purple +1 attack; Green/Blue +1 block; Orange ignore 1 block; Yellow add one different-hand switch color. Two spent: +1 attack.'], true)) {
            if ($mechanic['spend'] !== null) throw new RuntimeException('Duplicate advantage spender');
            $mechanic['spend'] = ['colors' => DFG_COLORS, 'flexible' => true, 'changes' => []];
        } else $combat[] = $line;
    }
    return ['text' => implode(' ', $combat), 'mechanic' => $mechanic, 'special' => $special];
}

function DFGCatalog(): array {
    // Standalone variants may supply a compiled catalog without changing the core set.
    if (function_exists('DFGCustomCatalog')) return DFGCustomCatalog();
    static $cards;
    if ($cards !== null) return $cards;
    $snapshot = json_decode(file_get_contents(__DIR__ . '/../data/cards.json'), true, 64, JSON_THROW_ON_ERROR);
    $cards = [];
    $counts = array_fill_keys(DFG_COLORS, 0);
    foreach ($snapshot['cards'] as $card) {
        if (!is_int($card['id']) || isset($cards[$card['id']]) || !in_array($card['color'], DFG_COLORS, true)
            || !is_int($card['attack']) || !is_int($card['block']) || min($card['attack'], $card['block']) < 0
            || count($card['switches']) < 1 || count($card['switches']) > 3 || array_diff($card['switches'], DFG_COLORS)) throw new RuntimeException('Invalid card catalog');
        $main = DFGCompileAdvantage($card['ability']);
        $card['advantage'] = $main['mechanic'];
        $card['special'] = $main['special'];
        foreach (['ability', 'bonus', 'reaction'] as $mode) $card['effects'][$mode] = DFGCompileEffect($mode === 'ability' ? $main['text'] : $card[$mode]);
        $cards[$card['id']] = $card;
        ++$counts[$card['color']];
    }
    if (count($cards) !== 36 || array_filter($counts, fn($count) => $count !== 6)) throw new RuntimeException('Expected six cards in each of six hands');
    return $cards;
}

function DFGCardPublic(array $card): array {
    unset($card['effects']);
    return $card;
}
