<?php
function PokeEffectProtected($target, $source, string $kind = 'Attack'): bool {
    if (!in_array($kind, ['Attack', 'Ability'], true)) return false;
    if ($target->Controller === $source->Controller) return false;
    $evaluate = $kind === 'Ability' ? 'EvaluateAbilityEffectProtection' : 'EvaluateAttackEffectProtection';
    if (!HasNoAbilities($target) && $evaluate($target->CardID, $target->Controller, $target, $source)>0) return true;
    foreach ($target->Energy as $id) if ($evaluate($id,$target->Controller,$target,$source)>0) return true;
    return false;
}
/** Damage counters are effects; they bypass weakness/resistance and damage boosts. */
function PokePlaceDamageCounters(string $sourceRef, string $targetRef, int $count, string $kind = 'Attack'): void {
    $source = GetZoneObject($sourceRef); $target = GetZoneObject($targetRef);
    if (!$source || !$target || $target->Removed() || PokeEffectProtected($target, $source, $kind)) return;
    $stadium=PokeStadium();
    if ($source->Controller !== $target->Controller && $stadium && EvaluateDamageCounterProtection($stadium->CardID,$target->Controller,$target,$source)>0) return;
    if($count>0)$target->Counters['opposingAttackKO']=false;
    $target->Damage += max(0, $count) * 10;
    PokeRecordDamage($source->Controller, $target->Controller, max(0, $count) * 10);
}
function PokeDealAttackDamage(int $player, string $sourceRef, int $damage, bool $ignoreEffects=false, ?string $targetRef=null): void {
    $source = GetZoneObject($sourceRef); $targetRef ??= PokeFirstRef(3 - $player, 'Active');
    if (!$source || $targetRef === '') return; $target = GetZoneObject($targetRef);
    if ($damage <= 0) return; // Damage boosts do not turn a non-damaging attack into damage.
    if (!$ignoreEffects && PokeFieldModifier('BenchDamageProtection',3-$player,$target,$source)>0) return;
    $damage += EvaluateAttackDamageModifier($source->CardID, $player, $source, $damage, $source);
    if($source->Tool!=='-')$damage+=EvaluateAttackDamageModifier($source->Tool,$player,$target,$damage,$source);
    if (PokeVar('blackBelt:' . $player) === GetTurnNumber() && in_array(CardSuffix($target->CardID), ['ex'], true)) $damage += 40;
    if (!$ignoreEffects && !HasNoAbilities($target)) $damage += EvaluateIncomingAttackDamageModifier($target->CardID, $target->Controller, $target, $source);
    $damage = max(0, $damage);
    $types = explode(',', EffectiveCardElement($source));
    $weaknesses=$target->Location==='Active'?(CardWeaknesses($target->CardID)??[]):[];
    if ($target->Location==='Active' && PokeFieldModifier('DragonPsychicWeakness',$player,$target,$source)>0) $weaknesses=[['type'=>'Psychic','value'=>'×2']];
    foreach ($weaknesses as $weakness) {
        if (!in_array($weakness['type'], $types, true)) continue;
        $value = $weakness['value'] ?? '×2';
        $damage = str_contains($value, '×') || str_contains($value, 'x') ? $damage * (int)preg_replace('/\D/', '', $value) : $damage + (int)$value;
    }
    if ($target->Location==='Active') foreach (CardResistances($target->CardID) ?? [] as $resistance) if (in_array($resistance['type'], $types, true)) $damage += (int)($resistance['value'] ?? 0);
    $armor=PokeFirstRef(3-$player,'Active');
    if(!$ignoreEffects&&$armor!==''&&GetZoneObject($armor)->CardID==='me05-072'&&!HasNoAbilities(GetZoneObject($armor)))$damage-=10;
    $target->Damage += max(0, $damage);
    if($damage>0){
        $target->Counters['opposingAttackKO']=$target->Controller!==$source->Controller && $target->Damage >= (int)CardHp($target->CardID);
        if($target->Tool!=='-'){
            $draw=EvaluateAttackDamageDraw($target->Tool,$target->Controller,$target,$source);
            if($draw>0)PokeDraw($target->Controller,$draw,false);
        }
        foreach ($target->Energy as $energy) {
            $counters = EvaluateAttackDamageCounterReflect($energy, $target->Controller, $target, $source);
            if ($counters > 0) PokePlaceDamageCounters($targetRef, $sourceRef, $counters, 'Energy');
        }
    }
    if(!$ignoreEffects&&$damage>0&&$target->Location==='Active'&&$target->CardID==='me05-073'&&!HasNoAbilities($target))PokePlaceDamageCounters($targetRef,$sourceRef,3,'Ability');
    PokeRecordDamage($player, $target->Controller, max(0, $damage));
    PokeLog('attack-damage', ['player'=>$player,'source'=>$source->CardID,'target'=>$target->CardID,'amount'=>max(0,$damage)]);
}
function PokeSetCondition(string $sourceRef, string $targetRef, string $condition, string $kind = 'Attack'): void {
    $source = GetZoneObject($sourceRef); $target = GetZoneObject($targetRef);
    if (!$source || !$target || $target->Location !== 'Active' || PokeIsFossil($target->CardID) || PokeEffectProtected($target, $source, $kind)) return;
    if (in_array($condition, ['Asleep','Paralyzed','Confused'], true)) foreach (['Asleep','Paralyzed','Confused'] as $other) unset($target->Conditions[$other]);
    $target->Conditions[$condition] = $condition === 'Poisoned' ? 10 : true;
}
function PokePrizeValue(string $id): int {
    if (CardStage($id) === 'VMAX' || str_starts_with(CardName($id) ?? '', 'Mega ') && PokeHasRuleBox($id)) return 3;
    return in_array(CardSuffix($id), ['ex','EX','GX','V','TAG TEAM-GX'], true) || str_ends_with(CardName($id)??'',' ex') ? (CardSuffix($id) === 'TAG TEAM-GX' ? 3 : 2) : 1;
}
/** Resolve every KO together before evaluating victory or choosing replacements. */
function PokeResolveKnockouts(): void {
    $claims = [1=>0,2=>0];
    foreach ([1,2] as $seat) foreach (PokeFieldRefs($seat) as $ref) {
        $obj = GetZoneObject($ref);
        if ($obj->Damage < (int)CardHp($obj->CardID)) continue;
        if (GetTurnPlayer() !== $seat && GetCurrentPhase() === 'MAIN') PokeSetVar('knockedOutOnOpponentTurn:'.$seat, GetTurnNumber());
        $prizes=PokePrizeValue($obj->CardID);
        foreach($obj->Energy as $energy)$prizes+=EvaluateKnockoutPrizeModifier($energy,$seat,$obj,$prizes);
        $claims[3-$seat] += max(0,$prizes);
        foreach (array_merge([$obj->CardID], $obj->Evolutions, $obj->Energy, $obj->Tool !== '-' ? [$obj->Tool] : []) as $id) PokeAdd($seat, 'Discard', $id);
        $obj->Remove(); PokeLog('knockout', ['player'=>$seat,'card'=>$obj->CardID]);
    }
    $winCounts = [1=>0,2=>0];
    foreach ([1,2] as $seat) {
        AddPrizeClaims($seat, GetPrizeClaims($seat) + $claims[$seat]);
        if (GetCurrentPhase() !== 'SETUP' && GetPrizeClaims($seat) > 0 && GetPrizeClaims($seat) >= PokeCount($seat,'Prizes')) ++$winCounts[$seat];
        if (!PokeFieldRefs(3-$seat)) ++$winCounts[$seat];
    }
    if ($winCounts[1] || $winCounts[2]) {
        if ($winCounts[1] === $winCounts[2]) {
            $decks = PokeVar('decks'); $seed = GetRandomState();
            PokeOpeningEndGame(); $openingStats = PokeVar('openingStats', []);
            PokeDamageFinishTurn(); $damageTurns = PokeVar('damageTurns', []); $statsFirst = PokeVar('statsFirstPlayer', GetFirstPlayer());
            PokeCreateGame($decks[0], $decks[1], $seed, 0, 1);
            PokeSetVar('openingStats', $openingStats); PokeSetVar('damageTurns', $damageTurns); PokeSetVar('statsFirstPlayer', $statsFirst); PokeLog('sudden-death'); return;
        }
        $winner = $winCounts[1] > $winCounts[2] ? 1 : 2;
        if (GetPrizeClaims($winner) >= PokeCount($winner,'Prizes')) foreach (PokeObjects($winner,'Prizes') as $i=>$obj) PokeMoveSimple(PokeRef($winner,'Prizes',$i),$winner,'Hand');
        PokeWin($winner, 'prizes-or-no-pokemon'); return;
    }
    foreach ([1,2] as $seat) {
        if (GetPrizeClaims($seat) > 0) {
            $refs = PokeCandidates($seat,'Prizes'); $count = min(GetPrizeClaims($seat),PokeCount($seat,'Prizes'));
            DecisionQueueController::AddDecision($seat,'MZMULTICHOOSE',"$count|$count|$refs",1,'Take_Prize_cards');
            DecisionQueueController::AddDecision($seat,'CUSTOM','PokeTakePrizes',1,'',1);
        }
        if (!PokeCount($seat,'Active') && PokeCount($seat,'Bench')) {
            $refs = PokeCandidates($seat,'Bench');
            DecisionQueueController::AddDecision($seat,'MZCHOOSE',$refs,1,'Choose_a_new_Active_Pokémon');
            DecisionQueueController::AddDecision($seat,'CUSTOM','PokePromote',1,'',1);
        }
    }
}
