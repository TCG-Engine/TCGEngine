<?php
/** Shared dispatch only. Named card effects belong in CardCode/DeckAbilities.php. */
function PokeMacro(string $macro, int $player, string $id, int $index = 0, array $params = []): bool {
    foreach ($params as $key => $value) DecisionQueueController::StoreVariable($key, $value);
    $key = $id . ':' . $index;
    $abilities = $GLOBALS[lcfirst($macro) . 'Abilities'] ?? [];
    $prereqs = $GLOBALS[lcfirst($macro) . 'Prereqs'] ?? [];
    if (!isset($abilities[$key])) return false;
    // Trainer legality was checked while it was in hand, before costs changed it.
    if ($macro !== 'TrainerPlayed' && isset($prereqs[$key]) && !$prereqs[$key]($player, ...array_values($params))) return false;
    $abilities[$key]($player);
    return true;
}

function PokeOnTrainerPlayed($player, $mzID): string {
    PokeMacro('TrainerPlayed', $player, GetZoneObject($mzID)->CardID, 0, ['mzID' => $mzID]);
    return 'PLAYED';
}
function PokeOnAttack($player, $mzID, $attackIndex): string {
    $obj = GetZoneObject($mzID);
    $copy = PokeVar('copiedAttack'); PokeSetVar('copiedAttack', null);
    $attackID = $copy ?? $obj->CardID;
    if (!PokeMacro('Attack', $player, $attackID, $attackIndex, ['mzID' => $mzID, 'attackIndex' => $attackIndex])) {
        $attack = CardAttacks($attackID)[$attackIndex];
        PokeDealAttackDamage($player, $mzID, (int)($attack['damage'] ?? 0));
    }
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'PokeFinishAttack', 90, '', 1);
    return 'ATTACKED';
}
function PokeOnPokemonBenched($player, $mzID): string {
    PokeMacro('PokemonBenched', $player, GetZoneObject($mzID)->CardID, 0, ['mzID'=>$mzID]);
    return 'BENCHED';
}
function PokeOnActivateAbility($player, $mzID, $abilityIndex): string {
    PokeMacro('ActivateAbility', $player, GetZoneObject($mzID)->CardID, $abilityIndex, ['mzID' => $mzID, 'abilityIndex' => $abilityIndex]);
    return 'ACTIVATED';
}
function PokeOnEnergyAttached($player, $mzID, $energyID): string {
    PokeMacro('EnergyAttached', $player, $energyID, 0, ['mzID' => $mzID, 'energyID' => $energyID]);
    return 'ATTACHED';
}
function PokeCardImplemented(string $id): bool {
    if (CardType($id) === 'Energy') return (CardEnergyType($id) === 'Normal' && empty(CardEffect($id))) || isset($GLOBALS['energyAttachedAbilities'][$id . ':0']);
    if (CardType($id) === 'Trainer') return isset($GLOBALS['trainerPlayedAbilities'][$id . ':0']);
    if (CardType($id) !== 'Pokemon') return false;
    foreach (CardAttacks($id) ?? [] as $i => $attack) {
        if (isset($GLOBALS['attackAbilities'][$id . ':' . $i])) continue;
        if (!empty($attack['effect']) || !preg_match('/^\d+$/D', (string)($attack['damage'] ?? ''))) return false;
    }
    foreach (CardAbilities($id) ?? [] as $i => $ability) {
        if (($ability['name'] ?? '') === "Hide 'n' Sneak" && isset($GLOBALS['attackEffectProtectionAbilities'][$id . ':0'])) continue;
        foreach (['activateAbility','pokemonBenched','benchDamageProtection','selfKnockoutAbilityLock','dragonPsychicWeakness','attackCopyAllowed'] as $macro) {
            if (isset($GLOBALS[$macro.'Abilities'][$id.':'.$i])) continue 2;
        }
        return false;
    }
    return true;
}
function PokeHasRuleBox(string $id): bool {
    return !empty(CardSuffix($id)) || str_ends_with(CardName($id) ?? '', ' ex') || in_array(CardStage($id), ['VMAX', 'VSTAR', 'V-UNION'], true) || str_starts_with(CardName($id) ?? '', 'Radiant ');
}
function EffectiveCardType($obj): string { return !is_string($obj) && in_array($obj->Location,['Active','Bench'],true) && PokeIsFossil($obj->CardID) ? 'Pokemon' : (CardType(is_string($obj) ? $obj : $obj->CardID) ?? ''); }
function EffectiveCardSubtypes($obj): array { return !is_string($obj) && in_array($obj->Location,['Active','Bench'],true) && PokeIsFossil($obj->CardID) ? ['Basic'] : array_filter([CardStage(is_string($obj) ? $obj : $obj->CardID), CardTrainerType(is_string($obj) ? $obj : $obj->CardID)]); }
function EffectiveCardClasses($obj): array { return []; }
function EffectiveCardElement($obj): string { return !is_string($obj) && in_array($obj->Location,['Active','Bench'],true) && PokeIsFossil($obj->CardID) ? 'Colorless' : (CardTypes(is_string($obj) ? $obj : $obj->CardID) ?? ''); }
function HasNoAbilities($obj): bool { return !empty($obj->Counters['noAbilities']); }
function ParseModifierResult($result): array { return ['delta' => is_array($result) ? (int)($result['delta'] ?? 0) : (int)$result, 'consume' => false, 'applied' => true]; }
function ConsumeModifierSource($source): void {}

/** Candidate search used by authored macros; deck search is allowed to fail. */
function PokeCandidates(int $player, string $zone, string $filter = 'any'): string {
    $ids = [];
    foreach (PokeObjects($player, $zone) as $i => $obj) {
        $id = $obj->CardID; $match = match ($filter) {
            'pokemon' => EffectiveCardType($obj) === 'Pokemon',
            'nonRuleBox' => EffectiveCardType($obj) === 'Pokemon' && !PokeHasRuleBox($id),
            'nonRuleBoxOrBasicEnergy' => (EffectiveCardType($obj) === 'Pokemon' && !PokeHasRuleBox($id))
                || (EffectiveCardType($obj) === 'Energy' && CardEnergyType($id) === 'Normal'),
            'trainer' => EffectiveCardType($obj) === 'Trainer',
            'supporter' => CardTrainerType($id) === 'Supporter',
            'rocketSupporter' => CardTrainerType($id) === 'Supporter' && str_contains(CardName($id), 'Team Rocket'),
            'basicEnergy' => EffectiveCardType($obj) === 'Energy' && CardEnergyType($id) === 'Normal',
            'pokemonOrEnergy' => EffectiveCardType($obj) === 'Pokemon' || (EffectiveCardType($obj) === 'Energy' && CardEnergyType($id) === 'Normal'),
            'basicPsychic' => EffectiveCardType($obj) === 'Pokemon' && CardStage($id) === 'Basic' && in_array('Psychic', explode(',', EffectiveCardElement($obj)), true),
            'fanCall' => EffectiveCardType($obj) === 'Pokemon' && str_contains(EffectiveCardElement($obj), 'Colorless') && (int)CardHp($id) <= 100,
            'poffin' => EffectiveCardType($obj) === 'Pokemon' && CardStage($id) === 'Basic' && (int)CardHp($id) <= 70,
            'evolution' => EffectiveCardType($obj) === 'Pokemon' && CardStage($id) !== 'Basic',
            'energy' => EffectiveCardType($obj) === 'Energy',
            'stadium' => CardTrainerType($id) === 'Stadium',
            'item' => CardTrainerType($id) === 'Item',
            'tool' => CardTrainerType($id) === 'Tool',
            'antiqueItem' => EffectiveCardType($obj) === 'Trainer' && CardTrainerType($id) === 'Item' && str_contains(CardName($id)??'', 'Antique'),
            default => true,
        };
        if ($match) $ids[] = PokeRef($player, $zone, $i);
    }
    return implode('&', $ids);
}
function PokeResolveSearch(int $player, string $selected, string $destination = 'Hand', bool $reveal = true, bool $shuffle = true): void {
    foreach (explode('&', $selected) as $ref) {
        if ($ref === '' || $ref === '-' || $ref === 'PASS') continue;
        $obj = GetZoneObject($ref);
        if ($obj === null || $obj->Removed()) continue;
        if ($destination === 'Bench') PokePlayBasicFromZone($player, $ref, 'Bench');
        else { if ($reveal) PokeLog('reveal-search', ['player'=>$player, 'card'=>$obj->CardID]); PokeMoveSimple($ref, $player, $destination); }
    }
    if ($shuffle) PokeShuffle($player);
}
function PokeResolveRecovery(int $player, string $selected): void {
    foreach (explode('&', $selected) as $ref) {
        if ($ref === '' || $ref === '-' || $ref === 'PASS') continue;
        if (str_contains($ref, 'TempZone')) PokeLog('reveal-search', ['player'=>$player, 'card'=>GetZoneObject($ref)->CardID]);
        PokeMoveSimple($ref, $player, 'Hand');
    }
}
function PokeDiscardSelection(int $player, string $selected): void {
    foreach (explode('&', $selected) as $ref) if ($ref !== '-' && $ref !== '' && $ref !== 'PASS') PokeMoveSimple($ref, $player, 'Discard');
}
function PokeCountHideSneak(int $player): int {
    $count = 0;
    foreach (PokeObjects($player, 'Discard') as $obj) {
        foreach (CardAbilities($obj->CardID) ?? [] as $ability) if (($ability['name'] ?? '') === "Hide 'n' Sneak") { ++$count; break; }
    }
    return $count;
}
function PokeRevealTop(int $player, int $number): void {
    for ($i = 0; $i < $number && PokeCount($player, 'Deck') > 0; ++$i) {
        $ref = PokeFirstRef($player, 'Deck'); PokeMoveSimple($ref, $player, 'TempZone');
    }
}
function PokeReturnTemp(int $player): void {
    foreach (PokeObjects($player, 'TempZone') as $i => $obj) PokeMoveSimple(PokeRef($player, 'TempZone', $i), $player, 'Deck');
    PokeShuffle($player);
}
/** Looked-at cards stay private; only the unchosen cards enter public discard. */
function PokeResolveExplorersGuidance(int $player, string $chosen): void {
    foreach (explode('&', $chosen) as $ref) {
        if (str_starts_with($ref, "p{$player}TempZone-")) PokeMoveSimple($ref, $player, 'Hand');
    }
    foreach (PokeObjects($player, 'TempZone') as $i => $obj) PokeMoveSimple(PokeRef($player, 'TempZone', $i), $player, 'Discard');
}
function PokeShuffleHandDraw(int $player, int $number, bool $bottom = false): void {
    $hand = [];
    foreach (PokeObjects($player, 'Hand') as $i => $obj) { $hand[] = $obj->CardID; $obj->Remove(); }
    if ($bottom) PokeShuffleArray($hand);
    foreach ($hand as $id) PokeAdd($player, 'Deck', $id);
    if (!$bottom) PokeShuffle($player);
    if (!$bottom || $hand) PokeDraw($player, $number, false);
}
function PokeBlackBelt(int $player): void { PokeSetVar('blackBelt:' . $player, GetTurnNumber()); }
