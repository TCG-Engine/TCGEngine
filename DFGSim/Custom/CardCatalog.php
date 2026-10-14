<?php
declare(strict_types=1);

const DFG_COLORS = ['Red', 'Orange', 'Yellow', 'Green', 'Blue', 'Purple'];
const DFG_STYLES = ['Red' => 'Pressure', 'Orange' => 'Grappling', 'Yellow' => 'Footwork', 'Green' => 'Defense', 'Blue' => 'Counterplay', 'Purple' => 'Power'];

function DFGCompileEffect(string $text): array {
    $text = trim($text);
    if ($text === '') return ['condition' => null, 'yes' => [], 'no' => []];
    $condition = null;
    if (str_starts_with($text, 'If ')) {
        [$clause, $text] = explode(', ', $text, 2);
        if ($clause === 'If your opponent committed a block-increasing bonus burn this round') {
            $condition = ['type'=>'block_bonus'];
        } elseif (preg_match('/^If (you|your opponent) committed (no|at least one|at least two) bonus burns? this round$/D', $clause, $m)) {
            $condition = ['type' => 'burns', 'opponent' => $m[1] !== 'you', 'minimum' => ['no' => 0, 'at least one' => 1, 'at least two' => 2][$m[2]], 'exact' => $m[2] === 'no'];
        } elseif (preg_match("/^If your opponent's main card has a printed (attack|block) value of (\d+) or (more|less)$/D", $clause, $m)) {
            $condition = ['type' => 'printed', 'stat' => $m[1], 'value' => (int)$m[2], 'comparison' => $m[3]];
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
        if (preg_match('/^(Gain|gain|Your opponent gains) \+(\d+) (attack|block)(?: and \+(\d+) (attack|block))? this round\.$/D', $sentence, $m)) {
            $changes[] = ['opponent' => $m[1] === 'Your opponent gains', 'stat' => $m[3], 'amount' => (int)$m[2]];
            if (isset($m[4]) && $m[4] !== '') $changes[] = ['opponent' => $m[1] === 'Your opponent gains', 'stat' => $m[5], 'amount' => (int)$m[4]];
        } elseif (preg_match("/^For your damage calculation this round, treat your opponent's block as (\d+) lower, to a minimum of 0\.$/D", $sentence, $m)) {
            $changes[] = ['opponent' => false, 'stat' => 'pierce', 'amount' => (int)$m[1]];
        } else throw new RuntimeException('Unsupported effect: ' . $sentence);
    }
    return $changes;
}

function DFGCompileAdvantage(string $text): array {
    $mechanic = ['earn' => null, 'spend' => null]; $combat = [];
    $objectives = [
        'Deal damage against a block-increasing bonus burn' => 'punish_block_bonus',
        'Deal damage against printed block 4+' => 'damage_vs_block',
        'Take no damage against printed attack 5+' => 'guard_high',
        'Deal damage against a bonus burn' => 'punish_bonus',
        'Deal 4+ damage without a bonus burn' => 'heavy_unassisted',
        'Deal damage and take no damage' => 'clean_counter',
        'Deal damage against printed attack 3 or less' => 'punish_low_attack',
        'Take no damage against a bonus burn' => 'guard_bonus',
        'Deal damage without a bonus or reaction burn' => 'efficient_hit',
        'Deal damage against printed attack 5+' => 'punish_high_attack',
        'Deal damage against printed block 5+' => 'break_heavy_guard'
    ];
    foreach (explode("\n", trim($text)) as $line) {
        $line = trim($line); if ($line === '') continue;
        if (preg_match('/^Objective: (.+): gain (Red|Orange|Yellow|Green|Blue|Purple) advantage\.$/D', $line, $m)) {
            if ($mechanic['earn'] !== null || !isset($objectives[$m[1]])) throw new RuntimeException('Unsupported advantage objective');
            $mechanic['earn'] = ['color' => $m[2], 'condition' => $objectives[$m[1]]];
        } elseif (preg_match('/^Spend (Red|Orange|Yellow|Green|Blue|Purple) advantage: (.+)$/D', $line, $m)) {
            if ($mechanic['spend'] !== null) throw new RuntimeException('Duplicate advantage spender');
            $mechanic['spend'] = ['colors' => [$m[1]], 'flexible' => false, 'changes' => DFGCompileChanges($m[2])];
        } elseif (in_array($line, ['Spend up to two different advantages: Red/Purple +1 attack; Green/Blue +1 block; Orange ignore 1 block; Yellow add one switch color. Two spent: +1 attack.', 'Spend up to two different advantages: Red/Purple +1 attack; Green/Blue +1 block; Orange ignore 1 block; Yellow add one different-hand switch color. Two spent: +1 attack.'], true)) {
            if ($mechanic['spend'] !== null) throw new RuntimeException('Duplicate advantage spender');
            $mechanic['spend'] = ['colors' => DFG_COLORS, 'flexible' => true, 'changes' => []];
        } else $combat[] = $line;
    }
    return ['text' => implode(' ', $combat), 'mechanic' => $mechanic];
}

function DFGCatalog(): array {
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
