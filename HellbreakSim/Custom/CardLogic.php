<?php

/**
 * Generator-backed card rules for Hellbreak.
 *
 * Phase, combat, and zone movement code calls the schema macros. Card-specific
 * implementations live in CardEditor; this file only supplies shared dispatch,
 * active-listener discovery, and value-modifier evaluation.
 */

function HellbreakMacroAbilityArrayName(string $macroName): string {
    return lcfirst($macroName) . 'Abilities';
}

function HellbreakMacroPrereqArrayName(string $macroName): string {
    return lcfirst($macroName) . 'Prereqs';
}

function HellbreakRunCardMacroAbilities(string $macroName, int $player, string $cardID, ?int $onlyIndex = null, array $eventParams = []): int {
    if($cardID === '') return 0;
    $abilities = $GLOBALS[HellbreakMacroAbilityArrayName($macroName)] ?? [];
    $prereqs = $GLOBALS[HellbreakMacroPrereqArrayName($macroName)] ?? [];
    if(!is_array($abilities)) return 0;

    $indexes = [];
    if($onlyIndex !== null) {
        $indexes[] = max(0, $onlyIndex);
    } else {
        $countFunction = 'Card' . $macroName . 'Count';
        $count = function_exists($countFunction) ? max(0, intval($countFunction($cardID))) : 0;
        if($count > 0) {
            for($i = 0; $i < $count; ++$i) $indexes[] = $i;
        } else {
            $prefix = $cardID . ':';
            foreach(array_keys($abilities) as $key) {
                if(strpos((string)$key, $prefix) !== 0) continue;
                $index = substr((string)$key, strlen($prefix));
                if(ctype_digit($index)) $indexes[] = intval($index);
            }
            sort($indexes);
        }
    }

    $resolved = 0;
    foreach(array_values(array_unique($indexes)) as $index) {
        $key = $cardID . ':' . $index;
        if(!isset($abilities[$key]) || !is_callable($abilities[$key])) continue;
        if(isset($prereqs[$key]) && is_callable($prereqs[$key])) {
            $prereqArgs = array_merge([$player], array_values($eventParams));
            $reflection = new ReflectionFunction($prereqs[$key]);
            $prereqArgs = array_slice($prereqArgs, 0, $reflection->getNumberOfParameters());
            if(!call_user_func_array($prereqs[$key], $prereqArgs)) continue;
        }
        $abilities[$key]($player);
        ++$resolved;
    }
    return $resolved;
}

function HellbreakActiveListenerZones(): array {
    return ['Monster', 'Characters', 'Assets', 'Locations'];
}

function HellbreakDispatchMacroEvent(string $macroName, int $player, array $eventParams, string $sourceCardID = '', ?int $abilityIndex = null): int {
    $resolved = 0;
    if($sourceCardID !== '') {
        $resolved += HellbreakRunCardMacroAbilities($macroName, $player, $sourceCardID, $abilityIndex, $eventParams);
    }
    if(function_exists('DispatchMacroListeners')) {
        $resolved += intval(DispatchMacroListeners($macroName, $player, $eventParams, HellbreakActiveListenerZones()));
    }
    return $resolved;
}

function HellbreakMacroCardIDFromMZ(string $mzID): string {
    $obj = GetZoneObject($mzID);
    if($obj === null || !is_object($obj) || (isset($obj->removed) && $obj->removed)) return '';
    return strval($obj->CardID ?? '');
}

function HellbreakCardTraits(string $cardID): array {
    if(!function_exists('CardTraits')) return [];
    $traits = CardTraits($cardID);
    if(is_string($traits)) {
        $decoded = json_decode($traits, true);
        if(is_array($decoded)) $traits = $decoded;
        else $traits = preg_split('/\s*[,|]\s*/', $traits, -1, PREG_SPLIT_NO_EMPTY);
    }
    if(!is_array($traits)) return [];
    return array_values(array_unique(array_map(fn($trait) => strtoupper(trim(strval($trait))), $traits)));
}

function HellbreakCardHasTrait(string $cardID, string $trait): bool {
    return in_array(strtoupper(trim($trait)), HellbreakCardTraits($cardID), true);
}

function HellbreakCardRulesText(string $cardID, string $side = ''): string {
    if(function_exists('CardFaces')) {
        $faces = CardFaces($cardID);
        if(is_string($faces)) $faces = json_decode($faces, true);
        if(is_array($faces)) {
            $side = strtolower(trim($side));
            if($side !== '' && is_array($faces[$side] ?? null)) return strval($faces[$side]['text'] ?? '');
            $faceTexts = [];
            foreach($faces as $face) {
                if(is_array($face) && trim(strval($face['text'] ?? '')) !== '') $faceTexts[] = strval($face['text']);
            }
            if(count($faceTexts) > 0) return implode(' ', $faceTexts);
        }
    }
    if(function_exists('HellbreakFixtureCard')) {
        $fixture = HellbreakFixtureCard($cardID);
        if(is_array($fixture) && isset($fixture['text'])) return strval($fixture['text']);
    }
    return function_exists('CardText') ? strval(CardText($cardID)) : '';
}

function HellbreakKeywordValue(string $cardID, string $keyword, int $defaultValue = 1): int {
    $keyword = trim($keyword);
    if($keyword === '') return 0;
    $pattern = '/(?:^|[.\\s])' . preg_quote($keyword, '/') . '(?:\\s+(\\d+))?(?=[.\\s,;]|$)/i';
    if(!preg_match($pattern, HellbreakCardRulesText($cardID), $matches)) return 0;
    return isset($matches[1]) && $matches[1] !== '' ? max(0, intval($matches[1])) : max(0, $defaultValue);
}

function HellbreakCardHasKeyword(string $cardID, string $keyword): bool {
    return HellbreakKeywordValue($cardID, $keyword, 1) > 0;
}

function HellbreakObjectKeywordValue($object, string $keyword, int $defaultValue = 1): int {
    if(!is_object($object)) return 0;
    $cardID = strval($object->CardID ?? '');
    $text = HellbreakCardRulesText($cardID, strval($object->Side ?? ''));
    $pattern = '/(?:^|[.\\s])' . preg_quote(trim($keyword), '/') . '(?:\\s+(\\d+))?(?=[.\\s,;]|$)/i';
    $value = preg_match($pattern, $text, $matches)
        ? (isset($matches[1]) && $matches[1] !== '' ? max(0, intval($matches[1])) : max(0, $defaultValue))
        : 0;
    if(function_exists('HellbreakTurnEffectGrantsKeyword')) {
        // A temporarily granted keyword counts as having it; take the larger value.
        $value = max($value, HellbreakTurnEffectGrantsKeyword($object, $keyword));
    }
    if(function_exists('HellbreakApplyValueModifiers')) {
        $player = intval($object->Controller ?? $object->Owner ?? 0);
        $value = HellbreakApplyValueModifiers('KeywordModifier', $player, $object, $value, [$keyword]);
    }
    return max(0, $value);
}

function HellbreakObjectHasKeyword($object, string $keyword): bool {
    return HellbreakObjectKeywordValue($object, $keyword, 1) > 0;
}

function HellbreakObjectHasTrait($object, string $trait, int $player = 0): bool {
    if(!is_object($object)) return false;
    if(HellbreakCardHasTrait(strval($object->CardID ?? ''), $trait)) return true;
    if(function_exists('HellbreakTurnEffectGrantsTrait') && HellbreakTurnEffectGrantsTrait($object, $trait)) return true;
    if(!function_exists('HellbreakApplyValueModifiers')) return false;
    if(!in_array($player, [1, 2], true)) {
        $player = intval($object->Controller ?? $object->Owner ?? 0);
    }
    return HellbreakApplyValueModifiers('TraitModifier', $player, $object, 0, [$trait]) > 0;
}

function HellbreakMZForObject(int $viewer, $needle): string {
    if(!is_object($needle)) return '';
    foreach([1, 2] as $owner) {
        $prefix = $owner === $viewer ? 'my' : 'their';
        foreach([
            'Monster' => GetMonster($owner), 'Characters' => GetCharacters($owner),
            'Assets' => GetAssets($owner), 'Crypt' => GetCrypt($owner),
        ] as $zoneName => $zone) {
            foreach($zone as $index => $obj) {
                if(!is_object($obj) || (isset($obj->removed) && $obj->removed)) continue;
                if($obj === $needle || (!empty($needle->UniqueID) && intval($obj->UniqueID ?? 0) === intval($needle->UniqueID))) {
                    return $prefix . $zoneName . '-' . $index;
                }
            }
        }
    }
    foreach(GetLocations() as $index => $obj) {
        if(!is_object($obj) || (isset($obj->removed) && $obj->removed)) continue;
        if($obj === $needle || (!empty($needle->UniqueID) && intval($obj->UniqueID ?? 0) === intval($needle->UniqueID))) {
            return 'Locations-' . $index;
        }
    }
    return '';
}

function HellbreakCardPlayedHook(int $player, string $cardID, string $type, $playedObject, ?int $locationSlot, string $fromZone = 'Hand'): void {
    $mzID = HellbreakMZForObject($player, $playedObject);
    if($mzID !== '' && function_exists('Played')) {
        Played($player, $mzID, $fromZone, intval($locationSlot));
        return;
    }
    HellbreakDispatchMacroEvent('Played', $player, [
        'mzID' => $mzID, 'fromZone' => $fromZone, 'locationSlot' => intval($locationSlot),
    ], $cardID);
}

$customDQHandlers['HellbreakAfterPlayedCard'] = function($player, $params, $lastDecision) {
    HellbreakAfterPlayedCard(intval($player));
};
$customDQHandlers['HellbreakChooseAbilityAction'] = function($player, $params, $lastDecision) {
    HellbreakChooseAbilityAction(intval($player), strval($lastDecision));
};
$customDQHandlers['HellbreakAfterActivatedAbility'] = function($player, $params, $lastDecision) {
    HellbreakAfterActivatedAbility(intval($player));
};

function HellbreakCanUseActivatedAbility($player, $mzID, $abilityIndex): bool {
    $player = intval($player);
    $abilityIndex = intval($abilityIndex);
    $mzID = strval($mzID);
    if(GetCurrentPhase() !== 'HORROR' || intval(GetTurnPlayer()) !== $player || intval(GetWinner()) > 0) return false;
    if(intval(GetSlumberPlayer()) === $player) return false;
    $source = GetZoneObject($mzID);
    if(!is_object($source)) return false;
    // Locations are contested permanents. Their own generated prerequisites decide
    // whether either player, only the controller, or neither player may use them.
    if(!preg_match('/^Locations-\d+$/', $mzID) && intval($source->Controller ?? 0) !== $player) return false;
    $cardID = HellbreakMacroCardIDFromMZ(strval($mzID));
    if($cardID === '') return false;
    $countFunction = 'CardActivateAbilityCount';
    if(!function_exists($countFunction) || $abilityIndex < 0 || $abilityIndex >= intval($countFunction($cardID))) return false;
    return !function_exists('HellbreakCanActivateAbility') || HellbreakCanActivateAbility($player, $mzID, $abilityIndex);
}

function HellbreakActivatableAbilities(int $player): array {
    if(!function_exists('CardActivateAbilityCount')) return [];
    $candidates = [];
    foreach([
        'myMonster' => GetMonster($player),
        'myCharacters' => GetCharacters($player),
        'myAssets' => GetAssets($player),
    ] as $prefix => $zone) {
        foreach(HellbreakLiveZoneObjects($zone) as $index => $object) {
            if(intval($object->Controller ?? 0) === $player) $candidates[] = [$prefix . '-' . $index, $object];
        }
    }
    foreach(HellbreakLiveZoneObjects(GetLocations()) as $index => $object) {
        $candidates[] = ['Locations-' . $index, $object];
    }

    $abilities = [];
    foreach($candidates as [$mzID, $object]) {
        $cardID = strval($object->CardID ?? '');
        $count = intval(CardActivateAbilityCount($cardID));
        for($abilityIndex = 0; $abilityIndex < $count; ++$abilityIndex) {
            if(!HellbreakCanUseActivatedAbility($player, $mzID, $abilityIndex)) continue;
            $cardName = function_exists('CardName') ? trim(strval(CardName($cardID))) : $cardID;
            $abilityName = function_exists('CardActivateAbilityCountNames')
                ? trim(strval(CardActivateAbilityCountNames($cardID, $abilityIndex))) : '';
            $abilities[] = [
                'mzID' => $mzID,
                'cardID' => $cardID,
                'abilityIndex' => $abilityIndex,
                'label' => $cardName . ($abilityName === '' ? '' : ': ' . $abilityName),
            ];
        }
    }
    return $abilities;
}

function HellbreakOnActivateAbility($player, $mzID, $abilityIndex): string {
    $cardID = HellbreakMacroCardIDFromMZ(strval($mzID));
    HellbreakDispatchMacroEvent('ActivateAbility', intval($player), [
        'mzID' => strval($mzID), 'abilityIndex' => intval($abilityIndex),
    ], $cardID, intval($abilityIndex));
    return 'ACTIVATE_ABILITY';
}

function HellbreakOnPlayed($player, $mzID, $fromZone, $locationSlot): string {
    $cardID = HellbreakMacroCardIDFromMZ(strval($mzID));
    HellbreakDispatchMacroEvent('Played', intval($player), [
        'mzID' => strval($mzID), 'fromZone' => strval($fromZone), 'locationSlot' => intval($locationSlot),
    ], $cardID);
    return 'PLAYED';
}

function HellbreakOnMoved($player, $mzID, $fromLocation, $toLocation): string {
    $cardID = HellbreakMacroCardIDFromMZ(strval($mzID));
    HellbreakDispatchMacroEvent('Moved', intval($player), [
        'mzID' => strval($mzID), 'fromLocation' => intval($fromLocation), 'toLocation' => intval($toLocation),
    ], $cardID);
    return 'MOVED';
}

function HellbreakOnAttackDeclared($player, $mzID, $locationSlot): string {
    $cardID = HellbreakMacroCardIDFromMZ(strval($mzID));
    HellbreakDispatchMacroEvent('AttackDeclared', intval($player), [
        'mzID' => strval($mzID), 'locationSlot' => intval($locationSlot),
    ], $cardID);
    return 'ATTACK_DECLARED';
}

function HellbreakAttackDeclaredHook(int $player, array $attacker): void {
    $mzID = HellbreakMZForObject($player, $attacker['object'] ?? null);
    $locationSlot = $attacker['kind'] === 'MINION'
        ? HellbreakCharacterLocation($attacker)
        : intval((DecisionQueueController::GetVariable('HellbreakPendingAttack'))['locationSlot'] ?? 0);
    if(function_exists('AttackDeclared')) {
        AttackDeclared($player, $mzID, $locationSlot);
        return;
    }
    HellbreakOnAttackDeclared($player, $mzID, $locationSlot);
}

function HellbreakOnTargetDeclared($player, $mzID, $attackerMZ, $locationSlot): string {
    $cardID = HellbreakMacroCardIDFromMZ(strval($mzID));
    HellbreakDispatchMacroEvent('TargetDeclared', intval($player), [
        'mzID' => strval($mzID), 'attackerMZ' => strval($attackerMZ), 'locationSlot' => intval($locationSlot),
    ], $cardID);
    return 'TARGET_DECLARED';
}

function HellbreakAttackTargetDeclaredHook(int $player, array $target): void {
    $pending = DecisionQueueController::GetVariable('HellbreakPendingAttack');
    $mzID = HellbreakMZForObject($player, $target['object'] ?? null);
    $attackerMZ = HellbreakMZForBattlefieldDescriptor($player, is_array($pending) ? ($pending['attacker'] ?? null) : null);
    $locationSlot = intval(is_array($pending) ? ($pending['locationSlot'] ?? 0) : 0);
    if(function_exists('TargetDeclared')) {
        TargetDeclared($player, $mzID, $attackerMZ, $locationSlot);
        return;
    }
    HellbreakOnTargetDeclared($player, $mzID, $attackerMZ, $locationSlot);
}

function HellbreakOnDefenderDeclared($player, $mzID, $attackerMZ, $locationSlot): string {
    $cardID = HellbreakMacroCardIDFromMZ(strval($mzID));
    HellbreakDispatchMacroEvent('DefenderDeclared', intval($player), [
        'mzID' => strval($mzID), 'attackerMZ' => strval($attackerMZ), 'locationSlot' => intval($locationSlot),
    ], $cardID);
    return 'DEFENDER_DECLARED';
}

function HellbreakDefenderDeclaredHook(int $player, array $defender): void {
    $pending = DecisionQueueController::GetVariable('HellbreakPendingAttack');
    $mzID = HellbreakMZForObject($player, $defender['object'] ?? null);
    $attackerMZ = HellbreakMZForBattlefieldDescriptor($player, is_array($pending) ? ($pending['attacker'] ?? null) : null);
    $locationSlot = intval(is_array($pending) ? ($pending['locationSlot'] ?? 0) : 0);
    if(function_exists('DefenderDeclared')) {
        DefenderDeclared($player, $mzID, $attackerMZ, $locationSlot);
        return;
    }
    HellbreakOnDefenderDeclared($player, $mzID, $attackerMZ, $locationSlot);
}

function HellbreakOnCombatCompleted($player, $attackerMZ, $targetMZ, $defenderMZ): string {
    HellbreakDispatchMacroEvent('CombatCompleted', intval($player), [
        'attackerMZ' => strval($attackerMZ), 'targetMZ' => strval($targetMZ), 'defenderMZ' => strval($defenderMZ),
    ]);
    return 'COMBAT_COMPLETED';
}

function HellbreakCombatCompletedHook(int $player, string $attackerMZ, string $targetMZ, string $defenderMZ = ''): void {
    if(function_exists('CombatCompleted')) {
        CombatCompleted($player, $attackerMZ, $targetMZ, $defenderMZ);
        return;
    }
    HellbreakOnCombatCompleted($player, $attackerMZ, $targetMZ, $defenderMZ);
}

function HellbreakOnSchemeStarted($player, $mzID, $locationSlot): string {
    $cardID = HellbreakMacroCardIDFromMZ(strval($mzID));
    HellbreakDispatchMacroEvent('SchemeStarted', intval($player), [
        'mzID' => strval($mzID), 'locationSlot' => intval($locationSlot),
    ], $cardID);
    return 'SCHEME_STARTED';
}

function HellbreakSchemeStartedHook(int $player, array $schemer, int $locationSlot): void {
    $mzID = HellbreakMZForObject($player, $schemer['object'] ?? null);
    if(function_exists('SchemeStarted')) {
        SchemeStarted($player, $mzID, $locationSlot);
        return;
    }
    HellbreakOnSchemeStarted($player, $mzID, $locationSlot);
}

function HellbreakOnSchemeIcon($player, $mzID, $schemeType, $amount, $locationSlot): string {
    $cardID = HellbreakMacroCardIDFromMZ(strval($mzID));
    HellbreakDispatchMacroEvent('SchemeIcon', intval($player), [
        'mzID' => strval($mzID), 'schemeType' => strval($schemeType), 'amount' => intval($amount),
        'locationSlot' => intval($locationSlot),
    ], $cardID);
    return 'SCHEME_ICON';
}

function HellbreakSchemeIconHook(int $player, string $schemeType, int $amount, array $pending): void {
    $mzID = HellbreakMZForBattlefieldDescriptor($player, $pending['schemer'] ?? null);
    $locationSlot = intval($pending['locationSlot'] ?? 0);
    if(function_exists('SchemeIcon')) {
        SchemeIcon($player, $mzID, $schemeType, $amount, $locationSlot);
        return;
    }
    HellbreakOnSchemeIcon($player, $mzID, $schemeType, $amount, $locationSlot);
}

function HellbreakOnLocationTaken($player, $mzID, $locationSlot, $previousController): string {
    $cardID = HellbreakMacroCardIDFromMZ(strval($mzID));
    HellbreakDispatchMacroEvent('LocationTaken', intval($player), [
        'mzID' => strval($mzID), 'locationSlot' => intval($locationSlot),
        'previousController' => intval($previousController),
    ], $cardID);
    return 'LOCATION_TAKEN';
}

function HellbreakLocationTakenHook(int $player, $location, int $previousController): void {
    $mzID = HellbreakMZForObject($player, $location);
    $locationSlot = intval($location->Slot ?? 0);
    if(function_exists('LocationTaken')) {
        LocationTaken($player, $mzID, $locationSlot, $previousController);
        return;
    }
    HellbreakOnLocationTaken($player, $mzID, $locationSlot, $previousController);
}

function HellbreakOnDamageDealt($player, $sourceMZ, $targetMZ, $amount, $damageType): string {
    HellbreakDispatchMacroEvent('DamageDealt', intval($player), [
        'sourceMZ' => strval($sourceMZ), 'targetMZ' => strval($targetMZ),
        'amount' => intval($amount), 'damageType' => strval($damageType),
    ]);
    return 'DAMAGE_DEALT';
}

function HellbreakMZForBattlefieldDescriptor(int $viewer, ?array $descriptor): string {
    if($descriptor === null) return '';
    $ref = HellbreakResolveBattlefieldDescriptor($descriptor);
    if($ref !== null) return HellbreakMZForObject($viewer, $ref['object']);
    $kind = strtoupper(strval($descriptor['kind'] ?? ''));
    $zonePlayer = intval($descriptor['zonePlayer'] ?? 0);
    if($kind !== 'MONSTER' || !in_array($zonePlayer, [1, 2], true)) return '';
    foreach(HellbreakLiveZoneObjects(GetMonster($zonePlayer)) as $monster) {
        return HellbreakMZForObject($viewer, $monster);
    }
    return '';
}

function HellbreakDamageDealtHook(
    array $targetDescriptor,
    int $amount,
    ?array $sourceDescriptor = null,
    string $damageType = 'EFFECT',
    int $eventPlayer = 0
): void {
    if($amount <= 0) return;
    if(!in_array($eventPlayer, [1, 2], true)) {
        $source = $sourceDescriptor === null ? null : HellbreakResolveBattlefieldDescriptor($sourceDescriptor);
        $target = HellbreakResolveBattlefieldDescriptor($targetDescriptor);
        $eventPlayer = intval($source['object']->Controller ?? $target['zonePlayer'] ?? 0);
    }
    if(!in_array($eventPlayer, [1, 2], true)) return;
    $sourceMZ = HellbreakMZForBattlefieldDescriptor($eventPlayer, $sourceDescriptor);
    $targetMZ = HellbreakMZForBattlefieldDescriptor($eventPlayer, $targetDescriptor);
    if(function_exists('DamageDealt')) {
        DamageDealt($eventPlayer, $sourceMZ, $targetMZ, $amount, strtoupper($damageType));
        return;
    }
    HellbreakOnDamageDealt($eventPlayer, $sourceMZ, $targetMZ, $amount, strtoupper($damageType));
}

function HellbreakOnMinionKilled($player, $cardID, $owner, $locationSlot, $sourceMZ): string {
    HellbreakDispatchMacroEvent('MinionKilled', intval($player), [
        'cardID' => strval($cardID), 'owner' => intval($owner), 'locationSlot' => intval($locationSlot),
        'sourceMZ' => strval($sourceMZ),
    ], strval($cardID));
    return 'MINION_KILLED';
}

function HellbreakMinionKilledHook(int $owner, $object, string $sourceMZ = ''): void {
    if(!is_object($object)) return;
    // Before any MinionKilled listener runs, so observers see a board with the host's attachments
    // already gone rather than pointing at a UniqueID that has left play.
    if(function_exists('HellbreakDetachAssetsFromHost')) HellbreakDetachAssetsFromHost($object);
    $eventPlayer = $owner;
    if($sourceMZ !== '') {
        $source = GetZoneObject($sourceMZ);
        if(is_object($source) && in_array(intval($source->Controller ?? 0), [1, 2], true)) {
            $eventPlayer = intval($source->Controller);
        }
    }
    $cardID = strval($object->CardID ?? '');
    $locationSlot = intval($object->LocationSlot ?? 0);
    if(function_exists('MinionKilled')) {
        MinionKilled($eventPlayer, $cardID, $owner, $locationSlot, $sourceMZ);
        return;
    }
    HellbreakOnMinionKilled($eventPlayer, $cardID, $owner, $locationSlot, $sourceMZ);
}

function HellbreakOnMonsterHealthRevealed($player, $cardID, $owner): string {
    HellbreakDispatchMacroEvent('MonsterHealthRevealed', intval($player), [
        'cardID' => strval($cardID), 'owner' => intval($owner),
    ], strval($cardID));
    return 'MONSTER_HEALTH_REVEALED';
}

if(!function_exists('HellbreakMonsterHealthCardRevealedHook')) {
    function HellbreakMonsterHealthCardRevealedHook(int $player, $card): void {
        if(!is_object($card)) return;
        $cardID = strval($card->CardID ?? '');
        if(function_exists('MonsterHealthRevealed')) {
            MonsterHealthRevealed($player, $cardID, $player);
            return;
        }
        HellbreakOnMonsterHealthRevealed($player, $cardID, $player);
    }
}

function HellbreakOnJumpscareUsed($player, $cardID, $owner): string {
    HellbreakDispatchMacroEvent('JumpscareUsed', intval($player), [
        'cardID' => strval($cardID), 'owner' => intval($owner),
    ], strval($cardID));
    // Retain the pre-rules-name event as a listener compatibility alias.
    HellbreakDispatchMacroEvent('HealthAbilityUsed', intval($player), [
        'cardID' => strval($cardID), 'owner' => intval($owner),
    ], strval($cardID));
    return 'JUMPSCARE_USED';
}

function HellbreakOnHealthAbilityUsed($player, $cardID, $owner): string {
    return HellbreakOnJumpscareUsed($player, $cardID, $owner);
}

if(!function_exists('HellbreakJumpscareUsedHook')) {
    function HellbreakJumpscareUsedHook(int $player, string $cardID): void {
        if(function_exists('JumpscareUsed')) {
            JumpscareUsed($player, $cardID, $player);
            return;
        }
        HellbreakOnJumpscareUsed($player, $cardID, $player);
    }
}

if(!function_exists('HellbreakHealthAbilityUsedHook')) {
    function HellbreakHealthAbilityUsedHook(int $player, string $cardID): void {
        HellbreakJumpscareUsedHook($player, $cardID);
    }
}

function HellbreakOnMonsterFlipped($player, $mzID, $fromSide, $toSide): string {
    $cardID = HellbreakMacroCardIDFromMZ(strval($mzID));
    HellbreakDispatchMacroEvent('MonsterFlipped', intval($player), [
        'mzID' => strval($mzID), 'fromSide' => strval($fromSide), 'toSide' => strval($toSide),
    ], $cardID);
    return 'MONSTER_FLIPPED';
}

if(!function_exists('HellbreakMonsterFlippedHook')) {
    function HellbreakMonsterFlippedHook(int $player, $monster, string $fromSide, string $toSide): void {
        $mzID = HellbreakMZForObject($player, $monster);
        if(function_exists('MonsterFlipped')) {
            MonsterFlipped($player, $mzID, $fromSide, $toSide);
            return;
        }
        HellbreakOnMonsterFlipped($player, $mzID, $fromSide, $toSide);
    }
}

function HellbreakOnResourcesCollected($player, $owner, $blood, $malice, $draw): string {
    HellbreakDispatchMacroEvent('ResourcesCollected', intval($player), [
        'owner' => intval($owner), 'blood' => intval($blood), 'malice' => intval($malice), 'draw' => intval($draw),
    ]);
    return 'RESOURCES_COLLECTED';
}

function HellbreakResourcesCollectedHook(int $player, array $resources, int $drawn): void {
    $blood = intval($resources['blood'] ?? 0);
    $malice = intval($resources['malice'] ?? 0);
    if(function_exists('ResourcesCollected')) {
        ResourcesCollected($player, $player, $blood, $malice, $drawn);
        return;
    }
    HellbreakOnResourcesCollected($player, $player, $blood, $malice, $drawn);
}

function HellbreakOnInitiativeBidRevealed($player, $owner, $cardID, $bidValue): string {
    HellbreakDispatchMacroEvent('InitiativeBidRevealed', intval($player), [
        'owner' => intval($owner), 'cardID' => strval($cardID), 'bidValue' => intval($bidValue),
    ], strval($cardID));
    return 'INITIATIVE_BID_REVEALED';
}

function HellbreakInitiativeBidRevealedHook(array $bids, int $winner, int $previousInitiative): void {
    foreach([1, 2] as $owner) {
        $bid = is_array($bids[$owner] ?? null) ? $bids[$owner] : [];
        $cardID = strval($bid['cardID'] ?? '');
        $bidValue = intval($bid['cost'] ?? 0);
        if(function_exists('InitiativeBidRevealed')) InitiativeBidRevealed($owner, $owner, $cardID, $bidValue);
        else HellbreakOnInitiativeBidRevealed($owner, $owner, $cardID, $bidValue);
    }
}

function HellbreakOnInitiativeAssigned($player, $winner, $holder): string {
    HellbreakDispatchMacroEvent('InitiativeAssigned', intval($player), [
        'winner' => intval($winner), 'holder' => intval($holder),
    ]);
    return 'INITIATIVE_ASSIGNED';
}

function HellbreakInitiativeAssignedHook(int $winner, int $holder): void {
    if(function_exists('InitiativeAssigned')) {
        InitiativeAssigned($winner, $winner, $holder);
        return;
    }
    HellbreakOnInitiativeAssigned($winner, $winner, $holder);
}

function HellbreakOnRefreshReady($player, $round): string {
    HellbreakDispatchMacroEvent('RefreshReady', intval($player), ['round' => intval($round)]);
    return 'REFRESH_READY';
}

function HellbreakRefreshReadyHook(): void {
    $round = max(1, intval(GetTurnNumber()));
    foreach([1, 2] as $player) {
        if(function_exists('RefreshReady')) RefreshReady($player, $round);
        else HellbreakOnRefreshReady($player, $round);
    }
}

function HellbreakOnRoundEnded($player, $round): string {
    HellbreakDispatchMacroEvent('RoundEnded', intval($player), ['round' => intval($round)]);
    return 'ROUND_ENDED';
}

function HellbreakRoundEndedHook(int $round): void {
    // Round and phase effects both go stale here; the sweep drops whatever its window has closed on.
    if(function_exists('HellbreakExpireTurnEffects')) HellbreakExpireTurnEffects();
    foreach([1, 2] as $player) {
        if(function_exists('RoundEnded')) RoundEnded($player, $round);
        else HellbreakOnRoundEnded($player, $round);
    }
}

// ---------------------------------------------------------------------------
// Attachments — "Attach to a character / minion / location" (rulebook p.5, Assets)
//
// An attached asset STAYS in its owner's Assets zone; the link is its AttachedTo field, holding the
// host's UniqueID (0 = unattached, so the assets that never attach are untouched). The rulebook puts
// assets in the play area and says one attached to your monster "should be played with your other
// assets", so there is no separate zone to move it to.
//
// Pool vocabulary, straight from the glossary: a "character" is any MINION or MONSTER. "Allied" and
// "enemy" are defined terms the set uses when it means them, so a pool with NEITHER qualifier spans
// BOTH seats — restrict a pool only where the card prints a restriction. All ten DOT attach cards
// are unqualified; they differ only in pool (character ×8, minion ×1, location ×1).
// ---------------------------------------------------------------------------

function HellbreakAttachTargets(int $viewer, string $pool = 'CHARACTER'): array {
    $pool = strtoupper(trim($pool));
    $targets = [];
    if($pool === 'LOCATION') {
        foreach(HellbreakLiveZoneObjects(GetLocations()) as $index => $location) {
            $targets[] = 'Locations-' . $index;
        }
        return $targets;
    }
    foreach([1, 2] as $zonePlayer) {
        $prefix = $zonePlayer === $viewer ? 'my' : 'their';
        foreach(HellbreakLiveZoneObjects(GetCharacters($zonePlayer)) as $index => $character) {
            $targets[] = $prefix . 'Characters-' . $index;
        }
        // A monster is a character but not a minion, so it joins only the wider pool.
        if($pool === 'CHARACTER') {
            foreach(HellbreakLiveZoneObjects(GetMonster($zonePlayer)) as $index => $monster) {
                $targets[] = $prefix . 'Monster-' . $index;
            }
        }
    }
    return $targets;
}

function HellbreakFindAssetByUniqueID(int $uniqueID) {
    if($uniqueID <= 0) return null;
    foreach([1, 2] as $owner) {
        foreach(HellbreakLiveZoneObjects(GetAssets($owner)) as $asset) {
            if(intval($asset->UniqueID ?? 0) === $uniqueID) return $asset;
        }
    }
    return null;
}

function HellbreakAttachHostUniqueID(int $viewer, string $hostMZ): int {
    $hostMZ = trim($hostMZ);
    if(preg_match('/^Locations-(\d+)$/', $hostMZ, $matches)) {
        $live = HellbreakLiveZoneObjects(GetLocations());
        $index = intval($matches[1]);
        return isset($live[$index]) ? intval($live[$index]->UniqueID ?? 0) : 0;
    }
    $ref = HellbreakBattlefieldRef($viewer, $hostMZ);
    return $ref === null ? 0 : intval($ref['uniqueID'] ?? 0);
}

// Queue the host choice. Called from a card's Played macro; the asset is addressed by UniqueID
// rather than mzID because the answer arrives in a later request, where an index may have shifted.
function HellbreakBeginAttach(int $player, string $assetMZ, string $pool = 'CHARACTER'): bool {
    $asset = GetZoneObject($assetMZ);
    if(!is_object($asset) || intval($asset->UniqueID ?? 0) <= 0) return false;
    $targets = HellbreakAttachTargets($player, $pool);
    // Unreachable for the CHARACTER pool — both players always have a monster in play, and a monster
    // leaving play ends the game — but a narrower pool can legitimately be empty.
    if(count($targets) === 0) return false;
    DecisionQueueController::StoreVariable('HellbreakPendingAttachP' . $player, [
        'assetUniqueID' => intval($asset->UniqueID),
        'pool' => strtoupper(trim($pool)),
    ]);
    DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1, 'Choose_a_card_to_attach_to');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'HellbreakResolveAttach', 1);
    return true;
}

function HellbreakResolveAttach(int $player, string $hostMZ): bool {
    $pending = DecisionQueueController::GetVariable('HellbreakPendingAttachP' . $player);
    if(!is_array($pending)) return false;
    DecisionQueueController::StoreVariable('HellbreakPendingAttachP' . $player, null);
    $asset = HellbreakFindAssetByUniqueID(intval($pending['assetUniqueID'] ?? 0));
    if(!is_object($asset)) return false;
    $hostUniqueID = HellbreakAttachHostUniqueID($player, $hostMZ);
    if($hostUniqueID <= 0) return false;
    $asset->AttachedTo = $hostUniqueID;
    return true;
}

$customDQHandlers['HellbreakResolveAttach'] = function($player, $params, $lastDecision) {
    HellbreakResolveAttach(intval($player), strval($lastDecision));
};

// THE predicate every attach card's value modifiers gate on: is $subjectObj the host of $assetObj?
function HellbreakAssetIsAttachedTo($assetObj, $subjectObj): bool {
    if(!is_object($assetObj) || !is_object($subjectObj)) return false;
    $host = intval($assetObj->AttachedTo ?? 0);
    if($host <= 0) return false;
    return $host === intval($subjectObj->UniqueID ?? 0);
}

function HellbreakAttachedHostObject($assetObj) {
    if(!is_object($assetObj)) return null;
    $host = intval($assetObj->AttachedTo ?? 0);
    if($host <= 0) return null;
    foreach([1, 2] as $zonePlayer) {
        foreach([GetMonster($zonePlayer), GetCharacters($zonePlayer)] as $zone) {
            foreach(HellbreakLiveZoneObjects($zone) as $obj) {
                if(intval($obj->UniqueID ?? 0) === $host) return $obj;
            }
        }
    }
    foreach(HellbreakLiveZoneObjects(GetLocations()) as $obj) {
        if(intval($obj->UniqueID ?? 0) === $host) return $obj;
    }
    return null;
}

// USER RULING 2026-09-16: the rulebook does not say what becomes of an attachment when its host is
// killed, so we follow p.5's "attached cards are at the location of the attached card" — with no
// host it has no location — and send it to its owner's crypt.
function HellbreakDetachAssetsFromHost($hostObj): int {
    if(!is_object($hostObj)) return 0;
    $hostUniqueID = intval($hostObj->UniqueID ?? 0);
    if($hostUniqueID <= 0) return 0;
    $detached = 0;
    foreach([1, 2] as $owner) {
        $assets = &GetAssets($owner);
        // Collect first: splicing inside the loop shifts every later index underneath us, so a host
        // carrying two attachments would strand the second one.
        $doomed = [];
        foreach($assets as $asset) {
            if(!is_object($asset) || (isset($asset->removed) && $asset->removed)) continue;
            if(intval($asset->AttachedTo ?? 0) === $hostUniqueID) $doomed[] = $asset;
        }
        foreach($doomed as $asset) {
            $assetOwner = intval($asset->Owner ?? 0) ?: $owner;
            foreach($assets as $index => $candidate) {
                if($candidate !== $asset) continue;
                array_splice($assets, $index, 1);
                break;
            }
            HellbreakReindexZone($assets);
            AddCrypt($assetOwner, $asset->CardID, 'Assets', intval(GetTurnNumber()), true, $asset);
            ++$detached;
        }
        unset($assets);
    }
    return $detached;
}

/**
 * Discard the top card of a deck to its owner's crypt, and report what it was.
 *
 * "Discard 1 card from a deck" is a shared shape across the set (Marishka, the Death Angel Action,
 * Oxygen Tank, Bloodhound, Tree of the Dead, Daredevil, Amplify Feedback), so the move lives here
 * and each card decides what to do with the discarded CardID.
 *
 * Returns '' when the deck is empty — the caller decides whether that matters. Note a discard is NOT
 * a failed draw: no monster damage is dealt.
 */
function HellbreakDiscardTopOfDeck(int $player): string
{
    $deck = &GetDeck($player);
    HellbreakReindexZone($deck);
    if(count($deck) === 0) return '';
    $card = array_shift($deck);
    HellbreakReindexZone($deck);
    $cardID = strval($card->CardID ?? '');
    AddCrypt($player, $cardID, 'Deck', intval(GetTurnNumber()), true, $card);
    return $cardID;
}

/**
 * Add a card's printed resource icons to a player's pools.
 *
 * "Collect its resource icons" means the blood and malice on the card's resource bar; the draw icons
 * draw cards. The aspect icons are a vault-only concept and are not collected here.
 */
function HellbreakCollectCardResourceIcons(int $player, string $cardID): array
{
    $resources = HellbreakCardResources($cardID);
    $blood = max(0, intval($resources['blood'] ?? 0));
    $malice = max(0, intval($resources['malice'] ?? 0));
    $draw = max(0, intval($resources['draw'] ?? 0));
    if($blood > 0) HellbreakGainBlood($player, $blood);
    if($malice > 0) HellbreakGainMalice($player, $malice);
    if($draw > 0) HellbreakDrawCards($player, $draw);
    return ['blood' => $blood, 'malice' => $malice, 'draw' => $draw];
}

function HellbreakActiveMacroSourceObjects(): array {
    $sources = [];
    foreach([1, 2] as $owner) {
        foreach([GetMonster($owner), GetCharacters($owner), GetAssets($owner)] as $zone) {
            foreach($zone as $obj) {
                if(!is_object($obj) || (isset($obj->removed) && $obj->removed) || empty($obj->CardID)) continue;
                $sources[] = $obj;
            }
        }
    }
    foreach(GetLocations() as $obj) {
        if(!is_object($obj) || (isset($obj->removed) && $obj->removed) || empty($obj->CardID)) continue;
        $sources[] = $obj;
    }
    return $sources;
}

function HellbreakApplyValueModifiers(string $macroName, int $player, $subjectObj, int $currentValue, array $extraArgs = [], bool $includeSubject = true): int {
    $evaluate = 'Evaluate' . $macroName;
    if(!function_exists($evaluate)) return max(0, $currentValue);

    $sources = HellbreakActiveMacroSourceObjects();
    if($includeSubject && is_object($subjectObj) && !empty($subjectObj->CardID)) array_unshift($sources, $subjectObj);
    $seen = [];
    foreach($sources as $sourceObj) {
        $identity = !empty($sourceObj->UniqueID)
            ? 'U:' . intval($sourceObj->UniqueID)
            : 'O:' . spl_object_id($sourceObj);
        if(isset($seen[$identity])) continue;
        $seen[$identity] = true;
        $cardID = strval($sourceObj->CardID ?? '');
        if($cardID === '') continue;
        $args = array_merge([$cardID, $player, $subjectObj], $extraArgs, [$currentValue, $sourceObj]);
        $currentValue += intval(call_user_func_array($evaluate, $args));
    }
    return max(0, $currentValue);
}

function ParseModifierResult($result): array {
    $parsed = ['delta' => 0, 'consume' => false, 'applied' => false];
    if(is_array($result)) {
        $parsed['delta'] = intval($result['delta'] ?? 0);
        $parsed['consume'] = !empty($result['consume']);
        $parsed['applied'] = array_key_exists('applied', $result)
            ? !empty($result['applied'])
            : $parsed['delta'] !== 0;
        return $parsed;
    }
    $parsed['delta'] = intval($result);
    $parsed['applied'] = $parsed['delta'] !== 0;
    return $parsed;
}

function ConsumeModifierSource($sourceObj): bool {
    return false;
}

?>
