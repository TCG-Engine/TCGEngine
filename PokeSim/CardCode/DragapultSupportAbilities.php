<?php
/** Additional Dragapult support cards; setup saves through CardAbilityRepository. */
$cards=[];
$ability=static fn($macro,$name,$code,$prereq='')=>['macroName'=>$macro,'abilityName'=>$name,'abilityCode'=>$code,'prereqCode'=>$prereq,'isImplemented'=>true];
$cards['me02.5-099'][]=$ability('ActivateAbility','Adrena-Brain', <<<'CODE'
PokeMarkAbilityUsed($mzID);
$targets = implode('&', array_filter(PokeFieldRefs($player), fn($ref) => GetZoneObject($ref)->Damage >= 10));
$from = await $player.MZChoose($targets, 'Adrena-Brain:_choose_your_damaged_Pokémon');
$maximum = min(3, intdiv(GetZoneObject($from)->Damage, 10));
$count = await $player.NumberChoose(0, $maximum, 'Adrena-Brain:_how_many_damage_counters');
if ((int)$count > 0) {
    $targets = implode('&', PokeFieldRefs(3 - $player));
    $to = await $player.MZChoose($targets, 'Adrena-Brain:_choose_an_opposing_Pokémon');
    GetZoneObject($from)->Damage -= (int)$count * 10;
    PokePlaceDamageCounters($mzID, $to, (int)$count, 'Ability');
    PokeResolveKnockouts();
}
CODE, 'return PokeAbilityUnused($mzID) && PokeHasAttackEnergy(GetZoneObject($mzID), ["Darkness"]) && count(array_filter(PokeFieldRefs($player), fn($ref) => GetZoneObject($ref)->Damage >= 10)) > 0;');
$cards['me02.5-099'][]=$ability('Attack','Mind Bend', 'PokeDealAttackDamage($player, $mzID, 60); PokeSetCondition($mzID, PokeFirstRef(3 - $player, "Active"), "Confused");');
$cards['sv05-128'][]=$ability('Attack','Gnaw','PokeDealAttackDamage($player, $mzID, 10);');
$cards['sv05-128'][]=$ability('Attack','Dig', <<<'CODE'
PokeDealAttackDamage($player, $mzID, 30);
$heads = PokeRandom(1) === 1;
PokeLog('coin-flip', ['player'=>$player, 'heads'=>$heads]);
if ($heads) GetZoneObject($mzID)->TurnEffects['digThrough'] = GetTurnNumber() + 1;
CODE);
$cards['sv07-133'][]=$ability('TrainerPlayed','Crispin', <<<'CODE'
$targets = PokeCandidates($player, 'Deck', 'basicEnergy');
$first = await $player.MZMayChoose($targets, 'Crispin:_choose_the_first_Basic_Energy');
if ($first !== '-' && $first !== '' && $first !== 'PASS') {
    $type = PokeEnergyTypes(GetZoneObject($first)->CardID);
    PokeResolveSearch($player, $first, 'TempZone', true, false);
    $targets = PokeDifferentBasicEnergyCandidates($player, $type);
    if ($targets !== '') {
        $second = await $player.MZMayChoose($targets, 'Crispin:_choose_a_different_type_of_Basic_Energy');
        PokeResolveSearch($player, $second, 'TempZone', true, false);
    }
    $targets = PokeCandidates($player, 'TempZone');
    $hand = await $player.MZChoose($targets, 'Crispin:_choose_the_Energy_to_put_in_hand');
    PokeMoveSimple($hand, $player, 'Hand');
    $energy = PokeFirstRef($player, 'TempZone');
    if ($energy !== '') {
        $targets = implode('&', PokeFieldRefs($player));
        $target = await $player.MZChoose($targets, 'Crispin:_attach_the_other_Energy');
        PokeAttachEnergyFromZone($player, $energy, $target);
    }
}
PokeShuffle($player);
CODE, 'return PokeCount($player, "Deck") > 0;');
$cards['me03-084'][]=$ability('TrainerPlayed',"Rosa's Encouragement", <<<'CODE'
$targets = PokeCandidates($player, 'Discard', 'basicEnergy');
$chosen = await $player.MZMultiChoose($targets, 0, 2, 'Rosas_Encouragement:_choose_up_to_two_Basic_Energy');
if ($chosen !== '-' && $chosen !== '' && $chosen !== 'PASS') {
    $targets = PokeStageTwoTargets($player);
    $target = await $player.MZChoose($targets, 'Rosas_Encouragement:_choose_one_Stage_2_Pokémon');
    PokeAttachEnergySelection($player, $chosen, $target);
}
CODE, 'return PokeCount($player, "Prizes") > PokeCount(3 - $player, "Prizes") && PokeStageTwoTargets($player) !== "" && PokeCandidates($player, "Discard", "basicEnergy") !== "";');
$cards['me03-071'][]=$ability('TrainerPlayed','Crushing Hammer', <<<'CODE'
$heads = PokeRandom(1) === 1;
PokeLog('coin-flip', ['player'=>$player, 'heads'=>$heads]);
if ($heads) {
    $targets = PokeEnergyBearingTargets(3 - $player);
    $target = await $player.MZChoose($targets, 'Crushing_Hammer:_choose_an_opposing_Pokémon');
    $maximum = count(GetZoneObject($target)->Energy) - 1;
    $index = await $player.NumberChoose(0, $maximum, 'Crushing_Hammer:_choose_the_attached_Energy_index');
    PokeDiscardAttachedEnergy($target, (int)$index);
}
CODE, 'return PokeEnergyBearingTargets(3 - $player) !== "";');
$cards['me01-127'][]=$ability('TrainerPlayed','Risky Ruins','PokePlayStadium($player, $mzID);');
$cards['me01-127'][]=array_merge($ability('PokemonBenched','Risky Ruins', <<<'CODE'
$target = GetZoneObject($event_mzID);
if (GetCurrentPhase() === 'MAIN' && $eventPlayer === GetTurnPlayer() && $target && !$target->Removed()
    && $target->Location === 'Bench' && in_array('Basic', EffectiveCardSubtypes($target), true)
    && !in_array('Darkness', explode(',', EffectiveCardElement($target)), true)) {
    PokePlaceDamageCounters($listenerMZ, $event_mzID, 2, 'Trainer');
    PokeResolveKnockouts();
}
CODE), ['abilityType'=>'listener','listenerZones'=>['Stadium']]);
return $cards;
