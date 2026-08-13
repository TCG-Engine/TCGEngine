<?php

/**
 * Deterministic Zero (Deck 51) policy distilled from completed human game logs.
 *
 * The rules intentionally score strategic roles and live board properties rather
 * than replaying exact logged turns. The published RL table remains a tie-breaker.
 */

function AzukiZeroHeuristicCardIDs(): array {
    return [
        'zero' => 'S1-STT04-001_Zero_L_L_die',
        'alley_thug' => 'S1-AZK01-004_Alley-Thug_E_C_die',
        'courier' => 'S1-AZK01-003_Black-Jade-Courier_E_C_die',
        'glass_blower' => 'S1-AZK01-056_Glass-Blower-Hokuto_E_C_die',
        'warlord' => 'S1-AZK01-058_Black-Jade-Warlord_E_C_die',
        'spice' => 'S1-AZK01-059_Spice_E_UC_die',
        'scarlett' => 'S1-AZK01-060_Scarlett_E_R_die',
        'pekiro' => 'S1-AZK01-062_Pekiro_E_R_die',
        'fire_orb' => 'S1-AZK01-065_Fire-Orb_S_C_die',
        'rushfire_gate' => 'S1-AZK01-122_Rushfire-Gate_G_G_die',
        'cinderwake' => 'S1-STT04-003_Cinderwake-Seer_E_UC_die',
        'kindler' => 'S1-STT04-004_Fanatic-Kindler_E_C_die',
        'ruby' => 'S1-STT04-005_Ruby_E_C_die',
        'black_jade_dagger' => 'S1-STT01-013_Black-Jade-Dagger_W_C_die',
        'detonation_pact' => 'S1-STT04-015_Detonation-Pact_S_C_die',
        'collateral_burst' => 'S1-STT04-016_Collateral-Burst_S_UC_die',
    ];
}

function AzukiZeroHeuristicVariable($name, $default = '') {
    if(!class_exists('DecisionQueueController') || !method_exists('DecisionQueueController', 'GetVariable')) return $default;
    $value = DecisionQueueController::GetVariable(strval($name));
    return ($value === null || $value === '') ? $default : $value;
}

function AzukiZeroHeuristicPendingHandler($player): string {
    if(!function_exists('GetDecisionQueue')) return '';
    $queue = GetDecisionQueue(intval($player));
    if(!is_array($queue)) return '';
    foreach($queue as $decision) {
        if(!is_object($decision) || strtoupper(strval($decision->Type ?? '')) !== 'CUSTOM') continue;
        return strval($decision->Param ?? '');
    }
    return '';
}

function AzukiZeroHeuristicDecisionSourceCardID($state): string {
    $handler = AzukiZeroHeuristicPendingHandler(intval($state['player'] ?? 0));
    if(str_starts_with($handler, 'PLAY_ENTITY_DEST|')) {
        $sourceMZ = explode('|', $handler, 2)[1] ?? '';
        $obj = AzukiZeroHeuristicObject($sourceMZ, intval($state['player'] ?? 0));
        $cardID = strval($obj->CardID ?? '');
        if($cardID !== '') return $cardID;
    }
    if(str_starts_with($handler, 'PLAY_WEAPON_TARGET|')) {
        $sourceMZ = explode('|', $handler, 2)[1] ?? '';
        $obj = AzukiZeroHeuristicObject($sourceMZ, intval($state['player'] ?? 0));
        $cardID = strval($obj->CardID ?? '');
        if($cardID !== '') return $cardID;
    }
    if(preg_match('/^(S1-[^:|]+):/', $handler, $matches) === 1) return strval($matches[1]);
    return strval(AzukiZeroHeuristicVariable('mzIDCardID'));
}

function AzukiZeroHeuristicActionKey($action, $legal): string {
    if(isset($action['_semanticKey'])) return strval($action['_semanticKey']);
    if(function_exists('BridgeRlSemanticActionKey')) return strval(BridgeRlSemanticActionKey($action, $legal, 'semantic-v2'));
    return strval($action['cardID'] ?? '');
}

function AzukiZeroHeuristicActionCardID($action): string {
    return strval($action['resolvedCardID'] ?? '');
}

function AzukiZeroHeuristicMZFromAction($action): string {
    $raw = strval($action['cardID'] ?? '');
    $separator = strpos($raw, '!');
    return $separator === false ? $raw : substr($raw, 0, $separator);
}

function AzukiZeroHeuristicObject($mzID, $player = 0) {
    if(!function_exists('GetZoneObject') || !is_string($mzID) || $mzID === '') return null;
    $player = intval($player);
    if($player === 1 || $player === 2) {
        $originalPlayerID = $GLOBALS['playerID'] ?? null;
        $GLOBALS['playerID'] = $player;
        try {
            $obj = GetZoneObject($mzID);
        } finally {
            if($originalPlayerID === null) unset($GLOBALS['playerID']);
            else $GLOBALS['playerID'] = $originalPlayerID;
        }
    } else {
        $obj = GetZoneObject($mzID);
    }
    return is_object($obj) && empty($obj->removed) ? $obj : null;
}

function AzukiZeroHeuristicCardType($cardID): string {
    return function_exists('CardType') ? strtoupper(strval(CardType($cardID))) : '';
}

function AzukiZeroHeuristicCardAttack($player, $obj, $cardID = ''): int {
    if(is_object($obj)) {
        if(function_exists('ResolveEntityAttackValue')) return max(0, intval(ResolveEntityAttackValue($player, $obj)));
        $cardID = strval($obj->CardID ?? $cardID);
    }
    return function_exists('CardAttack') ? max(0, intval(CardAttack($cardID))) : 0;
}

function AzukiZeroHeuristicRemainingHP($player, $obj, $cardID = ''): int {
    if(is_object($obj)) {
        $cardID = strval($obj->CardID ?? $cardID);
        $health = function_exists('ResolveEntityHealthValue')
            ? intval(ResolveEntityHealthValue($player, $obj))
            : (function_exists('CardHealth') ? intval(CardHealth($cardID)) : 0);
        return max(0, $health - intval($obj->Damage ?? 0));
    }
    return function_exists('CardHealth') ? max(0, intval(CardHealth($cardID))) : 0;
}

function AzukiZeroHeuristicLiveZone($zone): array {
    if(!is_array($zone)) return [];
    return array_values(array_filter($zone, fn($obj) => is_object($obj) && empty($obj->removed)));
}

function AzukiZeroHeuristicPlayerZone($getter, $player): array {
    if(!function_exists($getter)) return [];
    return AzukiZeroHeuristicLiveZone($getter(intval($player)));
}

function AzukiZeroHeuristicHasCard($zone, $cardID): bool {
    foreach($zone as $obj) {
        if(strval($obj->CardID ?? '') === strval($cardID)) return true;
    }
    return false;
}

function AzukiZeroHeuristicHasTurnEffect($obj, $effectID): bool {
    if(!is_object($obj)) return false;
    if(function_exists('HasTurnEffect')) return HasTurnEffect($obj, strval($effectID));
    $effects = is_array($obj->TurnEffects ?? null) ? $obj->TurnEffects : [];
    return in_array(strval($effectID), array_map('strval', $effects), true);
}

function AzukiZeroHeuristicPekiroRedirectTargetScore($state, $targetMZ, $target, $cardID = ''): float {
    if(!is_object($target)) return -10000;
    $player = intval($state['player'] ?? 0);
    $opponent = intval($state['opponent'] ?? 0);
    $cardID = strval($target->CardID ?? $cardID);
    if(AzukiZeroHeuristicCardType($cardID) !== 'ENTITY') return -10000;
    if(function_exists('IsImmuneToCardEffectDamage') && IsImmuneToCardEffectDamage($target)) return -10000;

    if(str_starts_with(strval($targetMZ), 'theirGarden-')) {
        $remaining = AzukiZeroHeuristicRemainingHP($opponent, $target, $cardID);
        $attack = AzukiZeroHeuristicCardAttack($opponent, $target, $cardID);
        $killBonus = $remaining > 0 && $remaining <= 1 ? 500 : 0;
        return 750 + $killBonus + ($attack * 60) - ($remaining * 10);
    }

    if(str_starts_with(strval($targetMZ), 'myGarden-')
        && AzukiZeroHeuristicHasTurnEffect($target, 'RUSHFIRE_SAC_EOT')) {
        $remaining = AzukiZeroHeuristicRemainingHP($player, $target, $cardID);
        if($remaining <= 1) return -10000;
        return 500 - ($remaining * 5);
    }
    return -10000;
}

function AzukiZeroHeuristicPekiroRedirectValue($state, $pekiroMZ = ''): float {
    $player = intval($state['player'] ?? 0);
    $opponent = intval($state['opponent'] ?? 0);
    $best = -10000;
    foreach(AzukiZeroHeuristicPlayerZone('GetGarden', $opponent) as $index => $obj) {
        $mzIndex = intval($obj->mzIndex ?? $index);
        $best = max($best, AzukiZeroHeuristicPekiroRedirectTargetScore(
            $state,
            'theirGarden-' . $mzIndex,
            $obj
        ));
    }
    foreach(AzukiZeroHeuristicPlayerZone('GetGarden', $player) as $index => $obj) {
        $targetMZ = 'myGarden-' . intval($obj->mzIndex ?? $index);
        if($targetMZ === strval($pekiroMZ)) continue;
        $best = max($best, AzukiZeroHeuristicPekiroRedirectTargetScore($state, $targetMZ, $obj));
    }
    return $best;
}

function AzukiZeroHeuristicCanEmpowerPekiro($state, $pekiro, $pekiroMZ): bool {
    $ids = AzukiZeroHeuristicCardIDs();
    if(!is_object($pekiro) || strval($pekiro->CardID ?? '') !== $ids['pekiro']) return false;
    if(AzukiZeroHeuristicHasTurnEffect($pekiro, 'PEKIRO_USED')) return false;
    if(!str_starts_with(strval($pekiroMZ), 'myGarden-')) return false;
    if(function_exists('CanAttackWith')
        && !CanAttackWith(intval($state['player'] ?? 0), strval($pekiroMZ))) return false;
    return AzukiZeroHeuristicPekiroRedirectValue($state, strval($pekiroMZ)) > 0;
}

function AzukiZeroHeuristicHasValidEmpowerTarget($player): bool {
    if(function_exists('GetTurnPlayer') && intval(GetTurnPlayer()) !== intval($player)) return false;
    if(function_exists('GetCurrentPhase') && strval(GetCurrentPhase()) !== 'MAIN') return false;
    if(function_exists('HasPendingAttackResponse') && HasPendingAttackResponse()) return false;
    $garden = AzukiZeroHeuristicPlayerZone('GetGarden', $player);
    $state = [
        'player' => intval($player),
        'opponent' => intval($player) === 1 ? 2 : 1,
    ];
    foreach($garden as $index => $obj) {
        $cardID = strval($obj->CardID ?? '');
        if(AzukiZeroHeuristicCardType($cardID) !== 'ENTITY') continue;
        $mzID = 'myGarden-' . intval($obj->mzIndex ?? $index);
        if(function_exists('CanAttackWith') && !CanAttackWith(intval($player), $mzID)) continue;
        if(AzukiZeroHeuristicRemainingHP($player, $obj, $cardID) <= 1
            && !AzukiZeroHeuristicCanEmpowerPekiro($state, $obj, $mzID)) continue;
        return true;
    }
    return false;
}

function AzukiZeroHeuristicHasCollateralSetup($player): bool {
    return AzukiZeroHeuristicCollateralCapacity($player) > 0;
}

function AzukiZeroHeuristicCollateralCapacity($player): int {
    $capacity = 0;
    foreach(AzukiZeroHeuristicPlayerZone('GetGarden', $player) as $obj) {
        $cardID = strval($obj->CardID ?? '');
        if(AzukiZeroHeuristicCardType($cardID) !== 'ENTITY') continue;
        $capacity += max(0, AzukiZeroHeuristicRemainingHP($player, $obj, $cardID) - 1);
    }
    return $capacity;
}

function AzukiZeroHeuristicLiveReadyAttack($player, $fallback = 0): int {
    if(!function_exists('GetGarden')) return max(0, intval($fallback));
    $garden = GetGarden(intval($player));
    if(!is_array($garden)) return max(0, intval($fallback));
    $total = 0;
    foreach($garden as $index => $obj) {
        if(!is_object($obj) || !empty($obj->removed)) continue;
        $cardID = strval($obj->CardID ?? '');
        $type = AzukiZeroHeuristicCardType($cardID);
        if($type !== 'ENTITY' && $type !== 'LEADER') continue;
        $mzID = 'myGarden-' . intval($index);
        if(function_exists('CanAttackWith') && !CanAttackWith(intval($player), $mzID)) continue;
        $total += AzukiZeroHeuristicCardAttack(intval($player), $obj, $cardID);
    }
    return $total;
}

function AzukiZeroHeuristicZeroBoostAvailable($state): bool {
    $ids = AzukiZeroHeuristicCardIDs();
    $player = intval($state['player'] ?? 0);
    if(intval($state['myLife'] ?? 0) <= 1) return false;
    if(!AzukiZeroHeuristicHasValidEmpowerTarget($player)) return false;
    foreach(AzukiZeroHeuristicPlayerZone('GetGarden', $player) as $obj) {
        if(strval($obj->CardID ?? '') !== $ids['zero']) continue;
        if(function_exists('HasTurnEffect') && HasTurnEffect($obj, 'STT04_ZERO_USED')) return false;
        return true;
    }
    return false;
}

function AzukiZeroHeuristicReachProfile($cardID, $state): ?array {
    $ids = AzukiZeroHeuristicCardIDs();
    $profiles = [
        $ids['fire_orb'] => ['damage' => 5, 'selfDamage' => 3, 'collateralDamage' => 0],
        $ids['detonation_pact'] => ['damage' => 2, 'selfDamage' => 1, 'collateralDamage' => 0],
        $ids['collateral_burst'] => ['damage' => 2, 'selfDamage' => 0, 'collateralDamage' => 1],
        // Dagger is combat reach rather than a spell: its base weapon attack
        // and optional On Play bonus add two damage to an existing attacker.
        $ids['black_jade_dagger'] => ['damage' => 2, 'selfDamage' => 1, 'collateralDamage' => 0],
    ];
    if(!isset($profiles[$cardID])) return null;
    if($cardID === $ids['collateral_burst']
        && !AzukiZeroHeuristicHasCollateralSetup(intval($state['player'] ?? 0))) return null;
    if($cardID === $ids['black_jade_dagger']
        && intval($state['myReadyAttack'] ?? 0) <= 0) return null;
    return $profiles[$cardID];
}

function AzukiZeroHeuristicReachCards($state): array {
    $cards = [];
    $player = intval($state['player'] ?? 0);
    foreach(AzukiZeroHeuristicPlayerZone('GetHand', $player) as $obj) {
        $cardID = strval($obj->CardID ?? '');
        $profile = AzukiZeroHeuristicReachProfile($cardID, $state);
        if(!is_array($profile)) continue;
        $cost = function_exists('EffectivePlayCost')
            ? max(0, intval(EffectivePlayCost($player, $cardID, $obj)))
            : (function_exists('CardCost') ? max(0, intval(CardCost($cardID))) : 0);
        $cards[] = [
            'cardID' => $cardID,
            'cost' => $cost,
            'damage' => intval($profile['damage'] ?? 0),
            'selfDamage' => intval($profile['selfDamage'] ?? 0),
            'collateralDamage' => intval($profile['collateralDamage'] ?? 0),
        ];
    }
    if(AzukiZeroHeuristicZeroBoostAvailable($state)) {
        $cards[] = [
            'cardID' => 'ZERO_EMPOWER_REACH',
            'cost' => 0,
            'damage' => 1,
            'selfDamage' => 1,
            'collateralDamage' => 0,
        ];
    }
    return $cards;
}

function AzukiZeroHeuristicMaxReachDamage($cards, $availableIKZ, $myLife, $collateralCapacity): int {
    $availableIKZ = max(0, intval($availableIKZ));
    $maxSelfDamage = max(0, intval($myLife) - 1);
    $collateralCapacity = max(0, intval($collateralCapacity));
    $best = ['0:0:0' => 0];
    foreach($cards as $card) {
        $next = $best;
        $cost = max(0, intval($card['cost'] ?? 0));
        $selfDamage = max(0, intval($card['selfDamage'] ?? 0));
        $collateralDamage = max(0, intval($card['collateralDamage'] ?? 0));
        $damage = max(0, intval($card['damage'] ?? 0));
        foreach($best as $key => $currentDamage) {
            [$spent, $selfSpent, $collateralSpent] = array_map('intval', explode(':', strval($key), 3));
            $newSpent = $spent + $cost;
            $newSelfSpent = $selfSpent + $selfDamage;
            $newCollateralSpent = $collateralSpent + $collateralDamage;
            if($newSpent > $availableIKZ
                || $newSelfSpent > $maxSelfDamage
                || $newCollateralSpent > $collateralCapacity) continue;
            $newKey = $newSpent . ':' . $newSelfSpent . ':' . $newCollateralSpent;
            $next[$newKey] = max(intval($next[$newKey] ?? 0), intval($currentDamage) + $damage);
        }
        $best = $next;
    }
    return empty($best) ? 0 : max(array_map('intval', array_values($best)));
}

function AzukiZeroHeuristicLethalPlan($state, $forcedCardID = ''): array {
    $cards = AzukiZeroHeuristicReachCards($state);
    $availableIKZ = max(0, intval($state['availableIKZ'] ?? 0));
    $myLife = max(0, intval($state['myLife'] ?? 0));
    $forcedDamage = 0;
    $forcedCost = 0;
    $forcedSelfDamage = 0;
    $forcedCollateralDamage = 0;

    if($forcedCardID !== '') {
        $found = false;
        foreach($cards as $index => $card) {
            if(strval($card['cardID'] ?? '') !== strval($forcedCardID)) continue;
            $forcedDamage = intval($card['damage'] ?? 0);
            $forcedCost = intval($card['cost'] ?? 0);
            $forcedSelfDamage = intval($card['selfDamage'] ?? 0);
            $forcedCollateralDamage = intval($card['collateralDamage'] ?? 0);
            unset($cards[$index]);
            $found = true;
            break;
        }
        if(!$found
            || $forcedCost > $availableIKZ
            || $forcedSelfDamage >= $myLife
            || $forcedCollateralDamage > AzukiZeroHeuristicCollateralCapacity(intval($state['player'] ?? 0))) {
            return ['lethal' => false, 'damage' => 0, 'burnDamage' => 0];
        }
    }

    $remainingLife = $myLife - $forcedSelfDamage;
    $burnDamage = $forcedDamage + AzukiZeroHeuristicMaxReachDamage(
        array_values($cards),
        $availableIKZ - $forcedCost,
        $remainingLife,
        AzukiZeroHeuristicCollateralCapacity(intval($state['player'] ?? 0)) - $forcedCollateralDamage
    );
    $totalDamage = intval($state['myReadyAttack'] ?? 0) + $burnDamage;
    return [
        'lethal' => intval($state['theirLife'] ?? 20) > 0
            && $totalDamage >= intval($state['theirLife'] ?? 20),
        'damage' => $totalDamage,
        'burnDamage' => $burnDamage,
    ];
}

function AzukiZeroHeuristicHasNonReachAction($actions, $legal, $state): bool {
    $ids = AzukiZeroHeuristicCardIDs();
    $reachCardIDs = [
        $ids['fire_orb'],
        $ids['detonation_pact'],
        $ids['collateral_burst'],
        $ids['black_jade_dagger'],
    ];
    foreach($actions as $candidate) {
        if(!is_array($candidate)) continue;
        $key = AzukiZeroHeuristicActionKey($candidate, $legal);
        if(str_starts_with($key, 'pass:')) continue;
        if(str_starts_with($key, 'play:')
            && in_array(AzukiZeroHeuristicActionCardID($candidate), $reachCardIDs, true)) continue;
        if(str_starts_with($key, 'activate:')
            && AzukiZeroHeuristicActionCardID($candidate) === $ids['zero']) {
            $lethalBoost = intval($state['theirLife'] ?? 20)
                <= intval($state['myReadyAttack'] ?? 0) + 1;
            if((intval($state['myLife'] ?? 20) <= 6 && !$lethalBoost)
                || !AzukiZeroHeuristicHasValidEmpowerTarget(intval($state['player'] ?? 0))) continue;
        }
        return true;
    }
    return false;
}

function AzukiZeroHeuristicKindlerShouldSacrifice($state): bool {
    $ids = AzukiZeroHeuristicCardIDs();
    $player = intval($state['player'] ?? 0);
    $opponent = intval($state['opponent'] ?? 0);
    $kindlerAttack = function_exists('CardAttack') ? max(1, intval(CardAttack($ids['kindler']))) : 1;
    $sourceMZ = strval(AzukiZeroHeuristicVariable('mzID'));
    $sourceObj = AzukiZeroHeuristicObject($sourceMZ, $player);
    if(is_object($sourceObj) && strval($sourceObj->CardID ?? '') === $ids['kindler']) {
        $kindlerAttack = AzukiZeroHeuristicCardAttack($player, $sourceObj, $ids['kindler']);
    }

    foreach(AzukiZeroHeuristicPlayerZone('GetGarden', $opponent) as $obj) {
        $cardID = strval($obj->CardID ?? '');
        if(AzukiZeroHeuristicCardType($cardID) !== 'ENTITY') continue;
        if(AzukiZeroHeuristicRemainingHP($opponent, $obj, $cardID) > 1) continue;
        $targetAttack = AzukiZeroHeuristicCardAttack($opponent, $obj, $cardID);
        $incomingAttack = max(
            intval($state['theirReadyAttack'] ?? 0),
            intval($state['theirBoardAttack'] ?? 0)
        );
        $preventsLethal = $incomingAttack >= intval($state['myLife'] ?? 20)
            && $incomingAttack - $targetAttack < intval($state['myLife'] ?? 20);
        // Preserve Kindler when trading it merely removes an equal-or-smaller
        // attacker; its own future attacks are worth at least as much.
        if($preventsLethal || $targetAttack > $kindlerAttack) return true;
    }
    return false;
}

function AzukiZeroHeuristicState($snapshot, $player): array {
    $player = intval($player);
    $opponent = $player === 1 ? 2 : 1;
    $compact = is_array($snapshot['azukiCompactState'] ?? null) ? $snapshot['azukiCompactState'] : [];
    $mine = is_array($compact['p' . $player] ?? null) ? $compact['p' . $player] : [];
    $theirs = is_array($compact['p' . $opponent] ?? null) ? $compact['p' . $opponent] : [];
    return [
        'player' => $player,
        'opponent' => $opponent,
        'myLife' => intval($mine['remainingLife'] ?? 20),
        'theirLife' => intval($theirs['remainingLife'] ?? 20),
        'availableIKZ' => intval($mine['availableIKZ'] ?? 0),
        'ikzToken' => array_key_exists('ikzToken', $mine)
            ? intval($mine['ikzToken'])
            : (function_exists('GetAccessibleIKZTokenCount') ? intval(GetAccessibleIKZTokenCount($player)) : 0),
        'myReadyAttack' => AzukiZeroHeuristicLiveReadyAttack($player, intval($mine['readyAttack'] ?? 0)),
        'theirReadyAttack' => AzukiZeroHeuristicLiveReadyAttack($opponent, intval($theirs['readyAttack'] ?? 0)),
        'myBoardAttack' => intval($mine['boardAttack'] ?? $mine['readyAttack'] ?? 0),
        'theirBoardAttack' => intval($theirs['boardAttack'] ?? $theirs['readyAttack'] ?? 0),
        'myBoardCount' => intval($mine['gardenCount'] ?? 0) + intval($mine['alleyCount'] ?? 0),
        'theirBoardCount' => intval($theirs['gardenCount'] ?? 0) + intval($theirs['alleyCount'] ?? 0),
    ];
}

function AzukiZeroHeuristicPlayScore($cardID, $state, $legal): float {
    $ids = AzukiZeroHeuristicCardIDs();
    $base = [
        $ids['ruby'] => 350,
        $ids['glass_blower'] => 345,
        $ids['alley_thug'] => 340,
        $ids['cinderwake'] => 325,
        $ids['courier'] => 315,
        $ids['kindler'] => 300,
        $ids['spice'] => 370,
        $ids['pekiro'] => 365,
        $ids['scarlett'] => 330,
        $ids['warlord'] => 290,
        $ids['black_jade_dagger'] => 265,
        $ids['collateral_burst'] => 250,
        $ids['detonation_pact'] => 245,
        $ids['fire_orb'] => 220,
    ];
    $score = floatval($base[$cardID] ?? 200);
    $cost = function_exists('CardCost') ? max(0, intval(CardCost($cardID))) : 0;

    // The logs strongly favor one-cost setup cards on the first two active turns.
    if(intval($state['availableIKZ'] ?? 0) <= 2 && $cost <= 1) $score += 90;
    if($cardID === $ids['pekiro'] && intval($state['availableIKZ'] ?? 0) >= 3) $score += 55;
    if($cardID === $ids['spice'] && intval($state['availableIKZ'] ?? 0) >= 2) $score += 45;

    $hand = AzukiZeroHeuristicPlayerZone('GetHand', intval($state['player'] ?? 0));
    if($cardID === $ids['pekiro'] && (AzukiZeroHeuristicHasCard($hand, $ids['warlord']) || AzukiZeroHeuristicHasCard($hand, $ids['spice']))) $score += 80;
    if($cardID === $ids['ruby'] && AzukiZeroHeuristicHasCard($hand, $ids['cinderwake'])) $score += 55;
    if($cardID === $ids['glass_blower'] && AzukiZeroHeuristicHasCard($hand, $ids['cinderwake'])) $score += 55;

    if($cardID === $ids['fire_orb']) {
        if(intval($state['myLife'] ?? 0) <= 5) $score -= 500;
        if(intval($state['theirLife'] ?? 20) <= 5) $score += 1000;
    }
    if(in_array($cardID, [$ids['collateral_burst'], $ids['detonation_pact']], true)
        && intval($state['theirBoardCount'] ?? 0) <= 1) $score -= 140;
    // With the current generated Decision Queue, passing Collateral's first
    // optional chooser skips its continuation. Only cast it when a friendly
    // entity can survive the enabling point of damage.
    if($cardID === $ids['collateral_burst']
        && !AzukiZeroHeuristicHasCollateralSetup(intval($state['player'] ?? 0))) return -10000;

    if(strval($legal['kind'] ?? '') === 'azuki-attack-response-fsm') {
        $incoming = intval($state['theirReadyAttack'] ?? 0);
        $score += $incoming >= intval($state['myLife'] ?? 0) ? 450 : ($incoming >= 4 ? 100 : -180);
    }
    return $score;
}

function AzukiZeroHeuristicPortalScore($cardID): float {
    $ids = AzukiZeroHeuristicCardIDs();
    $scores = [
        $ids['pekiro'] => 520,
        $ids['glass_blower'] => 500,
        $ids['ruby'] => 495,
        $ids['spice'] => 480,
        $ids['warlord'] => 410,
        $ids['alley_thug'] => 360,
        $ids['cinderwake'] => 330,
        $ids['kindler'] => 320,
        $ids['courier'] => 315,
    ];
    return floatval($scores[$cardID] ?? 250);
}

function AzukiZeroHeuristicRushfirePlayScore($cardID, $state): float {
    $ids = AzukiZeroHeuristicCardIDs();
    $scores = [
        $ids['warlord'] => 610,
        $ids['cinderwake'] => 585,
        $ids['courier'] => 575,
        $ids['kindler'] => 550,
        $ids['alley_thug'] => 535,
        $ids['spice'] => 525,
        $ids['glass_blower'] => 500,
        $ids['ruby'] => 495,
    ];
    $score = floatval($scores[$cardID] ?? 400);
    if($cardID === $ids['kindler'] && intval($state['theirBoardCount'] ?? 0) > 0) $score += 45;
    return $score;
}

function AzukiZeroHeuristicModelScore($stateLogits, $action, $legal): float {
    if(!is_array($stateLogits)) return 0.0;
    $key = AzukiZeroHeuristicActionKey($action, $legal);
    return floatval($stateLogits[$key] ?? 0.0);
}

function AzukiZeroHeuristicDecisionScore($action, $legal, $state): float {
    $ids = AzukiZeroHeuristicCardIDs();
    $type = strtoupper(strval($legal['decisionType'] ?? ''));
    $tooltip = strtolower(str_replace('_', ' ', strval($legal['decisionTooltipRaw'] ?? $legal['decisionTooltip'] ?? '')));
    $choice = strval($action['cardID'] ?? '');
    $choiceUpper = strtoupper($choice);
    $cardID = AzukiZeroHeuristicActionCardID($action);

    if($type === 'YESNO') {
        if(str_contains($tooltip, 'mulligan') || str_contains(strtolower(strval($legal['decisionParam'] ?? '')), 'review:myhand')) {
            $goodOpeners = [$ids['ruby'], $ids['glass_blower'], $ids['alley_thug'], $ids['cinderwake'], $ids['courier'], $ids['kindler']];
            $hand = AzukiZeroHeuristicPlayerZone('GetHand', intval($state['player'] ?? 0));
            $keep = false;
            foreach($hand as $obj) {
                if(in_array(strval($obj->CardID ?? ''), $goodOpeners, true)) { $keep = true; break; }
            }
            return $choiceUpper === ($keep ? 'NO' : 'YES') ? 1000 : -1000;
        }
        $source = AzukiZeroHeuristicDecisionSourceCardID($state);
        if($source === $ids['warlord']) {
            $garden = AzukiZeroHeuristicPlayerZone('GetGarden', intval($state['player'] ?? 0));
            return $choiceUpper === (count($garden) > 1 ? 'YES' : 'NO') ? 900 : -900;
        }
        if($source === $ids['kindler']) {
            $sacrifice = AzukiZeroHeuristicKindlerShouldSacrifice($state);
            return $choiceUpper === ($sacrifice ? 'YES' : 'NO') ? 850 : -850;
        }
        if($source === $ids['black_jade_dagger']) {
            $remainingBurn = AzukiZeroHeuristicLethalPlan($state);
            $payForLethal = intval($state['myLife'] ?? 0) > 1
                && intval($state['myReadyAttack'] ?? 0) + 1 + intval($remainingBurn['burnDamage'] ?? 0)
                    >= intval($state['theirLife'] ?? 20);
            return $choiceUpper === ($payForLethal ? 'YES' : 'NO') ? 850 : -850;
        }
        if($source === $ids['scarlett']) return $choiceUpper === 'YES' ? 700 : 0;
        return $choiceUpper === 'NO' ? 20 : 0;
    }

    if($type === 'CHOOSEZONE') {
        $source = AzukiZeroHeuristicDecisionSourceCardID($state);
        $alleyCards = [$ids['pekiro'], $ids['glass_blower'], $ids['ruby'], $ids['spice'], $ids['warlord']];
        $preferred = in_array($source, $alleyCards, true) ? 'MYALLEY' : 'MYGARDEN';
        return strtoupper($choice) === $preferred ? 900 : 0;
    }

    if($type === 'MZMODAL' && str_contains(strtolower(strval($legal['decisionParam'] ?? '')), 'go_first')) {
        return (str_contains(strtolower($choice), 'first') || $choice === '0') ? 900 : 0;
    }

    $handler = AzukiZeroHeuristicPendingHandler(intval($state['player'] ?? 0));
    if(str_starts_with($handler, 'PEKIRO_REDIRECT_DAMAGE')) {
        if($choice === '-' || $choiceUpper === 'PASS') return 0;
        $targetMZ = AzukiZeroHeuristicMZFromAction($action);
        $target = AzukiZeroHeuristicObject($targetMZ, intval($state['player'] ?? 0));
        return AzukiZeroHeuristicPekiroRedirectTargetScore($state, $targetMZ, $target, $cardID);
    }

    if($choice === '-' || $choiceUpper === 'PASS') return -500;
    if(str_contains($tooltip, 'select entity to portal')) return AzukiZeroHeuristicPortalScore($cardID);

    $gateSource = strval(AzukiZeroHeuristicVariable('entityMZCardID'));
    if(str_starts_with($handler, $ids['rushfire_gate'] . ':')
        && $gateSource !== ''
        && str_starts_with(AzukiZeroHeuristicMZFromAction($action), 'myHand-')) {
        return AzukiZeroHeuristicRushfirePlayScore($cardID, $state);
    }

    $targetMZ = AzukiZeroHeuristicMZFromAction($action);
    $target = AzukiZeroHeuristicObject($targetMZ, intval($state['player'] ?? 0));
    $targetPlayer = str_starts_with($targetMZ, 'their') ? intval($state['opponent']) : intval($state['player']);
    $targetType = AzukiZeroHeuristicCardType($cardID);
    $source = AzukiZeroHeuristicDecisionSourceCardID($state);

    if(str_contains($tooltip, 'attack target')) {
        $attackerMZ = strval(AzukiZeroHeuristicVariable('CombatTarget'));
        $attacker = AzukiZeroHeuristicObject($attackerMZ, intval($state['player'] ?? 0));
        $attack = AzukiZeroHeuristicCardAttack(intval($state['player']), $attacker);
        if($targetType === 'LEADER') {
            if($attack >= intval($state['theirLife'] ?? 20)) return 10000;
            return 600 + ($attack * 12) - (intval($state['theirBoardCount'] ?? 0) * 8);
        }
        $remaining = AzukiZeroHeuristicRemainingHP($targetPlayer, $target, $cardID);
        $targetAttack = AzukiZeroHeuristicCardAttack($targetPlayer, $target, $cardID);
        return 260 + ($attack >= $remaining ? 220 : -100) + ($targetAttack * 25) - ($remaining * 8);
    }

    if(str_starts_with($targetMZ, 'their')) {
        $remaining = AzukiZeroHeuristicRemainingHP($targetPlayer, $target, $cardID);
        $targetAttack = AzukiZeroHeuristicCardAttack($targetPlayer, $target, $cardID);
        $damage = 1;
        if($source === $ids['fire_orb']) $damage = 5;
        else if($source === $ids['collateral_burst']) $damage = 2;
        else if($source === $ids['detonation_pact']) $damage = 2;
        $isBurn = in_array($source, [$ids['fire_orb'], $ids['collateral_burst'], $ids['detonation_pact']], true);
        if($isBurn && $targetType === 'LEADER') {
            if($damage >= intval($state['theirLife'] ?? 20)) return 10000;
            $remainingPlan = AzukiZeroHeuristicLethalPlan($state);
            $wholeTurnDamage = intval($state['myReadyAttack'] ?? 0)
                + $damage
                + intval($remainingPlan['burnDamage'] ?? 0);
            if($wholeTurnDamage >= intval($state['theirLife'] ?? 20)) return 9000 + ($damage * 10);
            $postDamageLife = max(0, intval($state['theirLife'] ?? 20) - $damage);
            return 700 + ($damage * 60) + ($postDamageLife <= 5 ? 300 : 0);
        }
        if($source === $ids['kindler']) {
            if($targetType !== 'ENTITY' || $remaining > 1) return -10000;
            return 700 + ($targetAttack * 80);
        }
        $killBonus = $remaining > 0 && $damage >= $remaining ? 260 : 0;
        if($isBurn && $targetType !== 'LEADER') {
            $overkill = max(0, $damage - $remaining);
            $incomingAttack = max(
                intval($state['theirReadyAttack'] ?? 0),
                intval($state['theirBoardAttack'] ?? 0)
            );
            $preventsLethal = $incomingAttack >= intval($state['myLife'] ?? 20)
                && $incomingAttack - $targetAttack < intval($state['myLife'] ?? 20);
            return 350 + $killBonus + ($targetAttack * 35) - ($overkill * 60) + ($preventsLethal ? 1500 : 0);
        }
        if($targetType !== 'LEADER' && $killBonus > 0 && intval($state['myLife'] ?? 20) <= 6) $killBonus += 500;
        return 500 + $killBonus + ($targetAttack * 30) - ($remaining * 5);
    }

    if(str_starts_with($targetMZ, 'my')) {
        $remaining = AzukiZeroHeuristicRemainingHP($targetPlayer, $target, $cardID);
        $ready = is_object($target) && intval($target->Status ?? 2) === 2;
        $attack = AzukiZeroHeuristicCardAttack($targetPlayer, $target, $cardID);
        if($source === $ids['collateral_burst']) {
            if($remaining <= 1) return -10000;
            // Prefer the least valuable survivor; taking this damage is the
            // current runtime's cost of reaching Collateral's enemy target.
            return 650 - ($attack * 30) - ($remaining * 5);
        }
        if($source === $ids['zero']) {
            $canAttack = str_starts_with($targetMZ, 'myGarden-') && $ready;
            if($canAttack && function_exists('CanAttackWith')) {
                $canAttack = CanAttackWith(intval($state['player']), $targetMZ);
            }
            if(!$canAttack) return -10000;
            if($cardID === $ids['pekiro']
                && AzukiZeroHeuristicCanEmpowerPekiro($state, $target, $targetMZ)) {
                return 1100 + AzukiZeroHeuristicPekiroRedirectValue($state, $targetMZ) + ($attack * 20);
            }
            if($remaining <= 1) return -10000;
        }
        if($source === $ids['kindler']) return -10000;
        $score = 300 + ($ready ? 100 : 0) + ($attack * 20);
        if($cardID === $ids['spice']) $score += 220;
        if($source === $ids['warlord'] && $targetType === 'LEADER') $score += 120;
        return $score;
    }

    // Search/reveal choices favor the cards that most often completed logged lines.
    return AzukiZeroHeuristicRushfirePlayScore($cardID, $state);
}

function AzukiZeroHeuristicActionScore($action, $actions, $legal, $snapshot, $player): float {
    $ids = AzukiZeroHeuristicCardIDs();
    $state = AzukiZeroHeuristicState($snapshot, $player);
    $key = AzukiZeroHeuristicActionKey($action, $legal);
    $cardID = AzukiZeroHeuristicActionCardID($action);
    $kind = strval($legal['kind'] ?? '');

    if(strval($legal['decisionType'] ?? '') !== '') return AzukiZeroHeuristicDecisionScore($action, $legal, $state);

    if(str_starts_with($key, 'pass:')) {
        $nonPass = 0;
        foreach($actions as $candidate) {
            if(!str_starts_with(AzukiZeroHeuristicActionKey($candidate, $legal), 'pass:')) ++$nonPass;
        }
        if($kind === 'azuki-attack-response-fsm') {
            $incoming = intval($state['theirReadyAttack'] ?? 0);
            return $nonPass > 0 && $incoming >= intval($state['myLife'] ?? 20) ? -500 : 50;
        }
        return $nonPass > 0 ? -1000 : 0;
    }

    if(str_starts_with($key, 'attack:')) {
        $obj = AzukiZeroHeuristicObject(AzukiZeroHeuristicMZFromAction($action), $player);
        $attack = AzukiZeroHeuristicCardAttack($player, $obj, $cardID);
        $score = 650 + ($attack * 20);
        if($cardID === $ids['warlord']) $score += 180;
        if($cardID === $ids['alley_thug']) $score += 55;
        if($attack >= intval($state['theirLife'] ?? 20)) $score += 5000;
        return $score;
    }

    if(str_starts_with($key, 'activate:')) {
        if($cardID === $ids['zero']) {
            $myLife = intval($state['myLife'] ?? 20);
            $theirLife = intval($state['theirLife'] ?? 20);
            $lethalBoost = $theirLife > 0 && $theirLife <= intval($state['myReadyAttack'] ?? 0) + 1;
            // These must score below the normal pass action (-1000), otherwise
            // checkpoint tie-breaking can still choose an invalid activation.
            if($myLife <= 6 && !$lethalBoost) return -10000;
            if(!AzukiZeroHeuristicHasValidEmpowerTarget($player)) return -10000;
            return 780;
        }
        if($cardID === $ids['rushfire_gate']) return 850;
        return 360;
    }

    if(str_starts_with($key, 'play:')) {
        $reachProfile = AzukiZeroHeuristicReachProfile($cardID, $state);
        if(is_array($reachProfile)) {
            $forcedPlan = AzukiZeroHeuristicLethalPlan($state, $cardID);
            if(!empty($forcedPlan['lethal'])) {
                return 3000
                    + (intval($reachProfile['damage'] ?? 0) * 20)
                    - (intval($reachProfile['selfDamage'] ?? 0) * 10);
            }
            if(AzukiZeroHeuristicHasNonReachAction($actions, $legal, $state)) return -2000;
        }
        return AzukiZeroHeuristicPlayScore($cardID, $state, $legal);
    }

    return 100;
}

/**
 * Name the deterministic rule that covers this whole choice, or return an
 * empty string when the heuristic has no intentional opinion. The residual
 * trainer uses this as its abstention boundary; the live heuristic chooser is
 * deliberately unchanged and may still use its published model as a tie-break.
 */
function AzukiZeroHeuristicCoverageRule($actions, $legal, $snapshot, $player): string {
    if(!is_array($actions) || empty($actions)) return '';
    if(count($actions) === 1) return 'forced-action';

    $ids = AzukiZeroHeuristicCardIDs();
    $state = AzukiZeroHeuristicState($snapshot, $player);
    $type = strtoupper(strval($legal['decisionType'] ?? ''));
    $tooltip = strtolower(str_replace('_', ' ', strval($legal['decisionTooltipRaw'] ?? $legal['decisionTooltip'] ?? '')));
    $param = strtolower(strval($legal['decisionParam'] ?? ''));
    $source = AzukiZeroHeuristicDecisionSourceCardID($state);

    if($type !== '') {
        if($type === 'YESNO') {
            if(str_contains($tooltip, 'mulligan') || str_contains($param, 'review:myhand')) return 'mulligan';
            if($source === $ids['warlord']) return 'warlord-optional';
            if($source === $ids['kindler']) return 'kindler-optional';
            if($source === $ids['black_jade_dagger']) return 'black-jade-dagger-optional';
            if($source === $ids['scarlett']) return 'scarlett-optional';
            return '';
        }
        if($type === 'CHOOSEZONE') return 'entity-placement';
        if($type === 'MZMODAL' && str_contains($param, 'go_first')) return 'go-first';
        if(str_contains($tooltip, 'select entity to portal')) return 'portal-target';
        if(str_contains($tooltip, 'attack target')) return 'attack-target';

        $handler = AzukiZeroHeuristicPendingHandler(intval($state['player'] ?? 0));
        if(str_starts_with($handler, 'PEKIRO_REDIRECT_DAMAGE')) return 'pekiro-redirect';
        if(str_starts_with($handler, $ids['rushfire_gate'] . ':')) return 'rushfire-gate-choice';
        if(in_array($source, [
            $ids['zero'],
            $ids['warlord'],
            $ids['spice'],
            $ids['kindler'],
            $ids['fire_orb'],
            $ids['collateral_burst'],
            $ids['detonation_pact'],
        ], true)) return 'known-card-target';
        return '';
    }

    foreach($actions as $action) {
        if(!is_array($action)) return '';
        $key = AzukiZeroHeuristicActionKey($action, $legal);
        $cardID = AzukiZeroHeuristicActionCardID($action);
        if(str_starts_with($key, 'pass:') || str_starts_with($key, 'attack:')) continue;
        // Main-action play legality is already enforced by the engine. Keep the
        // deterministic policy active for an unlisted deck card and use the
        // generic play score; any card-specific follow-up decision can still
        // abstain to the residual policy independently.
        if(str_starts_with($key, 'play:') && $cardID !== '') continue;
        if(str_starts_with($key, 'activate:') && in_array($cardID, [$ids['zero'], $ids['rushfire_gate']], true)) continue;
        return '';
    }
    return 'known-free-play';
}

/**
 * Return a deterministic action only when the heuristic fully covers the
 * choice. Exact top-score ties between different semantic actions abstain so
 * the residual policy can learn the distinction.
 */
function AzukiZeroHeuristicCoveredChoice($actions, $legal, $snapshot, $player): array {
    $rule = AzukiZeroHeuristicCoverageRule($actions, $legal, $snapshot, $player);
    if($rule === '') return ['covered' => false, 'rule' => '', 'action' => null];

    $best = null;
    $bestScore = null;
    $bestKey = '';
    $ambiguous = false;
    foreach($actions as $action) {
        if(!is_array($action)) continue;
        $score = AzukiZeroHeuristicActionScore($action, $actions, $legal, $snapshot, $player);
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

function AzukiZeroHeuristicChooseAction($stateLogits, $actions, $legal, $snapshot, $player) {
    if(!is_array($actions) || empty($actions)) return null;
    $best = null;
    $bestHeuristic = null;
    $bestModel = null;
    foreach($actions as $action) {
        if(!is_array($action)) continue;
        $heuristic = AzukiZeroHeuristicActionScore($action, $actions, $legal, $snapshot, $player);
        $model = AzukiZeroHeuristicModelScore($stateLogits, $action, $legal);
        if($best === null || $heuristic > $bestHeuristic || ($heuristic === $bestHeuristic && $model > $bestModel)) {
            $best = $action;
            $bestHeuristic = $heuristic;
            $bestModel = $model;
        }
    }
    return $best;
}
