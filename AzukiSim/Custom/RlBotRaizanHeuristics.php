<?php

/**
 * Deterministic Raizan (Deck 373) tempo policy.
 *
 * These first rules are intentionally narrow and come from three completed
 * human Raizan-vs-Zero games. Choices with conflicting or sparse evidence
 * abstain to the published Raizan model.
 */

function AzukiRaizanHeuristicCardIDs(): array {
    return [
        'raizan' => 'S1-STT01-001_Raizan_L_L_die',
        'surge_gate' => 'S1-STT01-002_Surge-Gate_G_G_die',
        'recruit' => 'S1-STT01-004_Black-Jade-Recruit_E_C_die',
        'prowler' => 'S1-STT01-005_Alpine-Prowler_E_C_die',
        'haruhi' => 'S1-STT01-006_Silver-Current-Haruhi_E_R_die',
        'crewleader' => 'S1-STT01-008_Black-Jade-Crewleader_E_UC_die',
        'lightning_shuriken' => 'S1-STT01-012_Lightning-Shuriken_W_C_die',
        'black_jade_dagger' => 'S1-STT01-013_Black-Jade-Dagger_W_C_die',
        'hidden_dagger' => 'S1-AZK01-094_Hidden-Dagger_W_C_die',
        'tenshin' => 'S1-STT01-014_Tenshin_W_C_die',
        'lightning_orb' => 'S1-STT01-017_Lightning-Orb_S_UC_die',
        'sundering_strike' => 'S1-AZK01-127_Sundering-Strike_S_UC_die',
    ];
}

function AzukiRaizanHeuristicState($snapshot, $player): array {
    return AzukiZeroHeuristicState($snapshot, $player);
}

function AzukiRaizanHeuristicHasAction($actions, $legal, $prefix, $cardID): bool {
    foreach($actions as $action) {
        if(!is_array($action)) continue;
        if(!str_starts_with(AzukiZeroHeuristicActionKey($action, $legal), $prefix)) continue;
        if(AzukiZeroHeuristicActionCardID($action) === $cardID) return true;
    }
    return false;
}

function AzukiRaizanHeuristicMulliganKeep($player): bool {
    $ids = AzukiRaizanHeuristicCardIDs();
    return AzukiZeroHeuristicHasCard(
        AzukiZeroHeuristicPlayerZone('GetHand', intval($player)),
        $ids['recruit']
    );
}

function AzukiRaizanHeuristicAlleyCount($player): int {
    return count(AzukiZeroHeuristicPlayerZone('GetAlley', intval($player)));
}

function AzukiRaizanHeuristicGateReady($player): bool {
    $gates = AzukiZeroHeuristicPlayerZone('GetGate', intval($player));
    if(empty($gates)) return false;
    $gate = $gates[0];
    if(intval($gate->Status ?? 2) === 1) return false;
    return !AzukiZeroHeuristicHasTurnEffect($gate, 'GATE_USED_THIS_TURN');
}

function AzukiRaizanHeuristicRecruitExchangeScore($action): float {
    $choice = strval($action['cardID'] ?? '');
    if($choice === '-' || strtoupper($choice) === 'PASS') return -1000;

    $cardID = AzukiZeroHeuristicActionCardID($action);
    if(AzukiZeroHeuristicCardType($cardID) !== 'WEAPON') return -10000;
    $cost = function_exists('CardCost') ? max(0, intval(CardCost($cardID))) : 99;
    $index = 999;
    $targetMZ = AzukiZeroHeuristicMZFromAction($action);
    if(preg_match('/^myHand-(\d+)$/', $targetMZ, $matches) === 1) $index = intval($matches[1]);

    // Recruit should exchange a one-cost Weapon whenever possible. The small
    // hand-index term makes equally cheap choices deterministic without
    // changing the cost-first priority.
    return 1200 - ($cost * 200) - min(99, $index);
}

function AzukiRaizanHeuristicSurgeWeaponScore($action): float {
    $choice = strval($action['cardID'] ?? '');
    if($choice === '-' || strtoupper($choice) === 'PASS') return -1000;

    $ids = AzukiRaizanHeuristicCardIDs();
    $cardID = AzukiZeroHeuristicActionCardID($action);
    if(AzukiZeroHeuristicCardType($cardID) !== 'WEAPON') return -10000;
    $preference = [
        $ids['lightning_shuriken'] => 90,
        $ids['hidden_dagger'] => 60,
        $ids['black_jade_dagger'] => 30,
    ];
    $index = 999;
    $targetMZ = AzukiZeroHeuristicMZFromAction($action);
    if(preg_match('/^myDiscard-(\d+)$/', $targetMZ, $matches) === 1) $index = intval($matches[1]);
    return 1200 + floatval($preference[$cardID] ?? 0) - min(99, $index);
}

function AzukiRaizanHeuristicSurgeEquipScore($action, $state): float {
    $choice = strval($action['cardID'] ?? '');
    if($choice === '-' || strtoupper($choice) === 'PASS') return -1000;

    $ids = AzukiRaizanHeuristicCardIDs();
    $targetMZ = AzukiZeroHeuristicMZFromAction($action);
    $target = AzukiZeroHeuristicObject($targetMZ, intval($state['player'] ?? 0));
    $cardID = AzukiZeroHeuristicActionCardID($action);
    if(!is_object($target) || !str_starts_with($targetMZ, 'myGarden-')) return -10000;
    $ready = intval($target->Status ?? 2) === 2;
    $attack = AzukiZeroHeuristicCardAttack(intval($state['player'] ?? 0), $target, $cardID);
    $index = 999;
    if(preg_match('/^myGarden-(\d+)$/', $targetMZ, $matches) === 1) $index = intval($matches[1]);
    return 1000
        + ($cardID === $ids['raizan'] ? 500 : 0)
        + ($ready ? 100 : 0)
        + ($attack * 20)
        - min(99, $index);
}

function AzukiRaizanHeuristicTargetScore($action, $legal, $state, $source = '', $damage = 1): float {
    $choice = strval($action['cardID'] ?? '');
    if($choice === '-' || strtoupper($choice) === 'PASS') return -600;

    $targetMZ = AzukiZeroHeuristicMZFromAction($action);
    $target = AzukiZeroHeuristicObject($targetMZ, intval($state['player'] ?? 0));
    $cardID = AzukiZeroHeuristicActionCardID($action);
    $targetType = AzukiZeroHeuristicCardType($cardID);
    $targetPlayer = str_starts_with($targetMZ, 'their')
        ? intval($state['opponent'] ?? 0)
        : intval($state['player'] ?? 0);

    if($source === AzukiRaizanHeuristicCardIDs()['raizan']) {
        if(!str_starts_with($targetMZ, 'myGarden-') || !is_object($target)) return -10000;
        if($targetType !== 'ENTITY') return -10000;
        if(function_exists('HasEquippedWeapon') && !HasEquippedWeapon($target)) return -10000;
        $ready = intval($target->Status ?? 2) === 2;
        $attack = AzukiZeroHeuristicCardAttack($targetPlayer, $target, $cardID);
        return 900 + ($ready ? 200 : -300) + ($attack * 40);
    }

    if(str_starts_with($targetMZ, 'their')) {
        if($targetType === 'LEADER') {
            $lethal = $damage >= intval($state['theirLife'] ?? 20);
            return $lethal ? 10000 : 420;
        }
        if($targetType !== 'ENTITY' || !is_object($target)) return -10000;
        $remaining = AzukiZeroHeuristicRemainingHP($targetPlayer, $target, $cardID);
        $attack = AzukiZeroHeuristicCardAttack($targetPlayer, $target, $cardID);
        if($remaining > 0 && $damage >= $remaining) {
            return 850 + ($attack * 45) - ($remaining * 10);
        }
        return 200 + ($attack * 20) - ($remaining * 25);
    }

    return -10000;
}

function AzukiRaizanHeuristicDecisionScore($action, $legal, $state): float {
    $ids = AzukiRaizanHeuristicCardIDs();
    $type = strtoupper(strval($legal['decisionType'] ?? ''));
    $tooltip = strtolower(str_replace('_', ' ', strval($legal['decisionTooltipRaw'] ?? $legal['decisionTooltip'] ?? '')));
    $param = strtolower(strval($legal['decisionParam'] ?? ''));
    $choice = strval($action['cardID'] ?? '');
    $choiceUpper = strtoupper($choice);
    $cardID = AzukiZeroHeuristicActionCardID($action);
    $source = AzukiZeroHeuristicDecisionSourceCardID($state);
    $handler = AzukiZeroHeuristicPendingHandler(intval($state['player'] ?? 0));

    if($type === 'YESNO') {
        if(str_contains($tooltip, 'mulligan') || str_contains($param, 'review:myhand')) {
            return $choiceUpper === 'NO' ? 1000 : -1000;
        }
        if($source === $ids['black_jade_dagger']) {
            $safe = intval($state['myLife'] ?? 20) > 1;
            $lethal = intval($state['myReadyAttack'] ?? 0) + 1 >= intval($state['theirLife'] ?? 20);
            return $choiceUpper === ($safe && $lethal ? 'YES' : 'NO') ? 900 : -900;
        }
        return 0;
    }

    if($type === 'CHOOSEZONE') {
        $player = intval($state['player'] ?? 0);
        $preferred = AzukiRaizanHeuristicGateReady($player)
            && AzukiRaizanHeuristicAlleyCount($player) === 0
            ? 'MYALLEY'
            : 'MYGARDEN';
        return $choiceUpper === $preferred ? 900 : 0;
    }

    if($source === $ids['recruit']) return AzukiRaizanHeuristicRecruitExchangeScore($action);

    if(str_starts_with($handler, $ids['surge_gate'] . ':0:UseGate-1')) {
        return AzukiRaizanHeuristicSurgeWeaponScore($action);
    }
    if(str_starts_with($handler, $ids['surge_gate'] . ':0:UseGate-2')) {
        return AzukiRaizanHeuristicSurgeEquipScore($action, $state);
    }

    if(str_contains($tooltip, 'select entity to portal')) {
        $targetMZ = AzukiZeroHeuristicMZFromAction($action);
        $index = 999;
        if(preg_match('/^myAlley-(\d+)$/', $targetMZ, $matches) === 1) $index = intval($matches[1]);
        $gatePower = function_exists('CardGatePower') ? max(0, intval(CardGatePower($cardID))) : 0;
        return 1000 + ($gatePower * 50) - min(99, $index);
    }

    if(str_contains($tooltip, 'attack target')) {
        $attackerMZ = strval(AzukiZeroHeuristicVariable('CombatTarget'));
        $attacker = AzukiZeroHeuristicObject($attackerMZ, intval($state['player'] ?? 0));
        $attack = AzukiZeroHeuristicCardAttack(intval($state['player'] ?? 0), $attacker);
        return AzukiRaizanHeuristicTargetScore($action, $legal, $state, '', max(1, $attack));
    }

    return AzukiRaizanHeuristicTargetScore($action, $legal, $state, $source, 1);
}

function AzukiRaizanHeuristicActionScore($action, $actions, $legal, $snapshot, $player): float {
    $ids = AzukiRaizanHeuristicCardIDs();
    $state = AzukiRaizanHeuristicState($snapshot, $player);
    if(strval($legal['decisionType'] ?? '') !== '') {
        return AzukiRaizanHeuristicDecisionScore($action, $legal, $state);
    }

    $key = AzukiZeroHeuristicActionKey($action, $legal);
    $cardID = AzukiZeroHeuristicActionCardID($action);
    if(str_starts_with($key, 'pass:')) return -1000;

    if(str_starts_with($key, 'play:')) {
        if($cardID === $ids['recruit']) return 1000;
        if($cardID === $ids['haruhi']) return 900;
        if($cardID === $ids['crewleader']) return 850;
        return 100;
    }

    if(str_starts_with($key, 'attack:')) {
        $obj = AzukiZeroHeuristicObject(AzukiZeroHeuristicMZFromAction($action), intval($player));
        $index = 999;
        if(preg_match('/^myGarden-(\d+)$/', AzukiZeroHeuristicMZFromAction($action), $matches) === 1) {
            $index = intval($matches[1]);
        }
        return 600 + (AzukiZeroHeuristicCardAttack(intval($player), $obj, $cardID) * 25) - min(99, $index);
    }

    if(str_starts_with($key, 'activate:') && $cardID === $ids['surge_gate']) {
        return AzukiRaizanHeuristicAlleyCount($player) > 0 ? 1400 : -10000;
    }
    if(str_starts_with($key, 'activate:') && $cardID === $ids['raizan']) return 800;
    return 0;
}

function AzukiRaizanHeuristicCoverageRule($actions, $legal, $snapshot, $player): string {
    if(!is_array($actions) || empty($actions)) return '';
    if(count($actions) === 1) return 'forced-action';

    $ids = AzukiRaizanHeuristicCardIDs();
    $state = AzukiRaizanHeuristicState($snapshot, $player);
    $type = strtoupper(strval($legal['decisionType'] ?? ''));
    $tooltip = strtolower(str_replace('_', ' ', strval($legal['decisionTooltipRaw'] ?? $legal['decisionTooltip'] ?? '')));
    $param = strtolower(strval($legal['decisionParam'] ?? ''));
    $source = AzukiZeroHeuristicDecisionSourceCardID($state);
    $handler = AzukiZeroHeuristicPendingHandler(intval($state['player'] ?? 0));

    if($type !== '') {
        if($type === 'YESNO') {
            if((str_contains($tooltip, 'mulligan') || str_contains($param, 'review:myhand'))
                && AzukiRaizanHeuristicMulliganKeep($player)) return 'keep-recruit-opener';
            if($source === $ids['black_jade_dagger']) return 'dagger-lethal-bonus';
            return '';
        }
        if($type === 'CHOOSEZONE' && in_array($source, [
            $ids['recruit'], $ids['prowler'], $ids['haruhi'], $ids['crewleader'],
        ], true)) return 'core-entity-placement';
        if($source === $ids['recruit']) return 'recruit-cheapest-weapon-exchange';
        if(str_starts_with($handler, $ids['surge_gate'] . ':0:UseGate-1')) return 'surge-recover-weapon';
        if(str_starts_with($handler, $ids['surge_gate'] . ':0:UseGate-2')) return 'surge-equip-raizan';
        if(str_contains($tooltip, 'select entity to portal')) return 'clear-alley-target';
        if(str_contains($tooltip, 'attack target')) return 'efficient-attack-target';
        if($source === $ids['raizan']) return 'friendly-charge-target';
        if(in_array($source, [$ids['haruhi'], $ids['tenshin'], $ids['lightning_orb'], $ids['sundering_strike']], true)) {
            return 'one-point-removal-target';
        }
        return '';
    }

    if(AzukiRaizanHeuristicAlleyCount($player) > 0
        && AzukiRaizanHeuristicHasAction($actions, $legal, 'activate:', $ids['surge_gate'])) {
        return 'clear-alley';
    }
    $onlyPassAndAttacks = true;
    $hasAttack = false;
    foreach($actions as $action) {
        if(!is_array($action)) {
            $onlyPassAndAttacks = false;
            break;
        }
        $key = AzukiZeroHeuristicActionKey($action, $legal);
        if(str_starts_with($key, 'attack:')) {
            $hasAttack = true;
            continue;
        }
        if(!str_starts_with($key, 'pass:')) {
            $onlyPassAndAttacks = false;
            break;
        }
    }
    if($hasAttack && $onlyPassAndAttacks) return 'attack-before-pass';
    $availableIKZ = intval($state['availableIKZ'] ?? 0);
    if($availableIKZ <= 1 && AzukiRaizanHeuristicHasAction($actions, $legal, 'play:', $ids['recruit'])) {
        return 'recruit-one-drop';
    }
    if($availableIKZ >= 2 && $availableIKZ <= 3
        && AzukiRaizanHeuristicHasAction($actions, $legal, 'play:', $ids['haruhi'])) {
        return 'haruhi-two-drop';
    }
    return '';
}

function AzukiRaizanHeuristicCoveredChoice($actions, $legal, $snapshot, $player): array {
    $rule = AzukiRaizanHeuristicCoverageRule($actions, $legal, $snapshot, $player);
    if($rule === '') return ['covered' => false, 'rule' => '', 'action' => null];

    $best = null;
    $bestScore = null;
    $bestKey = '';
    $ambiguous = false;
    foreach($actions as $action) {
        if(!is_array($action)) continue;
        $score = AzukiRaizanHeuristicActionScore($action, $actions, $legal, $snapshot, $player);
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
    if($best === null || $ambiguous) {
        return ['covered' => false, 'rule' => $ambiguous ? 'ambiguous-' . $rule : '', 'action' => null];
    }
    return ['covered' => true, 'rule' => $rule, 'action' => $best, 'score' => $bestScore];
}
