<?php
/** Darkness package; setup.php saves this source through CardAbilityRepository. */
$cards=[];
$ability=static fn($macro,$name,$code,$prereq='')=>['macroName'=>$macro,'abilityName'=>$name,'abilityCode'=>$code,'prereqCode'=>$prereq,'isImplemented'=>true];
$cards['30th-092'][]=$ability('Attack','Lunatic Claw', <<<'CODE'
$target = PokeFirstRef(3 - $player, 'Active');
PokeDealAttackDamage($player, $mzID, $target !== '' && GetZoneObject($target)->Damage > 0 ? 240 : 100);
CODE);
$cards['me01-115'][]=$ability('TrainerPlayed','Energy Switch', <<<'CODE'
$targets = PokeBasicEnergySources($player);
$from = await $player.MZChoose($targets, 'Energy_Switch:_choose_the_Pokémon_to_move_Energy_from');
$preview = PokePreviewBasicEnergy($player, $from);
$targets = implode('&', array_keys($preview));
$chosen = await $player.MZChoose($targets, 'Energy_Switch:_choose_a_Basic_Energy');
$targets = implode('&', array_filter(PokeFieldRefs($player), fn($ref) => $ref !== $from));
$to = await $player.MZChoose($targets, 'Energy_Switch:_choose_a_different_Pokémon');
PokeMoveAttachedBasicEnergy($player, $from, $preview[$chosen], $to);
PokeClearEnergyPreview($preview);
CODE, 'return count(PokeFieldRefs($player)) > 1 && PokeBasicEnergySources($player) !== "";');
$cards['sv06-135'][]=$ability('Attack','Ascension', <<<'CODE'
$targets = PokeEvolutionSearchCandidates($player, $mzID);
$chosen = await $player.MZMayChoose($targets, 'Ascension:_search_for_an_evolution_of_this_Pokémon');
if ($chosen !== '' && $chosen !== '-' && $chosen !== 'PASS') PokeEvolve($player, $chosen, $mzID);
PokeShuffle($player);
CODE);
$cards['sv06-135'][]=$ability('Attack','Quick Attack', <<<'CODE'
$heads = PokeRandom(1) === 1;
PokeLog('coin-flip', ['player'=>$player, 'heads'=>$heads]);
PokeDealAttackDamage($player, $mzID, $heads ? 40 : 20);
CODE);
$cards['me02-067'][]=$ability('Attack','Call for Family', <<<'CODE'
$targets = PokeCandidates($player, 'Deck', 'basicPokemon');
$maximum = min(2, 5 - PokeCount($player, 'Bench'));
if ($maximum > 0) {
    $chosen = await $player.MZMultiChoose($targets, 0, $maximum, 'Call_for_Family:_Bench_up_to_two_Basic_Pokémon');
    PokeResolveSearch($player, $chosen, 'Bench');
} else {
    PokeShuffle($player);
}
CODE);
$cards['me02-067'][]=$ability('Attack','Playful Kick','PokeDealAttackDamage($player, $mzID, 20);');
$cards['me02-068'][]=$ability('ActivateAbility','Sinister Surge', <<<'CODE'
PokeMarkAbilityUsed($mzID);
$targets = PokeCandidates($player, 'Deck', 'basicDarkness');
$chosen = await $player.MZMayChoose($targets, 'Sinister_Surge:_search_for_a_Basic_Darkness_Energy');
if ($chosen !== '' && $chosen !== '-' && $chosen !== 'PASS') {
    $targets = PokeDarknessBenchTargets($player);
    $target = await $player.MZChoose($targets, 'Sinister_Surge:_choose_a_Benched_Darkness_Pokémon');
    PokeLog('reveal-search', ['player'=>$player, 'card'=>GetZoneObject($chosen)->CardID]);
    PokeAttachEnergyFromZone($player, $chosen, $target);
    PokeShuffle($player);
    PokePlaceDamageCounters($mzID, $target, 2, 'Ability');
    PokeResolveKnockouts();
} else {
    PokeShuffle($player);
}
CODE, 'return PokeAbilityUnused($mzID) && PokeCount($player, "Deck") > 0 && PokeDarknessBenchTargets($player) !== "";');
$cards['me02-068'][]=$ability('Attack','Gentle Slap','PokeDealAttackDamage($player, $mzID, 100);');
$cards['me05-101'][]=$ability('Attack','Dusk Raid', 'PokeDealAttackDamage($player, $mzID, count(array_filter(PokeObjects($player, "Bench"), fn($obj) => $obj->Damage > 0)) > 0 ? 220 : 110);');
$cards['me05-101'][]=$ability('Attack','Abyss Eye', <<<'CODE'
$target = PokeFirstRef(3 - $player, 'Active');
if ($target !== '' && !empty(GetZoneObject($target)->Conditions) && !PokeEffectProtected(GetZoneObject($target), GetZoneObject($mzID))) {
    GetZoneObject($target)->Damage = max(GetZoneObject($target)->Damage, PokeCurrentHP(GetZoneObject($target)));
    GetZoneObject($target)->Counters['opposingAttackKO'] = false;
}
CODE);
return $cards;
