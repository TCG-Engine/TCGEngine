<?php
// Player-knowledge inputs only. Never read CardID from a live deck here.
function GABotRememberDecklist($player, array $cardIDs) {
    $counts = array_count_values($cardIDs);
    ksort($counts);
    DecisionQueueController::StoreVariable('GADecklist' . intval($player), json_encode($counts));
}

function GABotDeckKnowledge($player) {
    $initial = json_decode(DecisionQueueController::GetVariable('GADecklist' . intval($player)) ?? '', true);
    $size = GABotLiveCount(GetDeck($player)); // Size only, including when opponents manipulate it.
    if (!is_array($initial)) return ['size' => $size, 'unseen' => null, 'exact' => false];
    $remaining = $initial;
    $subtract = function ($id) use (&$remaining) {
        if (isset($remaining[$id])) $remaining[$id] = max(0, $remaining[$id] - 1);
    };
    // Own hand/memory are known. Face-down banish identities are intentionally excluded.
    foreach (['Hand', 'Memory', 'Graveyard', 'Banish', 'Intent', 'TempZone'] as $zone) {
        foreach (('Get' . $zone)($player) as $obj) {
            if ($obj === null || !empty($obj->removed)) continue;
            if ($zone === 'Banish' && (GABanishFaceDown($obj)
                || !empty($obj->Counters['_sealFaceDown']) || !empty($obj->Counters['_shadowreaverFaceDown']))) continue;
            $subtract($obj->CardID);
        }
    }
    foreach ([1, 2] as $seat) {
        foreach (GetField($seat) as $obj) {
            if ($obj === null || !empty($obj->removed) || intval($obj->Owner) !== intval($player)) continue;
            $subtract($obj->CardID);
            foreach (($obj->Subcards ?? []) as $id) if (is_string($id)) $subtract($id);
        }
    }
    // Unseen cards may include opaque banishes, transformed/attached cards, or cards held
    // by an opponent. Do not assume these are all still in the deck.
    return ['size' => $size, 'unseen' => $remaining, 'exact' => array_sum($remaining) === $size];
}

// Probability of >=1 success in draws without replacement; no independent-draw shortcut.
function GABotHypergeometricHit($population, $successes, $draws) {
    $population = max(0, intval($population));
    $successes = min($population, max(0, intval($successes)));
    $draws = min($population, max(0, intval($draws)));
    if ($population === 0 || $successes === 0 || $draws === 0) return 0.0;
    $miss = 1.0;
    for ($i = 0; $i < $draws; ++$i) {
        $miss *= max(0, $population - $successes - $i) / ($population - $i);
    }
    return 1.0 - $miss;
}

function GABotDrawOdds($player, array $wanted, $draws = 1) {
    $knowledge = GABotDeckKnowledge($player);
    $n = $knowledge['size'];
    if ($n === 0 || $draws <= 0) return ['lower' => 0.0, 'upper' => 0.0, 'exact' => true];
    $counts = $knowledge['unseen'];
    if ($counts === null || array_sum($counts) < $n) return ['lower' => 0.0, 'upper' => 1.0, 'exact' => false];
    $hits = 0;
    foreach (array_unique($wanted) as $id) $hits += $counts[$id] ?? 0;
    $unknownOutside = array_sum($counts) - $n;
    return ['lower' => GABotHypergeometricHit($n, max(0, $hits - $unknownOutside), $draws),
        'upper' => GABotHypergeometricHit($n, min($n, $hits), $draws), 'exact' => $knowledge['exact']];
}

function GABotDrawPriority($player) {
    $combat = GABotCombatContext($player);
    if ($combat !== null && $combat['targetOwner'] === $player && $combat['threat'] >= $combat['hp']
        && $combat['threat'] - 2 < $combat['hp'] && DecisionQueueController::GetVariable('CombatDamageAmount') === null
        && CanPlayerUseCardElement($player, '1n3gygojwk', false, false)
        && CountAvailableReservePayments($player) >= CardCost_reserve('1n3gygojwk')) {
        // A chance at Evasive Maneuvers is worth more than routine development, but
        // less than a guaranteed lifesaving response. Use the conservative bound.
        return 20 + 120 * GABotDrawOdds($player, ['1n3gygojwk'])['lower'];
    }
    $knowledge = GABotDeckKnowledge($player);
    $allies = [];
    foreach (array_keys($knowledge['unseen'] ?? []) as $id) {
        if (PropertyContains(CardType($id), 'ALLY') && CanPlayerUseCardElement($player, $id, false, false)) $allies[] = $id;
    }
    return 20 + 10 * GABotDrawOdds($player, $allies)['lower'];
}

function GABotGlimpseChoice($player, $param) {
    // This parameter is the authorized, player-visible glimpse payload, not GetDeck().
    if (!preg_match('/^Top=([^;]*);Bottom=(.*)$/', $param, $match)) return $param;
    $cards = array_values(array_filter(explode(',', $match[1] . ',' . $match[2])));
    $scores = [];
    foreach ($cards as $id) {
        $score = CanPlayerUseCardElement($player, $id, false, false) ? 10 : -10;
        if (PropertyContains(CardType($id), 'ALLY')) $score += 2;
        $combat = GABotCombatContext($player);
        if ($id === '1n3gygojwk' && $combat !== null && $combat['targetOwner'] === $player
            && $combat['threat'] >= $combat['hp'] && $combat['threat'] - 2 < $combat['hp']) $score += 100;
        $scores[$id] = $score;
    }
    usort($cards, fn($a, $b) => $scores[$b] <=> $scores[$a]);
    $top = []; $bottom = [];
    foreach ($cards as $id) { if ($scores[$id] >= 0) $top[] = $id; else $bottom[] = $id; }
    return 'Top=' . implode(',', $top) . ';Bottom=' . implode(',', $bottom);
}

function GABotKnownCardName($player) {
    $counts = GABotDeckKnowledge($player)['unseen'] ?? [];
    // Most frequent unseen card is a fair guess; sorted IDs break ties without deck order.
    ksort($counts);
    arsort($counts, SORT_NUMERIC);
    foreach ($counts as $id => $count) if ($count > 0 && CardName($id)) return CardName($id);
    foreach (['Hand', 'Memory', 'Material', 'Graveyard'] as $zone) {
        foreach (('Get' . $zone)($player) as $obj) {
            if ($obj !== null && empty($obj->removed) && CardName($obj->CardID)) return CardName($obj->CardID);
        }
    }
    return 'Fireball';
}
