<?php

function HellbreakCardCombatValue(string $cardID, $subjectObj = null, int $player = 0): int {
    $fixture = function_exists('HellbreakFixtureCard') ? HellbreakFixtureCard($cardID) : null;
    $value = is_array($fixture) && isset($fixture['combat'])
        ? max(0, intval($fixture['combat']))
        : (function_exists('CardCombat') ? max(0, intval(CardCombat($cardID))) : 0);
    if(is_object($subjectObj) && function_exists('HellbreakApplyValueModifiers')) {
        if($player !== 1 && $player !== 2) $player = intval($subjectObj->Controller ?? $subjectObj->Owner ?? 0);
        $value = HellbreakApplyValueModifiers('CombatModifier', $player, $subjectObj, $value);
    }
    return max(0, $value);
}

function HellbreakCardHealthValue(string $cardID, $subjectObj = null, int $player = 0): int {
    $fixture = function_exists('HellbreakFixtureCard') ? HellbreakFixtureCard($cardID) : null;
    $value = is_array($fixture) && isset($fixture['health'])
        ? max(0, intval($fixture['health']))
        : (function_exists('CardHealth') ? max(0, intval(CardHealth($cardID))) : 0);
    if(is_object($subjectObj) && function_exists('HellbreakApplyValueModifiers')) {
        if($player !== 1 && $player !== 2) $player = intval($subjectObj->Controller ?? $subjectObj->Owner ?? 0);
        $value = HellbreakApplyValueModifiers('HealthModifier', $player, $subjectObj, $value);
    }
    return max(0, $value);
}

function HellbreakNormalizeSchemeIcons($value): array {
    if(is_string($value)) {
        $decoded = json_decode($value, true);
        if(is_array($decoded)) $value = $decoded;
        else return [];
    }
    if(!is_array($value)) return [];
    $icons = [];
    foreach($value as $icon) {
        if(!is_array($icon)) continue;
        $type = strtoupper(trim((string)($icon['type'] ?? '')));
        $amount = max(0, intval($icon['value'] ?? 0));
        if(in_array($type, ['PROWL', 'FORESEE', 'HAUNT'], true) && $amount > 0) {
            $icons[] = ['type' => $type, 'value' => $amount];
        }
    }
    return $icons;
}

function HellbreakCardSchemeIcons(string $cardID): array {
    $fixture = function_exists('HellbreakFixtureCard') ? HellbreakFixtureCard($cardID) : null;
    if(is_array($fixture) && isset($fixture['scheme'])) return HellbreakNormalizeSchemeIcons($fixture['scheme']);
    return function_exists('CardScheme') ? HellbreakNormalizeSchemeIcons(CardScheme($cardID)) : [];
}

function HellbreakBattlefieldRef(int $viewer, string $mzID): ?array {
    if(!preg_match('/^(my|their)(Monster|Characters)-(\d+)$/', trim($mzID), $matches)) return null;
    $zonePlayer = $matches[1] === 'my' ? $viewer : HellbreakOtherPlayer($viewer);
    $kind = $matches[2] === 'Monster' ? 'MONSTER' : 'MINION';
    $zone = $kind === 'MONSTER' ? GetMonster($zonePlayer) : GetCharacters($zonePlayer);
    $live = HellbreakLiveZoneObjects($zone);
    $index = intval($matches[3]);
    if(!isset($live[$index]) || !is_object($live[$index])) return null;
    return [
        'kind' => $kind, 'zonePlayer' => $zonePlayer, 'index' => $index,
        'uniqueID' => intval($live[$index]->UniqueID), 'object' => $live[$index], 'mzID' => trim($mzID),
    ];
}

function HellbreakBattlefieldDescriptor(array $ref): array {
    return ['kind' => $ref['kind'], 'zonePlayer' => intval($ref['zonePlayer']), 'uniqueID' => intval($ref['uniqueID'])];
}

function HellbreakBattlefieldObjectsMatch(int $viewerA, string $mzIDA, int $viewerB, string $mzIDB): bool {
    $a = HellbreakBattlefieldRef($viewerA, $mzIDA);
    $b = HellbreakBattlefieldRef($viewerB, $mzIDB);
    if($a === null || $b === null) return false;
    return intval($a['uniqueID'] ?? 0) > 0 && intval($a['uniqueID']) === intval($b['uniqueID']);
}

function HellbreakResolveBattlefieldDescriptor($descriptor): ?array {
    if(!is_array($descriptor)) return null;
    $kind = strtoupper((string)($descriptor['kind'] ?? ''));
    $player = intval($descriptor['zonePlayer'] ?? 0);
    $uniqueID = intval($descriptor['uniqueID'] ?? 0);
    if(!in_array($kind, ['MONSTER', 'MINION'], true) || !in_array($player, [1, 2], true) || $uniqueID <= 0) return null;
    $zone = $kind === 'MONSTER' ? GetMonster($player) : GetCharacters($player);
    foreach(HellbreakLiveZoneObjects($zone) as $index => $object) {
        if(intval($object->UniqueID) === $uniqueID) {
            return ['kind' => $kind, 'zonePlayer' => $player, 'index' => $index, 'uniqueID' => $uniqueID, 'object' => $object];
        }
    }
    return null;
}

function HellbreakCharacterLocation(array $ref): int {
    return $ref['kind'] === 'MINION' ? intval($ref['object']->LocationSlot) : 0;
}

function HellbreakIsReadyControlledCharacter(array $ref, int $player): bool {
    return intval($ref['object']->Controller ?? 0) === $player && intval($ref['object']->Status ?? 0) === 2;
}

function HellbreakAttackTargetsForRef(int $player, array $attacker, ?int $locationSlot = null): array {
    if(intval($attacker['object']->Controller ?? 0) !== $player) return [];
    if($attacker['kind'] === 'MINION') $locationSlot = HellbreakCharacterLocation($attacker);
    if(!in_array(intval($locationSlot), [1, 2], true)) return [];
    $opponent = HellbreakOtherPlayer($player);
    $targets = [];
    foreach(HellbreakLiveZoneObjects(GetMonster($opponent)) as $index => $monster) $targets[] = 'theirMonster-' . $index;
    foreach(HellbreakLiveZoneObjects(GetCharacters($opponent)) as $index => $character) {
        if(intval($character->Controller) === $opponent && intval($character->LocationSlot) === intval($locationSlot)) {
            $targets[] = 'theirCharacters-' . $index;
        }
    }
    return $targets;
}

function HellbreakLegalAttackers(int $player): array {
    if(GetCurrentPhase() !== 'HORROR' || intval(GetTurnPlayer()) !== $player || intval(GetSlumberPlayer()) === $player) return [];
    $attackers = [];
    foreach(HellbreakLiveZoneObjects(GetMonster($player)) as $index => $monster) {
        $ref = HellbreakBattlefieldRef($player, 'myMonster-' . $index);
        if($ref === null || !HellbreakIsReadyControlledCharacter($ref, $player)) continue;
        if(count(HellbreakAttackTargetsForRef($player, $ref, 1)) > 0 || count(HellbreakAttackTargetsForRef($player, $ref, 2)) > 0) {
            $attackers[] = 'myMonster-' . $index;
        }
    }
    foreach(HellbreakLiveZoneObjects(GetCharacters($player)) as $index => $character) {
        $ref = HellbreakBattlefieldRef($player, 'myCharacters-' . $index);
        if($ref !== null && count(HellbreakAttackTargetsForRef($player, $ref)) > 0) $attackers[] = 'myCharacters-' . $index;
    }
    return $attackers;
}

function HellbreakQueueAttackTargets(int $player): bool {
    $pending = DecisionQueueController::GetVariable('HellbreakPendingAttack');
    if(!is_array($pending) || intval($pending['player'] ?? 0) !== $player) return false;
    $attacker = HellbreakResolveBattlefieldDescriptor($pending['attacker'] ?? null);
    if($attacker === null) return false;
    $targets = HellbreakAttackTargetsForRef($player, $attacker, intval($pending['locationSlot'] ?? 0));
    if(count($targets) === 0) return false;
    DecisionQueueController::StoreVariable('HellbreakPendingAttackTargets', $targets);
    $labels = [];
    foreach($targets as $mzID) {
        $target = HellbreakBattlefieldRef($player, $mzID);
        $name = $target !== null && function_exists('CardName') ? trim((string)CardName($target['object']->CardID)) : '';
        if($name === '' && $target !== null) $name = (string)$target['object']->CardID;
        $suffix = $target !== null && $target['kind'] === 'MONSTER' ? 'Monster' : 'Location_' . HellbreakCharacterLocation($target);
        $labels[] = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $name . '_' . $suffix), '_');
    }
    DecisionQueueController::AddDecision($player, 'MZMODAL', '1|1|' . implode('&', $labels), 0, 'Choose_an_enemy_character_to_attack');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'HellbreakChooseAttackTarget', 1);
    return true;
}

function HellbreakChooseAttacker(int $player, string $mzID): bool {
    if(!in_array($mzID, HellbreakLegalAttackers($player), true)) return false;
    $attacker = HellbreakBattlefieldRef($player, $mzID);
    if($attacker === null) return false;
    $attacker['object']->Status = 1;
    DecisionQueueController::StoreVariable('HellbreakPendingAttack', [
        'player' => $player, 'attacker' => HellbreakBattlefieldDescriptor($attacker),
        'locationSlot' => $attacker['kind'] === 'MINION' ? HellbreakCharacterLocation($attacker) : 0,
    ]);
    if($attacker['kind'] === 'MONSTER') {
        DecisionQueueController::AddDecision($player, 'MZMODAL', '1|1|Location_1&Location_2', 0, 'Choose_the_monsters_attack_location');
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'HellbreakChooseAttackLocation', 1);
        return true;
    }
    $barrierContext = ['player' => $player, 'attacker' => HellbreakBattlefieldDescriptor($attacker)];
    HellbreakQueueAttackEventBarrier($barrierContext, 'ATTACK_DECLARED', 0, false);
    if(function_exists('HellbreakAttackDeclaredHook')) HellbreakAttackDeclaredHook($player, $attacker);
    HellbreakPumpAttackEventQueues();
    return true;
}

function HellbreakContinueAfterAttackDeclared(array $context): bool {
    DecisionQueueController::StoreVariable('HellbreakPendingAttackBarrier', []);
    $player = intval($context['player'] ?? 0);
    $attacker = HellbreakResolveBattlefieldDescriptor($context['attacker'] ?? null);
    if($attacker === null || intval($attacker['object']->Controller ?? 0) !== $player) return false;
    return HellbreakQueueAttackTargets($player);
}

function HellbreakChooseAttackLocation(int $player, string $selection): bool {
    if(!preg_match('/^[01]$/', trim($selection))) return false;
    $pending = DecisionQueueController::GetVariable('HellbreakPendingAttack');
    if(!is_array($pending) || intval($pending['player'] ?? 0) !== $player) return false;
    $pending['locationSlot'] = intval($selection) + 1;
    DecisionQueueController::StoreVariable('HellbreakPendingAttack', $pending);
    $attacker = HellbreakResolveBattlefieldDescriptor($pending['attacker'] ?? null);
    if($attacker === null || $attacker['kind'] !== 'MONSTER') return false;
    $barrierContext = ['player' => $player, 'attacker' => HellbreakBattlefieldDescriptor($attacker)];
    HellbreakQueueAttackEventBarrier($barrierContext, 'ATTACK_DECLARED', 0, false);
    if(function_exists('HellbreakAttackDeclaredHook')) HellbreakAttackDeclaredHook($player, $attacker);
    HellbreakPumpAttackEventQueues();
    return true;
}

function HellbreakDefenderCandidates(int $defendingPlayer, int $locationSlot): array {
    $candidates = [];
    foreach(HellbreakLiveZoneObjects(GetMonster($defendingPlayer)) as $index => $monster) {
        $ref = HellbreakBattlefieldRef($defendingPlayer, 'myMonster-' . $index);
        if($ref !== null && HellbreakIsReadyControlledCharacter($ref, $defendingPlayer)) $candidates[] = 'myMonster-' . $index;
    }
    foreach(HellbreakLiveZoneObjects(GetCharacters($defendingPlayer)) as $index => $character) {
        $ref = HellbreakBattlefieldRef($defendingPlayer, 'myCharacters-' . $index);
        if($ref !== null && HellbreakIsReadyControlledCharacter($ref, $defendingPlayer) && HellbreakCharacterLocation($ref) === $locationSlot) {
            $candidates[] = 'myCharacters-' . $index;
        }
    }
    return $candidates;
}

function HellbreakChooseAttackTarget(int $player, string $mzID): bool {
    $pending = DecisionQueueController::GetVariable('HellbreakPendingAttack');
    $targets = DecisionQueueController::GetVariable('HellbreakPendingAttackTargets');
    if(preg_match('/^\d+$/', trim($mzID)) && is_array($targets)) $mzID = (string)($targets[intval($mzID)] ?? '');
    if(!is_array($pending) || intval($pending['player'] ?? 0) !== $player || !is_array($targets) || !in_array($mzID, $targets, true)) return false;
    $target = HellbreakBattlefieldRef($player, $mzID);
    if($target === null) return false;
    $pending['target'] = HellbreakBattlefieldDescriptor($target);
    DecisionQueueController::StoreVariable('HellbreakPendingAttack', $pending);
    $barrierContext = ['player' => $player, 'target' => HellbreakBattlefieldDescriptor($target)];
    HellbreakQueueAttackEventBarrier($barrierContext, 'TARGET_DECLARED', 0, false);
    if(function_exists('HellbreakAttackTargetDeclaredHook')) HellbreakAttackTargetDeclaredHook($player, $target);
    HellbreakPumpAttackEventQueues();
    return true;
}

function HellbreakContinueAfterTargetDeclared(array $context): bool {
    DecisionQueueController::StoreVariable('HellbreakPendingAttackBarrier', []);
    $player = intval($context['player'] ?? 0);
    $pending = DecisionQueueController::GetVariable('HellbreakPendingAttack');
    if(!is_array($pending) || intval($pending['player'] ?? 0) !== $player) return false;
    $defenderPlayer = HellbreakOtherPlayer($player);
    $defenders = HellbreakDefenderCandidates($defenderPlayer, intval($pending['locationSlot']));
    DecisionQueueController::StoreVariable('HellbreakPendingDefenders', $defenders);
    if(HellbreakIsAutoSetupPlayer($defenderPlayer) || count($defenders) === 0) return HellbreakResolveAttack($player, null);
    DecisionQueueController::AddDecision($defenderPlayer, 'MZMAYCHOOSE', implode('&', $defenders), 0, 'Choose_a_defender_or_pass');
    DecisionQueueController::AddDecision($defenderPlayer, 'CUSTOM', 'HellbreakChooseDefender', 1, '', 1);
    return true;
}

function HellbreakChooseDefender(int $defendingPlayer, string $selection): bool {
    $pending = DecisionQueueController::GetVariable('HellbreakPendingAttack');
    $attackingPlayer = intval($pending['player'] ?? 0);
    if(!is_array($pending) || HellbreakOtherPlayer($attackingPlayer) !== $defendingPlayer) return false;
    $selection = trim($selection);
    if($selection === '' || $selection === '-' || strtoupper($selection) === 'PASS') return HellbreakResolveAttack($attackingPlayer, null);
    $candidates = DecisionQueueController::GetVariable('HellbreakPendingDefenders');
    if(!is_array($candidates) || !in_array($selection, $candidates, true)) return false;
    $defender = HellbreakBattlefieldRef($defendingPlayer, $selection);
    if($defender === null || !HellbreakIsReadyControlledCharacter($defender, $defendingPlayer)) return false;
    $defender['object']->Status = 1;
    $defenderDescriptor = HellbreakBattlefieldDescriptor($defender);
    $pending['defender'] = $defenderDescriptor;
    DecisionQueueController::StoreVariable('HellbreakPendingAttack', $pending);
    $barrierContext = ['player' => $attackingPlayer, 'defender' => $defenderDescriptor];
    HellbreakQueueAttackEventBarrier($barrierContext, 'DEFENDER_DECLARED', 0, false);
    if(function_exists('HellbreakDefenderDeclaredHook')) HellbreakDefenderDeclaredHook($attackingPlayer, $defender);
    HellbreakPumpAttackEventQueues();
    return true;
}

function HellbreakContinueAfterDefenderDeclared(array $context): bool {
    DecisionQueueController::StoreVariable('HellbreakPendingAttackBarrier', []);
    $attackingPlayer = intval($context['player'] ?? 0);
    $pending = DecisionQueueController::GetVariable('HellbreakPendingAttack');
    $defenderDescriptor = is_array($pending) ? ($pending['defender'] ?? null) : null;
    return is_array($defenderDescriptor) ? HellbreakResolveAttack($attackingPlayer, $defenderDescriptor) : false;
}

function HellbreakDealMinionDamage(
    array $descriptor,
    int $amount,
    ?array $sourceDescriptor = null,
    string $damageType = 'EFFECT',
    int $eventPlayer = 0
): bool {
    $ref = HellbreakResolveBattlefieldDescriptor($descriptor);
    if($ref === null || $ref['kind'] !== 'MINION' || $amount <= 0) return false;
    $source = $sourceDescriptor === null ? null : HellbreakResolveBattlefieldDescriptor($sourceDescriptor);
    $amount = HellbreakModifiedDamageAmount(
        $ref,
        $amount,
        $damageType,
        $source['object'] ?? null,
        false
    );
    if($amount <= 0) return true;
    $ref['object']->Damage = intval($ref['object']->Damage) + $amount;
    if(function_exists('HellbreakDamageDealtHook')) {
        HellbreakDamageDealtHook($descriptor, $amount, $sourceDescriptor, $damageType, $eventPlayer);
    }
    return true;
}

function HellbreakAddNextMonsterDamagePrevention(int $player, int $amount): int {
    if(!in_array($player, [1, 2], true) || $amount <= 0) return 0;
    $key = 'HellbreakMonsterDamagePreventionP' . $player;
    $existing = DecisionQueueController::GetVariable($key);
    $phase = strval(GetCurrentPhase());
    $current = is_array($existing) && strval($existing['phase'] ?? '') === $phase
        ? max(0, intval($existing['amount'] ?? 0)) : 0;
    $current += $amount;
    DecisionQueueController::StoreVariable($key, ['phase' => $phase, 'amount' => $current]);
    return $current;
}

function HellbreakConsumeMonsterDamagePrevention(int $player, int $amount): int {
    if(!in_array($player, [1, 2], true) || $amount <= 0) return 0;
    $key = 'HellbreakMonsterDamagePreventionP' . $player;
    $existing = DecisionQueueController::GetVariable($key);
    $phase = strval(GetCurrentPhase());
    if(!is_array($existing) || strval($existing['phase'] ?? '') !== $phase) {
        DecisionQueueController::StoreVariable($key, []);
        return 0;
    }
    $prevented = min($amount, max(0, intval($existing['amount'] ?? 0)));
    $remaining = max(0, intval($existing['amount'] ?? 0) - $prevented);
    DecisionQueueController::StoreVariable($key, $remaining > 0 ? ['phase' => $phase, 'amount' => $remaining] : []);
    return $prevented;
}

function HellbreakModifiedDamageAmount(array $targetRef, int $amount, string $damageType, $sourceObj = null, bool $isMonster = false): int {
    $amount = max(0, $amount);
    if($amount <= 0 || !isset($targetRef['object']) || !is_object($targetRef['object'])) return 0;
    $targetPlayer = intval($targetRef['object']->Controller ?? $targetRef['zonePlayer'] ?? 0);
    if(function_exists('HellbreakApplyValueModifiers')) {
        $amount = HellbreakApplyValueModifiers(
            'DamageModifier',
            $targetPlayer,
            $targetRef['object'],
            $amount,
            [strtoupper(trim($damageType))],
            true
        );
    }
    return max(0, $amount);
}

function HellbreakMinionTargetsAtLocation(int $viewer, int $locationSlot): array {
    if(!in_array($viewer, [1, 2], true) || $locationSlot <= 0) return [];
    $targets = [];
    foreach([$viewer, HellbreakOtherPlayer($viewer)] as $zonePlayer) {
        $prefix = $zonePlayer === $viewer ? 'myCharacters-' : 'theirCharacters-';
        foreach(HellbreakLiveZoneObjects(GetCharacters($zonePlayer)) as $index => $object) {
            if(intval($object->LocationSlot ?? 0) === $locationSlot) $targets[] = $prefix . $index;
        }
    }
    return $targets;
}

function HellbreakCharacterTargetsAtLocation(int $viewer, int $locationSlot): array {
    if(!in_array($viewer, [1, 2], true) || $locationSlot <= 0) return [];
    $targets = [];
    if(count(HellbreakLiveZoneObjects(GetMonster($viewer))) > 0) $targets[] = 'myMonster-0';
    if(count(HellbreakLiveZoneObjects(GetMonster(HellbreakOtherPlayer($viewer)))) > 0) $targets[] = 'theirMonster-0';
    return array_merge($targets, HellbreakMinionTargetsAtLocation($viewer, $locationSlot));
}

function HellbreakMinionTargetsAtLocationMatching(int $viewer, int $locationSlot, int $controller = 0, string $trait = ''): array {
    $targets = [];
    foreach(HellbreakMinionTargetsAtLocation($viewer, $locationSlot) as $mzID) {
        $ref = HellbreakBattlefieldRef($viewer, $mzID);
        if($ref === null) continue;
        if(in_array($controller, [1, 2], true) && intval($ref['object']->Controller ?? 0) !== $controller) continue;
        if($trait !== '' && (!function_exists('HellbreakCardHasTrait')
            || !HellbreakCardHasTrait(strval($ref['object']->CardID ?? ''), $trait))) continue;
        $targets[] = $mzID;
    }
    return $targets;
}

function HellbreakEnemyMinionsAtLocation(int $player, int $locationSlot, string $trait = ''): array {
    return HellbreakMinionTargetsAtLocationMatching($player, $locationSlot, HellbreakOtherPlayer($player), $trait);
}

function HellbreakExhaustedMinionsAtLocation(int $viewer, int $locationSlot): array {
    $targets = [];
    foreach(HellbreakMinionTargetsAtLocation($viewer, $locationSlot) as $mzID) {
        $ref = HellbreakBattlefieldRef($viewer, $mzID);
        if($ref !== null && intval($ref['object']->Status ?? 0) === 1) $targets[] = $mzID;
    }
    return $targets;
}

function HellbreakKillableMinionsForSource(int $player, string $sourceMZ): array {
    $source = HellbreakBattlefieldRef($player, $sourceMZ);
    if($source === null || $source['kind'] !== 'MINION' || intval($source['object']->Controller ?? 0) !== $player) return [];
    $locationSlot = HellbreakCharacterLocation($source);
    $combat = HellbreakCardCombatValue(strval($source['object']->CardID ?? ''), $source['object'], $player);
    $targets = [];
    foreach(HellbreakMinionTargetsAtLocation($player, $locationSlot) as $targetMZ) {
        $target = HellbreakBattlefieldRef($player, $targetMZ);
        if($target === null || $target['object'] === $source['object']) continue;
        $health = HellbreakCardHealthValue(
            strval($target['object']->CardID ?? ''),
            $target['object'],
            intval($target['object']->Controller ?? $target['zonePlayer'])
        );
        $remainingHealth = max(0, $health - intval($target['object']->Damage ?? 0));
        if($remainingHealth <= $combat) $targets[] = $targetMZ;
    }
    return $targets;
}

function HellbreakExhaustControlledCard(int $player, string $mzID): bool {
    $object = GetZoneObject($mzID);
    if(!is_object($object) || intval($object->Controller ?? 0) !== $player || intval($object->Status ?? 0) !== 2) return false;
    $object->Status = 1;
    return true;
}

function HellbreakReadyControlledCard(int $player, string $mzID): bool {
    $object = GetZoneObject($mzID);
    if(!is_object($object) || intval($object->Controller ?? 0) !== $player) return false;
    $object->Status = 2;
    return true;
}

function HellbreakKillMinionByMZ(int $viewer, string $targetMZ, string $sourceMZ = ''): bool {
    $target = HellbreakBattlefieldRef($viewer, $targetMZ);
    if($target === null || $target['kind'] !== 'MINION') return false;
    $sourceRef = $sourceMZ === '' ? null : HellbreakBattlefieldRef($viewer, $sourceMZ);
    $sourceObject = $sourceRef['object'] ?? null;
    $zone = &GetCharacters($target['zonePlayer']);
    $object = $target['object'];
    array_splice($zone, $target['index'], 1);
    HellbreakReindexZone($zone);
    $owner = intval($object->Owner ?? 0) ?: intval($target['zonePlayer']);
    AddCrypt($owner, $object->CardID, 'Characters', intval(GetTurnNumber()), true, $object);
    $liveSourceMZ = is_object($sourceObject) && function_exists('HellbreakMZForObject')
        ? HellbreakMZForObject($viewer, $sourceObject) : $sourceMZ;
    if(function_exists('HellbreakMinionKilledHook')) HellbreakMinionKilledHook($owner, $object, $liveSourceMZ);
    return true;
}

function HellbreakDealMinionDamageByMZ(
    int $viewer,
    string $targetMZ,
    int $amount,
    string $sourceMZ = '',
    string $damageType = 'EFFECT'
): bool {
    if($amount <= 0) return false;
    $target = HellbreakBattlefieldRef($viewer, $targetMZ);
    if($target === null || $target['kind'] !== 'MINION') return false;
    $source = $sourceMZ === '' ? null : HellbreakBattlefieldRef($viewer, $sourceMZ);
    $targetDescriptor = HellbreakBattlefieldDescriptor($target);
    $sourceDescriptor = $source === null ? null : HellbreakBattlefieldDescriptor($source);
    if(!HellbreakDealMinionDamage($targetDescriptor, $amount, $sourceDescriptor, $damageType, $viewer)) return false;
    HellbreakCheckLethal($targetDescriptor, $sourceDescriptor, $viewer, $sourceMZ);
    return true;
}

function HellbreakDealCharacterDamageByMZ(
    int $viewer,
    string $targetMZ,
    int $amount,
    string $sourceMZ = '',
    string $damageType = 'EFFECT'
): bool {
    if($amount <= 0) return false;
    $target = HellbreakBattlefieldRef($viewer, $targetMZ);
    if($target === null) return false;
    if($target['kind'] === 'MINION') {
        return HellbreakDealMinionDamageByMZ($viewer, $targetMZ, $amount, $sourceMZ, $damageType);
    }
    $source = $sourceMZ === '' ? null : HellbreakBattlefieldRef($viewer, $sourceMZ);
    return HellbreakStartMonsterDamage(
        intval($target['zonePlayer']),
        $amount,
        ['type' => 'NONE'],
        $source === null ? null : HellbreakBattlefieldDescriptor($source),
        $damageType
    );
}

function HellbreakKillControlledPermanentByMZ(int $player, string $mzID, string $sourceMZ = ''): bool {
    if(preg_match('/^myCharacters-\d+$/', $mzID)) {
        $target = HellbreakBattlefieldRef($player, $mzID);
        if($target === null || intval($target['object']->Controller ?? 0) !== $player) return false;
        return HellbreakKillMinionByMZ($player, $mzID, $sourceMZ);
    }
    if(!preg_match('/^myAssets-(\d+)$/', $mzID, $matches)) return false;
    $assets = &GetAssets($player);
    HellbreakReindexZone($assets);
    $index = intval($matches[1]);
    if(!isset($assets[$index]) || !is_object($assets[$index]) || intval($assets[$index]->Controller ?? 0) !== $player) return false;
    $object = $assets[$index];
    array_splice($assets, $index, 1);
    HellbreakReindexZone($assets);
    $owner = intval($object->Owner ?? 0) ?: $player;
    AddCrypt($owner, $object->CardID, 'Assets', intval(GetTurnNumber()), true, $object);
    return true;
}

function HellbreakCardHasJumpscare(string $cardID): bool {
    $fixture = function_exists('HellbreakFixtureCard') ? HellbreakFixtureCard($cardID) : null;
    if(is_array($fixture) && array_key_exists('healthAbility', $fixture)) return boolval($fixture['healthAbility']);
    $text = is_array($fixture) ? strval($fixture['text'] ?? '') : '';
    if($text === '' && function_exists('CardText')) $text = strval(CardText($cardID));
    if(stripos($text, 'Jumpscare') !== false) return true;
    return function_exists('CardHasHealthAbility') ? boolval(CardHasHealthAbility($cardID)) : false;
}

function HellbreakCardHasHealthAbility(string $cardID): bool {
    return HellbreakCardHasJumpscare($cardID);
}

function HellbreakCardJumpscareIsImplemented(string $cardID): bool {
    $fixture = function_exists('HellbreakFixtureCard') ? HellbreakFixtureCard($cardID) : null;
    if(is_array($fixture) && array_key_exists('healthAbility', $fixture)) return boolval($fixture['healthAbility']);
    return function_exists('CardJumpscareUsedCount') && intval(CardJumpscareUsedCount($cardID)) > 0;
}

function HellbreakCardJumpscareMaliceCost(string $cardID): int {
    $fixture = function_exists('HellbreakFixtureCard') ? HellbreakFixtureCard($cardID) : null;
    if(is_array($fixture) && isset($fixture['healthMaliceCost'])) return max(0, intval($fixture['healthMaliceCost']));
    $text = is_array($fixture) ? strval($fixture['text'] ?? '') : '';
    if($text === '' && function_exists('CardText')) $text = strval(CardText($cardID));
    $jumpscareAt = stripos($text, 'Jumpscare');
    if($jumpscareAt !== false) {
        $jumpscareText = substr($text, $jumpscareAt);
        if(preg_match('/Pay\s+(\d+)\s+malice/i', $jumpscareText, $matches)) return max(0, intval($matches[1]));
        return 0;
    }
    return function_exists('CardHealthMaliceCost') ? max(0, intval(CardHealthMaliceCost($cardID))) : 0;
}

function HellbreakCardHealthMaliceCost(string $cardID): int {
    return HellbreakCardJumpscareMaliceCost($cardID);
}

function HellbreakTakeRevealedJumpscareCard(int $player, string $cardID) {
    if(!in_array($player, [1, 2], true) || $cardID === '') return null;
    $temp = &GetTempZone($player);
    HellbreakReindexZone($temp);
    foreach($temp as $index => $object) {
        if(!is_object($object) || strval($object->CardID ?? '') !== $cardID) continue;
        array_splice($temp, $index, 1);
        HellbreakReindexZone($temp);
        return $object;
    }
    return null;
}

function HellbreakFinalizeRevealedJumpscareCard(int $player, string $cardID): bool {
    $object = HellbreakTakeRevealedJumpscareCard($player, $cardID);
    if(!is_object($object)) return false; // Card text already moved or played it.
    AddCrypt($player, $cardID, 'HealthStack', intval(GetTurnNumber()), true, $object);
    return true;
}

function HellbreakEndGame(int $winner): bool {
    if(!in_array($winner, [1, 2], true) || intval(GetWinner()) > 0) return false;
    SetWinner($winner);
    SetTurnPlayer($winner);
    $queue1 = &GetDecisionQueue(1); $queue1 = [];
    $queue2 = &GetDecisionQueue(2); $queue2 = [];
    HellbreakAddPublicLog('Player ' . $winner . ' wins the game.', 'VICTORY');
    if(function_exists('HellbreakGameEndedHook')) HellbreakGameEndedHook($winner);
    return true;
}

function HellbreakRunDamageContinuation($continuation): bool {
    if(!is_array($continuation) || intval(GetWinner()) > 0) return true;
    $type = strtoupper((string)($continuation['type'] ?? 'NONE'));
    if($type === 'ATTACK') return HellbreakFinishAttackResolution($continuation);
    if($type === 'SCHEME_INDIRECT') {
        $scheme = DecisionQueueController::GetVariable('HellbreakPendingScheme');
        if(is_array($scheme)) {
            $scheme['index'] = intval($scheme['index'] ?? 0) + 1;
            DecisionQueueController::StoreVariable('HellbreakPendingScheme', $scheme);
        }
        return HellbreakContinueScheme();
    }
    if($type === 'EMPTY_DRAW') {
        $failed = max(0, intval($continuation['remaining'] ?? 0));
        return $failed > 0 ? HellbreakStartMonsterDamage(intval($continuation['player'] ?? 0), 2, [
            'type' => 'EMPTY_DRAW', 'player' => intval($continuation['player'] ?? 0), 'remaining' => $failed - 1,
        ], null, 'EMPTY_DRAW') : true;
    }
    return true;
}

function HellbreakPumpHealthEventQueues(): void {
    $controller = new DecisionQueueController();
    $controller->ExecuteStaticMethods(1, '-');
    $controller->ExecuteStaticMethods(2, '-');
}

function HellbreakQueueHealthEventBarrier(array $context, string $stage, bool $pump = true): bool {
    $stage = strtoupper(trim($stage));
    if(!in_array($stage, ['REVEALED', 'USED'], true)) return false;
    $token = intval(DecisionQueueController::GetVariable('HellbreakHealthBarrierToken')) + 1;
    DecisionQueueController::StoreVariable('HellbreakHealthBarrierToken', $token);
    DecisionQueueController::StoreVariable('HellbreakPendingHealthBarrier', [
        'token' => $token,
        'stage' => $stage,
        'context' => $context,
        'reached' => ['1' => false, '2' => false],
    ]);
    foreach([1, 2] as $player) {
        DecisionQueueController::AddDecision(
            $player,
            'CUSTOM',
            'HellbreakHealthEventBarrier|' . $stage . '|' . $token,
            100,
            '',
            1
        );
    }
    if($pump) HellbreakPumpHealthEventQueues();
    return true;
}

function HellbreakReachHealthEventBarrier(int $player, string $stage, int $token): bool {
    $pending = DecisionQueueController::GetVariable('HellbreakPendingHealthBarrier');
    $stage = strtoupper(trim($stage));
    if(!is_array($pending) || intval($pending['token'] ?? 0) !== $token || strval($pending['stage'] ?? '') !== $stage) return false;
    $pending['reached'][strval($player)] = true;
    DecisionQueueController::StoreVariable('HellbreakPendingHealthBarrier', $pending);
    if(empty($pending['reached']['1']) || empty($pending['reached']['2'])) return true;
    DecisionQueueController::StoreVariable('HellbreakPendingHealthBarrier', []);
    $context = is_array($pending['context'] ?? null) ? $pending['context'] : [];
    if($stage === 'REVEALED') return HellbreakContinueAfterHealthRevealed($context);
    return HellbreakContinueAfterHealthAbilityUsed($context);
}

function HellbreakContinueAfterHealthRevealed(array $context): bool {
    $pending = DecisionQueueController::GetVariable('HellbreakPendingMonsterDamage');
    if(!is_array($pending)) return false;
    $player = intval($pending['targetPlayer'] ?? 0);
    $cardID = strval($context['cardID'] ?? $pending['revealedCardID'] ?? '');
    $cost = max(0, intval($context['jumpscareMaliceCost'] ?? $context['healthMaliceCost'] ?? HellbreakCardJumpscareMaliceCost($cardID)));
    if($cardID !== '' && HellbreakCardHasJumpscare($cardID) && HellbreakCardJumpscareIsImplemented($cardID) && intval(MaliceValue($player)) >= $cost) {
        $pending['revealedCardID'] = $cardID;
        $pending['jumpscareMaliceCost'] = $cost;
        DecisionQueueController::StoreVariable('HellbreakPendingMonsterDamage', $pending);
        DecisionQueueController::AddDecision($player, 'YESNO', '-', 0, 'Use_' . preg_replace('/[^A-Za-z0-9]+/', '_', $cardID) . '_Jumpscare');
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'HellbreakResolveHealthAbility', 1, '', 1);
        return true;
    }
    HellbreakFinalizeRevealedJumpscareCard($player, $cardID);
    unset($pending['revealedCardID'], $pending['healthMaliceCost'], $pending['jumpscareMaliceCost']);
    DecisionQueueController::StoreVariable('HellbreakPendingMonsterDamage', $pending);
    return HellbreakProcessMonsterDamage();
}

function HellbreakContinueAfterHealthAbilityUsed(array $context): bool {
    $pending = DecisionQueueController::GetVariable('HellbreakPendingMonsterDamage');
    $player = is_array($pending) ? intval($pending['targetPlayer'] ?? 0) : intval($context['player'] ?? 0);
    $cardID = strval($context['cardID'] ?? '');
    if(in_array($player, [1, 2], true) && $cardID !== '') HellbreakFinalizeRevealedJumpscareCard($player, $cardID);
    return HellbreakProcessMonsterDamage();
}

function HellbreakProcessMonsterDamage(): bool {
    $pending = DecisionQueueController::GetVariable('HellbreakPendingMonsterDamage');
    if(!is_array($pending)) return false;
    $player = intval($pending['targetPlayer'] ?? 0);
    if(!in_array($player, [1, 2], true)) return false;
    $stack = &GetHealthStack($player);
    HellbreakReindexZone($stack);
    while(intval($pending['remaining'] ?? 0) > 0) {
        if(count($stack) === 0) {
            DecisionQueueController::StoreVariable('HellbreakPendingMonsterDamage', []);
            return HellbreakEndGame(HellbreakOtherPlayer($player));
        }
        // Monster damage is applied one point at a time. A Jumpscare revealed
        // by an earlier point can prevent a later point in this sequence.
        if(HellbreakConsumeMonsterDamagePrevention($player, 1) > 0) {
            $pending['remaining'] = intval($pending['remaining']) - 1;
            DecisionQueueController::StoreVariable('HellbreakPendingMonsterDamage', $pending);
            continue;
        }
        $top = $stack[0];
        $top->RemainingHealth = max(0, intval($top->RemainingHealth) - 1);
        $health = &HealthValue($player);
        $health = max(0, intval($health) - 1);
        $pending['remaining'] = intval($pending['remaining']) - 1;
        $pending['dealt'] = intval($pending['dealt'] ?? 0) + 1;
        if(function_exists('HellbreakDamageDealtHook')) HellbreakDamageDealtHook(['kind' => 'MONSTER', 'zonePlayer' => $player], 1);
        if(intval($top->RemainingHealth) <= 0) {
            array_shift($stack);
            AddTempZone($player, $top->CardID, $top);
            HellbreakReindexZone($stack);
            $revealedName = function_exists('CardName') ? trim((string)CardName((string)$top->CardID)) : '';
            HellbreakAddPublicLog('Player ' . $player . ' revealed ' . ($revealedName !== '' ? $revealedName : (string)$top->CardID) . ' from their Health stack.', 'HEALTH');
            $topRemaining = &TopHealthRemainingValue($player);
            $topRemaining = count($stack) > 0 ? intval($stack[0]->RemainingHealth) : 0;
            $cardID = (string)$top->CardID;
            $cost = HellbreakCardJumpscareMaliceCost($cardID);
            $pending['revealedCardID'] = $cardID;
            $pending['jumpscareMaliceCost'] = $cost;
            DecisionQueueController::StoreVariable('HellbreakPendingMonsterDamage', $pending);
            $context = ['player' => $player, 'cardID' => $cardID, 'jumpscareMaliceCost' => $cost];
            HellbreakQueueHealthEventBarrier($context, 'REVEALED', false);
            if(function_exists('HellbreakMonsterHealthCardRevealedHook')) HellbreakMonsterHealthCardRevealedHook($player, $top);
            HellbreakPumpHealthEventQueues();
            return true;
        } else {
            $topRemaining = &TopHealthRemainingValue($player);
            $topRemaining = intval($top->RemainingHealth);
        }
        DecisionQueueController::StoreVariable('HellbreakPendingMonsterDamage', $pending);
    }
    if(count($stack) === 0) {
        DecisionQueueController::StoreVariable('HellbreakPendingMonsterDamage', []);
        return HellbreakEndGame(HellbreakOtherPlayer($player));
    }
    $continuation = $pending['continuation'] ?? ['type' => 'NONE'];
    DecisionQueueController::StoreVariable('HellbreakPendingMonsterDamage', []);
    return HellbreakRunDamageContinuation($continuation);
}

function HellbreakStartMonsterDamage(
    int $player,
    int $amount,
    array $continuation = ['type' => 'NONE'],
    ?array $sourceDescriptor = null,
    string $damageType = 'EFFECT',
    bool $alreadyModified = false
): bool {
    if(!in_array($player, [1, 2], true) || $amount <= 0 || intval(GetWinner()) > 0) return false;
    $active = DecisionQueueController::GetVariable('HellbreakPendingMonsterDamage');
    if(is_array($active) && intval($active['remaining'] ?? 0) > 0) return false;
    if(!$alreadyModified) {
        $target = HellbreakBattlefieldRef($player, 'myMonster-0');
        $source = $sourceDescriptor === null ? null : HellbreakResolveBattlefieldDescriptor($sourceDescriptor);
        if($target !== null) $amount = HellbreakModifiedDamageAmount($target, $amount, $damageType, $source['object'] ?? null, true);
    }
    if($amount <= 0) return HellbreakRunDamageContinuation($continuation);
    DecisionQueueController::StoreVariable('HellbreakPendingMonsterDamage', [
        'targetPlayer' => $player, 'remaining' => $amount, 'dealt' => 0, 'continuation' => $continuation,
    ]);
    return HellbreakProcessMonsterDamage();
}

function HellbreakResolveHealthAbility(int $player, string $selection): bool {
    $pending = DecisionQueueController::GetVariable('HellbreakPendingMonsterDamage');
    if(!is_array($pending) || intval($pending['targetPlayer'] ?? 0) !== $player || empty($pending['revealedCardID'])) return false;
    $use = in_array(strtoupper(trim($selection)), ['YES', 'Y', '1'], true);
    $cardID = (string)$pending['revealedCardID'];
    $cost = max(0, intval($pending['jumpscareMaliceCost'] ?? $pending['healthMaliceCost'] ?? HellbreakCardJumpscareMaliceCost($cardID)));
    unset($pending['revealedCardID'], $pending['healthMaliceCost'], $pending['jumpscareMaliceCost']);
    DecisionQueueController::StoreVariable('HellbreakPendingMonsterDamage', $pending);
    if($use && intval(MaliceValue($player)) >= $cost) {
        $malice = &MaliceValue($player);
        $malice -= $cost;
        HellbreakAddPublicLog('Player ' . $player . ' used a Jumpscare' . ($cost > 0 ? ' and paid ' . $cost . ' malice.' : '.'), 'JUMPSCARE');
        HellbreakQueueHealthEventBarrier(['player' => $player, 'cardID' => $cardID], 'USED', false);
        // The legacy hook delegates to JumpscareUsed by default, while remaining
        // overridable by older deterministic fixtures.
        if(function_exists('HellbreakHealthAbilityUsedHook')) HellbreakHealthAbilityUsedHook($player, $cardID);
        HellbreakPumpHealthEventQueues();
        return true;
    }
    HellbreakFinalizeRevealedJumpscareCard($player, $cardID);
    return HellbreakProcessMonsterDamage();
}

function HellbreakDealMonsterDamage(int $player, int $amount): int {
    if($amount <= 0) return 0;
    $before = intval(HealthValue($player));
    HellbreakStartMonsterDamage($player, $amount);
    return max(0, $before - intval(HealthValue($player)));
}

function HellbreakCheckLethal(
    array $descriptor,
    ?array $sourceDescriptor = null,
    int $eventPlayer = 0,
    string $sourceMZ = ''
): bool {
    $ref = HellbreakResolveBattlefieldDescriptor($descriptor);
    if($ref === null || $ref['kind'] !== 'MINION') return false;
    if(intval($ref['object']->Damage) < HellbreakCardHealthValue((string)$ref['object']->CardID, $ref['object'], intval($ref['object']->Controller ?? $ref['zonePlayer']))) return false;
    $zone = &GetCharacters($ref['zonePlayer']);
    $object = $ref['object'];
    array_splice($zone, $ref['index'], 1);
    HellbreakReindexZone($zone);
    $owner = intval($object->Owner) ?: $ref['zonePlayer'];
    AddCrypt($owner, $object->CardID, 'Characters', intval(GetTurnNumber()), true, $object);
    if($sourceMZ === '' && $sourceDescriptor !== null && function_exists('HellbreakMZForBattlefieldDescriptor')) {
        $sourceMZ = HellbreakMZForBattlefieldDescriptor($eventPlayer, $sourceDescriptor);
    }
    if(function_exists('HellbreakMinionKilledHook')) HellbreakMinionKilledHook($owner, $object, $sourceMZ);
    return true;
}

function HellbreakApplyCharacterDamage(
    array $descriptor,
    int $amount,
    ?array $sourceDescriptor = null,
    string $damageType = 'EFFECT',
    int $eventPlayer = 0
): void {
    $ref = HellbreakResolveBattlefieldDescriptor($descriptor);
    if($ref === null || $amount <= 0) return;
    if($ref['kind'] === 'MINION') HellbreakDealMinionDamage($descriptor, $amount, $sourceDescriptor, $damageType, $eventPlayer);
    else HellbreakDealMonsterDamage($ref['zonePlayer'], $amount);
}

function HellbreakPumpAttackEventQueues(): void {
    $controller = new DecisionQueueController();
    $controller->ExecuteStaticMethods(1, '-');
    $controller->ExecuteStaticMethods(2, '-');
}

function HellbreakQueueAttackEventBarrier(array $context, string $stage, int $token = 0, bool $pump = true): bool {
    $stage = strtoupper(trim($stage));
    if(!in_array($stage, ['ATTACK_DECLARED', 'TARGET_DECLARED', 'DEFENDER_DECLARED', 'DAMAGE', 'COMPLETION'], true)) return false;
    if($token <= 0) {
        $token = intval(DecisionQueueController::GetVariable('HellbreakAttackBarrierToken')) + 1;
        DecisionQueueController::StoreVariable('HellbreakAttackBarrierToken', $token);
    }
    DecisionQueueController::StoreVariable('HellbreakPendingAttackBarrier', [
        'token' => $token,
        'stage' => $stage,
        'context' => $context,
        'reached' => ['1' => false, '2' => false],
    ]);
    foreach([1, 2] as $player) {
        DecisionQueueController::AddDecision(
            $player,
            'CUSTOM',
            'HellbreakAttackEventBarrier|' . $stage . '|' . $token,
            100,
            '',
            1
        );
    }
    if($pump) HellbreakPumpAttackEventQueues();
    return true;
}

function HellbreakReachAttackEventBarrier(int $player, string $stage, int $token): bool {
    $pending = DecisionQueueController::GetVariable('HellbreakPendingAttackBarrier');
    $stage = strtoupper(trim($stage));
    if(!is_array($pending) || intval($pending['token'] ?? 0) !== $token || strval($pending['stage'] ?? '') !== $stage) return false;
    $pending['reached'][strval($player)] = true;
    DecisionQueueController::StoreVariable('HellbreakPendingAttackBarrier', $pending);
    if(empty($pending['reached']['1']) || empty($pending['reached']['2'])) return true;
    $context = $pending['context'] ?? [];
    if($stage === 'ATTACK_DECLARED') return HellbreakContinueAfterAttackDeclared($context);
    if($stage === 'TARGET_DECLARED') return HellbreakContinueAfterTargetDeclared($context);
    if($stage === 'DEFENDER_DECLARED') return HellbreakContinueAfterDefenderDeclared($context);
    if($stage === 'DAMAGE') return HellbreakAfterAttackDamageEvents($context, $token);
    return HellbreakCompleteAttackAfterEvents($context);
}

function HellbreakAfterAttackDamageEvents(array $context, int $token): bool {
    $attackingPlayer = intval($context['player'] ?? 0);
    $attackerDescriptor = $context['attacker'] ?? null;
    $recipientDescriptor = $context['recipient'] ?? null;
    $defenderDescriptor = $context['defender'] ?? null;
    HellbreakQueueAttackEventBarrier($context, 'COMPLETION', $token, false);
    if(is_array($recipientDescriptor)) {
        HellbreakCheckLethal(
            $recipientDescriptor,
            $attackerDescriptor,
            $attackingPlayer,
            strval($context['attackerMZ'] ?? '')
        );
    }
    if(is_array($defenderDescriptor) && is_array($attackerDescriptor)) {
        HellbreakCheckLethal(
            $attackerDescriptor,
            $defenderDescriptor,
            HellbreakOtherPlayer($attackingPlayer),
            strval($context['defenderSourceMZ'] ?? '')
        );
    }
    if(function_exists('HellbreakCombatCompletedHook')) {
        HellbreakCombatCompletedHook(
            $attackingPlayer,
            strval($context['attackerMZ'] ?? ''),
            strval($context['targetMZ'] ?? ''),
            strval($context['defenderMZ'] ?? '')
        );
    }
    HellbreakPumpAttackEventQueues();
    return true;
}

function HellbreakCompleteAttackAfterEvents(array $context): bool {
    DecisionQueueController::StoreVariable('HellbreakPendingAttackBarrier', []);
    $attackingPlayer = intval($context['player'] ?? 0);
    return intval(GetWinner()) > 0 ? true : HellbreakFinishNormalAction($attackingPlayer, 'ATTACK', [
        'attacker' => $context['attacker'] ?? null,
        'target' => $context['target'] ?? null,
    ]);
}

function HellbreakFinishAttackResolution(array $context): bool {
    return HellbreakQueueAttackEventBarrier($context, 'DAMAGE');
}

function HellbreakResolveAttack(int $attackingPlayer, ?array $defenderDescriptor): bool {
    $pending = DecisionQueueController::GetVariable('HellbreakPendingAttack');
    if(!is_array($pending) || intval($pending['player'] ?? 0) !== $attackingPlayer) return false;
    $attacker = HellbreakResolveBattlefieldDescriptor($pending['attacker'] ?? null);
    $target = HellbreakResolveBattlefieldDescriptor($pending['target'] ?? null);
    $defender = $defenderDescriptor === null ? null : HellbreakResolveBattlefieldDescriptor($defenderDescriptor);
    if($attacker === null || $target === null) return false;

    $attackerDamage = HellbreakCardCombatValue((string)$attacker['object']->CardID, $attacker['object'], $attackingPlayer);
    $damageRecipient = $defender ?? $target;
    $returnDamage = $defender === null ? 0 : HellbreakCardCombatValue((string)$defender['object']->CardID, $defender['object'], intval($defender['object']->Controller ?? HellbreakOtherPlayer($attackingPlayer)));
    $attackerDescriptor = HellbreakBattlefieldDescriptor($attacker);
    $recipientDescriptor = HellbreakBattlefieldDescriptor($damageRecipient);
    if($damageRecipient['kind'] === 'MINION') {
        HellbreakApplyCharacterDamage($recipientDescriptor, $attackerDamage, $attackerDescriptor, 'COMBAT_ATTACK', $attackingPlayer);
    }
    if($defender !== null && $attacker['kind'] !== 'MONSTER') {
        HellbreakApplyCharacterDamage(
            $attackerDescriptor,
            $returnDamage,
            $defenderDescriptor,
            'COMBAT_RETALIATION',
            HellbreakOtherPlayer($attackingPlayer)
        );
    }
    $context = [
        'type' => 'ATTACK', 'player' => $attackingPlayer, 'attacker' => $attackerDescriptor,
        'target' => HellbreakBattlefieldDescriptor($target), 'recipient' => $recipientDescriptor,
        'defender' => $defenderDescriptor, 'attackerDamage' => $attackerDamage, 'returnDamage' => $returnDamage,
        'attackerMZ' => HellbreakMZForBattlefieldDescriptor($attackingPlayer, $attackerDescriptor),
        'targetMZ' => HellbreakMZForBattlefieldDescriptor($attackingPlayer, HellbreakBattlefieldDescriptor($target)),
        'defenderMZ' => $defenderDescriptor === null ? '' : HellbreakMZForBattlefieldDescriptor($attackingPlayer, $defenderDescriptor),
        'defenderSourceMZ' => $defenderDescriptor === null ? '' : HellbreakMZForBattlefieldDescriptor(HellbreakOtherPlayer($attackingPlayer), $defenderDescriptor),
    ];
    if($damageRecipient['kind'] === 'MONSTER' && $attackerDamage > 0) {
        $attackerDamage = HellbreakModifiedDamageAmount(
            $damageRecipient,
            $attackerDamage,
            'COMBAT_ATTACK',
            $attacker['object'] ?? null,
            true
        );
        $context['attackerDamage'] = $attackerDamage;
        if($attackerDamage > 0 && function_exists('HellbreakDamageDealtHook')) {
            HellbreakDamageDealtHook(
                $recipientDescriptor,
                $attackerDamage,
                $attackerDescriptor,
                'COMBAT_ATTACK',
                $attackingPlayer
            );
        }
        if($attackerDamage <= 0) return HellbreakFinishAttackResolution($context);
        return HellbreakStartMonsterDamage(
            intval($damageRecipient['zonePlayer']),
            $attackerDamage,
            $context,
            $attackerDescriptor,
            'COMBAT_ATTACK',
            true
        );
    }
    return HellbreakFinishAttackResolution($context);
}

function HellbreakLegalSchemers(int $player): array {
    if(GetCurrentPhase() !== 'HORROR' || intval(GetTurnPlayer()) !== $player || intval(GetSlumberPlayer()) === $player) return [];
    $schemers = [];
    foreach(HellbreakLiveZoneObjects(GetMonster($player)) as $index => $monster) {
        $ref = HellbreakBattlefieldRef($player, 'myMonster-' . $index);
        if($ref !== null && HellbreakIsReadyControlledCharacter($ref, $player) && count(HellbreakCardSchemeIcons((string)$monster->CardID)) > 0) $schemers[] = 'myMonster-' . $index;
    }
    foreach(HellbreakLiveZoneObjects(GetCharacters($player)) as $index => $character) {
        $ref = HellbreakBattlefieldRef($player, 'myCharacters-' . $index);
        if($ref !== null && HellbreakIsReadyControlledCharacter($ref, $player) && count(HellbreakCardSchemeIcons((string)$character->CardID)) > 0) $schemers[] = 'myCharacters-' . $index;
    }
    return $schemers;
}

function HellbreakPumpSchemeEventQueues(): void {
    $controller = new DecisionQueueController();
    $controller->ExecuteStaticMethods(1, '-');
    $controller->ExecuteStaticMethods(2, '-');
}

function HellbreakQueueSchemeEventBarrier(array $context, string $stage, int $token = 0, bool $pump = true): bool {
    $stage = strtoupper(trim($stage));
    if(!in_array($stage, ['STARTED', 'ICON', 'LOCATION_TAKEN'], true)) return false;
    if($token <= 0) {
        $token = intval(DecisionQueueController::GetVariable('HellbreakSchemeBarrierToken')) + 1;
        DecisionQueueController::StoreVariable('HellbreakSchemeBarrierToken', $token);
    }
    DecisionQueueController::StoreVariable('HellbreakPendingSchemeBarrier', [
        'token' => $token,
        'stage' => $stage,
        'context' => $context,
        'reached' => ['1' => false, '2' => false],
    ]);
    foreach([1, 2] as $player) {
        DecisionQueueController::AddDecision(
            $player,
            'CUSTOM',
            'HellbreakSchemeEventBarrier|' . $stage . '|' . $token,
            100,
            '',
            1
        );
    }
    if($pump) HellbreakPumpSchemeEventQueues();
    return true;
}

function HellbreakReachSchemeEventBarrier(int $player, string $stage, int $token): bool {
    $pending = DecisionQueueController::GetVariable('HellbreakPendingSchemeBarrier');
    $stage = strtoupper(trim($stage));
    if(!is_array($pending) || intval($pending['token'] ?? 0) !== $token || strval($pending['stage'] ?? '') !== $stage) return false;
    $pending['reached'][strval($player)] = true;
    DecisionQueueController::StoreVariable('HellbreakPendingSchemeBarrier', $pending);
    if(empty($pending['reached']['1']) || empty($pending['reached']['2'])) return true;
    DecisionQueueController::StoreVariable('HellbreakPendingSchemeBarrier', []);
    $context = $pending['context'] ?? [];
    if($stage === 'STARTED') return HellbreakContinueScheme();
    if($stage === 'ICON') return HellbreakResolveSchemeIconEffect($context);
    return HellbreakContinueAfterLocationTaken($context);
}

function HellbreakBeginScheme(int $player, array $schemer, int $locationSlot): bool {
    if(!in_array($locationSlot, [1, 2], true)) return false;
    $schemer['object']->Status = 1;
    DecisionQueueController::StoreVariable('HellbreakPendingScheme', [
        'player' => $player, 'schemer' => HellbreakBattlefieldDescriptor($schemer),
        'locationSlot' => $locationSlot, 'icons' => HellbreakCardSchemeIcons((string)$schemer['object']->CardID), 'index' => 0,
    ]);
    $context = [
        'player' => $player,
        'schemer' => HellbreakBattlefieldDescriptor($schemer),
        'locationSlot' => $locationSlot,
    ];
    HellbreakQueueSchemeEventBarrier($context, 'STARTED', 0, false);
    if(function_exists('HellbreakSchemeStartedHook')) HellbreakSchemeStartedHook($player, $schemer, $locationSlot);
    HellbreakPumpSchemeEventQueues();
    return true;
}

function HellbreakChooseSchemer(int $player, string $mzID): bool {
    if(!in_array($mzID, HellbreakLegalSchemers($player), true)) return false;
    $schemer = HellbreakBattlefieldRef($player, $mzID);
    if($schemer === null) return false;
    if($schemer['kind'] === 'MONSTER') {
        DecisionQueueController::StoreVariable('HellbreakPendingSchemer', ['player' => $player, 'schemer' => HellbreakBattlefieldDescriptor($schemer)]);
        DecisionQueueController::AddDecision($player, 'MZMODAL', '1|1|Location_1&Location_2', 0, 'Choose_the_monsters_scheme_location');
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'HellbreakChooseSchemeLocation', 1);
        return true;
    }
    return HellbreakBeginScheme($player, $schemer, HellbreakCharacterLocation($schemer));
}

function HellbreakChooseSchemeLocation(int $player, string $selection): bool {
    if(!preg_match('/^[01]$/', trim($selection))) return false;
    $pending = DecisionQueueController::GetVariable('HellbreakPendingSchemer');
    if(!is_array($pending) || intval($pending['player'] ?? 0) !== $player) return false;
    $schemer = HellbreakResolveBattlefieldDescriptor($pending['schemer'] ?? null);
    if($schemer === null || !HellbreakIsReadyControlledCharacter($schemer, $player)) return false;
    return HellbreakBeginScheme($player, $schemer, intval($selection) + 1);
}

function HellbreakIndirectTargets(int $receivingPlayer, int $amount): array {
    $targets = [];
    foreach(HellbreakLiveZoneObjects(GetMonster($receivingPlayer)) as $index => $monster) $targets['myMonster-' . $index] = $amount;
    foreach(HellbreakLiveZoneObjects(GetCharacters($receivingPlayer)) as $index => $character) {
        if(intval($character->Controller) !== $receivingPlayer) continue;
        $remaining = HellbreakCardHealthValue((string)$character->CardID, $character, $receivingPlayer) - intval($character->Damage);
        if($remaining > 0) $targets['myCharacters-' . $index] = $remaining;
    }
    return $targets;
}

function HellbreakQueueIndirectDamageTo(int $sourcePlayer, int $receiver, int $amount): bool {
    if(!in_array($sourcePlayer, [1, 2], true) || !in_array($receiver, [1, 2], true) || $amount <= 0) return false;
    $targets = HellbreakIndirectTargets($receiver, $amount);
    if(count($targets) === 0) return false;
    DecisionQueueController::StoreVariable('HellbreakPendingIndirect', ['sourcePlayer' => $sourcePlayer, 'receiver' => $receiver, 'amount' => $amount, 'targets' => $targets]);
    if(HellbreakIsAutoSetupPlayer($receiver)) return HellbreakResolveIndirectAssignment($receiver, 'myMonster-0:' . $amount);
    $specs = [];
    foreach($targets as $mzID => $cap) $specs[] = $mzID . ':' . $cap;
    DecisionQueueController::AddDecision($receiver, 'MZSPLITASSIGN', $amount . '|' . implode('&', $specs), 0, 'Assign_the_indirect_damage');
    DecisionQueueController::AddDecision($receiver, 'CUSTOM', 'HellbreakResolveIndirectAssignment', 1);
    return true;
}

function HellbreakQueueIndirectDamage(int $sourcePlayer, int $amount): bool {
    return HellbreakQueueIndirectDamageTo($sourcePlayer, HellbreakOtherPlayer($sourcePlayer), $amount);
}

function HellbreakParseAssignment(string $selection): array {
    $assignment = [];
    foreach(array_filter(array_map('trim', explode(',', $selection))) as $part) {
        $split = strrpos($part, ':');
        if($split === false) return [];
        $mzID = substr($part, 0, $split);
        $amount = substr($part, $split + 1);
        if($mzID === '' || !preg_match('/^\d+$/', $amount) || isset($assignment[$mzID])) return [];
        $assignment[$mzID] = intval($amount);
    }
    return $assignment;
}

function HellbreakResolveIndirectAssignment(int $receivingPlayer, string $selection): bool {
    $pending = DecisionQueueController::GetVariable('HellbreakPendingIndirect');
    if(!is_array($pending) || intval($pending['receiver'] ?? 0) !== $receivingPlayer) return false;
    $allowed = $pending['targets'] ?? [];
    $assignment = HellbreakParseAssignment($selection);
    if(!is_array($allowed) || array_sum($assignment) !== intval($pending['amount'] ?? 0)) return false;
    foreach($assignment as $mzID => $amount) {
        if(!isset($allowed[$mzID]) || $amount < 0 || $amount > intval($allowed[$mzID])) return false;
    }
    $minionDescriptors = [];
    $monsterDamage = 0;
    foreach($assignment as $mzID => $amount) {
        if($amount <= 0) continue;
        $ref = HellbreakBattlefieldRef($receivingPlayer, $mzID);
        if($ref === null) return false;
        $descriptor = HellbreakBattlefieldDescriptor($ref);
        if($ref['kind'] === 'MINION') {
            HellbreakApplyCharacterDamage($descriptor, $amount);
            $minionDescriptors[] = $descriptor;
        } else $monsterDamage += $amount;
    }
    foreach($minionDescriptors as $descriptor) HellbreakCheckLethal($descriptor);
    if($monsterDamage > 0) return HellbreakStartMonsterDamage(
        $receivingPlayer,
        $monsterDamage,
        ['type' => 'SCHEME_INDIRECT'],
        null,
        'INDIRECT'
    );
    $scheme = DecisionQueueController::GetVariable('HellbreakPendingScheme');
    if(is_array($scheme)) {
        $scheme['index'] = intval($scheme['index'] ?? 0) + 1;
        DecisionQueueController::StoreVariable('HellbreakPendingScheme', $scheme);
    }
    return HellbreakContinueScheme();
}

function HellbreakQueueForesee(int $player, int $amount): bool {
    $deck = &GetDeck($player);
    HellbreakReindexZone($deck);
    $count = min($amount, count($deck));
    if($count <= 0) return false;
    $cards = [];
    for($i = 0; $i < $count; ++$i) $cards[] = (string)$deck[$i]->CardID;
    DecisionQueueController::StoreVariable('HellbreakPendingForeseeP' . $player, $cards);
    DecisionQueueController::AddDecision($player, 'MZREARRANGE', 'Top=' . implode(',', $cards) . ';Bottom=', 0, 'Arrange_foreseen_cards_on_top_or_bottom');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'HellbreakResolveForesee', 1);
    return true;
}

function HellbreakParsePiles(string $selection): ?array {
    $piles = [];
    foreach(explode(';', $selection) as $segment) {
        $parts = explode('=', trim($segment), 2);
        if(count($parts) !== 2) return null;
        $name = strtoupper(trim($parts[0]));
        if(!in_array($name, ['TOP', 'BOTTOM'], true) || isset($piles[$name])) return null;
        $piles[$name] = trim($parts[1]) === '' ? [] : array_values(array_filter(array_map('trim', explode(',', $parts[1])), fn($v) => $v !== ''));
    }
    return isset($piles['TOP'], $piles['BOTTOM']) ? $piles : null;
}

function HellbreakResolveForesee(int $player, string $selection): bool {
    $snapshot = DecisionQueueController::GetVariable('HellbreakPendingForeseeP' . $player);
    $piles = HellbreakParsePiles($selection);
    if(!is_array($snapshot) || $piles === null) return false;
    $submitted = array_merge($piles['TOP'], $piles['BOTTOM']);
    $expected = $snapshot;
    sort($submitted); sort($expected);
    if($submitted !== $expected) return false;
    $deck = &GetDeck($player);
    HellbreakReindexZone($deck);
    $count = count($snapshot);
    $current = array_map(fn($obj) => (string)$obj->CardID, array_slice($deck, 0, $count));
    $a = $current; $b = $snapshot; sort($a); sort($b);
    if($a !== $b) return false;
    $objectsByID = [];
    foreach(array_splice($deck, 0, $count) as $object) $objectsByID[(string)$object->CardID][] = $object;
    $take = function(string $cardID) use (&$objectsByID) { return array_shift($objectsByID[$cardID]); };
    $topObjects = array_map($take, $piles['TOP']);
    $bottomObjects = array_map($take, $piles['BOTTOM']);
    $deck = array_merge($topObjects, $deck, $bottomObjects);
    HellbreakReindexZone($deck);
    $scheme = DecisionQueueController::GetVariable('HellbreakPendingScheme');
    if(is_array($scheme)) {
        $scheme['index'] = intval($scheme['index'] ?? 0) + 1;
        DecisionQueueController::StoreVariable('HellbreakPendingScheme', $scheme);
    }
    return HellbreakContinueScheme();
}

function HellbreakLocationBySlot(int $slot): ?object {
    foreach(HellbreakLiveZoneObjects(GetLocations()) as $location) if(intval($location->Slot) === $slot) return $location;
    return null;
}

function HellbreakCollectLocationReward(int $player, string $cardID): array {
    $resources = HellbreakCardResources($cardID);
    $blood = &BloodValue($player); $blood += intval($resources['blood']);
    $malice = &MaliceValue($player); $malice += intval($resources['malice']);
    $drawn = HellbreakDrawCards($player, intval($resources['draw']));
    return ['blood' => intval($resources['blood']), 'malice' => intval($resources['malice']), 'draw' => intval($resources['draw']), 'drawn' => $drawn];
}

function HellbreakTakeLocation(int $player, int $slot, array $continuation = []): bool {
    $location = HellbreakLocationBySlot($slot);
    if($location === null) return false;
    $field = $player === 1 ? 'MaliceP1' : 'MaliceP2';
    if(intval($location->$field) < intval($location->Threshold)) return false;
    $previousController = intval($location->Controller ?? 0);
    $location->MaliceP1 = 0;
    $location->MaliceP2 = 0;
    $location->Controller = $player;
    $reward = HellbreakCollectLocationReward($player, (string)$location->CardID);
    $locationName = function_exists('CardName') ? trim((string)CardName((string)$location->CardID)) : '';
    HellbreakAddPublicLog('Player ' . $player . ' took control of ' . ($locationName !== '' ? $locationName : ('Location ' . $slot)) . '.', 'LOCATION');
    $context = [
        'player' => $player,
        'locationSlot' => $slot,
        'previousController' => $previousController,
        'reward' => $reward,
        'continuation' => $continuation,
    ];
    HellbreakQueueSchemeEventBarrier($context, 'LOCATION_TAKEN', 0, false);
    if(function_exists('HellbreakLocationTakenHook')) {
        HellbreakLocationTakenHook($player, $location, $previousController);
    }
    HellbreakPumpSchemeEventQueues();
    return true;
}

function HellbreakContinueAfterLocationTaken(array $context): bool {
    $continuation = $context['continuation'] ?? [];
    if(!is_array($continuation) || strtoupper(strval($continuation['type'] ?? '')) !== 'SCHEME_ICON') return true;
    $scheme = DecisionQueueController::GetVariable('HellbreakPendingScheme');
    if(!is_array($scheme)) return false;
    $scheme['index'] = intval($continuation['index'] ?? $scheme['index'] ?? 0) + 1;
    DecisionQueueController::StoreVariable('HellbreakPendingScheme', $scheme);
    return HellbreakContinueScheme();
}

function HellbreakAddLocationMalice(int $player, int $slot, int $amount, array $continuation = []): bool {
    $location = HellbreakLocationBySlot($slot);
    if($location === null || $amount <= 0) return false;
    $field = $player === 1 ? 'MaliceP1' : 'MaliceP2';
    $location->$field = intval($location->$field) + $amount;
    HellbreakTakeLocation($player, $slot, $continuation);
    return true;
}

function HellbreakResolveSchemeIconEffect(array $context): bool {
    $pending = DecisionQueueController::GetVariable('HellbreakPendingScheme');
    if(!is_array($pending)) return false;
    $player = intval($pending['player'] ?? 0);
    $index = intval($context['index'] ?? $pending['index'] ?? 0);
    if($index !== intval($pending['index'] ?? 0)) return false;
    $type = strtoupper(strval($context['type'] ?? ''));
    $amount = max(0, intval($context['amount'] ?? 0));
    if($type === 'HAUNT') {
        $slot = intval($pending['locationSlot'] ?? 0);
        $location = HellbreakLocationBySlot($slot);
        $field = $player === 1 ? 'MaliceP1' : 'MaliceP2';
        $willTake = $location !== null && intval($location->$field) + $amount >= intval($location->Threshold ?? 0);
        HellbreakAddLocationMalice($player, $slot, $amount, ['type' => 'SCHEME_ICON', 'index' => $index]);
        if($willTake) return true;
        $pending['index'] = $index + 1;
        DecisionQueueController::StoreVariable('HellbreakPendingScheme', $pending);
        return HellbreakContinueScheme();
    }
    if($type === 'PROWL') return HellbreakQueueIndirectDamage($player, $amount);
    if($type === 'FORESEE') {
        if(HellbreakQueueForesee($player, $amount)) return true;
        $pending['index'] = $index + 1;
        DecisionQueueController::StoreVariable('HellbreakPendingScheme', $pending);
        return HellbreakContinueScheme();
    }
    $pending['index'] = $index + 1;
    DecisionQueueController::StoreVariable('HellbreakPendingScheme', $pending);
    return HellbreakContinueScheme();
}

function HellbreakContinueScheme(): bool {
    $pending = DecisionQueueController::GetVariable('HellbreakPendingScheme');
    if(!is_array($pending)) return false;
    $player = intval($pending['player'] ?? 0);
    $icons = $pending['icons'] ?? [];
    $index = intval($pending['index'] ?? 0);
    if($index >= count($icons)) return HellbreakFinishNormalAction($player, 'SCHEME', ['schemer' => $pending['schemer'] ?? null]);
    $icon = $icons[$index];
    $type = strtoupper((string)($icon['type'] ?? ''));
    $amount = max(0, intval($icon['value'] ?? 0));
    $context = ['player' => $player, 'index' => $index, 'type' => $type, 'amount' => $amount];
    HellbreakQueueSchemeEventBarrier($context, 'ICON', 0, false);
    if(function_exists('HellbreakSchemeIconHook')) HellbreakSchemeIconHook($player, $type, $amount, $pending);
    HellbreakPumpSchemeEventQueues();
    return true;
}

?>
