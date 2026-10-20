<?php
// Decision policies are deliberately explicit: an optional sacrifice and a free draw share
// the same wire format. Never infer benefit merely from MZMAYCHOOSE or from a word in a tooltip.
function GABotDecisionContext($player, $front) {
    $next = false;
    $handler = '';
    foreach (GetDecisionQueue($player) as $entry) {
        if ($entry === null || !empty($entry->removed)) continue;
        if ($entry === $front) { $next = true; continue; }
        if ($next) {
            if (($entry->Type ?? '') === 'CUSTOM') $handler = strval($entry->Param ?? '');
            break;
        }
    }
    return ['tooltip' => strval($front->Tooltip ?? ''), 'param' => strval($front->Param ?? ''), 'handler' => $handler];
}

function GABotChoiceCandidates($player, $param) {
    global $playerID;
    $saved = $playerID;
    $playerID = intval($player);
    try {
        $choices = [];
        foreach (array_filter(explode('&', $param)) as $token) {
            $token = explode(':', $token)[0];
            if (preg_match('/^(my|their)[A-Za-z]+$/', $token)) {
                foreach ((array)GetZone($token) as $i => $obj) {
                    if ($obj !== null && empty($obj->removed)) $choices[] = "$token-$i";
                }
            } else {
                $ability = DecodeOpportunityAbilityChoice($token);
                $resolved = GABotResolveChoiceToken($player, $ability['mzID'] ?? $token);
                if ($resolved !== null && $ability !== null) $resolved = $token;
                if ($resolved !== null) $choices[] = $resolved;
            }
        }
        return array_values(array_unique($choices));
    } finally { $playerID = $saved; }
}

function GABotLiveCount($zone) {
    return count(array_filter((array)$zone, fn($obj) => $obj !== null && empty($obj->removed)));
}

function GABotOwnChampion($player) {
    foreach (GetField($player) as $obj) {
        if ($obj !== null && empty($obj->removed) && PropertyContains(EffectiveCardType($obj), 'CHAMPION')) return $obj;
    }
    return null;
}

function GABotRemainingHP($obj) {
    return $obj === null ? 0 : max(0, intval(ObjectCurrentHP($obj)) - intval($obj->Damage ?? 0));
}

function GABotMaterializeScore($player, $mz, $handler) {
    $obj = GetZoneObject($mz);
    if ($obj === null || !GAMaterialCanLeave($obj)) return -1000;
    if (!CanPlayerUseCardElement($player, $obj->CardID, false, false)) return -1000;
    $champion = PropertyContains(EffectiveCardType($obj), 'CHAMPION');
    if ($champion && !CanMaterializeChampion($player, $obj->CardID)) return -1000;
    $free = $handler === 'MATERIALIZE|NOCOST';
    // These cards have additional costs / special payment paths. Leave them to a dedicated
    // policy instead of committing a sacrifice or repeatedly undoing an unpayable choice.
    if (!$free && in_array($obj->CardID, ['2s08hssegf', '9f92917r84', 'u25fuv184p', 'dW5uyngvJW',
        'uvgflagxbb', 'YTO70fFsBY', 'kINobk9KQA', 'Y34Imzlr0n', 'mDN1CI9IEe'], true)) return -1000;
    if ($obj->CardID === '4nmxqsm4o9' && GABotLiveCount(GetMaterial($player)) > 1) return -1000;
    if (!$free && CardCost_memory($obj->CardID) < 0) return -1000;
    $cost = $free ? 0 : intval(CardMemoryCost($obj));
    if ($cost < 0) return -1000; // X needs its own value policy.
    $available = GABotLiveCount(GetMemory($player));
    foreach (GABotChoiceCandidates($player, GetMaterializeFloatingChoices($player)) as $choice) {
        // Spend graveyard floating memory, not field permanents whose value we haven't priced.
        if (strpos($choice, 'Graveyard-') !== false) ++$available;
    }
    if ($cost > $available) return -1000;
    $score = ($champion ? 100 : 25) - $cost * 5;
    // Establish a weapon before spending a materialization on standalone ammunition.
    if (PropertyContains(EffectiveCardSubtypes($obj), 'GUN')) $score += 15;
    if (PropertyContains(EffectiveCardSubtypes($obj), 'BULLET')) $score -= 10;
    return $score;
}

function GABotCombatContext($player) {
    $attackerMZ = GetCombatAttackerMZ();
    $targetMZ = GetCombatTargetMZ();
    $attacker = $attackerMZ ? GetZoneObject($attackerMZ) : null;
    $target = $targetMZ ? GetZoneObject($targetMZ) : null;
    if ($attacker === null || $target === null || !empty($attacker->removed) || !empty($target->removed)) return null;
    $owner = intval($attacker->Controller);
    return ['attacker' => $attackerMZ, 'target' => $targetMZ, 'owner' => $owner,
        'threat' => GetAttackThreatAmount($attacker, $owner), 'hp' => GABotRemainingHP($target),
        'targetOwner' => intval($target->Controller)];
}

// null means unsupported, negative means understood but undesirable. This distinction makes
// coverage reports useful without labeling a deliberate pass as a missing implementation.
function GABotOptionalScore($player, $answer, $legal) {
    $handler = $legal['handler'] ?? '';
    $tooltip = $legal['tooltip'] ?? '';
    if ($handler === 'MATERIALIZE' || $handler === 'MATERIALIZE|NOCOST') {
        return GABotMaterializeScore($player, $answer, $handler);
    }
    if ($handler === 'WeaponSelected') {
        $weapon = GetZoneObject($answer);
        if ($weapon === null || !in_array($answer, GetAvailableWeapons($player), true)) return -1;
        // Optional reserve taxes need a separate tradeoff policy.
        if (in_array($weapon->CardID, ['5iqigcom2r', 'chnppup4iz', 'f5ooozsikp', 'pyx8bd7ozu'], true)) return -1;
        return 10 * max(0, intval(ObjectCurrentPower($weapon))) - 1;
    }
    if ($tooltip === 'Take_a_fast_action?') {
        $ability = DecodeOpportunityAbilityChoice($answer);
        if ($ability !== null) {
            $source = GetZoneObject($ability['mzID']);
            // Starter bullets' REST: Load ability. The opportunity enumerator has checked speed
            // and activation costs; only take this known ability when an unloaded gun exists.
            if ($source !== null && $ability['abilityIndex'] === 0
                && in_array($source->CardID, ['l75tlzsmw3', '4x7e22tk3i', '97n2jnltv5'], true)) {
                return intval($source->Status) === 2 && !empty(GetUnloadedGuns($player)) ? 30 : -1;
            }
            return null;
        }
        $obj = GetZoneObject($answer);
        if ($obj === null || !CanActivateCardForSelection($player, $obj) || !CanAffordCardActivation($player, $obj)) return -1000;
        $combat = GABotCombatContext($player);
        // Evasive Maneuvers: use prevention when it turns a lethal hit into survival.
        if ($obj->CardID === '1n3gygojwk') {
            // Do not spend a second copy while the first is still resolving, or after damage.
            foreach (GetLiveEffectStackEntries() as $entry) {
                if ($entry->CardID === $obj->CardID && intval($entry->Controller) === $player) return -1;
            }
            if (DecisionQueueController::GetVariable('CombatDamageAmount') !== null) return -1;
            if ($combat !== null) {
                $target = GetZoneObject($combat['target']);
                foreach ($target->TurnEffects ?? [] as $effect) {
                    if (str_starts_with($effect, 'EVASIVE_MANEUVERS_')) {
                        $combat['threat'] -= intval(substr($effect, strlen('EVASIVE_MANEUVERS_')));
                        break;
                    }
                }
            }
            return $combat !== null && $combat['targetOwner'] === $player && $combat['threat'] >= $combat['hp']
                && $combat['threat'] - 2 < $combat['hp'] ? 100 : -1;
        }
        return null;
    }
    if ($handler === 'f15joh300z:0:CardActivated-1') {
        // Umbra Sight's extra draw deals 2 unpreventable damage per curse, including itself.
        $damage = 2 * (CountCursesInLineage($player) + 1);
        return GABotLiveCount(GetDeck($player)) > 1 && GABotRemainingHP(GABotOwnChampion($player)) > $damage + 4 ? 10 : -1;
    }
    if (in_array($tooltip, ['Recover_2?', 'Recover_3?', 'Recover_5?'], true)) {
        $champion = GABotOwnChampion($player);
        return $champion !== null && intval($champion->Damage ?? 0) > 0 ? 10 : -1;
    }
    if ($tooltip === 'Banish_floating_memory_to_draw?') {
        return GABotLiveCount(GetDeck($player)) > 1 && strpos($answer, 'myGraveyard-') === 0 ? 10 : -1;
    }
    if (str_starts_with($handler, 'DianaL2LoadChoice|')) {
        return GABotLiveCount(GetDeck($player)) > 2 ? 15 : -1;
    }
    return null;
}

function GABotRecordUnsupportedDecision($legal) {
    $entry = array_intersect_key($legal, array_flip(['decisionType', 'tooltip', 'handler', 'unsupportedCards']));
    $key = hash('sha256', json_encode($entry));
    if (isset($GLOBALS['GABotUnsupportedOptionalDecisions'][$key])) return;
    $GLOBALS['GABotUnsupportedOptionalDecisions'][$key] = $entry;
    error_log('GABot: unsupported optional policy ' . json_encode($entry));
}

function GABotChooseDecision(array $actions, array $legal) {
    global $playerID;
    $saved = $playerID;
    $player = intval($legal['playerID']);
    $playerID = $player;
    try {
        $type = $legal['decisionType'];
        $handler = $legal['handler'] ?? '';
        if ($type === 'MZMULTICHOOSE') {
            $min = $legal['minimum'];
            $max = $legal['maximum'];
            $candidates = $legal['candidates'];
            if (str_starts_with($handler, 'PAYFLOATING|')) {
                $candidates = array_values(array_filter($candidates, fn($mz) => strpos($mz, 'Graveyard-') !== false));
                $max = min($max, max(0, intval(explode('|', $handler)[1])));
                $actions[0]['cardID'] = implode('&', array_slice($candidates, 0, $max)) ?: '-';
            } elseif ($min === 0) {
                GABotRecordUnsupportedDecision($legal);
            }
            return $actions[0];
        }
        if ($type === 'MZCHOOSE') {
            $candidates = array_column($actions, 'cardID');
            if (in_array($handler, ['MATERIALIZE', 'MATERIALIZE|NOCOST', 'WeaponSelected'], true) && $candidates[0] !== 'PASS') {
                usort($candidates, fn($a, $b) => GABotOptionalScore($player, $b, $legal) <=> GABotOptionalScore($player, $a, $legal));
                $choice = $candidates[0];
            } elseif (stripos($legal['tooltip'] ?? '', 'attack_target') !== false) {
                $choice = GABotChooseCombatTarget($player, $candidates);
            } elseif ($handler === '1n3gygojwk:0:CardActivated-1') {
                $combat = GABotCombatContext($player);
                $choice = $combat !== null && $combat['targetOwner'] === $player && in_array($combat['target'], $candidates, true)
                    ? $combat['target'] : (array_values(array_filter($candidates, fn($mz) => intval(GetZoneObject($mz)->Controller ?? 0) === $player))[0] ?? $candidates[0]);
            } elseif (($legal['param'] ?? '') === 'myHand' && $candidates[0] !== 'PASS') {
                usort($candidates, fn($a, $b) => CardMemoryCost(GetZoneObject($a)) <=> CardMemoryCost(GetZoneObject($b)));
                $choice = $candidates[0];
            } else { $choice = $candidates[0]; }
            foreach ($actions as $action) if ($action['cardID'] === $choice) return $action;
        }
        if (!in_array($type, ['MZMAYCHOOSE', 'YESNO'], true)) return $actions[0];
        $best = null; $bestScore = 0; $unsupported = false; $unsupportedCards = [];
        foreach ($actions as $action) {
            if (in_array($action['cardID'], ['PASS', 'NO'], true)) { $best ??= $action; continue; }
            $score = GABotOptionalScore($player, $action['cardID'], $legal);
            if ($score === null) {
                $unsupported = true;
                $ability = DecodeOpportunityAbilityChoice($action['cardID']);
                $mz = $ability['mzID'] ?? $action['cardID'];
                if ($mz !== 'YES') {
                    $source = GetZoneObject($mz);
                    if ($source !== null) $unsupportedCards[] = $source->CardID;
                }
                continue;
            }
            if ($score > $bestScore) { $bestScore = $score; $best = $action; }
        }
        if ($unsupported) {
            $legal['unsupportedCards'] = array_values(array_unique($unsupportedCards));
            sort($legal['unsupportedCards']);
            GABotRecordUnsupportedDecision($legal);
        }
        return $best ?? $actions[0];
    } finally { $playerID = $saved; }
}
