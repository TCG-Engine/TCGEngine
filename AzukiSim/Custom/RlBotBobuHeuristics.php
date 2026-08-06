<?php

/**
 * Deterministic Bobu (Deck 241) midrange/control policy.
 *
 * The policy is intentionally role-based: develop cheap searchers, preserve
 * life, trade into opposing threats, and convert the late IKZ curve into
 * Plowman/Ancient/Python finishers. Decisions outside these rules abstain so a
 * residual checkpoint can learn them without fighting the heuristic.
 */

function AzukiBobuHeuristicCardIDs(): array {
    return [
        'bobu' => 'S1-STT03-001_Bobu_L_L_die',
        'stonehaven_gate' => 'S1-STT03-002_Stonehaven-Gate_G_G_die',
        'potter' => 'S1-STT03-003_Koyama-Farm-Potter_E_C_die',
        'warding_totem' => 'S1-STT03-009_Warding-Totem_E_UC_die',
        'plowman' => 'S1-STT03-011_Koyama-Farm-Plowman_E_C_die',
        'miharu' => 'S1-STT03-012_Miharu-of-the-White-Bloom_E_SR_die',
        'ancient' => 'S1-STT03-013_Stone-Masked-Ancient_E_SR_die',
        'ancient_alt' => 'S1-STT03-013A_Stone-Masked-Ancient_E_SR_die',
        'python' => 'S1-STT03-014_Sandcoil-Python_E_UC_die',
        'quicksand' => 'S1-STT03-016_Quicksand_S_R_die',
        'healing_flutter' => 'S1-AZK01-002_Healing-Flutter_S_UC_die',
        'treetop_scout' => 'S1-AZK01-045_Treetop-Scout_E_C_die',
        'shiko' => 'S1-AZK01-047_Shiko-the-Priestess_E_UC_die',
        'kale' => 'S1-AZK01-048_Kale_E_C_die',
        'shroom_tender' => 'S1-AZK01-050_Shroom-Tender_E_R_die',
        'frida' => 'S1-AZK01-067_Frida_E_C_die',
        'link' => 'S1-AZK01-069_Link_E_C_die',
    ];
}

function AzukiBobuHeuristicState($snapshot, $player): array {
    return AzukiZeroHeuristicState($snapshot, $player);
}

function AzukiBobuHeuristicEnemyTargets($state): array {
    $targets = [];
    $opponent = intval($state['opponent'] ?? 0);
    foreach(AzukiZeroHeuristicPlayerZone('GetGarden', $opponent) as $index => $obj) {
        $cardID = strval($obj->CardID ?? '');
        if(AzukiZeroHeuristicCardType($cardID) !== 'ENTITY') continue;
        $targets[] = [
            'mzID' => 'theirGarden-' . $index,
            'obj' => $obj,
            'cardID' => $cardID,
            'attack' => AzukiZeroHeuristicCardAttack($opponent, $obj, $cardID),
            'hp' => AzukiZeroHeuristicRemainingHP($opponent, $obj, $cardID),
        ];
    }
    return $targets;
}

function AzukiBobuHeuristicRemovalCount($state, $maxHP): int {
    $count = 0;
    foreach(AzukiBobuHeuristicEnemyTargets($state) as $target) {
        if(intval($target['hp']) <= intval($maxHP)) ++$count;
    }
    return $count;
}

function AzukiBobuHeuristicHasCheapMiharuTarget($player): bool {
    foreach(AzukiZeroHeuristicPlayerZone('GetHand', $player) as $obj) {
        $cardID = strval($obj->CardID ?? '');
        if(AzukiZeroHeuristicCardType($cardID) !== 'ENTITY') continue;
        if(function_exists('CardCost') && intval(CardCost($cardID)) <= 2) return true;
    }
    return false;
}

function AzukiBobuHeuristicEarthEntityCount($player): int {
    $count = 0;
    foreach(AzukiZeroHeuristicPlayerZone('GetGarden', $player) as $obj) {
        $cardID = strval($obj->CardID ?? '');
        if(AzukiZeroHeuristicCardType($cardID) !== 'ENTITY') continue;
        $isEarth = function_exists('IsCardEarthEntity')
            ? IsCardEarthEntity($cardID)
            : (!function_exists('CardElement') || strtoupper(strval(CardElement($cardID))) === 'EARTH');
        if($isEarth) ++$count;
    }
    return $count;
}

function AzukiBobuHeuristicHasProfitableEarthTrade($state): bool {
    $player = intval($state['player'] ?? 0);
    $opponent = intval($state['opponent'] ?? 0);
    $enemyTargets = AzukiBobuHeuristicEnemyTargets($state);
    foreach(AzukiZeroHeuristicPlayerZone('GetGarden', $player) as $index => $obj) {
        $cardID = strval($obj->CardID ?? '');
        if(AzukiZeroHeuristicCardType($cardID) !== 'ENTITY') continue;
        $isEarth = function_exists('IsCardEarthEntity')
            ? IsCardEarthEntity($cardID)
            : (!function_exists('CardElement') || strtoupper(strval(CardElement($cardID))) === 'EARTH');
        if(!$isEarth || intval($obj->Status ?? 2) === 1) continue;
        $mzID = 'myGarden-' . intval($obj->mzIndex ?? $index);
        if(function_exists('CanAttackWith') && !CanAttackWith($player, $mzID)) continue;
        $myAttack = AzukiZeroHeuristicCardAttack($player, $obj, $cardID);
        $myHP = AzukiZeroHeuristicRemainingHP($player, $obj, $cardID);
        foreach($enemyTargets as $target) {
            if($myAttack >= intval($target['hp'] ?? 0)
                && intval($target['attack'] ?? 0) >= $myHP) return true;
        }
    }
    return false;
}

function AzukiBobuHeuristicAlleyCount($player): int {
    return count(AzukiZeroHeuristicPlayerZone('GetAlley', $player));
}

function AzukiBobuHeuristicGateReady($player): bool {
    $gates = AzukiZeroHeuristicPlayerZone('GetGate', $player);
    if(empty($gates)) return false;
    $gate = $gates[0];
    if(intval($gate->Status ?? 2) === 1) return false;
    return !AzukiZeroHeuristicHasTurnEffect($gate, 'GATE_USED_THIS_TURN');
}

function AzukiBobuHeuristicPendingAttackPower($state): int {
    $attackerMZ = strval(AzukiZeroHeuristicVariable('PendingAttackAttackerMZ'));
    if($attackerMZ === '') return 0;
    $attackerPlayer = intval($state['opponent'] ?? 0);
    $attacker = AzukiZeroHeuristicObject($attackerMZ, $attackerPlayer);
    return AzukiZeroHeuristicCardAttack($attackerPlayer, $attacker);
}

function AzukiBobuHeuristicMulliganKeep($player): bool {
    $ids = AzukiBobuHeuristicCardIDs();
    $early = [$ids['potter'], $ids['link'], $ids['frida'], $ids['kale'], $ids['treetop_scout'], $ids['healing_flutter']];
    $earlyCount = 0;
    $expensiveCount = 0;
    foreach(AzukiZeroHeuristicPlayerZone('GetHand', $player) as $obj) {
        $cardID = strval($obj->CardID ?? '');
        if(in_array($cardID, $early, true)) ++$earlyCount;
        if(function_exists('CardCost') && intval(CardCost($cardID)) >= 5) ++$expensiveCount;
    }
    return $earlyCount >= 2 && $expensiveCount <= 2;
}

function AzukiBobuHeuristicSearchScore($cardID, $state): float {
    $ids = AzukiBobuHeuristicCardIDs();
    $ikz = intval($state['availableIKZ'] ?? 0);
    $life = intval($state['myLife'] ?? 20);
    $scores = [
        $ids['warding_totem'] => $ikz <= 4 ? 800 : 560,
        $ids['plowman'] => 720,
        $ids['miharu'] => 700,
        $ids['ancient'] => 680,
        $ids['ancient_alt'] => 680,
        $ids['python'] => 660,
        $ids['shroom_tender'] => $life <= 14 ? 760 : 610,
        $ids['shiko'] => $life <= 15 ? 690 : 570,
        $ids['healing_flutter'] => $life <= 13 ? 740 : 500,
        $ids['potter'] => 520,
        $ids['link'] => 510,
        $ids['frida'] => 500,
        $ids['kale'] => 490,
        $ids['treetop_scout'] => 480,
    ];
    return floatval($scores[$cardID] ?? 400);
}

function AzukiBobuHeuristicPlayScore($cardID, $state, $legal): float {
    $ids = AzukiBobuHeuristicCardIDs();
    $ikz = intval($state['availableIKZ'] ?? 0);
    $life = intval($state['myLife'] ?? 20);
    $pressure = max(intval($state['theirReadyAttack'] ?? 0), intval($state['theirBoardAttack'] ?? 0));
    $scores = [
        $ids['potter'] => 540,
        $ids['link'] => 525,
        $ids['frida'] => 500,
        $ids['kale'] => 490,
        $ids['treetop_scout'] => 480,
        $ids['healing_flutter'] => $life <= 14 ? 690 : 250,
        $ids['shiko'] => $life <= 16 ? 610 : 470,
        $ids['warding_totem'] => 660,
        $ids['shroom_tender'] => $life <= 15 ? 720 : 590,
        $ids['plowman'] => 700,
        $ids['quicksand'] => 580,
        $ids['miharu'] => 735,
        $ids['ancient'] => 790,
        $ids['ancient_alt'] => 790,
        $ids['python'] => 820,
    ];
    $score = floatval($scores[$cardID] ?? 400);
    $cost = function_exists('CardCost') ? max(0, intval(CardCost($cardID))) : 0;

    if($ikz <= 2 && $cost <= 1) $score += 180;
    if($ikz <= 3 && $cost >= 4) $score -= 250;
    if($cardID === $ids['warding_totem']) {
        if($ikz >= 4 && $ikz <= 6) $score += 180;
        if($pressure >= 4) $score += 120;
    }
    if($cardID === $ids['plowman']) $score += AzukiBobuHeuristicRemovalCount($state, 2) * 170;
    if($cardID === $ids['quicksand']) {
        $victims = AzukiBobuHeuristicRemovalCount($state, 2);
        if($victims === 0) return -10000;
        $score += $victims * 220;
    }
    if($cardID === $ids['python']) $score += min(240, $pressure * 25);
    if($cardID === $ids['miharu'] && AzukiBobuHeuristicHasCheapMiharuTarget(intval($state['player'] ?? 0))) $score += 140;
    if(in_array($cardID, [$ids['healing_flutter'], $ids['shroom_tender']], true) && $life <= 12) $score += 600;
    if(in_array($cardID, [$ids['healing_flutter'], $ids['shroom_tender']], true) && $life >= 19) $score -= 260;

    if(strval($legal['kind'] ?? '') === 'azuki-attack-response-fsm') {
        $score += $pressure >= $life ? 900 : ($pressure >= 4 ? 220 : -220);
    }
    return $score;
}

function AzukiBobuHeuristicDecisionScore($action, $legal, $state): float {
    $ids = AzukiBobuHeuristicCardIDs();
    $type = strtoupper(strval($legal['decisionType'] ?? ''));
    $tooltip = strtolower(str_replace('_', ' ', strval($legal['decisionTooltipRaw'] ?? $legal['decisionTooltip'] ?? '')));
    $param = strtolower(strval($legal['decisionParam'] ?? ''));
    $choice = strval($action['cardID'] ?? '');
    $choiceUpper = strtoupper($choice);
    $cardID = AzukiZeroHeuristicActionCardID($action);
    $source = AzukiZeroHeuristicDecisionSourceCardID($state);

    if($type === 'YESNO') {
        if(str_contains($tooltip, 'mulligan') || str_contains($param, 'review:myhand')) {
            $keep = AzukiBobuHeuristicMulliganKeep(intval($state['player'] ?? 0));
            return $choiceUpper === ($keep ? 'NO' : 'YES') ? 1000 : -1000;
        }
        if(str_contains($tooltip, 'bobu') && str_contains($tooltip, 'heal 1')) {
            return $choiceUpper === 'YES' ? 1000 : -1000;
        }
        if(in_array($source, [$ids['ancient'], $ids['ancient_alt']], true)) {
            return $choiceUpper === 'YES' ? 900 : -900;
        }
        return $choiceUpper === 'NO' ? 30 : 0;
    }

    if($type === 'CHOOSEZONE') {
        $gardenCards = [$ids['shiko'], $ids['warding_totem'], $ids['plowman'], $ids['miharu'], $ids['ancient'], $ids['ancient_alt'], $ids['python']];
        $player = intval($state['player'] ?? 0);
        $gateReady = AzukiBobuHeuristicGateReady($player);
        $alleyBacklog = AzukiBobuHeuristicAlleyCount($player);
        // Stonehaven can clear only one Alley entity each turn. Once an entity
        // is waiting, or the gate is already spent, additional bodies must go
        // straight to the Garden instead of creating an ever-growing backlog.
        $preferred = in_array($source, $gardenCards, true)
            || !$gateReady
            || $alleyBacklog > 0
            ? 'MYGARDEN'
            : 'MYALLEY';
        return $choiceUpper === $preferred ? 900 : 0;
    }

    if($type === 'MZMODAL' && str_contains($param, 'go_first')) {
        // Bobu wants the extra IKZ and card from going second.
        return (str_contains(strtolower($choice), 'second') || $choice === '1') ? 1000 : -1000;
    }

    if($choice === '-' || $choiceUpper === 'PASS') return -350;
    $targetMZ = AzukiZeroHeuristicMZFromAction($action);
    $target = AzukiZeroHeuristicObject($targetMZ, intval($state['player'] ?? 0));
    $targetPlayer = str_starts_with($targetMZ, 'their') ? intval($state['opponent']) : intval($state['player']);
    $targetType = AzukiZeroHeuristicCardType($cardID);
    $attack = AzukiZeroHeuristicCardAttack($targetPlayer, $target, $cardID);
    $remaining = AzukiZeroHeuristicRemainingHP($targetPlayer, $target, $cardID);

    if(str_contains($tooltip, 'select entity to portal')) {
        $gatePower = function_exists('CardGatePower') ? max(0, intval(CardGatePower($cardID))) : 0;
        $index = 0;
        if(preg_match('/^myAlley-(\d+)$/', $targetMZ, $matches) === 1) $index = intval($matches[1]);
        // Prefer a useful Stonehaven power, with stable oldest-first tie
        // breaking so newly played cards cannot starve an existing backlog.
        return 800 + ($gatePower * 90) + ($remaining * 20) - min(50, $index);
    }

    if(str_contains($tooltip, 'attack target')) {
        $attackerMZ = strval(AzukiZeroHeuristicVariable('CombatTarget'));
        $attacker = AzukiZeroHeuristicObject($attackerMZ, intval($state['player'] ?? 0));
        $attackerPower = AzukiZeroHeuristicCardAttack(intval($state['player']), $attacker);
        if($targetType === 'LEADER') {
            if($attackerPower >= intval($state['theirLife'] ?? 20)) return 10000;
            $stable = intval($state['myBoardAttack'] ?? 0) >= intval($state['theirBoardAttack'] ?? 0);
            return $stable ? 620 + ($attackerPower * 18) : 180;
        }
        $kill = $remaining > 0 && $attackerPower >= $remaining;
        return 520 + ($kill ? 380 : -120) + ($attack * 45) - ($remaining * 8);
    }

    if(str_starts_with($targetMZ, 'their')) {
        return 600 + ($attack * 55) - ($remaining * 8);
    }

    if(str_starts_with($targetMZ, 'my')) {
        if($source === $ids['stonehaven_gate']) return 560 + ($remaining * 30) - ($attack * 5);
        if($source === $ids['miharu']) return 600 + (function_exists('CardCost') ? intval(CardCost($cardID)) * 80 : 0);
        return 450 + ($remaining * 15) + ($attack * 10);
    }

    // Bottom-deck searches select the best curve/control role currently shown.
    return AzukiBobuHeuristicSearchScore($cardID, $state);
}

function AzukiBobuHeuristicActionScore($action, $actions, $legal, $snapshot, $player): float {
    $ids = AzukiBobuHeuristicCardIDs();
    $state = AzukiBobuHeuristicState($snapshot, $player);
    $key = AzukiZeroHeuristicActionKey($action, $legal);
    $cardID = AzukiZeroHeuristicActionCardID($action);
    $kind = strval($legal['kind'] ?? '');

    if(strval($legal['decisionType'] ?? '') !== '') return AzukiBobuHeuristicDecisionScore($action, $legal, $state);

    if($kind === 'azuki-attack-response-fsm') {
        $incoming = AzukiBobuHeuristicPendingAttackPower($state);
        if(str_starts_with($key, 'interact:')) {
            $defender = AzukiZeroHeuristicObject(AzukiZeroHeuristicMZFromAction($action), $player);
            $remaining = AzukiZeroHeuristicRemainingHP($player, $defender, $cardID);
            $preventsLethal = $incoming >= intval($state['myLife'] ?? 20);
            return 850
                + ($incoming * 90)
                + ($preventsLethal ? 5000 : 0)
                + (min($incoming, $remaining) * 25);
        }
        if(str_starts_with($key, 'pass:')) {
            return $incoming >= intval($state['myLife'] ?? 20) ? -10000 : 50;
        }
    }

    if(str_starts_with($key, 'pass:')) {
        $nonPass = 0;
        foreach($actions as $candidate) {
            if(!str_starts_with(AzukiZeroHeuristicActionKey($candidate, $legal), 'pass:')) ++$nonPass;
        }
        if($kind === 'azuki-attack-response-fsm') {
            $pressure = max(intval($state['theirReadyAttack'] ?? 0), intval($state['theirBoardAttack'] ?? 0));
            return $nonPass > 0 && $pressure >= intval($state['myLife'] ?? 20) ? -900 : 80;
        }
        return $nonPass > 0 ? -1000 : 0;
    }

    if(str_starts_with($key, 'attack:')) {
        $obj = AzukiZeroHeuristicObject(AzukiZeroHeuristicMZFromAction($action), $player);
        $power = AzukiZeroHeuristicCardAttack($player, $obj, $cardID);
        $pressure = max(intval($state['theirReadyAttack'] ?? 0), intval($state['theirBoardAttack'] ?? 0));
        $isDefender = is_object($obj) && function_exists('IsDefenderEntity') && IsDefenderEntity($obj);
        if($isDefender && ($pressure >= intval($state['myLife'] ?? 20)
            || (intval($state['myLife'] ?? 20) <= 10 && $pressure >= 4))) {
            return -10000;
        }
        $score = 560 + ($power * 22);
        if($cardID === $ids['shiko'] && intval($state['myLife'] ?? 20) < 20) $score += 180;
        if($power >= intval($state['theirLife'] ?? 20)) $score += 5000;
        return $score;
    }

    if(str_starts_with($key, 'activate:')) {
        if($cardID === $ids['bobu']) {
            $pressure = max(intval($state['theirReadyAttack'] ?? 0), intval($state['theirBoardAttack'] ?? 0));
            // Bobu Ward only pays off when an Earth entity is destroyed before
            // our next turn. With no such entity already exposed, spending the
            // IKZ is pure tempo loss (the decisive error in game 211).
            if(AzukiBobuHeuristicEarthEntityCount($player) === 0) return -10000;
            // If a ready Earth entity can make a mutual trade, its death is no
            // longer speculative. Ward first and collect the otherwise-free heal.
            if(AzukiBobuHeuristicHasProfitableEarthTrade($state)) return 1100;
            if($pressure < 4 && intval($state['myLife'] ?? 20) > 12) return -1200;
            return 760 + ($pressure * 25) + (intval($state['myLife'] ?? 20) <= 10 ? 260 : 0);
        }
        if($cardID === $ids['stonehaven_gate']) {
            $backlog = AzukiBobuHeuristicAlleyCount($player);
            return 700 + min(3, $backlog) * 170;
        }
        return 300;
    }

    if(str_starts_with($key, 'play:')) return AzukiBobuHeuristicPlayScore($cardID, $state, $legal);
    return 100;
}

function AzukiBobuHeuristicCoverageRule($actions, $legal, $snapshot, $player): string {
    if(!is_array($actions) || empty($actions)) return '';
    if(count($actions) === 1) return 'forced-action';

    $ids = AzukiBobuHeuristicCardIDs();
    $state = AzukiBobuHeuristicState($snapshot, $player);
    $type = strtoupper(strval($legal['decisionType'] ?? ''));
    $tooltip = strtolower(str_replace('_', ' ', strval($legal['decisionTooltipRaw'] ?? $legal['decisionTooltip'] ?? '')));
    $param = strtolower(strval($legal['decisionParam'] ?? ''));
    $source = AzukiZeroHeuristicDecisionSourceCardID($state);

    if($type !== '') {
        if($type === 'YESNO') {
            if(str_contains($tooltip, 'mulligan') || str_contains($param, 'review:myhand')) return 'mulligan';
            if(str_contains($tooltip, 'bobu') && str_contains($tooltip, 'heal 1')) return 'bobu-ward-heal';
            if(in_array($source, [$ids['ancient'], $ids['ancient_alt']], true)) return 'ancient-entry';
            return '';
        }
        if($type === 'CHOOSEZONE') return 'entity-placement';
        if($type === 'MZMODAL' && str_contains($param, 'go_first')) return 'go-second';
        if(str_contains($tooltip, 'attack target')) return 'attack-target';
        if(str_contains($tooltip, 'choose') || str_contains($tooltip, 'select')) return 'known-card-choice';
        if(in_array($source, array_values($ids), true)) return 'known-card-choice';
        return '';
    }

    if(strval($legal['kind'] ?? '') === 'azuki-attack-response-fsm') {
        foreach($actions as $action) {
            $key = AzukiZeroHeuristicActionKey($action, $legal);
            if(!str_starts_with($key, 'pass:') && !str_starts_with($key, 'interact:')) return '';
        }
        return 'defender-response';
    }

    foreach($actions as $action) {
        if(!is_array($action)) return '';
        $key = AzukiZeroHeuristicActionKey($action, $legal);
        $cardID = AzukiZeroHeuristicActionCardID($action);
        if(str_starts_with($key, 'pass:') || str_starts_with($key, 'attack:')) continue;
        if(str_starts_with($key, 'play:') && $cardID !== '') continue;
        if(str_starts_with($key, 'activate:') && in_array($cardID, [$ids['bobu'], $ids['stonehaven_gate']], true)) continue;
        return '';
    }
    return 'known-free-play';
}

function AzukiBobuHeuristicCoveredChoice($actions, $legal, $snapshot, $player): array {
    $rule = AzukiBobuHeuristicCoverageRule($actions, $legal, $snapshot, $player);
    if($rule === '') return ['covered' => false, 'rule' => '', 'action' => null];

    $best = null;
    $bestScore = null;
    $bestKey = '';
    $ambiguous = false;
    foreach($actions as $action) {
        if(!is_array($action)) continue;
        $score = AzukiBobuHeuristicActionScore($action, $actions, $legal, $snapshot, $player);
        $key = AzukiZeroHeuristicActionKey($action, $legal);
        if($best === null || $score > $bestScore) {
            $best = $action;
            $bestScore = $score;
            $bestKey = $key;
            $ambiguous = false;
        } else if($score === $bestScore && $key !== $bestKey) {
            $ambiguous = true;
        }
    }
    if($best === null || $ambiguous) return ['covered' => false, 'rule' => $ambiguous ? 'ambiguous-' . $rule : '', 'action' => null];
    return ['covered' => true, 'rule' => $rule, 'action' => $best, 'score' => $bestScore];
}

function AzukiBobuHeuristicChooseAction($stateLogits, $actions, $legal, $snapshot, $player) {
    if(!is_array($actions) || empty($actions)) return null;
    $decisionType = strtoupper(strval($legal['decisionType'] ?? ''));
    $decisionParam = strtolower(strval($legal['decisionParam'] ?? ''));
    if($decisionType === 'MZMODAL' && str_contains($decisionParam, 'go_first') && str_contains($decisionParam, 'go_second')) {
        foreach($actions as $action) {
            $choice = strtolower(strval($action['cardID'] ?? ''));
            // Runtime modal actions use the label; numeric 1 is retained for
            // compatibility with older serialized modal enumerators.
            if(str_contains($choice, 'go_second') || $choice === '1') return $action;
        }
    }
    $best = null;
    $bestHeuristic = null;
    $bestModel = null;
    foreach($actions as $action) {
        if(!is_array($action)) continue;
        $heuristic = AzukiBobuHeuristicActionScore($action, $actions, $legal, $snapshot, $player);
        $model = AzukiZeroHeuristicModelScore($stateLogits, $action, $legal);
        if($best === null || $heuristic > $bestHeuristic || ($heuristic === $bestHeuristic && $model > $bestModel)) {
            $best = $action;
            $bestHeuristic = $heuristic;
            $bestModel = $model;
        }
    }
    return $best;
}
